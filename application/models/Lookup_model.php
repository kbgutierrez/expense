<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once APPPATH . 'core/MY_Model.php';

class Lookup_model extends MY_Model
{
    public function get_departments()
    {
        $this->db->select('A.department_id, A.department_acro, A.department_decode, A.short_name AS department', false)
            ->from('BigEHRIS.dbo.department A')
            ->join('BigEUsers.dbo.users B', 'A.created_by = B.id', 'inner')
            ->where_not_in('A.department_id', array(31, 32, 81))
            ->order_by('A.department_decode', 'ASC');
        return $this->rows();
    }

    public function get_user_info($userId)
    {
        $this->db->select('A.id, A.email, A.firstname, A.lastname, A.designation, B.department_id, B.short_name', false)
            ->from('BigEUsers.dbo.users A')
            ->join('BigEHRIS.dbo.department B', 'A.department_id = B.department_id', 'inner')
            ->where('A.id', $userId);
        return $this->row();
    }

    public function get_payable_to_users($departmentId)
    {
        $this->db->select('id, firstname, lastname, designation', false)
            ->from('BigEUsers.dbo.users')
            ->where('department_id', $departmentId)
            ->where('is_active', 1)
            ->where('ISNULL(is_test, 0) = 0', null, false)
            ->order_by('firstname', 'ASC');
        return $this->rows();
    }

    public function get_sales_offices()
    {
        $this->db->distinct()
            ->select('SOffcCode, SoffcNm', false)
            ->from('BigEMasterData.dbo.tbl_SOffc')
            ->where("UPPER(SoffcNm) NOT LIKE '%DISSOLVED%'", null, false)
            ->order_by('SoffcNm');
        return $this->rows();
    }

    public function get_sales_districts($officeCode)
    {
        $this->db->select('SDstCode, SDstNm', false)
            ->from('BigEMasterData.dbo.tbl_SDst')
            ->where('SOffcCode', $officeCode)
            ->where('OpType <>', 'Dissolved')
            ->order_by('SDstNm');
        return $this->rows();
    }

    public function get_cost_centers()
    {
        $this->db->select('*', false)->from('tbl_cost_center');
        return $this->rows();
    }

    public function get_cost_center($costCenterCode)
    {
        $this->db->select('id, cost_center_code, cost_center_name, is_active, created_by, created_date, updated_by, updated_date', false)
            ->from('tbl_cost_center')
            ->where('cost_center_code', $costCenterCode);
        return $this->row();
    }

    public function get_cost_centers_by_department($departmentId)
    {
        $this->db->select('A.*', false)
            ->from('tbl_cost_center A')
            ->where('A.category = (SELECT category FROM BigEHRIS.dbo.department WHERE department_id = ' . (int) $departmentId . ')', null, false);
        return $this->rows();
    }

    public function get_expense_types()
    {
        $this->db->select('id, expense_code, description, long_text, short_text, category', false)
            ->from('tbl_expense_category')
            ->where('status', 'CAT_ACTIVE');
        return $this->rows();
    }

    public function get_expense_types_by_department($departmentId)
    {
        $this->db->select('id, expense_code, description, long_text, short_text, category', false)
            ->from('tbl_expense_category')
            ->where('status', 'CAT_ACTIVE')
            ->where('category = (SELECT category FROM BigEHRIS.dbo.department WHERE department_id = ' . (int) $departmentId . ')', null, false);
        return $this->rows();
    }
}
