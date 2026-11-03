<?php

require 'vendor/autoload.php';
(defined('BASEPATH')) or exit('No direct script access allowed');

class Notification_Templates extends MY_Controller
{
    /**
     * Fixed event list — kept in code (not a DB lookup table) to match how
     * transaction_type constants are already handled throughout this app.
     * "fires_when"/"sent_to" are shown directly in the UI so template
     * creators don't have to guess what an event code means.
     */
    private $eventCodes = array(
        'TXN_SUBMITTED' => array(
            'label' => 'Transaction Submitted',
            'fires_when' => 'A new request is submitted and enters the approval chain for the first time.',
            'sent_to' => 'The first approver in line.',
            'accent' => '#5b2bd6',
        ),
        'TXN_STEP_APPROVED' => array(
            'label' => 'Step Approved (moves to next approver)',
            'fires_when' => 'An approver approves their step, but at least one more approver still needs to act — the request "advances" to that next person instead of finishing.',
            'sent_to' => 'The next approver in line.',
            'accent' => '#5b2bd6',
        ),
        'TXN_REJECTED' => array(
            'label' => 'Transaction Rejected',
            'fires_when' => 'Any approver rejects the request. This ends the approval process immediately — no other approver will be asked to act.',
            'sent_to' => 'The person who originally submitted the request.',
            'accent' => '#e03131',
        ),
        'TXN_FULLY_APPROVED' => array(
            'label' => 'Fully Approved (last approver signs off)',
            'fires_when' => 'The last approver in the chain approves — there is no one left to approve after this.',
            'sent_to' => 'The person who originally submitted the request.',
            'accent' => '#17663a',
        ),
        'PAYMENT_ADVISED' => array(
            'label' => 'Payment Advised',
            'fires_when' => 'A finance staff member marks a fully-approved request as ready for payment (queued, not yet paid out).',
            'sent_to' => 'The requester, plus any approver authorized to release the payment.',
            'accent' => '#0f766e',
        ),
        'PAYMENT_RELEASED' => array(
            'label' => 'Payment Released',
            'fires_when' => 'A finance staff member confirms the payment has actually been paid out. This is the final step.',
            'sent_to' => 'The person who originally submitted the request.',
            'accent' => '#17663a',
        ),
        'LIQUIDATION_OVERDUE' => array(
            'label' => 'Liquidation Overdue Reminder',
            'fires_when' => 'An approver clicks "Notify Selected" on the Dashboard\'s Pending Liquidations panel for a released cash advance that has not been liquidated yet.',
            'sent_to' => 'The employee holding the cash advance.',
            'accent' => '#e8590c',
        ),
    );

