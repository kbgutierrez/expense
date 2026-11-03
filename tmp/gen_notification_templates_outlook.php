<?php
if (php_sapi_name() !== 'cli') {
    exit;
}
chdir(dirname(__DIR__));
define('BASEPATH', 'x');
define('APPPATH', getcwd() . '/application/');
define('FCPATH', getcwd() . '/');

class MY_Controller
{
}

function base_url($p = '')
{
    return $p;
}

require APPPATH . 'helpers/notification_helper.php';
require APPPATH . 'modules/notification-templates/controllers/Notification_Templates.php';

$ref = new ReflectionClass('Notification_Templates');
$obj = $ref->newInstanceWithoutConstructor();
$build = $ref->getMethod('buildDefaultTemplate');
$build->setAccessible(true);

$db = array();
$active_group = null;
$query_builder = true;
require APPPATH . 'config/database.php';
$cfg = $db['dbknet'];
$conn = sqlsrv_connect($cfg['hostname'], array('Database' => $cfg['database'], 'UID' => $cfg['username'], 'PWD' => $cfg['password'], 'CharacterSet' => 'UTF-8'));
if (!$conn) {
    fwrite(STDERR, "connect failed\n");
    exit(1);
}
$rows = array();
$stmt = sqlsrv_query($conn, 'SELECT id, event_code, transaction_type, body_html FROM tbl_notification_templates WHERE id BETWEEN 1 AND 6 ORDER BY id');
while ($r = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $rows[] = $r;
}
$sql = "SET NOCOUNT ON;\n\nIF OBJECT_ID('tbl_notification_templates_bak_20261022_outlook') IS NULL\n    SELECT * INTO tbl_notification_templates_bak_20261022_outlook FROM tbl_notification_templates;\n\n";
$report = array();

function plain($html)
{
    $t = html_entity_decode(strip_tags(str_replace(array('<br>', '</p>', '</td>', '</div>'), ' ', $html)), ENT_QUOTES, 'UTF-8');
    $t = str_replace(array("\xe2\x80\x94", ':', "\xc2\xa0"), array('-', '', ' '), $t);
    return trim(preg_replace('/\s+/', ' ', $t));
}

foreach ($rows as $row) {
    $old = plain(preg_replace('#<tr><td bgcolor="\#f8f8fc".*?</td></tr>#s', '', $row['body_html']));
    $same = false;
    foreach (array((string) $row['transaction_type'], '') as $type) {
        $built = $build->invoke($obj, $row['event_code'], $type);
        $new = plain(preg_replace('#<tr><td bgcolor="\#f8f8fc".*?</td></tr>#s', '', $built['body_html']));
        if ($old === $new) {
            $same = true;
            break;
        }
    }
    $report[] = sprintf('id=%s %s/%s text_match=%s', $row['id'], $row['event_code'], $row['transaction_type'] ?: 'ALL', $same ? 'YES' : 'NO');
    if (!$same) {
        $report[] = '   DB : ' . substr($old, 0, 300);
        $report[] = '   NEW: ' . substr($new, 0, 300);
        continue;
    }
    $sql .= "UPDATE tbl_notification_templates SET body_html = N'" . str_replace("'", "''", $built['body_html']) . "' WHERE id = " . (int) $row['id'] . ";\n\n";
}
$sql .= "SELECT id, event_code, LEN(body_html) AS len, CASE WHEN body_html LIKE '%presentation%' AND body_html NOT LIKE '%linear-gradient%' THEN 'OUTLOOK' ELSE 'OLD' END AS layout FROM tbl_notification_templates ORDER BY id;\n";
file_put_contents(__DIR__ . '/update_notification_templates_outlook_layout.sql', $sql);
echo implode("\n", $report), "\n";
