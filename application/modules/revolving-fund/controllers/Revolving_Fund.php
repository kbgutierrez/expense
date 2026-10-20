<?php
(defined('BASEPATH')) or exit('No direct script access allowed');

class Revolving_Fund extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('SPModel', 'sp');
        $this->sp->setDatabase('dbknet');
    }

    public function index()
    {
        $data = array(
            'title' => 'Revolving Fund',
            'main_view' => '../modules/revolving-fund/views/index',
            'module_group' => $this->module_group,
            'module' => $this->module,
            'scripts' => array(
                'index.js',
            ),
        );

        $this->load->view('main', $data);
    }

    /* ------------------------------------------------------------
       FUND (single create/edit action — fund + holder together)
       ------------------------------------------------------------ */

    public function api_get_funds()
    {
        try {
            $this->output->set_content_type('application/json');

            $take = $this->resolvePaginationTake($this->input->post('Take'));

            $cursorIdRaw = $this->input->post('CursorId');
            $cursorId = ($cursorIdRaw !== null && $cursorIdRaw !== '') ? (int) $cursorIdRaw : null;

            $this->load->model('Revolving_fund_model');
            $result = $this->Revolving_fund_model->list_funds(
                $cursorId,
                $take,
                $this->input->post('Keyword'),
                $this->input->post('ScopeType'),
                $this->input->post('Status')
            );

            $payload = $this->buildPaginationResult($result, $take);
            echo json_encode(array('status' => 'success') + $payload);
        } catch (Exception $e) {
            return $this->respondError('An error occurred: ' . $e->getMessage());
        }
    }

    public function api_save_fund()
    {
        try {
            $this->output->set_content_type('application/json');

            $data = $this->getRequestPayload();

            $scopeType = isset($data['ScopeType']) ? trim((string) $data['ScopeType']) : '';
            $scopeName = isset($data['ScopeName']) ? trim((string) $data['ScopeName']) : '';
            $openingBalance = isset($data['OpeningBalance']) ? (float) $data['OpeningBalance'] : 0;

            if (!in_array($scopeType, array('PERSON', 'DEPARTMENT', 'COMPANY'), true)) {
                return $this->respondError('Scope type must be Person, Department, or Company');
            }

            if ($scopeName === '') {
                return $this->respondError('Scope value is required');
            }

            if ($openingBalance < 0) {
                return $this->respondError('Opening balance cannot be negative');
            }

            $status = isset($data['Status']) ? trim((string) $data['Status']) : 'RF_ACTIVE';
            if (!in_array($status, array('RF_ACTIVE', 'RF_INACTIVE', 'RF_LOCKED'), true)) {
                $status = 'RF_ACTIVE';
            }

            $params = array(
                'ScopeType' => $scopeType,
                'ScopeId' => isset($data['ScopeId']) && $data['ScopeId'] !== '' ? (int) $data['ScopeId'] : null,
                'ScopeName' => $scopeName,
                'OpeningBalance' => $openingBalance,
                'AllowNegativeBalance' => !empty($data['AllowNegativeBalance']) ? 1 : 0,
                'AllowSelfCashIn' => !empty($data['AllowSelfCashIn']) ? 1 : 0,
                'Status' => $status,
                'Remarks' => isset($data['Remarks']) ? trim((string) $data['Remarks']) : null,
                'CreatedBy' => (int) $this->session->userdata('user_id'),
            );

            $result = $this->sp->createReturnId(
                build_sp('sp_insert_revolving_fund_with_holder', count($params)),
                $params
            );

            if (!$result || !isset($result['Id'])) {
                return $this->respondError('Failed to create revolving fund');
            }

            $this->logAuditTrail('REVOLVING_FUND', $result['Id'], 'CREATE', 'FUND', $result['Id'], null, null, json_encode($params));

            return $this->respondSuccess('Revolving fund created successfully', $result);
        } catch (Exception $e) {
            return $this->respondError('An error occurred: ' . $e->getMessage());
        }
    }

    public function api_update_fund()
    {
        try {
            $this->output->set_content_type('application/json');

            $data = $this->getRequestPayload();

            $id = isset($data['Id']) ? (int) $data['Id'] : 0;
            $scopeType = isset($data['ScopeType']) ? trim((string) $data['ScopeType']) : '';
            $scopeName = isset($data['ScopeName']) ? trim((string) $data['ScopeName']) : '';

            if ($id <= 0) {
                return $this->respondError('Invalid revolving fund id');
            }

            if (!in_array($scopeType, array('PERSON', 'DEPARTMENT', 'COMPANY'), true)) {
                return $this->respondError('Scope type must be Person, Department, or Company');
            }

            if ($scopeName === '') {
                return $this->respondError('Scope value is required');
            }

            $status = isset($data['Status']) ? trim((string) $data['Status']) : 'RF_ACTIVE';
            if (!in_array($status, array('RF_ACTIVE', 'RF_INACTIVE', 'RF_LOCKED'), true)) {
                $status = 'RF_ACTIVE';
            }

            $params = array(
                'Id' => $id,
                'ScopeType' => $scopeType,
                'ScopeId' => isset($data['ScopeId']) && $data['ScopeId'] !== '' ? (int) $data['ScopeId'] : null,
                'ScopeName' => $scopeName,
                'AllowNegativeBalance' => !empty($data['AllowNegativeBalance']) ? 1 : 0,
                'AllowSelfCashIn' => !empty($data['AllowSelfCashIn']) ? 1 : 0,
                'Status' => $status,
                'Remarks' => isset($data['Remarks']) ? trim((string) $data['Remarks']) : null,
                'UpdatedBy' => (int) $this->session->userdata('user_id'),
            );

            $result = $this->sp->createData(
                build_sp('sp_update_revolving_fund_with_holder', count($params)),
                $params
            );

            if ($result !== true) {
                return $this->respondError(is_string($result) ? $result : 'Failed to update revolving fund');
            }

            $this->logAuditTrail('REVOLVING_FUND', $id, 'UPDATE', 'FUND', $id, null, null, json_encode($params));

            return $this->respondSuccess('Revolving fund updated successfully');
        } catch (Exception $e) {
            return $this->respondError('An error occurred: ' . $e->getMessage());
        }
    }

    /* ------------------------------------------------------------
       LEDGER (Add Money / History, per fund row)
       ------------------------------------------------------------ */

    public function api_get_ledger()
    {
        try {
            $this->output->set_content_type('application/json');

            $take = $this->resolvePaginationTake($this->input->post('Take'));

            $cursorIdRaw = $this->input->post('CursorId');
            $cursorId = ($cursorIdRaw !== null && $cursorIdRaw !== '') ? (int) $cursorIdRaw : null;
            $fundIdRaw = $this->input->post('FundId');
            $fundId = ($fundIdRaw !== null && $fundIdRaw !== '') ? (int) $fundIdRaw : null;

            $this->load->model('Revolving_fund_model');
            $result = $this->Revolving_fund_model->list_ledger_entries(
                $cursorId,
                $take,
                $fundId,
                $this->input->post('TrxType'),
                $this->input->post('DateFrom'),
                $this->input->post('DateTo')
            );

            $payload = $this->buildPaginationResult($result, $take);
            echo json_encode(array('status' => 'success') + $payload);
        } catch (Exception $e) {
            return $this->respondError('An error occurred: ' . $e->getMessage());
        }
    }

    public function api_save_ledger()
    {
        try {
            $this->output->set_content_type('application/json');

            $data = $this->getRequestPayload();

            $fundId = isset($data['FundId']) ? (int) $data['FundId'] : 0;
            $trxType = isset($data['TrxType']) ? trim((string) $data['TrxType']) : '';
            $trxDate = isset($data['TrxDate']) ? trim((string) $data['TrxDate']) : '';
            $amount = isset($data['Amount']) ? (float) $data['Amount'] : 0;
            $remarks = isset($data['Remarks']) ? trim((string) $data['Remarks']) : '';

            if ($fundId <= 0) {
                return $this->respondError('Please select a revolving fund');
            }

            if (!in_array($trxType, array('RF_TOPUP', 'RF_ADJUSTMENT'), true)) {
                return $this->respondError('Transaction type must be Top-up or Adjustment');
            }

            if ($trxDate === '') {
                return $this->respondError('Transaction date is required');
            }

            if ($amount == 0) {
                return $this->respondError('Amount cannot be zero');
            }

            if ($trxType === 'RF_TOPUP' && $amount < 0) {
                return $this->respondError('Top-up amount must be positive');
            }

            if ($remarks === '') {
                return $this->respondError('Remarks are required');
            }

            $params = array(
                'FundId' => $fundId,
                'TrxDate' => $trxDate,
                'TrxType' => $trxType,
                'Amount' => $amount,
                'Remarks' => $remarks,
                'CreatedBy' => (int) $this->session->userdata('user_id'),
            );

            $result = $this->sp->createData(
                build_sp('sp_insert_revolving_fund_ledger', count($params)),
                $params
            );

            if ($result !== true) {
                return $this->respondError(is_string($result) ? $result : 'Failed to post ledger entry');
            }

            $this->logAuditTrail('REVOLVING_FUND', $fundId, 'CREATE', 'LEDGER', $fundId, null, null, json_encode($params));

            return $this->respondSuccess('Ledger entry posted successfully');
        } catch (Exception $e) {
            return $this->respondError('An error occurred: ' . $e->getMessage());
        }
    }

    /* ------------------------------------------------------------
       LOOKUPS
       ------------------------------------------------------------ */

    public function api_get_departments()
    {
        try {
            $this->output->set_content_type('application/json');

            $this->load->model('Lookup_model');
            $result = $this->Lookup_model->get_departments();

            return $this->respondSuccess('OK', $result);
        } catch (Exception $e) {
            return $this->respondError('An error occurred: ' . $e->getMessage());
        }
    }

    public function api_get_users()
    {
        try {
            $this->output->set_content_type('application/json');

            $this->load->model('Approval_matrix_model');
            $result = $this->Approval_matrix_model->list_approvers();

            return $this->respondSuccess('OK', $result);
        } catch (Exception $e) {
            return $this->respondError('An error occurred: ' . $e->getMessage());
        }
    }

    /* ------------------------------------------------------------
       HELPERS
       ------------------------------------------------------------ */

}
