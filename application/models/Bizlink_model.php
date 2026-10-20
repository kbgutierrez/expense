<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once APPPATH . 'core/MY_Model.php';

class Bizlink_model extends MY_Model
{
    public function get_batch_line($referenceNo)
    {
        $referenceNo = $this->clip($referenceNo, 20);

        $source = null;
        if ($referenceNo !== null) {
            if (strncmp($referenceNo, 'CA', 2) === 0) {
                $source = array('CASH_ADVANCE', 'tbl_cash_advance', 'cash_advance_id', 'user_id', 'amount');
            } elseif (strncmp($referenceNo, 'LQ', 2) === 0) {
                $source = array('LIQUIDATION', 'tbl_liquidation_header', 'liquidation_id', 'created_by', 'total_amount_spent');
            } elseif (strncmp($referenceNo, 'RMB', 3) === 0) {
                $source = array('REIMBURSEMENT', 'tbl_reimbursement_header', 'reimbursement_id', 'created_by', 'total_amount');
            } elseif (strncmp($referenceNo, 'RPL', 3) === 0) {
                $source = array('REPLENISHMENT', 'tbl_replenishment_header', 'replenishment_id', 'user_id', 'total_amount');
            }
        }

        $transactionType = null;
        $userId = null;
        $amount = null;
        if ($source !== null) {
            $found = $this->query_rows(
                'SELECT ' . $source[3] . ' AS user_id, CAST(' . $source[4] . ' AS DECIMAL(18, 2)) AS amount FROM dbo.' . $source[1] . ' WHERE ' . $source[2] . ' = ?',
                array($referenceNo)
            );
            foreach ($found as $row) {
                $transactionType = $source[0];
                $userId = $row['user_id'] === null ? null : (int) $row['user_id'];
                $amount = $row['amount'];
            }
        }

        $approved = $this->query_row(
            'SELECT ISNULL(' . Knet_functions::fn_approved_amount($this->db->escape($referenceNo)) . ', CAST(? AS DECIMAL(18, 2))) AS amount',
            array($amount)
        );
        $amount = $approved['amount'];

        $account = null;
        if ($userId !== null) {
            $this->db->select('empcode, account_number', false)
                ->from('dbo.tbl_bank_account_masterlist')
                ->where('lsbiz_id', (int) $userId)
                ->where('is_active', 1);
            $account = $this->row();
        }

        return array(
            'reference_no' => $referenceNo,
            'transaction_type' => $transactionType,
            'user_id' => $userId,
            'amount' => $amount,
            'empcode' => $account === null ? null : $account['empcode'],
            'account_number' => $account === null ? null : $account['account_number'],
        );
    }

    public function get_company_settings()
    {
        $this->db->select('TOP 1 id, company_code, company_account_number, presenting_office_code, bpi_payroll_identifier, updated_by, updated_date', false)
            ->from('dbo.tbl_bizlink_company_settings')
            ->order_by('id', 'DESC');
        return $this->row();
    }

    public function list_export_batches($take = 50)
    {
        $take = $take === null ? 50 : (int) $take;
        if ($take < 0) {
            $this->fail('A TOP N or FETCH rowcount value may not be negative.', 127);
        }
        return $this->query_rows(
            'SELECT TOP (' . $take . ')
                B.id, B.company_code, B.payroll_date, B.batch_number, B.record_count, B.total_debit_amount,
                B.is_void, B.void_reason, B.void_date,
                B.generated_by, GU.lastname + \', \' + GU.firstname AS generated_by_name, B.generated_date,
                B.void_by, VU.lastname + \', \' + VU.firstname AS void_by_name
            FROM dbo.tbl_bizlink_export_batch B
            INNER JOIN BigEUsers.dbo.users GU ON B.generated_by = GU.id
            LEFT JOIN BigEUsers.dbo.users VU ON B.void_by = VU.id
            ORDER BY B.generated_date DESC'
        );
    }

    public function save_company_settings($companyCode, $encryptedAccountNumber, $presentingOfficeCode, $userId)
    {
        list($companyCode, $encrypted, $officeCode, $userId) = $this->typed(
            array($companyCode, $encryptedAccountNumber, $presentingOfficeCode, $userId),
            array(
                'CompanyCode' => 'VARCHAR(10)',
                'CompanyAccountNumberEncrypted' => 'VARCHAR(500)',
                'PresentingOfficeCode' => 'VARCHAR(20)',
                'UserId' => 'INT',
            )
        );

        $existing = $this->query_row('SELECT TOP 1 id FROM dbo.tbl_bizlink_company_settings ORDER BY id DESC');

        if ($existing !== null) {
            $existingId = $existing['id'];
            $this->write(function ($db) use ($existingId, $companyCode, $encrypted, $officeCode, $userId) {
                $db->set('company_code', $companyCode)
                    ->set('company_account_number', $encrypted)
                    ->set('presenting_office_code', $officeCode)
                    ->set('updated_by', $userId)
                    ->set('updated_date', 'GETDATE()', false)
                    ->where('id', $existingId);
                return $db->update('dbo.tbl_bizlink_company_settings');
            });
            return array('id' => $existingId, 'action' => 'UPDATED');
        }

        $this->write(function ($db) use ($companyCode, $encrypted, $officeCode, $userId) {
            $db->set('company_code', $companyCode)
                ->set('company_account_number', $encrypted)
                ->set('presenting_office_code', $officeCode)
                ->set('bpi_payroll_identifier', '1')
                ->set('created_by', $userId)
                ->set('created_date', 'GETDATE()', false);
            return $db->insert('dbo.tbl_bizlink_company_settings');
        });
        return array('id' => $this->insert_id(), 'action' => 'INSERTED');
    }

