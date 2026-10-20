<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once APPPATH . 'core/MY_Model.php';

class Cash_advance_model extends MY_Model
{
    public function list_by_user($userId, $cursorId = null, $take = null)
    {
        $this->db->select('id, cash_advance_id, user_id, user_name, amount, approved_amount, description, needed_date, created_date, updated_date, status_code, status_name', false)
            ->from(Knet_views::sub('vw_ca', 'V'), false)
            ->where('user_id', $userId);

        if ($cursorId !== null) {
            $this->db->where('id <', $cursorId);
        }

        $this->db->order_by('id', 'DESC');

        if ($take !== null && (int) $take >= 1) {
            $this->db->limit((int) $take + 1);
        }

        return $this->rows();
    }

    public function find_by_batch_id($batchId)
    {
        $this->db->select('id, cash_advance_id, user_id, amount, description, status, kflow_batch_id, generated_pdf_path, final_pdf_path', false)
            ->from('tbl_cash_advance')
            ->where('kflow_batch_id', $batchId);
        return $this->row();
    }

    public function list_attachments($cashAdvanceId)
    {
        $caId = $this->varchar($cashAdvanceId, 20);

        if ($caId !== null && strncmp($caId, 'LQ', 2) === 0) {
            $found = $this->query_rows(
                'SELECT cash_advance_id FROM tbl_liquidation_header WHERE liquidation_id = ?',
                array($caId)
            );
            if (!empty($found)) {
                $last = end($found);
                $caId = $last['cash_advance_id'];
            }
        }

        if ($caId === null) {
            return array();
        }

        $this->db->select('id, cash_advance_id, original_name, file_name, file_path, uploaded_date', false)
            ->from('tbl_cash_advance_attachments')
            ->where('cash_advance_id', $caId);
        return $this->rows();
    }

    public function find_latest_report($userId)
    {
        $this->select_reports($userId);
        $this->db->limit(1);
        return $this->row();
    }

    public function list_reports($userId)
    {
        $this->select_reports($userId);
        return $this->rows();
    }

    private function select_reports($userId)
    {
        $this->convert(array(array($userId, 'int')));
        $this->db->select("A.cash_advance_id, A.amount, A.status, A.description, A.needed_date, COALESCE(PT.firstname + ' ' + PT.lastname, A.payable_to) AS payable_to, A.amount_in_words, A.approved_amount, A.approved_amount_in_words, A.io_number, A.cost_center_id", false)
            ->from('tbl_cash_advance A')
            ->join('BigEUsers.dbo.users PT', 'TRY_CAST(A.payable_to AS INT) = PT.id', 'left', false)
            ->order_by('A.id', 'DESC');
    }

    public function get_petty_cash_data($cashAdvanceId)
    {
        $this->db->select("A.cash_advance_id AS reference_no, D.lastname + ', ' + D.firstname AS requester_name, E.short_name AS department_name, ISNULL(A.approved_amount, A.amount) AS total_amount, A.description AS purpose, A.created_date", false)
            ->from('dbo.tbl_cash_advance A')
            ->join('BigEUsers.dbo.users D', 'A.user_id = D.id', 'inner')
            ->join('BigEHRIS.dbo.department E', 'D.department_id = E.department_id', 'inner')
            ->where('A.cash_advance_id', $this->varchar($cashAdvanceId, 20));
        return $this->row();
    }

    public function find_pending_by_user($userId)
    {
        $converted = $this->convert(array(array($userId, 'int')));
        $this->db->select('TOP 1 A.cash_advance_id, B.status_code, B.status_name, A.amount, A.created_date, A.needed_date, A.description', false)
            ->from('tbl_cash_advance A')
            ->join('tbl_status B', 'A.status = B.status_code', 'inner')
            ->where('A.user_id', $converted[0])
            ->where_not_in('B.status_code', array('CA_COMPLETED', 'CA_REJECTED', 'CA_CANCELLED'))
            ->order_by('created_date', 'DESC');
        return $this->row();
    }

    public function get_details($cashAdvanceId)
    {
        $this->db->select('id, cash_advance_id, user_id, user_name, amount, description, needed_date, created_date, updated_date, status_code, status_name, final_pdf_path, generated_pdf_path, is_kflow_submitted, kflow_doc_status, kflow_doc_id, payable_to, address, cost_center_id, cost_center_name, io_number, approved_amount', false)
            ->from(Knet_views::sub('vw_ca', 'V'), false)
            ->where('cash_advance_id', $this->varchar($cashAdvanceId, 20));
        return $this->row();
    }

