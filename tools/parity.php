<?php
if (php_sapi_name() !== 'cli') {
    exit;
}

define('BASEPATH', str_replace('\\', '/', dirname(__DIR__)) . '/system/');
define('APPPATH', str_replace('\\', '/', dirname(__DIR__)) . '/application/');
define('FCPATH', str_replace('\\', '/', dirname(__DIR__)) . '/');
define('ENVIRONMENT', 'development');
define('SYSDIR', 'system');

require BASEPATH . 'core/Common.php';
require BASEPATH . 'database/DB.php';

$db = null;
$active_group = null;
$query_builder = true;
require APPPATH . 'config/database.php';
$GLOBALS['parity_db_params'] = $db['dbknet'];
$GLOBALS['parity_db_params']['cache_on'] = false;

function parity_connect()
{
    $connection = DB($GLOBALS['parity_db_params']);
    $connection->db_debug = false;
    $connection->simple_query('SET LOCK_TIMEOUT 60000');
    return $connection;
}

$db = parity_connect();

require BASEPATH . 'core/Model.php';
require APPPATH . 'core/MY_Model.php';

$filter = isset($argv[1]) ? $argv[1] : '';
$caseDir = FCPATH . 'tools/parity_cases/';
$caseFiles = glob($caseDir . '*.php');
sort($caseFiles);

if ($filter !== '' && $filter[0] === '@') {
    $domain = substr($filter, 1);
    $caseFiles = is_file($caseDir . $domain . '.php') ? array($caseDir . $domain . '.php') : array();
    if (!$caseFiles) {
        echo "No case file for domain $domain\n";
        exit(1);
    }
}

function parity_run_model($db, $model, $method, array $args)
{
    require_once APPPATH . 'models/' . $model . '.php';
    $instance = new $model();
    $instance->db = $db;
    return call_user_func_array(array($instance, $method), $args);
}

function parity_model_rows($returns, $value, $column)
{
    if ($returns === 'none') {
        return array();
    }
    if ($returns === 'row') {
        return $value === null ? array() : array($value);
    }
    if ($returns === 'scalar') {
        return $value === null ? array() : array(array($column => $value));
    }
    return is_array($value) ? $value : array();
}

$cases = array();
foreach ($caseFiles as $caseFile) {
    $loaded = require $caseFile;
    if (is_array($loaded)) {
        $cases = array_merge($cases, $loaded);
    }
}

function parity_run_sp($db, $sp, array $params)
{
    $sql = $sp;
    if (count($params) > 0) {
        $sql .= ' ' . implode(',', array_fill(0, count($params), '?'));
    }
    $previous = $db->db_debug;
    $db->db_debug = false;
    $query = count($params) > 0 ? $db->query($sql, $params) : $db->query($sql);
    $db->db_debug = $previous;
    if (!$query) {
        $error = $db->error();
        throw new Exception(MY_Model::clean_message($error['message']));
    }
    return is_object($query) ? $query->result_array() : array();
}

function parity_value($value)
{
    if ($value === null) {
        return null;
    }
    if (is_bool($value)) {
        return $value ? '1' : '0';
    }
    if (is_numeric($value)) {
        return rtrim(rtrim(number_format((float) $value, 4, '.', ''), '0'), '.');
    }
    return (string) $value;
}

function parity_normalize_rows($rows)
{
    $out = array();
    foreach ($rows as $row) {
        $clean = array();
        foreach ($row as $key => $value) {
            $clean[$key] = parity_value($value);
        }
        $out[] = $clean;
    }
    return $out;
}

function parity_sort_rows(array $rows)
{
    usort($rows, function ($a, $b) {
        return strcmp(json_encode($a), json_encode($b));
    });
    return $rows;
}

function parity_compare_rows($a, $b, $label)
{
    if (count($a) !== count($b)) {
        return $label . 'row count SP=' . count($a) . ' PHP=' . count($b);
    }
    foreach ($a as $i => $row) {
        if (array_keys($row) !== array_keys($b[$i])) {
            return $label . 'row ' . $i . ' columns differ: SP=[' . implode(',', array_keys($row)) . '] PHP=[' . implode(',', array_keys($b[$i])) . ']';
        }
        foreach ($row as $key => $value) {
            if ($value !== $b[$i][$key]) {
                return $label . 'row ' . $i . ' column ' . $key . ': SP=' . var_export($value, true) . ' PHP=' . var_export($b[$i][$key], true);
            }
        }
    }
    return null;
}

