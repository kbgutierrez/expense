<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

class Knet_views
{
    public static function sub($name, $alias)
    {
        return '(' . self::$name() . ') ' . $alias;
    }

    public static function vw_ca()
    {
        return "SELECT
    A.id,
    A.cash_advance_id,
    A.user_id,
    B.lastname + ', ' + B.firstname AS user_name,
    A.amount,
    A.description,
    A.needed_date,
    A.created_date,
    A.updated_date,
    A.status AS status_code,
    C.status_name,
    A.final_pdf_path,
    A.generated_pdf_path,
    A.is_kflow_submitted,
    A.kflow_doc_status,
    A.kflow_doc_id,
    A.cost_center_id,
    D.cost_center_name,
    A.address,
    COALESCE(PT.firstname + ' ' + PT.lastname, A.payable_to) AS payable_to,
    TRY_CAST(A.payable_to AS INT) AS payable_to_user_id,
    A.io_number,
    A.approved_amount
FROM tbl_cash_advance A
INNER JOIN BigEUsers.dbo.users B ON A.user_id = B.id
INNER JOIN tbl_status C ON A.status = C.status_code
LEFT JOIN tbl_cost_center D ON A.cost_center_id = D.cost_center_code
LEFT JOIN BigEUsers.dbo.users PT ON TRY_CAST(A.payable_to AS INT) = PT.id";
    }

    public static function vw_approvals_header()
    {
        return "SELECT
    B.id AS approval_header_id,
    F.id AS approval_detail_id,
    F.approver_id,
    F.approval_order,
    F.approval_type,
    A.status AS status_code,
    F.status,
    A.cash_advance_id AS reference_no,
    A.description,
    C.transaction_type,
    A.user_id,
    D.lastname + ', ' + D.firstname AS requester_name,
    E.short_name AS department,
    A.amount AS ca_amount,
    NULL AS lq_amount,
    ISNULL(SD.prev_approval_date, A.created_date) AS submitted_date,
    F.approval_date AS decided_date
FROM tbl_cash_advance A
INNER JOIN tbl_approval_header B ON A.cash_advance_id = B.reference_id
INNER JOIN tbl_approval_matrix_header C ON B.approval_matrix_id = C.id
INNER JOIN BigEUsers.dbo.users D ON A.user_id = D.id
INNER JOIN BigEHRIS.dbo.department E ON D.department_id = E.department_id
INNER JOIN tbl_approval_details F ON B.id = F.approval_header_id
LEFT JOIN (" . self::approvalStepDates() . ") SD ON F.id = SD.approval_detail_id
WHERE A.kflow_doc_status = 4
UNION ALL
SELECT
    B.id AS approval_header_id,
    F.id AS approval_detail_id,
    F.approver_id,
    F.approval_order,
    F.approval_type,
    A.status AS status_code,
    F.status,
    A.liquidation_id AS reference_no,
    '',
    C.transaction_type,
    A.created_by AS user_id,
    D.lastname + ', ' + D.firstname AS requester_name,
    E.short_name AS department,
    NULL AS ca_amount,
    A.total_amount_spent AS lq_amount,
    ISNULL(SD.prev_approval_date, A.created_date) AS submitted_date,
    F.approval_date AS decided_date
FROM tbl_liquidation_header A
INNER JOIN tbl_approval_header B ON A.liquidation_id = B.reference_id
INNER JOIN tbl_approval_matrix_header C ON B.approval_matrix_id = C.id
INNER JOIN BigEUsers.dbo.users D ON A.created_by = D.id
INNER JOIN BigEHRIS.dbo.department E ON D.department_id = E.department_id
INNER JOIN tbl_approval_details F ON B.id = F.approval_header_id
LEFT JOIN (" . self::approvalStepDates() . ") SD ON F.id = SD.approval_detail_id
UNION ALL
SELECT
    B.id AS approval_header_id,
    F.id AS approval_detail_id,
    F.approver_id,
    F.approval_order,
    F.approval_type,
    A.status_code AS status_code,
    F.status,
    A.reimbursement_id AS reference_no,
    '',
    C.transaction_type,
    A.created_by AS user_id,
    D.lastname + ', ' + D.firstname AS requester_name,
    E.short_name AS department,
    NULL AS ca_amount,
    A.total_amount AS lq_amount,
    ISNULL(SD.prev_approval_date, A.created_date) AS submitted_date,
    F.approval_date AS decided_date
FROM tbl_reimbursement_header A
INNER JOIN tbl_approval_header B ON A.reimbursement_id = B.reference_id
INNER JOIN tbl_approval_matrix_header C ON B.approval_matrix_id = C.id
INNER JOIN BigEUsers.dbo.users D ON A.created_by = D.id
INNER JOIN BigEHRIS.dbo.department E ON D.department_id = E.department_id
INNER JOIN tbl_approval_details F ON B.id = F.approval_header_id
LEFT JOIN (" . self::approvalStepDates() . ") SD ON F.id = SD.approval_detail_id";
    }

