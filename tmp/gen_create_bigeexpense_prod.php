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
sqlsrv_configure('WarningsReturnAsErrors', 0);
$conn = sqlsrv_connect('192.168.1.229', array('UID' => $cfg['username'], 'PWD' => $cfg['password'], 'CharacterSet' => 'UTF-8', 'Database' => 'BigEExpense', 'ReturnDatesAsStrings' => true));
if (!$conn) {
    fwrite(STDERR, "connect failed\n");
    exit(1);
}

$dbName = 'BigEExpense';
$seedTables = array('tbl_status', 'tbl_expense_category', 'tbl_notification_templates', 'tbl_cost_center', 'tbl_bizlink_company_settings');
$skipPattern = '/_bak_/i';

function q($conn, $sql, $params = array())
{
    $stmt = sqlsrv_query($conn, $sql, $params);
    if (!$stmt) {
        fwrite(STDERR, print_r(sqlsrv_errors(), true));
        exit(1);
    }
    $rows = array();
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $rows[] = $row;
    }
    return $rows;
}

function col_type($c)
{
    $t = $c['type_name'];
    if (in_array($t, array('varchar', 'char', 'varbinary', 'binary'), true)) {
        return $t . '(' . ($c['max_length'] == -1 ? 'MAX' : $c['max_length']) . ')';
    }
    if (in_array($t, array('nvarchar', 'nchar'), true)) {
        return $t . '(' . ($c['max_length'] == -1 ? 'MAX' : $c['max_length'] / 2) . ')';
    }
    if (in_array($t, array('decimal', 'numeric'), true)) {
        return $t . '(' . $c['precision'] . ',' . $c['scale'] . ')';
    }
    if (in_array($t, array('datetime2', 'time', 'datetimeoffset'), true)) {
        return $t . '(' . $c['scale'] . ')';
    }
    return $t;
}

function sql_literal($v, $type)
{
    if ($v === null) {
        return 'NULL';
    }
    if (in_array($type, array('int', 'bigint', 'smallint', 'tinyint', 'bit', 'decimal', 'numeric', 'float', 'real', 'money'), true)) {
        return (string) $v;
    }
    if (in_array($type, array('varbinary', 'binary'), true)) {
        return $v;
    }
    return "N'" . str_replace("'", "''", $v) . "'";
}

$tables = q($conn, "SELECT t.object_id, t.name FROM sys.tables t WHERE t.is_ms_shipped = 0 ORDER BY t.name");
$tables = array_values(array_filter($tables, function ($t) use ($skipPattern) {
    return !preg_match($skipPattern, $t['name']);
}));

$out = "SET NOCOUNT ON;\nGO\n\n";
$out .= "IF DB_ID('$dbName') IS NULL\nBEGIN\n"
    . "    DECLARE @data nvarchar(400) = CAST(SERVERPROPERTY('InstanceDefaultDataPath') AS nvarchar(400));\n"
    . "    DECLARE @log nvarchar(400) = CAST(SERVERPROPERTY('InstanceDefaultLogPath') AS nvarchar(400));\n"
    . "    EXEC(N'CREATE DATABASE [$dbName] ON PRIMARY (NAME = N''{$dbName}'', FILENAME = N''' + @data + N'{$dbName}_Data.mdf'') LOG ON (NAME = N''{$dbName}_log'', FILENAME = N''' + @log + N'{$dbName}_Data_log.ldf'') COLLATE SQL_Latin1_General_CP850_BIN2');\n"
    . "END\nGO\n";
$out .= "ALTER DATABASE [$dbName] SET COMPATIBILITY_LEVEL = 120;\nGO\n";
$out .= "USE [$dbName];\nGO\n\n";

