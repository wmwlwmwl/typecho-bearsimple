<?php
use Typecho\Db;
ob_clean();
header("HTTP/1.1 200 OK");
    /* ponytail: 端点已有 bs_same_origin_check 同源校验，移除多余的 CORS 通配符 */
    header('Content-type: application/json');
    date_default_timezone_set('PRC');
    $options = Helper::options();
    $removeChar = ["https://", "http://"]; 
    \Typecho\Widget::widget('Widget\User')->to($user);
    $db = \Typecho\Db::get();
$id = $this->user->uid;
$opts = array(
  'http'=>array(
   'method' => 'GET',
          'header' => 'Content-type: application/json',
          'timeout' => 60 * 10,
       'Connection'=>"close"
  )
);

$context = stream_context_create($opts);
    if (bs_same_origin_check()) {
        $result = [];
        if(Bsoptions('Emoji_HideDefault') == false || Bsoptions('Emoji_HideDefault') == ''){
        // ponytail: 表情图标已本地化，JSON 内存相对子路径，输出前拼站点主题 URL（原为第三方 CDN 绝对链接）
        // ponytail: str_replace("/core","") 在 Windows 反斜杠路径下不匹配导致路径错位（端点曾恒返回 null），改用 dirname(__DIR__,2) 直接定位主题根
        $rawJson = (string)file_get_contents(dirname(__DIR__, 2).'/assets/vendors/bs-emoji/bs-emoji.json', false, $context);
        $res = json_decode(str_replace('assets/vendors/bs-emoji/emoji/', $options->themeUrl.'/assets/vendors/bs-emoji/emoji/', $rawJson),true);
        $result = $res;
        }
        if(Bsoptions('Emoji_Diy') == true && Bsoptions('Emoji_DiyUrl') !== ''){
        $res2 = json_decode(file_get_contents(Bsoptions('Emoji_DiyUrl'), false, $context),true);
        if(Bsoptions('Emoji_HideDefault') == false || Bsoptions('Emoji_HideDefault') == ''){
        $result = array_merge($res,$res2);
        }
        else{
        $result = $res2;    
        }
        }
       

        exit(json_encode($result,JSON_UNESCAPED_UNICODE));

}