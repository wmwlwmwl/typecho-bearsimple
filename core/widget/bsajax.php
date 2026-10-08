<?php
if(!class_exists('CSF')){
    require_once Helper::options()->pluginDir('BsCore').'/functions/defines.php';
    require_once Helper::options()->pluginDir('BsCore').'/bsoptions-framework.php';
}
use Typecho\Db;
use Utils\Helper;
ob_clean();
header("HTTP/1.1 200 OK");
    header("Access-Control-Allow-Origin: *");
    header('Content-type: application/json');
    date_default_timezone_set('PRC');
$user = \Typecho\Widget::widget('Widget\User');
        // ponytail: 该端点可改写全站主题配置，属高危操作，收紧为管理员权限（原先任意登录用户均可）
        if (!$user->hasLogin() || !$user->pass('administrator', true)) {
            $data = [
                'data' => [
                    'notice' => '未登录',
                    'errors' => []
                ],
                'success' => false
            ];
            $this->response->throwJson($data);
        }

        $action = $this->request->get('action', null);
        if (!$action) {
            $data = [
                'data' => [
                    'notice' => '参数错误',
                    'errors' => []
                ],
                'success' => false
            ];
            $this->response->throwJson($data);
        }
        if (preg_match('/csf_(.*)_ajax_save/i', $action)) {
            $data = json_decode($_POST['data'], true);
            $plugin = $data['plugin'];


            $obj = get_bs_key_params($plugin);
            $ret = $obj->set_options(true);

            if ($ret and empty($obj->errors)) {
                $data = [
                    'data' => [
                        'notice' => $obj->notice,
                        'errors' => $obj->errors
                    ],
                    'success' => true
                ];
                $this->response->throwJson($data);
            } else {
                $data = [
                    'data' => [
                        'notice' => $obj->notice,
                        'errors' => $obj->errors
                    ],
                    'success' => true
                ];
                $this->response->throwJson($data);
            }

        } else {
            $action = str_replace('-', '_', $action);
            CSF::include_plugin_file('functions/actions.php');
            $action($this->response);
        }