$fkSql = '';
$idxSql = '';
foreach ($tables as $t) {
    $oid = $t['object_id'];
    $name = $t['name'];
    $cols = q($conn, "SELECT c.column_id, c.name, ty.name AS type_name, c.max_length, c.precision, c.scale, c.is_nullable, c.is_identity, CAST(ic.seed_value AS bigint) AS seed, CAST(ic.increment_value AS bigint) AS incr, dc.name AS df_name, dc.definition AS df_def FROM sys.columns c JOIN sys.types ty ON ty.user_type_id = c.user_type_id LEFT JOIN sys.identity_columns ic ON ic.object_id = c.object_id AND ic.column_id = c.column_id LEFT JOIN sys.default_constraints dc ON dc.object_id = c.default_object_id WHERE c.object_id = ? ORDER BY c.column_id", array($oid));
    $lines = array();
    foreach ($cols as $c) {
        $line = '    [' . $c['name'] . '] ' . col_type($c);
        if ($c['is_identity']) {
            $line .= ' IDENTITY(' . $c['seed'] . ',' . $c['incr'] . ')';
        }
        $line .= $c['is_nullable'] ? ' NULL' : ' NOT NULL';
        if ($c['df_name'] !== null) {
            $line .= ' CONSTRAINT [' . $c['df_name'] . '] DEFAULT ' . $c['df_def'];
        }
        $lines[] = $line;
    }
    $keys = q($conn, "SELECT kc.name, kc.type, i.type_desc, i.index_id FROM sys.key_constraints kc JOIN sys.indexes i ON i.object_id = kc.parent_object_id AND i.index_id = kc.unique_index_id WHERE kc.parent_object_id = ? ORDER BY kc.type, kc.name", array($oid));
    foreach ($keys as $k) {
        $kcols = q($conn, "SELECT c.name, ic.is_descending_key FROM sys.index_columns ic JOIN sys.columns c ON c.object_id = ic.object_id AND c.column_id = ic.column_id WHERE ic.object_id = ? AND ic.index_id = ? AND ic.is_included_column = 0 ORDER BY ic.key_ordinal", array($oid, $k['index_id']));
        $parts = array_map(function ($x) {
            return '[' . $x['name'] . ']' . ($x['is_descending_key'] ? ' DESC' : ' ASC');
        }, $kcols);
        $lines[] = '    CONSTRAINT [' . $k['name'] . '] ' . ($k['type'] === 'PK' ? 'PRIMARY KEY' : 'UNIQUE') . ' ' . $k['type_desc'] . ' (' . implode(', ', $parts) . ')';
    }
    $checks = q($conn, "SELECT name, definition FROM sys.check_constraints WHERE parent_object_id = ? ORDER BY name", array($oid));
    foreach ($checks as $ck) {
        $lines[] = '    CONSTRAINT [' . $ck['name'] . '] CHECK ' . $ck['definition'];
    }
    $out .= "IF OBJECT_ID('dbo.$name', 'U') IS NULL\nCREATE TABLE [dbo].[$name] (\n" . implode(",\n", $lines) . "\n);\nGO\n\n";

    $fks = q($conn, "SELECT fk.object_id, fk.name, OBJECT_NAME(fk.referenced_object_id) AS ref_table, fk.delete_referential_action_desc AS del, fk.update_referential_action_desc AS upd FROM sys.foreign_keys fk WHERE fk.parent_object_id = ? ORDER BY fk.name", array($oid));
    foreach ($fks as $fk) {
        $fc = q($conn, "SELECT pc.name AS pcol, rc.name AS rcol FROM sys.foreign_key_columns k JOIN sys.columns pc ON pc.object_id = k.parent_object_id AND pc.column_id = k.parent_column_id JOIN sys.columns rc ON rc.object_id = k.referenced_object_id AND rc.column_id = k.referenced_column_id WHERE k.constraint_object_id = ? ORDER BY k.constraint_column_id", array($fk['object_id']));
        $p = implode(', ', array_map(function ($x) { return '[' . $x['pcol'] . ']'; }, $fc));
        $r = implode(', ', array_map(function ($x) { return '[' . $x['rcol'] . ']'; }, $fc));
        $fkSql .= "IF OBJECT_ID('dbo.{$fk['name']}', 'F') IS NULL\nALTER TABLE [dbo].[$name] ADD CONSTRAINT [{$fk['name']}] FOREIGN KEY ($p) REFERENCES [dbo].[{$fk['ref_table']}] ($r)"
            . ($fk['del'] !== 'NO_ACTION' ? ' ON DELETE ' . str_replace('_', ' ', $fk['del']) : '')
            . ($fk['upd'] !== 'NO_ACTION' ? ' ON UPDATE ' . str_replace('_', ' ', $fk['upd']) : '')
            . ";\nGO\n";
    }

    $idx = q($conn, "SELECT i.index_id, i.name, i.type_desc, i.is_unique, i.filter_definition FROM sys.indexes i WHERE i.object_id = ? AND i.is_primary_key = 0 AND i.is_unique_constraint = 0 AND i.type > 0 ORDER BY i.name", array($oid));
    foreach ($idx as $ix) {
        $icols = q($conn, "SELECT c.name, ic.is_descending_key, ic.is_included_column FROM sys.index_columns ic JOIN sys.columns c ON c.object_id = ic.object_id AND c.column_id = ic.column_id WHERE ic.object_id = ? AND ic.index_id = ? ORDER BY ic.is_included_column, ic.key_ordinal, ic.index_column_id", array($oid, $ix['index_id']));
        $key = array();
        $inc = array();
        foreach ($icols as $x) {
            if ($x['is_included_column']) {
                $inc[] = '[' . $x['name'] . ']';
            } else {
                $key[] = '[' . $x['name'] . ']' . ($x['is_descending_key'] ? ' DESC' : ' ASC');
            }
        }
        $idxSql .= "IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE object_id = OBJECT_ID('dbo.$name') AND name = '{$ix['name']}')\nCREATE " . ($ix['is_unique'] ? 'UNIQUE ' : '') . $ix['type_desc'] . " INDEX [{$ix['name']}] ON [dbo].[$name] (" . implode(', ', $key) . ')'
            . ($inc ? ' INCLUDE (' . implode(', ', $inc) . ')' : '')
            . ($ix['filter_definition'] !== null ? ' WHERE ' . $ix['filter_definition'] : '')
            . ";\nGO\n";
    }
}
$out .= $idxSql . "\n";

