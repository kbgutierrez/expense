<?php

require 'vendor/autoload.php';
(defined('BASEPATH')) or exit('No direct script access allowed');
class CA_Reports extends MY_Controller
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
        $pendingCa = $this->getCashAdvanceData($userId);

        $data = array(
            'title' => 'Cash Advance Reports',
            'main_view' => '../modules/ca_reports/views/index',
            'module_group' => $this->module_group,
            'module' => $this->module,
            'has_pending_ca' => !empty($pendingCa),
            'pending_ca_id' => $pendingCa ? $pendingCa['cash_advance_id'] : '',
            'pending_ca_status' => $pendingCa ? $pendingCa['status'] : '',
            'scripts' => array(
                '../ca_reports/index.js',
            ),
        );

        $this->load->view('main', $data);
    }

    private function getCashAdvanceData($userId)
    {
        $result = $this->Cash_advance_model->find_latest_report($userId);

        if (is_array($result) && isset($result['cash_advance_id']) && !empty($result['cash_advance_id'])) {
            return $result;
        }

        return null;
    }

    public function add()
    {
        $userId = $this->session->userdata('user_id');
        $departmentId = $this->session->userdata('user_info')['department_id'];
        $pendingCa = $this->getCashAdvanceData($userId);

        if (!empty($pendingCa)) {
            redirect('transactions/cash-advance');
            return;
        }

   
        $costCenters = $this->Lookup_model->get_cost_centers_by_department($departmentId);

        $data = array(
            'title' => 'New Cash Advance',
            'main_view' => '../modules/ca_reports/views/add',
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

            if (is_array($row) && isset($row['kflow_doc_status']) && trim((string) $row['kflow_doc_status']) !== '4') {
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
            'scripts' => array(

                '../cash-advance/detail.js',
                '../cash-advance/index.js',
            ),
        );

        $this->load->view('main', $data);
    }



    private function buildFilteredQuery($filters)
    {
        $dateFrom = trim((string) $filters['DateFrom']);
        $dateTo = trim((string) $filters['DateTo']);
        $status = trim((string) $filters['Status']);
        $department = trim((string) $filters['Department']);
        $company = trim((string) $filters['Company']);
        $employee = trim((string) $filters['Employee']);

        $db = $this->sp->db;
        $db->select(
            "A.id,
            A.cash_advance_id,
            A.user_id,
            LTRIM(RTRIM(ISNULL(B.lastname, '') + ', ' + ISNULL(B.firstname, ''))) AS employee_name,
            ISNULL(C.CMPNYNM, '') AS company_name,
            ISNULL(D.short_name, '') AS department_name,
            A.amount,
            A.description,
            A.needed_date,
            A.created_date,
            A.updated_date,
            A.status_code,
            A.status_name",
            false
        );
        $db->from('vw_ca A');
        $db->join('BigEUsers.dbo.users B', 'A.user_id = B.id', 'inner', false);
        $db->join('BigEHRIS.dbo.department D', 'B.department_id = D.department_id', 'left', false);
        $db->join('BIGEDISER.dbo.TBL_CMPNY_CLSS C', 'B.company = C.ID AND C.ISACTV = 1', 'left', false);

        if ($dateFrom !== '') {
            $db->where('CONVERT(date, A.created_date) >=', $dateFrom, false);
        }
        if ($dateTo !== '') {
            $db->where('CONVERT(date, A.created_date) <=', $dateTo, false);
        }
        if ($status !== '') {
            $db->where('A.status_name', $status);
        }
        if ($department !== '') {
            $db->where("ISNULL(D.short_name, '') =", $department, false);
        }
        if ($company !== '') {
            $db->where("ISNULL(C.CMPNYNM, '') =", $company, false);
        }
        if ($employee !== '') {
            $db->where("LTRIM(RTRIM(ISNULL(B.lastname, '') + ', ' + ISNULL(B.firstname, ''))) =", $employee, false);
        }

        $db->order_by('A.id', 'DESC');

        return $db;
    }

    public function api_get()
    {
        try {
            $this->output->set_content_type('application/json');

            $filters = array(
                'DateFrom' => $this->input->post('DateFrom'),
                'DateTo' => $this->input->post('DateTo'),
                'Status' => $this->input->post('Status'),
                'Department' => $this->input->post('Department'),
                'Company' => $this->input->post('Company'),
                'Employee' => $this->input->post('Employee'),
            );

            $result = $this->buildFilteredQuery($filters)->get()->result_array();

            $nextCursorId = null;
            if (!empty($result)) {
                $lastRow = end($result);
                $nextCursorId = isset($lastRow['id']) ? (int) $lastRow['id'] : null;
            }

            echo json_encode(array(
                'status' => 'success',
                'data' => $result,
                'pagination' => array(
                    'take' => count($result),
                    'hasMore' => false,
                    'nextCursorId' => $nextCursorId,
                ),
            ));
        } catch (Throwable $e) {
            echo json_encode(array(
                'status' => 'error',
                'response' => "An error occurred: " . $e->getMessage(),
            ));
        }
    }

    public function download_excel()
    {
        $filters = array(
            'DateFrom' => $this->input->get('DateFrom'),
            'DateTo' => $this->input->get('DateTo'),
            'Status' => $this->input->get('Status'),
            'Department' => $this->input->get('Department'),
            'Company' => $this->input->get('Company'),
            'Employee' => $this->input->get('Employee'),
        );

        $result = $this->buildFilteredQuery($filters)->get()->result_array();

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Cash Advance Report');

        $headers = array('Cash Advance No.', 'Employee', 'Department', 'Company', 'Amount', 'Purpose', 'Needed Date', 'Requested Date', 'Status');
        $sheet->fromArray($headers, null, 'A1');
        $sheet->getStyle('A1:I1')->getFont()->setBold(true);

        $rowIndex = 2;
        foreach ($result as $row) {
            $sheet->fromArray(array(
                isset($row['cash_advance_id']) ? $row['cash_advance_id'] : '',
                isset($row['employee_name']) ? $row['employee_name'] : '',
                isset($row['department_name']) ? $row['department_name'] : '',
                isset($row['company_name']) ? $row['company_name'] : '',
                isset($row['amount']) ? (float) $row['amount'] : 0,
                isset($row['description']) ? $row['description'] : '',
                isset($row['needed_date']) ? $row['needed_date'] : '',
                isset($row['created_date']) ? $row['created_date'] : '',
                isset($row['status_name']) ? $row['status_name'] : '',
            ), null, 'A' . $rowIndex);
            $rowIndex++;
        }

        foreach (range('A', 'I') as $col) {
            $sheet->getColumnDimension($col)->setWidth(22);
        }

        $filename = 'cash-advance-report-' . date('Ymd-His') . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save('php://output');
        exit;
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

            // Use input->post() because we are sending multipart/form-data
            $data = $this->input->post();

            $requiredFields = array('Amount', 'Description', 'NeededDate', 'PayableTo', 'Address', 'CostCenterId', 'AmountInWords');
            foreach ($requiredFields as $field) {
                if (!isset($data[$field]) || trim($data[$field]) === '') {
                    return $this->respondError("Missing required field: {$field}");
                }
            }

            $userId = $this->session->userdata('user_id');
            $userFullName = $this->session->userdata('user_full_name') ?? 'Employee';

            // 1. Insert into DB (use your new SP that accepts extra fields)
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

            if (empty($result) || $result <= 0) {
                return $this->respondError("Failed to create cash advance request.");
            }

            $caRef = is_array($result) ? ($result['GeneratedCashAdvanceID'] ?? '') : (string) $result;
            $cashAdvanceId = is_array($result) ? ($result['id'] ?? $result) : $result;

            // 2. Prepare Bridge Folder
            $bridgeBase = $this->config->item('kflow_bridge_path');
            $batchId = 'KNET_' . $caRef . '_' . uniqid();
            $batchDir = $bridgeBase . $batchId . '/';
            $attachDir = $batchDir . 'attachments/';

            if (!is_dir($batchDir)) {
                mkdir($batchDir, 0777, true);
                mkdir($attachDir, 0777, true);
            }

            // 3. Generate PDFs
            $this->load->helper('ca_pdf');

            $templatePath = FCPATH . 'assets/templates/ca_template.pdf';
            $pdfOutputPath = $batchDir . $caRef . '.pdf';

            // Split address into two lines if it has a newline
            $addressLines = explode("\n", $data['Address']);
            $addressLine1 = $addressLines[0] ?? '';
            $addressLine2 = $addressLines[1] ?? '';

            // Get Cost Center Name
            $costCenterRow = $this->Lookup_model->get_cost_center($data['CostCenterId']);
            $costCenterName = $costCenterRow['cost_center_name'] ?? '';

            $pdfData = array(
                'Date' => $data['Date'],
                'CashAdvanceId' => $caRef,
                'PayableTo' => $data['PayableTo'],
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

            // Keep a public unsigned copy for preview while still pending in K-flow.
            $unsignedRelativePath = 'assets/uploads/cash_advance/unsigned/' . $caRef . '_unsigned.pdf';
            $unsignedAbsolutePath = FCPATH . str_replace('/', DIRECTORY_SEPARATOR, $unsignedRelativePath);
            $unsignedDir = dirname($unsignedAbsolutePath);
            if (!is_dir($unsignedDir)) {
                mkdir($unsignedDir, 0777, true);
            }
            if (!@copy($pdfOutputPath, $unsignedAbsolutePath)) {
                return $this->respondError('Unable to prepare unsigned PDF preview.');
            }

            // 4. Save Attachments
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

                        // Also save to K-net DB for record keeping
                        try {
                            $this->Cash_advance_model->create_attachment($caRef, $origName, $safeName, $destPath);
                        } catch (Exception $e) {
                        }
                    }
                }
            }

            // 5. Write Manifest
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

            // 6. Update CA record with batch_id and path
            try {
                $this->Cash_advance_model->update_kflow_batch($caRef, $batchId, $pdfOutputPath, 1);
            } catch (Exception $e) {
            }

            // 7. Build K-flow URL with token
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

        // Ensure temp directory exists
        if (!is_dir(FCPATH . 'assets/temp/')) {
            mkdir(FCPATH . 'assets/temp/', 0777, true);
        }

        // Default coordinates (estimates — adjust these as your starting point)
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

        // If form submitted, use POST values; otherwise use defaults
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

        // GET ORIGINAL SIZE — this is the key fix
        $size = $pdf->getTemplateSize($tplId);

        // Create page that EXACTLY matches the template (no stretching, no cropping)
        $pdf->AddPage($size['orientation'], array($size['width'], $size['height']));
        $pdf->useTemplate($tplId, 0, 0, $size['width'], $size['height']);

        $pdf->SetAutoPageBreak(false);
        $pdf->SetFont('Arial', '', 10);

        foreach ($fields as $key => $f) {
            $pdf->SetXY($f['x'], $f['y']);
            // Red box for visibility


            $pdf->SetXY($f['x'], $f['y']);
            $pdf->SetTextColor(0, 0, 0);
            $pdf->Cell(0, 5, $f['text']);
        }

        // Grid overlay — use the actual page dimensions, not hardcoded 210/297
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

        // Find CA by batch_id
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

        // Normalize K-flow status into K-net internal CA statuses.
        $normalizedStatus = strtoupper(trim((string) $status));
        $isApproved = in_array($normalizedStatus, array('FULLY APPROVED', 'APPROVED', 'CA_KFLOW_APPROVED'), true);
        $isRejected = in_array($normalizedStatus, array('REJECTED', 'DISAPPROVED', 'DECLINED', 'CA_KFLOW_REJECTED'), true);
        $debug['steps']['normalized_status'] = $normalizedStatus;

        if (!$isApproved && !$isRejected) {
            echo json_encode(array(
                'status' => 'error',
                'response' => 'Invalid callback status.',
                'debug' => $debug,
            ));
            return;
        }

        $internalStatus = $isApproved ? 'CA_KFLOW_APPROVED' : 'CA_KFLOW_REJECTED';
        $kflowDocStatusCode = $isApproved ? 4 : 3;
        $finalPath = null;
        $finalRelativePath = null;

        // Signed PDF is required only for approved documents.
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
        // approval matrix flow — this callback only owns the KFlow
        // e-signature fields and must not touch status (self-approval can
        // advance it before this callback ever fires).
        try {
            $this->Cash_advance_model->update_kflow_status($caRef, trim((string) $kflowDocId), (string) $kflowDocStatusCode, $finalRelativePath);
            $spResult = true;
        } catch (Exception $e) {
            $spResult = $e->getMessage();
        }

        $debug['steps']['sp_update_ca_kflow_status_result'] = $spResult;

        // Verify write so callback doesn't falsely report success.
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

                // Get file size if file exists
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

        // Security: prevent path traversal
        $fileName = basename($fileName);
        if (strpos($fileName, '..') !== false) {
            show_404();
            return;
        }

        // Fetch attachments for this CA using the existing SP
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

        // Clean output buffers before sending file headers
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