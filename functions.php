<?php

use Typecho\Common;
use Utils\Helper;
use Widget\Options;
if(!class_exists('CSF')){
    require_once Helper::options()->pluginDir('BsCore').'/bsoptions-framework.php';
}

if (!class_exists('bsOptions')){
    require_once \Utils\Helper::options()->pluginDir('BsCore').'/bsOptions.php';
}
require_once('core/func.php');
function themeVersion()
        {
            return '2.9.9.20261008';
        }


function themeVersionOnly()
        {
            return '2.9.9';
        }

// ponytail: BsCore 未启用时配置降级为空数组，避免 fatal；升级路径：要求启用 BsCore
$options = class_exists('bsOptions') ? bsOptions::getInstance()::get_option( 'bearsimple' ) : array();


function Bsoptions($key, $default = false){
    // ponytail: 整包静态缓存，原先底层 get_option() 每次调用都查库，单页触发数百条相同 SQL；
    // 同一请求内配置保存后不可见（下次请求生效），升级路径：保存配置后清空该缓存
    static $cache = null;
    if (!class_exists('bsOptions')) {
        return $default;
    }
    if ($cache === null) {
        $cache = bsOptions::getInstance()::get_option( 'bearsimple' );
    }
    return $cache[$key] ?? $default;
}

/**解析表情**/
$emo = false;
function reEmo($comment,$type){
    global $emo;
    $options = Helper::options();
    if(!$emo){
        $opts = array(
  'http'=>array(
   'method' => 'GET',
          'header' => 'Content-type: application/json',
          'timeout' => 60,
       'Connection'=>"close"
  )
);
$context = stream_context_create($opts);

        if(Bsoptions('Emoji_HideDefault') == false || Bsoptions('Emoji_HideDefault') == ''){
        $res = json_decode(file_get_contents(__DIR__.'/assets/vendors/bs-emoji/bs-emoji.json', false, $context),true) ?: [];
        $emo = $res;
        }
        if(Bsoptions('Emoji_Diy') == true && Bsoptions('Emoji_DiyUrl') !== ''){
        $res2 = json_decode(file_get_contents(Bsoptions('Emoji_DiyUrl'), false, $context),true) ?: [];
        if(Bsoptions('Emoji_HideDefault') == false || Bsoptions('Emoji_HideDefault') == ''){
        $emo = array_merge($res,$res2);
        }
        else{
        $emo = $res2;    
        }
        }
        
        
    }


    foreach ($emo as $v){
        if($v['category'] !== ''){
                if($type == 'comment'){
                $comment = str_replace($v['data'], '<img width="30" src='.$v['icon'] .''.' loading="lazy" style="vertical-align:bottom">', $comment);
                }
                elseif($type == 'reply'){
                $comment = str_replace($v['data'], '<img width="20" src='.$v['icon'] .''.' loading="lazy" style="vertical-align:middle">', $comment);
                }
                elseif($type == 'circle'){
                $comment = str_replace($v['data'], '<img width="30" src='.$v['icon'] .''.' loading="lazy" style="vertical-align:middle">', $comment);
                $comment = str_replace($v['codex'], '<img width="30" src='.$v['icon'] .''.' loading="lazy" style="vertical-align:middle">', $comment);
                }
            
                else{
                $comment = str_replace($v['data'], '<img width="30" src='.$v['icon'] .''.' loading="lazy" style="vertical-align:middle">', $comment);                    
                }
        }
    }
    //当评论没有私密评论时去除私密评论识别标签
    $comment = str_replace('@私密@', '', $comment);
    return $comment;
}

function reEmoPost($post){
    global $emo;
$options = Helper::options();
    if(!$emo){
        $opts = array(
  'http'=>array(
   'method' => 'GET',
          'header' => 'Content-type: application/json',
          'timeout' => 15, // ponytail: 外链表情 json 限 15s，失败走 $emo 空数组兜底
       'Connection'=>"close"
  )
);
$context = stream_context_create($opts);

        if(Bsoptions('Emoji_HideDefault') == false || Bsoptions('Emoji_HideDefault') == ''){
        // ponytail: 直接读本地文件，原先走站点 URL 回环 HTTP 每请求一次
        $res = json_decode(file_get_contents(__DIR__.'/assets/vendors/bs-emoji/bs-emoji.json', false, $context),true) ?: [];
        $emo = $res;
        }
        if(Bsoptions('Emoji_Diy') == true && Bsoptions('Emoji_DiyUrl') !== ''){
        $res2 = json_decode(file_get_contents(Bsoptions('Emoji_DiyUrl'), false, $context),true) ?: [];
        if(Bsoptions('Emoji_HideDefault') == false || Bsoptions('Emoji_HideDefault') == ''){
        $emo = array_merge($res,$res2);
        }
        else{
        $emo = $res2;    
        }
        }
        
        
    }



    foreach ($emo as $v){
        if($v['category'] !== ''){
                
                $post = str_replace($v['data'], '<img style="display:inline-block;margin: 0;padding: 0;width:30px;height:30px;vertical-align: middle;" class="emoji" src="'.$v['icon'] .'"'.' loading="lazy">', $post);
        }
    }
    return $post;
}




