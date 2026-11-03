<?php
if (php_sapi_name() !== 'cli') {
    exit;
}
define('BASEPATH', 'x');
define('ENVIRONMENT', 'development');
$db = array();
$active_group = null;
$query_builder = true;
require dirname(__DIR__) . '/application/config/database.php';
$cfg = $db['dbknet'];
$conn = sqlsrv_connect($cfg['hostname'], array('UID' => $cfg['username'], 'PWD' => $cfg['password'], 'CharacterSet' => 'UTF-8'));
if (!$conn) {
    fwrite(STDERR, "connect failed\n");
    exit(1);
}

$targets = array(
    'BigEKnet' => array('dbo.vw_expense_types', 'dbo.vw_ca', 'dbo.sp_fetch_approval_matrix_header', 'dbo.sp_fetch_approval_matrix_header_by_id', 'dbo.sp_fetch_ca_attachments_by_caid'),
    'BigEKFlow' => array('dbo.sp_insert_documents', 'dbo.sp_fetch_knet_unsynced_documents'),
);

$out = "SET NOCOUNT ON;\nGO\n\n";
$out .= "IF DB_ID('BigEERLP') IS NULL AND DB_ID('BigEKnet') IS NOT NULL\nBEGIN\n    ALTER DATABASE BigEKnet SET SINGLE_USER WITH ROLLBACK IMMEDIATE;\n    ALTER DATABASE BigEKnet MODIFY NAME = BigEERLP;\n    ALTER DATABASE BigEERLP SET MULTI_USER;\nEND\nGO\n\n";

foreach ($targets as $dbName => $objects) {
    $useDb = $dbName === 'BigEKnet' ? 'BigEERLP' : $dbName;
    foreach ($objects as $obj) {
        $stmt = sqlsrv_query($conn, "SELECT m.definition AS def FROM [$dbName].sys.sql_modules m WHERE m.object_id = OBJECT_ID(?)", array($dbName . '.' . $obj));
        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        $def = $row ? $row['def'] : null;
        if (!$def) {
            fwrite(STDERR, "missing definition: $dbName.$obj\n");
            exit(1);
        }
        $count = 0;
        $def = preg_replace('/\bBigEKnet\b/i', 'BigEERLP', $def, -1, $count);
        $altered = preg_replace('/^(\s*(?:--[^\n]*\n|\/\*.*?\*\/|\s)*)CREATE(\s+)(PROC|PROCEDURE|VIEW|FUNCTION)\b/is', '$1ALTER$2$3', $def, 1, $swap);
        if ($swap !== 1) {
            fwrite(STDERR, "could not convert CREATE to ALTER: $dbName.$obj\n");
            exit(1);
        }
        $out .= "USE [$useDb];\nGO\n" . rtrim($altered) . "\nGO\n\n";
        fwrite(STDOUT, sprintf("%-10s %-45s replaced=%d\n", $dbName, $obj, $count));
    }
}

$out .= "UPDATE BigEUsers.dbo.portal SET name = 'ERLP', route = 'erlp' WHERE id = 47 AND route = 'k-net';\nGO\n\n";
$out .= "SELECT name FROM sys.databases WHERE name IN ('BigEKnet', 'BigEERLP');\n";
$out .= "SELECT d, COUNT(*) AS still_referencing_bigeknet FROM (SELECT 'BigEERLP' d FROM BigEERLP.sys.sql_modules WHERE definition LIKE '%BigEKnet%' UNION ALL SELECT 'BigEKFlow' FROM BigEKFlow.sys.sql_modules WHERE definition LIKE '%BigEKnet%') x GROUP BY d;\n";
$out .= "SELECT id, name, route FROM BigEUsers.dbo.portal WHERE id = 47;\n";

file_put_contents(__DIR__ . '/rename_bigeknet_to_bigeerlp.sql', $out);
echo "written tmp/rename_bigeknet_to_bigeerlp.sql\n";
