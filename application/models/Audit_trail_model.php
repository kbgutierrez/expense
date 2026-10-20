<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once APPPATH . 'core/MY_Model.php';

class Audit_trail_model extends MY_Model
{
    public function list_by_transaction($transactionId)
    {
        return $this->query_rows(
            "SELECT
                at.id,
                at.transaction_type,
                at.transaction_id,
                at.action,
                at.entity_type,
                at.entity_id,
                COALESCE(ld_direct.description, ld_via_item.description, rd_direct.description, rd_via_item.description) AS description,
                COALESCE(ld_direct.actual_amount, ld_via_item.actual_amount, rd_direct.actual_amount, rd_via_item.actual_amount) AS actual_amount,
                COALESCE(ld_direct.approved_amount, ld_via_item.approved_amount, rd_direct.approved_amount, rd_via_item.approved_amount) AS approved_amount,
                at.field_name,
                at.old_value,
                at.new_value,
                at.changed_by,
                at.remarks,
                u.lastname + ', ' + u.firstname AS changed_by_name,
                d.short_name AS changed_by_dept,
                at.created_date
            FROM tbl_audit_trail at
            LEFT JOIN BigEUsers.dbo.users u
                ON at.changed_by = u.id
            LEFT JOIN BigEHRIS.dbo.department d
                ON u.department_id = d.department_id
            LEFT JOIN tbl_approval_per_items c
                ON at.transaction_type = 'LIQUIDATION' AND at.entity_type = 'ITEM' AND at.field_name = 'status' AND TRY_CAST(at.entity_id AS BIGINT) = c.id
            LEFT JOIN tbl_liquidation_details ld_via_item
                ON c.line_item_id = ld_via_item.id
            LEFT JOIN tbl_liquidation_details ld_direct
                ON at.transaction_type = 'LIQUIDATION' AND at.entity_type = 'ITEM' AND at.field_name <> 'status' AND TRY_CAST(at.entity_id AS BIGINT) = ld_direct.id
            LEFT JOIN tbl_approval_per_items rc
                ON at.transaction_type = 'REIMBURSEMENT' AND at.entity_type = 'ITEM' AND at.field_name = 'status' AND TRY_CAST(at.entity_id AS BIGINT) = rc.id
            LEFT JOIN tbl_reimbursement_details rd_via_item
                ON rc.line_item_id = rd_via_item.id
            LEFT JOIN tbl_reimbursement_details rd_direct
                ON at.transaction_type = 'REIMBURSEMENT' AND at.entity_type = 'ITEM' AND at.field_name <> 'status' AND TRY_CAST(at.entity_id AS BIGINT) = rd_direct.id
            WHERE at.transaction_id = CAST(? AS VARCHAR(20))
            ORDER BY at.created_date ASC",
            array($transactionId)
        );
    }

    public function create($transactionType, $transactionId, $action, $entityType, $entityId, $fieldName, $oldValue, $newValue, $changedBy)
    {
        $this->query_rows(
            'INSERT INTO tbl_audit_trail (
                transaction_type, transaction_id, action, entity_type, entity_id, field_name, old_value, new_value, changed_by, created_date
            ) VALUES (
                CAST(? AS VARCHAR(20)), CAST(? AS VARCHAR(20)), CAST(? AS VARCHAR(20)), CAST(? AS VARCHAR(20)), CAST(? AS VARCHAR(50)),
                CAST(? AS VARCHAR(50)), CAST(? AS NVARCHAR(MAX)), CAST(? AS NVARCHAR(MAX)), CAST(? AS INT), GETDATE()
            )',
            array($transactionType, $transactionId, $action, $entityType, $entityId, $fieldName, $oldValue, $newValue, $changedBy)
        );

        return $this->insert_id();
    }
}
