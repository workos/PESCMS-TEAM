<?php

/**
 * PESCMS for PHP 5.4+
 *
 * Copyright (c) 2014 PESCMS (http://www.pescms.com)
 *
 * For the full copyright and license information, please view
 * the file LICENSE.md that was distributed with this source code.
 */

namespace App\Team\GET;

class Index extends \App\Team\Common {

    public function index() {
        $notice = $this->db('notice')->field('notice_type, count(notice_type) AS total_notice')->where('notice_read = 0 AND user_id = :user_id')->group('user_id, notice_type')->select(array('user_id' => $_SESSION['team']['user_id']));
        $this->assign('notice', $notice);
        $this->assign('menu', \Model\Menu::menu($_SESSION['team']['user_group_id']));
        $this->display();
    }

    /**
     * 全体动态
     */
    public function dynamic() {

        $page = new \Expand\Team\Page;
        $page->listRows = "30";
        $total = count($this->db('dynamic AS d')->field('d.dynamic_id')->join("{$this->prefix}task AS t ON t.task_id = d.task_id")->order('dynamic_id DESC')->group('d.dynamic_id')->select());
        $count = $page->total($total);
        $page->handle();
        $list = $this->db('dynamic AS d')->join("{$this->prefix}task AS t ON t.task_id = d.task_id")->order('dynamic_id DESC')->group('d.dynamic_id')->limit("{$page->firstRow}, {$page->listRows}")->select();
        $show = $page->show();
        $this->assign('page', $show);
        $this->assign('list', $list);

        //获取更新信息
        $updateTips = $this->db('update_list')->where('update_list_read = 0')->order('update_list_type DESC')->find();
        $this->assign('updateTips', $updateTips);

        $this->assign('title', \Model\Menu::getTitleWithMenu());

        $this->getUpdate();
        $this->layout();
    }

    /**
     * 自动获取更新
     */
    private function getUpdate() {
        $version = \Model\Option::findOption('version')['value'];
        $findUpdate = \Model\Content::findContent('update_list', $version, 'update_list_pre_version');
        if (empty($findUpdate)) {
            $update = \Model\Extra::getUpdate($version);
            if ($update['status'] == '-1') {
                $this->assign('noCurl', '1');
                return false;
            }

            if ($update['status'] == '200') {
                $this->db('update_list')->insert(array('update_list_pre_version' => $version, 'update_list_version' => $update['info']['version'], 'update_list_createtime' => $update['info']['createtime'], 'update_list_type' => $update['info']['type'], 'update_list_content' => $update['info']['content']));
            }
        }
    }

    /**
     * 后台菜单
     */
    public function menuList() {
        $this->assign('menu', \Model\Menu::menu());
        $this->assign('title', \Model\Menu::getTitleWithMenu());
        $this->layout();
    }

    /**
     * 添加/编辑菜单
     */
    public function menuAction() {
        $menuId = $this->g('id');
        if (empty($menuId)) {
            $this->assign('title', $GLOBALS['_LANG']['COMMON']['ADD']);
            $this->routeMethod('POST');
        } else {
            if (!$content = \Model\Menu::findMenu($menuId)) {
                $this->error($GLOBALS['_LANG']['MENU']['NOT_EXITS_MENU']);
            }
            $this->assign($content);
            $this->assign('title', $GLOBALS['_LANG']['COMMON']['EDIT']);
            $this->routeMethod('PUT');
        }
        $this->assign('topMenu', \Model\Menu::topMenu());
        $this->assign('menu_id', $menuId);
        $this->assign('url', $this->url('Team-Index-menuAction'));
        $this->layout();
    }

    /**
     * 清空换成
     * @param type $dirName
     */
    public function clear($dirName = 'Temp') {
        if ($handle = opendir("$dirName")) {
            while (false !== ($item = readdir($handle))) {
                if ($item != "." && $item != "..") {
                    if (is_dir("$dirName/$item")) {
                        $this->clear("$dirName/$item");
                    } else {
                        if (!unlink("$dirName/$item")) {
                            $this->error("{$GLOBALS['_LANG']['INDEX']['REMOVE_FAILE_FAILE']}： $dirName/$item");
                        }
                    }
                }
            }
            closedir($handle);
            if ($dirName == 'Temp') {
                $this->success($GLOBALS['_LANG']['INDEX']['CLEAR_CACHE_SUCCESS'], $this->url('Team-Index-systemInfo'));
            }
            if (!rmdir($dirName)) {
                $this->error("{$GLOBALS['_LANG']['INDEX']['REMOVE_DIR_FAIL']}： $dirName");
            }
        }
    }

    /**
     * 注销帐号
     */
    public function logout() {
        session_destroy();
        $this->success($GLOBALS['_LANG']['INDEX']['LOGOUT'], $this->url('Team-Login-index'));
    }

}
