<?php

require 'vendor/autoload.php';
(defined('BASEPATH')) or exit('No direct script access allowed');

class Approval_Matrix extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('SPModel', 'sp');
        $this->sp->setDatabase('dbknet');
        $this->load->model('Approval_matrix_model');
        $this->load->model('Lookup_model');
    }

    public function index()
    {
        $data = array(
            'title' => 'Approval Matrix',
            'main_view' => '../modules/approval-matrix/views/index',
            'module_group' => $this->module_group,
            'module' => $this->module,
            'scripts' => array(
                'index.js',
            ),
        );

        $this->load->view('main', $data);
    }

    public function add()
    {
        $data = array(
            'title' => 'New Approval Matrix',
            'main_view' => '../modules/approval-matrix/views/add',
            'module_group' => $this->module_group,
            'module' => $this->module,
            'matrix_id' => 0,
            'is_edit_mode' => false,
            'scripts' => array(
                'add.js',
            ),
        );

        $this->load->view('main', $data);
    }

    public function edit($matrix_id = 0)
    {
        $id = (int) $matrix_id;
        if ($id <= 0) {
            redirect('maintenance/approval-matrix');
            return;
        }

        $data = array(
            'title' => 'Edit Approval Matrix',
            'main_view' => '../modules/approval-matrix/views/details',
            'module_group' => $this->module_group,
            'module' => $this->module,
            'matrix_id' => $id,
            'scripts' => array(
                'details.js',
            ),
        );

        $this->load->view('main', $data);
    }

    public function api_get_approvers(){
         try{
            $this->output->set_content_type('application/json');
            $result = $this->Approval_matrix_model->list_approvers();
    
            return $this->respondSuccess("success", $result);
        }catch(Exception $e){
            return $this->respondError("An error occurred: " . $e->getMessage());
        }


    }

    public function api_get_departments(){
        try{
            $this->output->set_content_type('application/json');
            $result = $this->Lookup_model->get_departments();

            return $this->respondSuccess("success", $result);
        }catch(Exception $e){
            return $this->respondError("An error occurred: " . $e->getMessage());
        }
    }

    public function api_get_sales_offices(){
        try{
            $this->output->set_content_type('application/json');
            $result = $this->Lookup_model->get_sales_offices();

            return $this->respondSuccess("success", $result);
        }catch(Exception $e){
            return $this->respondError("An error occurred: " . $e->getMessage());
        }
    }

    public function api_get_sales_districts(){
        try{
            $this->output->set_content_type('application/json');
            $sOffcCode = trim((string) $this->input->post('SOffcCode'));

            if ($sOffcCode === '') {
                return $this->respondError("SOffcCode is required");
            }

            $result = $this->Lookup_model->get_sales_districts($sOffcCode);

            return $this->respondSuccess("success", $result);
        }catch(Exception $e){
            return $this->respondError("An error occurred: " . $e->getMessage());
        }
    }

    public function api_save()
    {
        try{
            $this->output->set_content_type('application/json');
            $data = $this->getRequestPayload();
            try {
                $headerId = $this->Approval_matrix_model->create_header(
                    $data['matrix_name'],
                    $data['transaction_type'],
                    $data['department_id'],
                    $data['min_amount'],
                    $data['max_amount'],
                    $this->session->userdata('user_id'),
                    !empty($data['sales_office_code']) ? $data['sales_office_code'] : null,
                    !empty($data['sales_district_code']) ? $data['sales_district_code'] : null
                );
            } catch (Exception $e) {
                return $this->respondError($e->getMessage() !== '' ? $e->getMessage() : "Failed to save approval matrix");
            }
            if (empty($headerId)) {
                return $this->respondError("Failed to save approval matrix");
            }
            $details = isset($data['details']) && is_array($data['details']) ? $data['details'] : array();
            foreach($details as $detail){
                try {
                    $this->Approval_matrix_model->create_detail(
                        $headerId,
                        $detail['approver_id'],
                        $detail['approval_order'],
                        $detail['approval_type'],
                        !empty($detail['is_payment_advisory']) ? 1 : 0,
                        !empty($detail['is_payment_release']) ? 1 : 0,
                        !empty($detail['is_petty_cash_slip']) ? 1 : 0,
                        !empty($detail['is_bizlink_export']) ? 1 : 0
                    );
                } catch (Exception $e) {
                }
            }
            return $this->respondSuccess("Approval matrix saved successfully");

        }catch(Exception $e){
            return $this->respondError("An error occurred: " . $e->getMessage());
        }
    }
      public function api_update()
    {
        try {
            $this->output->set_content_type('application/json');
            $data = $this->getRequestPayload();

            $id = isset($data['id']) ? (int) $data['id'] : 0;
            if ($id <= 0) {
                return $this->respondError("Invalid matrix ID");
            }

            try {
                $this->Approval_matrix_model->update_header(
                    $id,
                    $data['matrix_name'],
                    $data['transaction_type'],
                    $data['department_id'],
                    $data['min_amount'],
                    $data['max_amount'],
                    isset($data['is_active']) ? (int) $data['is_active'] : 1,
                    $this->session->userdata('user_id'),
                    !empty($data['sales_office_code']) ? $data['sales_office_code'] : null,
                    !empty($data['sales_district_code']) ? $data['sales_district_code'] : null
                );
                $headerResult = true;
            } catch (Exception $e) {
                $headerResult = $e->getMessage();
            }

            if ($headerResult !== true) {
                return $this->respondError(is_string($headerResult) ? $headerResult : "Failed to update approval matrix");
            }

            try {
                $this->Approval_matrix_model->delete_details($id);
            } catch (Exception $e) {
            }

            $details = isset($data['details']) && is_array($data['details']) ? $data['details'] : array();
            foreach ($details as $detail) {
                try {
                    $this->Approval_matrix_model->create_detail(
                        $id,
                        $detail['approver_id'],
                        $detail['approval_order'],
                        $detail['approval_type'],
                        !empty($detail['is_payment_advisory']) ? 1 : 0,
                        !empty($detail['is_payment_release']) ? 1 : 0,
                        !empty($detail['is_petty_cash_slip']) ? 1 : 0,
                        !empty($detail['is_bizlink_export']) ? 1 : 0
                    );
                } catch (Exception $e) {
                }
            }

            return $this->respondSuccess("Approval matrix updated successfully");
        } catch (Exception $e) {
            return $this->respondError("An error occurred: " . $e->getMessage());
        }
    }

    public function api_get_header()
    {
        try{
                $this->output->set_content_type('application/json');
                $cursorIdRaw = $this->input->post('CursorId');
                $take = $this->resolvePaginationTake($this->input->post('Take'));

                $cursorId = null;
                if ($cursorIdRaw !== null && $cursorIdRaw !== '') {
                    $cursorId = (int) $cursorIdRaw;
                }
                $result = $this->Approval_matrix_model->list_headers($cursorId, $take);

                $payload = $this->buildPaginationResult($result, $take);
                echo json_encode(array('status' => 'success') + $payload);
        }catch(Exception $e){
            echo json_encode(array(
                'status' => 'error',
                'response' => "An error occurred: " . $e->getMessage(),
            ));
        }    
    }

    public function api_get_details()
    {
        try{
            $this->output->set_content_type('application/json');
            $matrixIdRaw = $this->input->post('MatrixId');
            $matrixId = null;
            if ($matrixIdRaw !== null && $matrixIdRaw !== '') {
                $matrixId = (int) $matrixIdRaw;
            }
            if ($matrixId === null) {
                return $this->respondError("MatrixId is required");
            }
            $result = $this->Approval_matrix_model->list_details($matrixId);

            return $this->respondSuccess("success", $result);
        }catch(Exception $e){
            return $this->respondError("An error occurred: " . $e->getMessage());
        }
    }

    public function api_get_header_by_id($matrixId)
    {
        try{
            $this->output->set_content_type('application/json');
            $id = (int) $matrixId;
            if ($id <= 0) {
                return $this->respondError("Invalid MatrixId");
            }
            $result = $this->Approval_matrix_model->get_header_rows($id);

            if (empty($result)) {
                return $this->respondError("Approval matrix not found");
            }

            return $this->respondSuccess("success", $result[0]);
        }catch(Exception $e){
            return $this->respondError("An error occurred: " . $e->getMessage());
        }
    }
}