    private $mergeFields = array(
        'reference_no' => "The transaction's reference number, e.g. RMB2026070100001",
        'transaction_type' => 'Cash Advance, Liquidation, or Reimbursement',
        'requester_name' => 'Full name of the person who submitted the request',
        'requester_department' => 'Department of the requester',
        'approver_name' => 'Name of the approver who just took action',
        'amount' => 'Transaction amount, formatted with commas (e.g. 12,500.00)',
        'status' => 'Resulting status code (e.g. APPROVED, REJECTED)',
        'remarks' => 'Remarks or reason entered by the approver, if any',
        'action_date' => 'Date and time the action was taken',
        'review_link' => "A clickable link straight to the transaction's review page",
        'due_date' => 'Liquidation due date (Liquidation Overdue Reminder only)',
        'days_overdue' => 'Number of days past the due date (Liquidation Overdue Reminder only)',
    );

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Notification_model');
    }

    public function index()
    {
        $data = array(
            'title' => 'Notification Templates',
            'main_view' => '../modules/notification-templates/views/index',
            'module_group' => $this->module_group,
            'module' => $this->module,
            'event_codes' => $this->eventCodes,
            'scripts' => array('index.js'),
        );
        $this->load->view('main', $data);
    }

    public function add()
    {
        $data = array(
            'title' => 'New Notification Template',
            'main_view' => '../modules/notification-templates/views/add',
            'module_group' => $this->module_group,
            'module' => $this->module,
            'event_codes' => $this->eventCodes,
            'merge_fields' => $this->mergeFields,
            'scripts' => array('add.js'),
        );
        $this->load->view('main', $data);
    }

    public function edit($id = 0)
    {
        $id = (int) $id;
        if ($id <= 0) {
            redirect('maintenance/notification-templates');
            return;
        }

        $data = array(
            'title' => 'Edit Notification Template',
            'main_view' => '../modules/notification-templates/views/details',
            'module_group' => $this->module_group,
            'module' => $this->module,
            'template_id' => $id,
            'event_codes' => $this->eventCodes,
            'merge_fields' => $this->mergeFields,
            'scripts' => array('details.js'),
        );
        $this->load->view('main', $data);
    }

    public function api_get_header()
    {
        try {
            $this->output->set_content_type('application/json');
            $take = $this->resolvePaginationTake($this->input->post('Take'));
            $cursorIdRaw = $this->input->post('CursorId');
            $cursorId = ($cursorIdRaw !== null && $cursorIdRaw !== '') ? (int) $cursorIdRaw : null;

            $result = $this->Notification_model->list_templates($cursorId, $take);

            $payload = $this->buildPaginationResult($result, $take);
            echo json_encode(array('status' => 'success') + $payload);
        } catch (Exception $e) {
            echo json_encode(array('status' => 'error', 'response' => "An error occurred: " . $e->getMessage()));
        }
    }

    public function api_get_by_id()
    {
        try {
            $this->output->set_content_type('application/json');
            $id = (int) $this->input->post('Id');
            if ($id <= 0) {
                return $this->respondError('Invalid template id.');
            }

            $result = $this->Notification_model->find_template($id);

            if (empty($result)) {
                return $this->respondError('Template not found.');
            }

            return $this->respondSuccess('success', $result);
        } catch (Exception $e) {
            return $this->respondError("An error occurred: " . $e->getMessage());
        }
    }

    public function api_save()
    {
        try {
            $this->output->set_content_type('application/json');
            $data = $this->getRequestPayload();

            $error = $this->validateTemplatePayload($data);
            if ($error !== '') {
                return $this->respondError($error);
            }

            try {
                $this->Notification_model->create_template(
                    $data['event_code'],
                    !empty($data['transaction_type']) ? $data['transaction_type'] : null,
                    $data['subject'],
                    $data['body_html'],
                    (int) $this->session->userdata('user_id')
                );
            } catch (Exception $e) {
                return $this->respondError($e->getMessage() !== '' ? $e->getMessage() : 'Failed to save the notification template.');
            }

            return $this->respondSuccess('Notification template created successfully.');
        } catch (Throwable $e) {
            return $this->respondError($e->getMessage());
        }
    }

    public function api_update()
    {
        try {
            $this->output->set_content_type('application/json');
            $data = $this->getRequestPayload();

            $id = isset($data['id']) ? (int) $data['id'] : 0;
            if ($id <= 0) {
                return $this->respondError('Invalid template id.');
            }

            $error = $this->validateTemplatePayload($data);
            if ($error !== '') {
                return $this->respondError($error);
            }

            try {
                $this->Notification_model->update_template(
                    $id,
                    $data['event_code'],
                    !empty($data['transaction_type']) ? $data['transaction_type'] : null,
                    $data['subject'],
                    $data['body_html'],
                    isset($data['is_active']) ? (int) $data['is_active'] : 1,
                    (int) $this->session->userdata('user_id')
                );
            } catch (Exception $e) {
                return $this->respondError($e->getMessage() !== '' ? $e->getMessage() : 'Failed to update the notification template.');
            }

            return $this->respondSuccess('Notification template updated successfully.');
        } catch (Throwable $e) {
            return $this->respondError($e->getMessage());
        }
    }

    public function api_preview()
    {
        try {
            $this->output->set_content_type('application/json');
            $data = $this->getRequestPayload();

            $subject = isset($data['subject']) ? (string) $data['subject'] : '';
            $body = isset($data['body_html']) ? (string) $data['body_html'] : '';

            $sample = array(
                'reference_no' => 'RMB2026070100001',
                'transaction_type' => 'REIMBURSEMENT',
                'requester_name' => 'Juan Dela Cruz',
                'requester_department' => 'Sales & Distribution',
                'approver_name' => 'Maria Santos',
                'amount' => '12,500.00',
                'status' => 'APPROVED',
                'remarks' => 'Sample remarks for preview.',
                'action_date' => date('Y-m-d H:i:s'),
                'review_link' => base_url('transactions/approvals/review/RMB2026070100001'),
                'view_link' => base_url('transactions/reimbursement/view/RMB2026070100001'),
                'liquidation_link' => base_url('transactions/liquidation/add'),
                'due_date' => date('Y-m-d', strtotime('-3 days')),
                'days_overdue' => '3',
                'app_name' => config_item('app_name'),
                'app_long_name' => html_escape(config_item('app_long_name')),
            );

            $replace = array('cid:knetlogo' => base_url(config_item('app_logo_email')));
            foreach ($sample as $key => $value) {
                $replace['{{' . $key . '}}'] = $value;
            }

            return $this->respondSuccess('success', array(
                'subject' => strtr($subject, $replace),
                'body_html' => strtr($body, $replace),
            ));
        } catch (Throwable $e) {
            return $this->respondError($e->getMessage());
        }
    }

    public function api_get_default_template()
    {
        try {
            $this->output->set_content_type('application/json');
            $data = $this->getRequestPayload();
            $eventCode = isset($data['event_code']) ? trim((string) $data['event_code']) : '';
            $transactionType = isset($data['transaction_type']) ? trim((string) $data['transaction_type']) : '';

            if (!array_key_exists($eventCode, $this->eventCodes)) {
                return $this->respondError('Invalid event type.');
            }

            return $this->respondSuccess('success', $this->buildDefaultTemplate($eventCode, $transactionType));
        } catch (Throwable $e) {
            return $this->respondError($e->getMessage());
        }
    }

    /**
     * Ready-to-use starter Subject + HTML Body per event (and, when a
     * specific transaction type is chosen, worded for that type instead of
     * generic "request" language), so template creators have a
     * professional email to start editing instead of a blank page.
     */
    private function buildDefaultTemplate($eventCode, $transactionType = '')
    {
        $typeLabels = array(
            'CASH_ADVANCE' => 'Cash Advance',
            'LIQUIDATION' => 'Liquidation',
            'REIMBURSEMENT' => 'Reimbursement',
        );
        // %TYPE% = "Cash Advance request" / "request" when applied to all types.
        // %TYPE_PREFIX% = "Cash Advance " / "" — for subject lines.
        $typeNoun = isset($typeLabels[$transactionType]) ? ($typeLabels[$transactionType] . ' request') : 'request';
        $typePrefix = isset($typeLabels[$transactionType]) ? ($typeLabels[$transactionType] . ' ') : '';

        $copy = array(
            'TXN_SUBMITTED' => array(
                'subject' => '%TYPE_PREFIX%Approval Required: {{reference_no}}',
                'accent' => '#5b2bd6',
                'greeting' => 'Dear Approver,',
                'intro' => 'A new %TYPE% has been submitted and requires your approval.',
                'extra_rows' => array(),
                'cta_label' => 'Review and Approve',
                'closing' => 'Please review and take action at your earliest convenience.',
            ),
            'TXN_STEP_APPROVED' => array(
                'subject' => '%TYPE_PREFIX%Approval Required: {{reference_no}}',
                'accent' => '#5b2bd6',
                'greeting' => 'Dear Approver,',
                'intro' => 'The %TYPE% below has cleared a previous approval step and now requires your action.',
                'extra_rows' => array(array('Previous Approver', '{{approver_name}}')),
                'cta_label' => 'Review and Approve',
                'closing' => 'Please review and take action at your earliest convenience.',
            ),
            'TXN_REJECTED' => array(
                'subject' => 'Your %TYPE_PREFIX%Request Was Rejected: {{reference_no}}',
                'accent' => '#e03131',
                'greeting' => 'Dear {{requester_name}},',
                'intro' => 'Unfortunately, your %TYPE% below was rejected.',
                'extra_rows' => array(array('Rejected By', '{{approver_name}}'), array('Reason', '{{remarks}}')),
                'cta_label' => 'View Details',
                'closing' => 'If you have questions about this decision, please reach out to the approver above.',
            ),
            'TXN_FULLY_APPROVED' => array(
                'subject' => 'Your %TYPE_PREFIX%Request Has Been Fully Approved: {{reference_no}}',
                'accent' => '#17663a',
                'greeting' => 'Dear {{requester_name}},',
                'intro' => 'Good news — your %TYPE% below has been fully approved.',
                'extra_rows' => array(),
                'cta_label' => 'View Details',
                'closing' => 'No further action is needed from you at this time.',
            ),
            'PAYMENT_ADVISED' => array(
                'subject' => '%TYPE_PREFIX%Payment Advised: {{reference_no}}',
                'accent' => '#0f766e',
                'greeting' => 'Dear {{requester_name}},',
                'intro' => 'Your approved %TYPE% has been advised for payment and is now queued for release.',
                'extra_rows' => array(array('Advised By', '{{approver_name}}')),
                'cta_label' => 'View Details',
                'closing' => 'You will receive another notification once payment is released.',
            ),
            'PAYMENT_RELEASED' => array(
                'subject' => '%TYPE_PREFIX%Payment Released: {{reference_no}}',
                'accent' => '#17663a',
                'greeting' => 'Dear {{requester_name}},',
                'intro' => 'Your %TYPE% payment has been released.',
                'extra_rows' => array(array('Released By', '{{approver_name}}')),
                'cta_label' => 'View Details',
                'closing' => 'This transaction is now complete.',
            ),
            'LIQUIDATION_OVERDUE' => array(
                'subject' => 'Reminder: Liquidation overdue for {{reference_no}}',
                'accent' => '#e8590c',
                'greeting' => 'Dear {{requester_name}},',
                'intro' => 'Your cash advance below was released but has not been liquidated yet. Please file your liquidation as soon as possible.',
                'extra_rows' => array(array('Due Date', '{{due_date}}'), array('Days Overdue', '{{days_overdue}}')),
                'cta_label' => 'File Liquidation',
                'closing' => 'If you have already filed your liquidation, please disregard this reminder.',
            ),
        );

        $c = $copy[$eventCode];
        $typeTokens = array('%TYPE_PREFIX%' => $typePrefix, '%TYPE%' => $typeNoun);
        $subject = strtr($c['subject'], $typeTokens);
        $intro = strtr($c['intro'], $typeTokens);

        $rows = array(
            array('Reference No.', '{{reference_no}}'),
            array('Transaction Type', '{{transaction_type}}'),
            array('Requester', '{{requester_name}} ({{requester_department}})'),
            array('Amount', '{{amount}}'),
            array('Status', '{{status}}'),
        );
        $rows = array_merge($rows, $c['extra_rows']);

        $body = notification_email_layout($c['greeting'], $intro, $rows, $c['accent'], $c['cta_label'], $c['closing']);

        return array('subject' => $subject, 'body_html' => $body);
    }

    public function api_get_log()
    {
        try {
            $this->output->set_content_type('application/json');
            $take = $this->resolvePaginationTake($this->input->post('Take'));
            $cursorIdRaw = $this->input->post('CursorId');
            $cursorId = ($cursorIdRaw !== null && $cursorIdRaw !== '') ? (int) $cursorIdRaw : null;

            $result = $this->Notification_model->list_log($cursorId, $take);

            $payload = $this->buildPaginationResult($result, $take);
            echo json_encode(array('status' => 'success') + $payload);
        } catch (Exception $e) {
            echo json_encode(array('status' => 'error', 'response' => "An error occurred: " . $e->getMessage()));
        }
    }

    private function validateTemplatePayload($data)
    {
        $eventCode = isset($data['event_code']) ? trim((string) $data['event_code']) : '';
        $subject = isset($data['subject']) ? trim((string) $data['subject']) : '';
        $body = isset($data['body_html']) ? trim((string) $data['body_html']) : '';

        if (!array_key_exists($eventCode, $this->eventCodes)) {
            return 'Invalid event type.';
        }
        if ($subject === '') {
            return 'Subject is required.';
        }
        if ($body === '') {
            return 'Body is required.';
        }
        return '';
    }

}
