<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once APPPATH . 'core/MY_Model.php';

class Portal_model extends MY_Model
{
    public function list_favorite_modules($userId)
    {
        $this->db->select('module_name, module_route, module_group, created_date', false)
            ->from('dbo.tbl_user_favorite_module')
            ->where('user_id', $userId)
            ->order_by('created_date', 'DESC');
        return $this->rows();
    }

    public function toggle_favorite_module($userId, $moduleName, $moduleRoute, $moduleGroup = null)
    {
        $existing = $this->query_rows(
            'SELECT TOP 1 1 AS found FROM dbo.tbl_user_favorite_module WHERE user_id = ? AND module_route = ?',
            array($userId, $moduleRoute)
        );

        if (!empty($existing)) {
            $this->write(function ($db) use ($userId, $moduleRoute) {
                return $db->delete('dbo.tbl_user_favorite_module', array('user_id' => $userId, 'module_route' => $moduleRoute));
            });
            return array('is_favorite' => 0);
        }

        $this->write(function ($db) use ($userId, $moduleName, $moduleRoute, $moduleGroup) {
            return $db->insert('dbo.tbl_user_favorite_module', array(
                'user_id' => $userId,
                'module_name' => $moduleName,
                'module_route' => $moduleRoute,
                'module_group' => $moduleGroup,
            ));
        });
        return array('is_favorite' => 1);
    }

    public function list_modules($userId)
    {
        $this->db->select('m.[name] AS module, m.[route] AS route, mg.[name] AS module_group', false)
            ->from('BigEUsers.dbo.users u')
            ->join('BigEUsers.dbo.user_role ur', 'u.role_id = ur.id', 'inner')
            ->join('BigEUsers.dbo.user_role_detail ud', 'ud.role_id = ur.id', 'inner')
            ->join('BigEUsers.dbo.module m', 'm.id = ud.module_id', 'inner')
            ->join('BigEUsers.dbo.module_group mg', 'mg.id = m.group_id', 'inner')
            ->join('BigEUsers.dbo.portal P', 'P.id = m.portal_id', 'inner')
            ->where('u.id', $userId)
            ->where_in('mg.id', array(92, 38, 32))
            ->where('P.id', (int) config_item('portal_id'));
        return $this->rows();
    }

    public function list_module_groups($userId)
    {
        $this->db->select('mg.[name] AS module_group, mg.[icon] AS module_icon', false)
            ->from('BigEUsers.dbo.users u')
            ->join('BigEUsers.dbo.user_role ur', 'u.role_id = ur.id', 'inner')
            ->join('BigEUsers.dbo.user_role_detail ud', 'ud.role_id = ur.id', 'inner')
            ->join('BigEUsers.dbo.module m', 'm.id = ud.module_id', 'inner')
            ->join('BigEUsers.dbo.module_group mg', 'mg.id = m.group_id', 'inner')
            ->join('BigEUsers.dbo.portal P', 'P.id = m.portal_id', 'inner')
            ->where('u.id', $userId)
            ->where('P.id', (int) config_item('portal_id'))
            ->group_by(array('mg.icon', 'mg.name'))
            ->order_by('mg.name', 'DESC');
        $rows = $this->rows();
        foreach ($rows as &$row) {
            $row['module_icon'] = $this->normalize_icon(isset($row['module_icon']) ? $row['module_icon'] : '');
        }
        unset($row);
        return $rows;
    }

    private function normalize_icon($icon)
    {
        $classes = preg_split('/\s+/', trim((string) $icon), -1, PREG_SPLIT_NO_EMPTY);
        if (!$classes) {
            return 'fas fa-folder';
        }
        if (!preg_grep('/^fa[srlbd]?$/', $classes)) {
            array_unshift($classes, 'fas');
        }
        return implode(' ', array_unique($classes));
    }
}