foreach ($seedTables as $st) {
    $cols = q($conn, "SELECT c.name, ty.name AS type_name, c.is_identity FROM sys.columns c JOIN sys.types ty ON ty.user_type_id = c.user_type_id WHERE c.object_id = OBJECT_ID(?) ORDER BY c.column_id", array('dbo.' . $st));
    $hasIdentity = false;
    $select = array();
    $names = array();
    foreach ($cols as $c) {
        $hasIdentity = $hasIdentity || $c['is_identity'];
        $names[] = '[' . $c['name'] . ']';
        $select[] = in_array($c['type_name'], array('varbinary', 'binary'), true) ? 'CONVERT(varchar(max), [' . $c['name'] . '], 1) AS [' . $c['name'] . ']' : '[' . $c['name'] . ']';
    }
    $rows = q($conn, 'SELECT ' . implode(', ', $select) . " FROM dbo.$st ORDER BY 1");
    $out .= "IF NOT EXISTS (SELECT 1 FROM dbo.$st)\nBEGIN\n";
    if ($hasIdentity) {
        $out .= "    SET IDENTITY_INSERT dbo.$st ON;\n";
    }
    foreach ($rows as $row) {
        $vals = array();
        foreach ($cols as $c) {
            $vals[] = sql_literal($row[$c['name']], $c['type_name']);
        }
        $out .= "    INSERT INTO dbo.$st (" . implode(', ', $names) . ') VALUES (' . implode(', ', $vals) . ");\n";
    }
    if ($hasIdentity) {
        $out .= "    SET IDENTITY_INSERT dbo.$st OFF;\n";
    }
    $out .= "END\nGO\n\n";
    fwrite(STDOUT, sprintf("seed %-32s rows=%d\n", $st, count($rows)));
}

$out .= $fkSql . "\n";

$mods = q($conn, "SELECT o.object_id, o.name, o.type, m.definition FROM sys.sql_modules m JOIN sys.objects o ON o.object_id = m.object_id WHERE o.is_ms_shipped = 0 AND o.type IN ('FN','IF','TF','V','P','TR')");
$byId = array();
foreach ($mods as $m) {
    $byId[$m['object_id']] = $m;
}
$deps = q($conn, "SELECT d.referencing_id, d.referenced_id FROM sys.sql_expression_dependencies d WHERE d.referenced_id IS NOT NULL AND d.referencing_id <> d.referenced_id");
$edges = array();
foreach ($deps as $d) {
    if (isset($byId[$d['referencing_id']]) && isset($byId[$d['referenced_id']])) {
        $edges[$d['referencing_id']][] = $d['referenced_id'];
    }
}
$rank = array('FN' => 0, 'TF' => 0, 'IF' => 1, 'V' => 2, 'P' => 3, 'TR' => 4);
uasort($byId, function ($a, $b) use ($rank) {
    return $rank[$a['type']] === $rank[$b['type']] ? strcmp($a['name'], $b['name']) : $rank[$a['type']] - $rank[$b['type']];
});
$done = array();
$order = array();
$visit = function ($id) use (&$visit, &$done, &$order, $edges, $byId) {
    if (isset($done[$id])) {
        return;
    }
    $done[$id] = true;
    if (isset($edges[$id]) && $byId[$id]['type'] !== 'P') {
        foreach ($edges[$id] as $dep) {
            $visit($dep);
        }
    }
    $order[] = $id;
};
foreach (array_keys($byId) as $id) {
    $visit($id);
}
$counts = array();
foreach ($order as $id) {
    $m = $byId[$id];
    $counts[$m['type']] = (isset($counts[$m['type']]) ? $counts[$m['type']] : 0) + 1;
    $out .= "IF OBJECT_ID('dbo.{$m['name']}') IS NULL\nBEGIN TRY\nEXEC(N'" . str_replace("'", "''", trim($m['definition'])) . "');\nEND TRY\nBEGIN CATCH\n    SELECT 'SKIPPED' AS k, N'{$m['name']}' AS object_name, ERROR_MESSAGE() AS reason;\nEND CATCH\nGO\n\n";
}
foreach ($counts as $k => $v) {
    fwrite(STDOUT, "module $k = $v\n");
}

$out .= "SELECT 'tables' AS k, COUNT(*) AS n FROM sys.tables UNION ALL SELECT 'modules', COUNT(*) FROM sys.sql_modules UNION ALL SELECT 'fks', COUNT(*) FROM sys.foreign_keys UNION ALL SELECT 'indexes', COUNT(*) FROM sys.indexes WHERE type > 0;\n";

file_put_contents(__DIR__ . '/create_bigeexpense_prod.sql', $out);
fwrite(STDOUT, 'tables=' . count($tables) . "\nwritten tmp/create_bigeexpense_prod.sql\n");