    private static function approvalStepDates()
    {
        return "SELECT
        id AS approval_detail_id,
        approval_header_id,
        LAG(approval_date) OVER (PARTITION BY approval_header_id ORDER BY approval_order) AS prev_approval_date
    FROM tbl_approval_details";
    }

    public static function vw_cost_center()
    {
        return "SELECT
    A.id,
    A.cost_center_code,
    A.cost_center_name,
    A.category,
    A.is_active,
    A.created_date,
    A.updated_date,
    A.created_by,
    B.lastname + ',' + B.firstname AS created_by_name,
    A.updated_by,
    D.lastname + ',' + D.firstname AS updated_by_name
FROM tbl_cost_center A
LEFT JOIN BigEUsers.dbo.users B ON A.created_by = B.id
LEFT JOIN BigEUsers.dbo.users D ON A.updated_by = D.id";
    }

    public static function vw_expense_types()
    {
        return "SELECT
    A.id,
    A.expense_code,
    A.description AS category_name,
    A.long_text,
    A.short_text,
    A.category,
    A.created_date,
    A.updated_date,
    A.created_by,
    B.lastname + ',' + B.firstname AS created_by_name,
    A.updated_by,
    D.lastname + ',' + D.firstname AS updated_by_name,
    A.status,
    C.status_name
FROM tbl_expense_category A
INNER JOIN BigEUsers.dbo.users B ON A.created_by = B.id
INNER JOIN tbl_status C ON A.status = C.status_code
LEFT JOIN BigEUsers.dbo.users D ON A.updated_by = D.id";
    }

    public static function vw_lq_details()
    {
        return "SELECT
    A.id,
    A.liquidation_id,
    A.description,
    A.invoice_receipt_no,
    A.actual_amount,
    A.attachment,
    A.document_date,
    A.updated_date,
    A.expense_category,
    B.long_text AS category_name,
    A.is_vatable,
    A.net_amount,
    A.vat_amount,
    A.vendor_name,
    A.vendor_address,
    A.vendor_tin,
    B.category,
    A.approved_amount
FROM tbl_liquidation_details A
INNER JOIN tbl_expense_category B ON A.expense_category = B.expense_code";
    }

    public static function vw_lq_header()
    {
        return "SELECT
    A.id,
    A.liquidation_id,
    A.cash_advance_id,
    C.amount AS ca_amount,
    C.description,
    A.total_amount_spent,
    A.refund_amount,
    A.reimburse_amount,
    A.submitted_date,
    A.created_date,
    A.created_by AS created_by_id,
    D.lastname + ',' + D.firstname AS created_by,
    A.updated_date,
    A.updated_by,
    A.status AS status_code,
    B.status_name,
    C.cost_center_id,
    COALESCE(PT.firstname + ' ' + PT.lastname, C.payable_to) AS payable_to,
    C.address,
    E.cost_center_name,
    C.approved_amount
FROM tbl_liquidation_header A
INNER JOIN tbl_status B ON A.status = B.status_code
INNER JOIN tbl_cash_advance C ON A.cash_advance_id = C.cash_advance_id
INNER JOIN BigEUsers.dbo.users D ON A.created_by = D.id
LEFT JOIN tbl_cost_center E ON C.cost_center_id = E.cost_center_code
LEFT JOIN BigEUsers.dbo.users PT ON TRY_CAST(C.payable_to AS INT) = PT.id";
    }

    public static function vw_rf_assignment_list()
    {
        return "SELECT
    A.id,
    A.fund_id,
    F.fund_code,
    F.scope_name AS fund_scope_name,
    A.assignee_type,
    A.assignee_id,
    A.assignee_name,
    A.status,
    CASE A.status
        WHEN 'RF_ASSIGN_ACTIVE' THEN 'Active'
        WHEN 'RF_ASSIGN_INACTIVE' THEN 'Inactive'
        ELSE A.status
    END AS status_name,
    A.effective_date,
    A.end_date,
    A.created_by,
    CB.lastname + ', ' + CB.firstname AS created_by_name,
    A.created_date,
    A.updated_by,
    A.updated_date
FROM tbl_revolving_fund_assignment A
INNER JOIN tbl_revolving_fund F ON A.fund_id = F.id
INNER JOIN BigEUsers.dbo.users CB ON A.created_by = CB.id";
    }

    public static function vw_rf_fund_list()
    {
        return "SELECT
    A.id,
    A.fund_code,
    A.scope_type,
    A.scope_id,
    A.scope_name,
    A.opening_balance,
    A.available_balance,
    A.allow_negative_balance,
    A.allow_self_cash_in,
    A.status,
    CASE A.status
        WHEN 'RF_ACTIVE' THEN 'Active'
        WHEN 'RF_INACTIVE' THEN 'Inactive'
        WHEN 'RF_LOCKED' THEN 'Locked'
        ELSE A.status
    END AS status_name,
    A.remarks,
    A.created_by,
    CB.lastname + ', ' + CB.firstname AS created_by_name,
    A.created_date,
    A.updated_by,
    UB.lastname + ', ' + UB.firstname AS updated_by_name,
    A.updated_date
FROM tbl_revolving_fund A
INNER JOIN BigEUsers.dbo.users CB ON A.created_by = CB.id
LEFT JOIN BigEUsers.dbo.users UB ON A.updated_by = UB.id";
    }

