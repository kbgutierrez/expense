<?php
if (php_sapi_name() !== 'cli') {
    exit;
}

date_default_timezone_set('Asia/Manila');

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
$params = $db['dbknet'];
$params['cache_on'] = false;
$conn = DB($params);
$conn->db_debug = false;

$notesDir = FCPATH . 'notes/';
$today = date('Y-m-d');
$dbName = $params['database'];
$written = 0;

function q($conn, $sql)
{
    $res = $conn->query($sql);
    if (!$res) {
        $err = $conn->error();
        fwrite(STDERR, "SQL failed: " . $err['message'] . "\n");
        exit(1);
    }
    return $res->result_array();
}

function module_title($slug)
{
    $upper = array('ca' => 'CA', 'gl' => 'GL', 'rf' => 'RF');
    $parts = preg_split('/[-_]+/', $slug);
    foreach ($parts as $i => $p) {
        $parts[$i] = isset($upper[strtolower($p)]) ? $upper[strtolower($p)] : ucfirst(strtolower($p));
    }
    return implode(' ', $parts);
}

function col_type($r)
{
    $t = $r['typ'];
    if (in_array($t, array('varchar', 'char', 'varbinary', 'binary'))) {
        return $t . '(' . ($r['max_length'] == -1 ? 'max' : $r['max_length']) . ')';
    }
    if (in_array($t, array('nvarchar', 'nchar'))) {
        return $t . '(' . ($r['max_length'] == -1 ? 'max' : $r['max_length'] / 2) . ')';
    }
    if (in_array($t, array('decimal', 'numeric'))) {
        return $t . '(' . $r['precision'] . ',' . $r['scale'] . ')';
    }
    return $t;
}

function save_note($path, $genBlock, $skeleton, $today)
{
    global $written;
    $block = "%% gen:start %%\n" . rtrim($genBlock) . "\n%% gen:end %%";
    if (!is_dir(dirname($path))) {
        mkdir(dirname($path), 0777, true);
    }
    if (is_file($path)) {
        $old = file_get_contents($path);
        $new = preg_replace('/%% gen:start %%.*?%% gen:end %%/s', str_replace(array('\\', '$'), array('\\\\', '\\$'), $block), $old, 1, $count);
        if (!$count) {
            $new = rtrim($old) . "\n\n" . $block . "\n";
        }
        if ($new === $old) {
            return;
        }
        $new = preg_replace('/^updated: .*$/m', 'updated: ' . $today, $new, 1);
    } else {
        $new = str_replace('{{GEN}}', $block, $skeleton);
    }
    file_put_contents($path, $new);
    $written++;
}

function php_files($dir)
{
    $out = array();
    if (!is_dir($dir)) {
        return $out;
    }
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS));
    foreach ($it as $f) {
        if (strtolower($f->getExtension()) === 'php') {
            $out[] = str_replace('\\', '/', $f->getPathname());
        }
    }
    sort($out);
    return $out;
}

function split_methods($src)
{
    preg_match_all('/^\s*((?:public|protected|private|static|\s)*)function\s+(\w+)\s*\(([^)]*)\)/m', $src, $m, PREG_OFFSET_CAPTURE);
    $methods = array();
    $n = count($m[0]);
    for ($i = 0; $i < $n; $i++) {
        $start = $m[0][$i][1];
        $end = $i + 1 < $n ? $m[0][$i + 1][1] : strlen($src);
        $mods = $m[1][$i][0];
        $methods[] = array(
            'name' => $m[2][$i][0],
            'args' => trim(preg_replace('/\s+/', ' ', $m[3][$i][0])),
            'public' => stripos($mods, 'private') === false && stripos($mods, 'protected') === false,
            'body' => substr($src, $start, $end - $start),
        );
    }
    return $methods;
}

