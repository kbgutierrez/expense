<?php

defined('BASEPATH') or exit('No direct script access allowed');
class MainController extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('SPModel', 'sp');
        $this->sp->setDatabase('dbknet');
    }
    public function index()
    {
      
        if ($this->session->userdata('user_info')) {

       
      
            $data = array(
                'title' => config_item('app_name'),
                'main_view' => '../modules/dashboard/views/dashboard',
                'module_group' => $this->module_group,
                'module' => $this->module,
                'company_id' => $this->session->userdata('user_info')['company']

            );
   
        $this->load->view('main', $data);
        
        } else {
            redirect(config_item('portal_url'));
        }
    }
   


   

}
