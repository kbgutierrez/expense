<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once APPPATH . 'core/MY_Model.php';

class Payment_model extends MY_Model
{
    public function get_user_payment_capability($referenceNo, $userId)
    {
        if ($referenceNo !== null) {
            $referenceNo = substr((string) $referenceNo, 0, 20);
        }

        $trimmed = $referenceNo === null ? null : strtoupper(trim($referenceNo, ' '));
        $prefix = null;
        if ($trimmed !== null) {
            $prefix = substr($trimmed, 0, 3) === 'RMB' ? 'RMB' : substr($trimmed, 0, 2);
            if (substr($trimmed, 0, 3) === 'RPL') {
                $prefix = 'RPL';
            }
        }

        $matrixHeaderId = null;
        $matrix = $this->query_rows(
            'SELECT approval_matrix_id FROM tbl_approval_header WHERE reference_id = CAST(? AS VARCHAR(20)) AND is_active = 1',
            array($referenceNo)
        );
        if (!empty($matrix)) {
            $last = end($matrix);
            $matrixHeaderId = $last['approval_matrix_id'];
        }

        $sources = array(
            'CA' => array('CASH_ADVANCE', 'CA_APPROVED', 'CA_FOR_RELEASE', 'SELECT status AS v FROM tbl_cash_advance WHERE cash_advance_id = CAST(? AS VARCHAR(20))'),
            'LQ' => array('LIQUIDATION', 'LQ_APPROVED', 'LQ_FOR_RELEASE', 'SELECT status AS v FROM tbl_liquidation_header WHERE liquidation_id = CAST(? AS VARCHAR(20))'),
            'RPL' => array('REPLENISHMENT', 'RPL_APPROVED', 'RPL_FOR_RELEASE', 'SELECT status_code AS v FROM tbl_replenishment_header WHERE replenishment_id = CAST(? AS VARCHAR(20))'),
            'RMB' => array('REIMBURSEMENT', 'RMB_APPROVED', 'RMB_FOR_RELEASE', 'SELECT status_code AS v FROM tbl_reimbursement_header WHERE reimbursement_id = CAST(? AS VARCHAR(20))'),
        );

        $transactionType = null;
        $approvedCode = null;
        $forReleaseCode = null;
        $statusCode = null;
        if ($prefix !== null && isset($sources[$prefix])) {
            list($transactionType, $approvedCode, $forReleaseCode, $statusSql) = $sources[$prefix];
            $status = $this->query_rows($statusSql, array($referenceNo));
            if (!empty($status)) {
                $last = end($status);
                $statusCode = $last['v'];
            }
        }

        return $this->query_row(
            'SELECT
    CAST(? AS VARCHAR(20)) AS transaction_type,
    CAST(? AS VARCHAR(20)) AS reference_no,
    CAST(? AS VARCHAR(50)) AS status_code,
    ISNULL(S.status_name, CAST(? AS VARCHAR(50))) AS status_name,
    ISNULL(D.is_payment_advisory, 0) AS is_payment_advisory,
    ISNULL(D.is_payment_release, 0) AS is_payment_release,
    CASE WHEN ISNULL(D.is_payment_advisory, 0) = 1 AND CAST(? AS VARCHAR(50)) = CAST(? AS VARCHAR(50)) THEN 1 ELSE 0 END AS can_advise,
    CASE WHEN ISNULL(D.is_payment_release, 0) = 1 AND CAST(? AS VARCHAR(50)) = CAST(? AS VARCHAR(50)) THEN 1 ELSE 0 END AS can_release
FROM (SELECT 1 AS dummy) X
LEFT JOIN tbl_approval_matrix_details D
    ON D.matrix_header_id = CAST(? AS INT)
    AND D.approver_id = CAST(? AS INT)
LEFT JOIN tbl_status S
    ON S.status_code = CAST(? AS VARCHAR(50))',
            array($transactionType, $referenceNo, $statusCode, $statusCode, $statusCode, $approvedCode, $statusCode, $forReleaseCode, $matrixHeaderId, $userId, $statusCode)
        );
    }
}
