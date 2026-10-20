<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once APPPATH . 'core/MY_Model.php';

class Notification_model extends MY_Model
{
    const DUPLICATE_TEMPLATE = 'A template for this event and transaction type already exists. Edit it instead.';

    public function find_template($id)
    {
        $this->db->select('id, event_code, transaction_type, subject, body_html, is_active, created_date, updated_date', false)
            ->from('tbl_notification_templates')
            ->where('id', $id);
        return $this->row();
    }

    public function find_template_for_event($eventCode, $transactionType = null)
    {
        return $this->query_row(
            'SELECT TOP 1 id, event_code, transaction_type, subject, body_html
            FROM tbl_notification_templates
            WHERE event_code = CAST(? AS VARCHAR(30))
                AND is_active = 1
                AND (transaction_type = CAST(? AS VARCHAR(20)) OR transaction_type IS NULL)
            ORDER BY CASE WHEN transaction_type IS NULL THEN 1 ELSE 0 END',
            array($eventCode, $transactionType)
        );
    }

    public function list_templates($cursorId = null, $take = null)
    {
        $this->db->select('id, event_code, transaction_type, subject, is_active, updated_date', false)
            ->from('tbl_notification_templates');

        if ($cursorId !== null) {
            $this->db->where('id <', $cursorId);
        }

        if ($take === null || (int) $take < 1) {
            $this->db->order_by('event_code', 'ASC')
                ->order_by("ISNULL(transaction_type, '')", 'ASC', false);
            return $this->rows();
        }

        $this->db->order_by('id', 'DESC')->limit((int) $take + 1);
        return $this->rows();
    }

    public function list_log($cursorId = null, $take = null)
    {
        $this->db->select('id, event_code, reference_no, transaction_type, recipient_email, recipient_name, subject, status, error_message, sent_date', false)
            ->from('tbl_notification_log');

        if ($cursorId !== null) {
            $this->db->where('id <', $cursorId);
        }

        $this->db->order_by('id', 'DESC');

        if ($take !== null && (int) $take >= 1) {
            $this->db->limit((int) $take + 1);
        }

        return $this->rows();
    }

