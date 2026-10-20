<?php

require 'vendor/autoload.php';
(defined('BASEPATH')) or exit('No direct script access allowed');

class Expense_Types extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('SPModel', 'sp');
        $this->sp->setDatabase('dbknet');
        $this->load->model('Expense_type_model');
    }

    public function index()
    {
        $data = array(
            'title' => 'Expense Types',
            'main_view' => '../modules/expense-types/views/index',
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

            $result = $this->Expense_type_model->list_paged($cursorId, $take);

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

            $expenseCode = isset($data['ExpenseCode']) ? trim((string) $data['ExpenseCode']) : '';
            $categoryName = isset($data['CategoryName']) ? trim((string) $data['CategoryName']) : '';
            $longText = isset($data['LongText']) ? trim((string) $data['LongText']) : '';
            $shortText = isset($data['ShortText']) ? trim((string) $data['ShortText']) : '';
            $category = isset($data['Category']) ? trim((string) $data['Category']) : '';
            $status = isset($data['Status']) ? trim((string) $data['Status']) : 'CAT_ACTIVE';

            if ($expenseCode === '') {
                return $this->respondError('Expense code is required');
            }

            if (!preg_match('/^[0-9]{10}$/', $expenseCode)) {
                return $this->respondError('Expense code must be exactly 10 digits.');
            }

            if ($categoryName === '') {
                return $this->respondError('Category name is required');
            }

            if (!in_array($category, array('SD', 'GA'), true)) {
                return $this->respondError('Category must be SD or GA');
            }

            if (!in_array($status, array('CAT_ACTIVE', 'CAT_INACTIVE'), true)) {
                $status = 'CAT_ACTIVE';
            }

            $params = array(
                'ExpenseCode'  => $expenseCode,
                'CategoryName' => $categoryName,
                'LongText'     => $longText,
                'ShortText'    => $shortText,
                'Category'     => $category,
                'Status'       => $status,
                'CreatedBy'    => (int) $this->session->userdata('user_id'),
            );

            $result = $this->sp->createData(
                build_sp('sp_insert_maintenance_expense_type', count($params)),
                $params
            );

            if ($result === true) {
                return $this->respondSuccess('Expense type added successfully');
            }

            return $this->respondError(is_string($result) ? $result : 'Failed to add expense type');
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
            $expenseCode = isset($data['ExpenseCode']) ? trim((string) $data['ExpenseCode']) : '';
            $categoryName = isset($data['CategoryName']) ? trim((string) $data['CategoryName']) : '';
            $longText = isset($data['LongText']) ? trim((string) $data['LongText']) : '';
            $shortText = isset($data['ShortText']) ? trim((string) $data['ShortText']) : '';
            $category = isset($data['Category']) ? trim((string) $data['Category']) : '';
            $status = isset($data['Status']) ? trim((string) $data['Status']) : 'CAT_ACTIVE';

            if ($id <= 0) {
                return $this->respondError('Invalid expense type id');
            }

            if ($expenseCode === '') {
                return $this->respondError('Expense code is required');
            }

            if (!preg_match('/^[0-9]{10}$/', $expenseCode)) {
                return $this->respondError('Expense code must be exactly 10 digits.');
            }

            if ($categoryName === '') {
                return $this->respondError('Category name is required');
            }

            if (!in_array($category, array('SD', 'GA'), true)) {
                return $this->respondError('Category must be SD or GA');
            }

            if (!in_array($status, array('CAT_ACTIVE', 'CAT_INACTIVE'), true)) {
                $status = 'CAT_ACTIVE';
            }

            try {
                $this->Expense_type_model->update(
                    $id,
                    $expenseCode,
                    $categoryName,
                    $longText,
                    $shortText,
                    $category,
                    $status,
                    (int) $this->session->userdata('user_id')
                );
                $result = true;
            } catch (Exception $e) {
                $result = $e->getMessage();
            }

            if ($result === true) {
                return $this->respondSuccess('Expense type updated successfully');
            }

            return $this->respondError(is_string($result) ? $result : 'Failed to update expense type');
        } catch (Exception $e) {
            return $this->respondError('An error occurred: ' . $e->getMessage());
        }
    }

}