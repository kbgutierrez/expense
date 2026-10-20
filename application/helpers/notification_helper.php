<?php
(defined('BASEPATH')) or exit('No direct script access allowed');

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

        return array(
            'subject' => strtr((string) $template['subject'], $replace),
            'body' => strtr((string) $template['body_html'], $replace),
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

            $mail->setFrom($smtp['from_email'], $smtp['from_name']);
            $mail->addAddress($toEmail, (string) $toName);
            $mail->isHTML(true);
            $mail->Subject = (string) $subject;
            $mail->Body = (string) $bodyHtml;

            // Embedded (CID) instead of a remotely-hosted <img src="https://...">
            // — Gmail/Outlook/most corporate mail gateways block or strip
            // remotely-fetched images by default, which is why the logo
            // showed as a broken-image icon. An inline attachment travels
            // with the message itself, so it always renders regardless of
            // the recipient's "block external images" setting.
            $logoPath = FCPATH . 'assets/img/k-net logo.png';
            if (is_file($logoPath) && stripos($mail->Body, 'cid:knetlogo') !== false) {
                $mail->addEmbeddedImage($logoPath, 'knetlogo', 'k-net-logo.png');
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

        if (!isset($mergeData['reference_no'])) {
            $mergeData['reference_no'] = $referenceNo;
        }
        if (!isset($mergeData['transaction_type'])) {
            $mergeData['transaction_type'] = $transactionType;
        }
        if (!isset($mergeData['review_link'])) {
            $mergeData['review_link'] = base_url('transactions/approvals/review/' . $referenceNo);
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
