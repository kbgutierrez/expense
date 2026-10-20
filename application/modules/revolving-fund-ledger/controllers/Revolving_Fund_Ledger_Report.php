<?php

require 'vendor/autoload.php';
(defined('BASEPATH')) or exit('No direct script access allowed');

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class Revolving_Fund_Ledger_Report extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('SPModel', 'sp');
        $this->sp->setDatabase('dbknet');
    }

    public function index()
    {
        $data = array(
            'title' => 'Revolving Fund Ledger',
            'main_view' => '../modules/revolving-fund-ledger/views/index',
            'module_group' => $this->module_group,
            'module' => $this->module,
            'scripts' => array(
                'index.js',
            ),
        );

        $this->load->view('main', $data);
    }

    private function resolveFilters()
    {
        $fundCode = trim((string) $this->input->get_post('FundCode'));
        $dateFrom = trim((string) $this->input->get_post('DateFrom'));
        $dateTo = trim((string) $this->input->get_post('DateTo'));

        return array(
            'FundCode' => $fundCode !== '' ? $fundCode : null,
            'DateFrom' => $dateFrom !== '' ? $dateFrom : null,
            'DateTo' => $dateTo !== '' ? $dateTo : null,
        );
    }

    private function fetchRows($cursorId, $take)
    {
        $filters = $this->resolveFilters();

        $this->load->model('Revolving_fund_model');
        $result = $this->Revolving_fund_model->get_ledger_report(
            $filters['FundCode'],
            $filters['DateFrom'],
            $filters['DateTo'],
            $cursorId,
            $take
        );

        return is_array($result) ? $result : array();
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

            $result = $this->fetchRows($cursorId, $take);

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

    public function download_excel()
    {
        $result = $this->fetchRows(null, null);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Revolving Fund Ledger');

        $headers = array('Fund Code', 'Fund Scope', 'Transaction Date', 'Transaction Type', 'Reference No.', 'Employee', 'Position', 'Amount', 'Balance After', 'Remarks', 'Created By', 'Created Date');
        $sheet->fromArray($headers, null, 'A1');
        $sheet->getStyle('A1:L1')->getFont()->setBold(true);

        $rowIndex = 2;
        foreach ($result as $row) {
            $sheet->fromArray(array(
                isset($row['fund_code']) ? $row['fund_code'] : '',
                isset($row['fund_scope_name']) ? $row['fund_scope_name'] : '',
                isset($row['trx_date']) ? $row['trx_date'] : '',
                isset($row['trx_type_name']) ? $row['trx_type_name'] : '',
                isset($row['reference_no']) ? $row['reference_no'] : '',
                isset($row['employee_name']) ? $row['employee_name'] : '',
                isset($row['employee_position']) ? $row['employee_position'] : '',
                isset($row['amount']) ? (float) $row['amount'] : 0,
                isset($row['balance_after']) ? (float) $row['balance_after'] : 0,
                isset($row['remarks']) ? $row['remarks'] : '',
                isset($row['created_by_name']) ? $row['created_by_name'] : '',
                isset($row['created_date']) ? $row['created_date'] : '',
            ), null, 'A' . $rowIndex);
            $rowIndex++;
        }

        foreach (range('A', 'L') as $col) {
            $sheet->getColumnDimension($col)->setWidth(20);
        }

        $filename = 'revolving-fund-ledger-' . date('Ymd-His') . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save('php://output');
        exit;
    }
}
