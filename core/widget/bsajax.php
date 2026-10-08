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
            if (!is_object($obj) || !method_exists($obj, 'set_options')) {
                // ponytail: 配置对象行损坏（如被 Typecho 1.3.0 升级脚本转 JSON）时返回明确 JSON，避免 500 触发前端"防火墙"误导提示
                $this->response->throwJson([
                    'data' => ['notice' => '配置对象未初始化，请重新打开主题设置页面后重试', 'errors' => []],
                    'success' => false
                ]);
            }
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