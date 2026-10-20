<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once APPPATH . 'core/MY_Model.php';

class Replenishment_model extends MY_Model
{
    public function list_details($replenishmentId)
    {
        if ($replenishmentId === null) {
            return array();
        }
        return $this->query_rows(
            'SELECT * FROM ' . Knet_views::sub('vw_rpl_header', 'V') . ' WHERE V.replenishment_id = CAST(? AS VARCHAR(20))',
            array((string) $replenishmentId)
        );
    }

    public function find_header($replenishmentId)
    {
        $rows = $this->list_details($replenishmentId);
        return isset($rows[0]) ? $rows[0] : null;
    }

    public function list_by_user($userId, $cursorId = null, $take = null)
    {
        if ($userId === null) {
            return array();
        }
        $top = ($take === null || (int) $take < 1) ? '' : 'TOP (' . ((int) $take + 1) . ') ';
        return $this->query_rows(
            'SELECT ' . $top . '* FROM ' . Knet_views::sub('vw_rpl_header', 'V') . '
WHERE V.user_id = CAST(? AS INT)
    AND (CAST(? AS BIGINT) IS NULL OR V.id < CAST(? AS BIGINT))
ORDER BY V.id DESC',
            array($userId, $cursorId, $cursorId)
        );
    }
}
