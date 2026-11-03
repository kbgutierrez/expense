<?php

require 'vendor/autoload.php';
(defined('BASEPATH')) or exit('No direct script access allowed');
class Liquidation extends MY_Controller
{
    private $draftEditWindowDays = 7;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('SPModel', 'sp');
        $this->sp->setDatabase('dbknet');

    }
    public function index()
    {
        $data = array(
            'title' => 'Liquidation',
            'main_view' => '../modules/liquidation/views/index',
            'module_group' => $this->module_group,
            'module' => $this->module,
            'scripts' => array(
                '../liquidation/index.js',
            ),
        );

        $this->load->view('main', $data);
    }

    public function add($liquidation_no = '')
    {
        $ref = trim((string) $liquidation_no);
        $isEditMode = $ref !== '';
        $data = array(
            'title' => $isEditMode ? 'Edit Draft Liquidation' : 'New Liquidation',
            'main_view' => '../modules/liquidation/views/add',
            'module_group' => $this->module_group,
            'module' => $this->module,
            'liquidation_no' => $ref,
            'is_edit_mode' => $isEditMode,
            'draft_edit_window_days' => $this->draftEditWindowDays,
            'scripts' => array(
                '../shared/pdfjs/pdf.min.js',
                '../shared/receipt-ocr.js',
                '../liquidation/index.js',
                '../liquidation/add.js',
            ),
        );

        $this->load->view('main', $data);
    }

    public function view($liquidation_no = '')
    {
        $data = array(
            'title' => 'Liquidation Details',
            'main_view' => '../modules/liquidation/views/detail',
            'module_group' => $this->module_group,
            'module' => $this->module,
            'liquidation_no' => $liquidation_no,
            'scripts' => array(
                '../liquidation/index.js',
                '../liquidation/detail.js',
            ),
        );

        $this->load->view('main', $data);
    }

    public function api_get_pending_ca_nums_by_userid()
    {
        try {
            $this->output->set_content_type('application/json');
            $userId = $this->session->userdata('user_id');

            $this->load->model('Cash_advance_model');
            $result = $this->Cash_advance_model->list_unliquidated_by_user($userId);

            return $this->respondSuccess("success", $result);


        } catch (Exception $e) {
            return $this->respondError("An error occurred: " . $e->getMessage());
        }
    }

    private function ensureAttachmentDir()
    {
        $targetDir = 'assets/uploads/attachments/';
        $absoluteDir = rtrim(FCPATH, '/\\') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $targetDir);

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

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw)) {
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

        if (preg_match('/\{[\s\S]*\}/', $trimmed, $matches)) {
            $decoded = json_decode($matches[0], true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return null;
    }

    private function getDraftHeaderByLiquidationId($liquidationId)
    {
        $this->load->model('Liquidation_model');
        $header = $this->Liquidation_model->find_header($liquidationId);

        if (!is_array($header) || count($header) === 0) {
            return null;
        }

        return $header;
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

    public function api_get_draft()
    {
        try {
            $this->output->set_content_type('application/json');
            $data = $this->getRequestPayload();

            $liquidationId = isset($data['LiquidationId']) ? trim((string) $data['LiquidationId']) : '';
            if ($liquidationId === '') {
                return $this->respondError('Missing required field: LiquidationId');
            }

            $header = $this->getDraftHeaderByLiquidationId($liquidationId);
            if (!$header) {
                return $this->respondError('Draft liquidation not found.');
            }

            $userId = (int) $this->session->userdata('user_id');
            $createdById = isset($header['created_by_id']) ? (int) $header['created_by_id'] : 0;
            if ($createdById !== $userId) {
                return $this->respondError('You are not allowed to edit this draft.');
            }

            $statusCode = isset($header['status_code']) ? trim((string) $header['status_code']) : '';
            if ($statusCode !== 'LQ_DRAFT') {
                return $this->respondError('Only draft liquidation can be edited.');
            }

            $ageDays = $this->getDraftAgeDays(isset($header['created_date']) ? $header['created_date'] : '');
            $canEdit = $ageDays <= $this->draftEditWindowDays;

            $this->load->model('Liquidation_model');
            $details = $this->Liquidation_model->list_details($liquidationId);

            return $this->respondSuccess('success', array(
                'header' => $header,
                'details' => is_array($details) ? $details : array(),
                'canEdit' => $canEdit,
                'draftAgeDays' => $ageDays,
                'draftEditWindowDays' => $this->draftEditWindowDays,
            ));
        } catch (Exception $e) {
            return $this->respondError('An error occurred: ' . $e->getMessage());
        }
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

          $prompt = "Extract receipt fields and respond with STRICT JSON only. Use keys:
                    document_date, invoice_receipt_no, actual_amount, description, expense_category_name,
                    is_vatable, vendor_name, vendor_address, vendor_tin.
                    document_date must be YYYY-MM-DD if possible.
                    actual_amount must be number only.
                    is_vatable must be true or false.
                    vendor_tin should be numeric TIN with dashes if present (e.g., 123-456-789-000).
                    If unknown, return empty string for text fields and 0 for amount.";

            $ocrResult = groq_ocr_extract($imageDataUrl, $prompt, $apiKey, array(
                'endpoint' => 'transactions/liquidation/api/ocr',
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

    public function api_get_ca_details_by_ca_no()
    {
        try {
            $this->output->set_content_type('application/json');
            $data = $this->getRequestPayload();
            $requiredFields = array('CashAdvanceId');
            foreach ($requiredFields as $field) {
                if (!isset($data[$field]) || $data[$field] === '') {
                    return $this->respondError("Missing required field: {$field}");
                }
            }

            $this->load->model('Cash_advance_model');
            $result = $this->Cash_advance_model->get_details($data['CashAdvanceId']);

            return $this->respondSuccess("success", $result);
        } catch (Exception $e) {
            return $this->respondError("An error occurred: " . $e->getMessage());
        }
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
            $this->load->model('Lookup_model');
            $result = $this->Lookup_model->get_expense_types_by_department($departmentId);

            return $this->respondSuccess("success", $result);
        } catch (Exception $e) {
            return $this->respondError("error: " . $e->getMessage());
        }
    }

    public function api_save_liquidation()
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
            $requestedLiquidationId = isset($data['LiquidationId']) ? trim((string) $data['LiquidationId']) : '';
            if (!isset($data['CashAdvanceId']) || $data['CashAdvanceId'] === '') {
                return $this->respondError("Missing required field: CashAdvanceId");
            }
            if (!isset($data['Expenses']) || !is_array($data['Expenses']) || count($data['Expenses']) === 0) {
                return $this->respondError("Missing required field: Expenses");
            }

            $totalAmountSpent = isset($data['TotalAmountSpent']) ? (float) $data['TotalAmountSpent'] : (isset($data['TotalAmount']) ? (float) $data['TotalAmount'] : 0);
            if ($totalAmountSpent <= 0) {
                return $this->respondError("Missing required field: TotalAmountSpent");
            }

            $cashAdvanceAmount = isset($data['CashAdvanceAmount']) ? (float) $data['CashAdvanceAmount'] : 0;
            $refundAmount = isset($data['RefundAmount']) ? (float) $data['RefundAmount'] : 0;
            $reimburseAmount = isset($data['ReimburseAmount']) ? (float) $data['ReimburseAmount'] : 0;
            $expenseRangeFrom = isset($data['ExpenseRangeFrom']) ? trim((string) $data['ExpenseRangeFrom']) : '';
            $expenseRangeTo = isset($data['ExpenseRangeTo']) ? trim((string) $data['ExpenseRangeTo']) : '';

            if ($expenseRangeFrom === '' || $expenseRangeTo === '') {
                $validExpenseDates = array();
                foreach ($data['Expenses'] as $expenseItem) {
                    $docDate = isset($expenseItem['DocumentDate']) ? trim((string) $expenseItem['DocumentDate']) : '';
                    if ($docDate !== '' && strtotime($docDate) !== false) {
                        $validExpenseDates[] = date('Y-m-d', strtotime($docDate));
                    }
                }

                if (count($validExpenseDates) > 0) {
                    sort($validExpenseDates);
                    if ($expenseRangeFrom === '') {
                        $expenseRangeFrom = $validExpenseDates[0];
                    }
                    if ($expenseRangeTo === '') {
                        $expenseRangeTo = $validExpenseDates[count($validExpenseDates) - 1];
                    }
                }
            }

            if ($cashAdvanceAmount > 0 && !isset($data['RefundAmount']) && !isset($data['ReimburseAmount'])) {
                $variance = $cashAdvanceAmount - $totalAmountSpent;
                $refundAmount = $variance > 0 ? $variance : 0;
                $reimburseAmount = $variance < 0 ? abs($variance) : 0;
            }

            $statusCode = isset($data['StatusCode']) && $data['StatusCode'] !== '' ? trim((string) $data['StatusCode']) : 'LQ_DRAFT';
            if ($statusCode !== 'LQ_DRAFT' && $statusCode !== 'LQ_SUBMITTED') {
                $statusCode = 'LQ_DRAFT';
            }

            $liquidationId = '';
            if ($requestedLiquidationId !== '') {
                $existingHeader = $this->getDraftHeaderByLiquidationId($requestedLiquidationId);
                if (!$existingHeader) {
                    return $this->respondError('Draft liquidation not found.');
                }

                $currentUserId = (int) $this->session->userdata('user_id');
                $createdById = isset($existingHeader['created_by_id']) ? (int) $existingHeader['created_by_id'] : 0;
                if ($createdById !== $currentUserId) {
                    return $this->respondError('You are not allowed to update this draft.');
                }

                $existingStatus = isset($existingHeader['status_code']) ? trim((string) $existingHeader['status_code']) : '';
                if ($existingStatus !== 'LQ_DRAFT') {
                    return $this->respondError('Only draft liquidation can be updated.');
                }

                $ageDays = $this->getDraftAgeDays(isset($existingHeader['created_date']) ? $existingHeader['created_date'] : '');
                if ($ageDays > $this->draftEditWindowDays) {
                    return $this->respondError('Draft edit window has expired.');
                }

                $this->load->model('Liquidation_model');
                try {
                    $this->Liquidation_model->update_draft_header($requestedLiquidationId, $currentUserId, $totalAmountSpent, $refundAmount, $reimburseAmount, $statusCode);
                    $updateHeaderResult = TRUE;
                } catch (Exception $e) {
                    $updateHeaderResult = $e->getMessage();
                }
                $this->logAuditTrail(
                    'LIQUIDATION',
                    $liquidationId,
                    $statusCode === 'LQ_SUBMITTED' ? 'SUBMITTED' : 'SAVED_DRAFT',
                    'HEADER',
                    $liquidationId
                );

                if ($updateHeaderResult !== TRUE) {
                    return $this->respondError('Failed to update draft liquidation header.');
                }

                try {
                    $this->Liquidation_model->delete_details($requestedLiquidationId);
                    $deleteDetailResult = TRUE;
                } catch (Exception $e) {
                    $deleteDetailResult = $e->getMessage();
                }

                if ($deleteDetailResult !== TRUE) {
                    return $this->respondError('Failed to refresh draft expense details.');
                }

                $liquidationId = $requestedLiquidationId;
            }

            if ($liquidationId === '') {
                $headerParams = array(
                    "UserId" => $this->session->userdata('user_id'),
                    "CashAdvanceId" => $data['CashAdvanceId'],
                    "TotalAmountSpent" => $totalAmountSpent,
                    "RefundAmount" => $refundAmount,
                    "ReimburseAmount" => $reimburseAmount,
                    "StatusCode" => $statusCode,
                );

                $headerResult = $this->sp->createReturnId(
                    build_sp('sp_insert_liquidation_header', count($headerParams)),
                    $headerParams
                );

                if (!is_array($headerResult) || !isset($headerResult['GeneratedLiquidationID']) || $headerResult['GeneratedLiquidationID'] === '') {
                    $dbMessage = is_array($headerResult) && isset($headerResult['message']) ? (string) $headerResult['message'] : '';
                    log_message('error', 'sp_insert_liquidation_header failed: ' . json_encode($headerResult));
                    if (stripos($dbMessage, 'No approval matrix found') !== false) {
                        return $this->respondError('No approval workflow is set up yet for your department/amount range. Please contact your administrator.');
                    }
                    return $this->respondError($dbMessage !== '' ? $dbMessage : 'Failed to create liquidation');
                }

                $liquidationId = $headerResult['GeneratedLiquidationID'];

                $this->logAuditTrail(
                    'LIQUIDATION',
                    $liquidationId,
                    $statusCode === 'LQ_SUBMITTED' ? 'SUBMITTED' : 'SAVED_DRAFT',
                    'HEADER',
                    $liquidationId,
                    null,
                    null,
                    null
                );
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
                    "LiquidationId" => $liquidationId,
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
                    build_sp('sp_insert_liquidation_details', count($detailParams)),
                    $detailParams
                );

                if ($detailResult !== TRUE) {
                    return $this->respondError("Failed to save expense detail at index {$index}");
                }
            }

            if ($statusCode === 'LQ_SUBMITTED') {
                try {
                    $this->sp->readData(build_sp('sp_auto_approve_own_turn', 1), array('ReferenceId' => $liquidationId), 'row');
                } catch (\Throwable $e) {
                    log_message('error', 'sp_auto_approve_own_turn failed for ' . $liquidationId . ': ' . $e->getMessage());
                }
                $this->notifyFirstApprover($liquidationId);
            }

            $message = $statusCode === 'LQ_DRAFT'
                ? 'Liquidation draft saved successfully'
                : 'Liquidation submitted successfully';

            return $this->respondSuccess($message, array('id' => $liquidationId));

        } catch (Exception $e) {
            return $this->respondError("An error occurred: " . $e->getMessage());
        }
    }

    private function notifyFirstApprover($liquidationId)
    {
        try {
            if (!$this->sp || !$this->sp->db) {
                return;
            }

            $header = $this->sp->db->get_where('tbl_approval_header', array('reference_id' => $liquidationId, 'is_active' => 1), 1)->row_array();
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

            $lq = $this->sp->db->get_where('tbl_liquidation_header', array('liquidation_id' => $liquidationId), 1)->row_array();
            $requesterInfo = (is_array($lq) && !empty($lq['created_by'])) ? get_user_info((int) $lq['created_by']) : null;

            $approverName = trim((string) ($approverInfo['firstname'] ?? '') . ' ' . (string) ($approverInfo['lastname'] ?? ''));
            $requesterName = is_array($requesterInfo)
                ? trim((string) ($requesterInfo['firstname'] ?? '') . ' ' . (string) ($requesterInfo['lastname'] ?? ''))
                : '';

            $mergeData = array(
                'amount' => number_format((float) ($lq['total_amount_spent'] ?? 0), 2),
                'status' => 'PENDING',
                'remarks' => '',
                'action_date' => date('Y-m-d H:i:s'),
                'requester_name' => $requesterName,
                'requester_department' => is_array($requesterInfo) ? (string) ($requesterInfo['short_name'] ?? '') : '',
                'approver_name' => $approverName,
            );

            notify_event('TXN_SUBMITTED', 'LIQUIDATION', $liquidationId, array(array(
                'email' => $approverInfo['email'],
                'name' => $approverName !== '' ? $approverName : $approverInfo['email'],
                'user_id' => (int) $firstApprover['approver_id'],
            )), $mergeData);
        } catch (Throwable $e) {
            log_message('error', 'notifyFirstApprover failed for ' . $liquidationId . ': ' . $e->getMessage());
        }
    }

        public function api_get_header_by_id()
    {
        try {
            $this->output->set_content_type('application/json');
            $data = $this->getRequestPayload();

            $liquidationId = isset($data['LiquidationId']) ? trim((string) $data['LiquidationId']) : '';
            if ($liquidationId === '') {
                return $this->respondError('Missing required field: LiquidationId');
            }

            $header = $this->getDraftHeaderByLiquidationId($liquidationId);
            if (!$header) {
                return $this->respondError('Liquidation not found.');
            }

            return $this->respondSuccess('success', $header);

        } catch (Exception $e) {
            return $this->respondError('An error occurred: ' . $e->getMessage());
        }
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

            $this->load->model('Liquidation_model');
            $result = $this->Liquidation_model->list_by_user($userId, $cursorId, $take);

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

    public function api_get_details()
    {
        try {
            $this->output->set_content_type('application/json');
            $data = $this->getRequestPayload();
            $requiredFields = array('LiquidationId');
            foreach ($requiredFields as $field) {
                if (!isset($data[$field]) || $data[$field] === '') {
                    return $this->respondError("Missing required field: {$field}");
                }
            }

            $this->load->model('Liquidation_model');
            $result = $this->Liquidation_model->list_details($data['LiquidationId']);

            return $this->respondSuccess("success", $result);
        } catch (Exception $e) {
            return $this->respondError("An error occurred: " . $e->getMessage());
        }
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

    public function edit($liquidation_no = '')
    {
        $ref = trim((string) $liquidation_no);
        if ($ref === '') {
            redirect('transactions/liquidation');
            return;
        }

        $editHeader = $this->getDraftHeaderByLiquidationId($ref);
        $editStatus = is_array($editHeader) && isset($editHeader['status_code']) ? trim((string) $editHeader['status_code']) : '';
        if ($editStatus !== 'LQ_REJECTED' && $this->hasApproverApproved($ref)) {
            redirect('transactions/liquidation/view/' . rawurlencode($ref));
            return;
        }

        $data = array(
            'title' => 'Edit Liquidation',
            'main_view' => '../modules/liquidation/views/edit',
            'module_group' => $this->module_group,
            'module' => $this->module,
            'liquidation_no' => $ref,
            'scripts' => array(
                '../shared/pdfjs/pdf.min.js',
                '../shared/receipt-ocr.js',
                '../liquidation/edit.js',

            ),
        );

        $this->load->view('main', $data);
    }

    public function api_get_for_edit()
    {
        try {
            $this->output->set_content_type('application/json');
            $data = $this->getRequestPayload();

            $liquidationId = isset($data['LiquidationId']) ? trim((string) $data['LiquidationId']) : '';
            if ($liquidationId === '') {
                return $this->respondError('Missing required field: LiquidationId');
            }

            $header = $this->getDraftHeaderByLiquidationId($liquidationId);
            if (!$header) {
                return $this->respondError('Liquidation not found.');
            }

            $userId = (int) $this->session->userdata('user_id');
            $createdById = isset($header['created_by_id']) ? (int) $header['created_by_id'] : 0;
            if ($createdById !== $userId) {
                return $this->respondError('You are not allowed to edit this liquidation.');
            }

            $statusCode = isset($header['status_code']) ? trim((string) $header['status_code']) : '';
            if ($statusCode !== 'LQ_SUBMITTED' && $statusCode !== 'LQ_REJECTED') {
                return $this->respondError('Only submitted or rejected liquidations can be edited.');
            }

            if ($statusCode === 'LQ_SUBMITTED' && $this->hasApproverApproved($liquidationId)) {
                return $this->respondError('This liquidation can no longer be edited because an approver has already approved it.');
            }

            $this->load->model('Liquidation_model');
            $details = $this->Liquidation_model->list_details($liquidationId);
            $approvals = $this->Liquidation_model->list_approvals($liquidationId);

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
            ));
        } catch (Exception $e) {
            return $this->respondError('An error occurred: ' . $e->getMessage());
        }
    }

    public function api_update_liquidation()
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

            $liquidationId = isset($data['LiquidationId']) ? trim((string) $data['LiquidationId']) : '';
            if ($liquidationId === '') {
                return $this->respondError('Missing required field: LiquidationId');
            }

            $existingHeader = $this->getDraftHeaderByLiquidationId($liquidationId);
            if (!$existingHeader) {
                return $this->respondError('Liquidation not found.');
            }

            $currentUserId = (int) $this->session->userdata('user_id');
            $createdById = isset($existingHeader['created_by_id']) ? (int) $existingHeader['created_by_id'] : 0;
            if ($createdById !== $currentUserId) {
                return $this->respondError('You are not allowed to update this liquidation.');
            }

            $existingStatus = isset($existingHeader['status_code']) ? trim((string) $existingHeader['status_code']) : '';
            if ($existingStatus !== 'LQ_SUBMITTED' && $existingStatus !== 'LQ_REJECTED') {
                return $this->respondError('Only submitted or rejected liquidations can be updated.');
            }

            if ($existingStatus === 'LQ_SUBMITTED' && $this->hasApproverApproved($liquidationId)) {
                return $this->respondError('This liquidation can no longer be edited because an approver has already approved it.');
            }

            if (!isset($data['CashAdvanceId']) || $data['CashAdvanceId'] === '') {
                return $this->respondError('Missing required field: CashAdvanceId');
            }
            if (!isset($data['Expenses']) || !is_array($data['Expenses']) || count($data['Expenses']) === 0) {
                return $this->respondError('Missing required field: Expenses');
            }

            $totalAmountSpent = isset($data['TotalAmountSpent']) ? (float) $data['TotalAmountSpent'] : 0;
            if ($totalAmountSpent <= 0) {
                return $this->respondError('Missing required field: TotalAmountSpent');
            }

            $cashAdvanceAmount = isset($data['CashAdvanceAmount']) ? (float) $data['CashAdvanceAmount'] : 0;
            $refundAmount = isset($data['RefundAmount']) ? (float) $data['RefundAmount'] : 0;
            $reimburseAmount = isset($data['ReimburseAmount']) ? (float) $data['ReimburseAmount'] : 0;
            $expenseRangeFrom = isset($data['ExpenseRangeFrom']) ? trim((string) $data['ExpenseRangeFrom']) : '';
            $expenseRangeTo = isset($data['ExpenseRangeTo']) ? trim((string) $data['ExpenseRangeTo']) : '';
            $statusCode = isset($data['StatusCode']) && $data['StatusCode'] !== '' ? trim((string) $data['StatusCode']) : 'LQ_SUBMITTED';

            $this->load->model('Liquidation_model');
            try {
                $this->Liquidation_model->update_submitted_header($liquidationId, $currentUserId, $totalAmountSpent, $refundAmount, $reimburseAmount, $statusCode, $expenseRangeFrom, $expenseRangeTo);
                $updateHeaderResult = TRUE;
            } catch (Exception $e) {
                $updateHeaderResult = $e->getMessage();
            }

            $this->logAuditTrail(
                'LIQUIDATION',
                $liquidationId,
                'RESUBMITTED',
                'HEADER',
                $liquidationId
            );
            if ($updateHeaderResult !== TRUE) {
                return $this->respondError('Failed to update liquidation header.');
            }

            $restartRoute = $existingStatus === 'LQ_REJECTED' && $statusCode === 'LQ_SUBMITTED';
            if ($restartRoute) {
                try {
                    $this->sp->readData(
                        build_sp('sp_restart_approval_route', 2),
                        array('ReferenceId' => $liquidationId, 'UserId' => $currentUserId),
                        'result'
                    );
                } catch (\Throwable $e) {
                    return $this->respondError('Liquidation was saved but could not be routed for approval: ' . $e->getMessage());
                }
            }

            $approvals = $this->Liquidation_model->list_approvals($liquidationId);

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
                $this->Liquidation_model->delete_details_except($liquidationId, implode(',', $approvedItemIds));
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
                    "LiquidationId" => $liquidationId,
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
                    build_sp('sp_insert_liquidation_details', count($detailParams)),
                    $detailParams
                );

                if ($detailResult !== TRUE) {
                    return $this->respondError('Failed to save expense detail at index ' . $index);
                }
            }

            if ($restartRoute) {
                try {
                    $this->sp->readData(build_sp('sp_auto_approve_own_turn', 1), array('ReferenceId' => $liquidationId), 'row');
                } catch (\Throwable $e) {
                    log_message('error', 'sp_auto_approve_own_turn failed for ' . $liquidationId . ': ' . $e->getMessage());
                }

                $this->notifyFirstApprover($liquidationId);
            }

            $message = $statusCode === 'LQ_DRAFT'
                ? 'Liquidation saved as draft successfully'
                : 'Liquidation updated and resubmitted successfully';

            return $this->respondSuccess($message, array('id' => $liquidationId));
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
}