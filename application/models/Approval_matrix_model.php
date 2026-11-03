<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once APPPATH . 'core/MY_Model.php';

class Approval_matrix_model extends MY_Model
{
    public function list_approvals_by_reference($referenceId)
    {
        $this->db->select("ad.id AS approval_details_id, ad.approval_order, ad.approver_id, u.lastname + ', ' + u.firstname AS approver_name, d.short_name AS approver_dept, ad.is_active, ad.approval_date AS created_date, api.status AS decision_status, api.remarks AS decision_remarks, api.updated_at AS decision_date, api.is_notify", false)
            ->from('tbl_approval_header ah')
            ->join('tbl_approval_details ad', 'ah.id = ad.approval_header_id', 'inner')
            ->join('BigEUsers.dbo.users u', 'ad.approver_id = u.id', 'inner')
            ->join('BigEHRIS.dbo.department d', 'u.department_id = d.department_id', 'inner')
            ->join('tbl_approval_per_items api', 'ad.id = api.approval_details_id AND api.is_active = 1', 'left', false)
            ->where('ah.reference_id = CAST(' . $this->db->escape($referenceId) . ' AS VARCHAR(20))', null, false)
            ->where('ah.is_active', 1)
            ->where('ad.is_active', 1)
            ->order_by('ad.approval_order', 'ASC');
        return $this->rows();
    }

    private function header_sql($updatedJoin, $where)
    {
        return "SELECT A.id, A.matrix_name, A.transaction_type, A.department_id, D.short_name AS department,"
            . " A.sales_office_code, SO.SoffcNm AS sales_office_name, A.sales_district_code, SD.SDstNm AS sales_district_name,"
            . " A.min_amount, A.max_amount, A.is_active, A.created_by, B.lastname + ',' + B.firstname AS created_by_name,"
            . " A.updated_by, C.lastname + ',' + C.firstname AS updated_by_name, A.created_date, A.updated_date"
            . " FROM dbo.tbl_approval_matrix_header A"
            . " INNER JOIN BigEUsers.dbo.users B ON A.created_by = B.id"
            . " LEFT JOIN BigEUsers.dbo.users C ON " . $updatedJoin
            . " INNER JOIN BigEHRIS.dbo.department D ON A.department_id = D.department_id"
            . " LEFT JOIN BigEMasterData.dbo.tbl_SOffc SO ON A.sales_office_code = SO.SOffcCode"
            . " LEFT JOIN BigEMasterData.dbo.tbl_SDst SD ON A.sales_district_code = SD.SDstCode"
            . " WHERE " . $where
            . " ORDER BY id DESC";
    }

    public function list_headers($cursorId = null, $take = null)
    {
        if ($take !== null && (int) $take >= 1) {
            return array();
        }

        return $this->query_rows(
            $this->header_sql('A.updated_by = C.id', '(CAST(? AS BIGINT) IS NULL OR A.id < CAST(? AS BIGINT))'),
            array($cursorId, $cursorId)
        );
    }

    public function get_header_rows($matrixId)
    {
        return $this->query_rows(
            $this->header_sql('A.updated_by = B.id', 'A.id = CAST(? AS INT)'),
            array($matrixId)
        );
    }

    public function list_details($matrixId)
    {
        $this->db->select('id, matrix_header_id, approver_id, approval_order, approval_type, is_payment_advisory, is_payment_release, is_petty_cash_slip, is_bizlink_export, created_date', false)
            ->from('tbl_approval_matrix_details')
            ->where('matrix_header_id = CAST(' . $this->db->escape($matrixId) . ' AS INT)', null, false)
            ->order_by('approval_order', 'ASC')
            ->order_by('id', 'ASC');
        return $this->rows();
    }

    public function list_approvers()
    {
        $exact = array('Accounting', 'NSM', 'DSM', 'Top Management');
        $keywords = array('Supervisor', 'Head', 'Manager', 'Mngr', 'Director', 'Chief', 'President');
        $match = array("A.designation IN ('" . implode("', '", $exact) . "')");
        foreach ($keywords as $keyword) {
            $match[] = "A.designation LIKE '%" . $keyword . "%'";
        }
        $rank = "CASE"
            . " WHEN A.designation = 'Top Management' OR A.designation LIKE '%President%' OR A.designation LIKE '%Chief%' THEN 11"
            . " WHEN A.designation LIKE '%Director%' THEN 10"
            . " WHEN A.designation LIKE '%General Manager%' THEN 9"
            . " WHEN A.designation LIKE '%Ass%t%Manager%' OR A.designation LIKE '%Asst%Mngr%' THEN 7"
            . " WHEN A.designation LIKE '%Manager%' OR A.designation LIKE '%Mngr%' THEN 8"
            . " WHEN A.designation = 'NSM' THEN 6"
            . " WHEN A.designation = 'DSM' THEN 5"
            . " WHEN A.designation LIKE '%Supervisor%' THEN 1"
            . " WHEN A.designation LIKE '%Head%' THEN 4"
            . " WHEN A.designation = 'Accounting' THEN 3"
            . " ELSE 99 END";

        $this->db->select('A.id, A.firstname, A.lastname, A.designation, A.department_id, B.short_name AS department', false)
            ->from('BigEUsers.dbo.users A')
            ->join('BigEHRIS.dbo.department B', 'A.department_id = B.department_id', 'inner')
            ->where('A.is_active', 1)
            ->where('ISNULL(A.is_test, 0) = 0', null, false)
            ->where('(' . implode(' OR ', $match) . ')', null, false)
            ->order_by($rank, 'ASC', false)
            ->order_by('B.short_name', 'ASC')
            ->order_by('A.lastname', 'ASC');
        return $this->rows();
    }