require_once(__DIR__.'/core/admin-options.php');
function themeConfig($form)
{

   ?>
       <?php $all = \Typecho\Plugin::export();
       \Widget\Security::alloc()->to($security);?>
<?php if (!array_key_exists('BsCore', $all['activated'])) : ?>
   <div class="update-check message error"><p>检测到您未安装BsCore插件，主题尚处于封印状态，您需要安装启用BsCore核心插件后方能解除封印QAQ！<br>若您还未下载核心插件，可戳这里进行下载并将核心插件放入/usr/plugins，当下方出现解除封印按钮时点击按钮后即可解除封印~~~
   <?php if(is_dir(Helper::options()->pluginDir('BsCore'))):?><br><button onclick="window.location.href='<?php $security->index('/action/plugins-edit?activate=BsCore'); ?>'"  class="btn">
解除封印</button></p>
</div>
<?php endif;?>
   <?php else:?>
   
<style>
.body.container{
    max-width:100%!important;
}
</style>
        <link href="<?php echo Helper::options()->themeUrl;?>/assets/vendors/toastr.js/toastr.min.css" rel="stylesheet">
	<script src="<?php echo Helper::options()->themeUrl;?>/assets/vendors/toastr.js/toastr.min.js"></script>
	<link href="<?php echo Helper::options()->themeUrl;?>/assets/vendors/driver.js/driver.min.css" rel="stylesheet">
	<script src="<?php echo Helper::options()->themeUrl;?>/assets/vendors/driver.js/driver.min.js.iife.js"></script>
        
<?php

    $params = [
        'args'=> [
            'framework_title' => 'Bearsimple v'.themeVersionOnly(),
            'footer_text' => '自豪的使用BearSimple主题!',
        ]
    ];
    ?>
    
<script>

           window.deactivateURL = '<?php $security->index('/action/plugins-edit?deactivate=BsCore'); ?>';
           window.activateURL = '<?php $security->index('/action/plugins-edit?activate=BsCore'); ?>';
           window.CacheUrl = '<?php echo Common::url('clean-cache', Options::alloc()->index); ?>';
           window.siteName = "<?php echo Helper::options()->title; ?>";
           window.siteDesc = "<?php echo Helper::options()->description; ?>";
           window.siteUrl = "<?php echo Helper::options()->siteUrl; ?>";
           window.useTheme = 'bearsimple';
           window.siteToken = '<?php echo md5Encode(Helper::options()->siteUrl); ?>7bae2123bear';
        </script>
        <?php
    CSF::setup('bearsimple', $params);
    
    ?>
   
       <?php endif; ?>
       <?php
       CSF::setTypechoOptionForm($form);
       ?>
              <?php if (array_key_exists('BsCore', $all['activated'])) : ?>
          <style>

        .popup{
            all:unset;
            font-size:0; overflow:hidden;
        }
        .message{
            all:unset;
            font-size:0; overflow:hidden;
        }
        .message a{
            all:unset;
            font-size:0; overflow:hidden;
        }
        .success{
            all:unset;
            font-size:0; overflow:hidden;
        }
        .success a{
            all:unset;
            font-size:0; overflow:hidden;
        }
        .message.popup.success{
            all:unset;
            font-size:0; overflow:hidden;
        }
        </style>
<?php endif; ?>

       <?php if (!array_key_exists('BsCore', $all['activated'])) : ?>
       
    <script src="<?php echo Helper::options()->themeUrl; ?>assets/js/jquery.min.js"></script>
    

   <script>
       $('#wpwrap').hide();
   </script>
   <?php endif; ?>
    <?php
} ?>