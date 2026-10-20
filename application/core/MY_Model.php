<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once APPPATH . 'libraries/Knet_views.php';
require_once APPPATH . 'libraries/Knet_functions.php';

class MY_Model extends CI_Model
{
    public static function clean_message($rawMessage)
    {
        $cleaned = preg_replace('/^(?:\[[^\]]*\])+\s*/', '', (string) $rawMessage);
        return ($cleaned !== null && trim($cleaned) !== '') ? trim($cleaned) : 'Something went wrong while saving. Please try again.';
    }

    protected function fail($message, $code = 50000)
    {
        throw new Exception($message, $code);
    }

    protected function rows()
    {
        $previous = $this->db->db_debug;
        $this->db->db_debug = false;
        try {
            $query = $this->db->get();
        } finally {
            $this->db->db_debug = $previous;
        }
        return $this->result_of($query);
    }

    protected function row()
    {
        $rows = $this->rows();
        return isset($rows[0]) ? $rows[0] : null;
    }

    protected function query_rows($sql, array $binds = array())
    {
        $previous = $this->db->db_debug;
        $this->db->db_debug = false;
        try {
            $query = empty($binds) ? $this->db->query($sql) : $this->db->query($sql, $binds);
        } finally {
            $this->db->db_debug = $previous;
        }
        return $this->result_of($query);
    }

    protected function query_row($sql, array $binds = array())
    {
        $rows = $this->query_rows($sql, $binds);
        return isset($rows[0]) ? $rows[0] : null;
    }

    protected function write($callable)
    {
        $previous = $this->db->db_debug;
        $this->db->db_debug = false;
        try {
            $result = call_user_func($callable, $this->db);
        } finally {
            $this->db->db_debug = $previous;
        }
        if ($result === false) {
            $this->throw_db_error();
        }
        return $result;
    }

    protected function insert_id()
    {
        $row = $this->query_row('SELECT CAST(SCOPE_IDENTITY() AS BIGINT) AS id');
        return isset($row['id']) ? $row['id'] : null;
    }

    protected function transaction($callable)
    {
        $previous = $this->db->db_debug;
        $this->db->db_debug = false;
        $this->db->trans_begin();
        try {
            $result = call_user_func($callable);
            if ($this->db->trans_status() === false) {
                $this->throw_db_error();
            }
            $this->db->trans_commit();
            $this->db->db_debug = $previous;
            return $result;
        } catch (Exception $e) {
            $this->db->trans_rollback();
            $this->db->db_debug = $previous;
            throw $e;
        }
    }

    private function result_of($query)
    {
        if (!$query) {
            $this->throw_db_error();
        }
        if (is_object($query) && method_exists($query, 'result_array')) {
            return $query->result_array();
        }
        return array();
    }

    private function throw_db_error()
    {
        $error = $this->db->error();
        throw new Exception(self::clean_message($error['message']), (int) $error['code']);
    }
}
