<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

return array(
    array('sp' => 'sp_fetch_department', 'params' => array(), 'model' => 'Lookup_model', 'method' => 'get_departments'),
    array('sp' => 'sp_fetch_user_info', 'params' => array(43), 'model' => 'Lookup_model', 'method' => 'get_user_info', 'returns' => 'row'),
    array('sp' => 'sp_fetch_user_info', 'params' => array(32063), 'model' => 'Lookup_model', 'method' => 'get_user_info', 'returns' => 'row'),
    array('sp' => 'sp_fetch_user_info', 'params' => array(999999), 'model' => 'Lookup_model', 'method' => 'get_user_info', 'returns' => 'row'),
    array('sp' => 'sp_fetch_payable_to_users', 'params' => array(29), 'model' => 'Lookup_model', 'method' => 'get_payable_to_users'),
    array('sp' => 'sp_fetch_payable_to_users', 'params' => array(34), 'model' => 'Lookup_model', 'method' => 'get_payable_to_users'),
    array('sp' => 'sp_fetch_sales_offices', 'params' => array(), 'model' => 'Lookup_model', 'method' => 'get_sales_offices'),
    array('sp' => 'sp_fetch_sales_districts_by_office', 'params' => array('011'), 'model' => 'Lookup_model', 'method' => 'get_sales_districts'),
    array('sp' => 'sp_fetch_sales_districts_by_office', 'params' => array('012'), 'model' => 'Lookup_model', 'method' => 'get_sales_districts'),
    array('sp' => 'sp_fetch_active_cost_centers', 'params' => array(), 'model' => 'Lookup_model', 'method' => 'get_cost_centers'),
    array('sp' => 'sp_fetch_cost_center_by_id', 'params' => array('103031001'), 'model' => 'Lookup_model', 'method' => 'get_cost_center', 'returns' => 'row'),
    array('sp' => 'sp_fetch_cost_center_by_id', 'params' => array('does-not-exist'), 'model' => 'Lookup_model', 'method' => 'get_cost_center', 'returns' => 'row'),
    array('sp' => 'sp_fetch_cost_center_by_deptid', 'params' => array(29), 'model' => 'Lookup_model', 'method' => 'get_cost_centers_by_department'),
    array('sp' => 'sp_fetch_cost_center_by_deptid', 'params' => array(34), 'model' => 'Lookup_model', 'method' => 'get_cost_centers_by_department'),
    array('sp' => 'sp_fetch_cost_center_by_deptid', 'params' => array(999999), 'model' => 'Lookup_model', 'method' => 'get_cost_centers_by_department'),
    array('sp' => 'sp_fetch_expense_types', 'params' => array(), 'model' => 'Lookup_model', 'method' => 'get_expense_types'),
    array('sp' => 'sp_fetch_expense_types_by_department', 'params' => array(29), 'model' => 'Lookup_model', 'method' => 'get_expense_types_by_department'),
    array('sp' => 'sp_fetch_expense_types_by_department', 'params' => array(34), 'model' => 'Lookup_model', 'method' => 'get_expense_types_by_department'),
    array('sp' => 'sp_fetch_expense_types_by_department', 'params' => array(999999), 'model' => 'Lookup_model', 'method' => 'get_expense_types_by_department'),
);
