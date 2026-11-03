<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once APPPATH . 'core/MY_Model.php';

class Liquidation_model extends MY_Model
{
    public function list_approvals($liquidationId)
    {
        return $this->query_rows(
            "SELECT
                A.id,
                A.approval_details_id,
                A.line_item_id,
                B.approver_id,
                C.lastname + ', ' + C.firstname AS approver_name,
                A.status,
                A.remarks,
                A.is_active,
                A.created_date
            FROM tbl_approval_per_items A
            INNER JOIN tbl_approval_details B ON A.approval_details_id = B.id
            INNER JOIN BigEUsers.dbo.users C ON B.approver_id = C.id
            WHERE A.line_item_id IN (
                SELECT id FROM tbl_liquidation_details
                WHERE liquidation_id = CAST(? AS VARCHAR(20))
            )
            AND A.is_active = 1
            ORDER BY A.line_item_id, A.created_date, A.id",
            array($liquidationId)
        );
    }

    public function list_details($liquidationId)
    {
        return $this->query_rows(
            'SELECT V.id, V.liquidation_id, V.description, V.attachment,
                V.invoice_receipt_no, V.actual_amount,
                V.document_date, V.updated_date, V.expense_category, V.category_name,
                V.is_vatable, V.net_amount, V.vat_amount, V.vendor_name, V.vendor_tin, V.vendor_address
            FROM ' . Knet_views::sub('vw_lq_details', 'V') . '
            WHERE V.liquidation_id = CAST(? AS VARCHAR(20))
                AND V.category = (
                    SELECT CAST(C.category AS VARCHAR(20))
                    FROM tbl_liquidation_header H
                    INNER JOIN BigEUsers.dbo.users U ON H.created_by = U.id
                    INNER JOIN BigEHRIS.dbo.department C ON U.department_id = C.department_id
                    WHERE H.liquidation_id = CAST(? AS VARCHAR(20))
                )',
            array($liquidationId, $liquidationId)
        );
    }

    public function find_header($liquidationId)
    {
        return $this->query_row(
            'SELECT TOP 1
                V.id, V.liquidation_id, V.cash_advance_id, V.ca_amount, V.description, V.total_amount_spent,
                V.refund_amount, V.reimburse_amount, V.approved_amount, V.submitted_date, V.created_date,
                V.created_by_id, V.created_by, V.updated_date, V.updated_by, V.status_code, V.status_name,
                V.payable_to, V.address, V.cost_center_id, V.cost_center_name, V.ca_date
            FROM ' . Knet_views::sub('vw_lq_header', 'V') . '
            WHERE V.liquidation_id = CAST(? AS VARCHAR(50))',
            array($liquidationId)
        );
    }

    public function list_by_user($userId, $cursorId = null, $take = null)
    {
        $columns = "
                V.id, V.liquidation_id, V.cash_advance_id, V.ca_amount, V.description, V.total_amount_spent,
                (
                    SELECT SUM(LD.approved_amount)
                    FROM tbl_liquidation_details LD
                    WHERE LD.liquidation_id = V.liquidation_id
                ) AS approved_liquidated_amount,
                CASE WHEN EXISTS (
                    SELECT 1 FROM tbl_approval_header AH
                    INNER JOIN tbl_approval_details AD ON AD.approval_header_id = AH.id
                    WHERE AH.reference_id = V.liquidation_id AND AH.is_active = 1 AND AD.is_active = 1 AND AD.status = 'APPROVED'
                ) THEN 1 ELSE 0 END AS has_approval,
                V.refund_amount, V.reimburse_amount, V.submitted_date,
                V.created_date, V.created_by_id, V.created_by, V.updated_date, V.updated_by,
                V.status_code, V.status_name
            FROM " . Knet_views::sub('vw_lq_header', 'V') . '
            WHERE V.created_by_id = @UserId
                AND (@CursorId IS NULL OR V.id < @CursorId)
            ORDER BY V.id DESC;';

        return $this->execute(
            'IF @Take IS NULL OR @Take < 1
            BEGIN
                SELECT ' . $columns . '
                RETURN;
            END
            SELECT TOP (@Take + 1) ' . $columns,
            '@UserId INT, @CursorId BIGINT, @Take INT',
            array($userId, $cursorId, $take)
        );
    }

    public function get_petty_cash_data($liquidationId)
    {
        return $this->query_row(
            "SELECT
                A.liquidation_id AS reference_no,
                D.lastname + ', ' + D.firstname AS requester_name,
                E.short_name AS department_name,
                ISNULL(
                    (
                        SELECT SUM(ISNULL(LD.approved_amount, LD.actual_amount))
                        FROM dbo.tbl_liquidation_details LD
                        WHERE LD.liquidation_id = A.liquidation_id
                    ),
                    A.total_amount_spent
                ) AS total_amount,
                CASE
                    WHEN LTRIM(RTRIM(ISNULL(C.description, ''))) <> ''
                        THEN C.description
                    ELSE CONCAT('Liquidation of Cash Advance ', A.cash_advance_id)
                END AS purpose,
                A.created_date
            FROM dbo.tbl_liquidation_header A
            INNER JOIN BigEUsers.dbo.users D ON A.created_by = D.id
            INNER JOIN BigEHRIS.dbo.department E ON D.department_id = E.department_id
            LEFT JOIN dbo.tbl_cash_advance C ON A.cash_advance_id = C.cash_advance_id
            WHERE A.liquidation_id = CAST(? AS VARCHAR(20))",
            array($liquidationId)
        );
    }