    private function overlaps($isUpdate, $excludeId, $isActive, $transactionType, $typeLength, $departmentId, $min, $max, $officeCode, $districtCode)
    {
        $row = $this->query_row(
            "SELECT CASE WHEN CAST(? AS BIT) = 1 AND EXISTS ("
            . " SELECT 1 FROM dbo.tbl_approval_matrix_header"
            . " WHERE (? = 0 OR id <> CAST(? AS BIGINT))"
            . " AND transaction_type = CAST(? AS VARCHAR(" . (int) $typeLength . "))"
            . " AND department_id = CAST(? AS INT)"
            . " AND is_active = 1"
            . " AND ISNULL(sales_office_code, '') = ISNULL(NULLIF(CAST(? AS VARCHAR(10)), ''), '')"
            . " AND ISNULL(sales_district_code, '') = ISNULL(NULLIF(CAST(? AS VARCHAR(10)), ''), '')"
            . " AND CAST(? AS DECIMAL(18,2)) <= max_amount"
            . " AND CAST(? AS DECIMAL(18,2)) >= min_amount"
            . ") THEN 1 ELSE 0 END AS overlaps",
            array($isActive, $isUpdate ? 1 : 0, $excludeId, $transactionType, $departmentId, $officeCode, $districtCode, $min, $max)
        );
        return (int) $row['overlaps'] === 1;
    }

    public function create_header($matrixName, $transactionType, $departmentId, $minAmount, $maxAmount, $createdBy, $salesOfficeCode = null, $salesDistrictCode = null)
    {
        $n = $this->query_row(
            "EXEC sp_executesql N'SELECT @matrix_name AS matrix_name, @transaction_type AS transaction_type, @department_id AS department_id, @min_amount AS min_amount, @max_amount AS max_amount, @created_by AS created_by, @sales_office_code AS sales_office_code, @sales_district_code AS sales_district_code',"
            . " N'@matrix_name VARCHAR(100), @transaction_type VARCHAR(30), @department_id INT, @min_amount DECIMAL(18,2), @max_amount DECIMAL(18,2), @created_by INT, @sales_office_code VARCHAR(10), @sales_district_code VARCHAR(10)', ?, ?, ?, ?, ?, ?, ?, ?",
            array($matrixName, $transactionType, $departmentId, $minAmount, $maxAmount, $createdBy, $salesOfficeCode, $salesDistrictCode)
        );

        if ($this->overlaps(false, null, 1, $n['transaction_type'], 30, $n['department_id'], $n['min_amount'], $n['max_amount'], $n['sales_office_code'], $n['sales_district_code'])) {
            $this->fail('This amount range overlaps with an existing active rule for the same department and area scope.', 50002);
        }

        $row = $this->query_row(
            "DECLARE @ids TABLE (id INT); SET NOCOUNT ON;"
            . " INSERT INTO dbo.tbl_approval_matrix_header (matrix_name, transaction_type, department_id, min_amount, max_amount, is_active, created_by, created_date, sales_office_code, sales_district_code)"
            . " OUTPUT inserted.id INTO @ids"
            . " VALUES (CAST(? AS VARCHAR(100)), CAST(? AS VARCHAR(30)), CAST(? AS INT), CAST(? AS DECIMAL(18,2)), CAST(? AS DECIMAL(18,2)), 1, CAST(? AS INT), GETDATE(), NULLIF(CAST(? AS VARCHAR(10)), ''), NULLIF(CAST(? AS VARCHAR(10)), ''));"
            . " SET NOCOUNT OFF; SELECT id FROM @ids;",
            array($n['matrix_name'], $n['transaction_type'], $n['department_id'], $n['min_amount'], $n['max_amount'], $n['created_by'], $n['sales_office_code'], $n['sales_district_code'])
        );
        return isset($row['id']) ? $row['id'] : null;
    }

