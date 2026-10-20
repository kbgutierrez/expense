<?php

require 'vendor/autoload.php';
(defined('BASEPATH')) or exit('No direct script access allowed');
class Cash_Advance extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('SPModel', 'sp');
        $this->sp->setDatabase('dbknet');
        $this->load->model('Cash_advance_model');
        $this->load->model('Lookup_model');
    }

    public function index()
    {
        $userId = $this->session->userdata('user_id');
        $pendingCa = $this->getPendingCashAdvance($userId);

        $data = array(
            'title' => 'Cash Advance',
            'main_view' => '../modules/cash-advance/views/index',
            'module_group' => $this->module_group,
            'module' => $this->module,
            'has_pending_ca' => !empty($pendingCa),
            'pending_ca_id' => $pendingCa ? $pendingCa['cash_advance_id'] : '',
            'pending_ca_status' => $pendingCa ? $pendingCa['status_name'] : '',
            'scripts' => array(
                '../cash-advance/index.js',
            ),
        );

        $this->load->view('main', $data);
    }

    private function getPendingCashAdvance($userId)
    {
        $result = $this->Cash_advance_model->find_pending_by_user($userId);

        if (is_array($result) && isset($result['cash_advance_id']) && !empty($result['cash_advance_id'])) {
            return $result;
        }

        return null;
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

    public function add()
    {
        $userId = $this->session->userdata('user_id');
        $departmentId = $this->session->userdata('user_info')['department_id'];
        $pendingCa = $this->getPendingCashAdvance($userId);

        if (!empty($pendingCa)) {
            redirect('transactions/cash-advance');
            return;
        }

   
        $costCenters = $this->Lookup_model->get_cost_centers_by_department($departmentId);

        $data = array(
            'title' => 'New Cash Advance',
            'main_view' => '../modules/cash-advance/views/add',
            'module_group' => $this->module_group,
            'cost_centers' => $costCenters,
            'module' => $this->module,
            'scripts' => array(

                '../cash-advance/add.js',
            ),
        );
    
        $this->load->view('main', $data);
    }

    public function view($cash_advance_no = '')
    {
        $resumeKflowUrl = '';
        $caNo = trim((string) $cash_advance_no);
        if ($caNo !== '') {
            $row = $this->Cash_advance_model->get_details($caNo);

            $isOpenCa = is_array($row) && isset($row['status_code']) && trim((string) $row['status_code']) === 'CA_PENDING';
            if ($isOpenCa && isset($row['kflow_doc_status']) && !in_array(trim((string) $row['kflow_doc_status']), array('3', '4', '5'), true)) {
                $batchId = isset($row['kflow_batch_id']) ? trim((string) $row['kflow_batch_id']) : '';
                if ($batchId !== '') {
                    $resumeKflowUrl = $this->buildKflowEmbedUrl($batchId);
                }
            }
        }

        $data = array(
            'title' => 'Cash Advance Details',
            'main_view' => '../modules/cash-advance/views/detail',
            'module_group' => $this->module_group,
            'module' => $this->module,
            'cash_advance_no' => $cash_advance_no,
            'resume_kflow_url' => $resumeKflowUrl,
            'current_user_id' => (int) $this->session->userdata('user_id'),
            'scripts' => array(

                '../cash-advance/detail.js',
                '../cash-advance/index.js',
            ),
        );

        $this->load->view('main', $data);
    }



    public function api_get()
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

            $result = $this->Cash_advance_model->list_by_user($userId, $cursorId, $take);

            $payload = $this->buildPaginationResult($result, $take);

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

    public function api_get_detail()
    {
        try {
            $this->output->set_content_type('application/json');
            $data = $this->getRequestPayload();

            $cashAdvanceId = isset($data['CashAdvanceId']) ? trim((string) $data['CashAdvanceId']) : '';
            if ($cashAdvanceId === '') {
                return $this->respondError('Missing required field: CashAdvanceId');
            }

            $result = $this->Cash_advance_model->get_details($cashAdvanceId);

            if (!$result) {
                return $this->respondError('Cash advance not found.');
            }

            $result = $this->decorateCaDocumentState($result);

            return $this->respondSuccess('success', $result);
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

    private function normalizeRelativeAssetPath($path)
    {
        $value = trim((string) $path);
        if ($value === '') {
            return '';
        }

        $value = str_replace('\\', '/', $value);

        if (preg_match('#^https?://#i', $value)) {
            return '';
        }

        if (strpos($value, 'assets/') === 0) {
            return $value;
        }

        $assetsPos = stripos($value, '/assets/');
        if ($assetsPos !== false) {
            return ltrim(substr($value, $assetsPos + 1), '/');
        }

        return '';
    }

    private function fileExistsFromRelativePath($relativePath)
    {
        $relative = $this->normalizeRelativeAssetPath($relativePath);
        if ($relative === '') {
            return false;
        }

        $absolute = FCPATH . str_replace('/', DIRECTORY_SEPARATOR, $relative);
        return is_file($absolute);
    }

    private function buildPublicUrlFromRelativePath($relativePath)
    {
        $relative = $this->normalizeRelativeAssetPath($relativePath);
        if ($relative === '') {
            return '';
        }

        return base_url($relative);
    }

    private function buildKflowEmbedUrl($batchId)
    {
        $batchValue = trim((string) $batchId);
        if ($batchValue === '') {
            return '';
        }

        $secret = $this->config->item('kflow_secret_key');
        $baseUrl = $this->config->item('kflow_base_url');
        if (empty($secret) || empty($baseUrl)) {
            return '';
        }

        $token = hash_hmac('sha256', $batchValue, $secret);
        return $baseUrl
            . 'workflow/document_list/upload_knet?batch_id=' . urlencode($batchValue)
            . '&token=' . urlencode($token);
    }

    private function decorateCaDocumentState($row)
    {
        if (!is_array($row)) {
            return $row;
        }

        $caRef = trim((string) ($row['cash_advance_id'] ?? ''));
        $kflowDocStatus = trim((string) ($row['kflow_doc_status'] ?? ''));
        $isFinalApproved = ($kflowDocStatus === '4');

        $unsignedCandidates = array();
        if (!empty($row['unsigned_pdf_path'])) {
            $unsignedCandidates[] = $row['unsigned_pdf_path'];
        }
        if (!empty($row['generated_pdf_path'])) {
            $unsignedCandidates[] = $row['generated_pdf_path'];
        }
        if ($caRef !== '') {
            $unsignedCandidates[] = 'assets/uploads/cash_advance/unsigned/' . $caRef . '_unsigned.pdf';
        }

        $unsignedRelativePath = '';
        foreach ($unsignedCandidates as $candidate) {
            $relativeCandidate = $this->normalizeRelativeAssetPath($candidate);
            if ($relativeCandidate !== '' && $this->fileExistsFromRelativePath($relativeCandidate)) {
                $unsignedRelativePath = $relativeCandidate;
                break;
            }
        }

        $signedRelativePath = '';
        if (!empty($row['final_pdf_path'])) {
            $relativeSigned = $this->normalizeRelativeAssetPath($row['final_pdf_path']);
            if ($relativeSigned !== '' && $this->fileExistsFromRelativePath($relativeSigned)) {
                $signedRelativePath = $relativeSigned;
            }
        }

        $hasSignedPdf = ($signedRelativePath !== '');
        $isSignedView = $isFinalApproved && $hasSignedPdf;
        $activeRelativePath = $isSignedView ? $signedRelativePath : $unsignedRelativePath;

        $row['is_final_approved'] = $isFinalApproved ? 1 : 0;
        $row['unsigned_pdf_path'] = $unsignedRelativePath;
        $row['unsigned_pdf_url'] = $this->buildPublicUrlFromRelativePath($unsignedRelativePath);
        $row['signed_pdf_path'] = $signedRelativePath;
        $row['signed_pdf_url'] = $this->buildPublicUrlFromRelativePath($signedRelativePath);
        $row['active_pdf_path'] = $activeRelativePath;
        $row['active_pdf_url'] = $this->buildPublicUrlFromRelativePath($activeRelativePath);

        $batchId = trim((string) ($row['kflow_batch_id'] ?? ''));
        $row['kflow_embed_url'] = $isSignedView ? '' : $this->buildKflowEmbedUrl($batchId);

        return $row;
    }

    public function api_save()
    {
        try {
            $this->output->set_content_type('application/json');

            $data = $this->input->post();

            $requiredFields = array('Amount', 'Description', 'NeededDate', 'PayableTo', 'Address', 'CostCenterId', 'AmountInWords');
            foreach ($requiredFields as $field) {
                if (!isset($data[$field]) || trim($data[$field]) === '') {
                    return $this->respondError("Missing required field: {$field}");
                }
            }

            $userId = $this->session->userdata('user_id');
            $userFullName = $this->session->userdata('user_full_name') ?? 'Employee';

             $params = array(
                "UserId" => $userId,
                "Amount" => $data['Amount'],
                "AmountInWords" => $data['AmountInWords'],
                "Description" => $data['Description'],
                "NeededDate" => $data['NeededDate'],
                "PayableTo" => $data['PayableTo'],
                "Address" => $data['Address'],
                "CostCenterId" => $data['CostCenterId'],
                "IONumber" => isset($data['IONumber']) ? trim($data['IONumber']) : '',
            );

            $result = $this->sp->createReturnId(
                build_sp('sp_insert_ca_v2', count($params)),
                $params,
                'result'
            );

            $caRef = is_array($result) ? trim((string) ($result['GeneratedCashAdvanceID'] ?? '')) : '';

            if ($caRef === '') {
                $dbMessage = is_array($result) && isset($result['message']) ? (string) $result['message'] : '';
                log_message('error', 'sp_insert_ca_v2 failed for user ' . $userId . ': ' . json_encode($result));

                if (stripos($dbMessage, 'No approval matrix found') !== false) {
                    return $this->respondError('No approval workflow is set up yet for your department/amount range. Please contact your administrator.');
                }

                return $this->respondError($dbMessage !== '' ? $dbMessage : 'Failed to create cash advance request.');
            }

            $cashAdvanceId = $result['id'] ?? $caRef;

            try {
                $this->sp->readData(build_sp('sp_auto_approve_own_turn', 1), array('ReferenceId' => $caRef), 'row');
            } catch (\Throwable $e) {
                log_message('error', 'sp_auto_approve_own_turn failed for ' . $caRef . ': ' . $e->getMessage());
            }

            $bridgeBase = $this->config->item('kflow_bridge_path');
            $batchId = 'KNET_' . $caRef . '_' . uniqid();
            $batchDir = $bridgeBase . $batchId . '/';
            $attachDir = $batchDir . 'attachments/';

            if (!is_dir($batchDir)) {
                mkdir($batchDir, 0777, true);
                mkdir($attachDir, 0777, true);
            }

            $this->load->helper('ca_pdf');

            $templatePath = FCPATH . 'assets/templates/ca_template.pdf';
            $pdfOutputPath = $batchDir . $caRef . '.pdf';

            $addressLines = explode("\n", $data['Address']);
            $addressLine1 = $addressLines[0] ?? '';
            $addressLine2 = $addressLines[1] ?? '';

            $costCenterRow = $this->Lookup_model->get_cost_center($data['CostCenterId']);
            $costCenterName = $costCenterRow['cost_center_name'] ?? '';

            $payableToDisplay = trim((string) $data['PayableTo']);
            $payableToUserId = (int) $payableToDisplay;
            if ($payableToUserId > 0) {
                $payableToInfo = get_user_info($payableToUserId);
                if (is_array($payableToInfo) && (trim((string) ($payableToInfo['firstname'] ?? '')) !== '' || trim((string) ($payableToInfo['lastname'] ?? '')) !== '')) {
                    $payableToDisplay = trim(trim((string) ($payableToInfo['firstname'] ?? '')) . ' ' . trim((string) ($payableToInfo['lastname'] ?? '')));
                }
            }

            $pdfData = array(
                'Date' => $data['Date'],
                'CashAdvanceId' => $caRef,
                'PayableTo' => $payableToDisplay,
                'AddressLine1' => $addressLine1,
                'AddressLine2' => $addressLine2,
                'Amount' => $data['Amount'],
                'AmountInWords' => $data['AmountInWords'],
                'Description' => $data['Description'],
                'CostCenterName' => $costCenterName,
                'NeededDate' => $data['NeededDate'],
                'RequestedBy' => $userFullName,
            );

            generate_ca_pdf($pdfData, $templatePath, $pdfOutputPath);

            $unsignedRelativePath = 'assets/uploads/cash_advance/unsigned/' . $caRef . '_unsigned.pdf';
            $unsignedAbsolutePath = FCPATH . str_replace('/', DIRECTORY_SEPARATOR, $unsignedRelativePath);
            $unsignedDir = dirname($unsignedAbsolutePath);
            if (!is_dir($unsignedDir)) {
                mkdir($unsignedDir, 0777, true);
            }
            if (!@copy($pdfOutputPath, $unsignedAbsolutePath)) {
                return $this->respondError('Unable to prepare unsigned PDF preview.');
            }

            $attachmentManifest = array();
            if (!empty($_FILES['attachments'])) {
                $fileCount = count($_FILES['attachments']['name']);
                for ($i = 0; $i < $fileCount; $i++) {
                    if ($_FILES['attachments']['error'][$i] === 0) {
                        $origName = $_FILES['attachments']['name'][$i];
                        $ext = pathinfo($origName, PATHINFO_EXTENSION);
                        $safeName = 'att_' . $i . '_' . uniqid() . '.' . $ext;
                        $destPath = $attachDir . $safeName;
                        move_uploaded_file($_FILES['attachments']['tmp_name'][$i], $destPath);

                        $attachmentManifest[] = array(
                            'original_name' => $origName,
                            'stored_name' => $safeName,
                        );

                        try {
                            $this->Cash_advance_model->create_attachment($caRef, $origName, $safeName, $destPath);
                        } catch (Exception $e) {
                        }
                    }
                }
            }

            $manifest = array(
                'source' => 'knet_cash_advance',
                'ca_ref' => $caRef,
                'doc_title' => 'Cash Advance - ' . $caRef,
                'doc_description' => $data['Description'],
                'pdf_filename' => $caRef . '.pdf',
                'attachments' => $attachmentManifest,
                'created_at' => date('Y-m-d H:i:s'),
            );
            file_put_contents($batchDir . 'manifest.json', json_encode($manifest, JSON_PRETTY_PRINT));

            try {
                $this->Cash_advance_model->update_kflow_batch($caRef, $batchId, $pdfOutputPath, 1);
            } catch (Exception $e) {
            }

            $secret = $this->config->item('kflow_secret_key');
            $token = hash_hmac('sha256', $batchId, $secret);
            $kflowUrl = $this->config->item('kflow_base_url')
                . 'workflow/document_list/upload_knet?batch_id=' . urlencode($batchId)
                . '&token=' . urlencode($token);
            $viewUrl = base_url('transactions/cash-advance/view/' . rawurlencode($caRef))
                . '?open_workflow=1&kflow_url=' . urlencode($kflowUrl);

            return $this->respondSuccess(
                "Cash advance request created successfully.",
                array(
                    'id' => $cashAdvanceId,
                    'cash_advance_id' => $caRef,
                    'redirect_url' => $kflowUrl,
                    'kflow_url' => $kflowUrl,
                    'view_url' => $viewUrl,
                    'unsigned_pdf_path' => $unsignedRelativePath,
                    'unsigned_pdf_url' => base_url($unsignedRelativePath),
                )
            );

        } catch (Exception $e) {
            return $this->respondError("An error occurred: " . $e->getMessage());
        }
    }
    public function test_pdf_coords()
    {
        $this->load->helper('ca_pdf');

        $templatePath = FCPATH . 'assets/templates/ca_template.pdf';
        $testOutputPath = FCPATH . 'assets/temp/test_ca_output.pdf';

        if (!is_dir(FCPATH . 'assets/temp/')) {
            mkdir(FCPATH . 'assets/temp/', 0777, true);
        }

        $defaults = array(
            'Date' => array('x' => 25, 'y' => 31, 'text' => date('Y-m-d')),
            'ECA_No' => array('x' => 0, 'y' => 0, 'text' => 'ECA-2024-00001'),
            'PayableTo' => array('x' => 32, 'y' => 36, 'text' => 'Juan Dela Cruz'),
            'AddressLine1' => array('x' => 32, 'y' => 40, 'text' => '123 Main Street, Barangay Uno'),
            'AddressLine2' => array('x' => 32, 'y' => 45, 'text' => 'Quezon City, Metro Manila'),
            'Amount' => array('x' => 60, 'y' => 50, 'text' => '12,500.00'),
            'AmountInWords' => array('x' => 75, 'y' => 54, 'text' => 'Twelve Thousand Five Hundred Pesos Only'),
            'Purpose' => array('x' => 20, 'y' => 65, 'text' => 'Transportation and meal allowance for site visit at Cabuyao plant. This covers 5 days of field work including toll fees and parking.'),
            'CostCenter' => array('x' => 55, 'y' => 75, 'text' => 'Finance Department'),
            'DateNeeded' => array('x' => 38, 'y' => 80, 'text' => date('Y-m-d', strtotime('+3 days'))),
            'RequestedBy' => array('x' => 38, 'y' => 97, 'text' => 'Juan Dela Cruz'),
            'RequestDate' => array('x' => 80, 'y' => 97, 'text' => date('Y-m-d')),
        );

        $fields = array();
        foreach ($defaults as $key => $def) {
            $fields[$key] = array(
                'x' => $this->input->post("{$key}_x") !== null ? (float) $this->input->post("{$key}_x") : $def['x'],
                'y' => $this->input->post("{$key}_y") !== null ? (float) $this->input->post("{$key}_y") : $def['y'],
                'text' => $this->input->post("{$key}_text") !== null ? $this->input->post("{$key}_text") : $def['text'],
            );
        }

        $pdf = new \setasign\Fpdi\Fpdi();

        $pdf->setSourceFile($templatePath);
        $tplId = $pdf->importPage(1);

        $size = $pdf->getTemplateSize($tplId);

        $pdf->AddPage($size['orientation'], array($size['width'], $size['height']));
        $pdf->useTemplate($tplId, 0, 0, $size['width'], $size['height']);

        $pdf->SetAutoPageBreak(false);
        $pdf->SetFont('Arial', '', 10);

        foreach ($fields as $key => $f) {
            $pdf->SetXY($f['x'], $f['y']);


            $pdf->SetXY($f['x'], $f['y']);
            $pdf->SetTextColor(0, 0, 0);
            $pdf->Cell(0, 5, $f['text']);
        }

        $pageW = $size['width'];
        $pageH = $size['height'];

        $pdf->SetDrawColor(200, 200, 200);
        $pdf->SetTextColor(150, 150, 150);
        $pdf->SetFont('Arial', '', 6);

        for ($x = 0; $x <= $pageW; $x += 10) {
            $pdf->Line($x, 0, $x, $pageH);
            $pdf->SetXY($x + 1, 1);
            $pdf->Cell(5, 3, round($x));
        }

        for ($y = 0; $y <= $pageH; $y += 10) {
            $pdf->Line(0, $y, $pageW, $y);
            $pdf->SetXY(1, $y + 1);
            $pdf->Cell(5, 3, round($y));
        }

        $pdf->Output('F', $testOutputPath);

        $data = array(
            'title' => 'Test PDF Coordinates',
            'main_view' => '../modules/cash-advance/views/test_pdf_coords',
            'module_group' => $this->module_group,
            'module' => $this->module,
            'fields' => $fields,
            'pdf_url' => base_url('assets/temp/test_ca_output.pdf?v=' . time()),
            'template_url' => base_url('assets/templates/QMS-FM-FIN-02-03_Employee_Cash_Advance_Form.pdf'),
        );

        $this->load->view('main', $data);
    }

    public function api_cancel()
    {
        try {
            $this->output->set_content_type('application/json');
            $data = $this->getRequestPayload();

            $cashAdvanceId = isset($data['CashAdvanceId']) ? trim((string) $data['CashAdvanceId']) : '';
            $remarks = isset($data['Remarks']) ? trim((string) $data['Remarks']) : '';
            $userId = (int) $this->session->userdata('user_id');

            if ($cashAdvanceId === '') {
                return $this->respondError('Missing required field: CashAdvanceId');
            }
            if ($remarks === '') {
                return $this->respondError('Please enter the reason for cancelling.');
            }
            if ($userId <= 0) {
                return $this->respondError('User not authenticated.');
            }

            $params = array(
                'CashAdvanceId' => $cashAdvanceId,
                'UserId' => $userId,
                'Remarks' => $remarks,
                'Source' => 'KNET',
            );
            $result = $this->sp->createReturnId(
                build_sp('sp_cancel_cash_advance', count($params)),
                $params
            );

            if (!is_array($result) || (int) ($result['is_cancelled'] ?? 0) !== 1) {
                $message = is_array($result) && !empty($result['message']) ? $result['message'] : 'Failed to cancel the cash advance.';
                return $this->respondError($message);
            }

            $this->logAuditTrail('CASH_ADVANCE', $cashAdvanceId, 'CANCELLED', 'HEADER', $cashAdvanceId, 'remarks', null, $remarks);

            return $this->respondSuccess('Cash advance cancelled successfully.', $result);
        } catch (Exception $e) {
            return $this->respondError('An error occurred: ' . $e->getMessage());
        }
    }

    public function api_kflow_callback()
    {
        $this->output->set_content_type('application/json');

        $batchId = $this->input->post('batch_id');
        $kflowDocId = $this->input->post('kflow_doc_id');
        $status = $this->input->post('status');
        $signedPdfUrl = $this->input->post('signed_pdf_url');
        $hasUploadedSignedPdf = isset($_FILES['signed_pdf']) && !empty($_FILES['signed_pdf']['tmp_name']);

        $debug = array(
            'received' => array(
                'batch_id' => $batchId,
                'kflow_doc_id' => $kflowDocId,
                'status' => $status,
                'has_signed_pdf_file' => $hasUploadedSignedPdf,
                'has_signed_pdf_url' => !empty($signedPdfUrl),
            ),
            'steps' => array(),
        );

        if (empty($batchId) || empty($status)) {
            echo json_encode(array(
                'status' => 'error',
                'response' => 'Missing parameters',
                'debug' => $debug,
            ));
            return;
        }

        $ca = $this->Cash_advance_model->find_by_batch_id($batchId);
        $debug['steps']['fetch_ca_by_batch'] = $ca ? 'found' : 'not_found';

        if (!$ca) {
            echo json_encode(array(
                'status' => 'error',
                'response' => 'Cash advance not found for this batch.',
                'debug' => $debug,
            ));
            return;
        }

        $caRef = $ca['cash_advance_id'];

        $normalizedStatus = strtoupper(trim((string) $status));
        $isApproved = in_array($normalizedStatus, array('FULLY APPROVED', 'APPROVED', 'CA_KFLOW_APPROVED'), true);
        $isRejected = in_array($normalizedStatus, array('REJECTED', 'DISAPPROVED', 'DECLINED', 'CA_KFLOW_REJECTED'), true);
        $isCancelled = in_array($normalizedStatus, array('CANCELLED', 'CANCELED', 'CA_KFLOW_CANCELLED'), true);
        $debug['steps']['normalized_status'] = $normalizedStatus;

        if (!$isApproved && !$isRejected && !$isCancelled) {
            echo json_encode(array(
                'status' => 'error',
                'response' => 'Invalid callback status.',
                'debug' => $debug,
            ));
            return;
        }

        if ($isCancelled) {
            $cancelParams = array(
                'CashAdvanceId' => $caRef,
                'UserId' => 0,
                'Remarks' => 'Cancelled in K-Flow',
                'Source' => 'KFLOW',
            );
            $cancelResult = $this->sp->createReturnId(
                build_sp('sp_cancel_cash_advance', count($cancelParams)),
                $cancelParams
            );
            $debug['steps']['sp_cancel_cash_advance_result'] = $cancelResult;

            if (!is_array($cancelResult) || !isset($cancelResult['cash_advance_id'])) {
                echo json_encode(array(
                    'status' => 'error',
                    'response' => is_array($cancelResult) && !empty($cancelResult['message']) ? $cancelResult['message'] : 'Failed to cancel the cash advance.',
                    'debug' => $debug,
                ));
                return;
            }

            if ((int) ($cancelResult['is_cancelled'] ?? 0) === 1) {
                $this->logAuditTrail('CASH_ADVANCE', $caRef, 'CANCELLED', 'HEADER', $caRef, 'remarks', null, 'Cancelled in K-Flow');
            }

            echo json_encode(array(
                'status' => 'success',
                'message' => 'K-net updated',
                'internal_status' => 'CA_KFLOW_CANCELLED',
                'data' => array(
                    'cash_advance_id' => $caRef,
                    'status_code' => $cancelResult['status_code'] ?? null,
                    'is_cancelled' => (int) ($cancelResult['is_cancelled'] ?? 0),
                ),
                'debug' => $debug,
            ));
            return;
        }

        $internalStatus = $isApproved ? 'CA_KFLOW_APPROVED' : 'CA_KFLOW_REJECTED';
        $kflowDocStatusCode = $isApproved ? 4 : 3;
        $finalPath = null;
        $finalRelativePath = null;

        if ($isApproved) {
            $finalRelativeDir = 'assets/uploads/cash_advance/approved/';
            $finalDir = FCPATH . $finalRelativeDir;
            if (!is_dir($finalDir)) {
                mkdir($finalDir, 0777, true);
            }

            $finalFileName = 'CA_' . $caRef . '_approved_' . time() . '.pdf';
            $finalPath = $finalDir . $finalFileName;
            $finalRelativePath = $finalRelativeDir . $finalFileName;
            $debug['steps']['final_path_absolute'] = $finalPath;
            $debug['steps']['final_path_relative'] = $finalRelativePath;

            if ($hasUploadedSignedPdf) {
                $uploadedFile = $_FILES['signed_pdf'];
                $uploadOk = move_uploaded_file($uploadedFile['tmp_name'], $finalPath);
                $debug['steps']['save_mode'] = 'direct_upload';
                $debug['steps']['upload_move_ok'] = $uploadOk;

                if (!$uploadOk) {
                    echo json_encode(array(
                        'status' => 'error',
                        'response' => 'Failed to save uploaded signed PDF.',
                        'debug' => $debug,
                    ));
                    return;
                }
            } else if (!empty($signedPdfUrl)) {
                $debug['steps']['save_mode'] = 'url_download_fallback';

                $ch = curl_init($signedPdfUrl);
                $fp = fopen($finalPath, 'wb');
                curl_setopt($ch, CURLOPT_FILE, $fp);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
                curl_exec($ch);
                $curlError = curl_error($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                fclose($fp);

                $debug['steps']['curl_http_code'] = $httpCode;
                $debug['steps']['curl_error'] = $curlError;
            } else {
                echo json_encode(array(
                    'status' => 'error',
                    'response' => 'Missing signed PDF file for approved callback.',
                    'debug' => $debug,
                ));
                return;
            }

            clearstatcache(true, $finalPath);
            $fileSize = file_exists($finalPath) ? filesize($finalPath) : 0;
            $debug['steps']['saved_file_size'] = $fileSize;

            if ($fileSize <= 0) {
                echo json_encode(array(
                    'status' => 'error',
                    'response' => 'Saved signed PDF is empty.',
                    'debug' => $debug,
                ));
                return;
            }
        }

        // The internal approval `status` column belongs exclusively to the
        // approval matrix flow (sp_insert_ca_v2 / sp_approval_decision /
        // sp_release_payment) — self-approval can advance it before this
        // callback ever fires, so this callback must not touch it. It only
        // owns the KFlow e-signature fields.
        try {
            $this->Cash_advance_model->update_kflow_status($caRef, trim((string) $kflowDocId), (string) $kflowDocStatusCode, $finalRelativePath);
            $spResult = true;
        } catch (Exception $e) {
            $spResult = $e->getMessage();
        }

        $debug['steps']['sp_update_ca_kflow_status_result'] = $spResult;

        $verifyCa = $this->Cash_advance_model->find_by_batch_id($batchId);
        $savedFinalPath = '';
        if (is_array($verifyCa) && isset($verifyCa['final_pdf_path'])) {
            $savedFinalPath = (string) $verifyCa['final_pdf_path'];
        }
        $debug['steps']['verify_saved_final_pdf_path'] = $savedFinalPath;

        if ($isApproved && trim($savedFinalPath) === '') {
            echo json_encode(array(
                'status' => 'error',
                'response' => 'Signed PDF saved on disk but DB final_pdf_path update failed.',
                'debug' => $debug,
            ));
            return;
        }

        if ($isApproved) {
            $this->notifyFirstKnetApprover($caRef);
        }

        if ($isRejected) {
            $rejectParams = array('CashAdvanceId' => $caRef);
            $rejectResult = $this->sp->createReturnId(
                build_sp('sp_reject_cash_advance_from_kflow', count($rejectParams)),
                $rejectParams
            );
            $debug['steps']['sp_reject_cash_advance_from_kflow_result'] = $rejectResult;

            if (is_array($rejectResult) && (int) ($rejectResult['is_rejected'] ?? 0) === 1) {
                $this->logAuditTrail('CASH_ADVANCE', $caRef, 'REJECTED', 'HEADER', $caRef, 'remarks', null, 'Rejected in K-Flow');
            }
        }

        echo json_encode(array(
            'status' => 'success',
            'message' => 'K-net updated',
            'internal_status' => $internalStatus,
            'data' => array(
                'cash_advance_id' => $caRef,
                'final_pdf_path' => $finalRelativePath,
                'kflow_doc_status' => $kflowDocStatusCode,
            ),
            'debug' => $debug,
        ));
    }

    private function notifyFirstKnetApprover($caRef)
    {
        try {
            if (!$this->sp || !$this->sp->db) {
                return;
            }

            $header = $this->sp->db->get_where('tbl_approval_header', array('reference_id' => $caRef, 'is_active' => 1), 1)->row_array();
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

            $ca = $this->sp->db->get_where('tbl_cash_advance', array('cash_advance_id' => $caRef), 1)->row_array();
            $requesterInfo = (is_array($ca) && !empty($ca['user_id'])) ? get_user_info((int) $ca['user_id']) : null;

            $approverName = trim((string) ($approverInfo['firstname'] ?? '') . ' ' . (string) ($approverInfo['lastname'] ?? ''));
            $requesterName = is_array($requesterInfo)
                ? trim((string) ($requesterInfo['firstname'] ?? '') . ' ' . (string) ($requesterInfo['lastname'] ?? ''))
                : '';

            $mergeData = array(
                'amount' => number_format((float) ($ca['amount'] ?? 0), 2),
                'status' => 'PENDING',
                'remarks' => '',
                'action_date' => date('Y-m-d H:i:s'),
                'requester_name' => $requesterName,
                'requester_department' => is_array($requesterInfo) ? (string) ($requesterInfo['short_name'] ?? '') : '',
                'approver_name' => $approverName,
            );

            notify_event('TXN_SUBMITTED', 'CASH_ADVANCE', $caRef, array(array(
                'email' => $approverInfo['email'],
                'name' => $approverName !== '' ? $approverName : $approverInfo['email'],
                'user_id' => (int) $firstApprover['approver_id'],
            )), $mergeData);
        } catch (Throwable $e) {
            log_message('error', 'notifyFirstKnetApprover failed for ' . $caRef . ': ' . $e->getMessage());
        }
    }

    public function api_get_attachments()
    {
        try {
            $this->output->set_content_type('application/json');
            $data = $this->getRequestPayload();

            $cashAdvanceId = isset($data['CashAdvanceId']) ? trim((string) $data['CashAdvanceId']) : '';
            if ($cashAdvanceId === '') {
                return $this->respondError('Missing required field: CashAdvanceId');
            }

            $attachments = $this->Cash_advance_model->list_attachments($cashAdvanceId);

            $processed = array();
            foreach ($attachments as $att) {
                $caRef = isset($att['cash_advance_id']) ? trim((string) $att['cash_advance_id']) : '';
                $fileName = isset($att['file_name']) ? trim((string) $att['file_name']) : '';
                $filePath = isset($att['file_path']) ? trim((string) $att['file_path']) : '';

                $fileSize = 0;
                if ($filePath !== '' && file_exists($filePath)) {
                    $fileSize = filesize($filePath);
                }

                $processed[] = array(
                    'id' => $att['id'] ?? null,
                    'cash_advance_id' => $caRef,
                    'original_name' => $att['original_name'] ?? '',
                    'file_name' => $fileName,
                    'file_path' => $filePath,
                    'file_size' => $fileSize,
                    'uploaded_date' => $att['uploaded_date'] ?? '',
                    'view_url' => base_url('transactions/cash-advance/attachment/view?ca=' . urlencode($caRef) . '&file=' . urlencode($fileName)),
                    'download_url' => base_url('transactions/cash-advance/attachment/view?ca=' . urlencode($caRef) . '&file=' . urlencode($fileName) . '&download=1'),
                );
            }

            return $this->respondSuccess('Attachments fetched', array(
                'attachments' => $processed,
                'count' => count($processed),
            ));
        } catch (Exception $e) {
            return $this->respondError('An error occurred: ' . $e->getMessage());
        }
    }

    public function view_attachment()
    {
        $caRef = trim((string) $this->input->get('ca'));
        $fileName = trim((string) $this->input->get('file'));

        if ($caRef === '' || $fileName === '') {
            show_404();
            return;
        }

        $fileName = basename($fileName);
        if (strpos($fileName, '..') !== false) {
            show_404();
            return;
        }

        $attachments = $this->Cash_advance_model->list_attachments($caRef);

        $target = null;
        foreach ($attachments as $att) {
            if (isset($att['file_name']) && $att['file_name'] === $fileName) {
                $target = $att;
                break;
            }
        }

        if (!$target) {
            show_404();
            return;
        }

        $filePath = isset($target['file_path']) ? trim((string) $target['file_path']) : '';
        if ($filePath === '' || !file_exists($filePath)) {
            show_404();
            return;
        }

        $originalName = isset($target['original_name']) ? $target['original_name'] : $fileName;
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        $mimeTypes = array(
            'pdf'  => 'application/pdf',
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png'  => 'image/png',
            'gif'  => 'image/gif',
            'bmp'  => 'image/bmp',
            'webp' => 'image/webp',
            'svg'  => 'image/svg+xml',
            'doc'  => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls'  => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'csv'  => 'text/csv',
            'txt'  => 'text/plain',
            'zip'  => 'application/zip',
        );

        $mime = isset($mimeTypes[$ext]) ? $mimeTypes[$ext] : 'application/octet-stream';
        $isDownload = $this->input->get('download') === '1';
        $disposition = $isDownload ? 'attachment' : 'inline';

        while (ob_get_level()) {
            ob_end_clean();
        }

        header('Content-Type: ' . $mime);
        header('Content-Disposition: ' . $disposition . '; filename="' . $originalName . '"');
        header('Content-Length: ' . filesize($filePath));
        header('Cache-Control: private, max-age=86400');
        header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 86400) . ' GMT');

        readfile($filePath);
        exit;
    }
}
?>