<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once APPPATH . 'core/MY_Model.php';

class Revolving_fund_model extends MY_Model
{
    public function list_funds($cursorId = null, $take = null, $keyword = null, $scopeType = null, $status = null)
    {
        $this->assert_convertible(array(array($cursorId, 'bigint'), array($take, 'int')));
        $this->assert_take_fits($take);

        $where = array();
        $binds = array();
        if ($cursorId !== null) {
            $where[] = 'V.id < CAST(? AS BIGINT)';
            $binds[] = $cursorId;
        }
        if ($scopeType !== null) {
            $where[] = "(CAST(? AS VARCHAR(20)) = '' OR V.scope_type = CAST(? AS VARCHAR(20)))";
            $binds[] = $scopeType;
            $binds[] = $scopeType;
        }
        if ($status !== null) {
            $where[] = "(CAST(? AS VARCHAR(20)) = '' OR V.status = CAST(? AS VARCHAR(20)))";
            $binds[] = $status;
            $binds[] = $status;
        }
        if ($keyword !== null) {
            $where[] = "(CAST(? AS VARCHAR(200)) = '' OR V.fund_code LIKE '%' + CAST(? AS VARCHAR(200)) + '%' OR V.scope_name LIKE '%' + CAST(? AS VARCHAR(200)) + '%')";
            $binds[] = $keyword;
            $binds[] = $keyword;
            $binds[] = $keyword;
        }

        return $this->query_rows(
            'SELECT ' . $this->top_clause($take) . 'V.* FROM ' . Knet_views::sub('vw_rf_fund_list', 'V')
            . $this->where_clause($where) . ' ORDER BY V.id DESC',
            $binds
        );
    }

    public function list_ledger_entries($cursorId = null, $take = 20, $fundId = null, $trxType = null, $dateFrom = null, $dateTo = null)
    {
        $this->assert_convertible(array(array($cursorId, 'bigint'), array($take, 'int'), array($fundId, 'bigint'), array($dateFrom, 'date'), array($dateTo, 'date')));

        if ($take === null || (int) $take < 1) {
            $take = 20;
        }
        if ((int) $take > 200) {
            $take = 200;
        }

        $where = array();
        $binds = array();
        if ($cursorId !== null) {
            $where[] = 'V.id < CAST(? AS BIGINT)';
            $binds[] = $cursorId;
        }
        if ($fundId !== null) {
            $where[] = 'V.fund_id = CAST(? AS BIGINT)';
            $binds[] = $fundId;
        }
        if ($trxType !== null) {
            $where[] = "(CAST(? AS VARCHAR(30)) = '' OR V.trx_type = CAST(? AS VARCHAR(30)))";
            $binds[] = $trxType;
            $binds[] = $trxType;
        }
        if ($dateFrom !== null) {
            $where[] = 'V.trx_date >= CAST(? AS DATE)';
            $binds[] = $dateFrom;
        }
        if ($dateTo !== null) {
            $where[] = 'V.trx_date <= CAST(? AS DATE)';
            $binds[] = $dateTo;
        }

        return $this->query_rows(
            'SELECT ' . $this->top_clause($take) . 'V.* FROM ' . Knet_views::sub('vw_rf_ledger_list', 'V')
            . $this->where_clause($where) . ' ORDER BY V.id DESC',
            $binds
        );
    }

