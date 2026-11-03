<?php
(defined('BASEPATH')) or exit('No direct script access allowed');

if (!function_exists('notification_email_layout')) {
    function notification_email_layout($greeting, $intro, array $rows, $accent, $ctaLabel, $closing)
    {
        $font = "font-family:Arial,Helvetica,sans-serif;";

        $rowsHtml = '';
        foreach ($rows as $row) {
            $rowsHtml .= '<tr>'
                . '<td width="38%" valign="top" style="' . $font . 'font-size:13px;line-height:20px;color:#6b7280;padding:6px 0;">' . $row[0] . '</td>'
                . '<td valign="top" style="' . $font . 'font-size:13px;line-height:20px;color:#111827;font-weight:bold;padding:6px 0;">' . $row[1] . '</td>'
                . '</tr>';
        }

        return '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#f3f4f8" style="background-color:#f3f4f8;">'
            . '<tr><td align="center" style="padding:24px 12px;">'
            . '<!--[if mso]><table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" align="center"><tr><td><![endif]-->'
            . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#ffffff" style="width:100%;max-width:600px;background-color:#ffffff;border:1px solid #e5e7eb;">'
            . '<tr><td align="center" bgcolor="#16153d" style="background-color:#16153d;padding:22px 24px;">'
            . '<img src="cid:knetlogo" width="180" height="58" alt="{{app_name}} - {{app_long_name}}" style="display:block;border:0;outline:none;text-decoration:none;width:180px;height:58px;' . $font . 'font-size:16px;font-weight:bold;color:#ffffff;">'
            . '</td></tr>'
            . '<tr><td style="padding:0;font-size:0;line-height:0;">'
            . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"><tr>'
            . '<td width="50%" height="4" bgcolor="#8b5cf6" style="background-color:#8b5cf6;height:4px;font-size:0;line-height:0;">&nbsp;</td>'
            . '<td width="50%" height="4" bgcolor="#34d399" style="background-color:#34d399;height:4px;font-size:0;line-height:0;">&nbsp;</td>'
            . '</tr></table>'
            . '</td></tr>'
            . '<tr><td style="padding:28px 32px 8px 32px;' . $font . 'font-size:14px;line-height:22px;color:#1f2937;">'
            . '<p style="margin:0 0 14px 0;' . $font . 'font-size:14px;line-height:22px;color:#1f2937;">' . $greeting . '</p>'
            . '<p style="margin:0 0 20px 0;' . $font . 'font-size:14px;line-height:22px;color:#1f2937;">' . $intro . '</p>'
            . '</td></tr>'
            . '<tr><td style="padding:0 32px;">'
            . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border:1px solid #e5e7eb;"><tr>'
            . '<td width="4" bgcolor="' . $accent . '" style="background-color:' . $accent . ';width:4px;font-size:0;line-height:0;">&nbsp;</td>'
            . '<td bgcolor="#f8f7ff" style="background-color:#f8f7ff;padding:12px 18px;">'
            . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">' . $rowsHtml . '</table>'
            . '</td></tr></table>'
            . '</td></tr>'
            . '<tr><td align="left" style="padding:24px 32px 8px 32px;">'
            . '<table role="presentation" cellpadding="0" cellspacing="0" border="0"><tr>'
            . '<td align="center" bgcolor="' . $accent . '" style="background-color:' . $accent . ';border-radius:6px;mso-padding-alt:12px 26px;">'
            . '<a href="{{review_link}}" target="_blank" style="display:inline-block;padding:12px 26px;' . $font . 'font-size:14px;line-height:18px;font-weight:bold;color:#ffffff;text-decoration:none;border-radius:6px;">' . $ctaLabel . '</a>'
            . '</td></tr></table>'
            . '</td></tr>'
            . '<tr><td style="padding:16px 32px 28px 32px;' . $font . 'font-size:14px;line-height:22px;color:#1f2937;">'
            . '<p style="margin:0 0 14px 0;' . $font . 'font-size:14px;line-height:22px;color:#1f2937;">' . $closing . '</p>'
            . '<p style="margin:0;' . $font . 'font-size:14px;line-height:22px;color:#1f2937;">Regards,<br>{{app_name}} - {{app_long_name}}</p>'
            . '</td></tr>'
            . '<tr><td bgcolor="#f8f8fc" style="background-color:#f8f8fc;border-top:1px solid #e5e7eb;padding:14px 32px;' . $font . 'font-size:11px;line-height:16px;color:#6b7280;">'
            . 'This is an automated message from {{app_name}} ({{app_long_name}}). Please do not reply to this email.'
            . '</td></tr>'
            . '</table>'
            . '<!--[if mso]></td></tr></table><![endif]-->'
            . '</td></tr>'
            . '</table>';
    }
}