    public function list_unliquidated_by_user($userId)
    {
        $converted = $this->convert(array(array($userId, 'int')));
        $this->db->select('A.cash_advance_id, A.status_code, A.status_name, ISNULL(A.approved_amount, A.amount) AS approved_amount', false)
            ->from(Knet_views::sub('vw_ca', 'A'), false)
            ->join(Knet_views::sub('vw_lq_header', 'B'), 'A.cash_advance_id = B.cash_advance_id', 'left', false)
            ->where_in('A.status_code', array('CA_APPROVED', 'CA_FOR_LIQUIDATION'))
            ->where('A.user_id', $converted[0])
            ->where('B.cash_advance_id IS NULL', null, false)
            ->order_by('A.id', 'DESC');
        return $this->rows();
    }

    public function list_overdue_liquidations()
    {
        $due = 'ISNULL(A.liquidation_due_date, DATEADD(DAY, 7, CAST(A.updated_date AS DATE)))';
        return $this->query_rows(
            "SELECT
    A.cash_advance_id,
    A.user_id,
    U.lastname + ', ' + U.firstname AS employee_name,
    D.short_name AS department_name,
    A.amount,
    CAST(A.updated_date AS DATE) AS released_date,
    $due AS due_date,
    DATEDIFF(DAY, $due, CAST(GETDATE() AS DATE)) AS days_overdue,
    CASE WHEN $due < CAST(GETDATE() AS DATE) THEN 1 ELSE 0 END AS is_overdue,
    A.due_extension_remarks,
    A.due_extended_date,
    EB.lastname + ', ' + EB.firstname AS due_extended_by_name
FROM dbo.tbl_cash_advance A
INNER JOIN BigEUsers.dbo.users U ON A.user_id = U.id
INNER JOIN BigEHRIS.dbo.department D ON U.department_id = D.department_id
LEFT JOIN BigEUsers.dbo.users EB ON A.due_extended_by = EB.id
WHERE A.status = 'CA_FOR_LIQUIDATION'
    AND NOT EXISTS (SELECT 1 FROM dbo.tbl_liquidation_header L WHERE L.cash_advance_id = A.cash_advance_id)
ORDER BY is_overdue DESC, days_overdue DESC"
        );
    }

    public function create_attachment($cashAdvanceId, $originalName, $fileName, $filePath)
    {
        $this->query_rows(
            'INSERT INTO tbl_cash_advance_attachments (cash_advance_id, original_name, file_name, file_path)
VALUES (CAST(? AS VARCHAR(20)), CAST(? AS NVARCHAR(255)), CAST(? AS NVARCHAR(255)), CAST(? AS NVARCHAR(500)))',
            array($cashAdvanceId, $originalName, $fileName, $filePath)
        );
    }

    public function update_detail_review($referenceNo, $description, $amount, $updatedBy, $approvedAmount = null, $approvedAmountInWords = null)
    {
        $this->convert(array(array($amount, 'decimal'), array($updatedBy, 'int'), array($approvedAmount, 'decimal')));
        $this->query_rows(
            "UPDATE tbl_cash_advance
SET [description] = CAST(? AS NVARCHAR(MAX)),
    [updated_by] = CAST(? AS INT),
    [updated_date] = GETDATE(),
    [approved_amount] = CASE WHEN CAST(? AS DECIMAL(18,2)) IS NOT NULL THEN CAST(? AS DECIMAL(18,2)) ELSE [approved_amount] END,
    [approved_amount_in_words] = CASE WHEN CAST(? AS NVARCHAR(MAX)) IS NOT NULL AND CAST(? AS NVARCHAR(MAX)) <> '' THEN CAST(? AS NVARCHAR(MAX)) ELSE [approved_amount_in_words] END
WHERE [cash_advance_id] = CAST(? AS NVARCHAR(50))",
            array($description, $updatedBy, $approvedAmount, $approvedAmount, $approvedAmountInWords, $approvedAmountInWords, $approvedAmountInWords, $referenceNo)
        );
    }

    public function update_header_fields($referenceNo, $costCenterId, $payableTo, $address, $updatedBy, $ioNumber)
    {
        $this->convert(array(array($updatedBy, 'int')));
        $this->query_rows(
            'UPDATE tbl_cash_advance
SET cost_center_id = CAST(? AS VARCHAR(50)),
    payable_to = CAST(? AS NVARCHAR(255)),
    address = CAST(? AS NVARCHAR(500)),
    updated_by = CAST(? AS INT),
    updated_date = GETDATE(),
    io_number = CAST(? AS NVARCHAR(50))
WHERE cash_advance_id = CAST(? AS VARCHAR(50))',
            array($costCenterId, $payableTo, $address, $updatedBy, $ioNumber, $referenceNo)
        );
    }

    public function update_kflow_batch($cashAdvanceId, $batchId, $generatedPdfPath, $isSubmitted)
    {
        $this->convert(array(array($isSubmitted, 'bit')));
        $this->query_rows(
            'UPDATE tbl_cash_advance
SET kflow_batch_id = CAST(? AS NVARCHAR(100)),
    generated_pdf_path = CAST(? AS NVARCHAR(500)),
    is_kflow_submitted = CAST(? AS BIT)
WHERE cash_advance_id = CAST(? AS VARCHAR(20))',
            array($batchId, $generatedPdfPath, $isSubmitted, $cashAdvanceId)
        );
    }