    public function get_ledger_report($fundCode = null, $dateFrom = null, $dateTo = null, $cursorId = null, $take = null)
    {
        $this->assert_convertible(array(array($dateFrom, 'date'), array($dateTo, 'date'), array($cursorId, 'bigint'), array($take, 'int')));
        $this->assert_take_fits($take);

        $where = array();
        $binds = array();
        if ($fundCode !== null) {
            $where[] = 'L.fund_code = CAST(? AS VARCHAR(50))';
            $binds[] = $fundCode;
        }
        if ($dateFrom !== null) {
            $where[] = 'L.trx_date >= CAST(? AS DATE)';
            $binds[] = $dateFrom;
        }
        if ($dateTo !== null) {
            $where[] = 'L.trx_date <= CAST(? AS DATE)';
            $binds[] = $dateTo;
        }
        if ($cursorId !== null) {
            $where[] = 'L.id < CAST(? AS BIGINT)';
            $binds[] = $cursorId;
        }

        $sql = 'SELECT ' . $this->top_clause($take)
            . "L.id, L.fund_id, L.fund_code, L.fund_scope_name, L.trx_date, L.trx_type, L.trx_type_name,
    L.amount, L.balance_after,
    L.reference_type,
    COALESCE(CA.cash_advance_id, RMB.reimbursement_id) AS reference_no,
    COALESCE(CA.user_name, RMB.user_name, L.created_by_name) AS employee_name,
    COALESCE(UA.designation, UR.designation) AS employee_position,
    L.remarks,
    L.created_by, L.created_by_name, L.created_date
FROM " . Knet_views::sub('vw_rf_ledger_list', 'L') . "
LEFT JOIN " . Knet_views::sub('vw_ca', 'CA') . " ON L.reference_type = 'CASH_ADVANCE' AND CA.id = L.reference_id
LEFT JOIN " . Knet_views::sub('vw_rmb_header', 'RMB') . " ON L.reference_type = 'REIMBURSEMENT' AND RMB.id = L.reference_id
LEFT JOIN BigEUsers.dbo.users UA ON L.reference_type = 'CASH_ADVANCE' AND UA.id = CA.user_id
LEFT JOIN BigEUsers.dbo.users UR ON L.reference_type = 'REIMBURSEMENT' AND UR.id = RMB.user_id"
            . $this->where_clause($where) . ' ORDER BY L.id DESC';

        return $this->query_rows($sql, $binds);
    }

    public function find_active_fund_for_user($userId)
    {
        $this->assert_convertible(array(array($userId, 'int')));

        return $this->query_row(
            "SELECT TOP 1 F.id, F.fund_code, F.available_balance, F.opening_balance, F.allow_negative_balance, F.allow_self_cash_in
FROM dbo.tbl_revolving_fund_assignment A
INNER JOIN dbo.tbl_revolving_fund F ON A.fund_id = F.id
WHERE A.assignee_type = 'PERSON'
    AND A.assignee_id = CAST(? AS INT)
    AND A.status = 'RF_ASSIGN_ACTIVE'
    AND F.status = 'RF_ACTIVE'
    AND A.effective_date <= CAST(GETDATE() AS DATE)
    AND (A.end_date IS NULL OR A.end_date >= CAST(GETDATE() AS DATE))
ORDER BY A.id DESC",
            array($userId)
        );
    }

    private function top_clause($take)
    {
        if ($take === null || (int) $take < 1) {
            return '';
        }
        return 'TOP (' . ((int) $take + 1) . ') ';
    }

    private function assert_take_fits($take)
    {
        if ($take !== null && (int) $take >= 2147483647) {
            $this->fail('Arithmetic overflow error converting expression to data type int.', 8115);
        }
    }

    private function assert_convertible(array $checks)
    {
        $types = array('int' => 'INT', 'bigint' => 'BIGINT', 'date' => 'DATE');
        $select = array();
        $binds = array();
        $names = array();
        foreach ($checks as $check) {
            if (!is_string($check[0])) {
                continue;
            }
            $select[] = 'CASE WHEN TRY_CAST(CAST(? AS VARCHAR(8000)) AS ' . $types[$check[1]] . ') IS NULL THEN 0 ELSE 1 END AS c' . count($names);
            $binds[] = $check[0];
            $names[] = $check[1];
        }
        if (empty($names)) {
            return;
        }
        $row = $this->query_row('SELECT ' . implode(', ', $select), $binds);
        foreach ($names as $i => $name) {
            if ((int) $row['c' . $i] === 0) {
                $this->fail('Error converting data type varchar to ' . $name . '.', 8114);
            }
        }
    }

    private function where_clause(array $where)
    {
        return empty($where) ? '' : ' WHERE ' . implode(' AND ', $where);
    }
}
