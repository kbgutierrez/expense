<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once APPPATH . 'core/MY_Model.php';

class Approval_model extends MY_Model
{
    public function list_pending_headers($userId, $cursorId = null, $take = null)
    {
        return $this->query_rows(
            'SELECT ' . $this->top($take) . 'V.approval_detail_id, V.reference_no, V.transaction_type, V.user_id, V.requester_name, V.department, V.ca_amount, V.lq_amount, V.submitted_date
FROM ' . Knet_views::sub('vw_approvals_header', 'V') . "
WHERE V.approver_id = CAST(? AS INT)
    AND (CAST(? AS BIGINT) IS NULL OR V.approval_detail_id < CAST(? AS BIGINT))
    AND V.status = 'PENDING'
    AND (
        V.approval_order = (
            SELECT MIN(D.approval_order)
            FROM tbl_approval_details D
            WHERE D.approval_header_id = V.approval_header_id
                AND D.status = 'PENDING'
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
                AND D.status = 'APPROVED'
        )
    )
ORDER BY V.submitted_date DESC",
            array($userId, $cursorId, $cursorId)
        );
    }

    public function list_past_headers($userId, $cursorId = null, $take = null)
    {
        return $this->query_rows(
            'SELECT ' . $this->top($take) . 'V.approval_detail_id, V.reference_no, V.transaction_type, V.user_id, V.requester_name, V.department, V.ca_amount, V.lq_amount, V.submitted_date,
    V.status AS decision_status,
    ISNULL(V.decided_date, V.submitted_date) AS decided_date,
    CASE WHEN EXISTS (
        SELECT 1
        FROM dbo.tbl_bizlink_export_batch_line L
        INNER JOIN dbo.tbl_bizlink_export_batch B ON B.id = L.batch_id
        WHERE L.reference_no = V.reference_no AND B.is_void = 0
    ) THEN 1 ELSE 0 END AS is_bizlink_exported,
    ' . Knet_functions::fn_approved_amount('V.reference_no') . ' AS approved_amount
FROM ' . Knet_views::sub('vw_approvals_header', 'V') . "
WHERE V.approver_id = CAST(? AS INT)
    AND (CAST(? AS BIGINT) IS NULL OR V.approval_detail_id < CAST(? AS BIGINT))
    AND V.status IN ('APPROVED', 'REJECTED')
ORDER BY ISNULL(V.decided_date, V.submitted_date) DESC",
            array($userId, $cursorId, $cursorId)
        );
    }

    public function find_transaction_by_item($approvalPerItemId)
    {
        $this->db->select('ah.reference_id, ah.reference_id AS transaction_id, api.status, api.remarks, api.is_notify, ad.approver_id, ad.approval_order', false)
            ->from('tbl_approval_per_items api')
            ->join('tbl_approval_details ad', 'api.approval_details_id = ad.id', 'inner')
            ->join('tbl_approval_header ah', 'ad.approval_header_id = ah.id', 'inner')
            ->where('api.id', $approvalPerItemId)
            ->where('api.is_active', 1)
            ->where('ad.is_active', 1)
            ->where('ah.is_active', 1);
        return $this->row();
    }

    private function top($take)
    {
        if ($take === null || (int) $take < 1) {
            return '';
        }
        return 'TOP (' . ((int) $take + 1) . ') ';
    }
}
