<?php
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}
// if (!function_exists('is_distri')) {
//     function is_distri()
//     {
//         $ci = &get_instance();
//         $get_char = substr($ci->session->userdata('user_info')['user_type_code'], 0, 6);
//         return (strtolower($get_char) == 'distri') ? 1 : 0;
//         // return $get_char;
//     }
// }
if (!function_exists('is_distri_super_admin')) {
    function is_distri_super_admin()
    {
        $ci = &get_instance();
        $user_type_name = $ci->session->userdata('user_info')['user_type_code'];
        return ($user_type_name == 'distri_super_admin') ? 1 : 0;
    }
}
if (!function_exists('is_distri_logistics_checker')) {
    function is_distri_logistics_checker()
    {
        $ci = &get_instance();
        $user_type_name = $ci->session->userdata('user_info')['user_type_code'];
        return ($user_type_name == 'distri_logistic_checker') ? 1 : 0;
    }
}
if (!function_exists('get_sales_area')) {
    function get_sales_area($user_role_id = null)
    {
        $ci = &get_instance();
        $ci->load->model('User_Roles/UserRoleModel', 'user_roles');
        $sales_area = $ci->user_roles->get_selected_sales_area($user_role_id);
        $count = count($sales_area);
        $string_sales_area = '';
        for ($i = 0; $i < $count; $i++) {
            if ($i == $count - 1) {
                $string_sales_area .= $sales_area[$i]['sales_district'];
            } else {
                $string_sales_area .= $sales_area[$i]['sales_district'] . ',';
            }
        }
        return $string_sales_area;
    }
}
if (!function_exists('get_user_fullname')) {
    function get_user_fullname($user_id = null)
    {
        $ci = &get_instance();
        $ci->load->model('Users/UserModel', 'users');
        $fullname = '--';
        if (!is_null($user_id)) {
            $user = $ci->users->fetch($user_id);
            if($user) {
                $fullname = $user['lastname'] . ', ' . $user['firstname'];
            }
        }
        return $fullname;
    }
}
if (!function_exists('fetch_sales_district')) {
    function fetch_sales_district($user_role_id = null, $is_array = false)
    {
        $ci = &get_instance();
        $ci->load->model('User_Roles/UserRoleModel', 'user_roles');
        $sales_area = $ci->user_roles->get_selected_sales_area($user_role_id);
        $count = count($sales_area);
        $string_sales_area = '';
        $sales_area_array = array();
        for ($i = 0; $i < $count; $i++) {
            if ($i == $count - 1) {
                $string_sales_area .= $sales_area[$i]['sales_district'];
            } else {
                $string_sales_area .= $sales_area[$i]['sales_district'] . ',';
            }
            $sales_area_array[] = $sales_area[$i]['sales_district'];
        }
        return ($is_array) ? $sales_area_array : $string_sales_area;
    }
}
if (!function_exists('build_post_data')) {
    function build_post_data($post_data, $exclude = array(), $include_metadata = false)
    {
        $ci = &get_instance();
        $data = array();
        foreach ($post_data as $val) {
            if (!in_array($val['name'], $exclude)) {
                $data[$val['name']] = (!is_numeric($val['value'])) ? strtoupper($val['value']) : $val['value'];
            } else {
                $data[$val['name']] = $val['value'];
            }
            if ($include_metadata) {
                $data['created_by'] = $ci->session->userdata('user_info')['user_id'];
                $data['updated_by'] = $ci->session->userdata('user_info')['user_id'];
                $data['updated_date'] = date('Y-m-d H:i:s');
            }
        }
        return $data;
    }
}
if (!function_exists('row_status')) {
    function row_status($status)
    {
        return ($status == 1) ? '<span class="badge badge-success">ACTIVE</span>' : '<span class="badge badge-warning">INACTIVE</span>';
    }
}
if (!function_exists('compress_image')) {
    function compress_image($source_url, $destination_url, $quality)
    {
        $info = getimagesize($source_url);
        if ($info['mime'] == 'image/jpeg') {
            $image = imagecreatefromjpeg($source_url);
        }
        if ($info['mime'] == 'image/gif') {
            $image = imagecreatefromgif($source_url);
        }
        if ($info['mime'] == 'image/png') {
            $image = imagecreatefrompng($source_url);
        }
        imagejpeg($image, $destination_url, $quality);
    }
}
if (!function_exists('fetch_distributor_sales_district')) {
    function fetch_distributor_sales_district($operation_type)
    {
        $ci = &get_instance();
        $ci->load->model('QueryTransModel', 'query');
        $ci->query->setDatabase($ci->session->userdata('user_info')['database_name']);
        $params = array(
          'status' => 1,
          'operation_type' => strtoupper($operation_type)
        );
        $sales_district = $ci->query->get_where('sales_district', $params, 'all');
        return $sales_district;
    }
}
if (!function_exists('generate_random_number')) {
    function generate_random_number($digits)
    {
        return random_int(10 ** ($digits - 1), (10 ** $digits) - 1);
    }
}
if (!function_exists('get_operation_type')) {
    function get_operation_type($sales_district_code)
    {
        $ci = &get_instance();
        $ci->load->model('QueryTransModel', 'query');
        $ci->query->setDatabase($ci->session->userdata('user_info')['database_name']);
        $params = array(
          'sales_district' => $sales_district_code,
        );
        $operation_type = $ci->query->get_where('sales_district', $params, 'single');
        return (!empty($operation_type['operation_type'])) ? $operation_type['operation_type'] : '';
    }
}
if (!function_exists('human_date')) {
    function human_date($date, $is_date_only = false)
    {
        return (!$is_date_only) ? date('Y-m-d h:i A', strtotime($date)) :
          date('Y-m-d', strtotime($date));
    }
}
if (!function_exists('expense_status')) {
    function expense_status($status)
    {
        $btn_class = '';
        $status = strtoupper($status);
        if ($status == 'NEW') {
            $btn_class = 'btn-warning';
        }
        if ($status == 'APP1') {
            $btn_class = 'btn-secondary';
        }
        if ($status == 'APP2') {
            $btn_class = 'btn-secondary';
        }

               if ($status == 'APP3') {
            $btn_class = 'btn-secondary';
        }
        if ($status == 'REJECTED') {
            $btn_class = 'btn-danger';
        }
        if ($status == 'APPROVED') {
            $btn_class = 'btn-success';
        }
        return '<span class="badge ' . $btn_class . '">' . $status . '</span>';
    }

}
if (!function_exists('get_user_info')) {
    function get_user_info($id)
    {
        $ci = &get_instance();
        $ci->load->model('Lookup_model');
        $result = $ci->Lookup_model->get_user_info($id);

     return $result;

    }
}