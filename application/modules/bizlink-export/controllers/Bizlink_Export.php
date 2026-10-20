<?php

require 'vendor/autoload.php';
(defined('BASEPATH')) or exit('No direct script access allowed');

class Bizlink_Export extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('SPModel', 'sp');
        $this->sp->setDatabase('dbknet');
        $this->load->model('Approval_model');
        $this->load->model('Bizlink_model');
    }

    public function index()
    {
        $data = array(
            'title' => 'BizLink Export',
            'main_view' => '../modules/bizlink-export/views/index',
            'module_group' => $this->module_group,
            'module' => $this->module,
            'scripts' => array('index.js'),
        );
        $this->load->view('main', $data);
    }

    public function api_get_header()
    {
        try {
            $this->output->set_content_type('application/json');
            $userId = $this->session->userdata('user_id');
            $cursorIdRaw = $this->input->post('CursorId');
            $take = $this->resolvePaginationTake($this->input->post('Take'));

            $cursorId = null;
            if ($cursorIdRaw !== null && $cursorIdRaw !== '') {
                $cursorId = (int) $cursorIdRaw;
            }

            $result = $this->Approval_model->list_past_headers($userId, $cursorId, $take);

            $payload = $this->buildPaginationResult($result, $take, 'approval_detail_id');

            echo json_encode(array(
                'status' => 'success',
                'data' => $payload['data'],
                'pagination' => $payload['pagination'],
            ));
        } catch (Exception $e) {
            echo json_encode(array(
                'status' => 'error',
                'response' => "An error occurred: " . $e->getMessage(),
            ));
        }
    }

    private function userHasBizlinkExportCapability($referenceNo, $userId)
    {
        $row = $this->sp->db->select('D.id')
            ->from('tbl_approval_matrix_details D')
            ->join('tbl_approval_header H', 'H.approval_matrix_id = D.matrix_header_id')
            ->where('H.reference_id', $referenceNo)
            ->where('H.is_active', 1)
            ->where('D.approver_id', (int) $userId)
            ->where('D.is_bizlink_export', 1)
            ->limit(1)
            ->get()
            ->row_array();

        return !empty($row);
    }

    private function findActiveBizlinkExportForReference($referenceNo)
    {
        return $this->sp->db->select('B.id, B.payroll_date, B.batch_number')
            ->from('tbl_bizlink_export_batch_line L')
            ->join('tbl_bizlink_export_batch B', 'B.id = L.batch_id')
            ->where('L.reference_no', $referenceNo)
            ->where('B.is_void', 0)
            ->limit(1)
            ->get()
            ->row_array();
    }

    public function api_bizlink_export_eligibility()
    {
        try {
            $this->output->set_content_type('application/json');
            $data = $this->getRequestPayload();

            $referenceNumbers = isset($data['reference_numbers']) && is_array($data['reference_numbers'])
                ? $data['reference_numbers']
                : array();

            if (count($referenceNumbers) === 0) {
                throw new Exception('No transactions selected.');
            }

            $userId = (int) $this->session->userdata('user_id');
            if ($userId <= 0) {
                throw new Exception('User not authenticated.');
            }

            $allowed = array();
            $skipped = array();
            $alreadyExported = array();

            foreach ($referenceNumbers as $referenceNo) {
                $referenceNo = trim((string) $referenceNo);
                if ($referenceNo === '') {
                    continue;
                }

                if (!$this->userHasBizlinkExportCapability($referenceNo, $userId)) {
                    $skipped[] = $referenceNo;
                    continue;
                }

                $existing = $this->findActiveBizlinkExportForReference($referenceNo);
                if (!empty($existing)) {
                    $alreadyExported[] = $referenceNo;
                    continue;
                }

                $allowed[] = $referenceNo;
            }

            return $this->respondSuccess('OK', array(
                'allowed' => $allowed,
                'skipped' => $skipped,
                'already_exported' => $alreadyExported,
            ));
        } catch (Throwable $e) {
            return $this->respondError($e->getMessage());
        }
    }

    private function bizlinkPad($value, $length)
    {
        if (is_float($value)) {
            $value = sprintf('%.0f', $value);
        }
        return str_pad((string) $value, $length, '0', STR_PAD_LEFT);
    }

    private function bizlinkPadFixed($label, $value, $length)
    {
        $padded = $this->bizlinkPad($value, $length);
        if (strlen($padded) > $length) {
            throw new Exception($label . ' is too long for the BizLink file format (max ' . $length . ' digits, got "' . $padded . '"). Please correct it before generating.');
        }
        return $padded;
    }

    private function bizlinkNormalizeAccountNumber($accountNumber)
    {
        $accountNumber = $this->bizlinkPad(preg_replace('/\D/', '', (string) $accountNumber), 10);
        if (strlen($accountNumber) > 10) {
            $accountNumber = substr($accountNumber, -10);
        }
        return $accountNumber;
    }

    private function bizlinkApplySavingsDigitSwap($accountNumber)
    {
        $accountNumber = $this->bizlinkNormalizeAccountNumber($accountNumber);
        if ($accountNumber[3] === '5') {
            $accountNumber[3] = '6';
        }
        return $accountNumber;
    }

    private function bizlinkAmountToCents($amount)
    {
        return round(((float) $amount) * 100);
    }

    private function bizlinkExportError($message)
    {
        $this->output->set_content_type('application/json');
        echo json_encode(array('status' => 'error', 'response' => $message));
    }

    public function download_bizlink_export_batch()
    {
        $data = $this->getRequestPayload();

        $referenceNumbers = isset($data['reference_numbers']) && is_array($data['reference_numbers'])
            ? $data['reference_numbers']
            : array();
        $payrollDateRaw = isset($data['PayrollDate']) ? trim((string) $data['PayrollDate']) : '';
        $batchNumberRaw = isset($data['BatchNumber']) ? trim((string) $data['BatchNumber']) : '';

        $userId = (int) $this->session->userdata('user_id');
        if ($userId <= 0) {
            $this->bizlinkExportError('User not authenticated.');
            return;
        }

        $eligible = array();
        $alreadyExported = array();
        foreach ($referenceNumbers as $referenceNo) {
            $referenceNo = trim((string) $referenceNo);
            if ($referenceNo === '' || !$this->userHasBizlinkExportCapability($referenceNo, $userId)) {
                continue;
            }
            if (!empty($this->findActiveBizlinkExportForReference($referenceNo))) {
                $alreadyExported[] = $referenceNo;
                continue;
            }
            $eligible[] = $referenceNo;
        }

        if (count($alreadyExported) > 0) {
            $this->bizlinkExportError('These transactions were already included in a previous BizLink batch and cannot be exported again: ' . implode(', ', $alreadyExported) . '. Void that batch first if you need to regenerate it.');
            return;
        }

        if (count($eligible) === 0) {
            $this->bizlinkExportError('You are not assigned to generate the text file for the selected transaction(s). Please ask your administrator to enable the BizLink Export control for you on the approval matrix used by these transactions.');
            return;
        }

        $payrollDate = DateTime::createFromFormat('Y-m-d', $payrollDateRaw);
        if (!$payrollDate) {
            $payrollDate = DateTime::createFromFormat('m/d/Y', $payrollDateRaw);
        }
        if (!$payrollDate) {
            $this->bizlinkExportError('A valid Payroll Date is required.');
            return;
        }

        $batchNumber = (int) $batchNumberRaw;
        if ($batchNumber < 1 || $batchNumber > 99) {
            $this->bizlinkExportError('Batch Number must be between 01 and 99.');
            return;
        }
        $batchNumberStr = $this->bizlinkPad($batchNumber, 2);

        $existingBatch = $this->sp->db->select('id, generated_by, generated_date')
            ->from('tbl_bizlink_export_batch')
            ->where('payroll_date', $payrollDate->format('Y-m-d'))
            ->where('batch_number', $batchNumberStr)
            ->where('is_void', 0)
            ->limit(1)
            ->get()
            ->row_array();
        if (!empty($existingBatch)) {
            $this->bizlinkExportError('Batch ' . $batchNumberStr . ' for ' . $payrollDate->format('m/d/Y') . ' has already been generated. Void the existing batch first if you need to regenerate it.');
            return;
        }

        $company = $this->Bizlink_model->get_company_settings();
        if (!is_array($company) || empty($company['id']) || empty($company['company_account_number'])) {
            $this->bizlinkExportError('Company / BizLink Settings must be configured before generating the text file.');
            return;
        }

        $lines = array();
        $missingAccount = array();
        foreach ($eligible as $referenceNo) {
            $line = $this->Bizlink_model->get_batch_line($referenceNo);

            if (!is_array($line) || empty($line['account_number'])) {
                $missingAccount[] = $referenceNo;
                continue;
            }

            $lines[] = $line;
        }

        if (count($missingAccount) > 0) {
            $this->bizlinkExportError('No active bank account on file for: ' . implode(', ', $missingAccount) . '. Please add it in the Bank Account Masterlist first.');
            return;
        }

        try {
            $companyCode = $this->bizlinkPadFixed('Company Code', preg_replace('/\D/', '', (string) $company['company_code']), 5);
            $companyAccountNumber = $this->bizlinkNormalizeAccountNumber(bank_account_decrypt($company['company_account_number']));
            $presentingOfficeCode = $this->bizlinkPadFixed('Presenting Office Code', preg_replace('/\D/', '', (string) $company['presenting_office_code']), 3);
            $bpiPayrollIdentifier = $company['bpi_payroll_identifier'] !== null && $company['bpi_payroll_identifier'] !== ''
                ? (string) $company['bpi_payroll_identifier']
                : '1';

            $payrollDateStr = $payrollDate->format('mdy');

            $detailRecords = array();
            $totalDebitCents = 0;
            $ceilingAmountCents = 0;
            $accountHashTotal = 0;
            $grandHorizontalHashTotal = 0;

            foreach ($lines as $line) {
                $employeeAccountNumber = $this->bizlinkApplySavingsDigitSwap(bank_account_decrypt($line['account_number']));
                $amountCents = $this->bizlinkAmountToCents($line['amount']);

                $horizontalHash =
                    ((int) substr($employeeAccountNumber, 4, 2)) * $amountCents +
                    ((int) substr($employeeAccountNumber, 6, 2)) * $amountCents +
                    ((int) substr($employeeAccountNumber, 8, 2)) * $amountCents;

                $detailRecords[] = 'D'
                    . $companyCode
                    . $payrollDateStr
                    . $batchNumberStr
                    . '3'
                    . $employeeAccountNumber
                    . $this->bizlinkPadFixed('Transaction Amount', $amountCents, 12)
                    . $this->bizlinkPadFixed('Horizontal Hash', $horizontalHash, 12)
                    . str_repeat(' ', 79);

                $totalDebitCents += $amountCents;
                $ceilingAmountCents = max($ceilingAmountCents, $amountCents);
                $accountHashTotal += (float) $employeeAccountNumber;
                $grandHorizontalHashTotal += $horizontalHash;
            }

            $header = 'H'
                . $companyCode
                . $payrollDateStr
                . $batchNumberStr
                . '1'
                . $companyAccountNumber
                . $presentingOfficeCode
                . $this->bizlinkPadFixed('Ceiling Amount', $ceilingAmountCents, 12)
                . $this->bizlinkPadFixed('Debit Amount', $totalDebitCents, 12)
                . $bpiPayrollIdentifier
                . str_repeat(' ', 75);

            $trailer = 'T'
                . $companyCode
                . $payrollDateStr
                . $batchNumberStr
                . '2'
                . $companyAccountNumber
                . $this->bizlinkPadFixed('Account Number Hash Total', $accountHashTotal, 15)
                . $this->bizlinkPadFixed('Transaction Amount Hash Total', $totalDebitCents, 15)
                . $this->bizlinkPadFixed('Grand Horizontal Hash Total', $grandHorizontalHashTotal, 18)
                . $this->bizlinkPadFixed('Record Count', count($detailRecords), 5)
                . str_repeat(' ', 50);

            $content = $header . "\r\n" . implode("\r\n", $detailRecords) . "\r\n" . $trailer . "\r\n";
        } catch (Throwable $e) {
            $this->bizlinkExportError($e->getMessage());
            return;
        }

        try {
            $batchId = $this->Bizlink_model->create_export_batch(
                $companyCode,
                $payrollDate->format('Y-m-d'),
                $batchNumberStr,
                count($detailRecords),
                $totalDebitCents / 100,
                $userId
            );
            if (empty($batchId)) {
                throw new Exception('Failed to record the generated batch.');
            }

            foreach ($eligible as $referenceNo) {
                $this->Bizlink_model->add_export_batch_line((int) $batchId, $referenceNo);
            }
        } catch (Throwable $e) {
            $this->bizlinkExportError($e->getMessage());
            return;
        }

        $this->logAuditTrail(
            'BIZLINK_EXPORT',
            $companyCode . '-' . $payrollDateStr . '-' . $batchNumberStr,
            'GENERATE',
            'bizlink_export',
            $companyCode . '-' . $payrollDateStr . '-' . $batchNumberStr,
            null,
            null,
            'BizLink text file generated for ' . count($detailRecords) . ' transaction(s) by user #' . $userId . ': ' . implode(', ', $eligible)
        );

        $filename = 'BizLink_' . $companyCode . '_' . $payrollDateStr . '_' . $batchNumberStr . '.txt';

        $this->output
            ->set_content_type('text/plain')
            ->set_header('Content-Disposition: attachment; filename="' . $filename . '"')
            ->set_output($content);
    }

    public function api_fetch_bizlink_export_batches()
    {
        try {
            $this->output->set_content_type('application/json');

            $userId = (int) $this->session->userdata('user_id');

            $rows = $this->Bizlink_model->list_export_batches(50);
            $rows = is_array($rows) ? $rows : array();

            foreach ($rows as &$row) {
                $row['can_void'] = empty($row['is_void']) && (int) $row['generated_by'] === $userId;
            }
            unset($row);

            return $this->respondSuccess('OK', $rows);
        } catch (Throwable $e) {
            return $this->respondError('An error occurred: ' . $e->getMessage());
        }
    }

    public function api_void_bizlink_export_batch()
    {
        try {
            $this->output->set_content_type('application/json');
            $data = $this->getRequestPayload();

            $userId = (int) $this->session->userdata('user_id');
            if ($userId <= 0) {
                throw new Exception('User not authenticated.');
            }

            $batchId = isset($data['BatchId']) ? (int) $data['BatchId'] : 0;
            $reason = isset($data['Reason']) ? trim((string) $data['Reason']) : '';

            if ($batchId <= 0) {
                throw new Exception('Invalid batch.');
            }
            if ($reason === '') {
                throw new Exception('A reason is required to void a batch.');
            }

            $voidedId = $this->Bizlink_model->void_export_batch($batchId, $userId, $reason);

            if (empty($voidedId)) {
                throw new Exception('Failed to void the batch.');
            }

            $this->logAuditTrail(
                'BIZLINK_EXPORT',
                (string) $batchId,
                'VOID',
                'bizlink_export_batch',
                (string) $batchId,
                null,
                null,
                'BizLink batch voided by user #' . $userId . ': ' . $reason
            );

            return $this->respondSuccess('Batch voided.', array('id' => $batchId));
        } catch (Throwable $e) {
            return $this->respondError($e->getMessage());
        }
    }
}
