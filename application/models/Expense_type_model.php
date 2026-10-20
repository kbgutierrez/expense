<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once APPPATH . 'core/MY_Model.php';

class Expense_type_model extends MY_Model
{
    public function list_paged($cursorId = null, $take = null)
    {
        $top = ($take !== null && (int) $take >= 1) ? 'TOP (' . ((int) $take + 1) . ') ' : '';
        return $this->query_rows(
            'SELECT ' . $top . 'id, expense_code, category_name, long_text, short_text, category, created_date, updated_date, created_by, created_by_name, updated_by, updated_by_name, status, status_name'
            . ' FROM ' . Knet_views::sub('vw_expense_types', 'V')
            . ' WHERE (CAST(? AS BIGINT) IS NULL OR id < CAST(? AS BIGINT))'
            . ' ORDER BY id DESC',
            array($cursorId, $cursorId)
        );
    }

    public function update($id, $code, $categoryName, $longText, $shortText, $category, $status = 'CAT_ACTIVE', $updatedBy = null)
    {
        $n = $this->query_row(
            "EXEC sp_executesql N'SELECT @id AS id,"
            . " LTRIM(RTRIM(ISNULL(@code, ''''))) AS code,"
            . " LTRIM(RTRIM(ISNULL(@category_name, ''''))) AS category_name,"
            . " LTRIM(RTRIM(ISNULL(@long_text, ''''))) AS long_text,"
            . " LTRIM(RTRIM(ISNULL(@short_text, ''''))) AS short_text,"
            . " CAST(UPPER(LTRIM(RTRIM(ISNULL(@category, '''')))) AS VARCHAR(10)) AS category,"
            . " CAST(CASE WHEN @status IN (''CAT_ACTIVE'', ''CAT_INACTIVE'') THEN @status ELSE ''CAT_ACTIVE'' END AS VARCHAR(20)) AS status,"
            . " @user_id AS user_id',"
            . " N'@id BIGINT, @code NVARCHAR(50), @category_name NVARCHAR(200), @long_text NVARCHAR(300), @short_text NVARCHAR(100), @category VARCHAR(10), @status VARCHAR(20), @user_id BIGINT', ?, ?, ?, ?, ?, ?, ?, ?",
            array($id, $code, $categoryName, $longText, $shortText, $category, $status, $updatedBy)
        );

        if ($n['id'] === null || (float) $n['id'] <= 0) {
            $this->fail('Invalid expense type id.', 50006);
        }
        if ($n['code'] === '') {
            $this->fail('Expense code is required.', 50007);
        }
        if (mb_strlen($n['code'], 'UTF-8') !== 10 || preg_match('/[^0-9]/', $n['code'])) {
            $this->fail('Expense code must be exactly 10 digits.', 50013);
        }
        if ($n['category_name'] === '') {
            $this->fail('Category name is required.', 50008);
        }
        if (!in_array($n['category'], array('SD', 'GA'), true)) {
            $this->fail('Category must be SD or GA.', 50009);
        }

        $idKey = (string) $n['id'];

        $exists = $this->query_rows(
            'SELECT TOP 1 1 AS found FROM dbo.tbl_expense_category WHERE id = CAST(CAST(? AS BIGINT) AS NVARCHAR(20))',
            array($idKey)
        );
        if (empty($exists)) {
            $this->fail('Expense type record not found.', 50010);
        }

        $dup = $this->query_rows(
            'SELECT TOP 1 1 AS found FROM dbo.tbl_expense_category WHERE UPPER(LTRIM(RTRIM(expense_code))) = UPPER(CAST(? AS NVARCHAR(50))) AND id <> CAST(CAST(? AS BIGINT) AS NVARCHAR(20))',
            array($n['code'], $idKey)
        );
        if (!empty($dup)) {
            $this->fail('Expense code already exists.', 50011);
        }

        $dup = $this->query_rows(
            'SELECT TOP 1 1 AS found FROM dbo.tbl_expense_category WHERE UPPER(LTRIM(RTRIM([description]))) = UPPER(CAST(? AS NVARCHAR(200))) AND id <> CAST(CAST(? AS BIGINT) AS NVARCHAR(20))',
            array($n['category_name'], $idKey)
        );
        if (!empty($dup)) {
            $this->fail('Category name already exists.', 50012);
        }

        $this->write(function ($db) use ($n, $idKey) {
            $db->set('expense_code', $n['code'])
                ->set('description', $n['category_name'] === '' ? null : $n['category_name'])
                ->set('long_text', $n['long_text'] === '' ? null : $n['long_text'])
                ->set('short_text', $n['short_text'] === '' ? null : $n['short_text'])
                ->set('category', $n['category'])
                ->set('status', $n['status'])
                ->set('updated_date', 'GETDATE()', false)
                ->set('updated_by', $n['user_id'])
                ->where('id = CAST(CAST(' . $db->escape($idKey) . ' AS BIGINT) AS NVARCHAR(20))', null, false);
            return $db->update('dbo.tbl_expense_category');
        });
    }
}