if (!function_exists('render_notification_template')) {
    function render_notification_template($eventCode, $transactionType, array $mergeData = array())
    {
        $ci = &get_instance();
        $ci->load->model('Notification_model');
        $template = $ci->Notification_model->find_template_for_event($eventCode, $transactionType);

        if (!is_array($template) || empty($template['subject'])) {
            return null;
        }

        $replace = array();
        foreach ($mergeData as $key => $value) {
            $replace['{{' . $key . '}}'] = (string) $value;
        }

        $leftover = '/\{\{\s*[A-Za-z0-9_]+\s*\}\}/';

        return array(
            'subject' => trim(preg_replace($leftover, '', strtr((string) $template['subject'], $replace))),
            'body' => preg_replace($leftover, '', strtr((string) $template['body_html'], $replace)),
        );
    }
}

if (!function_exists('send_notification_email')) {
    function send_notification_email($toEmail, $toName, $subject, $bodyHtml, $eventCode, $referenceNo = null, $transactionType = null, $recipientUserId = null)
    {
        $ci = &get_instance();
        $toEmail = trim((string) $toEmail);

        if ($toEmail === '') {
            return false;
        }

        $ci->config->load('notification_email');
        $smtp = $ci->config->item('notification_smtp');

        $status = 'FAILED';
        $errorMessage = null;

        try {
            if (empty($smtp['host']) || empty($smtp['username']) || empty($smtp['password'])) {
                throw new \Exception('SMTP is not configured (application/config/notification_email.php).');
            }

            require_once 'vendor/autoload.php';

            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = $smtp['host'];
            $mail->SMTPAuth = true;
            $mail->Username = $smtp['username'];
            $mail->Password = $smtp['password'];
            $mail->SMTPSecure = $smtp['secure'];
            $mail->Port = (int) $smtp['port'];

            $fromName = !empty($smtp['from_name']) ? $smtp['from_name'] : config_item('app_name') . ' - ' . config_item('app_long_name');
            $mail->setFrom($smtp['from_email'], $fromName);
            $mail->addAddress($toEmail, (string) $toName);
            $mail->isHTML(true);
            $mail->Subject = (string) $subject;
            $mail->Body = (string) $bodyHtml;
            $mail->AltBody = trim(html_entity_decode(strip_tags(preg_replace('/<(br|\/p|\/tr|\/h[1-6])[^>]*>/i', "\n", (string) $bodyHtml)), ENT_QUOTES, 'UTF-8'));

            // Embedded (CID) instead of a remotely-hosted <img src="https://...">
            // — Gmail/Outlook/most corporate mail gateways block or strip
            // remotely-fetched images by default, which is why the logo
            // showed as a broken-image icon. An inline attachment travels
            // with the message itself, so it always renders regardless of
            // the recipient's "block external images" setting.
            $logoPath = FCPATH . config_item('app_logo_email');
            if (is_file($logoPath) && stripos($mail->Body, 'cid:knetlogo') !== false) {
                $mail->addEmbeddedImage($logoPath, 'knetlogo', 'logo.png');
            }

            $mail->send();
            $status = 'SENT';
        } catch (\Throwable $e) {
            $status = 'FAILED';
            $errorMessage = $e->getMessage();
            log_message('error', 'Notification email failed: ' . $errorMessage);
        }

        try {
            $ci->load->model('Notification_model');
            $ci->Notification_model->create_log($eventCode, $referenceNo, $transactionType, $toEmail, $toName, $subject, $status, $errorMessage, $recipientUserId);
        } catch (\Throwable $e) {
            log_message('error', 'Failed to write notification log: ' . $e->getMessage());
        }

        return $status === 'SENT';
    }
}