    public function update_detail_review($detailId, $description, $invoiceReceiptNo, $actualAmount, $documentDate, $expenseCategory, $isVatable, $netAmount, $vatAmount, $vendorName = null, $vendorAddress = null, $vendorTin = null, $approvedGrossAmount = null)
    {
        $this->execute(
            "UPDATE dbo.tbl_liquidation_details
            SET
                description = @Description,
                invoice_receipt_no = NULLIF(LTRIM(RTRIM(@InvoiceReceiptNo)), ''),
                actual_amount = @ActualAmount,
                approved_amount = ISNULL(@ApprovedGrossAmount, @ActualAmount),
                document_date = @DocumentDate,
                updated_date = GETDATE(),
                expense_category = @ExpenseCategory,
                is_vatable = @IsVatable,
                net_amount = @NetAmount,
                vat_amount = @VatAmount,
                vendor_name = NULLIF(LTRIM(RTRIM(@VendorName)), ''),
                vendor_address = NULLIF(LTRIM(RTRIM(@VendorAddress)), ''),
                vendor_tin = NULLIF(LTRIM(RTRIM(@VendorTin)), '')
            WHERE id = @DetailId",
            '@DetailId BIGINT, @Description NVARCHAR(MAX), @InvoiceReceiptNo VARCHAR(50), @ActualAmount DECIMAL(12, 2),
            @DocumentDate DATETIME, @ExpenseCategory NVARCHAR(10), @IsVatable BIT, @NetAmount DECIMAL(18, 2),
            @VatAmount DECIMAL(18, 2), @VendorName NVARCHAR(255), @VendorAddress NVARCHAR(500), @VendorTin NVARCHAR(50),
            @ApprovedGrossAmount DECIMAL(12, 2)',
            array(
                $detailId, $description, $invoiceReceiptNo, $actualAmount, $documentDate, $expenseCategory, $isVatable,
                $netAmount, $vatAmount, $vendorName, $vendorAddress, $vendorTin, $approvedGrossAmount,
            )
        );
    }

    public function update_draft_header($liquidationId, $userId, $totalAmountSpent, $refundAmount, $reimburseAmount, $statusCode)
    {
        $this->execute(
            "UPDATE dbo.tbl_liquidation_header
            SET
                total_amount_spent = @TotalAmountSpent,
                refund_amount = @RefundAmount,
                reimburse_amount = @ReimburseAmount,
                status = @StatusCode,
                submitted_date = CASE WHEN @StatusCode = 'LQ_SUBMITTED' THEN GETDATE() ELSE NULL END,
                updated_date = GETDATE(),
                updated_by = @UserId
            WHERE liquidation_id = @LiquidationId
                AND created_by = @UserId
                AND status = 'LQ_DRAFT'",
            '@LiquidationId VARCHAR(50), @UserId INT, @TotalAmountSpent DECIMAL(18, 2),
            @RefundAmount DECIMAL(18, 2), @ReimburseAmount DECIMAL(18, 2), @StatusCode VARCHAR(20)',
            array($liquidationId, $userId, $totalAmountSpent, $refundAmount, $reimburseAmount, $statusCode)
        );
    }

    public function update_submitted_header($liquidationId, $userId, $totalAmountSpent, $refundAmount, $reimburseAmount, $statusCode, $expenseRangeFrom = null, $expenseRangeTo = null)
    {
        $this->execute(
            "UPDATE tbl_liquidation_header
            SET
                total_amount_spent = @TotalAmountSpent,
                refund_amount = @RefundAmount,
                reimburse_amount = @ReimburseAmount,
                status = @StatusCode,
                updated_by = @UserId,
                updated_date = GETDATE()
            WHERE liquidation_id = @LiquidationId
                AND status IN ('LQ_SUBMITTED', 'LQ_REJECTED')",
            '@LiquidationId VARCHAR(20), @UserId INT, @TotalAmountSpent DECIMAL(18, 2), @RefundAmount DECIMAL(18, 2),
            @ReimburseAmount DECIMAL(18, 2), @StatusCode VARCHAR(20), @ExpenseRangeFrom DATE, @ExpenseRangeTo DATE',
            array($liquidationId, $userId, $totalAmountSpent, $refundAmount, $reimburseAmount, $statusCode, $expenseRangeFrom, $expenseRangeTo)
        );
    }

    public function delete_details($liquidationId)
    {
        $this->query_rows(
            'DELETE FROM dbo.tbl_liquidation_details WHERE liquidation_id = CAST(? AS VARCHAR(50))',
            array($liquidationId)
        );
    }

    public function delete_details_except($liquidationId, $keepItemIds = '')
    {
        $keep = Knet_functions::split_string($keepItemIds, ',');

        $binds = array($liquidationId);
        $keepSql = '';
        if (!empty($keep)) {
            $keepSql = ' AND id NOT IN (' . implode(', ', array_fill(0, count($keep), 'CAST(? AS INT)')) . ')';
            $binds = array_merge($binds, $keep);
        }

        $this->query_rows(
            'DELETE FROM tbl_liquidation_details WHERE liquidation_id = CAST(? AS VARCHAR(20))' . $keepSql,
            $binds
        );
    }

    private function execute($statement, $declarations, array $binds)
    {
        return $this->query_rows(
            'EXEC sp_executesql N' . $this->db->escape($statement) . ', N' . $this->db->escape($declarations)
                . str_repeat(', ?', count($binds)),
            array_values($binds)
        );
    }
}