function parity_read_compare($old, $new, $unordered)
{
    if (isset($old['__error__']) || isset($new['__error__'])) {
        $left = isset($old['__error__']) ? $old['__error__'] : 'no error';
        $right = isset($new['__error__']) ? $new['__error__'] : 'no error';
        return $left === $right ? null : 'error mismatch: SP=[' . $left . '] PHP=[' . $right . ']';
    }
    $a = parity_normalize_rows($old);
    $b = parity_normalize_rows($new);
    if ($unordered) {
        $a = parity_sort_rows($a);
        $b = parity_sort_rows($b);
    }
    return parity_compare_rows($a, $b, '');
}

function parity_tables($db)
{
    static $tables = null;
    if ($tables === null) {
        $tables = array();
        foreach ($db->query("SELECT name FROM sys.tables WHERE is_ms_shipped = 0 ORDER BY name")->result_array() as $row) {
            $tables[] = $row['name'];
        }
    }
    return $tables;
}

function parity_identity_columns($db)
{
    static $identity = null;
    if ($identity === null) {
        $identity = array();
        foreach ($db->query("SELECT OBJECT_NAME(object_id) AS t, name AS c FROM sys.identity_columns WHERE OBJECTPROPERTY(object_id, 'IsUserTable') = 1")->result_array() as $row) {
            $identity[$row['t']] = $row['c'];
        }
    }
    return $identity;
}

function parity_snapshot($db, array $tables)
{
    $snapshot = array();
    foreach ($tables as $table) {
        $query = $db->query('SELECT * FROM ' . $table);
        if (!$query) {
            $error = $db->error();
            throw new Exception('snapshot of ' . $table . ' failed: ' . MY_Model::clean_message($error['message']));
        }
        $snapshot[$table] = parity_normalize_rows($query->result_array());
    }
    return $snapshot;
}

function parity_table_diff(array $before, array $after)
{
    $count = array();
    foreach ($before as $row) {
        $key = json_encode($row);
        $count[$key] = isset($count[$key]) ? $count[$key] + 1 : 1;
    }
    $added = array();
    foreach ($after as $row) {
        $key = json_encode($row);
        if (!empty($count[$key])) {
            $count[$key]--;
        } else {
            $added[] = $row;
        }
    }
    $removed = array();
    foreach ($count as $key => $n) {
        for ($i = 0; $i < $n; $i++) {
            $removed[] = json_decode($key, true);
        }
    }
    return array('added' => $added, 'removed' => $removed);
}