    public static function vw_rf_ledger_list()
    {
        return "SELECT
    A.id,
    A.fund_id,
    F.fund_code,
    F.scope_name AS fund_scope_name,
    A.trx_date,
    A.trx_type,
    CASE A.trx_type
        WHEN 'RF_TOPUP' THEN 'Top-up'
        WHEN 'RF_CASH_IN' THEN 'Cash In'
        WHEN 'RF_ADJUSTMENT' THEN 'Adjustment'
        WHEN 'RF_CA_PAYOUT' THEN 'Cash Advance Payout'
        WHEN 'RF_REIMBURSEMENT_PAYOUT' THEN 'Reimbursement Payout'
        WHEN 'RF_LQ_PAYOUT' THEN 'Liquidation Payout'
        ELSE A.trx_type
    END AS trx_type_name,
    A.amount,
    A.balance_after,
    A.reference_type,
    A.reference_id,
    A.remarks,
    A.created_by,
    CB.lastname + ', ' + CB.firstname AS created_by_name,
    A.created_date
FROM tbl_revolving_fund_ledger A
INNER JOIN tbl_revolving_fund F ON A.fund_id = F.id
INNER JOIN BigEUsers.dbo.users CB ON A.created_by = CB.id";
    }

    public static function vw_rmb_details()
    {
        return "SELECT
    A.id,
    A.reimbursement_id,
    A.description,
    A.invoice_receipt_no,
    A.actual_amount,
    A.attachment,
    A.document_date,
    A.updated_date,
    A.expense_category,
    B.long_text AS category_name,
    A.is_vatable,
    A.net_amount,
    A.vat_amount,
    A.vendor_name,
    A.vendor_address,
    A.vendor_tin,
    B.category,
    A.approved_amount
FROM tbl_reimbursement_details A
INNER JOIN tbl_expense_category B ON A.expense_category = B.expense_code";
    }

    public static function vw_rmb_header()
    {
        return "SELECT
    A.id,
    A.reimbursement_id,
    A.user_id,
    D.lastname + ',' + D.firstname AS user_name,
    A.total_amount,
    A.status_code,
    CASE A.status_code
        WHEN 'RMB_DRAFT' THEN 'Draft'
        WHEN 'RMB_SUBMITTED' THEN 'Submitted'
        WHEN 'RMB_PENDING' THEN 'Pending Approval'
        WHEN 'RMB_APPROVED' THEN 'Approved'
        WHEN 'RMB_REJECTED' THEN 'Rejected'
        WHEN 'RMB_PAID' THEN 'Paid'
        ELSE A.status_code
    END AS status_name,
    A.description,
    A.cost_center_id,
    E.cost_center_name,
    COALESCE(PT.firstname + ' ' + PT.lastname, A.payable_to) AS payable_to,
    TRY_CAST(A.payable_to AS INT) AS payable_to_user_id,
    A.address,
    A.io_number,
    A.created_by,
    A.created_date,
    A.updated_by,
    A.updated_date,
    A.filed_by,
    F.lastname + ',' + F.firstname AS filed_by_name
FROM tbl_reimbursement_header A
INNER JOIN BigEUsers.dbo.users D ON A.created_by = D.id
LEFT JOIN BigEUsers.dbo.users F ON A.filed_by = F.id
LEFT JOIN BigEUsers.dbo.users PT ON TRY_CAST(A.payable_to AS INT) = PT.id
LEFT JOIN (
    SELECT cost_center_code, MIN(cost_center_name) AS cost_center_name
    FROM tbl_cost_center
    GROUP BY cost_center_code
) E ON A.cost_center_id = E.cost_center_code";
    }

    public static function vw_rpl_header()
    {
        return "SELECT
    A.id,
    A.replenishment_id,
    A.fund_id,
    F.fund_code,
    A.user_id,
    D.lastname + ',' + D.firstname AS user_name,
    A.total_amount,
    A.status_code,
    CASE A.status_code
        WHEN 'RPL_DRAFT' THEN 'Draft'
        WHEN 'RPL_SUBMITTED' THEN 'Submitted'
        WHEN 'RPL_PENDING' THEN 'Pending Approval'
        WHEN 'RPL_APPROVED' THEN 'Approved'
        WHEN 'RPL_FOR_RELEASE' THEN 'Payment Advised - For Release'
        WHEN 'RPL_COMPLETED' THEN 'Completed'
        WHEN 'RPL_REJECTED' THEN 'Rejected'
        ELSE A.status_code
    END AS status_name,
    A.remarks,
    A.created_by,
    CB.lastname + ',' + CB.firstname AS created_by_name,
    A.created_date,
    A.updated_by,
    A.updated_date
FROM tbl_replenishment_header A
INNER JOIN tbl_revolving_fund F ON A.fund_id = F.id
INNER JOIN BigEUsers.dbo.users D ON A.user_id = D.id
INNER JOIN BigEUsers.dbo.users CB ON A.created_by = CB.id";
    }
}