    public function create_detail($matrixHeaderId, $approverId, $approvalOrder, $approvalType, $isPaymentAdvisory = 0, $isPaymentRelease = 0, $isPettyCashSlip = 0, $isBizlinkExport = 0)
    {
        $n = $this->query_row(
            "EXEC sp_executesql N'SELECT @matrix_header_id AS matrix_header_id, @approver_id AS approver_id, @approval_order AS approval_order, @approval_type AS approval_type, @is_payment_advisory AS is_payment_advisory, @is_payment_release AS is_payment_release, @is_petty_cash_slip AS is_petty_cash_slip, @is_bizlink_export AS is_bizlink_export',"
            . " N'@matrix_header_id INT, @approver_id INT, @approval_order INT, @approval_type VARCHAR(20), @is_payment_advisory BIT, @is_payment_release BIT, @is_petty_cash_slip BIT, @is_bizlink_export BIT', ?, ?, ?, ?, ?, ?, ?, ?",
            array($matrixHeaderId, $approverId, $approvalOrder, $approvalType, $isPaymentAdvisory, $isPaymentRelease, $isPettyCashSlip, $isBizlinkExport)
        );

        $this->query_rows(
            'INSERT INTO dbo.tbl_approval_matrix_details (matrix_header_id, approver_id, approval_order, approval_type, is_payment_advisory, is_payment_release, is_petty_cash_slip, is_bizlink_export, created_date)'
            . ' VALUES (CAST(? AS INT), CAST(? AS INT), CAST(? AS INT), CAST(? AS VARCHAR(20)), CAST(? AS BIT), CAST(? AS BIT), CAST(? AS BIT), CAST(? AS BIT), GETDATE())',
            array($n['matrix_header_id'], $n['approver_id'], $n['approval_order'], $n['approval_type'], $n['is_payment_advisory'], $n['is_payment_release'], $n['is_petty_cash_slip'], $n['is_bizlink_export'])
        );
    }

    public function update_header($id, $matrixName, $transactionType, $departmentId, $minAmount, $maxAmount, $isActive, $updatedBy, $salesOfficeCode = null, $salesDistrictCode = null)
    {
        $n = $this->query_row(
            "EXEC sp_executesql N'SELECT @id AS id, @matrix_name AS matrix_name, @transaction_type AS transaction_type, @department_id AS department_id, @min_amount AS min_amount, @max_amount AS max_amount, @is_active AS is_active, @updated_by AS updated_by, @sales_office_code AS sales_office_code, @sales_district_code AS sales_district_code',"
            . " N'@id BIGINT, @matrix_name VARCHAR(255), @transaction_type VARCHAR(50), @department_id INT, @min_amount DECIMAL(18,2), @max_amount DECIMAL(18,2), @is_active BIT, @updated_by BIGINT, @sales_office_code VARCHAR(10), @sales_district_code VARCHAR(10)', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?",
            array($id, $matrixName, $transactionType, $departmentId, $minAmount, $maxAmount, $isActive, $updatedBy, $salesOfficeCode, $salesDistrictCode)
        );

        if ($this->overlaps(true, $n['id'], $n['is_active'], $n['transaction_type'], 50, $n['department_id'], $n['min_amount'], $n['max_amount'], $n['sales_office_code'], $n['sales_district_code'])) {
            $this->fail('This amount range overlaps with an existing active rule for the same department and area scope.', 50002);
        }

        $this->query_rows(
            'UPDATE dbo.tbl_approval_matrix_header SET'
            . ' matrix_name = CAST(? AS VARCHAR(255)),'
            . ' transaction_type = CAST(? AS VARCHAR(50)),'
            . ' department_id = CAST(? AS INT),'
            . ' min_amount = CAST(? AS DECIMAL(18,2)),'
            . ' max_amount = CAST(? AS DECIMAL(18,2)),'
            . ' is_active = CAST(? AS BIT),'
            . ' updated_by = CAST(? AS BIGINT),'
            . ' updated_date = GETDATE(),'
            . " sales_office_code = NULLIF(CAST(? AS VARCHAR(10)), ''),"
            . " sales_district_code = NULLIF(CAST(? AS VARCHAR(10)), '')"
            . ' WHERE id = CAST(? AS BIGINT)',
            array($n['matrix_name'], $n['transaction_type'], $n['department_id'], $n['min_amount'], $n['max_amount'], $n['is_active'], $n['updated_by'], $n['sales_office_code'], $n['sales_district_code'], $n['id'])
        );
    }

    public function delete_details($matrixHeaderId)
    {
        $n = $this->query_row(
            "EXEC sp_executesql N'SELECT @matrix_header_id AS matrix_header_id', N'@matrix_header_id BIGINT', ?",
            array($matrixHeaderId)
        );

        $this->query_rows('DELETE FROM dbo.tbl_approval_matrix_details WHERE matrix_header_id = CAST(? AS BIGINT)', array($n['matrix_header_id']));
    }
}