if (!function_exists('notify_event')) {
    function notify_event($eventCode, $transactionType, $referenceNo, array $recipients, array $mergeData = array())
    {
        if (empty($recipients)) {
            return;
        }

        $typeLabels = array(
            'CASH_ADVANCE' => 'Cash Advance',
            'LIQUIDATION' => 'Liquidation',
            'REIMBURSEMENT' => 'Reimbursement',
            'REPLENISHMENT' => 'Replenishment',
        );
        $viewPaths = array(
            'CASH_ADVANCE' => 'transactions/cash-advance/view/',
            'LIQUIDATION' => 'transactions/liquidation/view/',
            'REIMBURSEMENT' => 'transactions/reimbursement/view/',
            'REPLENISHMENT' => 'transactions/replenishment/view/',
        );
        $statusLabels = array(
            'PENDING' => 'Pending Approval',
            'APPROVED' => 'Approved',
            'REJECTED' => 'Rejected',
            'PAYMENT_ADVISED' => 'Payment Advised - For Release',
            'PAYMENT_RELEASED' => 'Payment Released',
        );

        $mergeData = array_merge(array(
            'amount' => '',
            'status' => '',
            'remarks' => '',
            'action_date' => date('Y-m-d H:i:s'),
            'requester_name' => '',
            'requester_department' => '',
            'approver_name' => '',
            'due_date' => '',
            'days_overdue' => '0',
            'liquidation_link' => base_url('transactions/liquidation/add'),
            'app_name' => html_escape((string) config_item('app_name')),
            'app_long_name' => html_escape((string) config_item('app_long_name')),
        ), $mergeData);

        if (!isset($mergeData['reference_no'])) {
            $mergeData['reference_no'] = $referenceNo;
        }
        if (!isset($mergeData['transaction_type'])) {
            $mergeData['transaction_type'] = isset($typeLabels[$transactionType]) ? $typeLabels[$transactionType] : (string) $transactionType;
        }
        if (isset($statusLabels[$mergeData['status']])) {
            $mergeData['status'] = $statusLabels[$mergeData['status']];
        }
        if (!isset($mergeData['review_link'])) {
            $mergeData['review_link'] = base_url('transactions/approvals/review/' . $referenceNo);
        }
        if (!isset($mergeData['view_link'])) {
            $mergeData['view_link'] = isset($viewPaths[$transactionType]) ? base_url($viewPaths[$transactionType] . $referenceNo) : $mergeData['review_link'];
        }

        try {
            $rendered = render_notification_template($eventCode, $transactionType, $mergeData);
        } catch (\Throwable $e) {
            log_message('error', 'Failed to render notification template for ' . $eventCode . ': ' . $e->getMessage());
            return;
        }

        if ($rendered === null) {
            return;
        }

        $seen = array();
        foreach ($recipients as $recipient) {
            $email = isset($recipient['email']) ? trim((string) $recipient['email']) : '';
            $name = isset($recipient['name']) ? $recipient['name'] : '';
            $recipientUserId = isset($recipient['user_id']) ? (int) $recipient['user_id'] : null;
            if ($email === '' || isset($seen[strtolower($email)])) {
                continue;
            }
            $seen[strtolower($email)] = true;

            send_notification_email($email, $name, $rendered['subject'], $rendered['body'], $eventCode, $referenceNo, $transactionType, $recipientUserId);
        }
    }
}
