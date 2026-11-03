<?php
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

require 'vendor/autoload.php';
(defined('BASEPATH')) or exit('No direct script access allowed');
class Dashboard extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
            $this->load->model('SPModel', 'sp');
            $this->sp->setDatabase('dbknet');
    }
    public function index()
    {
        $set_view = '../modules/dashboard/views/dashboard';
        $user_info = $this->session->userdata('user_info');
        $is_support = $user_info['is_support'];
        $sectionID = $user_info['SectionID'];
        $is_maintenance_mode = 0;
        $user_id = $this->session->userdata('user_id');

        if ($is_maintenance_mode && $user_id != 11592 && $user_id != 11318) {

            $this->load->view('wait_maintenance');
            return;
        }

        $activeFund = $this->getActiveFundForUser((int) $user_id);

        $data = array(
            'title' => config_item('app_long_name'),
            'is_support' => $is_support,
            'main_view' => $set_view,
            'user_id' => $user_id,
            'company_id' => $user_info['company'],
            'section_id' => $sectionID,
            'module_group' => $this->module_group,
            'module' => $this->module,
            'hasActiveFund' => $activeFund !== null,
            'revolvingFundBalance' => $activeFund['available_balance'] ?? null,
            'revolvingFundCode' => $activeFund['fund_code'] ?? null,
            'allowSelfCashIn' => !empty($activeFund['allow_self_cash_in']),
            'revolvingFundLimit' => $activeFund['opening_balance'] ?? null,
            'revolvingFundUnlimited' => !empty($activeFund['allow_negative_balance']),
            'canManageOverdueCa' => $this->userIsMatrixApprover((int) $user_id),
            'scripts' => array(
                'index.js',
            ),
        );

        $this->load->view('main', $data);
    }

    private function userIsMatrixApprover($userId)
    {
        $row = $this->sp->db->select('D.id')
            ->from('tbl_approval_matrix_details D')
            ->join('tbl_approval_matrix_header H', 'H.id = D.matrix_header_id')
            ->where('D.approver_id', (int) $userId)
            ->where('H.is_active', 1)
            ->limit(1)
            ->get()
            ->row_array();

        return !empty($row);
    }

    public function api_global_search()
    {
        try {
            $this->output->set_content_type('application/json');
            $data = $this->getRequestPayload();

            $userId = (int) $this->session->userdata('user_id');
            if ($userId <= 0) {
                return $this->respondError('User not authenticated.');
            }

            $keyword = isset($data['Keyword']) ? trim((string) $data['Keyword']) : '';
            if (mb_strlen($keyword) < 2) {
                return $this->respondSuccess('OK', array());
            }

            $result = $this->sp->readData(
                build_sp('sp_global_search', 2),
                array('UserId' => $userId, 'Keyword' => $keyword),
                'result'
            );
            $result = is_array($result) ? $result : array();

            $moduleMatches = $this->searchOwnModules($keyword);

            return $this->respondSuccess('OK', array_merge($moduleMatches, $result));
        } catch (\Throwable $e) {
            return $this->respondError('An error occurred: ' . $e->getMessage());
        }
    }

    private function searchOwnModules($keyword)
    {
        $keyword = trim((string) $keyword);
        if ($keyword === '' || empty($this->module) || !is_array($this->module)) {
            return array();
        }

        $needle = mb_strtolower($keyword);
        $maxDistance = mb_strlen($needle) <= 4 ? 1 : (mb_strlen($needle) <= 8 ? 2 : 3);
        $matches = array();

        foreach ($this->module as $m) {
            $name = trim((string) ($m['module'] ?? ''));
            $route = trim((string) ($m['route'] ?? ''));
            if ($name === '' || $route === '') {
                continue;
            }

            $haystack = mb_strtolower($name);
            $isMatch = (mb_strpos($haystack, $needle) !== false);

            if (!$isMatch && mb_strlen($needle) >= 3) {
                foreach (preg_split('/\s+/', $haystack) as $word) {
                    if ($word !== '' && levenshtein($word, $needle) <= $maxDistance) {
                        $isMatch = true;
                        break;
                    }
                }
            }

            if ($isMatch) {
                $matches[] = array(
                    'transaction_type' => 'MODULE',
                    'reference_no' => null,
                    'description' => $name,
                    'requester_name' => (string) ($m['module_group'] ?? ''),
                    'amount' => null,
                    'status_name' => null,
                    'created_date' => null,
                    'is_approver' => null,
                    'is_pending' => null,
                    'module_name' => $name,
                    'module_route' => $route,
                    'module_group' => (string) ($m['module_group'] ?? ''),
                );
            }
        }

        return $matches;
    }

    public function api_get_favorite_modules()
    {
        try {
            $this->output->set_content_type('application/json');

            $userId = (int) $this->session->userdata('user_id');
            if ($userId <= 0) {
                return $this->respondError('User not authenticated.');
            }

            $this->load->model('Portal_model');
            $rows = $this->Portal_model->list_favorite_modules($userId);

            return $this->respondSuccess('OK', is_array($rows) ? $rows : array());
        } catch (\Throwable $e) {
            return $this->respondError('An error occurred: ' . $e->getMessage());
        }
    }

    public function api_toggle_favorite_module()
    {
        try {
            $this->output->set_content_type('application/json');
            $data = $this->getRequestPayload();

            $userId = (int) $this->session->userdata('user_id');
            if ($userId <= 0) {
                return $this->respondError('User not authenticated.');
            }

            $moduleName = isset($data['ModuleName']) ? trim((string) $data['ModuleName']) : '';
            $moduleRoute = isset($data['ModuleRoute']) ? trim((string) $data['ModuleRoute']) : '';
            $moduleGroup = isset($data['ModuleGroup']) ? trim((string) $data['ModuleGroup']) : '';

            if ($moduleName === '' || $moduleRoute === '') {
                return $this->respondError('Module is required.');
            }

            $allowedRoutes = array();
            foreach ((array) $this->module as $m) {
                $allowedRoutes[trim((string) ($m['route'] ?? ''))] = true;
            }
            if (!isset($allowedRoutes[$moduleRoute])) {
                return $this->respondError('You do not have access to this module.');
            }

            $this->load->model('Portal_model');
            $result = $this->Portal_model->toggle_favorite_module(
                $userId,
                $moduleName,
                $moduleRoute,
                $moduleGroup !== '' ? $moduleGroup : null
            );

            return $this->respondSuccess('OK', array(
                'is_favorite' => !empty($result['is_favorite']),
            ));
        } catch (\Throwable $e) {
            return $this->respondError('An error occurred: ' . $e->getMessage());
        }
    }

    public function api_get_overdue_liquidations()
    {
        try {
            $this->output->set_content_type('application/json');

            $userId = (int) $this->session->userdata('user_id');
            if ($userId <= 0 || !$this->userIsMatrixApprover($userId)) {
                return $this->respondError('Not authorized.');
            }

            $this->load->model('Cash_advance_model');
            $rows = $this->Cash_advance_model->list_overdue_liquidations();
            $rows = is_array($rows) ? $rows : array();

            return $this->respondSuccess('OK', array('rows' => $rows));
        } catch (\Throwable $e) {
            return $this->respondError('An error occurred: ' . $e->getMessage());
        }
    }

    public function api_notify_overdue()
    {
        try {
            $this->output->set_content_type('application/json');
            $data = $this->getRequestPayload();

            $userId = (int) $this->session->userdata('user_id');
            if ($userId <= 0 || !$this->userIsMatrixApprover($userId)) {
                return $this->respondError('Not authorized.');
            }

            $referenceNumbers = isset($data['reference_numbers']) && is_array($data['reference_numbers'])
                ? $data['reference_numbers']
                : array();

            if (count($referenceNumbers) === 0) {
                throw new Exception('No cash advances selected.');
            }

            $this->load->model('Cash_advance_model');
            $rows = $this->Cash_advance_model->list_overdue_liquidations();
            $rows = is_array($rows) ? $rows : array();
            $byRef = array();
            foreach ($rows as $row) {
                $byRef[$row['cash_advance_id']] = $row;
            }

            $notified = array();
            $skipped = array();

            foreach ($referenceNumbers as $referenceNo) {
                $referenceNo = trim((string) $referenceNo);
                if ($referenceNo === '' || !isset($byRef[$referenceNo])) {
                    $skipped[] = $referenceNo;
                    continue;
                }

                $row = $byRef[$referenceNo];
                $info = get_user_info((int) $row['user_id']);
                if (!is_array($info) || empty($info['email'])) {
                    $skipped[] = $referenceNo;
                    continue;
                }

                $name = trim((string) ($info['firstname'] ?? '') . ' ' . (string) ($info['lastname'] ?? ''));
                $recipient = array(
                    'email' => $info['email'],
                    'name' => $name !== '' ? $name : $info['email'],
                    'user_id' => (int) $row['user_id'],
                );

                $daysOverdue = (int) $row['days_overdue'];
                $mergeData = array(
                    'amount' => number_format((float) $row['amount'], 2),
                    'due_date' => (string) $row['due_date'],
                    'days_overdue' => $daysOverdue > 0 ? $daysOverdue : 0,
                    'requester_name' => $recipient['name'],
                    'action_date' => date('Y-m-d H:i:s'),
                    'liquidation_link' => base_url('transactions/liquidation/add'),
                );

                notify_event('LIQUIDATION_OVERDUE', 'CASH_ADVANCE', $referenceNo, array($recipient), $mergeData);
                $notified[] = $referenceNo;
            }

            return $this->respondSuccess('Reminders sent.', array(
                'notified' => $notified,
                'skipped' => $skipped,
            ));
        } catch (\Throwable $e) {
            return $this->respondError('An error occurred: ' . $e->getMessage());
        }
    }

    public function api_extend_due()
    {
        try {
            $this->output->set_content_type('application/json');
            $data = $this->getRequestPayload();

            $userId = (int) $this->session->userdata('user_id');
            if ($userId <= 0 || !$this->userIsMatrixApprover($userId)) {
                return $this->respondError('Not authorized.');
            }

            $cashAdvanceId = isset($data['CashAdvanceId']) ? trim((string) $data['CashAdvanceId']) : '';
            $newDueDate = isset($data['NewDueDate']) ? trim((string) $data['NewDueDate']) : '';
            $remarks = isset($data['Remarks']) ? trim((string) $data['Remarks']) : '';

            if ($cashAdvanceId === '') {
                throw new Exception('Cash advance is required.');
            }
            if ($newDueDate === '' || strtotime($newDueDate) === false) {
                throw new Exception('A valid new due date is required.');
            }
            if (strtotime($newDueDate) <= strtotime(date('Y-m-d'))) {
                throw new Exception('New due date must be a future date.');
            }
            if ($remarks === '') {
                throw new Exception('Remarks are required when extending a due date.');
            }

            $this->load->model('Cash_advance_model');
            $result = $this->Cash_advance_model->extend_liquidation_due(
                $cashAdvanceId,
                date('Y-m-d', strtotime($newDueDate)),
                $remarks,
                $userId
            );

            if (!is_array($result) || empty($result['cash_advance_id'])) {
                throw new Exception('Failed to extend the due date.');
            }

            return $this->respondSuccess('Due date extended.', array(
                'cash_advance_id' => $result['cash_advance_id'],
                'liquidation_due_date' => $result['liquidation_due_date'],
            ));
        } catch (\Throwable $e) {
            return $this->respondError('An error occurred: ' . $e->getMessage());
        }
    }

    private function getActiveFundForUser($userId)
    {
        $this->load->model('Revolving_fund_model');
        $row = $this->Revolving_fund_model->find_active_fund_for_user($userId);

        return !empty($row) ? $row : null;
    }

    public function api_fund_cash_in()
    {
        try {
            $this->output->set_content_type('application/json');

            $userId = (int) $this->session->userdata('user_id');
            if ($userId <= 0) {
                return $this->respondError('User not authenticated.');
            }

            $fund = $this->getActiveFundForUser($userId);
            if (empty($fund)) {
                return $this->respondError('You do not hold an active revolving fund.');
            }
            if (empty($fund['allow_self_cash_in'])) {
                return $this->respondError('Self cash in is not enabled for your fund. Please contact your administrator.');
            }

            $amount = (float) $this->input->post('Amount');
            if ($amount <= 0) {
                return $this->respondError('Amount must be greater than zero.');
            }

            $remarks = trim((string) $this->input->post('Remarks'));
            if ($remarks === '') {
                $remarks = 'Cash in by fund holder';
            }

            $capacity = (float) ($fund['opening_balance'] ?? 0) - (float) ($fund['available_balance'] ?? 0);
            if ($amount > $capacity) {
                return $this->respondError(
                    'Cash in exceeds your fund limit of ₱' . number_format((float) ($fund['opening_balance'] ?? 0), 2)
                    . '. You can add up to ₱' . number_format(max($capacity, 0), 2) . ' only.'
                );
            }

            $params = array(
                'FundId' => (int) $fund['id'],
                'Amount' => $amount,
                'Remarks' => $remarks,
                'CreatedBy' => $userId,
            );

            $result = $this->sp->readData(
                build_sp('sp_revolving_fund_self_cash_in', count($params)),
                $params,
                'row'
            );

            if (!is_array($result) || !isset($result['new_balance'])) {
                return $this->respondError('Failed to record cash in.');
            }

            return $this->respondSuccess('Cash in recorded.', array(
                'new_balance' => $result['new_balance'],
            ));
        } catch (\Throwable $e) {
            return $this->respondError('An error occurred: ' . $e->getMessage());
        }
    }

    public function api_fund_passbook()
    {
        try {
            $this->output->set_content_type('application/json');

            $userId = (int) $this->session->userdata('user_id');
            if ($userId <= 0) {
                return $this->respondError('User not authenticated.');
            }

            $fund = $this->getActiveFundForUser($userId);
            if (empty($fund)) {
                return $this->respondError('You do not hold an active revolving fund.');
            }

            $cursorIdRaw = $this->input->post('CursorId');
            $cursorId = ($cursorIdRaw !== null && $cursorIdRaw !== '') ? (int) $cursorIdRaw : null;
            $take = (int) $this->input->post('Take');
            if ($take < 1) {
                $take = 20;
            }

            $this->load->model('Revolving_fund_model');
            $rows = $this->Revolving_fund_model->list_ledger_entries($cursorId, $take, (int) $fund['id']);
            $rows = is_array($rows) ? $rows : array();

            $hasMore = count($rows) > $take;
            if ($hasMore) {
                $rows = array_slice($rows, 0, $take);
            }

            return $this->respondSuccess('OK', array(
                'fund_code' => $fund['fund_code'] ?? '',
                'balance' => $fund['available_balance'] ?? 0,
                'rows' => $rows,
                'has_more' => $hasMore,
                'next_cursor' => $hasMore && !empty($rows) ? end($rows)['id'] : null,
            ));
        } catch (\Throwable $e) {
            return $this->respondError('An error occurred: ' . $e->getMessage());
        }
    }

    public function api_get_summary()
    {
        try {
            $this->output->set_content_type('application/json');

            $userId = (int) $this->session->userdata('user_id');
            if ($userId <= 0) {
                return $this->respondError('User not authenticated.');
            }

            $scope = $this->input->post('Scope');
            $scope = in_array($scope, array('today', 'week', 'month'), true) ? $scope : 'month';

            $summary = $this->sp->readData(
                build_sp('sp_fetch_dashboard_summary', 2),
                array('UserId' => $userId, 'Scope' => $scope),
                'row'
            );

            $recent = $this->sp->readData(
                build_sp('sp_fetch_dashboard_recent', 2),
                array('UserId' => $userId, 'Take' => 10),
                'result'
            );

            $statusOverview = $this->sp->readData(
                build_sp('sp_fetch_dashboard_status_overview', 1),
                array('UserId' => $userId),
                'result'
            );

            $attention = $this->sp->readData(
                build_sp('sp_fetch_dashboard_attention', 1),
                array('UserId' => $userId),
                'result'
            );

            return $this->respondSuccess('OK', array(
                'summary' => is_array($summary) ? $summary : array(),
                'recent' => is_array($recent) ? $recent : array(),
                'status_overview' => is_array($statusOverview) ? $statusOverview : array(),
                'attention' => is_array($attention) ? $attention : array(),
            ));
        } catch (\Throwable $e) {
            return $this->respondError('An error occurred: ' . $e->getMessage());
        }
    }

}
