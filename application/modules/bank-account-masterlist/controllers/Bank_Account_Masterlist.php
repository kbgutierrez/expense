<?php

require 'vendor/autoload.php';
(defined('BASEPATH')) or exit('No direct script access allowed');

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class Bank_Account_Masterlist extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('SPModel', 'sp');
        $this->sp->setDatabase('dbknet');
        $this->load->model('Bank_account_model');
        $this->load->model('Bizlink_model');
    }

    public function index()
    {
        $data = array(
            'title' => 'Bank Account Masterlist',
            'main_view' => '../modules/bank-account-masterlist/views/index',
            'module_group' => $this->module_group,
            'module' => $this->module,
            'scripts' => array(
                'index.js',
            ),
        );

        $this->load->view('main', $data);
    }

    private function rowToDisplay($row)
    {
        $plaintext = bank_account_decrypt($row['account_number']);

        return array(
            'id' => (int) $row['id'],
            'lsbiz_id' => $row['lsbiz_id'] !== null ? (int) $row['lsbiz_id'] : null,
            'empcode' => $row['empcode'],
            'fullname' => $row['fullname'],
            'bank_account' => $row['bank_account'],
            'account_number_masked' => bank_account_mask($plaintext),
            'is_active' => (int) $row['is_active'] === 1,
            'created_by' => $row['created_by'],
            'created_date' => $row['created_date'],
            'updated_by' => $row['updated_by'],
            'updated_date' => $row['updated_date'],
        );
    }

    public function api_get()
    {
        try {
            $this->output->set_content_type('application/json');

            $page = max(1, (int) $this->input->post('Page'));
            $pageSize = (int) $this->input->post('PageSize');
            if ($pageSize <= 0) {
                $pageSize = 20;
            }
            $keyword = trim((string) $this->input->post('Keyword'));
            $cursorIdRaw = $this->input->post('CursorId');
            $cursorId = ($cursorIdRaw !== null && $cursorIdRaw !== '') ? (int) $cursorIdRaw : null;

            $result = $this->Bank_account_model->list_masterlist(
                $keyword !== '' ? $keyword : null,
                $cursorId,
                $page,
                $pageSize
            );
            $result = is_array($result) ? $result : array();

            $totalCount = !empty($result) ? (int) $result[0]['total_count'] : 0;
            $totalPages = $totalCount > 0 ? (int) ceil($totalCount / $pageSize) : 1;
            $lastRow = !empty($result) ? end($result) : null;

            echo json_encode(array(
                'status' => 'success',
                'data' => array_map(array($this, 'rowToDisplay'), $result),
                'pagination' => array(
                    'page' => $page,
                    'pageSize' => $pageSize,
                    'totalCount' => $totalCount,
                    'totalPages' => $totalPages,
                    'nextCursorId' => $lastRow ? (int) $lastRow['id'] : null,
                ),
            ));
        } catch (\Throwable $e) {
            echo json_encode(array(
                'status' => 'error',
                'response' => 'An error occurred: ' . $e->getMessage(),
            ));
        }
    }

    public function api_get_employee_options()
    {
        try {
            $this->output->set_content_type('application/json');

            $keyword = trim((string) $this->input->post('Keyword'));

            $rows = $this->Bank_account_model->list_employee_options($keyword !== '' ? $keyword : null);

            return $this->respondSuccess('OK', is_array($rows) ? $rows : array());
        } catch (\Throwable $e) {
            return $this->respondError('An error occurred: ' . $e->getMessage());
        }
    }

    private function saveOne($id, $empcode, $bankAccount, $accountNumber, $isActive, $userId)
    {
        $empcode = trim((string) $empcode);
        $bankAccount = trim((string) $bankAccount);
        $accountNumber = trim((string) $accountNumber);

        if ($empcode === '') {
            throw new \Exception('Employee is required.');
        }
        if ($bankAccount === '') {
            throw new \Exception('Bank account is required.');
        }
        if ($accountNumber === '') {
            throw new \Exception('Account number is required.');
        }
        if (!preg_match('/^[0-9\- ]{4,50}$/', $accountNumber)) {
            throw new \Exception('Account number format looks invalid.');
        }

        $matched = $this->Bank_account_model->find_employee_by_code($empcode);
        if (!is_array($matched) || empty($matched['empcode'])) {
            throw new \Exception('Employee code "' . $empcode . '" was not found among active employees.');
        }

        $encrypted = bank_account_encrypt($accountNumber);

        $params = array(
            'Id' => $id !== null && $id !== '' ? (int) $id : null,
            'LsbizId' => (int) $matched['lsbiz_id'],
            'Empcode' => $matched['empcode'],
            'BankAccount' => $bankAccount,
            'AccountNumberEncrypted' => $encrypted,
            'IsActive' => $isActive ? 1 : 0,
            'UserId' => $userId,
        );

        $result = $this->sp->readData(
            build_sp('sp_save_bank_account_masterlist', count($params)),
            $params,
            'row'
        );

        if (!is_array($result) || empty($result['id'])) {
            throw new \Exception('Failed to save the bank account record.');
        }

        $this->logAuditTrail(
            'BANK_ACCOUNT_MASTERLIST',
            (string) $result['id'],
            $result['action'] === 'INSERTED' ? 'CREATE' : 'UPDATE',
            'bank_account_masterlist',
            (string) $result['id'],
            'account_number',
            null,
            'Bank account on file for ' . $matched['empcode'] . ' (' . $bankAccount . ')'
        );

        return $result;
    }

    public function api_save()
    {
        try {
            $this->output->set_content_type('application/json');
            $data = $this->getRequestPayload();

            $userId = (int) $this->session->userdata('user_id');
            if ($userId <= 0) {
                return $this->respondError('User not authenticated.');
            }

            $result = $this->saveOne(
                isset($data['Id']) ? $data['Id'] : null,
                isset($data['Empcode']) ? $data['Empcode'] : '',
                isset($data['BankAccount']) ? $data['BankAccount'] : '',
                isset($data['AccountNumber']) ? $data['AccountNumber'] : '',
                array_key_exists('IsActive', $data) ? !empty($data['IsActive']) : true,
                $userId
            );

            return $this->respondSuccess(
                $result['action'] === 'INSERTED' ? 'Bank account added.' : 'Bank account updated.',
                array('id' => (int) $result['id'])
            );
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage());
        }
    }

    public function api_reveal()
    {
        try {
            $this->output->set_content_type('application/json');
            $data = $this->getRequestPayload();

            $userId = (int) $this->session->userdata('user_id');
            if ($userId <= 0) {
                return $this->respondError('User not authenticated.');
            }

            $id = isset($data['Id']) ? (int) $data['Id'] : 0;
            if ($id <= 0) {
                return $this->respondError('Invalid record.');
            }

            $row = $this->Bank_account_model->reveal_account_number($id);

            if (!is_array($row) || empty($row['id'])) {
                return $this->respondError('Record not found.');
            }

            $plaintext = bank_account_decrypt($row['account_number']);

            $this->logAuditTrail(
                'BANK_ACCOUNT_MASTERLIST',
                (string) $id,
                'REVEAL',
                'bank_account_masterlist',
                (string) $id,
                'account_number',
                null,
                'Account number revealed for ' . $row['empcode'] . ' by user #' . $userId
            );

            return $this->respondSuccess('OK', array(
                'account_number' => $plaintext,
            ));
        } catch (\Throwable $e) {
            return $this->respondError('An error occurred: ' . $e->getMessage());
        }
    }

    public function api_toggle_active()
    {
        try {
            $this->output->set_content_type('application/json');
            $data = $this->getRequestPayload();

            $userId = (int) $this->session->userdata('user_id');
            if ($userId <= 0) {
                return $this->respondError('User not authenticated.');
            }

            $id = isset($data['Id']) ? (int) $data['Id'] : 0;
            $isActive = !empty($data['IsActive']) ? 1 : 0;
            if ($id <= 0) {
                return $this->respondError('Invalid record.');
            }

            $this->Bank_account_model->toggle_active($id, $isActive, $userId);

            $this->logAuditTrail(
                'BANK_ACCOUNT_MASTERLIST',
                (string) $id,
                $isActive ? 'ACTIVATE' : 'DEACTIVATE',
                'bank_account_masterlist',
                (string) $id
            );

            return $this->respondSuccess($isActive ? 'Record activated.' : 'Record deactivated.');
        } catch (\Throwable $e) {
            return $this->respondError('An error occurred: ' . $e->getMessage());
        }
    }

    private function normalizeHeader($text)
    {
        return preg_replace('/[^a-z0-9]/', '', strtolower((string) $text));
    }

    public function api_mass_upload()
    {
        try {
            $this->output->set_content_type('application/json');

            $userId = (int) $this->session->userdata('user_id');
            if ($userId <= 0) {
                return $this->respondError('User not authenticated.');
            }

            if (empty($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
                return $this->respondError('Please choose a valid Excel file to upload.');
            }

            $ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, array('xlsx', 'xls', 'csv'), true)) {
                return $this->respondError('Only .xlsx, .xls, or .csv files are supported.');
            }

            $spreadsheet = IOFactory::load($_FILES['file']['tmp_name']);
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray(null, true, true, false);

            if (count($rows) < 2) {
                return $this->respondError('The uploaded file has no data rows.');
            }

            $headerRow = array_shift($rows);
            $columnMap = array();
            foreach ($headerRow as $idx => $header) {
                $columnMap[$this->normalizeHeader($header)] = $idx;
            }

            $colEmpCode = isset($columnMap['employeecode']) ? $columnMap['employeecode'] : null;
            $colBank = isset($columnMap['bankaccount']) ? $columnMap['bankaccount'] : null;
            $colAccountNo = isset($columnMap['accountno'])
                ? $columnMap['accountno']
                : (isset($columnMap['accountnumber']) ? $columnMap['accountnumber'] : null);

            if ($colEmpCode === null || $colBank === null || $colAccountNo === null) {
                return $this->respondError('The file must have "Employee Code", "Bank Account", and "Account No." columns.');
            }

            $inserted = 0;
            $updated = 0;
            $skipped = array();

            // If the same employee code appears more than once in this
            // file, only the last occurrence is kept -- otherwise both
            // rows would race to save the same employee within a single
            // request, and one had to lose silently. This way it's an
            // explicit, visible skip instead of an implicit overwrite.
            $lastLineByEmpcode = array();
            foreach ($rows as $i => $row) {
                $empcode = trim((string) ($row[$colEmpCode] ?? ''));
                if ($empcode === '') {
                    continue;
                }
                $key = strtoupper($empcode);
                $lastLineByEmpcode[$key] = $i;
            }

            foreach ($rows as $i => $row) {
                $lineNo = $i + 2;
                $empcode = trim((string) ($row[$colEmpCode] ?? ''));
                if ($empcode === '') {
                    continue;
                }

                $key = strtoupper($empcode);
                if ($lastLineByEmpcode[$key] !== $i) {
                    $skipped[] = 'Row ' . $lineNo . ' (' . $empcode . '): duplicate employee code in this file — kept row ' . ($lastLineByEmpcode[$key] + 2) . ' instead.';
                    continue;
                }

                $bankAccount = trim((string) ($row[$colBank] ?? ''));
                $accountNumber = trim((string) ($row[$colAccountNo] ?? ''));

                try {
                    $result = $this->saveOne(null, $empcode, $bankAccount, $accountNumber, true, $userId);
                    if ($result['action'] === 'INSERTED') {
                        $inserted++;
                    } else {
                        $updated++;
                    }
                } catch (\Throwable $rowError) {
                    $skipped[] = 'Row ' . $lineNo . ' (' . $empcode . '): ' . $rowError->getMessage();
                }
            }

            return $this->respondSuccess('Mass upload complete.', array(
                'inserted' => $inserted,
                'updated' => $updated,
                'skipped_count' => count($skipped),
                'skipped' => $skipped,
            ));
        } catch (\Throwable $e) {
            return $this->respondError('An error occurred: ' . $e->getMessage());
        }
    }

    public function api_get_company_settings()
    {
        try {
            $this->output->set_content_type('application/json');

            $row = $this->Bizlink_model->get_company_settings();

            if (!is_array($row) || empty($row['id'])) {
                return $this->respondSuccess('OK', array(
                    'exists' => false,
                    'company_code' => '',
                    'account_number_masked' => '',
                    'presenting_office_code' => '',
                ));
            }

            $plaintext = $row['company_account_number'] !== null
                ? bank_account_decrypt($row['company_account_number'])
                : '';

            return $this->respondSuccess('OK', array(
                'exists' => true,
                'company_code' => $row['company_code'],
                'account_number_masked' => bank_account_mask($plaintext),
                'presenting_office_code' => $row['presenting_office_code'],
                'updated_date' => $row['updated_date'],
            ));
        } catch (\Throwable $e) {
            return $this->respondError('An error occurred: ' . $e->getMessage());
        }
    }

    public function api_reveal_company_account()
    {
        try {
            $this->output->set_content_type('application/json');

            $userId = (int) $this->session->userdata('user_id');
            if ($userId <= 0) {
                return $this->respondError('User not authenticated.');
            }

            $row = $this->Bizlink_model->get_company_settings();

            if (!is_array($row) || empty($row['id']) || $row['company_account_number'] === null) {
                return $this->respondError('No company account number is on file yet.');
            }

            $plaintext = bank_account_decrypt($row['company_account_number']);

            $this->logAuditTrail(
                'BIZLINK_COMPANY_SETTINGS',
                (string) $row['id'],
                'REVEAL',
                'bizlink_company_settings',
                (string) $row['id'],
                'company_account_number',
                null,
                'Company debit account number revealed by user #' . $userId
            );

            return $this->respondSuccess('OK', array(
                'account_number' => $plaintext,
            ));
        } catch (\Throwable $e) {
            return $this->respondError('An error occurred: ' . $e->getMessage());
        }
    }

    public function api_save_company_settings()
    {
        try {
            $this->output->set_content_type('application/json');
            $data = $this->getRequestPayload();

            $userId = (int) $this->session->userdata('user_id');
            if ($userId <= 0) {
                return $this->respondError('User not authenticated.');
            }

            $companyCode = trim((string) (isset($data['CompanyCode']) ? $data['CompanyCode'] : ''));
            $accountNumber = trim((string) (isset($data['CompanyAccountNumber']) ? $data['CompanyAccountNumber'] : ''));
            $presentingOfficeCode = trim((string) (isset($data['PresentingOfficeCode']) ? $data['PresentingOfficeCode'] : ''));

            if ($companyCode === '') {
                return $this->respondError('Company Code is required.');
            }
            if ($presentingOfficeCode === '') {
                return $this->respondError('Presenting Office Code is required.');
            }

            // Account number left blank on an edit means "keep the
            // existing one on file" -- otherwise every save would force
            // re-entering the debit account number even when only the
            // ceiling amount or office code changed.
            if ($accountNumber !== '') {
                if (!preg_match('/^[0-9\- ]{4,50}$/', $accountNumber)) {
                    return $this->respondError('Company Account Number format looks invalid.');
                }
                $encrypted = bank_account_encrypt($accountNumber);
            } else {
                $existing = $this->Bizlink_model->get_company_settings();
                if (!is_array($existing) || empty($existing['company_account_number'])) {
                    return $this->respondError('Company Account Number is required.');
                }
                $encrypted = $existing['company_account_number'];
            }

            $result = $this->Bizlink_model->save_company_settings(
                $companyCode,
                $encrypted,
                $presentingOfficeCode,
                $userId
            );

            if (!is_array($result) || empty($result['id'])) {
                return $this->respondError('Failed to save the company BizLink settings.');
            }

            $this->logAuditTrail(
                'BIZLINK_COMPANY_SETTINGS',
                (string) $result['id'],
                $result['action'] === 'INSERTED' ? 'CREATE' : 'UPDATE',
                'bizlink_company_settings',
                (string) $result['id'],
                'company_account_number',
                null,
                'BizLink company settings updated by user #' . $userId
            );

            return $this->respondSuccess('Company BizLink settings saved.', array('id' => (int) $result['id']));
        } catch (\Throwable $e) {
            return $this->respondError('An error occurred: ' . $e->getMessage());
        }
    }

    public function download_template()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray(
            array('Employee Code', 'Fullname', 'Bank Account', 'Account No.'),
            null,
            'A1'
        );
        $sheet->getStyle('A1:D1')->getFont()->setBold(true);
        foreach (range('A', 'D') as $col) {
            $sheet->getColumnDimension($col)->setWidth(24);
        }

        $filename = 'bank-account-masterlist-template.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save('php://output');
        exit;
    }
}
