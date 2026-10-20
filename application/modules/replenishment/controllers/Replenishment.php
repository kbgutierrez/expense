<?php

require 'vendor/autoload.php';
(defined('BASEPATH')) or exit('No direct script access allowed');

class Replenishment extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('SPModel', 'sp');
        $this->sp->setDatabase('dbknet');
    }

    public function index()
    {
        $userId = (int) $this->session->userdata('user_id');
        $activeFund = $this->getActiveFundForUser($userId);

        $data = array(
            'title' => 'Revolving Fund Replenishment',
            'main_view' => '../modules/replenishment/views/index',
            'module_group' => $this->module_group,
            'module' => $this->module,
            'hasActiveFund' => $activeFund !== null,
            'revolvingFundBalance' => $activeFund['available_balance'] ?? null,
            'revolvingFundCode' => $activeFund['fund_code'] ?? null,
            'scripts' => array(
                '../replenishment/index.js',
            ),
        );

        $this->load->view('main', $data);
    }

    public function add()
    {
        $userId = (int) $this->session->userdata('user_id');
        $activeFund = $this->getActiveFundForUser($userId);

        $data = array(
            'title' => 'New Replenishment',
            'main_view' => '../modules/replenishment/views/add',
            'module_group' => $this->module_group,
            'module' => $this->module,
            'hasActiveFund' => $activeFund !== null,
            'revolvingFundBalance' => $activeFund['available_balance'] ?? null,
            'revolvingFundCode' => $activeFund['fund_code'] ?? null,
            'scripts' => array(
                '../replenishment/add.js',
            ),
        );

        $this->load->view('main', $data);
    }

    public function view($replenishment_no = '')
    {
        $ref = trim((string) $replenishment_no);
        if ($ref === '') {
            redirect('transactions/replenishment');
            return;
        }

        $data = array(
            'title' => 'Replenishment Details',
            'main_view' => '../modules/replenishment/views/detail',
            'module_group' => $this->module_group,
            'module' => $this->module,
            'replenishment_no' => $ref,
            'scripts' => array(
                '../replenishment/detail.js',
            ),
        );

        $this->load->view('main', $data);
    }

    private function getActiveFundForUser($userId)
    {
        $this->load->model('Revolving_fund_model');
        $row = $this->Revolving_fund_model->find_active_fund_for_user($userId);

        return !empty($row) ? $row : null;
    }

    public function api_get_claimable()
    {
        try {
            $this->output->set_content_type('application/json');

            $userId = (int) $this->session->userdata('user_id');
            if ($this->getActiveFundForUser($userId) === null) {
                return $this->respondError('You do not currently hold an active revolving fund.');
            }

            $this->load->model('Reimbursement_model');
            $result = $this->Reimbursement_model->list_claimable_paid($userId);

            return $this->respondSuccess('OK', is_array($result) ? $result : array());
        } catch (Exception $e) {
            return $this->respondError('An error occurred: ' . $e->getMessage());
        }
    }

    public function api_save_replenishment()
    {
        try {
            $this->output->set_content_type('application/json');
            $data = $this->getRequestPayload();

            $userId = (int) $this->session->userdata('user_id');
            $activeFund = $this->getActiveFundForUser($userId);
            if ($activeFund === null) {
                return $this->respondError('You do not currently hold an active revolving fund.');
            }

            $reimbursementIds = isset($data['ReimbursementIds']) && is_array($data['ReimbursementIds'])
                ? array_values(array_filter(array_map('trim', $data['ReimbursementIds'])))
                : array();

            if (count($reimbursementIds) === 0) {
                return $this->respondError('Select at least one paid reimbursement to claim.');
            }

            $remarks = isset($data['Remarks']) ? trim((string) $data['Remarks']) : '';
            $statusCode = isset($data['StatusCode']) && $data['StatusCode'] === 'RPL_SUBMITTED'
                ? 'RPL_SUBMITTED'
                : 'RPL_DRAFT';

            $params = array(
                'UserId' => $userId,
                'FundId' => (int) $activeFund['id'],
                'ReimbursementIds' => implode(',', $reimbursementIds),
                'Remarks' => $remarks,
                'StatusCode' => $statusCode,
            );

            $result = $this->sp->readData(
                build_sp('sp_insert_replenishment_header', count($params)),
                $params,
                'row'
            );

            if (!is_array($result) || empty($result['GeneratedReplenishmentID'])) {
                return $this->respondError('Failed to create replenishment request.');
            }

            $replenishmentId = $result['GeneratedReplenishmentID'];

            $this->logAuditTrail(
                'REPLENISHMENT',
                $replenishmentId,
                $statusCode === 'RPL_DRAFT' ? 'SAVED_DRAFT' : 'SUBMITTED',
                'HEADER',
                $replenishmentId
            );

            if ($statusCode === 'RPL_SUBMITTED') {
                $this->notifyFirstApprover($replenishmentId);
            }

            $message = $statusCode === 'RPL_DRAFT'
                ? 'Replenishment draft saved successfully'
                : 'Replenishment request submitted successfully';

            return $this->respondSuccess($message, array('id' => $replenishmentId));
        } catch (Exception $e) {
            return $this->respondError('An error occurred: ' . $e->getMessage());
        }
    }

    public function api_get_list()
    {
        try {
            $this->output->set_content_type('application/json');

            $userId = (int) $this->session->userdata('user_id');
            $cursorIdRaw = $this->input->post('CursorId');
            $take = isset($_POST['Take']) ? (int) $_POST['Take'] : 20;

            $this->load->model('Replenishment_model');
            $result = $this->Replenishment_model->list_by_user(
                $userId,
                ($cursorIdRaw !== null && $cursorIdRaw !== '') ? (int) $cursorIdRaw : null,
                $take
            );

            return $this->respondSuccess('OK', is_array($result) ? $result : array());
        } catch (Exception $e) {
            return $this->respondError('An error occurred: ' . $e->getMessage());
        }
    }

    public function api_get_replenishment()
    {
        try {
            $this->output->set_content_type('application/json');
            $data = $this->getRequestPayload();

            $replenishmentId = isset($data['ReplenishmentId']) ? trim((string) $data['ReplenishmentId']) : '';
            if ($replenishmentId === '') {
                return $this->respondError('Missing required field: ReplenishmentId');
            }

            $this->load->model('Replenishment_model');
            $header = $this->Replenishment_model->find_header($replenishmentId);
            $details = $this->Replenishment_model->list_details($replenishmentId);

            if (!is_array($header) || empty($header)) {
                return $this->respondError('Replenishment request not found.');
            }

            return $this->respondSuccess('OK', array(
                'header' => $header,
                'details' => is_array($details) ? $details : array(),
            ));
        } catch (Exception $e) {
            return $this->respondError('An error occurred: ' . $e->getMessage());
        }
    }

    public function api_get_timeline()
    {
        try {
            $this->output->set_content_type('application/json');
            $data = $this->getRequestPayload();

            $referenceNo = isset($data['ReferenceNo']) ? trim((string) $data['ReferenceNo']) : '';
            if ($referenceNo === '') {
                return $this->respondError('Missing ReferenceNo');
            }

            $this->load->model('Audit_trail_model');
            $auditTrail = $this->Audit_trail_model->list_by_transaction($referenceNo);

            return $this->respondSuccess('Timeline fetched', array(
                'audit_trail' => is_array($auditTrail) ? $auditTrail : array(),
            ));
        } catch (Throwable $e) {
            return $this->respondError($e->getMessage());
        }
    }

    private function notifyFirstApprover($replenishmentId)
    {
        try {
            if (!$this->sp || !$this->sp->db) {
                return;
            }

            $header = $this->sp->db->get_where('tbl_approval_header', array('reference_id' => $replenishmentId, 'is_active' => 1), 1)->row_array();
            if (!is_array($header) || empty($header['id'])) {
                return;
            }

            $firstApprover = $this->sp->db->select('approver_id')
                ->from('tbl_approval_details')
                ->where('approval_header_id', $header['id'])
                ->where('status', 'PENDING')
                ->order_by('approval_order', 'ASC')
                ->limit(1)
                ->get()
                ->row_array();

            if (!is_array($firstApprover) || empty($firstApprover['approver_id'])) {
                return;
            }

            $approverInfo = get_user_info((int) $firstApprover['approver_id']);
            if (!is_array($approverInfo) || empty($approverInfo['email'])) {
                return;
            }

            $rplHeader = $this->sp->db->get_where('tbl_replenishment_header', array('replenishment_id' => $replenishmentId), 1)->row_array();
            $requesterInfo = is_array($rplHeader) && !empty($rplHeader['user_id']) ? get_user_info((int) $rplHeader['user_id']) : array();

            $approverName = trim((string) ($approverInfo['firstname'] ?? '') . ' ' . (string) ($approverInfo['lastname'] ?? ''));

            $mergeData = array(
                'amount' => is_array($rplHeader) ? ($rplHeader['total_amount'] ?? 0) : 0,
                'status' => 'PENDING',
                'remarks' => '',
                'action_date' => date('Y-m-d H:i:s'),
                'requester_name' => trim((string) ($requesterInfo['firstname'] ?? '') . ' ' . (string) ($requesterInfo['lastname'] ?? '')),
                'requester_department' => (string) ($requesterInfo['department_name'] ?? ''),
                'approver_name' => $approverName,
            );

            notify_event('TXN_SUBMITTED', 'REPLENISHMENT', $replenishmentId, array(array(
                'email' => $approverInfo['email'],
                'name' => $approverName !== '' ? $approverName : $approverInfo['email'],
                'user_id' => (int) $firstApprover['approver_id'],
            )), $mergeData);
        } catch (Exception $e) {
            log_message('error', 'notifyFirstApprover (Replenishment) failed for ' . $replenishmentId . ': ' . $e->getMessage());
        }
    }

}
