<?php

date_default_timezone_set('Asia/Manila');
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

require APPPATH . "third_party/MX/Controller.php";

class MY_Controller extends MX_Controller
{
    public $module_name;
    public $module_group = [];
    public $module = [];
    public $tableau_url;

    public function __construct()
    {
        parent::__construct();

        $this->output
            ->set_header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0')
            ->set_header('Cache-Control: post-check=0, pre-check=0', false)
            ->set_header('Pragma: no-cache')
            ->set_header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');

        if (version_compare(CI_VERSION, '2.1.0', '<')) {
            $this->load->library('security');
        }

        $this->module_name = strtolower($this->router->fetch_module());
        $this->tableau_url = 'tableau.lemonsquare.com.ph';

        if ($this->session->userdata('user_info')) {
            $this->load->model('SPModel', 'sp');
             $this->sp->setDatabase('dbknet');
     
            $this->load->model('Portal_model');
            $userId = $this->session->userdata('user_id');
            $this->module_group = $this->Portal_model->list_module_groups($userId);
            $this->module = $this->Portal_model->list_modules($userId);
        }

        
    }

    protected function logAuditTrail($transactionType, $transactionId, $action, $entityType, $entityId, $fieldName = null, $oldValue = null, $newValue = null)
    {
        try {
            $this->load->model('Audit_trail_model');
            $this->Audit_trail_model->create(
                $transactionType,
                $transactionId,
                $action,
                $entityType,
                $entityId,
                $fieldName,
                $oldValue,
                $newValue,
                (int) $this->session->userdata('user_id')
            );
            return TRUE;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    protected function resolvePaginationTake($takeRaw)
    {
        $take = ($takeRaw !== null && $takeRaw !== '') ? (int) $takeRaw : 0;
        return $take > 0 ? $take : null;
    }

    protected function buildPaginationResult($result, $take, $cursorField = 'id')
    {
        if ($take === null) {
            return array(
                'data' => $result,
                'pagination' => array('take' => 0, 'hasMore' => false, 'nextCursorId' => null),
            );
        }

        $hasMore = count($result) > $take;
        if ($hasMore) {
            array_pop($result);
        }

        $nextCursorId = null;
        if (!empty($result)) {
            $lastRow = end($result);
            $nextCursorId = isset($lastRow[$cursorField]) ? (int) $lastRow[$cursorField] : null;
        }

        return array(
            'data' => $result,
            'pagination' => array('take' => $take, 'hasMore' => $hasMore, 'nextCursorId' => $nextCursorId),
        );
    }

    protected function getRequestPayload()
    {
        $contentType = $this->input->server('CONTENT_TYPE');
        if (is_string($contentType) && stripos($contentType, 'application/json') !== false) {
            $data = json_decode($this->input->raw_input_stream, true);
            return is_array($data) ? $data : array();
        }

        $postData = $this->input->post();
        return is_array($postData) ? $postData : array();
    }

    protected function respondSuccess($message, $data = array())
    {
        echo json_encode(array(
            'status' => 'success',
            'response' => $message,
            'data' => $data,
        ));
        return;
    }

    protected function respondError($message)
    {
        echo json_encode(array(
            'status' => 'error',
            'response' => $message,
        ));
        return;
    }
}