function parity_write_run($sp, $runner, array $tables, $identity)
{
    $connection = parity_connect();
    $nowRows = $connection->query("SELECT CONVERT(varchar(23), DATEADD(second, -5, GETDATE()), 121) AS ts")->result_array();
    $runStart = $nowRows[0]['ts'];

    $connection->trans_begin();
    $before = parity_snapshot($connection, $tables);
    $error = null;
    $result = array();
    try {
        $result = parity_normalize_rows(call_user_func($runner, $connection));
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
    $after = parity_snapshot($connection, $tables);
    $connection->trans_rollback();
    $connection->simple_query('IF @@TRANCOUNT > 0 ROLLBACK TRANSACTION');
    $connection->close();

    $diffs = array();
    $newIds = array();
    foreach ($tables as $table) {
        $diff = parity_table_diff($before[$table], $after[$table]);
        if (!$diff['added'] && !$diff['removed']) {
            continue;
        }
        $diffs[$table] = $diff;
        $short = preg_replace('/^.*\./', '', $table);
        $idColumn = isset($identity[$short]) ? $identity[$short] : 'id';
        $existing = array();
        foreach ($before[$table] as $row) {
            if (isset($row[$idColumn])) {
                $existing[$row[$idColumn]] = true;
            }
        }
        $fresh = array();
        foreach ($diff['added'] as $row) {
            if (isset($row[$idColumn]) && !isset($existing[$row[$idColumn]])) {
                $fresh[] = $row[$idColumn];
            }
        }
        sort($fresh, SORT_NUMERIC);
        foreach ($fresh as $i => $value) {
            $newIds[$short][$value] = 'NEW:' . $short . '#' . ($i + 1);
        }
    }

    $mask = function (array $rows) use ($newIds, $runStart) {
        $out = array();
        foreach ($rows as $row) {
            foreach ($row as $column => $value) {
                if ($value === null) {
                    continue;
                }
                if (preg_match('/^\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}/', $value) && strcmp($value, $runStart) >= 0) {
                    $row[$column] = '<now>';
                    continue;
                }
                if ($column === 'id' || preg_match('/_id$/', $column)) {
                    $stem = 'tbl_' . preg_replace('/_id$/', '', $column);
                    if (isset($newIds[$stem][$value])) {
                        $row[$column] = $newIds[$stem][$value];
                        continue;
                    }
                    foreach ($newIds as $ids) {
                        if (isset($ids[$value])) {
                            $row[$column] = $ids[$value];
                            break;
                        }
                    }
                }
            }
            $out[] = $row;
        }
        return $out;
    };

    $maskedDiffs = array();
    foreach ($diffs as $table => $diff) {
        $short = preg_replace('/^.*\./', '', $table);
        $added = array();
        foreach ($diff['added'] as $row) {
            $idColumn = isset($identity[$short]) ? $identity[$short] : 'id';
            if (isset($row[$idColumn]) && isset($newIds[$short][$row[$idColumn]])) {
                $row[$idColumn] = $newIds[$short][$row[$idColumn]];
            }
            $added[] = $row;
        }
        $maskedDiffs[$table] = array(
            'added' => parity_sort_rows($mask($added)),
            'removed' => parity_sort_rows($mask($diff['removed'])),
        );
    }

    return array('error' => $error, 'result' => $mask($result), 'diffs' => $maskedDiffs);
}

function parity_write_compare($old, $new)
{
    if ($old['error'] !== $new['error']) {
        return 'error mismatch: SP=[' . var_export($old['error'], true) . '] PHP=[' . var_export($new['error'], true) . ']';
    }
    $diff = parity_compare_rows($old['result'], $new['result'], 'result ');
    if ($diff !== null) {
        return $diff;
    }
    $tables = array_unique(array_merge(array_keys($old['diffs']), array_keys($new['diffs'])));
    sort($tables);
    foreach ($tables as $table) {
        $a = isset($old['diffs'][$table]) ? $old['diffs'][$table] : array('added' => array(), 'removed' => array());
        $b = isset($new['diffs'][$table]) ? $new['diffs'][$table] : array('added' => array(), 'removed' => array());
        foreach (array('added', 'removed') as $kind) {
            $diff = parity_compare_rows($a[$kind], $b[$kind], $table . ' ' . $kind . ' ');
            if ($diff !== null) {
                return $diff;
            }
        }
    }
    return null;
}

$identity = parity_identity_columns($db);
$covered = array();
$failed = 0;
$passed = 0;

foreach ($cases as $case) {
    if (isset($case['function'])) {
        $function = $case['function'];
        if ($filter !== '' && $filter[0] !== '@' && $filter !== $function) {
            continue;
        }
        $covered[$function] = true;
        $args = isset($case['args']) ? $case['args'] : array();
        $placeholders = implode(',', array_fill(0, count($args), '?'));
        $label = 'FUNC ' . $function . ' ' . json_encode($args);
        try {
            $oldRows = count($args) > 0
                ? $db->query('SELECT dbo.' . $function . '(' . $placeholders . ') AS v', $args)->result_array()
                : $db->query('SELECT dbo.' . $function . '() AS v')->result_array();
            $old = parity_value($oldRows[0]['v']);
        } catch (Exception $e) {
            $old = 'ERROR ' . $e->getMessage();
        }
        try {
            if (isset($case['expr'])) {
                $literals = array();
                foreach ($args as $arg) {
                    $literals[] = $db->escape($arg);
                }
                $expression = call_user_func_array(array('Knet_functions', $case['expr']), $literals);
                $newRows = $db->query('SELECT (' . $expression . ') AS v')->result_array();
                $new = parity_value($newRows[0]['v']);
            } else {
                $new = parity_value(call_user_func_array(array('Knet_functions', $case['php']), array_merge(array($db), $args)));
            }
        } catch (Exception $e) {
            $new = 'ERROR ' . $e->getMessage();
        }
        if ($old === $new) {
            $passed++;
            echo str_pad('OK', 6) . $label . ' = ' . var_export($old, true) . "\n";
        } else {
            $failed++;
            echo str_pad('DIFF', 6) . $label . "\n       SQL=" . var_export($old, true) . ' PHP=' . var_export($new, true) . "\n";
        }
        continue;
    }

    if (isset($case['view'])) {
        $view = $case['view'];
        if ($filter !== '' && $filter[0] !== '@' && $filter !== $view) {
            continue;
        }
        $covered[$view] = true;
        $order = isset($case['order']) ? $case['order'] : '1';
        try {
            $old = $db->query('SELECT * FROM ' . $view . ' ORDER BY ' . $order)->result_array();
        } catch (Exception $e) {
            $old = array('__error__' => $e->getMessage());
        }
        try {
            $new = $db->query('SELECT * FROM ' . Knet_views::sub($view, 'V') . ' ORDER BY ' . $order)->result_array();
        } catch (Exception $e) {
            $new = array('__error__' => $e->getMessage());
        }
        $diff = parity_read_compare($old, $new, false);
        if ($diff === null) {
            $passed++;
            echo str_pad('OK', 6) . 'VIEW ' . $view . ' rows=' . count($old) . "\n";
        } else {
            $failed++;
            echo str_pad('DIFF', 6) . 'VIEW ' . $view . "\n       " . $diff . "\n";
        }
        continue;
    }

    $sp = $case['sp'];
    if ($filter !== '' && $filter[0] !== '@' && $filter !== $sp) {
        continue;
    }
    $covered[$sp] = true;

    if (!isset($case['model'])) {
        echo str_pad('SKIP', 6) . $sp . " (no model)\n";
        continue;
    }

    $params = $case['params'];
    $model = $case['model'];
    $method = $case['method'];
    $label = $sp . ' ' . json_encode($params) . ' -> ' . $model . '::' . $method;
    $modelArgs = isset($case['args']) ? $case['args'] : $params;
    $returns = isset($case['returns']) ? $case['returns'] : 'rows';

    if (!empty($case['write'])) {
        $tables = parity_tables($db);
        if (!empty($case['tables'])) {
            $tables = array_merge($tables, $case['tables']);
        }
        try {
            $old = parity_write_run($sp, function ($connection) use ($sp, $params) {
                return parity_run_sp($connection, $sp, $params);
            }, $tables, $identity);
            $firstColumn = isset($old['result'][0]) ? key($old['result'][0]) : 'value';
            $new = parity_write_run($sp, function ($connection) use ($model, $method, $modelArgs, $returns, $firstColumn) {
                return parity_model_rows($returns, parity_run_model($connection, $model, $method, $modelArgs), $firstColumn);
            }, $tables, $identity);
            if ($returns === 'none') {
                $old['result'] = array();
            }
            $diff = parity_write_compare($old, $new);
        } catch (Exception $e) {
            $diff = 'harness error: ' . $e->getMessage();
        }
        $label = 'WRITE ' . $label;
        if ($diff !== null && !empty($case['known_sp_bug']) && isset($old['error']) && array_key_exists('error', $new) && $new['error'] === null) {
            $passed++;
            echo str_pad('FIXED', 6) . $label . ' SP fails [' . $old['error'] . '] PHP works; reason: ' . $case['known_sp_bug'] . "\n";
            continue;
        }
        $summary = isset($old['diffs']) ? implode(',', array_map(function ($t) use ($old) {
            return $t . '(+' . count($old['diffs'][$t]['added']) . '/-' . count($old['diffs'][$t]['removed']) . ')';
        }, array_keys($old['diffs']))) : '';
        if ($diff === null) {
            $passed++;
            echo str_pad('OK', 6) . $label . ' ' . ($old['error'] !== null ? 'error=[' . $old['error'] . ']' : 'changes=' . ($summary === '' ? 'none' : $summary)) . "\n";
        } else {
            $failed++;
            echo str_pad('DIFF', 6) . $label . "\n       " . $diff . "\n";
        }
        continue;
    }

    $unordered = !empty($case['unordered']);
    try {
        $old = parity_run_sp($db, $sp, $params);
    } catch (Exception $e) {
        $old = array('__error__' => $e->getMessage());
    }
    try {
        $firstColumn = (!isset($old['__error__']) && isset($old[0])) ? key($old[0]) : 'value';
        $new = parity_model_rows($returns, parity_run_model($db, $model, $method, $modelArgs), $firstColumn);
        if ($returns === 'none' && !isset($old['__error__'])) {
            $old = array();
        }
    } catch (Exception $e) {
        $new = array('__error__' => $e->getMessage());
    }

    $diff = parity_read_compare($old, $new, $unordered);
    if ($diff !== null && !empty($case['known_sp_bug']) && isset($old['__error__']) && !isset($new['__error__'])) {
        $passed++;
        echo str_pad('FIXED', 6) . $label . ' SP fails [' . $old['__error__'] . '] PHP works; reason: ' . $case['known_sp_bug'] . "\n";
        continue;
    }
    if ($diff === null) {
        $passed++;
        echo str_pad('OK', 6) . $label . ' rows=' . (isset($old['__error__']) ? 'error=[' . $old['__error__'] . ']' : count($old)) . "\n";
    } else {
        $failed++;
        echo str_pad('DIFF', 6) . $label . "\n       " . $diff . "\n";
    }
}

echo "\nPASSED=$passed FAILED=$failed\n";
exit($failed > 0 ? 1 : 0);
