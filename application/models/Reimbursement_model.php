<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once APPPATH . 'core/MY_Model.php';

class Reimbursement_model extends MY_Model
{
    public function list_approvals($reimbursementId)
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
                SELECT id FROM tbl_reimbursement_details
                WHERE reimbursement_id = CAST(? AS VARCHAR(20))
            )
            AND A.is_active = 1
            ORDER BY A.line_item_id, A.created_date",
            array($this->varchar($reimbursementId, 20))
        );
    }

    public function list_details($reimbursementId)
    {
        $id = $this->varchar($reimbursementId, 20);
        return $this->query_rows(
            "SELECT
                id, reimbursement_id, description, invoice_receipt_no,
                actual_amount, approved_amount, document_date,
                expense_category, category_name,
                is_vatable, net_amount, vat_amount,
                vendor_name, vendor_address, vendor_tin,
                attachment, updated_date
            FROM " . Knet_views::sub('vw_rmb_details', 'V') . "
            WHERE reimbursement_id = CAST(? AS VARCHAR(20))
                AND category = (
                    SELECT CAST(C.category AS VARCHAR(20))
                    FROM tbl_reimbursement_header A
                    INNER JOIN BigEUsers.dbo.users B ON A.created_by = B.id
                    INNER JOIN BigEHRIS.dbo.department C ON B.department_id = C.department_id
                    WHERE A.reimbursement_id = CAST(? AS VARCHAR(20))
                )
            ORDER BY id",
            array($id, $id)
        );
    }

    public function find_header($reimbursementId)
    {
        return $this->query_row(
            "SELECT
                id, reimbursement_id, user_id, user_name,
                total_amount, status_code, status_name, description,
                cost_center_id, cost_center_name, payable_to, payable_to_user_id, address, io_number,
                created_by AS created_by_id, created_date,
                updated_by AS updated_by_id, updated_date,
                created_date AS submitted_date,
                filed_by, filed_by_name
            FROM " . Knet_views::sub('vw_rmb_header', 'V') . "
            WHERE reimbursement_id = CAST(? AS VARCHAR(20))",
            array($this->varchar($reimbursementId, 20))
        );
    }

    public function list_by_user($userId, $cursorId = null, $take = 20)
    {
        if ($take === null || $take === '' || (int) $take < 1) {
            $take = 20;
        }
        $take = (int) $take;
        if ($take > 100) {
            $take = 100;
        }
        $binds = array($userId);
        $cursorSql = '';
        if ($cursorId !== null) {
            $cursorSql = ' AND V.id < CAST(? AS BIGINT)';
            $binds[] = (string) $cursorId;
        }
        return $this->query_rows(
            "SELECT TOP (" . ($take + 1) . ")
                V.id, V.reimbursement_id, V.user_id, V.user_name,
                V.total_amount,
                (
                    SELECT SUM(RD.approved_amount)
                    FROM tbl_reimbursement_details RD
                    WHERE RD.reimbursement_id = V.reimbursement_id
                ) AS approved_amount,
                CASE WHEN EXISTS (
                    SELECT 1 FROM tbl_approval_header AH
                    INNER JOIN tbl_approval_details AD ON AD.approval_header_id = AH.id
                    WHERE AH.reference_id = V.reimbursement_id AND AH.is_active = 1 AND AD.is_active = 1 AND AD.status = 'APPROVED'
                ) THEN 1 ELSE 0 END AS has_approval,
                V.description,
                V.created_date AS submitted_date,
                V.created_date, V.created_by AS created_by_id, V.updated_date, V.updated_by,
                V.status_code, V.status_name,
                V.cost_center_id, V.cost_center_name, V.payable_to, V.address, V.io_number
            FROM " . Knet_views::sub('vw_rmb_header', 'V') . "
            WHERE V.created_by = CAST(? AS INT)" . $cursorSql . "
            ORDER BY V.id DESC",
            $binds
        );
    }

    public function get_petty_cash_data($reimbursementId)
    {
        return $this->query_row(
            "SELECT
                H.reimbursement_id,
                H.user_name AS requester_name,
                E.short_name AS department_name,
                ISNULL(
                    (
                        SELECT SUM(ISNULL(RD.approved_amount, RD.actual_amount))
                        FROM dbo.tbl_reimbursement_details RD
                        WHERE RD.reimbursement_id = H.reimbursement_id
                    ),
                    H.total_amount
                ) AS total_amount,
                H.description AS purpose,
                H.created_date
            FROM " . Knet_views::sub('vw_rmb_header', 'H') . "
            INNER JOIN BigEUsers.dbo.users D ON H.created_by = D.id
            LEFT JOIN BigEHRIS.dbo.department E ON D.department_id = E.department_id
            WHERE H.reimbursement_id = CAST(? AS VARCHAR(20))",
            array($this->varchar($reimbursementId, 20))
        );
    }

    public function list_claimable_paid($supervisorUserId)
    {
        $deptId = $this->department_of($supervisorUserId);
        return $this->query_rows(
            "SELECT
                H.reimbursement_id,
                Team.salesman,
                H.total_amount,
                H.description,
                H.created_date,
                H.updated_date
            FROM " . Knet_views::sub('vw_rmb_header', 'H') . "
            INNER JOIN (" . $this->team_sql() . ") Team ON H.created_by = Team.salesman_user_id
            WHERE H.status_code = 'RMB_PAID'
                AND NOT EXISTS (
                    SELECT 1
                    FROM dbo.tbl_replenishment_details RD
                    INNER JOIN dbo.tbl_replenishment_header RH ON RD.replenishment_id = RH.replenishment_id
                    WHERE RD.reimbursement_id = H.reimbursement_id
                        AND RH.status_code <> 'RPL_REJECTED'
                )
            ORDER BY H.created_date DESC",
            array($deptId, $deptId, $supervisorUserId)
        );
    }

    public function list_supervisor_team($supervisorUserId)
    {
        $deptId = $this->department_of($supervisorUserId);
        return $this->query_rows(
            "SELECT X.id AS member_user_id, X.name AS member_name, X.designation
            FROM BigEUsers.dbo.users A
            INNER JOIN BigEUsers.dbo.user_role_area B ON A.role_id = B.role_id
            INNER JOIN (
                SELECT A.id, A.role_id, B.area_id, A.lastname + ',' + A.firstname AS name, A.designation
                FROM BigEUsers.dbo.users A
                INNER JOIN BigEUsers.dbo.user_role_area B ON A.role_id = B.role_id
                WHERE A.department_id = CAST(? AS INT)
                    AND A.is_active = 1
                    AND A.id <> CAST(? AS INT)
            ) X ON B.area_id = X.area_id
            WHERE A.department_id = CAST(? AS INT)
                AND A.designation = 'Supervisor'
                AND A.is_active = 1
                AND A.id = CAST(? AS INT)",
            array($deptId, $supervisorUserId, $deptId, $supervisorUserId)
        );
    }

    public function list_team_details($supervisorUserId, $dateFrom, $dateTo)
    {
        $dates = $this->query_row(
            'SELECT CONVERT(CHAR(10), TRY_CONVERT(DATE, ?), 23) AS f, CONVERT(CHAR(10), TRY_CONVERT(DATE, ?), 23) AS t',
            array($dateFrom, $dateTo)
        );
        if (($dateFrom !== null && $dates['f'] === null) || ($dateTo !== null && $dates['t'] === null)) {
            $this->fail('Error converting data type varchar to date.', 8114);
        }
        $dateFrom = $dates['f'];
        $dateTo = $dates['t'];
        $deptId = $this->department_of($supervisorUserId);
        $category = null;
        if ($deptId !== null) {
            $row = $this->query_row(
                'SELECT CAST(category AS VARCHAR(20)) AS category FROM BigEHRIS.dbo.department WHERE department_id = CAST(? AS INT)',
                array($deptId)
            );
            $category = $row === null ? null : $row['category'];
        }
        return $this->query_rows(
            "SELECT
                H.reimbursement_id,
                Team.salesman,
                H.cost_center_id,
                H.cost_center_name,
                H.payable_to,
                H.status_code,
                H.status_name,
                H.filed_by,
                H.filed_by_name,
                H.created_date,
                H.total_amount AS header_total_amount,
                D.id AS detail_id,
                D.expense_category,
                D.category_name,
                D.description,
                D.invoice_receipt_no,
                D.document_date,
                D.actual_amount,
                D.approved_amount,
                D.is_vatable,
                D.net_amount,
                D.vat_amount,
                D.vendor_name,
                D.vendor_address,
                D.vendor_tin,
                D.attachment
            FROM " . Knet_views::sub('vw_rmb_header', 'H') . "
            INNER JOIN (" . $this->team_sql() . ") Team ON H.created_by = Team.salesman_user_id
            INNER JOIN " . Knet_views::sub('vw_rmb_details', 'D') . "
                ON D.reimbursement_id = H.reimbursement_id
                AND D.category = CAST(? AS VARCHAR(20))
            WHERE H.status_code <> 'RMB_DRAFT'
                AND CAST(H.created_date AS DATE) BETWEEN CAST(? AS DATE) AND CAST(? AS DATE)
            ORDER BY H.created_date DESC, H.reimbursement_id, D.id",
            array($deptId, $deptId, $supervisorUserId, $category, $dateFrom, $dateTo)
        );
    }

    public function update_detail_review($detailId, $description, $invoiceReceiptNo, $actualAmount, $documentDate, $expenseCategory, $isVatable, $netAmount, $vatAmount, $vendorName = null, $vendorAddress = null, $vendorTin = null, $approvedGrossAmount = null)
    {
        $this->assert_convertible(array(
            array($detailId, 'BIGINT', 'bigint'),
            array($actualAmount, 'DECIMAL(18, 2)', 'decimal'),
            array($documentDate, 'DATETIME', 'datetime'),
            array($isVatable, 'BIT', 'bit'),
            array($netAmount, 'DECIMAL(18, 2)', 'decimal'),
            array($vatAmount, 'DECIMAL(18, 2)', 'decimal'),
            array($approvedGrossAmount, 'DECIMAL(18, 2)', 'decimal'),
        ));

        $this->query_rows(
            "UPDATE dbo.tbl_reimbursement_details
            SET
                description = CAST(? AS NVARCHAR(MAX)),
                invoice_receipt_no = NULLIF(LTRIM(RTRIM(CAST(? AS VARCHAR(100)))), ''),
                actual_amount = CAST(? AS DECIMAL(18, 2)),
                approved_amount = ISNULL(CAST(? AS DECIMAL(18, 2)), CAST(? AS DECIMAL(18, 2))),
                document_date = CAST(? AS DATETIME),
                updated_date = GETDATE(),
                expense_category = CAST(? AS VARCHAR(20)),
                is_vatable = CAST(? AS BIT),
                net_amount = CAST(? AS DECIMAL(18, 2)),
                vat_amount = CAST(? AS DECIMAL(18, 2)),
                vendor_name = NULLIF(LTRIM(RTRIM(CAST(? AS NVARCHAR(255)))), ''),
                vendor_address = NULLIF(LTRIM(RTRIM(CAST(? AS NVARCHAR(500)))), ''),
                vendor_tin = NULLIF(LTRIM(RTRIM(CAST(? AS NVARCHAR(50)))), '')
            WHERE id = CAST(? AS BIGINT)",
            array($description, $invoiceReceiptNo, $actualAmount, $approvedGrossAmount, $actualAmount, $documentDate, $expenseCategory, $isVatable, $netAmount, $vatAmount, $vendorName, $vendorAddress, $vendorTin, $detailId)
        );
    }

    public function update_draft_header($reimbursementId, $userId, $totalAmount, $statusCode, $description, $costCenterId = null, $payableTo = null, $address = null, $ioNumber = null)
    {
        $this->update_header("status_code = 'RMB_DRAFT'", $reimbursementId, $userId, $totalAmount, $statusCode, $description, $costCenterId, $payableTo, $address, $ioNumber);
    }

    public function update_submitted_header($reimbursementId, $userId, $totalAmount, $statusCode, $description, $costCenterId = null, $payableTo = null, $address = null, $ioNumber = null)
    {
        $this->update_header("status_code IN ('RMB_SUBMITTED', 'RMB_REJECTED')", $reimbursementId, $userId, $totalAmount, $statusCode, $description, $costCenterId, $payableTo, $address, $ioNumber);
    }

    public function update_header_fields($reimbursementId, $costCenterId, $address, $updatedBy, $ioNumber = null)
    {
        $this->assert_convertible(array(array($updatedBy, 'BIGINT', 'bigint')));

        $this->query_rows(
            "UPDATE H
            SET H.cost_center_id = NULLIF(LTRIM(RTRIM(CAST(? AS VARCHAR(20)))), ''),
                H.payable_to = ISNULL(U.lastname + ', ' + U.firstname, H.payable_to),
                H.address = NULLIF(LTRIM(RTRIM(CAST(? AS NVARCHAR(500)))), ''),
                H.io_number = NULLIF(LTRIM(RTRIM(CAST(? AS VARCHAR(50)))), ''),
                H.updated_by = CAST(? AS BIGINT),
                H.updated_date = GETDATE()
            FROM dbo.tbl_reimbursement_header H
            LEFT JOIN BigEUsers.dbo.users U ON U.id = H.user_id
            WHERE H.reimbursement_id = CAST(? AS VARCHAR(20))",
            array($costCenterId, $address, $ioNumber, $updatedBy, $reimbursementId)
        );
    }

    public function delete_details($reimbursementId)
    {
        $this->query_rows(
            'DELETE FROM dbo.tbl_reimbursement_details WHERE reimbursement_id = CAST(? AS VARCHAR(20))',
            array($reimbursementId)
        );
    }

    public function delete_details_except($reimbursementId, $keepItemIds = '')
    {
        if ($keepItemIds === '') {
            $this->delete_details($reimbursementId);
            return;
        }

        $keep = Knet_functions::split_string($keepItemIds, ',');
        $sql = 'DELETE FROM dbo.tbl_reimbursement_details WHERE reimbursement_id = CAST(? AS VARCHAR(20))';
        $binds = array($reimbursementId);
        if (!empty($keep)) {
            $sql .= ' AND id NOT IN (' . implode(', ', array_fill(0, count($keep), 'CAST(CAST(? AS NVARCHAR(4000)) AS INT)')) . ')';
            $binds = array_merge($binds, $keep);
        }
        $this->query_rows(
            'IF EXISTS (SELECT 1 FROM dbo.tbl_reimbursement_details WHERE reimbursement_id = CAST(? AS VARCHAR(20))) ' . $sql,
            array_merge(array($reimbursementId), $binds)
        );
    }

    private function update_header($statusFilter, $reimbursementId, $userId, $totalAmount, $statusCode, $description, $costCenterId, $payableTo, $address, $ioNumber)
    {
        $this->assert_convertible(array(
            array($userId, 'BIGINT', 'bigint'),
            array($totalAmount, 'DECIMAL(18, 2)', 'decimal'),
        ));

        $this->query_rows(
            'UPDATE dbo.tbl_reimbursement_header
            SET total_amount = CAST(? AS DECIMAL(18, 2)),
                status_code = CAST(? AS VARCHAR(20)),
                description = CAST(? AS NVARCHAR(500)),
                cost_center_id = CAST(? AS VARCHAR(20)),
                payable_to = CAST(? AS NVARCHAR(255)),
                address = CAST(? AS NVARCHAR(500)),
                io_number = CAST(? AS VARCHAR(50)),
                updated_by = CAST(? AS BIGINT),
                updated_date = GETDATE()
            WHERE reimbursement_id = CAST(? AS VARCHAR(20))
              AND ' . $statusFilter,
            array($totalAmount, $statusCode, $description, $costCenterId, $payableTo, $address, $ioNumber, $userId, $reimbursementId)
        );
    }

    private function department_of($userId)
    {
        $rows = $this->query_rows(
            'SELECT department_id FROM BigEUsers.dbo.users WHERE id = CAST(? AS INT)',
            array($userId)
        );
        $last = empty($rows) ? null : $rows[count($rows) - 1];
        return $last === null ? null : $last['department_id'];
    }

    private function team_sql()
    {
        return "SELECT DISTINCT X.id AS salesman_user_id, X.name AS salesman
            FROM BigEUsers.dbo.users A
            INNER JOIN BigEUsers.dbo.user_role_area B ON A.role_id = B.role_id
            INNER JOIN (
                SELECT A.id, A.role_id, B.area_id, A.lastname + ',' + A.firstname AS name
                FROM BigEUsers.dbo.users A
                INNER JOIN BigEUsers.dbo.user_role_area B ON A.role_id = B.role_id
                WHERE A.department_id = CAST(? AS INT)
                    AND A.designation = 'Salesman'
                    AND A.is_active = 1
            ) X ON B.area_id = X.area_id
            WHERE A.department_id = CAST(? AS INT)
                AND A.designation = 'Supervisor'
                AND A.is_active = 1
                AND A.id = CAST(? AS INT)";
    }

    private function assert_convertible(array $checks)
    {
        $selects = array();
        $binds = array();
        $labels = array();
        foreach ($checks as $check) {
            list($value, $sqlType, $typeName) = $check;
            if (is_string($value)) {
                $source = 'varchar';
            } elseif (is_float($value)) {
                $source = stripos((string) $value, 'E') !== false ? 'float' : 'decimal';
            } else {
                continue;
            }
            $selects[] = 'CASE WHEN TRY_CONVERT(' . $sqlType . ', ?) IS NULL THEN 1 ELSE 0 END AS c' . count($selects);
            $binds[] = $value;
            $labels[] = 'Error converting data type ' . $source . ' to ' . $typeName . '.';
        }
        if (empty($selects)) {
            return;
        }
        $row = $this->query_row('SELECT ' . implode(', ', $selects), $binds);
        foreach ($labels as $i => $label) {
            if ((int) $row['c' . $i] === 1) {
                $this->fail($label, 8114);
            }
        }
    }

    private function varchar($value, $length)
    {
        if ($value === null) {
            return null;
        }
        return mb_substr((string) $value, 0, $length, 'UTF-8');
    }
}