function sp_refs($text)
{
    preg_match_all('/[\'"](?:EXEC(?:UTE)?\s+)?(?:dbo\.)?(sp_\w+)/i', $text, $m);
    $out = array_values(array_diff(array_unique($m[1]), array('sp_executesql')));
    sort($out);
    return $out;
}

function table_refs($text, $known)
{
    preg_match_all('/\b(tbl_\w+|vw_\w+)\b/i', $text, $m);
    preg_match_all('/Knet_views::(vw_\w+)\b/', $text, $m2);
    preg_match_all('/Knet_views::sub\(\s*[\'"](vw_\w+)/', $text, $m3);
    preg_match_all('/\b(BigE\w+\.dbo\.\w+)/i', $text, $m4);
    $cross = array();
    foreach ($m4[1] as $c) {
        $parts = explode('.', $c);
        $cross[strtolower(end($parts))] = true;
    }
    $local = array_filter($m[1], function ($t) use ($cross) {
        return !isset($cross[strtolower($t)]);
    });
    $all = array_merge($local, $m2[1], $m3[1], $m4[1]);
    $out = array();
    foreach ($all as $t) {
        $key = strtolower($t);
        $out[$key] = isset($known[$key]) ? $known[$key] : $t;
    }
    ksort($out);
    return array_values($out);
}

function link_table($name, $tables)
{
    return isset($tables[strtolower($name)]) ? '[[' . $tables[strtolower($name)] . ']]' : '`' . $name . '`';
}

