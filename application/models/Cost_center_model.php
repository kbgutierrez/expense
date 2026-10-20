<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once APPPATH . 'core/MY_Model.php';

class Cost_center_model extends MY_Model
{
    public function list_paged($cursorId = null, $take = null)
    {
        $top = ($take !== null && (int) $take >= 1) ? 'TOP (' . ((int) $take + 1) . ') ' : '';
        return $this->query_rows(
            'SELECT ' . $top . 'id, cost_center_code, cost_center_name, category, is_active, created_date, updated_date, created_by, created_by_name, updated_by, updated_by_name'
            . ' FROM ' . Knet_views::sub('vw_cost_center', 'V')
            . ' WHERE (CAST(? AS INT) IS NULL OR id < CAST(? AS INT))'
            . ' ORDER BY id DESC',
            array($cursorId, $cursorId)
        );
    }

    public function create($code, $name, $category, $isActive = 1, $createdBy = null)
    {
        $n = $this->query_row(
            "EXEC sp_executesql N'SELECT LTRIM(RTRIM(ISNULL(@code, ''''))) AS code,"
            . " LTRIM(RTRIM(ISNULL(@name, ''''))) AS name,"
            . " CAST(UPPER(LTRIM(RTRIM(ISNULL(@category, '''')))) AS VARCHAR(10)) AS category,"
            . " ISNULL(@is_active, 1) AS is_active,"
            . " @created_by AS created_by',"
            . " N'@code NVARCHAR(50), @name NVARCHAR(255), @category VARCHAR(10), @is_active BIT, @created_by BIGINT', ?, ?, ?, ?, ?",
            array($code, $name, $category, $isActive, $createdBy)
        );

        if ($n['code'] === '') {
            $this->fail('Cost center code is required.', 51001);
        }
        if ($n['name'] === '') {
            $this->fail('Cost center name is required.', 51002);
        }
        if (!in_array($n['category'], array('SD', 'GA'), true)) {
            $this->fail('Category must be SD or GA.', 51003);
        }

        $dup = $this->query_rows(
            'SELECT TOP 1 1 AS found FROM dbo.tbl_cost_center WHERE UPPER(LTRIM(RTRIM(cost_center_code))) = UPPER(CAST(? AS NVARCHAR(50)))',
            array($n['code'])
        );
        if (!empty($dup)) {
            $this->fail('Cost center code already exists.', 51004);
        }

        $this->write(function ($db) use ($n) {
            $db->set('cost_center_code', $n['code'])
                ->set('cost_center_name', $n['name'])
                ->set('category', $n['category'])
                ->set('is_active', (int) $n['is_active'])
                ->set('created_date', 'GETDATE()', false)
                ->set('updated_date', 'GETDATE()', false)
                ->set('created_by', $n['created_by'])
                ->set('updated_by', $n['created_by']);
            return $db->insert('dbo.tbl_cost_center');
        });
    }

    public function update($id, $code, $name, $category, $isActive = 1, $updatedBy = null)
    {
        $n = $this->query_row(
            "EXEC sp_executesql N'SELECT @id AS id,"
            . " LTRIM(RTRIM(ISNULL(@code, ''''))) AS code,"
            . " LTRIM(RTRIM(ISNULL(@name, ''''))) AS name,"
            . " CAST(UPPER(LTRIM(RTRIM(ISNULL(@category, '''')))) AS VARCHAR(10)) AS category,"
            . " ISNULL(@is_active, 1) AS is_active,"
            . " @updated_by AS updated_by',"
            . " N'@id INT, @code NVARCHAR(50), @name NVARCHAR(255), @category VARCHAR(10), @is_active BIT, @updated_by BIGINT', ?, ?, ?, ?, ?, ?",
            array($id, $code, $name, $category, $isActive, $updatedBy)
        );

        if ($n['id'] === null || (int) $n['id'] <= 0) {
            $this->fail('Invalid cost center id.', 51005);
        }
        if ($n['code'] === '') {
            $this->fail('Cost center code is required.', 51006);
        }
        if ($n['name'] === '') {
            $this->fail('Cost center name is required.', 51007);
        }
        if (!in_array($n['category'], array('SD', 'GA'), true)) {
            $this->fail('Category must be SD or GA.', 51008);
        }

        $exists = $this->query_rows('SELECT TOP 1 1 AS found FROM dbo.tbl_cost_center WHERE id = ?', array((int) $n['id']));
        if (empty($exists)) {
            $this->fail('Cost center record not found.', 51009);
        }

        $dup = $this->query_rows(
            'SELECT TOP 1 1 AS found FROM dbo.tbl_cost_center WHERE UPPER(LTRIM(RTRIM(cost_center_code))) = UPPER(CAST(? AS NVARCHAR(50))) AND id <> ?',
            array($n['code'], (int) $n['id'])
        );
        if (!empty($dup)) {
            $this->fail('Cost center code already exists.', 51010);
        }

        $this->write(function ($db) use ($n) {
            $db->set('cost_center_code', $n['code'])
                ->set('cost_center_name', $n['name'])
                ->set('category', $n['category'])
                ->set('is_active', (int) $n['is_active'])
                ->set('updated_date', 'GETDATE()', false)
                ->set('updated_by', $n['updated_by'])
                ->where('id', (int) $n['id']);
            return $db->update('dbo.tbl_cost_center');
        });
    }
}
