<?php

require 'vendor/autoload.php';
(defined('BASEPATH')) or exit('No direct script access allowed');

class Reimbursement extends MY_Controller
{
    private $draftEditWindowDays = 7;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('SPModel', 'sp');
        $this->sp->setDatabase('dbknet');
        $this->load->model('Lookup_model');
        $this->load->model('Reimbursement_model');
        $this->load->model('Revolving_fund_model');
    }

    public function api_get_payable_to_options()
    {
        try {
            $this->output->set_content_type('application/json');

            $departmentId = (int) ($this->session->userdata('user_info')['department_id'] ?? 0);
            if ($departmentId <= 0) {
                throw new Exception('Department not found for the logged-in user.');
            }

            $result = $this->Lookup_model->get_payable_to_users($departmentId);

            return $this->respondSuccess('OK', is_array($result) ? $result : array());
        } catch (Exception $e) {
            return $this->respondError('An error occurred: ' . $e->getMessage());
        }
    }

    public function index()
    {
        $activeFund = $this->getActiveFundForUser((int) $this->session->userdata('user_id'));

        $data = array(
            'title' => 'Reimbursement',
            'main_view' => '../modules/reimbursement/views/index',
            'module_group' => $this->module_group,
            'module' => $this->module,
            'hasActiveFund' => $activeFund !== null,
            'revolvingFundBalance' => $activeFund['available_balance'] ?? null,
            'revolvingFundCode' => $activeFund['fund_code'] ?? null,
            'scripts' => array(
                '../reimbursement/index.js',
            ),
        );

        $this->load->view('main', $data);
    }


    public function api_get_team_report()
    {
        try {
            $this->output->set_content_type('application/json');

            $userId = (int) $this->session->userdata('user_id');

            if ($this->getActiveFundForUser($userId) === null) {
                return $this->respondError('You do not currently hold an active revolving fund.');
            }

            $dateFrom = trim((string) $this->input->post('DateFrom'));
            $dateTo = trim((string) $this->input->post('DateTo'));

            if ($dateFrom === '' || $dateTo === '') {
                return $this->respondError('Please select a date range.');
            }

            $result = $this->Reimbursement_model->list_team_details($userId, $dateFrom, $dateTo);

            return $this->respondSuccess('OK', $result);
        } catch (Exception $e) {
            return $this->respondError('An error occurred: ' . $e->getMessage());
        }
    }

    private function getActiveFundForUser($userId)
    {
        $row = $this->Revolving_fund_model->find_active_fund_for_user($userId);

        return !empty($row) ? $row : null;
    }

    public function api_get_team()
    {
        try {
            $this->output->set_content_type('application/json');

            $team = $this->getTeamForUser((int) $this->session->userdata('user_id'));

            return $this->respondSuccess('OK', $team);
        } catch (Exception $e) {
            return $this->respondError('An error occurred: ' . $e->getMessage());
        }
    }

    private function getTeamForUser($userId)
    {
        $team = $this->Reimbursement_model->list_supervisor_team($userId);

        return is_array($team) ? $team : array();
    }

    private function isOwnerInSupervisorsTeam($supervisorUserId, $ownerUserId)
    {
        $team = $this->getTeamForUser($supervisorUserId);
        foreach ($team as $member) {
            if ((int) ($member['member_user_id'] ?? 0) === (int) $ownerUserId) {
                return true;
            }
        }
        return false;
    }

    public function team_view($reimbursement_no = '')
    {
        $ref = trim((string) $reimbursement_no);
        if ($ref === '') {
            redirect('transactions/reimbursement');
            return;
        }

        $expenseTypes = $this->Lookup_model->get_expense_types();
        if (!is_array($expenseTypes)) {
            $expenseTypes = array();
        }

        $departmentId = $this->session->userdata('user_info')['department_id'];
        $costCenters = $this->Lookup_model->get_cost_centers_by_department($departmentId);
        if (!is_array($costCenters)) {
            $costCenters = array();
        }

        $payableToUsers = $this->Lookup_model->get_payable_to_users((int) $departmentId);
        if (!is_array($payableToUsers)) {
            $payableToUsers = array();
        }

        $data = array(
            'title' => 'Team Reimbursement Details',
            'main_view' => '../modules/reimbursement/views/team-view',
            'module_group' => $this->module_group,
            'module' => $this->module,
            'reimbursement_no' => $ref,
            'expense_types' => $expenseTypes,
            'cost_centers' => $costCenters,
            'payable_to_users' => $payableToUsers,
            'scripts' => array(
                '../shared/pdfjs/pdf.min.js',
                '../shared/receipt-ocr.js',
                '../reimbursement/team-view.js',
            ),
        );

        $this->load->view('main', $data);
    }

    public function api_get_team_reimbursement_full()
    {
        try {
            $this->output->set_content_type('application/json');
            $data = $this->getRequestPayload();

            $reimbursementId = isset($data['ReimbursementId']) ? trim((string) $data['ReimbursementId']) : '';
            if ($reimbursementId === '') {
                return $this->respondError('Missing required field: ReimbursementId');
            }

            $header = $this->getDraftHeaderByReimbursementId($reimbursementId);
            if (!$header) {
                return $this->respondError('Reimbursement not found.');
            }

            $supervisorUserId = (int) $this->session->userdata('user_id');
            $ownerUserId = isset($header['user_id']) ? (int) $header['user_id'] : (int) ($header['created_by_id'] ?? 0);

            if (!$this->isOwnerInSupervisorsTeam($supervisorUserId, $ownerUserId)) {
                return $this->respondError('You are not allowed to access this reimbursement.');
            }

            $details = $this->Reimbursement_model->list_details($reimbursementId);

            $statusCode = isset($header['status_code']) ? trim((string) $header['status_code']) : '';
            $canCorrect = in_array($statusCode, array('RMB_SUBMITTED', 'RMB_PENDING'), true);

            return $this->respondSuccess('success', array(
                'header' => $header,
                'details' => is_array($details) ? $details : array(),
                'canCorrect' => $canCorrect,
            ));
        } catch (Exception $e) {
            return $this->respondError('An error occurred: ' . $e->getMessage());
        }
    }

    private function assertTeamReimbursementCorrectable($reimbursementId, $supervisorUserId)
    {
        $header = $this->getDraftHeaderByReimbursementId($reimbursementId);
        if (!$header) {
            throw new Exception('Reimbursement not found.');
        }

        $ownerUserId = isset($header['user_id']) ? (int) $header['user_id'] : (int) ($header['created_by_id'] ?? 0);
        if (!$this->isOwnerInSupervisorsTeam($supervisorUserId, $ownerUserId)) {
            throw new Exception('You are not allowed to modify this reimbursement.');
        }

        $statusCode = isset($header['status_code']) ? trim((string) $header['status_code']) : '';
        if (!in_array($statusCode, array('RMB_SUBMITTED', 'RMB_PENDING'), true)) {
            throw new Exception('This reimbursement has already been finalized and can no longer be corrected.');
        }

        return $header;
    }

    public function api_update_team_reimbursement_item()
    {
        try {
            $this->output->set_content_type('application/json');
            $data = $this->getRequestPayload();

            $reimbursementId = isset($data['ReimbursementId']) ? trim((string) $data['ReimbursementId']) : '';
            $detailId = isset($data['DetailId']) ? (int) $data['DetailId'] : 0;

            if ($reimbursementId === '' || $detailId <= 0) {
                return $this->respondError('Missing required field.');
            }

            $supervisorUserId = (int) $this->session->userdata('user_id');
            $this->assertTeamReimbursementCorrectable($reimbursementId, $supervisorUserId);

            $newAttachment = null;
            if (isset($_FILES['Attachment']) && isset($_FILES['Attachment']['error']) && (int) $_FILES['Attachment']['error'] === UPLOAD_ERR_OK) {
                list($targetDir, $absoluteDir) = $this->ensureAttachmentDir();
                $newAttachment = $this->saveUploadedFile($_FILES['Attachment'], $targetDir, $absoluteDir);
            }

            $description = trim((string) ($data['Description'] ?? ''));

            try {
                $this->Reimbursement_model->update_detail_review(
                    $detailId,
                    $description,
                    trim((string) ($data['InvoiceReceiptNo'] ?? '')),
                    (float) ($data['ActualAmount'] ?? 0),
                    trim((string) ($data['DocumentDate'] ?? '')),
                    trim((string) ($data['ExpenseCategory'] ?? '')),
                    (int) (bool) ($data['IsVatable'] ?? 0),
                    (float) ($data['NetAmount'] ?? 0),
                    (float) ($data['VatAmount'] ?? 0),
                    trim((string) ($data['VendorName'] ?? '')),
                    trim((string) ($data['VendorAddress'] ?? '')),
                    trim((string) ($data['VendorTin'] ?? '')),
                    null
                );
                $result = true;
            } catch (Exception $e) {
                $result = $e->getMessage();
            }

            if ($result !== true) {
                return $this->respondError(is_string($result) ? $result : 'Failed to update line item.');
            }

            $this->logAuditTrail(
                'REIMBURSEMENT',
                $reimbursementId,
                'CORRECTED_ITEM',
                'ITEM',
                $detailId,
                null,
                null,
                'Corrected by supervisor: ' . $description
            );

            return $this->respondSuccess('Line item corrected successfully.');
        } catch (Throwable $e) {
            return $this->respondError($e->getMessage());
        }
    }

    public function api_update_team_reimbursement_header()
    {
        try {
            $this->output->set_content_type('application/json');
            $data = $this->getRequestPayload();

            $reimbursementId = isset($data['ReimbursementId']) ? trim((string) $data['ReimbursementId']) : '';
            if ($reimbursementId === '') {
                return $this->respondError('Missing required field: ReimbursementId');
            }

            $supervisorUserId = (int) $this->session->userdata('user_id');
            $this->assertTeamReimbursementCorrectable($reimbursementId, $supervisorUserId);

            try {
                $this->Reimbursement_model->update_header_fields(
                    $reimbursementId,
                    trim((string) ($data['CostCenterId'] ?? '')),
                    trim((string) ($data['Address'] ?? '')),
                    $supervisorUserId,
                    trim((string) ($data['IO'] ?? ''))
                );
            } catch (Exception $e) {
            }

            $this->logAuditTrail(
                'REIMBURSEMENT',
                $reimbursementId,
                'CORRECTED_HEADER',
                'HEADER',
                $reimbursementId,
                null,
                null,
                'Header corrected by supervisor'
            );

            return $this->respondSuccess('Reimbursement details corrected successfully.');
        } catch (Throwable $e) {
            return $this->respondError($e->getMessage());
        }
    }

    public function add($reimbursement_no = '')
    {
        $ref = trim((string) $reimbursement_no);
        $isEditMode = $ref !== '';

        $departmentId = $this->session->userdata('user_info')['department_id'];
        $costCenters = $this->Lookup_model->get_cost_centers_by_department($departmentId);
        if (!is_array($costCenters)) {
            $costCenters = array();
        }

        $expenseTypes = $this->Lookup_model->get_expense_types();
        if (!is_array($expenseTypes)) {
            $expenseTypes = array();
        }

        $teamMembers = $this->getTeamForUser((int) $this->session->userdata('user_id'));

        $payableToUsers = $this->Lookup_model->get_payable_to_users((int) $departmentId);
        if (!is_array($payableToUsers)) {
            $payableToUsers = array();
        }

        $data = array(
            'title' => $isEditMode ? 'Edit Draft Reimbursement' : 'New Reimbursement',
            'main_view' => '../modules/reimbursement/views/add',
            'module_group' => $this->module_group,
            'module' => $this->module,
            'reimbursement_no' => $ref,
            'is_edit_mode' => $isEditMode,
            'draft_edit_window_days' => $this->draftEditWindowDays,
            'cost_centers' => $costCenters,
            'expense_types' => $expenseTypes,
            'team_members' => $teamMembers,
            'payable_to_users' => $payableToUsers,
            'current_user_id' => (int) $this->session->userdata('user_id'),
            'scripts' => array(
                '../shared/pdfjs/pdf.min.js',
                '../shared/receipt-ocr.js',
                '../reimbursement/index.js',
                '../reimbursement/add.js',
            ),
        );

        $this->load->view('main', $data);
    }

    public function view($reimbursement_no = '')
    {
        $data = array(
            'title' => 'Reimbursement Details',
            'main_view' => '../modules/reimbursement/views/detail',
            'module_group' => $this->module_group,
            'module' => $this->module,
            'reimbursement_no' => $reimbursement_no,
            'scripts' => array(
                '../reimbursement/index.js',
                '../reimbursement/detail.js',
            ),
        );

        $this->load->view('main', $data);
    }

    private function hasApproverApproved($referenceNo)
    {
        $row = $this->sp->db->select('D.id')
            ->from('tbl_approval_header H')
            ->join('tbl_approval_details D', 'D.approval_header_id = H.id')
            ->where('H.reference_id', $referenceNo)
            ->where('H.is_active', 1)
            ->where('D.is_active', 1)
            ->where('D.status', 'APPROVED')
            ->limit(1)
            ->get()
            ->row_array();

        return !empty($row);
    }

    public function edit($reimbursement_no = '')
    {
        $ref = trim((string) $reimbursement_no);
        if ($ref === '') {
            redirect('transactions/reimbursement');
            return;
        }

        $editHeader = $this->getDraftHeaderByReimbursementId($ref);
        $editStatus = is_array($editHeader) && isset($editHeader['status_code']) ? trim((string) $editHeader['status_code']) : '';
        if ($editStatus === 'RMB_SUBMITTED' && $this->hasApproverApproved($ref)) {
            redirect('transactions/reimbursement/view/' . rawurlencode($ref));
            return;
        }

        $departmentId = $this->session->userdata('user_info')['department_id'];
        $costCenters = $this->Lookup_model->get_cost_centers_by_department($departmentId);
        if (!is_array($costCenters)) {
            $costCenters = array();
        }

        $expenseTypes = $this->Lookup_model->get_expense_types();
        if (!is_array($expenseTypes)) {
            $expenseTypes = array();
        }

        $data = array(
            'title' => 'Edit Reimbursement',
            'main_view' => '../modules/reimbursement/views/edit',
            'module_group' => $this->module_group,
            'module' => $this->module,
            'reimbursement_no' => $ref,
            'cost_centers' => $costCenters,
            'expense_types' => $expenseTypes,
            'scripts' => array(
                '../shared/pdfjs/pdf.min.js',
                '../shared/receipt-ocr.js',
                '../reimbursement/edit.js',
            ),
        );

        $this->load->view('main', $data);
    }


    public function api_get_expense_types()
    {
        try {
            $this->output->set_content_type('application/json');

            $authHeader = $this->input->get_request_header('Authorization', TRUE);

            if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
                return $this->respondError("Unauthorized: Token missing", 401);
            }
            $departmentId = $this->session->userdata('user_info')['department_id'];
            $token = $matches[1];

            $secureToken = "12345678";

            if ($token !== $secureToken) {
                return $this->respondError("Unauthorized: Invalid token.", 401);
            }
            $result = $this->Lookup_model->get_expense_types_by_department($departmentId);

            return $this->respondSuccess("success", $result);
        } catch (Exception $e) {
            return $this->respondError("error: " . $e->getMessage());
        }
    }

    public function api_get_reimbursement()
    {
        try {
            $this->output->set_content_type('application/json');
            $data = $this->getRequestPayload();

            $reimbursementId = isset($data['ReimbursementId']) ? trim((string) $data['ReimbursementId']) : '';
            if ($reimbursementId === '') {
                return $this->respondError('Missing required field: ReimbursementId');
            }

            $header = $this->getDraftHeaderByReimbursementId($reimbursementId);
            if (!$header) {
                return $this->respondError('Reimbursement not found.');
            }

            $userId = (int) $this->session->userdata('user_id');
            $createdById = isset($header['created_by_id']) ? (int) $header['created_by_id'] : 0;
            $filedById = isset($header['filed_by']) ? (int) $header['filed_by'] : 0;
            if ($createdById !== $userId && $filedById !== $userId) {
                return $this->respondError('You are not allowed to access this reimbursement.');
            }

            $ageDays = $this->getDraftAgeDays(isset($header['created_date']) ? $header['created_date'] : '');
            $canEdit = $ageDays <= $this->draftEditWindowDays;
            $statusCode = isset($header['status_code']) ? trim((string) $header['status_code']) : '';

            $details = $this->Reimbursement_model->list_details($reimbursementId);

            $approvals = $this->Reimbursement_model->list_approvals($reimbursementId);

            $approvalMap = array();
            if (is_array($approvals)) {
                foreach ($approvals as $approval) {
                    $lineItemId = isset($approval['line_item_id']) ? (int) $approval['line_item_id'] : 0;
                    if ($lineItemId > 0) {
                        if (!isset($approvalMap[$lineItemId])) {
                            $approvalMap[$lineItemId] = array();
                        }
                        $approvalMap[$lineItemId][] = array(
                            'approver_name' => isset($approval['approver_name']) ? $approval['approver_name'] : '',
                            'status' => isset($approval['status']) ? $approval['status'] : '',
                            'remarks' => isset($approval['remarks']) ? $approval['remarks'] : '',
                        );
                    }
                }
            }

            $detailsWithStatus = array();
            if (is_array($details)) {
                foreach ($details as $detail) {
                    $detailId = isset($detail['id']) ? (int) $detail['id'] : 0;
                    $detail['approvals'] = isset($approvalMap[$detailId]) ? $approvalMap[$detailId] : array();
                    $detailsWithStatus[] = $detail;
                }
            }

            return $this->respondSuccess('success', array(
                'header' => $header,
                'details' => $detailsWithStatus,
                'canEdit' => $canEdit,
                'draftAgeDays' => $ageDays,
                'draftEditWindowDays' => $this->draftEditWindowDays,
            ));
        } catch (Exception $e) {
            return $this->respondError('An error occurred: ' . $e->getMessage());
        }
    }

    public function api_save_reimbursement()
    {
        try {
            $this->output->set_content_type('application/json');
            $data = $this->getRequestPayload();

            if (isset($data['Expenses']) && is_string($data['Expenses'])) {
                $decodedExpenses = json_decode($data['Expenses'], true);
                if (is_array($decodedExpenses)) {
                    $data['Expenses'] = $decodedExpenses;
                }
            }

            $requestedReimbursementId = isset($data['ReimbursementId']) ? trim((string) $data['ReimbursementId']) : '';

            if (!isset($data['Expenses']) || !is_array($data['Expenses']) || count($data['Expenses']) === 0) {
                return $this->respondError("Missing required field: Expenses");
            }

            $totalAmount = isset($data['TotalAmount']) ? (float) $data['TotalAmount'] : 0;
            if ($totalAmount <= 0) {
                return $this->respondError("Missing required field: TotalAmount");
            }

            $description = isset($data['Description']) ? trim((string) $data['Description']) : '';
            $statusCode = isset($data['StatusCode']) && $data['StatusCode'] !== '' ? trim((string) $data['StatusCode']) : 'RMB_DRAFT';
            if ($statusCode !== 'RMB_DRAFT' && $statusCode !== 'RMB_SUBMITTED') {
                $statusCode = 'RMB_DRAFT';
            }

            $costCenterId = isset($data['CostCenterId']) ? trim((string) $data['CostCenterId']) : '';
            $address = isset($data['Address']) ? trim((string) $data['Address']) : '';
            $ioNumber = isset($data['IoNumber']) ? trim((string) $data['IoNumber']) : '';

            $reimbursementId = '';
            $currentUserId = (int) $this->session->userdata('user_id');

            $userInfo = $this->session->userdata('user_info');
            $payableTo = trim(
                trim((string) ($userInfo['lastname'] ?? '')) . ', ' . trim((string) ($userInfo['firstname'] ?? '')),
                ', '
            );

            $fileForUserId = isset($data['FileForUserId']) ? (int) $data['FileForUserId'] : 0;
            $ownerUserId = $currentUserId;
            if ($fileForUserId > 0 && $fileForUserId !== $currentUserId) {
                $matchedMember = null;
                foreach ($this->getTeamForUser($currentUserId) as $member) {
                    if (isset($member['member_user_id']) && (int) $member['member_user_id'] === $fileForUserId) {
                        $matchedMember = $member;
                        break;
                    }
                }
                if ($matchedMember === null) {
                    return $this->respondError('You can only file on behalf of your own team members.');
                }
                $ownerUserId = $fileForUserId;
                $payableTo = isset($matchedMember['member_name']) ? (string) $matchedMember['member_name'] : $payableTo;
            }

            $payableToUserId = isset($data['PayableTo']) ? (int) $data['PayableTo'] : 0;
            if ($payableToUserId > 0) {
                $allowedPayableTo = $this->Lookup_model->get_payable_to_users((int) ($userInfo['department_id'] ?? 0));
                $isAllowedPayableTo = false;
                foreach ((is_array($allowedPayableTo) ? $allowedPayableTo : array()) as $payableUser) {
                    if ((int) ($payableUser['id'] ?? 0) === $payableToUserId) {
                        $isAllowedPayableTo = true;
                        break;
                    }
                }
                if (!$isAllowedPayableTo) {
                    return $this->respondError('Please select a valid Payable To from your department.');
                }
                $payableTo = (string) $payableToUserId;
            }

            if ($requestedReimbursementId !== '') {
                $existingHeader = $this->getDraftHeaderByReimbursementId($requestedReimbursementId);
                if (!$existingHeader) {
                    return $this->respondError('Draft reimbursement not found.');
                }

                $createdById = isset($existingHeader['created_by_id']) ? (int) $existingHeader['created_by_id'] : 0;
                $filedById = isset($existingHeader['filed_by']) ? (int) $existingHeader['filed_by'] : 0;
                if ($createdById !== $currentUserId && $filedById !== $currentUserId) {
                    return $this->respondError('You are not allowed to update this draft.');
                }

                $existingStatus = isset($existingHeader['status_code']) ? trim((string) $existingHeader['status_code']) : '';
                if ($existingStatus !== 'RMB_DRAFT') {
                    return $this->respondError('Only draft reimbursement can be updated.');
                }

                $ageDays = $this->getDraftAgeDays(isset($existingHeader['created_date']) ? $existingHeader['created_date'] : '');
                if ($ageDays > $this->draftEditWindowDays) {
                    return $this->respondError('Draft edit window has expired.');
                }

                try {
                    $this->Reimbursement_model->update_draft_header(
                        $requestedReimbursementId,
                        $createdById,
                        $totalAmount,
                        $statusCode,
                        $description,
                        $costCenterId,
                        $payableTo,
                        $address,
                        $ioNumber
                    );
                    $updateHeaderResult = TRUE;
                } catch (Exception $e) {
                    $updateHeaderResult = $e->getMessage();
                }

                if ($updateHeaderResult !== TRUE) {
                    return $this->respondError('Failed to update draft reimbursement header.');
                }

                try {
                    $this->Reimbursement_model->delete_details($requestedReimbursementId);
                    $deleteDetailResult = TRUE;
                } catch (Exception $e) {
                    $deleteDetailResult = $e->getMessage();
                }

                if ($deleteDetailResult !== TRUE) {
                    return $this->respondError('Failed to refresh draft expense details.');
                }

                $reimbursementId = $requestedReimbursementId;

                $this->logAuditTrail(
                    'REIMBURSEMENT',
                    $reimbursementId,
                    'UPDATED_DRAFT',
                    'HEADER',
                    $reimbursementId
                );
            }

            if ($reimbursementId === '') {
                $headerParams = array(
                    "UserId" => $ownerUserId,
                    "TotalAmount" => $totalAmount,
                    "StatusCode" => $statusCode,
                    "Description" => $description,
                    "CostCenterId" => $costCenterId,
                    "PayableTo" => $payableTo,
                    "Address" => $address,
                    "IoNumber" => $ioNumber,
                    "FiledBy" => $currentUserId,
                );

                $headerResult = $this->sp->createReturnId(
                    build_sp('sp_insert_reimbursement_header', count($headerParams)),
                    $headerParams
                );

                if (!is_array($headerResult) || !isset($headerResult['GeneratedReimbursementID']) || $headerResult['GeneratedReimbursementID'] === '') {
                    $dbMessage = is_array($headerResult) && isset($headerResult['message']) ? (string) $headerResult['message'] : '';
                    log_message('error', 'sp_insert_reimbursement_header failed for user ' . $ownerUserId . ': ' . json_encode($headerResult));

                    if (stripos($dbMessage, 'No approval matrix found') !== false) {
                        return $this->respondError('No approval workflow is set up yet for your department/amount range. Please contact your administrator.');
                    }

                    return $this->respondError('Failed to create reimbursement. Please try again or contact support.');
                }

                $reimbursementId = $headerResult['GeneratedReimbursementID'];

                if ($statusCode === 'RMB_DRAFT') {
                    $this->logAuditTrail(
                        'REIMBURSEMENT',
                        $reimbursementId,
                        'SAVED_DRAFT',
                        'HEADER',
                        $reimbursementId
                    );
                }
            }

            foreach ($data['Expenses'] as $index => $expense) {
                $actualAmount = isset($expense['ActualAmount']) ? (float) $expense['ActualAmount'] : (isset($expense['amount']) ? (float) $expense['amount'] : 0);
                $expenseCategory = isset($expense['ExpenseCategory']) ? trim((string) $expense['ExpenseCategory']) : (isset($expense['expenseType']) ? trim((string) $expense['expenseType']) : '');
                $isVatable = isset($expense['IsVatable']) ? (bool) $expense['IsVatable'] : (isset($expense['isVattable']) ? (bool) $expense['isVattable'] : false);

                if ($actualAmount <= 0 || $expenseCategory === '') {
                    return $this->respondError("Invalid expense item at index {$index}");
                }

                $vatAmount = isset($expense['VatAmount']) ? (float) $expense['VatAmount'] : ($isVatable ? round($actualAmount * 0.12 / 1.12, 2) : 0);
                $netAmount = isset($expense['NetAmount']) ? (float) $expense['NetAmount'] : ($isVatable ? round($actualAmount - $vatAmount, 2) : $actualAmount);

                $attachment = $this->collectAttachmentPaths($expense, $index);

                $detailParams = array(
                    "ReimbursementId" => $reimbursementId,
                    "Description" => isset($expense['Description']) ? $expense['Description'] : '',
                    "InvoiceReceiptNo" => isset($expense['InvoiceReceiptNo']) ? $expense['InvoiceReceiptNo'] : '',
                    "ActualAmount" => $actualAmount,
                    "DocumentDate" => isset($expense['DocumentDate']) ? $expense['DocumentDate'] : '',
                    "ExpenseCategory" => $expenseCategory,
                    "IsVatable" => $isVatable ? 1 : 0,
                    "NetAmount" => $netAmount,
                    "VatAmount" => $vatAmount,
                    "Attachment" => $attachment,
                    "VendorName" => isset($expense['VendorName']) ? $expense['VendorName'] : '',
                    "VendorAddress" => isset($expense['VendorAddress']) ? $expense['VendorAddress'] : '',
                    "VendorTin" => isset($expense['VendorTin']) ? $expense['VendorTin'] : '',
                );

                $detailResult = $this->sp->createData(
                    build_sp('sp_insert_reimbursement_details', count($detailParams)),
                    $detailParams
                );

                if ($detailResult !== TRUE) {
                    return $this->respondError("Failed to save expense detail at index {$index}");
                }
            }

            if ($statusCode === 'RMB_SUBMITTED') {
                $this->logAuditTrail(
                    'REIMBURSEMENT',
                    $reimbursementId,
                    'SUBMITTED',
                    'HEADER',
                    $reimbursementId
                );

                try {
                    $this->sp->readData(build_sp('sp_auto_approve_own_turn', 1), array('ReferenceId' => $reimbursementId), 'row');
                } catch (\Throwable $e) {
                    log_message('error', 'sp_auto_approve_own_turn failed for ' . $reimbursementId . ': ' . $e->getMessage());
                }

                $this->notifyFirstApprover($reimbursementId);
            }

            $message = $statusCode === 'RMB_DRAFT'
                ? 'Reimbursement draft saved successfully'
                : 'Reimbursement submitted successfully';

            return $this->respondSuccess($message, array('id' => $reimbursementId));
        } catch (Exception $e) {
            return $this->respondError("An error occurred: " . $e->getMessage());
        }
    }

    private function notifyFirstApprover($reimbursementId)
    {
        try {
            if (!$this->sp || !$this->sp->db) {
                return;
            }

            $header = $this->sp->db->get_where('tbl_approval_header', array('reference_id' => $reimbursementId, 'is_active' => 1), 1)->row_array();
            if (!is_array($header)) {
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

            $rmb = $this->sp->db->get_where('tbl_reimbursement_header', array('reimbursement_id' => $reimbursementId), 1)->row_array();
            $requesterInfo = (is_array($rmb) && !empty($rmb['user_id'])) ? get_user_info((int) $rmb['user_id']) : null;

            $approverName = trim((string) ($approverInfo['firstname'] ?? '') . ' ' . (string) ($approverInfo['lastname'] ?? ''));
            $requesterName = is_array($requesterInfo)
                ? trim((string) ($requesterInfo['firstname'] ?? '') . ' ' . (string) ($requesterInfo['lastname'] ?? ''))
                : '';

            $mergeData = array(
                'amount' => number_format((float) ($rmb['total_amount'] ?? 0), 2),
                'status' => 'PENDING',
                'remarks' => '',
                'action_date' => date('Y-m-d H:i:s'),
                'requester_name' => $requesterName,
                'requester_department' => is_array($requesterInfo) ? (string) ($requesterInfo['short_name'] ?? '') : '',
                'approver_name' => $approverName,
            );

            notify_event('TXN_SUBMITTED', 'REIMBURSEMENT', $reimbursementId, array(array(
                'email' => $approverInfo['email'],
                'name' => $approverName !== '' ? $approverName : $approverInfo['email'],
                'user_id' => (int) $firstApprover['approver_id'],
            )), $mergeData);
        } catch (Throwable $e) {
            log_message('error', 'notifyFirstApprover failed for ' . $reimbursementId . ': ' . $e->getMessage());
        }
    }

    public function api_update_reimbursement()
    {
        try {
            $this->output->set_content_type('application/json');
            $data = $this->getRequestPayload();

            if (isset($data['Expenses']) && is_string($data['Expenses'])) {
                $decodedExpenses = json_decode($data['Expenses'], true);
                if (is_array($decodedExpenses)) {
                    $data['Expenses'] = $decodedExpenses;
                }
            }

            $reimbursementId = isset($data['ReimbursementId']) ? trim((string) $data['ReimbursementId']) : '';
            if ($reimbursementId === '') {
                return $this->respondError('Missing required field: ReimbursementId');
            }

            $existingHeader = $this->getDraftHeaderByReimbursementId($reimbursementId);
            if (!$existingHeader) {
                return $this->respondError('Reimbursement not found.');
            }

            $currentUserId = (int) $this->session->userdata('user_id');
            $createdById = isset($existingHeader['created_by_id']) ? (int) $existingHeader['created_by_id'] : 0;
            $filedById = isset($existingHeader['filed_by']) ? (int) $existingHeader['filed_by'] : 0;
            if ($createdById !== $currentUserId && $filedById !== $currentUserId) {
                return $this->respondError('You are not allowed to update this reimbursement.');
            }

            $existingStatus = isset($existingHeader['status_code']) ? trim((string) $existingHeader['status_code']) : '';
            if ($existingStatus !== 'RMB_SUBMITTED' && $existingStatus !== 'RMB_REJECTED') {
                return $this->respondError('Only submitted or rejected reimbursements can be updated.');
            }

            if ($existingStatus === 'RMB_SUBMITTED' && $this->hasApproverApproved($reimbursementId)) {
                return $this->respondError('This reimbursement can no longer be edited because an approver has already approved it.');
            }

            if (!isset($data['Expenses']) || !is_array($data['Expenses']) || count($data['Expenses']) === 0) {
                return $this->respondError('Missing required field: Expenses');
            }

            $totalAmount = isset($data['TotalAmount']) ? (float) $data['TotalAmount'] : 0;
            if ($totalAmount <= 0) {
                return $this->respondError('Missing required field: TotalAmount');
            }

            $description = isset($data['Description']) ? trim((string) $data['Description']) : '';
            $statusCode = isset($data['StatusCode']) && $data['StatusCode'] !== '' ? trim((string) $data['StatusCode']) : 'RMB_SUBMITTED';

            $costCenterId = isset($data['CostCenterId']) ? trim((string) $data['CostCenterId']) : '';
            $payableTo = isset($existingHeader['payable_to']) ? trim((string) $existingHeader['payable_to']) : '';
            $address = isset($data['Address']) ? trim((string) $data['Address']) : '';
            $ioNumber = isset($data['IoNumber']) ? trim((string) $data['IoNumber']) : '';

            try {
                $this->Reimbursement_model->update_submitted_header(
                    $reimbursementId,
                    $createdById,
                    $totalAmount,
                    $statusCode,
                    $description,
                    $costCenterId,
                    $payableTo,
                    $address,
                    $ioNumber
                );
                $updateHeaderResult = TRUE;
            } catch (Exception $e) {
                $updateHeaderResult = $e->getMessage();
            }

            if ($updateHeaderResult !== TRUE) {
                return $this->respondError('Failed to update reimbursement header.');
            }

            $restartRoute = $existingStatus === 'RMB_REJECTED' && $statusCode === 'RMB_SUBMITTED';
            if ($restartRoute) {
                try {
                    $this->sp->readData(
                        build_sp('sp_restart_approval_route', 2),
                        array('ReferenceId' => $reimbursementId, 'UserId' => $currentUserId),
                        'result'
                    );
                } catch (\Throwable $e) {
                    return $this->respondError('Reimbursement was saved but could not be routed for approval: ' . $e->getMessage());
                }
            }

            $approvals = $this->Reimbursement_model->list_approvals($reimbursementId);

            $approvedItemIds = array();
            $itemApprovalStatus = array();
            if (is_array($approvals)) {
                foreach ($approvals as $approval) {
                    $lineItemId = isset($approval['line_item_id']) ? (int) $approval['line_item_id'] : 0;
                    $status = isset($approval['status']) ? trim((string) $approval['status']) : '';
                    if ($lineItemId > 0) {
                        if (!isset($itemApprovalStatus[$lineItemId])) {
                            $itemApprovalStatus[$lineItemId] = array();
                        }
                        $itemApprovalStatus[$lineItemId][] = $status;
                    }
                }
            }

            foreach ($itemApprovalStatus as $itemId => $statuses) {
                $allApproved = true;
                foreach ($statuses as $status) {
                    if ($status !== 'APPROVED') {
                        $allApproved = false;
                        break;
                    }
                }
                if ($allApproved && count($statuses) > 0) {
                    $approvedItemIds[] = $itemId;
                }
            }

            try {
                $this->Reimbursement_model->delete_details_except($reimbursementId, implode(',', $approvedItemIds));
                $deleteDetailResult = TRUE;
            } catch (Exception $e) {
                $deleteDetailResult = $e->getMessage();
            }

            if ($deleteDetailResult !== TRUE) {
                return $this->respondError('Failed to refresh expense details.');
            }

            foreach ($data['Expenses'] as $index => $expense) {
                $itemId = isset($expense['Id']) ? trim((string) $expense['Id']) : '';
                $isNew = isset($expense['IsNew']) ? (bool) $expense['IsNew'] : false;

                if (!$isNew && $itemId !== '') {
                    $itemIdNum = (int) $itemId;
                    if (in_array($itemIdNum, $approvedItemIds)) {
                        continue;
                    }
                }

                $actualAmount = isset($expense['ActualAmount']) ? (float) $expense['ActualAmount'] : 0;
                $expenseCategory = isset($expense['ExpenseCategory']) ? trim((string) $expense['ExpenseCategory']) : '';
                $isVatable = isset($expense['IsVatable']) ? (bool) $expense['IsVatable'] : false;

                if ($actualAmount <= 0 || $expenseCategory === '') {
                    return $this->respondError('Invalid expense item at index ' . $index);
                }

                $vatAmount = $isVatable ? round($actualAmount * 0.12 / 1.12, 2) : 0;
                $netAmount = $isVatable ? round($actualAmount - $vatAmount, 2) : $actualAmount;
                $attachment = $this->collectAttachmentPaths($expense, $index);

                $detailParams = array(
                    'ReimbursementId' => $reimbursementId,
                    'Description' => isset($expense['Description']) ? $expense['Description'] : '',
                    'InvoiceReceiptNo' => isset($expense['InvoiceReceiptNo']) ? $expense['InvoiceReceiptNo'] : '',
                    'ActualAmount' => $actualAmount,
                    'DocumentDate' => isset($expense['DocumentDate']) ? $expense['DocumentDate'] : '',
                    'ExpenseCategory' => $expenseCategory,
                    'IsVatable' => $isVatable ? 1 : 0,
                    'NetAmount' => $netAmount,
                    'VatAmount' => $vatAmount,
                    'Attachment' => $attachment,
                    'VendorName' => isset($expense['VendorName']) ? $expense['VendorName'] : '',
                    'VendorAddress' => isset($expense['VendorAddress']) ? $expense['VendorAddress'] : '',
                    'VendorTin' => isset($expense['VendorTin']) ? $expense['VendorTin'] : '',
                );

                $detailResult = $this->sp->createData(
                    build_sp('sp_insert_reimbursement_details', count($detailParams)),
                    $detailParams
                );

                if ($detailResult !== TRUE) {
                    return $this->respondError('Failed to save expense detail at index ' . $index);
                }
            }

            $this->logAuditTrail(
                'REIMBURSEMENT',
                $reimbursementId,
                'RESUBMITTED',
                'HEADER',
                $reimbursementId
            );

            if ($restartRoute) {
                try {
                    $this->sp->readData(build_sp('sp_auto_approve_own_turn', 1), array('ReferenceId' => $reimbursementId), 'row');
                } catch (\Throwable $e) {
                    log_message('error', 'sp_auto_approve_own_turn failed for ' . $reimbursementId . ': ' . $e->getMessage());
                }

                $this->notifyFirstApprover($reimbursementId);
            }

            $message = $statusCode === 'RMB_DRAFT'
                ? 'Reimbursement saved as draft successfully'
                : 'Reimbursement updated and resubmitted successfully';

            return $this->respondSuccess($message, array('id' => $reimbursementId));
        } catch (Exception $e) {
            return $this->respondError('An error occurred: ' . $e->getMessage());
        }
    }

    public function api_get_list()
    {
        try {
            $this->output->set_content_type('application/json');
            $userId = $this->session->userdata('user_id');
            $cursorIdRaw = $this->input->post('CursorId');
            $takeRaw = $this->input->post('Take');

            $take = (int) $takeRaw;
            if ($take <= 0) {
                $take = 20;
            }

            $cursorId = null;
            if ($cursorIdRaw !== null && $cursorIdRaw !== '') {
                $cursorId = (int) $cursorIdRaw;
            }

            $result = $this->Reimbursement_model->list_by_user($userId, $cursorId, $take);

            $hasMore = count($result) > $take;
            if ($hasMore) {
                array_pop($result);
            }

            $nextCursorId = null;
            if (!empty($result)) {
                $lastRow = end($result);
                $nextCursorId = isset($lastRow['id']) ? (int) $lastRow['id'] : null;
            }

            echo json_encode(array(
                'status' => 'success',
                'data' => $result,
                'pagination' => array(
                    'take' => $take,
                    'hasMore' => $hasMore,
                    'nextCursorId' => $nextCursorId,
                ),
            ));
        } catch (Exception $e) {
            echo json_encode(array(
                'status' => 'error',
                'response' => "An error occurred: " . $e->getMessage(),
            ));
        }
    }

    public function api_delete_draft()
    {
        try {
            $this->output->set_content_type('application/json');
            $data = $this->getRequestPayload();

            $reimbursementId = isset($data['ReimbursementId']) ? trim((string) $data['ReimbursementId']) : '';
            if ($reimbursementId === '') {
                return $this->respondError('Missing required field: ReimbursementId');
            }

            $header = $this->getDraftHeaderByReimbursementId($reimbursementId);
            if (!$header) {
                return $this->respondError('Reimbursement not found.');
            }

            $currentUserId = (int) $this->session->userdata('user_id');
            $createdById = isset($header['created_by_id']) ? (int) $header['created_by_id'] : 0;
            $filedById = isset($header['filed_by']) ? (int) $header['filed_by'] : 0;
            if ($createdById !== $currentUserId && $filedById !== $currentUserId) {
                return $this->respondError('You are not allowed to delete this reimbursement.');
            }

            $statusCode = isset($header['status_code']) ? trim((string) $header['status_code']) : '';
            if ($statusCode !== 'RMB_DRAFT') {
                return $this->respondError('Only draft reimbursements can be deleted.');
            }

            $params = array(
                'ReimbursementId' => $reimbursementId,
            );

            $result = $this->sp->createData(
                build_sp('sp_delete_reimbursement_draft', count($params)),
                $params
            );

            if ($result !== TRUE) {
                return $this->respondError('Failed to delete reimbursement draft.');
            }

            return $this->respondSuccess('Reimbursement draft deleted successfully');
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


    private function getDraftHeaderByReimbursementId($reimbursementId)
    {
        return $this->Reimbursement_model->find_header($reimbursementId);
    }

    private function getDraftAgeDays($createdDate)
    {
        $createdTimestamp = strtotime((string) $createdDate);
        if ($createdTimestamp === false) {
            return 999999;
        }

        $seconds = time() - $createdTimestamp;
        if ($seconds < 0) {
            $seconds = 0;
        }

        return (int) floor($seconds / 86400);
    }

    private function ensureAttachmentDir()
    {
        $targetDir = 'assets/uploads/attachments/';
        $absoluteDir = rtrim(FCPATH, '/\\\\') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $targetDir);

        if (!is_dir($absoluteDir)) {
            mkdir($absoluteDir, 0777, true);
        }

        return array($targetDir, $absoluteDir);
    }

    private function normalizeSingleFileFromInput($fileInput, $index = null)
    {
        if (!isset($fileInput['name'])) {
            return null;
        }

        if (is_array($fileInput['name'])) {
            if ($index === null || !isset($fileInput['name'][$index]) || $fileInput['name'][$index] === '') {
                return null;
            }

            return array(
                'name' => $fileInput['name'][$index],
                'type' => isset($fileInput['type'][$index]) ? $fileInput['type'][$index] : '',
                'tmp_name' => isset($fileInput['tmp_name'][$index]) ? $fileInput['tmp_name'][$index] : '',
                'error' => isset($fileInput['error'][$index]) ? $fileInput['error'][$index] : UPLOAD_ERR_NO_FILE,
                'size' => isset($fileInput['size'][$index]) ? $fileInput['size'][$index] : 0,
            );
        }

        return $fileInput;
    }

    private function saveUploadedFile($file, $targetDir, $absoluteDir)
    {
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return null;
        }

        $originalName = isset($file['name']) ? $file['name'] : '';
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        $safeName = uniqid('att_', true) . ($extension ? '.' . strtolower($extension) : '');
        $targetPath = $absoluteDir . $safeName;

        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            return null;
        }

        return $safeName;
    }

    private function saveBase64Attachment($base64Data, $targetDir, $absoluteDir)
    {
        if (!is_string($base64Data) || stripos($base64Data, 'data:') !== 0) {
            return null;
        }

        $parts = explode(',', $base64Data, 2);
        if (count($parts) !== 2) {
            return null;
        }

        $meta = $parts[0];
        $payload = base64_decode($parts[1], true);
        if ($payload === false) {
            return null;
        }

        $extension = 'bin';
        if (preg_match('#^data:([^;]+);base64$#i', $meta, $matches)) {
            $mime = strtolower($matches[1]);
            if (strpos($mime, 'image/jpeg') === 0) {
                $extension = 'jpg';
            } elseif (strpos($mime, 'image/png') === 0) {
                $extension = 'png';
            } elseif (strpos($mime, 'image/webp') === 0) {
                $extension = 'webp';
            } elseif (strpos($mime, 'application/pdf') === 0) {
                $extension = 'pdf';
            }
        }

        $safeName = uniqid('att_', true) . '.' . $extension;
        $targetPath = $absoluteDir . $safeName;
        $written = file_put_contents($targetPath, $payload);
        if ($written === false) {
            return null;
        }

        return $safeName;
    }

    private function collectAttachmentPaths($expense, $index)
    {
        list($targetDir, $absoluteDir) = $this->ensureAttachmentDir();
        $savedPaths = array();
        $hasNewUpload = false;

        $fileCandidates = array(
            'Attachment_' . $index,
            'attachments_' . $index,
            'Attachment',
            'attachments',
        );

        foreach ($fileCandidates as $fieldName) {
            if (!isset($_FILES[$fieldName])) {
                continue;
            }

            $singleFile = $this->normalizeSingleFileFromInput($_FILES[$fieldName], null);
            if ($singleFile && isset($singleFile['error']) && (int) $singleFile['error'] === UPLOAD_ERR_OK) {
                $savedPath = $this->saveUploadedFile($singleFile, $targetDir, $absoluteDir);
                if ($savedPath) {
                    $savedPaths[] = $savedPath;
                    $hasNewUpload = true;
                }
            }

            if (is_array($_FILES[$fieldName]['name'])) {
                foreach ($_FILES[$fieldName]['name'] as $fileIndex => $ignored) {
                    $file = $this->normalizeSingleFileFromInput($_FILES[$fieldName], $fileIndex);
                    if ($file && isset($file['error']) && (int) $file['error'] === UPLOAD_ERR_OK) {
                        $savedPath = $this->saveUploadedFile($file, $targetDir, $absoluteDir);
                        if ($savedPath) {
                            $savedPaths[] = $savedPath;
                            $hasNewUpload = true;
                        }
                    }
                }
            }
        }

        if (isset($_FILES['attachments']) && isset($_FILES['attachments']['name']) && is_array($_FILES['attachments']['name'])) {
            if (isset($_FILES['attachments']['name'][$index])) {
                $itemNames = $_FILES['attachments']['name'][$index];
                if (is_array($itemNames)) {
                    foreach ($itemNames as $innerIndex => $ignored) {
                        $file = array(
                            'name' => $_FILES['attachments']['name'][$index][$innerIndex],
                            'type' => isset($_FILES['attachments']['type'][$index][$innerIndex]) ? $_FILES['attachments']['type'][$index][$innerIndex] : '',
                            'tmp_name' => isset($_FILES['attachments']['tmp_name'][$index][$innerIndex]) ? $_FILES['attachments']['tmp_name'][$index][$innerIndex] : '',
                            'error' => isset($_FILES['attachments']['error'][$index][$innerIndex]) ? $_FILES['attachments']['error'][$index][$innerIndex] : UPLOAD_ERR_NO_FILE,
                            'size' => isset($_FILES['attachments']['size'][$index][$innerIndex]) ? $_FILES['attachments']['size'][$index][$innerIndex] : 0,
                        );
                        if ((int) $file['error'] === UPLOAD_ERR_OK) {
                            $savedPath = $this->saveUploadedFile($file, $targetDir, $absoluteDir);
                            if ($savedPath) {
                                $savedPaths[] = $savedPath;
                                $hasNewUpload = true;
                            }
                        }
                    }
                }
            }
        }

        if (!$hasNewUpload) {
            $base64Candidates = array();
            if (isset($expense['Attachment'])) {
                $base64Candidates[] = $expense['Attachment'];
            }
            if (isset($expense['attachments']) && is_array($expense['attachments'])) {
                foreach ($expense['attachments'] as $attachmentItem) {
                    if (is_string($attachmentItem)) {
                        $base64Candidates[] = $attachmentItem;
                    } elseif (is_array($attachmentItem) && isset($attachmentItem['data'])) {
                        $base64Candidates[] = $attachmentItem['data'];
                    }
                }
            }

            foreach ($base64Candidates as $candidate) {
                $candidateText = is_string($candidate) ? trim($candidate) : '';
                if ($candidateText === '') {
                    continue;
                }

                if (stripos($candidateText, 'data:') === 0) {
                    $savedPath = $this->saveBase64Attachment($candidateText, $targetDir, $absoluteDir);
                    if ($savedPath) {
                        $savedPaths[] = $savedPath;
                    }
                    continue;
                }

                $existingNames = explode(',', $candidateText);
                foreach ($existingNames as $existingName) {
                    $trimmedName = trim($existingName);
                    if ($trimmedName === '') {
                        continue;
                    }

                    if (preg_match('/^[A-Za-z0-9._-]+$/', $trimmedName)) {
                        $savedPaths[] = $trimmedName;
                    }
                }
            }
        }

        if (!count($savedPaths)) {
            return null;
        }

        $savedPaths = array_values(array_unique($savedPaths));
        if (count($savedPaths) === 1) {
            return $savedPaths[0];
        }

        return implode(',', $savedPaths);
    }

    private function getGroqApiKey()
    {
        $key = getenv('GROQ_API_KEY');
        if ($key) {
            return trim($key);
        }

        if (defined('GROQ_API_KEY')) {
            return trim(GROQ_API_KEY);
        }

        return '';
    }

    private function normalizeOcrDate($value)
    {
        $raw = is_string($value) ? trim($value) : '';
        if ($raw === '') {
            return '';
        }

        if (preg_match('/^\\d{4}-\\d{2}-\\d{2}$/', $raw)) {
            return $raw;
        }

        $timestamp = strtotime($raw);
        if ($timestamp === false) {
            return '';
        }

        return date('Y-m-d', $timestamp);
    }

    private function parseOcrJsonFromText($text)
    {
        if (!is_string($text) || trim($text) === '') {
            return null;
        }

        $trimmed = trim($text);
        $decoded = json_decode($trimmed, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        if (preg_match('/\\{[\\s\\S]*\\}/', $trimmed, $matches)) {
            $decoded = json_decode($matches[0], true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return null;
    }

    public function api_ocr_receipt()
    {
        try {
            $this->output->set_content_type('application/json');

            $apiKey = $this->getGroqApiKey();
            if ($apiKey === '') {
                log_message('error', 'Groq OCR: GROQ_API_KEY is not configured.');
                return $this->respondError("Receipt scanning isn't available right now. Please enter the details manually.");
            }

            if (!isset($_FILES['image'])) {
                return $this->respondError('Missing image file.');
            }

            $file = $_FILES['image'];
            if (!isset($file['error']) || (int) $file['error'] !== UPLOAD_ERR_OK) {
                return $this->respondError('Image upload failed.');
            }

            if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
                return $this->respondError('Invalid uploaded image.');
            }

            $mimeType = isset($file['type']) ? strtolower($file['type']) : '';
            if (strpos($mimeType, 'image/') !== 0) {
                return $this->respondError('Only image files are supported for OCR.');
            }

            $binary = file_get_contents($file['tmp_name']);
            if ($binary === false || $binary === '') {
                return $this->respondError('Unable to read uploaded image.');
            }

            $imageDataUrl = 'data:' . $mimeType . ';base64,' . base64_encode($binary);

            $prompt = "Extract receipt fields and respond with STRICT JSON only. Use keys: document_date, invoice_receipt_no, actual_amount, description, expense_category_name, is_vatable, vendor_name, vendor_address, vendor_tin. document_date must be YYYY-MM-DD if possible. actual_amount must be number only. is_vatable must be true or false. vendor_tin should be numeric TIN with dashes if present (e.g., 123-456-789-000). If unknown, return empty string for text fields and 0 for amount.";

            $ocrResult = groq_ocr_extract($imageDataUrl, $prompt, $apiKey, array(
                'endpoint' => 'transactions/reimbursement/api/ocr',
                'user_id' => (int) $this->session->userdata('user_id'),
            ));

            if (!$ocrResult['ok']) {
                return $this->respondError($ocrResult['user_message']);
            }

            $ocr = $this->parseOcrJsonFromText($ocrResult['content']);
            if (!is_array($ocr)) {
                log_message('error', 'Groq OCR: response could not be parsed as JSON. Raw content: ' . $ocrResult['content']);
                return $this->respondError("We couldn't read this receipt clearly. Please try again, or enter the details manually.");
            }

            $result = array(
                'document_date' => $this->normalizeOcrDate(isset($ocr['document_date']) ? $ocr['document_date'] : ''),
                'invoice_receipt_no' => isset($ocr['invoice_receipt_no']) ? (string) $ocr['invoice_receipt_no'] : '',
                'actual_amount' => isset($ocr['actual_amount']) ? (float) $ocr['actual_amount'] : 0,
                'description' => isset($ocr['description']) ? (string) $ocr['description'] : '',
                'expense_category_name' => isset($ocr['expense_category_name']) ? (string) $ocr['expense_category_name'] : '',
                'is_vatable' => isset($ocr['is_vatable']) ? (bool) $ocr['is_vatable'] : false,
                'vendor_name' => isset($ocr['vendor_name']) ? (string) $ocr['vendor_name'] : '',
                'vendor_address' => isset($ocr['vendor_address']) ? (string) $ocr['vendor_address'] : '',
                'vendor_tin' => isset($ocr['vendor_tin']) ? (string) $ocr['vendor_tin'] : '',
            );

            return $this->respondSuccess('success', $result);
        } catch (Exception $e) {
            return $this->respondError('An error occurred: ' . $e->getMessage());
        }
    }
}