$tableRows = q($conn, "SELECT t.name tbl, c.column_id, c.name col, ty.name typ, c.max_length, c.precision, c.scale, c.is_nullable, c.is_identity,
    CASE WHEN EXISTS (SELECT 1 FROM sys.index_columns ic JOIN sys.indexes i ON i.object_id = ic.object_id AND i.index_id = ic.index_id
        WHERE i.is_primary_key = 1 AND ic.object_id = t.object_id AND ic.column_id = c.column_id) THEN 1 ELSE 0 END is_pk,
    (SELECT TOP 1 OBJECT_NAME(fk.referenced_object_id) + '.' + COL_NAME(fk.referenced_object_id, fk.referenced_column_id)
        FROM sys.foreign_key_columns fk WHERE fk.parent_object_id = t.object_id AND fk.parent_column_id = c.column_id) fk_ref
    FROM sys.tables t JOIN sys.columns c ON c.object_id = t.object_id JOIN sys.types ty ON ty.user_type_id = c.user_type_id
    WHERE t.is_ms_shipped = 0 ORDER BY t.name, c.column_id");
$countRows = q($conn, "SELECT t.name tbl, SUM(p.rows) cnt FROM sys.tables t JOIN sys.partitions p ON p.object_id = t.object_id AND p.index_id IN (0, 1) WHERE t.is_ms_shipped = 0 GROUP BY t.name");
$viewRows = q($conn, "SELECT name FROM sys.views WHERE is_ms_shipped = 0 ORDER BY name");
$fnRows = q($conn, "SELECT name, type_desc FROM sys.objects WHERE type IN ('FN', 'IF', 'TF') AND is_ms_shipped = 0 ORDER BY name");
$procRows = q($conn, "SELECT p.name, OBJECT_DEFINITION(p.object_id) def FROM sys.procedures p WHERE p.is_ms_shipped = 0 ORDER BY p.name");
$paramRows = q($conn, "SELECT p.name sp, pa.parameter_id, pa.name pname, TYPE_NAME(pa.user_type_id) typ, pa.is_output FROM sys.procedures p JOIN sys.parameters pa ON pa.object_id = p.object_id ORDER BY p.name, pa.parameter_id");
$depRows = q($conn, "SELECT p.name sp, d.referenced_database_name db, d.referenced_entity_name ent FROM sys.sql_expression_dependencies d JOIN sys.procedures p ON p.object_id = d.referencing_id WHERE d.referenced_entity_name IS NOT NULL");

$tables = array();
$tableCols = array();
foreach ($tableRows as $r) {
    if (preg_match('/_bak(_|$)|_backup|_old$/i', $r['tbl'])) {
        continue;
    }
    $tables[strtolower($r['tbl'])] = $r['tbl'];
    $tableCols[$r['tbl']][] = $r;
}
$counts = array();
foreach ($countRows as $r) {
    $counts[$r['tbl']] = (float)$r['cnt'];
}
$views = array();
foreach ($viewRows as $r) {
    $views[strtolower($r['name'])] = $r['name'];
}
$known = $tables;

$procs = array();
foreach ($procRows as $r) {
    $procs[strtolower($r['name'])] = array('name' => $r['name'], 'def' => (string)$r['def'], 'params' => array(), 'deps' => array(), 'writes' => array(), 'callers' => array());
}
foreach ($paramRows as $r) {
    $k = strtolower($r['sp']);
    $procs[$k]['params'][] = '`' . $r['pname'] . ' ' . $r['typ'] . ($r['is_output'] ? ' OUT' : '') . '`';
}
foreach ($depRows as $r) {
    $k = strtolower($r['sp']);
    $ent = $r['ent'];
    if ($r['db'] && strcasecmp($r['db'], $dbName) !== 0) {
        $label = '`' . $r['db'] . '.' . $ent . '`';
    } elseif (isset($tables[strtolower($ent)])) {
        $label = '[[' . $tables[strtolower($ent)] . ']]';
    } elseif (isset($procs[strtolower($ent)])) {
        continue;
    } else {
        $label = '`' . $ent . '`';
    }
    $procs[$k]['deps'][$label] = $label;
}
foreach ($procs as $k => $p) {
    $def = preg_replace('/--[^\n]*|\/\*.*?\*\//s', '', $p['def']);
    preg_match_all('/\b(?:INSERT\s+(?:INTO\s+)?|UPDATE\s+|DELETE\s+(?:FROM\s+)?|MERGE\s+(?:INTO\s+)?)([#@\[\]\w\.]+)/i', $def, $m);
    preg_match_all('/\b(?:FROM|JOIN)\s+([\[\]\w\.]+)\s+(?:AS\s+)?(\w+)/i', $def, $am, PREG_SET_ORDER);
    $aliases = array();
    foreach ($am as $a) {
        $aliases[strtolower($a[2])] = strtolower(str_replace(array('[', ']', 'dbo.'), '', $a[1]));
    }
    foreach ($m[1] as $t) {
        if ($t[0] === '#' || $t[0] === '@' || preg_match('/^(SET|TOP|FROM|INTO)$/i', $t)) {
            continue;
        }
        $t = strtolower(str_replace(array('[', ']', 'dbo.'), '', $t));
        if (!isset($tables[$t]) && isset($aliases[$t])) {
            $t = $aliases[$t];
        }
        $procs[$k]['writes'][$t] = true;
    }
    $procs[$k]['crossdb'] = preg_match('/\bBigE(Users|HRIS|MasterData|KFlow)\b/i', $def) ? true : false;
    ksort($procs[$k]['deps']);
}

$moduleDirs = glob(APPPATH . 'modules/*', GLOB_ONLYDIR);
sort($moduleDirs);
$modules = array();
foreach ($moduleDirs as $d) {
    $slug = basename($d);
    $modules[$slug] = module_title($slug);
}

$owners = array();
$modelCallers = array();
$files = array_merge(php_files(APPPATH . 'modules'), php_files(APPPATH . 'models'), php_files(APPPATH . 'helpers'), php_files(APPPATH . 'libraries'), php_files(APPPATH . 'controllers'), php_files(APPPATH . 'core'));
foreach ($files as $f) {
    $rel = substr($f, strlen(APPPATH));
    if (preg_match('#^modules/([^/]+)/#', $rel, $mm)) {
        if (strpos($rel, '/views/') !== false) {
            continue;
        }
        $owner = '[[' . $modules[$mm[1]] . ']]';
    } else {
        $owner = '`' . basename($f, '.php') . '`';
    }
    $src = file_get_contents($f);
    foreach (sp_refs($src) as $sp) {
        if (isset($procs[strtolower($sp)])) {
            $procs[strtolower($sp)]['callers'][$owner] = $owner;
        }
    }
    preg_match_all('/->([A-Z]\w*_model)->(\w+)\s*\(/', $src, $mc, PREG_SET_ORDER);
    foreach ($mc as $c) {
        $modelCallers[$c[1]][$c[2]][$owner] = $owner;
    }
}

$models = array();
$tableUsers = array();
foreach (php_files(APPPATH . 'models') as $f) {
    $name = basename($f, '.php');
    $src = file_get_contents($f);
    preg_match('/class\s+\w+\s+extends\s+(\w+)/', $src, $ext);
    $methods = array();
    $all = array();
    foreach (split_methods($src) as $m) {
        $all[strtolower($m['name'])] = $m['body'];
    }
    foreach (split_methods($src) as $m) {
        if (!$m['public'] || $m['name'] === '__construct') {
            continue;
        }
        $body = $m['body'];
        $seen = array(strtolower($m['name']) => true);
        for ($depth = 0; $depth < 3; $depth++) {
            preg_match_all('/\$this->(\w+)\s*\(/', $body, $calls);
            foreach ($calls[1] as $c) {
                $c = strtolower($c);
                if (isset($all[$c]) && !isset($seen[$c])) {
                    $seen[$c] = true;
                    $body .= $all[$c];
                }
            }
        }
        $tbls = table_refs($body, $known);
        $sps = sp_refs($body);
        $methods[] = array('name' => $m['name'], 'args' => $m['args'], 'tables' => $tbls, 'sps' => $sps);
        foreach ($tbls as $t) {
            $tableUsers[strtolower($t)]['models'][$name . '::' . $m['name']] = true;
        }
    }
    $models[$name] = array('extends' => isset($ext[1]) ? $ext[1] : '', 'methods' => $methods);
}

foreach ($procs as $k => $p) {
    foreach ($p['deps'] as $label) {
        if (preg_match('/^\[\[(.+)\]\]$/', $label, $mm)) {
            $w = isset($p['writes'][strtolower($mm[1])]);
            $tableUsers[strtolower($mm[1])]['sps'][$p['name']] = $w ? 'W' : 'R';
        }
    }
}

$skeletonTable = "---\ntags: [table]\nrows: {{ROWS}}\ncreated: $today\nupdated: $today\n---\n# {{NAME}}\n\n{{GEN}}\n\n## Notes\n- \n";
foreach ($tableCols as $tbl => $cols) {
    $g = "DB: `$dbName` · " . number_format(isset($counts[$tbl]) ? $counts[$tbl] : 0) . " rows (noong $today) · [[Database Schema]]\n\n";
    $g .= "## Columns\n| Column | Type | Null | Key |\n|---|---|---|---|\n";
    foreach ($cols as $c) {
        $key = array();
        if ($c['is_pk']) {
            $key[] = 'PK';
        }
        if ($c['is_identity']) {
            $key[] = 'identity';
        }
        if ($c['fk_ref']) {
            $ref = explode('.', $c['fk_ref']);
            $key[] = 'FK → ' . link_table($ref[0], $tables) . '.' . $ref[1];
        }
        $g .= '| `' . $c['col'] . '` | ' . col_type($c) . ' | ' . ($c['is_nullable'] ? 'Y' : 'N') . ' | ' . implode(' ', $key) . " |\n";
    }
    $u = isset($tableUsers[strtolower($tbl)]) ? $tableUsers[strtolower($tbl)] : array();
    $g .= "\n## Ginagamit ng mga model\n";
    if (!empty($u['models'])) {
        $byModel = array();
        foreach (array_keys($u['models']) as $mm) {
            list($mn, $meth) = explode('::', $mm);
            $byModel[$mn][] = '`' . $meth . '`';
        }
        ksort($byModel);
        foreach ($byModel as $mn => $meths) {
            $g .= "- `$mn`: " . implode(', ', $meths) . "\n";
        }
    } else {
        $g .= "- _wala_\n";
    }
    $g .= "\n## Ginagamit ng mga SP\n";
    if (!empty($u['sps'])) {
        ksort($u['sps']);
        foreach ($u['sps'] as $sp => $rw) {
            $callers = $procs[strtolower($sp)]['callers'];
            ksort($callers);
            $g .= "- `$sp` ($rw) — " . ($callers ? implode(', ', $callers) : '_hindi tinatawag sa code_') . "\n";
        }
    } else {
        $g .= "- _wala_\n";
    }
    $sk = str_replace(array('{{ROWS}}', '{{NAME}}'), array(isset($counts[$tbl]) ? sprintf('%.0f', $counts[$tbl]) : 0, $tbl), $skeletonTable);
    save_note($notesDir . 'Database/Tables/' . $tbl . '.md', $g, $sk, $today);
}

$spSkeleton = "---\ntags: [database, reference]\ncreated: $today\nupdated: $today\n---\n# Stored Procedures\n\n{{GEN}}\n";
$groups = array('multi' => array(), 'models' => array(), 'none' => array());
foreach ($modules as $slug => $title) {
    $groups['[[' . $title . ']]'] = array();
}
foreach ($procs as $k => $p) {
    $mods = array_filter(array_keys($p['callers']), function ($c) {
        return strpos($c, '[[') === 0;
    });
    if (count($p['callers']) === 0) {
        $groups['none'][] = $p;
    } elseif (count($p['callers']) >= 3) {
        $groups['multi'][] = $p;
    } elseif ($mods) {
        sort($mods);
        $groups[reset($mods)][] = $p;
    } else {
        $groups['models'][] = $p;
    }
}
$spRow = function ($p) {
    $callers = $p['callers'];
    ksort($callers);
    $rw = $p['writes'] ? 'W' : 'R';
    if ($p['crossdb']) {
        $rw .= ' ⇄db';
    }
    return '| `' . $p['name'] . '` | ' . ($p['params'] ? implode(', ', $p['params']) : '—') . ' | ' . ($p['deps'] ? implode(', ', $p['deps']) : '—') . " | $rw | " . ($callers ? implode(', ', $callers) : '—') . " |\n";
};
$head = "| SP | Params | Tables | R/W | Tinatawag ng |\n|---|---|---|---|---|\n";
$g = "Hinango mula sa `$dbName` noong $today: " . count($procs) . " SP. **Params ay positional** (`build_sp`): ang order dito = order ng `\$params` sa PHP.\n";
$g .= "R/W = may INSERT/UPDATE/DELETE/MERGE sa SP body (hindi kasama ang temp table). **⇄db** = may cross-DB reference (`BigEUsers`, `BigEHRIS`, `BigEMasterData`, `BigEKFlow`). Buong SQL: `OBJECT_DEFINITION` (tingnan ang CLAUDE.md).\n";
$g .= "Ang SP na \"hindi tinatawag sa code\" ay kadalasang na-convert na sa plain model (tingnan ang [[Models]] at ang parity tool) o luma na.\n\nBumalik sa [[Database Schema]].\n";
$labels = array('multi' => 'Ginagamit ng 3+ modules / files', 'models' => 'Models / helpers lang');
foreach ($groups as $key => $list) {
    if (!$list || $key === 'none') {
        continue;
    }
    $g .= "\n## " . (isset($labels[$key]) ? $labels[$key] : trim($key, '[]')) . "\n" . $head;
    foreach ($list as $p) {
        $g .= $spRow($p);
    }
}
if ($groups['none']) {
    $g .= "\n## Hindi tinatawag sa code\n" . $head;
    foreach ($groups['none'] as $p) {
        $g .= $spRow($p);
    }
}
if ($fnRows) {
    $g .= "\n## SQL functions\n";
    foreach ($fnRows as $r) {
        $g .= '- `' . $r['name'] . '` (' . strtolower(str_replace('_', ' ', $r['type_desc'])) . ")\n";
    }
}
save_note($notesDir . 'Database/Stored Procedures.md', $g, $spSkeleton, $today);

$modelSkeleton = "---\ntags: [database, reference]\ncreated: $today\nupdated: $today\n---\n# Models\n\nPlain CI models sa `application/models/` (extends `MY_Model`). Default ito para sa reads/lookup/CRUD; SP lang kapag id generation, atomic multi-step, cross-DB write o heavy report (tingnan ang CLAUDE.md).\n\n{{GEN}}\n";
$g = "Hinango mula sa code noong $today. Tables = `tbl_*` / `vw_*` / `Knet_views::*` / `BigE*.dbo.*` na nabanggit sa method.\n";
ksort($models);
foreach ($models as $name => $info) {
    if (!$info['methods']) {
        continue;
    }
    $g .= "\n## $name" . ($info['extends'] ? " (extends `" . $info['extends'] . "`)" : '') . "\n| Method | Args | Tables / SP | Tinatawag ng |\n|---|---|---|---|\n";
    foreach ($info['methods'] as $m) {
        $refs = array();
        foreach ($m['tables'] as $t) {
            $refs[] = link_table($t, $tables);
        }
        foreach ($m['sps'] as $s) {
            $refs[] = '`' . $s . '`';
        }
        $callers = isset($modelCallers[$name][$m['name']]) ? $modelCallers[$name][$m['name']] : array();
        ksort($callers);
        $g .= '| `' . $m['name'] . '` | `' . str_replace('|', '\|', $m['args']) . '` | ' . ($refs ? implode(', ', $refs) : '—') . ' | ' . ($callers ? implode(', ', $callers) : '—') . " |\n";
    }
}
save_note($notesDir . 'Database/Models.md', $g, $modelSkeleton, $today);

foreach ($modules as $slug => $title) {
    $dir = APPPATH . 'modules/' . $slug . '/';
    $routes = array();
    if (is_file($dir . 'config/routes.php')) {
        preg_match_all('/\$route\[\s*[\'"]([^\'"]+)[\'"]\s*\]\s*=\s*[\'"]([^\'"]+)[\'"]/', file_get_contents($dir . 'config/routes.php'), $rm, PREG_SET_ORDER);
        foreach ($rm as $r) {
            $parts = explode('/', $r[2]);
            $method = isset($parts[1]) && $parts[1] !== '' && $parts[1][0] !== '$' ? strtolower($parts[1]) : 'index';
            $routes[strtolower($parts[0])][$method][] = $r[1];
        }
    }
    $ctrlFiles = glob($dir . 'controllers/*.php');
    sort($ctrlFiles);
    $viewFiles = php_files($dir . 'views');
    $jsDir = FCPATH . 'assets/js/modules/' . $slug . '/';
    $jsFiles = is_dir($jsDir) ? array_map('basename', glob($jsDir . '*.js')) : array();
    $g = "## Mga file\n";
    $g .= '- Controllers: ' . implode(', ', array_map(function ($f) use ($slug) {
        return '`modules/' . $slug . '/controllers/' . basename($f) . '`';
    }, $ctrlFiles)) . "\n";
    $g .= '- Views: ' . ($viewFiles ? implode(', ', array_map(function ($f) use ($dir) {
        return '`' . substr($f, strlen($dir . 'views/')) . '`';
    }, $viewFiles)) : '_wala_') . "\n";
    $g .= '- JS (`assets/js/modules/' . $slug . '/`): ' . ($jsFiles ? implode(', ', array_map(function ($f) {
        return '`' . $f . '`';
    }, $jsFiles)) : '_wala_') . "\n";
    $g .= "- Routes: `modules/$slug/config/routes.php`\n";
    $usedModels = array();
    $usedSps = array();
    $g .= "\n## Endpoints\n| URL | Controller::method | Type | Model / SP |\n|---|---|---|---|\n";
    foreach ($ctrlFiles as $cf) {
        $cname = basename($cf, '.php');
        foreach (split_methods(file_get_contents($cf)) as $m) {
            if (!$m['public'] || $m['name'] === '__construct' || $m['name'][0] === '_') {
                continue;
            }
            $urls = isset($routes[strtolower($cname)][strtolower($m['name'])]) ? $routes[strtolower($cname)][strtolower($m['name'])] : array();
            $type = strpos($m['name'], 'api_') === 0 ? 'api' : (preg_match('/load->view\(/', $m['body']) ? 'page' : 'other');
            preg_match_all('/->([A-Z]\w*_model)->(\w+)\s*\(/', $m['body'], $mc, PREG_SET_ORDER);
            $refs = array();
            foreach ($mc as $c) {
                $refs[$c[1] . '::' . $c[2]] = '`' . $c[1] . '::' . $c[2] . '`';
                $usedModels[$c[1]] = true;
            }
            foreach (sp_refs($m['body']) as $s) {
                $refs['sp:' . $s] = '`' . $s . '`';
                $usedSps[$s] = true;
            }
            $g .= '| ' . ($urls ? implode('<br>', array_map(function ($u) {
                return '`' . $u . '`';
            }, $urls)) : '_walang route_') . " | $cname::" . $m['name'] . " | $type | " . ($refs ? implode(', ', $refs) : '—') . " |\n";
        }
    }
    $g .= "\n## Models / SP / tables\n";
    $g .= '- Models: ' . ($usedModels ? implode(', ', array_map(function ($m) {
        return '`' . $m . '`';
    }, array_keys($usedModels))) . ' → [[Models]]' : '_wala_') . "\n";
    $spLinks = array();
    $tblSet = array();
    foreach (array_keys($usedSps) as $s) {
        $spLinks[] = '`' . $s . '`';
        if (isset($procs[strtolower($s)])) {
            foreach ($procs[strtolower($s)]['deps'] as $label) {
                if (strpos($label, '[[') === 0) {
                    $tblSet[$label] = true;
                }
            }
        }
    }
    foreach (array_keys($usedModels) as $mn) {
        if (!isset($models[$mn])) {
            continue;
        }
        foreach ($models[$mn]['methods'] as $mm) {
            if (!isset($modelCallers[$mn][$mm['name']]['[[' . $title . ']]'])) {
                continue;
            }
            foreach ($mm['tables'] as $t) {
                if (isset($tables[strtolower($t)])) {
                    $tblSet['[[' . $tables[strtolower($t)] . ']]'] = true;
                }
            }
        }
    }
    ksort($tblSet);
    $g .= '- SP: ' . ($spLinks ? implode(', ', $spLinks) . ' → [[Stored Procedures]]' : '_wala_') . "\n";
    $g .= '- Tables: ' . ($tblSet ? implode(', ', array_keys($tblSet)) : '_wala_') . "\n";
    $sk = "---\ntags: [module]\nmodule: $slug\ncreated: $today\nupdated: $today\n---\n# $title\n\n## Ano ang ginagawa\n\n\n{{GEN}}\n\n## Business rules / gotchas\n- \n\n## Kaugnay\n- [[Architecture]] · [[Database Schema]]\n";
    save_note($notesDir . 'Modules/' . $title . '.md', $g, $sk, $today);
}

echo "Tables: " . count($tableCols) . ", views: " . count($views) . ", SP: " . count($procs) . ", models: " . count($models) . ", modules: " . count($modules) . "\n";
echo "Notes written/updated: $written\n";