    public function create_export_batch($companyCode, $payrollDate, $batchNumber, $recordCount, $totalDebitAmount, $userId)
    {
        list($companyCode, $payrollDate, $batchNumber, $recordCount, $totalDebit, $userId) = $this->typed(
            array($companyCode, $payrollDate, $batchNumber, $recordCount, $totalDebitAmount, $userId),
            array(
                'CompanyCode' => 'VARCHAR(10)',
                'PayrollDate' => 'DATE',
                'BatchNumber' => 'VARCHAR(2)',
                'RecordCount' => 'INT',
                'TotalDebitAmount' => 'DECIMAL(18, 2)',
                'UserId' => 'INT',
            )
        );

        $duplicate = $this->query_rows(
            'SELECT TOP 1 1 AS found FROM dbo.tbl_bizlink_export_batch
            WHERE payroll_date = CAST(? AS DATE) AND batch_number = ? AND is_void = 0',
            array($payrollDate, $batchNumber)
        );
        if (!empty($duplicate)) {
            $this->fail('This Payroll Date and Batch Number combination has already been generated. Void the existing batch first if you need to regenerate it.', 51000);
        }

        $this->query_rows(
            'INSERT INTO dbo.tbl_bizlink_export_batch
                (company_code, payroll_date, batch_number, record_count, total_debit_amount, generated_by, generated_date)
            VALUES (?, CAST(? AS DATE), ?, ?, CAST(? AS DECIMAL(18, 2)), ?, GETDATE())',
            array($companyCode, $payrollDate, $batchNumber, $recordCount, $totalDebit, $userId)
        );
        return $this->insert_id();
    }

    public function add_export_batch_line($batchId, $referenceNo)
    {
        list($batchId, $referenceNo) = $this->typed(
            array($batchId, $referenceNo),
            array('BatchId' => 'INT', 'ReferenceNo' => 'VARCHAR(20)')
        );

        $this->write(function ($db) use ($batchId, $referenceNo) {
            return $db->insert('dbo.tbl_bizlink_export_batch_line', array(
                'batch_id' => $batchId,
                'reference_no' => $referenceNo,
            ));
        });
    }

    public function void_export_batch($batchId, $userId, $reason)
    {
        list($batchId, $userId, $reason) = $this->typed(
            array($batchId, $userId, $reason),
            array('BatchId' => 'INT', 'UserId' => 'INT', 'Reason' => 'VARCHAR(500)')
        );

        $generatedBy = null;
        $found = $this->query_rows(
            'SELECT generated_by FROM dbo.tbl_bizlink_export_batch WHERE id = ? AND is_void = 0',
            array($batchId)
        );
        foreach ($found as $row) {
            $generatedBy = $row['generated_by'];
        }

        if ($generatedBy === null) {
            $this->fail('Batch not found or already voided.', 51001);
        }
        if ($userId !== null && (int) $generatedBy !== $userId) {
            $this->fail('Only the user who generated this batch can void it.', 51002);
        }

        $this->write(function ($db) use ($batchId, $userId, $reason) {
            $db->set('is_void', 1)
                ->set('void_reason', $reason)
                ->set('void_by', $userId)
                ->set('void_date', 'GETDATE()', false)
                ->where('id', $batchId);
            return $db->update('dbo.tbl_bizlink_export_batch');
        });
        return $batchId;
    }

    private function typed(array $values, array $decls)
    {
        $select = array();
        $declare = array();
        $marks = array();
        foreach (array_keys($decls) as $name) {
            $select[] = '@' . $name . ' AS ' . $name;
            $declare[] = '@' . $name . ' ' . $decls[$name];
            $marks[] = '?';
        }
        $row = $this->query_row(
            'EXEC sp_executesql N\'SELECT ' . implode(', ', $select) . '\', N\'' . implode(', ', $declare) . '\', ' . implode(', ', $marks),
            array_values($values)
        );
        return array_values($row);
    }

    private function clip($value, $length)
    {
        if ($value === null) {
            return null;
        }
        $value = (string) $value;
        if (preg_match('/[^\x00-\x7F]/', $value)) {
            $row = $this->query_row('SELECT CAST(? AS VARCHAR(' . (int) $length . ')) AS v', array($value));
            return $row['v'];
        }
        return strlen($value) > $length ? substr($value, 0, $length) : $value;
    }
}
