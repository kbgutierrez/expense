<?php

(defined('BASEPATH')) or exit('No direct script access allowed');

class Cost_Center extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('SPModel', 'sp');
        $this->sp->setDatabase('dbknet');
        $this->load->model('Cost_center_model');
    }

    public function index()
    {
        $data = array(
            'title' => 'Cost Center',
            'main_view' => '../modules/cost-center/views/index',
            'module_group' => $this->module_group,
            'module' => $this->module,
            'scripts' => array(
                'index.js',
            ),
        );

        $this->load->view('main', $data);
    }

    public function api_get()
    {
        try {
            $this->output->set_content_type('application/json');
            $cursorIdRaw = $this->input->post('CursorId');
            $take = $this->resolvePaginationTake($this->input->post('Take'));

            $cursorId = null;
            if ($cursorIdRaw !== null && $cursorIdRaw !== '') {
                $cursorId = (int) $cursorIdRaw;
            }

            $result = $this->Cost_center_model->list_paged($cursorId, $take);

            $payload = $this->buildPaginationResult($result, $take);

            echo json_encode(array(
                'status' => 'success',
                'data' => $payload['data'],
                'pagination' => $payload['pagination'],
            ));
        } catch (Exception $e) {
            echo json_encode(array(
                'status' => 'error',
                'response' => "An error occurred: " . $e->getMessage(),
            ));
        }
    }

    public function api_save()
    {
        try {
            $this->output->set_content_type('application/json');

            $data = $this->getRequestPayload();

            $costCenterCode = isset($data['CostCenterCode']) ? trim((string) $data['CostCenterCode']) : '';
            $costCenterName = isset($data['CostCenterName']) ? trim((string) $data['CostCenterName']) : '';
            $category = isset($data['Category']) ? trim((string) $data['Category']) : '';
            $isActive = array_key_exists('IsActive', $data) ? !empty($data['IsActive']) : true;

            if ($costCenterCode === '') {
                return $this->respondError('Cost center code is required');
            }

            if ($costCenterName === '') {
                return $this->respondError('Cost center name is required');
            }

            if (!in_array($category, array('SD', 'GA'), true)) {
                return $this->respondError('Category must be SD or GA');
            }

            try {
                $this->Cost_center_model->create(
                    $costCenterCode,
                    $costCenterName,
                    $category,
                    $isActive ? 1 : 0,
                    (int) $this->session->userdata('user_id')
                );
                $result = true;
            } catch (Exception $e) {
                $result = $e->getMessage();
            }

            if ($result === true) {
                return $this->respondSuccess('Cost center added successfully');
            }

            return $this->respondError(is_string($result) ? $result : 'Failed to add cost center');
        } catch (Exception $e) {
            return $this->respondError('An error occurred: ' . $e->getMessage());
        }
    }

    public function api_update()
    {
        try {
            $this->output->set_content_type('application/json');

            $data = $this->getRequestPayload();

            $id = isset($data['Id']) ? (int) $data['Id'] : 0;
            $costCenterCode = isset($data['CostCenterCode']) ? trim((string) $data['CostCenterCode']) : '';
            $costCenterName = isset($data['CostCenterName']) ? trim((string) $data['CostCenterName']) : '';
            $category = isset($data['Category']) ? trim((string) $data['Category']) : '';
            $isActive = array_key_exists('IsActive', $data) ? !empty($data['IsActive']) : true;

            if ($id <= 0) {
                return $this->respondError('Invalid cost center id');
            }

            if ($costCenterCode === '') {
                return $this->respondError('Cost center code is required');
            }

            if ($costCenterName === '') {
                return $this->respondError('Cost center name is required');
            }

            if (!in_array($category, array('SD', 'GA'), true)) {
                return $this->respondError('Category must be SD or GA');
            }

            try {
                $this->Cost_center_model->update(
                    $id,
                    $costCenterCode,
                    $costCenterName,
                    $category,
                    $isActive ? 1 : 0,
                    (int) $this->session->userdata('user_id')
                );
                $result = true;
            } catch (Exception $e) {
                $result = $e->getMessage();
            }

            if ($result === true) {
                return $this->respondSuccess('Cost center updated successfully');
            }

            return $this->respondError(is_string($result) ? $result : 'Failed to update cost center');
        } catch (Exception $e) {
            return $this->respondError('An error occurred: ' . $e->getMessage());
        }
    }
}
