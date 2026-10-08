<?php
header("HTTP/1.1 200 OK");
    header("Access-Control-Allow-Origin: *");
    date_default_timezone_set('PRC');

if($_POST['action'] == 'find'){
    \Typecho\Widget::widget('Widget\User')->to($user);
if($user->hasLogin()){
$this->response->throwJson([
            'code'=> 1,
            'name'=> $this->user->screenName,
            'group'=>$this->user->group,
            'url'=> $this->options->adminUrl,
            'msg' => '已登录',
    ]);
}
else{
$this->response->throwJson([
            'code'=> 0,
            'msg' => '未登录',
    ]);
}
}
