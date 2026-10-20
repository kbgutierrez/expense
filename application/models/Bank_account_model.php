<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once APPPATH . 'core/MY_Model.php';

class Bank_account_model extends MY_Model
{
    public function find_employee_by_code($empcode)
    {
        return $this->query_row(
            'SELECT TOP 1
                U.id,
                U.empcode,
                U.lastname + \', \' + U.firstname AS fullname,
                U.designation,
                U.id AS lsbiz_id
            FROM [BigEUsers].[dbo].[users] U
            WHERE U.is_active = 1
                AND U.empcode = CAST(? AS VARCHAR(50)) COLLATE SQL_Latin1_General_CP1_CI_AI',
            array($empcode)
        );
    }

    public function list_employee_options($keyword = null)
    {
        $like = $this->keyword_like($keyword);
        $binds = array();
        $filter = '';
        if ($like !== null) {
            $binds = array($like, $like, $like);
            $filter = ' AND (
                    U.empcode LIKE CAST(? AS NVARCHAR(102)) COLLATE SQL_Latin1_General_CP1_CI_AI
                    OR U.firstname LIKE CAST(? AS NVARCHAR(102)) COLLATE SQL_Latin1_General_CP1_CI_AI
                    OR U.lastname LIKE CAST(? AS NVARCHAR(102)) COLLATE SQL_Latin1_General_CP1_CI_AI
                )';
        }
        return $this->query_rows(
            'SELECT TOP 30
                U.id,
                U.empcode,
                U.lastname + \', \' + U.firstname AS fullname,
                U.designation,
                U.id AS lsbiz_id
            FROM [BigEUsers].[dbo].[users] U
            WHERE U.is_active = 1
                AND U.empcode IS NOT NULL AND LTRIM(RTRIM(U.empcode)) <> \'\'
                AND NOT EXISTS (
                    SELECT 1 FROM dbo.tbl_bank_account_masterlist B
                    WHERE B.empcode = U.empcode AND B.is_active = 1
                )' . $filter . '
            ORDER BY U.lastname ASC, U.firstname ASC',
            $binds
        );
    }

    public function list_masterlist($keyword = null, $cursorId = null, $page = 1, $pageSize = 20)
    {
        if ($page !== null && (int) $page < 1) {
            $page = 1;
        }
        if ($pageSize !== null && (int) $pageSize < 1) {
            $pageSize = 20;
        }
        if ($pageSize === null) {
            $this->fail('A TOP or FETCH clause contains an invalid value.', 1014);
        }
        $page = $page === null ? null : (int) $page;
        $pageSize = (int) $pageSize;
        $like = $this->keyword_like($keyword);

        $countBinds = array();
        $countRow = $this->query_row(
            'SELECT COUNT(*) AS total_count
            FROM dbo.tbl_bank_account_masterlist B' . $this->user_apply() . '
            WHERE 1 = 1' . $this->masterlist_filter($like, $countBinds),
            $countBinds
        );
        $totalCount = (int) $countRow['total_count'];

        $columns = 'B.id, B.lsbiz_id, B.empcode,
                U.lastname + \', \' + U.firstname AS fullname,
                B.bank_account, B.account_number, B.is_active,
                B.created_by, B.created_date, B.updated_by, B.updated_date,
                ' . $totalCount . ' AS total_count';

        $binds = array();
        if ($cursorId !== null) {
            $binds[] = (int) $cursorId;
            return $this->query_rows(
                'SELECT TOP (' . $pageSize . ') ' . $columns . '
                FROM dbo.tbl_bank_account_masterlist B' . $this->user_apply() . '
                WHERE B.id < ?' . $this->masterlist_filter($like, $binds) . '
                ORDER BY B.id DESC',
                $binds
            );
        }

        if ($page === null) {
            $this->fail('The number of rows provided for a OFFSET clause must be an integer.', 10743);
        }
        $offset = ((float) $page - 1) * (float) $pageSize;
        if ($offset > 2147483647 || $offset < -2147483648) {
            $this->fail('Arithmetic overflow error converting expression to data type int.', 8115);
        }
        return $this->query_rows(
            'SELECT ' . $columns . '
            FROM dbo.tbl_bank_account_masterlist B' . $this->user_apply() . '
            WHERE 1 = 1' . $this->masterlist_filter($like, $binds) . '
            ORDER BY B.id DESC
            OFFSET ' . (int) $offset . ' ROWS FETCH NEXT ' . $pageSize . ' ROWS ONLY',
            $binds
        );
    }

    public function reveal_account_number($id)
    {
        return $this->query_row(
            'SELECT B.id, B.empcode, U.lastname + \', \' + U.firstname AS fullname, B.bank_account, B.account_number
            FROM dbo.tbl_bank_account_masterlist B' . $this->user_apply() . '
            WHERE B.id = ?',
            array($id === null ? null : (int) $id)
        );
    }

    public function toggle_active($id, $isActive, $userId)
    {
        $typed = $this->query_row(
            'EXEC sp_executesql N\'SELECT @Id AS Id, @IsActive AS IsActive, @UserId AS UserId\', N\'@Id INT, @IsActive BIT, @UserId INT\', ?, ?, ?',
            array($id, $isActive, $userId)
        );
        $id = $typed['Id'];
        $isActive = $typed['IsActive'];
        $userId = $typed['UserId'];

        $this->write(function ($db) use ($id, $isActive, $userId) {
            $db->set('is_active', $isActive)
                ->set('updated_by', $userId)
                ->set('updated_date', 'GETDATE()', false)
                ->where('id', $id);
            return $db->update('dbo.tbl_bank_account_masterlist');
        });
    }

    private function keyword_like($keyword)
    {
        if ($keyword === null) {
            return null;
        }
        $keyword = (string) $keyword;
        if (preg_match('/[^\x00-\x7F]/', $keyword)) {
            $row = $this->query_row('SELECT CAST(? AS NVARCHAR(100)) AS v', array($keyword));
            $keyword = (string) $row['v'];
        } else {
            $keyword = substr($keyword, 0, 100);
        }
        $trimmed = trim($keyword, ' ');
        if ($trimmed === '') {
            return null;
        }
        return mb_substr('%' . $trimmed . '%', 0, 102, 'UTF-8');
    }

    private function user_apply()
    {
        return ' OUTER APPLY (
                SELECT TOP 1 U2.firstname, U2.lastname
                FROM [BigEUsers].[dbo].[users] U2
                WHERE U2.empcode = B.empcode
                ORDER BY U2.is_active DESC, U2.id DESC
            ) U ';
    }

    private function masterlist_filter($like, array &$binds)
    {
        if ($like === null) {
            return '';
        }
        $binds[] = $like;
        $binds[] = $like;
        $binds[] = $like;
        $binds[] = $like;
        return ' AND (
                B.empcode LIKE CAST(? AS NVARCHAR(102)) COLLATE SQL_Latin1_General_CP1_CI_AI
                OR B.bank_account LIKE CAST(? AS NVARCHAR(102)) COLLATE SQL_Latin1_General_CP1_CI_AI
                OR U.lastname LIKE CAST(? AS NVARCHAR(102)) COLLATE SQL_Latin1_General_CP1_CI_AI
                OR U.firstname LIKE CAST(? AS NVARCHAR(102)) COLLATE SQL_Latin1_General_CP1_CI_AI
            )';
    }
}