    public function list_log_for_user($email, $userId, $take = 10)
    {
        $top = $take === null ? null : (int) $take;

        $sql = 'DECLARE @Take INT = ?;
            SELECT TOP (@Take) * FROM (
                SELECT
                    NULL AS id,
                    \'TXN_SUBMITTED\' AS event_code,
                    V.reference_no,
                    V.transaction_type,
                    \'Approval Required: \' + V.reference_no AS subject,
                    V.submitted_date AS sent_date,
                    1 AS is_approver,
                    1 AS is_pending
                FROM ' . Knet_views::sub('vw_approvals_header', 'V') . '
                WHERE V.approver_id = CAST(? AS INT)
                    AND V.status = \'PENDING\'
                    AND (
                        V.approval_order = (
                            SELECT MIN(D.approval_order)
                            FROM tbl_approval_details D
                            WHERE D.approval_header_id = V.approval_header_id
                                AND D.status = \'PENDING\'
                        )
                        OR EXISTS (
                            SELECT 1
                            FROM tbl_approval_details D
                            WHERE D.approval_header_id = V.approval_header_id
                                AND D.approval_order = (
                                    SELECT MAX(X.approval_order)
                                    FROM tbl_approval_details X
                                    WHERE X.approval_header_id = V.approval_header_id
                                        AND X.approval_order < V.approval_order
                                )
                                AND D.status = \'APPROVED\'
                        )
                    )

                UNION ALL

                SELECT
                    L.id,
                    L.event_code,
                    L.reference_no,
                    L.transaction_type,
                    L.subject,
                    L.sent_date,
                    CASE WHEN EXISTS (
                        SELECT 1
                        FROM dbo.tbl_approval_header AH
                        INNER JOIN dbo.tbl_approval_matrix_details AD ON AD.matrix_header_id = AH.approval_matrix_id
                        WHERE AH.reference_id = L.reference_no
                            AND AH.is_active = 1
                            AND AD.approver_id = CAST(? AS INT)
                    ) THEN 1 ELSE 0 END AS is_approver,
                    0 AS is_pending
                FROM dbo.tbl_notification_log L
                WHERE (
                        (L.recipient_user_id IS NOT NULL AND L.recipient_user_id = CAST(? AS INT))
                        OR (L.recipient_user_id IS NULL AND L.recipient_email = CAST(? AS NVARCHAR(255)))
                    )
                    AND L.status = \'SENT\'
                    AND L.event_code NOT IN (\'TXN_SUBMITTED\', \'TXN_STEP_APPROVED\')
            ) Feed
            ORDER BY Feed.sent_date DESC';

        return $this->query_rows($sql, array($top, $userId, $userId, $userId, $email));
    }

    public function create_log($eventCode, $referenceNo, $transactionType, $recipientEmail, $recipientName, $subject, $status, $errorMessage = null, $recipientUserId = null)
    {
        $this->query_rows(
            'INSERT INTO dbo.tbl_notification_log
                (event_code, reference_no, transaction_type, recipient_email, recipient_name, subject, status, error_message, sent_date, recipient_user_id)
            VALUES
                (CAST(? AS VARCHAR(30)), CAST(? AS VARCHAR(20)), CAST(? AS VARCHAR(20)), CAST(? AS NVARCHAR(255)), CAST(? AS NVARCHAR(255)), CAST(? AS NVARCHAR(255)), CAST(? AS VARCHAR(10)), CAST(? AS NVARCHAR(MAX)), GETDATE(), CAST(? AS INT))',
            array($eventCode, $referenceNo, $transactionType, $recipientEmail, $recipientName, $subject, $status, $errorMessage, $recipientUserId)
        );
    }

    public function create_template($eventCode, $transactionType, $subject, $bodyHtml, $createdBy)
    {
        $existing = $this->query_rows(
            "SELECT TOP 1 1 AS found FROM tbl_notification_templates
            WHERE event_code = CAST(? AS VARCHAR(30))
                AND ISNULL(transaction_type, '') = ISNULL(CAST(? AS VARCHAR(20)), '')",
            array($eventCode, $transactionType)
        );

        if (!empty($existing)) {
            $this->fail(self::DUPLICATE_TEMPLATE, 50003);
        }

        $this->query_rows(
            "INSERT INTO tbl_notification_templates
                (event_code, transaction_type, subject, body_html, is_active, created_by, created_date)
            VALUES
                (CAST(? AS VARCHAR(30)), NULLIF(CAST(? AS VARCHAR(20)), ''), CAST(? AS NVARCHAR(255)), CAST(? AS NVARCHAR(MAX)), 1, CAST(? AS INT), GETDATE())",
            array($eventCode, $transactionType, $subject, $bodyHtml, $createdBy)
        );

        return $this->insert_id();
    }

    public function update_template($id, $eventCode, $transactionType, $subject, $bodyHtml, $isActive, $updatedBy)
    {
        $existing = $this->query_rows(
            "SELECT TOP 1 1 AS found FROM tbl_notification_templates
            WHERE id <> CAST(? AS INT)
                AND event_code = CAST(? AS VARCHAR(30))
                AND ISNULL(transaction_type, '') = ISNULL(CAST(? AS VARCHAR(20)), '')",
            array($id, $eventCode, $transactionType)
        );

        if (!empty($existing)) {
            $this->fail(self::DUPLICATE_TEMPLATE, 50003);
        }

        $this->query_rows(
            "UPDATE tbl_notification_templates
            SET event_code = CAST(? AS VARCHAR(30)),
                transaction_type = NULLIF(CAST(? AS VARCHAR(20)), ''),
                subject = CAST(? AS NVARCHAR(255)),
                body_html = CAST(? AS NVARCHAR(MAX)),
                is_active = CAST(? AS BIT),
                updated_by = CAST(? AS INT),
                updated_date = GETDATE()
            WHERE id = CAST(? AS INT)",
            array($eventCode, $transactionType, $subject, $bodyHtml, $isActive, $updatedBy, $id)
        );
    }
}
