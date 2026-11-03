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

$oldDb = 'BigEERLP';
$newDb = 'BigEExpense';

$dbs = array();
$stmt = sqlsrv_query($conn, "SELECT name FROM sys.databases WHERE database_id > 4 AND state = 0 ORDER BY name");
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $dbs[] = $row['name'];
}

$out = "SET NOCOUNT ON;\nGO\n\n";
$out .= "IF DB_ID('$newDb') IS NULL AND DB_ID('$oldDb') IS NOT NULL\nBEGIN\n    ALTER DATABASE $oldDb SET SINGLE_USER WITH ROLLBACK IMMEDIATE;\n    ALTER DATABASE $oldDb MODIFY NAME = $newDb;\n    ALTER DATABASE $newDb SET MULTI_USER;\nEND\nGO\n\n";

$checks = array();
foreach ($dbs as $dbName) {
    $sql = "SELECT OBJECT_SCHEMA_NAME(m.object_id, DB_ID('$dbName')) AS s, o.name AS n, m.definition AS def FROM [$dbName].sys.sql_modules m JOIN [$dbName].sys.objects o ON o.object_id = m.object_id WHERE m.definition LIKE '%$oldDb%' ORDER BY o.type, o.name";
    $stmt = sqlsrv_query($conn, $sql);
    if (!$stmt) {
        fwrite(STDERR, "scan failed: $dbName\n");
        exit(1);
    }
    $found = false;
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $found = true;
        $useDb = $dbName === $oldDb ? $newDb : $dbName;
        $count = 0;
        $def = preg_replace('/\b' . $oldDb . '\b/i', $newDb, $row['def'], -1, $count);
        $altered = preg_replace('/^(\s*(?:--[^\n]*\n|\/\*.*?\*\/|\s)*)CREATE(\s+)(PROC|PROCEDURE|VIEW|FUNCTION|TRIGGER)\b/is', '$1ALTER$2$3', $def, 1, $swap);
        if ($swap !== 1) {
            fwrite(STDERR, "could not convert CREATE to ALTER: $dbName.{$row['s']}.{$row['n']}\n");
            exit(1);
        }
        $out .= "USE [$useDb];\nGO\n" . rtrim($altered) . "\nGO\n\n";
        fwrite(STDOUT, sprintf("%-14s %-45s replaced=%d\n", $dbName, $row['s'] . '.' . $row['n'], $count));
    }
    if ($found) {
        $checks[] = $dbName === $oldDb ? $newDb : $dbName;
    }
}

$out .= "USE [master];\nGO\n";
$out .= "SELECT name FROM sys.databases WHERE name IN ('$oldDb', '$newDb');\n";
$union = array();
foreach ($checks as $c) {
    $union[] = "SELECT '$c' AS d FROM [$c].sys.sql_modules WHERE definition LIKE '%$oldDb%'";
}
if ($union) {
    $out .= "SELECT d, COUNT(*) AS still_referencing_old FROM (" . implode(' UNION ALL ', $union) . ") x GROUP BY d;\n";
}

file_put_contents(__DIR__ . '/rename_bigeerlp_to_bigeexpense.sql', $out);
echo "written tmp/rename_bigeerlp_to_bigeexpense.sql\n";