    public function update_kflow_status($cashAdvanceId, $docId, $docStatus, $finalPdfPath)
    {
        $this->query_rows(
            'UPDATE tbl_cash_advance
SET kflow_doc_id = CAST(? AS NVARCHAR(100)),
    kflow_doc_status = CAST(? AS NVARCHAR(50)),
    final_pdf_path = CAST(? AS NVARCHAR(500))
WHERE cash_advance_id = CAST(? AS VARCHAR(20))',
            array($docId, $docStatus, $finalPdfPath, $cashAdvanceId)
        );
    }

    public function extend_liquidation_due($cashAdvanceId, $newDueDate, $remarks, $userId)
    {
        $this->convert(array(array($newDueDate, 'date'), array($userId, 'int')));
        $caId = $this->varchar($cashAdvanceId, 20);
        $remarks = $this->varchar($remarks, 500);

        $exists = $this->query_rows(
            "SELECT TOP 1 1 AS found FROM dbo.tbl_cash_advance WHERE cash_advance_id = ? AND status = 'CA_FOR_LIQUIDATION'",
            array($caId)
        );
        if (empty($exists)) {
            $this->fail('Cash advance not found or is not awaiting liquidation.');
        }

        $future = $this->query_row(
            'SELECT CASE WHEN CAST(? AS DATE) > CAST(GETDATE() AS DATE) THEN 1 ELSE 0 END AS ok',
            array($newDueDate)
        );
        if ($newDueDate === null || (int) $future['ok'] !== 1) {
            $this->fail('New due date must be a future date.');
        }

        if ($remarks === null || trim((string) $remarks, ' ') === '') {
            $this->fail('Remarks are required when extending a due date.');
        }

        $this->query_rows(
            'UPDATE dbo.tbl_cash_advance
SET liquidation_due_date = CAST(? AS DATE),
    due_extension_remarks = CAST(? AS VARCHAR(500)),
    due_extended_by = CAST(? AS INT),
    due_extended_date = GETDATE()
WHERE cash_advance_id = ?',
            array($newDueDate, $remarks, $userId, $caId)
        );

        return $this->query_row(
            'SELECT cash_advance_id, liquidation_due_date FROM dbo.tbl_cash_advance WHERE cash_advance_id = ?',
            array($caId)
        );
    }

    private function convert(array $specs)
    {
        $out = array();
        $pending = array();
        foreach ($specs as $i => $spec) {
            $value = $spec[0];
            $type = $spec[1];
            $out[$i] = $value;
            if ($value === null || is_bool($value)) {
                continue;
            }
            if (is_float($value)) {
                if ($type === 'decimal' && abs(round($value, 2)) >= 1e16) {
                    $pending[$i] = array('float', null);
                }
                if ($type === 'int') {
                    if ($value > -2147483649.0 && $value < 2147483648.0) {
                        $out[$i] = (int) $value;
                    } else {
                        $pending[$i] = array(stripos((string) $value, 'E') !== false ? 'float' : 'numeric', null);
                    }
                }
                continue;
            }
            if (!is_string($value)) {
                continue;
            }
            if ($type === 'int') {
                if (preg_match('/^ *([+-]?) *([0-9]*) *$/', $value, $m) !== 1) {
                    $pending[$i] = array('varchar', null);
                    continue;
                }
                $digits = ltrim($m[2], '0');
                $limit = $m[1] === '-' ? '2147483648' : '2147483647';
                if (strlen($digits) > 10 || (strlen($digits) === 10 && strcmp($digits, $limit) > 0)) {
                    $pending[$i] = array('varchar', null);
                    continue;
                }
                $out[$i] = $digits === '' ? 0 : (int) ($m[1] . $digits);
                continue;
            }
            $sqlType = $type === 'decimal' ? 'DECIMAL(18,2)' : strtoupper($type);
            $pending[$i] = array('varchar', 'CASE WHEN TRY_CAST(? AS ' . $sqlType . ') IS NULL THEN 0 ELSE 1 END');
        }

        $binds = array();
        $columns = array();
        foreach ($pending as $i => $check) {
            if ($check[1] !== null) {
                $columns[] = $check[1] . ' AS c' . $i;
                $binds[] = $specs[$i][0];
            }
        }
        $valid = array();
        if (!empty($columns)) {
            $valid = $this->query_row('SELECT ' . implode(', ', $columns), $binds);
        }

        foreach ($pending as $i => $check) {
            if ($check[1] !== null && (int) $valid['c' . $i] === 1) {
                continue;
            }
            $this->fail('Error converting data type ' . $check[0] . ' to ' . $specs[$i][1] . '.', 8114);
        }

        return $out;
    }

    private function varchar($value, $length)
    {
        if ($value === null) {
            return null;
        }
        return mb_substr((string) $value, 0, $length, 'UTF-8');
    }
}
