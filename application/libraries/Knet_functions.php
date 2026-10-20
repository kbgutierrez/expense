<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

class Knet_functions
{
    public static function fn_approved_amount($refSql)
    {
        $r = 'CAST(' . $refSql . ' AS VARCHAR(20)) COLLATE DATABASE_DEFAULT';
        return "CAST(CASE
    WHEN LEFT($r, 2) = 'CA' THEN (SELECT TOP (1) ISNULL(fa_ca.approved_amount, fa_ca.amount) FROM tbl_cash_advance fa_ca WHERE fa_ca.cash_advance_id = $r)
    WHEN LEFT($r, 2) = 'LQ' THEN COALESCE(
        (SELECT CAST(SUM(ISNULL(fa_lqd.approved_amount, fa_lqd.actual_amount)) AS DECIMAL(18, 2)) FROM tbl_liquidation_details fa_lqd WHERE fa_lqd.liquidation_id = $r),
        (SELECT TOP (1) fa_lqh.total_amount_spent FROM tbl_liquidation_header fa_lqh WHERE fa_lqh.liquidation_id = $r))
    WHEN LEFT($r, 3) = 'RMB' THEN COALESCE(
        (SELECT CAST(SUM(ISNULL(fa_rmd.approved_amount, fa_rmd.actual_amount)) AS DECIMAL(18, 2)) FROM tbl_reimbursement_details fa_rmd WHERE fa_rmd.reimbursement_id = $r),
        (SELECT TOP (1) fa_rmh.total_amount FROM tbl_reimbursement_header fa_rmh WHERE fa_rmh.reimbursement_id = $r))
    WHEN LEFT($r, 3) = 'RPL' THEN (SELECT TOP (1) fa_rpl.total_amount FROM tbl_replenishment_header fa_rpl WHERE fa_rpl.replenishment_id = $r)
END AS DECIMAL(18, 2))";
    }

    public static function split_string($string, $delimiter)
    {
        if ($string === null || $delimiter === null) {
            return array();
        }
        $delimiter = (string) $delimiter;
        $delimiter = $delimiter === '' ? ' ' : mb_substr($delimiter, 0, 1, 'UTF-8');
        $string = mb_substr((string) $string, 0, 4000, 'UTF-8');
        $out = array();
        foreach (explode($delimiter, $string) as $part) {
            $value = trim($part, ' ');
            if ($value !== '') {
                $out[] = $value;
            }
        }
        return $out;
    }

    public static function levenshtein($db, $s1, $s2)
    {
        $a = self::chars($s1);
        $b = self::chars($s2);
        $len1 = count($a);
        $len2 = count($b);
        if ($len1 === 0) {
            return $len2;
        }
        if ($len2 === 0) {
            return $len1;
        }
        if ($len1 > 100 || $len2 > 100) {
            return 999;
        }
        $prev = range(0, $len2);
        for ($i = 1; $i <= $len1; $i++) {
            $curr = array($i);
            for ($j = 1; $j <= $len2; $j++) {
                $cost = $a[$i - 1] === $b[$j - 1] ? 0 : 1;
                $curr[$j] = min($prev[$j] + 1, $curr[$j - 1] + 1, $prev[$j - 1] + $cost);
            }
            $prev = $curr;
        }
        return $prev[$len2];
    }

    private static function chars($value)
    {
        if ($value === null) {
            return array();
        }
        $value = rtrim(mb_substr((string) $value, 0, 200, 'UTF-8'), ' ');
        if ($value === '') {
            return array();
        }
        return preg_split('//u', $value, -1, PREG_SPLIT_NO_EMPTY);
    }
}
