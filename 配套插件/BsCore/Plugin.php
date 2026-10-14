<?php
namespace TypechoPlugin\BsCore;
error_reporting(0);
/* ponytail: 原无条件 session_start 使所有请求持有会话文件锁直至请求结束，同会话请求被串行化；
   改为仅当存在会话 Cookie 时 read_and_close：评论 filter() 只需读 $_SESSION['token']（read_and_close 后 $_SESSION 仍可读），
   写 token 的 getCommentAction/friendlink 端点各自自行 start（重新打开可写），匿名首访不再生成会话文件 */
if (isset($_COOKIE[session_name()])) {
    session_start(['read_and_close' => true]);
}
use Utils\Helper;
use Typecho\Plugin\PluginInterface;
use Typecho\Widget\Helper\Form;
use Typecho\{Plugin\Exception, Widget, Db, Widget\Request as WidgetRequest};
use \CSF as CSF;
use bsOptions;
use Widget\Options;
use Typecho\Common;
use Typecho\Router;
use Widget\Archive;
use Widget\Contents\Post\Admin;
use Widget\Contents\Post\Edit;
use Widget\Feedback;
use Widget\Service;
use Widget\Upload;
use CURLFile;
use ReflectionClass;
use Utils\PasswordHash;
use Typecho\Widget\Response as WidgetResponse;
use Utils\Markdown as Markdown;
require_once 'modules/DX_Captcha/CaptchaClient.php';
use CaptchaClient;
require_once 'modules/Markdown/Parser.php';
require_once 'modules/Tinypng/Tinify/Exception.php';
require_once 'modules/Tinypng/Tinify/ResultMeta.php';
require_once 'modules/Tinypng/Tinify/Result.php';
require_once 'modules/Tinypng/Tinify/Source.php';
require_once 'modules/Tinypng/Tinify/Client.php';
require_once 'modules/Tinypng/Tinify.php';
require_once 'modules/aliyun_oss/aliyun-oss-php-sdk-2.6.0.phar';
use OSS\OssClient;
use OSS\Core\OssException;
require_once 'modules/qiniu_kodo/autoload.php';
use Qiniu\Auth;
use Qiniu\Storage\BucketManager;
use Qiniu\Storage\UploadManager;
require_once 'modules/upyun_uss/vendor/autoload.php';
use Upyun\Upyun;
use Upyun\Config;
require_once 'modules/huawei_obs/vendor/autoload.php';
require_once 'modules/huawei_obs/obs-autoloader.php';
use Obs\ObsClient;
require_once 'modules/baidu_bos/BaiduBce.phar';
use BaiduBce\Services\Bos\BosClient;
use BaiduBce\Services\Bos\BosOptions;

require_once 'modules/cache/autoload.php';
/**
 * BearSimple主题核心插件
 * <br>安装后无需进行其他设置
 * @package BsCore
 * @author BearNotion
 * @version v2.9.9-release
 * @link https://www.bearnotion.ru/
 *
 */



if (!defined('__TYPECHO_ROOT_DIR__')) exit;
if (!defined('pluginName')) {
  define('pluginName', 'BsCore');
}
require_once 'bsoptions-framework.php';
class Plugin implements PluginInterface
{
    public static $action = 'bs-ajax';
    #邮件
    private static $_isMailLog  = false;
    private static $_adapter    = false;
    
    #上传文件目录
  const UPLOAD_DIR = 'usr/uploads';
  const PLUGIN_DIR = '/usr/plugins/BsCore/';
public static $cache = null;
	public static $html = null;
	public static $path = null;
	public static $sys_config = null;
	public static $plugin_config = null;
	public static $request = null;
	public static $passed = false;
    public static $_panel  = 'BsCore/pages/panel.php';
    
     public static function getNS(): string
    {
        return __NAMESPACE__;
    }
    
    public static function activate()
    {
        /* 
         *
         *  初始化路由和方法
         *
         */
        bsRouter::initRouter();
        Helper::addPanel(0, self::$_panel, 'BearSimple主题扩展控制', 'BearSimple主题扩展控制面板', 'administrator');
        if(get_option('bearsimple')['goLinkOpen'] == true){
        //外链转内链钩子
        Helper::addRoute('go', '/go/[key]/', 'TypechoPlugin\BsCore\Action', 'bsshortlink');
        \Typecho\Plugin::factory('Widget_Abstract_Contents')->contentEx_12 = [__CLASS__, 'replace'];
        \Typecho\Plugin::factory('Widget_Abstract_Contents')->excerptEx_12 = [__CLASS__, 'replace'];
        }
        \Typecho\Plugin::factory('index.php')->begin = [__CLASS__, 'initevent'];
        \Typecho\Plugin::factory('admin/header.php')->header_999 = [__CLASS__, 'enqueue_style'];
        \Typecho\Plugin::factory('admin/footer.php')->end_999 = [__CLASS__, 'enqueue_script'];
         if(get_option('bearsimple')['Cache'] == true && !empty(get_option('bearsimple')['Cache_choose']) && get_option('bearsimple')['Cache_choose'] !== '1'){
        //缓存功能钩子
        Helper::addRoute('clean-cache', '/clean-cache', 'TypechoPlugin\BsCore\Action', 'clean_cache');
		\Typecho\Plugin::factory('index.php')->begin = array(__CLASS__, 'C');
		\Typecho\Plugin::factory('index.php')->end = array(__CLASS__, 'S');
        \Typecho\Plugin::factory('Widget_Contents_Post_Edit')->finishPublish = [__CLASS__, 'post_update'];
        \Typecho\Plugin::factory('Widget_Contents_Page_Edit')->finishPublish = [__CLASS__, 'post_update'];
        \Typecho\Plugin::factory('Widget_Contents_Post_Edit')->delete = [__CLASS__, 'post_del_update'];
        \Typecho\Plugin::factory('Widget_Contents_Page_Edit')->delete = [__CLASS__, 'post_del_update'];
        \Typecho\Plugin::factory('Widget_Feedback')->finishComment_1 = [__CLASS__, 'comment_update'];
		\Typecho\Plugin::factory('Widget_Comments_Edit')->finishDelete = [__CLASS__, 'comment_update2'];
		\Typecho\Plugin::factory('Widget_Comments_Edit')->finishEdit = [__CLASS__, 'comment_update2'];
		\Typecho\Plugin::factory('Widget_Comments_Edit')->finishComment = [__CLASS__, 'comment_update2'];
        \Typecho\Plugin::factory('Widget_Abstract_Contents')->contentEx = [__CLASS__, 'cache_contentEx'];
        \Typecho\Plugin::factory('BsCache.Widget_Cache')->getCache = [__CLASS__, 'BsCache_getCache'];
        \Typecho\Plugin::factory('BsCache.Widget_Cache')->setCache = [__CLASS__, 'BsCache_setCache'];
         }
         if(get_option('bearsimple')['Cache'] == true && !empty(get_option('bearsimple')['Cache_choose']) && get_option('bearsimple')['Cache_choose'] == '1'){
         \Typecho\Plugin::factory('index.php')->begin = [__CLASS__, 'getCache_file'];
        \Typecho\Plugin::factory('index.php')->end = [__CLASS__, 'setCache_file'];
         }
        if(get_option('bearsimple')['Editors'] == '2'){
        \Typecho\Plugin::factory('admin/write-post.php')->bottom_1000 =  [__CLASS__,'btbutton'];
        \Typecho\Plugin::factory('admin/write-page.php')->bottom_1000 = [__CLASS__, 'btbutton'];
        }
        else{
        \Typecho\Plugin::factory('admin/write-post.php')->richEditor_1000 = [__CLASS__, 'Editor'];
        \Typecho\Plugin::factory('admin/write-page.php')->richEditor_1000 = [__CLASS__, 'Editor'];
        }
        \Typecho\Plugin::factory('Widget_Feedback')->comment = [__CLASS__, 'filter'];
        \Typecho\Plugin::factory('Widget_Feedback')->finishComment_134 = [__CLASS__, 'filter2'];
          if(get_option('bearsimple')['Sticky'] == true){
        //文章置顶
        \Typecho\Plugin::factory('Widget_Archive')->indexHandle =  [__CLASS__, 'sticky'];
		\Typecho\Plugin::factory('Widget_Archive')->categoryHandle =[__CLASS__, 'stickyC'];
          }
          if(get_option('bearsimple')['seo_push'] == true){
          \Typecho\Plugin::factory('Widget_Contents_Post_Edit')->finishPublish = [__CLASS__, 'seo_publishpush'];
          }
          //LskyPro
          if(get_option('bearsimple')['bucket_choose'] == '8'){
\Typecho\Plugin::factory('Widget_Upload')->uploadHandle = [__CLASS__, 'lsky_uploadHandle'];
   \Typecho\Plugin::factory('Widget_Upload')->modifyHandle = [__CLASS__, 'lsky_modifyHandle'];
    \Typecho\Plugin::factory('Widget_Upload')->deleteHandle = [__CLASS__, 'lsky_deleteHandle'];
    \Typecho\Plugin::factory('Widget_Upload')->attachmentHandle = [__CLASS__, 'lsky_attachmentHandle'];
    \Typecho\Plugin::factory('Widget_Upload')->attachmentDataHandle = [__CLASS__, 'lsky_attachmentDataHandle'];
        }
        //腾讯云COS
        if(get_option('bearsimple')['bucket_choose'] == '2'){
\Typecho\Plugin::factory('Widget_Upload')->uploadHandle = [__CLASS__, 'uploadHandle'];
   \Typecho\Plugin::factory('Widget_Upload')->modifyHandle = [__CLASS__, 'modifyHandle'];
    \Typecho\Plugin::factory('Widget_Upload')->deleteHandle = [__CLASS__, 'deleteHandle'];
    \Typecho\Plugin::factory('Widget_Upload')->attachmentHandle = [__CLASS__, 'attachmentHandle'];
    \Typecho\Plugin::factory('Widget_Upload')->attachmentDataHandle = [__CLASS__, 'attachmentDataHandle'];
    \Typecho\Plugin::factory('Widget_Archive')->beforeRender = [__CLASS__, 'Widget_Archive_beforeRender'];
        }
        //阿里云COS
        if(get_option('bearsimple')['bucket_choose'] == '3'){
\Typecho\Plugin::factory('Widget_Upload')->uploadHandle = [__CLASS__, 'ali_uploadHandle'];
   \Typecho\Plugin::factory('Widget_Upload')->modifyHandle = [__CLASS__, 'ali_modifyHandle'];
    \Typecho\Plugin::factory('Widget_Upload')->deleteHandle = [__CLASS__, 'ali_deleteHandle'];
    \Typecho\Plugin::factory('Widget_Upload')->attachmentHandle = [__CLASS__, 'ali_attachmentHandle'];
    \Typecho\Plugin::factory('Widget_Upload')->attachmentDataHandle = [__CLASS__, 'ali_attachmentDataHandle'];
        }
        //七牛云Kodo
        if(get_option('bearsimple')['bucket_choose'] == '4'){
\Typecho\Plugin::factory('Widget_Upload')->uploadHandle = [__CLASS__, 'qiniu_uploadHandle'];
   \Typecho\Plugin::factory('Widget_Upload')->modifyHandle = [__CLASS__, 'qiniu_modifyHandle'];
    \Typecho\Plugin::factory('Widget_Upload')->deleteHandle = [__CLASS__, 'qiniu_deleteHandle'];
    \Typecho\Plugin::factory('Widget_Upload')->attachmentHandle = [__CLASS__, 'qiniu_attachmentHandle'];
    \Typecho\Plugin::factory('Widget_Upload')->attachmentDataHandle = [__CLASS__, 'qiniu_attachmentDataHandle'];
        }
        //又拍云USS
        if(get_option('bearsimple')['bucket_choose'] == '5'){
\Typecho\Plugin::factory('Widget_Upload')->uploadHandle = [__CLASS__, 'upyun_uploadHandle'];
   \Typecho\Plugin::factory('Widget_Upload')->modifyHandle = [__CLASS__, 'upyun_modifyHandle'];
    \Typecho\Plugin::factory('Widget_Upload')->deleteHandle = [__CLASS__, 'upyun_deleteHandle'];
    \Typecho\Plugin::factory('Widget_Upload')->attachmentHandle = [__CLASS__, 'upyun_attachmentHandle'];
    \Typecho\Plugin::factory('Widget_Upload')->attachmentDataHandle = [__CLASS__, 'upyun_attachmentDataHandle'];
        }
        //华为云OBS
        if(get_option('bearsimple')['bucket_choose'] == '7'){
\Typecho\Plugin::factory('Widget_Upload')->uploadHandle = [__CLASS__, 'hw_uploadHandle'];
   \Typecho\Plugin::factory('Widget_Upload')->modifyHandle = [__CLASS__, 'hw_modifyHandle'];
    \Typecho\Plugin::factory('Widget_Upload')->deleteHandle = [__CLASS__, 'hw_deleteHandle'];
    \Typecho\Plugin::factory('Widget_Upload')->attachmentHandle = [__CLASS__, 'hw_attachmentHandle'];
    \Typecho\Plugin::factory('Widget_Upload')->attachmentDataHandle = [__CLASS__, 'hw_attachmentDataHandle'];
        }
        //百度云BOS
        if(get_option('bearsimple')['bucket_choose'] == '6'){
\Typecho\Plugin::factory('Widget_Upload')->uploadHandle = [__CLASS__, 'bd_uploadHandle'];
   \Typecho\Plugin::factory('Widget_Upload')->modifyHandle = [__CLASS__, 'bd_modifyHandle'];
    \Typecho\Plugin::factory('Widget_Upload')->deleteHandle = [__CLASS__, 'bd_deleteHandle'];
    \Typecho\Plugin::factory('Widget_Upload')->attachmentHandle = [__CLASS__, 'bd_attachmentHandle'];
    \Typecho\Plugin::factory('Widget_Upload')->attachmentDataHandle = [__CLASS__, 'bd_attachmentDataHandle'];
        }
        //代码高亮
        \Typecho\Plugin::factory('Widget_Archive')->header = [__CLASS__, 'headlink'];
       \Typecho\Plugin::factory('Widget_Archive')->footer = [__CLASS__, 'footlink'];
       //登陆验证逻辑
       //\Typecho\Plugin::factory('admin/header.php')->header_1081 = [__CLASS__, 'loginverify'];
         //邮件
         if(get_option('bearsimple')['CommentNotify__Open'] == true && get_option('bearsimple')['Smtp_open'] == true){
         \Typecho\Plugin::factory('Widget_Feedback')->finishComment_28654 = [__CLASS__,'parseComment'];
        \Typecho\Plugin::factory('Widget_Comments_Edit')->finishComment_25674 = [__CLASS__,'parseComment'];
        \Typecho\Plugin::factory('Widget_Comments_Edit')->mark_25544 = [__CLASS__,'parseCommentMark'];
         }
         if(get_option('bearsimple')['Smtp_open'] == true){
         Helper::addAction(self::$action, 'TypechoPlugin\BsCore\Action');
         }
         if(get_option('bearsimple')['Comment_private'] == true){
         \Typecho\Plugin::factory('Widget_Abstract_Comments')->contentEx = [__CLASS__,'secretComment'];
         }
         if(get_option('bearsimple')['Cate_Encrypt_open'] == true && get_option('bearsimple')['Cate_Encrypt_hide'] == false){
         \Typecho\Plugin::factory('Widget_Archive')->indexHandle_133 = [__CLASS__,'CateFilter'];
         }
         //当腾讯云COS存储未开启时且Tinypng压缩开启
         if(get_option('bearsimple')['Tinypng_open'] == true && get_option('bearsimple')['bucket_choose'] !== '2'&& get_option('bearsimple')['bucket_choose'] !== '3'&& get_option('bearsimple')['bucket_choose'] !== '4'&& get_option('bearsimple')['bucket_choose'] !== '5'&& get_option('bearsimple')['bucket_choose'] !== '6'&& get_option('bearsimple')['bucket_choose'] !== '7'){
        \Typecho\Plugin::factory('Widget_Upload')->uploadHandle = [__CLASS__,'tinypng_uploadHandle'];
         }
         if(get_option('bearsimple')['Tinypng_open'] == true){
        \Typecho\Plugin::factory('admin/write-post.php')->bottom = [__CLASS__,'tinypng_bottom'];
         }
         \Typecho\Plugin::factory('admin/write-post.php')->bottom_99 = [__CLASS__,'tags_bottom'];
         //当开启用户中心功能
         if(get_option('bearsimple')['UserCenterOpen'] == true){
         \Typecho\Plugin::factory('Widget_Register')->register_99 = [__CLASS__,'bregister'];   
         \Typecho\Plugin::factory('Widget_Register')->finishRegister_99 = [__CLASS__,'bregister_finish'];   
         }
         \Typecho\Plugin::factory('Widget_Abstract_Contents')->markdown = [__CLASS__, 'markdownparse'];
         \Typecho\Plugin::factory('Widget_Archive')->beforeRender_siteMaintenance_15613 = [__CLASS__, 'siteMaintenance'];
        \CSF::activateEvent();
        
        /* 
         *
         *  检测数据库并插入数据库表
         *
         */
         
         $db = \Typecho\Db::get();
          $prefix = $db->getPrefix();
         $table = $db->getPrefix() . 'bscore_friendlinks';
         $table2 = $db->getPrefix() . 'bscore_sign_data';
         $table3 = $db->getPrefix() . 'bscore_notify_data';
         $table4 = $db->getPrefix() . 'bscore_says_data';
         $table5 = $db->getPrefix() . 'bscore_memos_agree_data';
    $adapter = $db->getAdapterName();
        if ("Pdo_SQLite" === $adapter || "SQLite" === $adapter) {
            //友链表检测初始化
            $db->query(" CREATE TABLE IF NOT EXISTS " . $table . " (
			   id INTEGER PRIMARY KEY,
			   friendname TEXT,
			   friendurl TEXT,
			   friendpic TEXT,
			   frienddec TEXT,
			   contactmail TEXT,
			   status TEXT)");
			   //用户中心签到表检测初始化
			   $db->query(" CREATE TABLE IF NOT EXISTS " . $table2 . " (
			   id INTEGER PRIMARY KEY,
			   signuid TEXT,
			   signtime TEXT,
			   signcoin TEXT)");
			   //用户中心通知表检测初始化
			   $db->query(" CREATE TABLE IF NOT EXISTS " . $table3 . " (
			   id INTEGER PRIMARY KEY,
			   notifyuid TEXT,
			   notifytime TEXT,
			   notifytitle TEXT,
			   notifytext TEXT)");
			   //用户中心动态表检测初始化
			   $db->query(" CREATE TABLE IF NOT EXISTS " . $table4 . " (
			   id INTEGER PRIMARY KEY,
			   saysuid TEXT,
			   saystime TEXT,
			   saystext TEXT,
			   saysprivate TEXT,
			   sayslike TEXT DEFAULT 0)");
			   //memos点赞表检测初始化
			   $db->query(" CREATE TABLE IF NOT EXISTS " . $table5 . " (
			   id INTEGER PRIMARY KEY,
			   memosid  TEXT,
			   agree TEXT)");

			   if (!array_key_exists('coins', $db->fetchRow($db->select()->from('table.users')))) {
$db->query('ALTER TABLE ' . $prefix . 'users ADD coins TEXT NOT NULL  DEFAULT 0;');
}
if (!array_key_exists('submission', $db->fetchRow($db->select()->from('table.users')))) {
$db->query('ALTER TABLE ' . $prefix . 'users ADD submission TEXT NOT NULL  DEFAULT true;');
}
if (!array_key_exists('signature', $db->fetchRow($db->select()->from('table.users')))) {
$db->query('ALTER TABLE ' . $prefix . 'users ADD signature TEXT NOT NULL  DEFAULT 0;');
}

if (strpos($db->fetchAll($db->query("select sql from sqlite_master where type = 'table' and name = '" . $table . "'"))[0]['sql'], 'checkurl') == false) {
            $db->query('ALTER TABLE ' . $prefix . 'bscore_friendlinks ADD checkurl TEXT NOT NULL DEFAULT 0;');
        }
if (strpos($db->fetchAll($db->query("select sql from sqlite_master where type = 'table' and name = '" . $table . "'"))[0]['sql'], 'type') == false) {
            $db->query('ALTER TABLE ' . $prefix . 'bscore_friendlinks ADD type TEXT NOT NULL DEFAULT 0;');
        }
if (strpos($db->fetchAll($db->query("select sql from sqlite_master where type = 'table' and name = '" . $table . "'"))[0]['sql'], 'rejectreason') == false) {
            $db->query('ALTER TABLE ' . $prefix . 'bscore_friendlinks ADD rejectreason TEXT NULL DEFAULT NULL;');
        }
        }
        
        
         if("pgsql" === $adapter || "Pdo_Pgsql" === $adapter){
            //友链表检测初始化
            $db->query(" CREATE TABLE IF NOT EXISTS " . $table . " (
			   id INTEGER PRIMARY KEY,
			   friendname TEXT,
			   friendurl TEXT,
			   friendpic TEXT,
			   frienddec TEXT,
			   contactmail TEXT,
			   checkurl TEXT NOT NULL DEFAULT 0,
			   type TEXT NOT NULL DEFAULT 0,
			   rejectreason TEXT NULL DEFAULT NULL,
			   status TEXT)");
			   //用户中心签到表检测初始化
			   $db->query(" CREATE TABLE IF NOT EXISTS " . $table2 . " (
			   id INTEGER PRIMARY KEY,
			   signuid TEXT,
			   signtime TEXT,
			   signcoin TEXT)");
			   //用户中心通知表检测初始化
			   $db->query(" CREATE TABLE IF NOT EXISTS " . $table3 . " (
			   id INTEGER PRIMARY KEY,
			   notifyuid TEXT,
			   notifytime TEXT,
			   notifytitle TEXT,
			   notifytext TEXT)");
			   //用户中心动态表检测初始化
			   $db->query(" CREATE TABLE IF NOT EXISTS " . $table4 . " (
			   id INTEGER PRIMARY KEY,
			   saysuid TEXT,
			   saystime TEXT,
			   saystext TEXT,
			   saysprivate TEXT,
			   sayslike TEXT DEFAULT 0)");
			   //memos点赞表检测初始化
			   $db->query(" CREATE TABLE IF NOT EXISTS " . $table5 . " (
			   id INTEGER PRIMARY KEY,
			   memosid  TEXT,
			   agree TEXT)");

			   if (!array_key_exists('coins', $db->fetchRow($db->select()->from('table.users')))) {
$db->query('ALTER TABLE ' . $prefix . 'users ADD coins TEXT NOT NULL  DEFAULT 0;');
}
if (!array_key_exists('submission', $db->fetchRow($db->select()->from('table.users')))) {
$db->query('ALTER TABLE ' . $prefix . 'users ADD submission TEXT NOT NULL  DEFAULT true;');
}
if (!array_key_exists('signature', $db->fetchRow($db->select()->from('table.users')))) {
$db->query('ALTER TABLE ' . $prefix . 'users ADD signature TEXT NOT NULL  DEFAULT 0;');
}


        
        
        
        }
        
        
        
        if ("Pdo_Mysql" === $adapter || "Mysql" === $adapter || "Mysqli" === $adapter) {
            $dbConfig = null;
            if (class_exists('\Typecho\Db')) {
                $dbConfig = $db->getConfig($db::READ);
            } else {
                $dbConfig = $db->getConfig()[0];
            }
            $charset = $dbConfig->charset;
            $db->query("CREATE TABLE IF NOT EXISTS " . $table . " (
				  `id` int(8) NOT NULL AUTO_INCREMENT,
				  `friendname` varchar(1000) NOT NULL,
			      `friendurl` varchar(1000) NOT NULL,
			      `friendpic` varchar(1000) NOT NULL,
			      `frienddec` varchar(1000) NOT NULL,
			      `contactmail` varchar(1000) DEFAULT NULL,
			      `status` varchar(1000) NOT NULL,
				  PRIMARY KEY (`id`)
				) DEFAULT CHARSET=$charset AUTO_INCREMENT=1");
				
			$db->query(" CREATE TABLE IF NOT EXISTS " . $table2 . " (
			   `id` int(8) NOT NULL AUTO_INCREMENT,
			   `signuid` varchar(1000) NOT NULL,
			   `signtime` varchar(1000) NOT NULL,
			   `signcoin` varchar(1000) NOT NULL,
			  PRIMARY KEY (`id`)
				) DEFAULT CHARSET=$charset AUTO_INCREMENT=1");
				
			$db->query(" CREATE TABLE IF NOT EXISTS " . $table3 . " (
			   `id` int(8) NOT NULL AUTO_INCREMENT,
			   `notifyuid` varchar(1000) NOT NULL,
			   `notifytime` varchar(1000) NOT NULL,
			   `notifytitle` varchar(1000) NOT NULL,
			   `notifytext` varchar(1000) NOT NULL,
			  PRIMARY KEY (`id`)
				) DEFAULT CHARSET=$charset AUTO_INCREMENT=1");
				
			   $db->query(" CREATE TABLE IF NOT EXISTS " . $table4 . " (
			   `id` int(8) NOT NULL AUTO_INCREMENT,
			   `saysuid` varchar(1000) NOT NULL,
			   `saystime`  varchar(1000) NOT NULL,
			   `saystext`  varchar(1000) NOT NULL,
			   `saysprivate`  varchar(1000) NOT NULL,
			   `sayslike`  varchar(1000) NOT NULL DEFAULT 0,
			  PRIMARY KEY (`id`)
				) DEFAULT CHARSET=$charset AUTO_INCREMENT=1");
				
				//memos点赞表检测初始化
			   $db->query(" CREATE TABLE IF NOT EXISTS " . $table5 . " (
			   `id` int(10) NOT NULL AUTO_INCREMENT,
			   `memosid`  varchar(150) NOT NULL,
			   `agree`  varchar(1000) NOT NULL,
			  PRIMARY KEY (`id`)
				) DEFAULT CHARSET=$charset AUTO_INCREMENT=1");
				
			
				
				if(json_decode(json_encode($db->fetchAll($db->query("SHOW FULL COLUMNS FROM ".$table))),true)[6]['Field'] !=='contactmail' && json_decode(json_encode($db->fetchAll($db->query("SHOW FULL COLUMNS FROM ".$table))),true)[5]['Field'] !=='contactmail'){
            $db->query('ALTER TABLE '.$table.' ADD `contactmail` varchar(1000) DEFAULT null;');
        }
        if (!array_key_exists('coins', $db->fetchRow($db->select()->from('table.users')))) {
$db->query('ALTER TABLE `' . $prefix . 'users` ADD `coins` varchar(1000) NOT NULL  DEFAULT 0;');
}
if (!array_key_exists('submission', $db->fetchRow($db->select()->from('table.users')))) {
$db->query('ALTER TABLE `' . $prefix . 'users` ADD `submission` varchar(1000) NOT NULL  DEFAULT true;');
}
if (!array_key_exists('signature', $db->fetchRow($db->select()->from('table.users')))) {
$db->query('ALTER TABLE `' . $prefix . 'users` ADD `signature` varchar(1000) NOT NULL  DEFAULT 0;');
}



if(json_decode(json_encode($db->fetchAll($db->query("SHOW FULL COLUMNS FROM ".$table))),true)[8]['Field'] !=='checkurl'&&json_decode(json_encode($db->fetchAll($db->query("SHOW FULL COLUMNS FROM ".$table))),true)[7]['Field'] !=='checkurl'){
            $db->query('ALTER TABLE ' . $table . '  ADD `checkurl` varchar(1000) NOT NULL  DEFAULT 0;');
        }
if(json_decode(json_encode($db->fetchAll($db->query("SHOW FULL COLUMNS FROM ".$table))),true)[9]['Field'] !=='type'&&json_decode(json_encode($db->fetchAll($db->query("SHOW FULL COLUMNS FROM ".$table))),true)[8]['Field'] !=='type'){
            $db->query('ALTER TABLE ' . $table . '  ADD `type` varchar(1000) NOT NULL  DEFAULT 0;');
        }
if(json_decode(json_encode($db->fetchAll($db->query("SHOW FULL COLUMNS FROM ".$table))),true)[10]['Field'] !=='rejectreason'&&json_decode(json_encode($db->fetchAll($db->query("SHOW FULL COLUMNS FROM ".$table))),true)[9]['Field'] !=='rejectreason'){
            $db->query('ALTER TABLE ' . $table . '  ADD `rejectreason` varchar(1000) NULL DEFAULT NULL;');
        }
        }
     
        
        
         return _t('BsCore核心同步完成');
    }

    public static function deactivate()
    {
        bsRouter::removeRouter();
        Helper::removePanel(0, self::$_panel);
        Helper::removeAction('upload');
        return _t('BsCore核心清除完成');
    }
    
    
   

    public static function initevent()
    {
        if (!class_exists('bsOptions')){
            require_once \Utils\Helper::options()->pluginDir('BsCore').'/bsOptions.php';
        }
    }
    public static function send_request($url, $postdata,$sendtype,$header = 'Content-type: application/x-www-form-urlencoded') {
    // ponytail: PHP 8.5 弃用 $http_response_header，改用 curl 取状态码；同时修复原 HTTP/1.1 精确比对对 HTTP/2 失效的问题
    $ch = curl_init($url);
    curl_setopt_array($ch, array(
        CURLOPT_CUSTOMREQUEST  => $sendtype,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 5,
        CURLOPT_HTTPHEADER     => $postdata ? array($header) : array('Content-type: application/x-www-form-urlencoded'),
    ));
    if ($postdata) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, is_array($postdata) ? http_build_query($postdata) : $postdata);
    }
    $result    = curl_exec($ch);
    $statuscode = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);
    if ($result === false || $statuscode !== 200){
        $result = array(
            "result" => "success",
            "reason" => "request fail"
        );
        return json_encode($result);
    }else{
        return $result;
    }
}

    public static function enqueue_style($header=null, $old=null)
    {
        \Typecho\Widget::widget('Widget_User')->to($user);
        if(\Typecho\Cookie::get('__typecho_notice_highlight') && \Typecho\Cookie::get('__typecho_notice_highlight') == 'plugin-BsCore'){
        \Typecho\Cookie::delete('__typecho_notice_type');
        \Typecho\Cookie::delete('__typecho_notice');
        }
        $options = get_option('bearsimple');
        if($user->hasLogin() && !$user->pass('administrator', true) && $options['UserCenterRedirect'] == true){
        header('Location: '.Helper::options()->siteUrl.'/index.php/usercenter', true, 302);
        }
        if ($old!=null) $header = $old;
        return CSF::get_enqueue_style($header);
    }

    public static function enqueue_script($footer=null)
    {
        return CSF::get_enqueue_script($footer);
    }

    public static function config(Form $form)
    {

    }

    public static function personalConfig(Form $form)
    {
    }
    
    

 public static function configHandle($origin_config, $is_init)
    {
        return true;
    }

 public static function siteMaintenance(){
        $options = bsOptions::getInstance()::get_option('bearsimple');
        \Typecho\Widget::widget('Widget_User')->to($user);
        if($options['siteMaintenance_Open'] == true && (!$user->hasLogin() && !$user->pass('administrator', true))){
            if(!$options['siteMaintenance_templates']){
                $tem = 'default.html';
            }
            else{
                $tem = $options['siteMaintenance_templates'];
            }
        // ponytail: str_replace("/plugins","") 在 Windows 反斜杠路径下不匹配导致路径错位，改用 dirname(__DIR__,2) 直接定位 usr 目录
        $content =  file_get_contents(dirname(__DIR__, 2).'/themes/bearsimple/modules/siteMaintenance/'.$tem);
        echo $content;
        exit;
        }
    }
    
 public static function bCore_parseMultilineData($str, $columnCount)
    {
        $result = array();
        if (!empty($str)) {
            $data = explode("\n", $str);
            foreach ($data as $item) {
                $item = trim($item);
                if (!empty($item)) {
                    $itemData = explode('|', $item, $columnCount);
                    if (count($itemData) == $columnCount) {
                        foreach ($itemData as $k => $v) {
                            $itemData[$k] = trim($v);
                        }
                        $result[] = $itemData;
                    }
                }
            }
        }
        return $result;
    }
 public static function bCore_seacharr_by_value($array, $index, $value){
    if(is_array($array) && count($array)>0) {
        foreach(array_keys($array) as $key){
            $temp[$key] = $array[$key][$index];
            if ($temp[$key] == $value){
                $newarray[$key] = $array[$key];
            }
        }
    }
    return $newarray;
}
public static function bCore_getDiyLinkToArray($data){
    $res = array();
foreach($data as $k=>$v){
    $res[]= array(
        'url' => $v[1],
        'key'=>$v[0]
        );
        
}
return $res;
 }
//获取自定义内链
public static function bCore_getDiyLink($key){
    $options = bsOptions::getInstance()::get_option('bearsimple');
    $data = self::bCore_parseMultilineData($options['goLinkList'],2);
    $result = self::bCore_seacharr_by_value(self::bCore_getDiyLinkToArray($data),'key',$key);
    $res = array();
foreach($result as $k=>$v){
   foreach ($v as $key=>$value) {
      $res[$key] = $value;
   }
}
    return $res;
}

public static function markdownparse($text)
    {
$parser = new \HyperDown\Parser();
return $parser->makeHtml($text);
        
        
    }
 public static function bregister($v) {
  $hasher = new PasswordHash(8, true);
  if(\Typecho\Widget::widget('Widget_Register')->request->password !== ''){
    $generatedPassword = \Typecho\Widget::widget('Widget_Register')->request->password;
  }else{
    $generatedPassword = \Typecho\Common::randString(7);
  }
  define('getpassd', (string)$generatedPassword);
  $wPassword = $hasher->HashPassword((string)$generatedPassword);
  $v['password']=$wPassword;
  if(\Typecho\Widget::widget('Widget_Register')->request->regtype == 'nobackend'){
  $v['group']='contributor';
  $v['submission']=1;
  }
  else{
   $v['submission']=0;   
  }
  return $v;
}   

public static function bregister_finish($obj) {
 $wPassword=getpassd;
 \Typecho\Widget::widget('Widget_User')->to($user);
 $user->login($obj->request->name,$wPassword);
 \Typecho\Cookie::delete('__typecho_first_run');
 \Typecho\Cookie::delete('__typecho_remember_name');
 \Typecho\Cookie::delete('__typecho_remember_mail');
 $obj->widget('Widget_Notice')->set(_t('用户 <strong>%s</strong> 已经成功注册, 密码为 <strong>%s</strong>', $obj->screenName, $wPassword), 'success');
 if (NULL != $obj->request->referer) {
 $obj->response->redirect($obj->request->referer);
 }else if(NULL != $obj->request->tz){
   if (Helper::options()->rewrite==0){$authorurl=Helper::options()->rootUrl.'/index.php/usercenter/';}else{$authorurl=Helper::options()->rootUrl.'/usercenter/';}
  $obj->response->redirect($authorurl.$obj->user->uid);
 }else{
 $obj->response->redirect(Helper::options()->adminUrl);
 }
}
 //外链转内链
 public static function replace($text, $widget, $lastResult)
    {
        $text = empty($lastResult) ? $text : $lastResult;
        $options = get_option('bearsimple');
        
        $target = '';
        if($options['Pjax'] == 1){
            $target .= ' pjax="no"';
        }
        if($options['goLinkNewWindow'] == 1){
            $target .= ' target="_blank"';
        }
            $fields = unserialize($widget->fields);
            // 文章内容和评论内容处理
            @preg_match_all('/<a(.*?)href="(?!#)(.*?)"(.*?)>/', $text, $matches);
            if ($matches) {
                foreach ($matches[2] as $link) {
                    $link = str_replace('mailto:','',$link);
                    if (!filter_var($link, FILTER_VALIDATE_EMAIL)) {
                    $text = str_replace("href=\"$link\"", "href=\"" . self::convertLinks($link) . "\"" . $target, $text);
                    }
                    
                
                }
            }
        return $text;
    }
 public static function urlSafeB64Encodes($str)
    {
        $data = base64_encode($str);
        return str_replace(array('+', '/', '='), array('-', '_', ''), $data);
    }
public static function urlSafeB64Decodes($str)
    {
        $data = str_replace(array('-', '_'), array('+', '/'), $str);
        $mod = strlen($data) % 4;
        if ($mod) {
            $data .= substr('====', $mod);
        }
        return base64_decode($data);
    }
  public static function textareaToArrs($textarea)
    {
        $str = str_replace(array("\r\n", "\r", "\n"), "|", $textarea);
        if ($str == "") {
            return null;
        }

        return explode("|", $str);
    }
 public static function checkDomains($url, $arr)
    {
        if ($arr === null) {
            return false;
        }

        if (count($arr) === 0) {
            return false;
        }

        foreach ($arr as $a) {
            if (strpos($url, $a) !== false) {
                return true;
            }
        }
        return false;
    }
 public static function convertLinks($link, $check = true)
    {
        $options = get_option('bearsimple');
        $linkBase = ltrim(rtrim(\Typecho\Router::get('go')['url'], '/'), '/'); // 防止链接形式修改后不能用
        $siteUrl = Helper::options()->siteUrl;
        $nonConvertList = self::textareaToArrs($options['goLinkNonConvertList']); // 不转换列表
        if ($check) {
            if (strpos($link, '://') !== false && strpos($link, rtrim($siteUrl, '/')) !== false) {
                return $link;
            }
            // 本站链接不处理 不转换列表中的不处理
            if (self::checkDomains($link, $nonConvertList)) {
                return $link;
            }

            // 图片不处理
            if (preg_match('/\.(jpg|jepg|png|ico|bmp|gif|tiff)/i', $link)) {
                return $link;
            }
        }
        return \Typecho\Common::url(str_replace('[key]', self::urlSafeB64Encodes(htmlspecialchars_decode($link)), $linkBase), Helper::options()->index);
    }
    
  //取出所有加密分类mid并剔除重复值
public static function getCols($arr, $col)
    {
        $ret = array();
        foreach ($arr as $row)
        {
            if (isset($row[$col])) {
                $ret[] = $row[$col];
            }
        }
        
        return array_unique($ret);
    }
    
//加密的分类排列
public static function EncryptCategory(){
    $options = get_option('bearsimple');
    $data = $options['Cate_Encrypt'];
    $res = self::getCols($data, 'Cate_Encrypt_Id');
    return $res;
}

  public static function CateFilter($thisx, $select) {
    \Typecho\Widget::widget('Widget_User')->to($user);
    $mid = self::EncryptCategory();
    $db = \Typecho\Db::get();
    
    if(empty($mid) || $user->hasLogin()) return $select;
    
    $encryptedMids = array_map('intval', (array)$mid);
    $encryptedMids = array_filter(array_unique($encryptedMids));
    
    if(empty($encryptedMids)) return $select;
    
    $midList = implode(',', $encryptedMids);

    $prefix = $db->getPrefix();
    $select = $select->join(
        $prefix.'relationships AS filter_rel', // 指定别名
        "filter_rel.cid = {$prefix}contents.cid AND filter_rel.mid IN ($midList)", 
        'LEFT'
    );
    
    $select->where('filter_rel.cid IS NULL');
    return $select;
}
   // 置顶
   public static function sticky($archive, $select)
    {
        $config  = get_option('bearsimple');
        if(!empty($config['Sticky_cids'])&&!empty($config['Sticky_cids_pages'])){
        $sticky_cids = array_merge($config['Sticky_cids'],$config['Sticky_cids_pages']);
        }
        if(!empty($config['Sticky_cids'])&&empty($config['Sticky_cids_pages'])){
        $sticky_cids = $config['Sticky_cids'];
        }
        if(empty($config['Sticky_cids'])&&!empty($config['Sticky_cids_pages'])){
        $sticky_cids = $config['Sticky_cids_pages'];
        }
        if (!$sticky_cids) return;

        $pagesize = Helper::options()->pageSize;
        $db = \Typecho\Db::get();
        $paded = $archive->request->get('page', 1);
$sticky_html = $config['Sticky_Mod'] ? $config['Sticky_Mod'] : "<span style='color:red'>[置顶] </span>";
        foreach($sticky_cids as $cid) {
          if ($cid && $sticky_post = $db->fetchRow($archive->select()->where('cid = ?', $cid))) {
              if ($paded == 1) {                
                $sticky_post['title'] = $sticky_post['title'];
                $sticky_post['sticky'] = $sticky_html;
                $archive->push($sticky_post); 
                //$pagesize = $pagesize - 1;
              }
              $select->where('table.contents.cid != ?', $cid);
              if ($pagesize > 0) { 
              $archive->parameter->pageSize = $pagesize;
              }
          }
        }
    }
    
	public static function stickyC($archive, $select)
	{
		$config  = get_option('bearsimple');
        $sticky_cid = $config['Sticky_cids_category'];
        if (!$sticky_cid) return;

        $pagesize = Helper::options()->pageSize;
        $db = \Typecho\Db::get();
        $paded = $archive->request->get('page', 1);
$sticky_html = $config['Sticky_Mod'] ? $config['Sticky_Mod'] : "<span style='color:red'>[置顶] </span>";
        foreach($sticky_cid as $cid) {
          $sticky_post = $db->fetchRow($archive->select()->where('cid = ?', $cid));
          if ($config['Sticky_Category']) {             
          	    $archive->widget('Widget_Archive@'.$cid, 'pageSize=1&type=post', 'cid='.$cid)->to($slug);
      	        $pattern = preg_match("/".$slug->category."/i", $_SERVER['PHP_SELF']);
          }else {
          	    $pattern = true;
          }
      	        
          if ($cid && $sticky_post && $pattern) {
              if ($paded == 1) {
                $sticky_post['title'] = $sticky_post['title'];
                $sticky_post['sticky'] = $sticky_html;
                $archive->push($sticky_post);
                //$pagesize = $pagesize - 1;
              }
              $select->where('table.contents.cid != ?', $cid);
              if ($pagesize > 0) {
              $archive->parameter->pageSize = $pagesize;
              }
          }

        }
	}
     public function setField(string $name, string $type, string $value, int $cid)
    {
        $db = \Typecho\Db::get();
        if (
            empty($name)
            || !in_array($type, ['str', 'int', 'float'])
        ) {
            return false;
        }

        $exist = $db->fetchRow($db->select('cid')->from('table.fields')
            ->where('cid = ? AND name = ?', $cid, $name));

        if (empty($exist)) {
            return $db->query($db->insert('table.fields')
                ->rows([
                    'cid'         => $cid,
                    'name'        => $name,
                    'type'        => $type,
                    'str_value'   => 'str' == $type ? $value : null,
                    'int_value'   => 'int' == $type ? intval($value) : 0,
                    'float_value' => 'float' == $type ? floatval($value) : 0
                ]));
        } else {
            return $db->query($db->update('table.fields')
                ->rows([
                    'type'        => $type,
                    'str_value'   => 'str' == $type ? $value : null,
                    'int_value'   => 'int' == $type ? intval($value) : 0,
                    'float_value' => 'float' == $type ? floatval($value) : 0
                ])
                ->where('cid = ? AND name = ?', $cid, $name));
        }
    }
    public static function C()
    {
        self::initEnv();
        $op = Helper::options();
        $scheme = parse_url($op->siteUrl)['scheme'];
        $host = parse_url($op->siteUrl)['host'];
        $ip = gethostbyname($_SERVER['HTTP_HOST']);
        if (self::$plugin_config->enable_gcache == '0')
            return ;
        if (self::$plugin_config->Cache_choose == '0')
            return;
        if (!self::preCheck()) return;

        if (!self::initPath()) return;
        try {
            $data = self::getCache();
            if (!empty($data)) {
                if ($data['time'] + self::$plugin_config->expire < time())
                    self::$passed = false;
               //处理可能存在开启缓存后域名被替换为IP地址的问题
               $html = $data['html'];
               $html = str_replace('https://'.$ip,$op->siteUrl,$html);
               $html = str_replace('http://'.$ip,$op->siteUrl,$html);
               $html = str_replace('http//','',$html);
               $html = str_replace('https//','',$html);
               $html = str_replace(':443/','',$html);
               $html = str_replace('http://'.$host.'/',$op->siteUrl,$html);
               $html = str_replace('https://'.$host.'/',$op->siteUrl,$html);
               $html = str_replace($op->siteUrl.'/',$op->siteUrl,$html);
               $html = str_replace($_SERVER['SERVER_ADDR'],$_SERVER['SERVER_NAME'],$html);
               header('BsCache: HIT');
         echo $html;
                die;
            }
        } catch (Exception $e) {
            echo $e->getMessage();
        }
        ob_flush();
    }

    public static function S($html = '')
    {
        if (self::$plugin_config->Cache_choose == '0')
            return;
        if (is_null(self::$path) || !self::$passed)
            return;
        if (empty($html))
            $html = ob_get_contents();
        $data = array();
        $data['time'] = time();
        $data['html'] = $html;
        self::setCache($data);
    }

    public static function getCache($name = null)
    {
        if ($name) return unserialize(self::$cache->get($name));
        return unserialize(self::$cache->get(self::$path));
    }

    public static function setCache($data, $name = null)
    {
        if ($name) return self::$cache->set($name, serialize($data));
        return self::$cache->set(self::$path, serialize($data));
    }

    public static function delCache($path, $rmHome = True)
    {
        self::$cache->delete($path);
        if ($rmHome)
            self::$cache->delete('/');
    }

    public static function preCheck($checkPost = True)
    {
        if ($checkPost && self::$request->isPost()) return false;

        if (self::$plugin_config->login && \Typecho\Widget::widget('Widget_User')->hasLogin())
            return false;
        if (self::$plugin_config->Cache_choose == '0')
            return false;
        self::$passed = true;
        return true;
    }

    public static function initEnv()
    {
        if (!class_exists('bsOptions')){
            require_once \Utils\Helper::options()->pluginDir('BsCore').'/bsOptions.php';
        }
        if (is_null(self::$sys_config))
            self::$sys_config = bsOptions::getInstance();
        if (is_null(self::$plugin_config))
            self::$plugin_config = (Object)self::$sys_config::get_option('bearsimple');
        if (is_null(self::$request))
            self::$request = new \Typecho\Request();
    }

    public static function initPath($pathInfo='')
    {

        if(empty($pathInfo))
            $pathInfo = self::$request->getPathInfo();
        if (!self::needCache($pathInfo)){
            return false;
        }

        self::$path = $pathInfo;
        return self::initBackend(self::$plugin_config->Cache_choose);
    }

    public static function initBackend($backend){
        if ($backend == '0') return false;
        $options = get_option('bearsimple');
        $class_name = "typecho_".$options['Cache_choose'];
        require_once 'modules/cache/cache.interface.php';
        require_once "modules/cache/$class_name.class.php";
        self::$cache = call_user_func(array($class_name, 'getInstance'), self::$plugin_config);
        if (is_null(self::$cache))
            return false;
        return true;
    }

    public static function needCache($path)
    {
        $pattern = '#^' . __TYPECHO_ADMIN_DIR__ . '#i';
        if (preg_match($pattern, $path)) return false;
        $pattern = '#^/action#i';
        if (preg_match($pattern, $path)) return false;
        $requestUrl = self::$request->getRequestUri();
        $pattern = '/.*?s=.*/i';
        if (preg_match($pattern, $requestUrl)) return false;
        $pattern = '#^/search#i';
        if (preg_match($pattern, $path) and in_array('search', self::$plugin_config->cache_page)) return true;
        $_routable = Helper::options()->routingTable;
        $post_regx = $_routable[0]['post']['regx'];
        if (array_key_exists('TePass', \Typecho\Plugin::export()['activated'])){
        if (preg_match($post_regx,$path,$arr)){
            if ($arr[1] and !empty($arr[1])){
                $db = \Typecho\Db::get();
                try {
		    $database = $db->getConfig($db::READ)['database'];
                    $tepass_exist = $db->fetchRow($db->select()->from('information_schema.TABLES')->where('TABLE_NAME = ?',$db->getPrefix().'tepass_posts')->where('TABLE_SCHEMA = ?',$database));
                    if (isset($tepass_exist) and count($tepass_exist) > 0){
                          $p_id = $db->fetchObject($db->select('id')->from('table.tepass_posts')->where('post_id = ?',$arr[1]))->id;
                          if ($p_id) return false;
                    }

                }catch (Typecho_Db_Query_Exception $e){
                    
                }

            } 
        }
}


        foreach ($_routable as $page => $route) {
            if ($route[0]['widget'] != '\Widget\Archive' and $route[0]['widget'] != 'Widget_Archive') continue;
            $regx = Router::get($page);
            if (preg_match($regx['regx'], $path)) {
                $exclude = array('_year', '_month', '_day', '_page');
                $page = str_replace($exclude, '', $page);

                if (in_array($page, get_option('bearsimple')['cache_page']))
                    return true;
            }
        }
        return false;
    }

    public static function post_update($contents, $class)
    {
        if ('publish' != $contents['visibility'] || $contents['created'] > time())
            return;

        self::initEnv();
        if (self::$plugin_config->Cache_choose == '0')
            return;
        self::$passed = true;

        $type = $contents['type'];
        $routeExists = (NULL != \Typecho\Router::get($type));
        if (!$routeExists) {
            self::initPath('#');
            self::delCache(self::$path);
            return;
        }

        $db = \Typecho\Db::get();
        $contents['cid'] = $class->cid;
        $contents['categories'] = $db->fetchAll($db->select()->from('table.metas')
            ->join('table.relationships', 'table.relationships.mid = table.metas.mid')
            ->where('table.relationships.cid = ?', $contents['cid'])
            ->where('table.metas.type = ?', 'category')
            ->order('table.metas.order', \Typecho\Db::SORT_ASC));
        $contents['category'] = urlencode(current(\Typecho\Common::arrayFlatten($contents['categories'], 'slug')));
        $contents['slug'] = urlencode(empty($contents['slug'])?$class->slug:$contents['slug']);
        $contents['date'] = new \Typecho\Date($contents['created']);
        $contents['year'] = $contents['date']->year;
        $contents['month'] = $contents['date']->month;
        $contents['day'] = $contents['date']->day;

        if (!self::initPath(\Typecho\Router::url($type, $contents))){
            return;
        }
        self::delCache(self::$path);
        if ($class->cid)
            self::delCache(self::getPostMarkCacheName($class->cid));
    }

    public static function post_del_update($cid, $obj)
    {
        if (self::$plugin_config->Cache_choose == '0')
            return;
        $db = \Typecho\Db::get();
        $postObject = $db->fetchRow($db->select()
            ->from('table.contents')->where('cid = ?', $cid));
        if (!$postObject['cid']){
            return;
        }
        $val = \Typecho\Widget::widget('Widget_Abstract_Contents')->push($postObject);
        $url =  str_replace(Helper::options()->siteUrl, "", $val['permalink']);
        $cacheUrl =  str_replace('index.php', "", $url);
        self::initEnv();

        self::initBackend(self::$plugin_config->Cache_choose);
        self::delCache($cacheUrl);
        if ($cid){
            self::delCache($cacheUrl.'_'.$cid.'_markdown');
        }
    }

    public static function comment_update($comment)
    {
        if (self::$plugin_config->Cache_choose == '0')
            return;
        self::initEnv();
        if (!self::preCheck(false)) return;
        if (!self::initBackend(self::$plugin_config->Cache_choose))
            return;
        $path_info = self::$request->getPathInfo();
        $article_url = preg_replace('/\/comment$/i','',$path_info);

        self::delCache($article_url);
        if ($comment->cid)
            self::delCache(self::getPostMarkCacheName($comment->cid));
    }

    public static function comment_update2($comment, $edit)
    {
        if (self::$plugin_config->Cache_choose == '0')
            return;
        self::initEnv();
        self::preCheck(false);
        self::initBackend(self::$plugin_config->Cache_choose);
        $perm = stripslashes($edit->parentContent['permalink']);
        $perm = preg_replace('/(https?):\/\//', '', $perm);
        $perm = preg_replace('/'.$_SERVER['HTTP_HOST'].'/', '', $perm);
        self::delCache($perm);
        if ($edit->cid)
            self::delCache(self::getPostMarkCacheName($edit->cid));
        if ($comment->cid)
            self::delCache(self::getPostMarkCacheName($comment->cid));
    }
    
    public static function getPostMarkCacheName($cid){
        if (!self::$path)
            self::$path = self::$request->getPathInfo();
        return self::$path.'_'.$cid.'_markdown';
    }

    public static function cache_contentEx($content, $obj, $lastResult){
        if (self::$plugin_config->Cache_choose == '0')
            return $content;
        $content = empty( $lastResult ) ? $content : $lastResult;
        if (self::$plugin_config->enable_markcache == '0'){
            return $content;
        }
        if (substr_count($content,'<!--no-cache-->'))
            return $content;
        self::initEnv();
        self::$path = self::$request->getPathInfo();
        $cacheName = self::getPostMarkCacheName($obj->cid);
        self::initEnv();
        if (!self::preCheck(false)) {
            return $content;
        }
        if (!self::initBackend(self::$plugin_config->Cache_choose)){
            return $content;
        }
        try {
            $data = self::getCache($cacheName);
            if (!empty($data)) {
                if ($data['time'] + self::$plugin_config->expire < time())
                    self::$passed = false;
                return $data['html'];
            }
        } catch (Exception $e) {
            return $content;
        }

        if (is_null(self::$path) || !self::$passed)
            return $content;
        $data = array();
        $data['time'] = time();
        $data['html'] = $content;
        self::setCache($data,$cacheName);
        return $content;
    }

    public static function BsCache_setCache($cacheKey,$val){
        self::initEnv();
        if (!self::preCheck(false)) {
            return false;
        }
        if (!self::initBackend(self::$plugin_config->Cache_choose)){
            return false;
        }
        $data = array();
        $data['time'] = time();
        $data['html'] = $val;
        self::setCache($data,$cacheKey);
        return true;
    }
    
    public static function BsCache_getCache($cacheKey){
        self::initEnv();
        if (!self::preCheck(false)) {
            return false;
        }
        if (!self::initBackend(self::$plugin_config->Cache_choose)){
            return false;
        }
        try{
            $data = self::getCache($cacheKey);
            if (!empty($data)) {
                if ($data['time'] + self::$plugin_config->expire < time())
                    self::$passed = false;
                return $data['html'];
            }
        } catch (Exception $e) {
            echo $e->getMessage();
            return false;
        }
        return false;
    }

    /* ponytail: btbutton/Editor 实现体拆分至 classes/editor.php（约 3300 行，仅后台编辑页 hook 触发），
       转发器按需 require——前台与其他后台页零解析开销；签名与 activate() 的 [__CLASS__,...] 注册保持不变 */
    public static function btbutton()
    {
        require_once __DIR__ . '/classes/editor.php';
        return Editor::btbutton();
    }

    public static function Editor()
    {
        require_once __DIR__ . '/classes/editor.php';
        return Editor::Editor();
    }
  private static function compareVersion($test)
  {
    $currentVersion = explode('.', PHP_VERSION);
    $testVersion = explode('.', $test);
    $ret = 0;
    for ($i = 0; $i < sizeof($currentVersion); $i++) {
      if ($currentVersion[$i] == $testVersion[$i]) {
        continue;
      }
      if ($currentVersion[$i] > $testVersion[$i]) {
        $ret = 1;
        break;
      } else {
        $ret = -1;
        break;
      }
    }
    return $ret;
  }
  
  
  //文章内容分页
  public static function parseContent($obj,$u,$r,$con){
		$options = \Typecho\Widget::widget('Widget_Options');
		$db = \Typecho\Db::get();
		$query= $db->select()->from('table.contents')->where('cid = ?', $obj->cid); 
		$row = $db->fetchRow($query);
		$log_content=$row['text'];
		if(get_option('Scroll') == 1){
		    $menutree = 'window.tocManager.displayDisableTocTips = false;window.tocManager.generateToc();';		}
		$Page_Mark = "----------";
		if(strpos($log_content, $Page_Mark)) $ob = "Y";
		if(isset($ob)){
			$content_list = explode($Page_Mark, $log_content);
			$page_count = count($content_list);
			$page_now = !empty($_GET['page_now']) ? intval($_GET['page_now']) : 1;
			$page_now = ($page_now > $page_count && $page_count>0) ? $page_count : $page_now;
			$log_content = stripslashes($content_list[$page_now -1]);
			
				$log_content = '
					<div id="pagearea"></div>
					<div id="pageBar"></div>
					<script>
					var curPage;
					var totalItem;
					var pageSize;
					var totalPage;
					 
					//获取分页数据
					function turnPage(page){
					  $.ajax({
						type: "POST",
						url: "'.$options->siteUrl.'/index.php/bs-pagecontent",
						data: {"page_now":page,"cid":"'.$obj->cid.'","title":"'.$obj->title.'","remember":"'.$r.'","haslogin":"'.$u.'","ArticleType":"'.$obj->fields->ArticleType.'"},
						dataType: "json",
						beforeSend: function() {
						  $("#pagearea").append("<div class=\"ui medium active secondary inline text loader\">加载中......</div>");
						},
						success: function(json) {
						  $("#pagearea").empty();/*移除原来的分页数据*/
						  totalItem = json.totalItem;
						  pageSize = json.pageSize;
						  curPage = page;
						  totalPage = json.totalPage;
						  var data_content = json.log_content;
						  $("#pagearea").append(data_content);

			'.$menutree.'
						},
						complete: function() {    /*添加分页按钮栏*/
						  getPageBar();
						},
						error: function() {
						  alert("数据加载失败");
						}
					  });
					}
					/*获取分页条（分页按钮栏的规则和样式根据自己的需要来设置）*/
					function getPageBar(){
					  if(curPage > totalPage) {
						curPage = totalPage;
					  }
					  if(curPage < 1) {
						curPage = 1;
					  }
					 
					  pageBar = "<div class=\"ui circular labels\"><div style=\"text-align:center\">";
					 
					  /*如果不是第一页*/
					  if(curPage != 1){
						pageBar += "<a class=\"ui large label\" href=\"javascript:turnPage(1)\">首页</a>&nbsp;";
						pageBar += "<a class=\"ui large label\" href=\"javascript:turnPage("+(curPage-1)+")\">上一页</a>&nbsp;";
					  }
					 
					  /*显示的页码按钮(5个)*/
					  var start,end;
					  if(totalPage <= 5) {
						start = 1;
						end = totalPage;
					  } else {
						if(curPage-2 <= 0) {
							start = 1;
							end = 5;
						} else {
							if(totalPage-curPage < 2) {
								start = totalPage - 4;
								end = totalPage;
							} else {
								start = curPage - 2;
								end = curPage + 2;
							}
						}
					  }
					  
					  for(var i=start;i<=end;i++) {
						if(i == curPage) {
							pageBar += "<a class=\"ui large grey  label\" href=\"javascript:turnPage("+i+")\">"+i+"</a>&nbsp;";
						} else {
							pageBar += "<a class=\"ui large label\" href=\"javascript:turnPage("+i+")\">"+i+"</a>&nbsp;";
						}
					  }
					  
					  /*如果不是最后页*/
					  if(curPage != totalPage){
						pageBar += "<a class=\"ui large label\" href=\"javascript:turnPage("+(parseInt(curPage)+1)+")\">下一页</a>&nbsp;";
						pageBar += "<a class=\"ui large label\" href=\"javascript:turnPage("+totalPage+")\">尾页</a>";
					  }
						pageBar += "</div></div>"; 
					  $("#pageBar").html(pageBar);
					}
					 
					/*页面加载时初始化分页*/
					$(function() {
					  turnPage(1);
					});
					     $(window).bind(\'popstate\',function(event) {  
					     turnPage(1);
					     });
					</script>
				';
				return $log_content;
			?>
				<?php
		}else{
			return $con;
		}
  }
  
  
  
  
  public static function xsscheck($text)
{
    $xsscheck = false;
    $list = array(
        '/onabort/is',
        '/onblur/is',
        '/onchange/is',
        '/onclick/is',
        '/ondblclick/is',
        '/onerror/is',
        '/onfocus/is',
        '/onkeydown/is',
        '/onkeypress/is',
        '/onkeyup/is',
        '/onload/is',
        '/onmousedown/is',
        '/onmousemove/is',
        '/onmouseout/is',
        '/onmouseover/is',
        '/onmouseup/is',
        '/onreset/is',
        '/onresize/is',
        '/onselect/is',
        '/onsubmit/is',
        '/onunload/is',
        '/eval/is',
        '/ascript:/is',
        '/style=/is',
        '/width=/is',
        '/width:/is',
        '/height=/is',
        '/height:/is',
        '/src=/is',
    );
    if (strip_tags($text)) {
        for ($i = 0; $i < count($list); $i++) {
            if (preg_match($list[$i], $text) > 0) {
                $xsscheck = true;
                break;
            }
        }
    } else {
        $xsscheck = true;
    };
    return $xsscheck;
}

/**
    * PHP获取字符串中英文混合长度 
    */
    public static function strLength($str){        
        preg_match_all('/./us', $str, $match);
        return count($match[0]);  // 输出9
    }
        
        

    /**
     * 检查$str中是否含有$words_str中的词汇
     * 
     */
    public static function searchArray($keyword, $array) {
    foreach ($array as $item) {
        if (preg_match("/\b[a-zA-Z\x{4e00}-\x{9fa5}]{$keyword}\b/", $item)) {
            return true;
        }
    }
    return false;
}

	public static function check_in($words_str, $str)  
{  
$words = explode("\n", $words_str);
		if (empty($words)) {
			return false;
		}
	$result = array_filter($words, function($word) use ($str){
		    if (false !== stripos($str, trim($word))) {
                return true;
            }
            else{
                return false;
            }
		});
		return $result;
	}

    /**
     * 检查$ip中是否在$words_ip的IP段中
     * 
     */
	public static function check_ip($words_ip, $ip)
	{
		$words = explode("\n", $words_ip);
		if (empty($words)) {
			return false;
		}
		foreach ($words as $word) {
			$word = trim($word);
			if (false !== strpos($word, '*')) {
				$word = "/^".str_replace('*', '\d{1,3}', $word)."$/";
				if (preg_match($word, $ip)) {
					return true;
				}
			} else {
				if (false !== strpos($ip, $word)) {
					return true;
				}
			}
		}
		return false;
	}
	
 public static function GetRandStr($length){
 $str = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
 $len = strlen($str)-1;
 $randstr = '';
 for ($i=0;$i<$length;$i++) {
  $num=mt_rand(0,$len);
  $randstr .= $str[$num];
 }
 return $randstr;
}
 public static function secretComment($data, $obj,$last){
     $text = empty($last)?$data:$last;             
     $db = \Typecho\Db::get();
     $iscircle = $db->fetchRow($db->select()->from('table.contents')
                ->where('cid = ? AND template = ?', $obj->cid,'friendcircle.php')->limit(1));   
    $iscross = $db->fetchRow($db->select()->from('table.contents')
                ->where('cid = ? AND template = ?', $obj->cid,'cross.php')->limit(1));      
     $parentid=array();$parentmail=array();//定义初始父级信息数组
     if($obj->parent>0){//如果拥有父级，则循环将所有父级信息加入数组
       
       $parent = $obj->parent;            
            while ($parent > 0) {
                $parentRows = $db->fetchRow($db->select()->from('table.comments')
                ->where('coid = ? AND status = ?', $parent, 'approved')->limit(1));
                
                if (!empty($parentRows)) {
                    $coid = $parent;
                    $parent = $parentRows['parent'];
                  array_push($parentid,$parentRows['authorId']);
                  array_push($parentmail,$parentRows['mail']);
                } else {
                    break;
                }
            }   
     }
     if($iscircle || $iscross){
         return $text;
     }
     \Typecho\Widget::widget('Widget_User')->to($user);//获取当前用户信息
     $stext = $text; 
      if(strpos($text,'@私密@') !== false){
          if(get_option('bearsimple')['avatar__choose'] !== 'closeavatar'){
     $stext = '<beartext class="comment-excerpta break bshideContent">该评论为私密评论，仅已登录者与评论双方可见</beartext>';
     $text = '<beartext class="comment-excerpta break">'.str_replace("@私密@","",$text).'</beartext>';
          }
          else{
      $stext = '<beartext class="comment-excerptb break bshideContent">该评论为私密评论，仅已登录者与评论双方可见</beartext>';
     $text = '<beartext class="comment-excerptb break">'.str_replace("@私密@","",$text).'</beartext>';        
          }
      
      
      
     
 if (\Typecho\Widget::widget('Widget_User')->hasLogin()){
   if($user->group=="administrator"||$user->group=="editor"||$user->uid==$obj->authorId||$user->uid==$obj->ownerId||in_array($user->uid,$parentid)){
       if(get_option('bearsimple')['avatar__choose'] !== 'closeavatar'){
       Helper::options()->commentsHTMLTagAllowed='<beartext class="comment-excerpta break">'.Helper::options()->commentsHTMLTagAllowed;
       }
       else{
        Helper::options()->commentsHTMLTagAllowed='<beartext class="comment-excerptb break">'.Helper::options()->commentsHTMLTagAllowed;   
       }
     return $text;
   }else{
     if(get_option('bearsimple')['avatar__choose'] !== 'closeavatar'){
       Helper::options()->commentsHTMLTagAllowed='<beartext class="comment-excerpta break bshideContent">'.Helper::options()->commentsHTMLTagAllowed;
       }
       else{
        Helper::options()->commentsHTMLTagAllowed='<beartext class="comment-excerptb break bshideContent">'.Helper::options()->commentsHTMLTagAllowed;   
       }
     return $stext;
   }
}else{
$commentsMail = \Typecho\Cookie::get('__typecho_remember_mail');
if(($obj->mail==$commentsMail && $obj->authorId==0)||(!empty($parentmail) && in_array($commentsMail,$parentmail))){
    if(get_option('bearsimple')['avatar__choose'] !== 'closeavatar'){
       Helper::options()->commentsHTMLTagAllowed='<beartext class="comment-excerpta break">'.Helper::options()->commentsHTMLTagAllowed;
       }
       else{
        Helper::options()->commentsHTMLTagAllowed='<beartext class="comment-excerptb break">'.Helper::options()->commentsHTMLTagAllowed;   
       }
    return $text;
}else{
    if(get_option('bearsimple')['avatar__choose'] !== 'closeavatar'){
       Helper::options()->commentsHTMLTagAllowed='<beartext class="comment-excerpta break bshideContent">'.Helper::options()->commentsHTMLTagAllowed;
       }
       else{
        Helper::options()->commentsHTMLTagAllowed='<beartext class="comment-excerptb break bshideContent">'.Helper::options()->commentsHTMLTagAllowed;   
       }
  return $stext;
} 
 }  
      }
      
      
      else{
          if(get_option('bearsimple')['avatar__choose'] !== 'closeavatar'){
        $text = '<beartext class="comment-excerpta break">'.str_replace("@私密@","",$text).'</beartext>';
        Helper::options()->commentsHTMLTagAllowed='<beartext class="comment-excerpta break">'.Helper::options()->commentsHTMLTagAllowed;
          }
          else{
           $text = '<beartext class="comment-excerptb break">'.str_replace("@私密@","",$text).'</beartext>';
        Helper::options()->commentsHTMLTagAllowed='<beartext class="comment-excerptb break">'.Helper::options()->commentsHTMLTagAllowed;   
          }
  return $text;
      }
     
   
   }
 

 public static function post_request($url, $postdata) {
    // ponytail: PHP 8.5 弃用 $http_response_header，改用 curl 取状态码；同时修复原 HTTP/1.1 精确比对对 HTTP/2 失效的问题
    $ch = curl_init($url);
    curl_setopt_array($ch, array(
        CURLOPT_POST           => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 5,
        CURLOPT_POSTFIELDS     => http_build_query($postdata),
        CURLOPT_HTTPHEADER     => array('Content-type: application/x-www-form-urlencoded'),
    ));
    $result    = curl_exec($ch);
    $statuscode = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);
    if ($result === false || $statuscode !== 200){
        $result = array(
            "result" => "success",
            "reason" => "request geetest api fail"
        );
        return json_encode($result);
    }else{
        return $result;
    }
}
 
 public static function spam_protection_mathjia(){
   
    $num1=rand(1,49);
    $num2=rand(1,49);

    echo "<div class=\"field\">
    <label class=\"required\">验证码</label>
  <input type=\"text\" name=\"sum\" class=\"text\" value=\"\" size=\"10\" tabindex=\"4\" style=\"width:180px\" placeholder=\"$num1 + $num2 = ?\">
</div>\n";
    echo "<input type=\"hidden\" name=\"num1\" value=\"$num1\">\n";
    echo "<input type=\"hidden\" name=\"num2\" value=\"$num2\">\n";
    echo "<input type=\"hidden\" name=\"spam_protection_mathstyle\" value=\"addition\">";
}

public static function spam_protection_mathjian(){
   
    $num1=rand(1,49);
    $num2=rand(1,49);

    echo "<div class=\"field\">
    <label class=\"required\">验证码</label>
  <input type=\"text\" name=\"sum\" class=\"text\" value=\"\" size=\"10\" tabindex=\"4\" style=\"width:180px\" placeholder=\"$num1 - $num2 = ?\">
</div>\n";
    echo "<input type=\"hidden\" name=\"num1\" value=\"$num1\">\n";
    echo "<input type=\"hidden\" name=\"num2\" value=\"$num2\">\n";
    echo "<input type=\"hidden\" name=\"spam_protection_mathstyle\" value=\"subtraction\">";
}

public static function spam_protection_question(){
   $opt = get_option('bearsimple');
   $rand = mt_rand(0,count($opt['VerifyQuestion'])-1);
   $rand_key = $opt['VerifyQuestion'][$rand]['VerifyQuestion_key'];
   $rand_answer = $opt['VerifyQuestion'][$rand]['VerifyQuestion_answer'];
   $rand_answer_tipshow = $opt['VerifyQuestion'][$rand]['VerifyQuestion_showtip'];
   $rand_answer_tipshow_text = $opt['VerifyQuestion'][$rand]['VerifyQuestion_showtiptext'];
   if($rand_answer_tipshow == true){
     echo "<div class=\"field\">
    <label class=\"required\">验证问答</label>
    <div class=\"ui input\">
  <input type=\"text\" placeholder=\"$rand_key\" name=\"answer\">
 
</div><br>
<div style=\"margin-top:5px;margin-bottom:5px;font-weight:bold;\"><a id=\"answerhide\">显示答案提示</a>
  <a id=\"answershow\" style=\"display:none;\">隐藏答案提示</a>
  <span class=\"answer\" style=\"display:none;\">
    「答案提示:$rand_answer_tipshow_text 」</span></div>
</div>";  
   }
   else{
    echo "<div class=\"field\">
    <label class=\"required\">验证问答</label>
    <div class=\"ui input\">
  <input type=\"text\" placeholder=\"$rand_key\" name=\"answer\">
 
</div><br>
<div style=\"margin-top:5px;margin-bottom:5px;font-weight:bold;\"><a id=\"answerhide\">显示答案</a>
  <a id=\"answershow\" style=\"display:none;\">隐藏答案</a>
  <span class=\"answer\" style=\"display:none;\">
    「答案:$rand_answer 」</span></div>
</div>";
}
 self::getSecurity('set','__typecho_comment_question_answer',$rand_answer);
}
 
public static  function authToken($string, $operation = 'DECODE', $key = '', $expiry = 0) { 
    $opurl = Helper::options()->siteUrl; 
  $ckey_length = 4;  
  $key = md5($key ? $key : $GLOBALS['discuz_auth_key']);  
  $keya = md5(substr($key, 0, 16));  
  $keyb = md5(substr($key, 16, 16));  
  $keyc = $ckey_length ? ($operation == 'DECODE' ? substr($string, 0, $ckey_length): 
substr(md5(microtime()), -$ckey_length)) : '';  
  $cryptkey = $keya.md5($keya.$keyc);  
  $key_length = strlen($cryptkey);  
  $string = $operation == 'DECODE' ? base64_decode(substr($string, $ckey_length)) : 
sprintf('%010d', $expiry ? $expiry + time() : 0).substr(md5($string.$keyb), 0, 16).$string;  
  $string_length = strlen($string);  
  $result = '';  
  $box = range(0, 255);  
  $rndkey = array();  
  for($i = 0; $i <= 255; $i++) {  
    $rndkey[$i] = ord($cryptkey[$i % $key_length]);  
  }  
  for($j = $i = 0; $i < 256; $i++) {  
    $j = ($j + $box[$i] + $rndkey[$i]) % 256;  
    $tmp = $box[$i];  
    $box[$i] = $box[$j];  
    $box[$j] = $tmp;  
  }  
  for($a = $j = $i = 0; $i < $string_length; $i++) {  
    $a = ($a + 1) % 256;  
    $j = ($j + $box[$a]) % 256;  
    $tmp = $box[$a];  
    $box[$a] = $box[$j];  
    $box[$j] = $tmp;  
    $result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));  
  }  
  if($operation == 'DECODE') { 
    if((substr($result, 0, 10) == 0 || substr($result, 0, 10) - time() > 0) && 
substr($result, 10, 16) == substr(md5(substr($result, 26).$keyb), 0, 16)) {  
      return substr($result, 26);  
    } else {  
      return '';  
    }  
  } else {  
    return $keyc.str_replace('=', '', base64_encode($result));  
  }  
}

 public static function getSecurityToken($type = NULL){
     $str = mt_rand(100001,999999); 
     $key = Helper::options()->siteUrl; 
     $securityToken = self::authToken($str,'ENCODE',$key,0);
     if($type == 'html'){
     echo "<input type=\"hidden\" name=\"SecurityToken\"  id=\"SecurityToken\" value='".$securityToken."'>\n";
     }
     self::getSecurity('set','__typecho_security_token',$securityToken);
 }
 
 // ponytail: per-IP 文件缓存壳（TTL 7 天，IP 归属地变化极慢）；缓存目录不可写时静默跳过，仅多一次外呼。
 // 限制：'天外来客'/'未知' 等失败结果同样缓存 7 天；如需强制刷新删除 usr 缓存目录下 iplocation 分片即可。
 public static function getIpLocation($ip){
     $cacheKey = md5((string)$ip);
     $bsOpts = bsOptions::getInstance()::get_option( 'bearsimple' );
     $cacheRoot = $bsOpts['cache_dir'] ?? '';
     if ($cacheRoot !== '' && strpos($cacheRoot, '/') !== 0) {
         $cacheRoot = __TYPECHO_ROOT_DIR__.'/usr/'.$cacheRoot;
     }
     if ((string)$cacheRoot === '') {
         $cacheRoot = __TYPECHO_ROOT_DIR__.'/usr/cache';
     }
     $cacheFile = self::hash2dir($cacheKey, rtrim($cacheRoot, '/').'/iplocation').$cacheKey.'.txt';
     if (is_file($cacheFile)) {
         $mtime = filemtime($cacheFile);
         if ($mtime !== false && time() - $mtime < 604800) {
             $cached = file_get_contents($cacheFile);
             if ($cached !== false && $cached !== '') {
                 return $cached;
             }
         }
     }
     $location = self::getIpLocationRemote($ip);
     if ($location !== '' && $location !== null) {
         $cacheDir = dirname($cacheFile);
         if (is_dir($cacheDir) || @mkdir($cacheDir, 0777, true)) {
             @file_put_contents($cacheFile, $location);
         }
     }
     return $location;
 }

 public static function getIpLocationRemote($ip){
     $options = bsOptions::getInstance()::get_option( 'bearsimple' );
     if($options['Comment_ipget_data_choose'] == 2 && $options['Comment_ipget_data_tencent_key'] !== ''){
         if(trim($options['Comment_ipget_data_tencent_sign']) !== ''){
             $md5 = md5('/ws/location/v1/ip?ip='.$ip.'&key='.$options['Comment_ipget_data_tencent_key'].$options['Comment_ipget_data_tencent_sign']);
             $url = 'https://apis.map.qq.com/ws/location/v1/ip?key='.$options['Comment_ipget_data_tencent_key'].'&ip='.$ip.'&sig='.$md5;
         }
         else{
        $url = 'https://apis.map.qq.com/ws/location/v1/ip?key='.$options['Comment_ipget_data_tencent_key'].'&ip='.$ip;
         }
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
// ponytail: 原实现把 header 数组误传给 CURLOPT_USERAGENT（PHP8 起触发 Array to string warning），改为直接传 UA 字符串
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:93.0)');
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);
   $info = json_decode($response,true)['result']['ad_info'];
   $nation = $info['nation'];
   $province = $info['province'];
   $city = $info['city'];
   $district = $info['district'];
   if($nation !== NULL){
   return $nation.$province.$city.$district;
   }
   else{
   return '天外来客';    
   }
     }
     else{
$url = 'https://ip.zxinc.org/api.php?type=json&ip='.$ip;
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
// ponytail: 原实现把 header 数组误传给 CURLOPT_USERAGENT（PHP8 起触发 Array to string warning），改为直接传 UA 字符串
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:93.0)');
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);

    $info = json_decode($response,true)['data']['country'];
    if($info == null){
    $info = '未知';    
    }
      return $info;
     }
     
  
     }


  /**
     * 智能过滤
     * 
     */
     
    public static function getLastCommentTimeByIp($ipAddress) {
    	$db = \Typecho\Db::get();
    $ipFind = $db->fetchRow($db->select()->from('table.comments')->where('table.comments.ip = ?', $ipAddress)->order('table.comments.created', \Typecho\Db::SORT_DESC));;
    if($ipFind){
        return $ipFind['created'];
    }
    return 0;
}

    public static function containsHiddenCharacters($str) {
    $hiddenChars = [
        "\x01", // SOH (Start of Heading)
        "\x02", // STX (Start of Text)
        "\x03", // ETX (End of Text)
        "\x04", // EOT (End of Transmission)
        "\x05", // ENQ (Enquiry)
        "\x06", // ACK (Acknowledge)
        "\x07", // BEL (Bell)
        "\x08", // BS (Backspace)
        "\x09", // HT (Horizontal Tab)
        "\x0A", // LF (Line Feed)
        "\x0B", // VT (Vertical Tab)
        "\x0C", // FF (Form Feed)
        "\x0D", // CR (Carriage Return)
        "\x0E", // SO (Shift Out)
        "\x0F", // SI (Shift In)
        "\x10", // DLE (Data Link Escape)
        "\x11", // DC1 (Device Control 1)
        "\x12", // DC2 (Device Control 2)
        "\x13", // DC3 (Device Control 3)
        "\x14", // DC4 (Device Control 4)
        "\x15", // NAK (Negative Acknowledge)
        "\x16", // SYN (Synchronous Idle)
        "\x17", // ETB (End of Transmission Block)
        "\x18", // CAN (Cancel)
        "\x19", // EM (End of Medium)
        "\x1A", // Substitute character
        "\x1B", // ESC (Escape)
        "\x1C", // File Separator
        "\x1D", // Group Separator
        "\x1E", // Record Separator
        "\x1F"  // Unit Separator
    ];

    foreach ($hiddenChars as $char) {
        if (strpos($str, $char) !== false) {
            return true;
        }
    }

    return false;
}
    public static function isUrl($text) {
    //$urlPattern = '/^(https?:\/\/)?([\da-z\.-]+)\.([a-z\.]{2,6})([\/\w \.-]*)*\/?$/';
    //return preg_match($urlPattern, $text);
    $urlPattern = '/\b(https?:\/\/)?([\da-z\.-]+)\.([a-z\.]{2,6})([\/\w \.-]*)*\/?\b/';
    return preg_match($urlPattern, $text);
}

	public static function isBotComment($comment, $timeSinceLastComment = 60){
	//一些常见的机器人用户代理字符串
    $botUserAgents = [
        'bot',
        'crawl',
        'spider',
        'robot',
        'crawling',
        'fetch',
        'indexing',
        'mediapartners'
    ];

    // 检查用户代理是否包含机器人关键词
    foreach ($botUserAgents as $botKeyword) {
        if (stripos($comment['agent'], $botKeyword) !== false) {
            return true;
        }
    }

    // 检查评论内容是否过于简单或重复
    if (strlen($comment['text']) < 10 || preg_match('/^\s*$/', $comment['text'])) {
        return true;
    }

    // 检查IP地址是否在短时间内发送了多个评论
    $lastCommentTime = self::getLastCommentTimeByIp($comment['ip']);
    if ($lastCommentTime && (time() - $lastCommentTime) < $timeSinceLastComment) {
        return true;
    }
    // 检查评论内容是否包含隐藏字符
    if (self::containsHiddenCharacters($comment['text'])) {
        return true;
    }
    // 检查评论昵称是否包含网址
    if (self::isUrl($comment['author'])) {
        return true;
    }
    // 如果以上条件都不满足，则认为不是机器人评论
    return false;
	}
	
	
 public static function filter($comment,$post){
 $options = bsOptions::getInstance()::get_option( 'bearsimple' );
 $db = \Typecho\Db::get();
 $removeChar = ["https://", "http://"]; 
$vtype = $_POST['vtype'] ?? '';
// ponytail: HTTP_REFERER 未定义时视为来源错误；strpos 命中位置 0 曾被 == false 误判为来源错误，改用 === false
if (strpos($_SERVER['HTTP_REFERER'] ?? '', str_replace($removeChar, "", Helper::options()->siteUrl)) === false ) {
    throw new \Typecho\Widget\Exception("抱歉，检测到来源错误，请在正确的页面进行评论。",200);
}
if ($vtype === '' && (empty($_SESSION['token']) || !hash_equals((string)$_SESSION['token'], (string)($_POST['SecurityToken'] ?? '')))){
    throw new \Typecho\Widget\Exception("抱歉，安全检测未通过，请刷新页面后重新进行评论。",200);
}
 if(Plugin::xsscheck($comment['text'])) {
			\Typecho\Cookie::set('__typecho_remember_text', $comment['text']);
			throw new \Typecho\Widget\Exception("抱歉，系统检测到您的评论包含危险内容，请返回修改后再试。",200);
        }
$comment['url'] =htmlspecialchars($comment['url'],ENT_QUOTES,'UTF-8');
        if ($comment['mail'] !== null && preg_match("/^[A-Za-z0-9\-]+([_\.][A-Za-z0-9\-]+)*@([A-Za-z0-9\-]+\.)+[A-Za-z]{2,}$/", $comment['mail']) !== 1) {
    throw new \Typecho\Widget\Exception("抱歉，系统检测到您的评论包含危险内容，请返回修改后再试。", 200);                      
}
 if(empty($vtype)){ //判断POST过来的vtype字段是否为指定类型或为空
 

         if (strlen(trim(preg_replace('/\xc2\xa0/',' ',$comment['text']))) == 0) {
			\Typecho\Cookie::set('__typecho_remember_text', $comment['text']);
			throw new \Typecho\Widget\Exception("抱歉，系统检测到您的评论内容只有空格，请返回修改后再试。",200);
            }
         if($options['BearSpam_Auto'] == true){
        if (self::isBotComment($comment)) {
              throw new \Typecho\Widget\Exception("抱歉，本条评论被判定为机器人评论，已自动拦截。",200);  
            }
            }   
      
        if($options['BearSpam_Pro'] == true){
        if (!empty($options['BearSpam_IP'])) {
			if (Plugin::check_ip($options['BearSpam_IP'], $comment['ip'])) {
			throw new \Typecho\Widget\Exception("抱歉，系统检测到您的IP处于屏蔽范围内，已拦截本条评论。",200);
			  \Typecho\Cookie::set('__typecho_remember_text', $comment['text']);
			}

            
        }
		if (!empty($options['BearSpam_EMAIL'])) {
			if (Plugin::check_in($options['BearSpam_EMAIL'], $comment['mail'])) {
				throw new \Typecho\Widget\Exception("抱歉，系统检测到您的邮箱处于屏蔽范围内，已拦截本条评论。",200);
				  \Typecho\Cookie::set('__typecho_remember_text', $comment['text']);
			}
		}
		
			if (!empty($options['BearSpam_NAME'])) {
			if (Plugin::check_in($options['BearSpam_NAME'], $comment['author'])) {
			    
				throw new \Typecho\Widget\Exception("抱歉，系统检测到您的昵称存在敏感禁止词汇，已拦截本条评论。",200);
				  \Typecho\Cookie::set('__typecho_remember_text', $comment['text']);
			}
		}
		
		if (!empty($options['BearSpam_URL'])) {
			if (Plugin::check_in($options['BearSpam_URL'], $comment['url'])) {
			    			throw new \Typecho\Widget\Exception("抱歉，系统检测到您的网址处于屏蔽范围内，已拦截本条评论。",200);
			\Typecho\Cookie::set('__typecho_remember_text', $comment['text']);
			}
			}
			
			if (!empty($options['BearSpam_ArticleTitle'])&& $options['BearSpam_ArticleTitle'] == "1") {
			$db = \Typecho\Db::get();
            // 获取评论所在文章
            $pot = $db->fetchRow($db->select('title')->from('table.contents')->where('cid = ?', $comment['cid']));        
            if(strstr($comment['text'], $pot['title'])){
                		throw new \Typecho\Widget\Exception("抱歉，系统检测到您的评论内容疑似存在灌水内容，已自动拦截。",200);
\Typecho\Cookie::set('__typecho_remember_text', $comment['text']);
			}
			}
			
			if (!empty($options['BearSpam_NAMEMIN'])) {    
            if(Plugin::strLength($comment['author']) < $options['BearSpam_NAMEMIN']){

			throw new \Typecho\Widget\Exception("抱歉，系统检测到您的评论昵称过短，已自动拦截。",200);
			\Typecho\Cookie::set('__typecho_remember_text', $comment['text']);
			}
			}
			
			if (!empty($options['BearSpam_NAMEMAX'])) {    
            if (Plugin::strLength($comment['author']) > $options['BearSpam_NAMEMAX']) {
                throw new \Typecho\Widget\Exception("抱歉，系统检测到您的评论昵称过长，已自动拦截。",200);
			\Typecho\Cookie::set('__typecho_remember_text', $comment['text']);
			}}
			
			if (!empty($options['BearSpam_NAMEURL'])&& $options['BearSpam_NAMEURL'] == "1") {    
            if (preg_match(" /^((https?|ftp|news):\/\/)?([a-z]([a-z0-9\-]*[\.。])+([a-z]{2}|aero|arpa|biz|com|coop|edu|gov|info|int|jobs|mil|museum|name|nato|net|org|pro|travel)|(([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\.){3}([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5]))(\/[a-z0-9_\-\.~]+)*(\/([a-z0-9_\-\.]*)(\?[a-z0-9+_\-\.%=&]*)?)?(#[a-z][a-z0-9_]*)?$/ ", $comment['author']) > 0) {
			                throw new \Typecho\Widget\Exception("抱歉，系统检测到您的评论昵称异常，已自动拦截。",200);
			\Typecho\Cookie::set('__typecho_remember_text', $comment['text']);
			}}
			
			if (!empty($options['BearSpam_Chinese'])&& $options['BearSpam_Chinese'] == "1") {    
            if (preg_match("/[\x{4e00}-\x{9fa5}]/u", $comment['text']) == 0) {
			throw new \Typecho\Widget\Exception("抱歉，系统检测到您的评论内容不包含中文，已自动拦截。",200);
			\Typecho\Cookie::set('__typecho_remember_text', $comment['text']);
			}}
			
			if (!empty($options['BearSpam_MIN'])) {    
            if(Plugin::strLength($comment['text']) < $options['BearSpam_MIN']){         
				throw new \Typecho\Widget\Exception("抱歉，系统检测到您的评论内容字数过少，已自动拦截。",200);
			\Typecho\Cookie::set('__typecho_remember_text', $comment['text']);
			}}
			
			if (!empty($options['BearSpam_MAX'])) {    
            if(Plugin::strLength($comment['text']) > $options['BearSpam_MAX']){         
				throw new \Typecho\Widget\Exception("抱歉，系统检测到您的评论内容字数过多，已自动拦截。",200);
			\Typecho\Cookie::set('__typecho_remember_text', $comment['text']);
			}}
			
			if (!empty($options['BearSpam_Words'])) {    
            if (Plugin::check_in($options['BearSpam_Words'], $comment['text'])) { 
	throw new \Typecho\Widget\Exception("抱歉，系统检测到您的评论内容包含敏感禁止词汇，已自动拦截。",200);
			\Typecho\Cookie::set('__typecho_remember_text', $comment['text']);
			}
					    
			}
        }
         if ($options['VerifyChoose'] == '22-2'){
            $answer = $_POST['answer'];
            $ganswer = self::getSecurity('get','__typecho_comment_question_answer');
             switch($answer){
        case $ganswer:
        break;
        case null:
        throw new \Typecho\Widget\Exception('对不起，请输入验证码。',200);
        break;
        default:
        throw new \Typecho\Widget\Exception('对不起，验证码错误，请重试。',200);
           
         }
         }
       if ($options['VerifyChoose'] == '1' || $options['VerifyChoose'] == '11'){
           $sum = $_POST['sum'];
           switch($_POST['spam_protection_mathstyle']){
               case 'addition':
                   switch($sum){
        case $_POST['num1'] + $_POST['num2']:
        break;
        case null:
        throw new \Typecho\Widget\Exception('对不起，请输入验证码。',200);
        break;
        default:
        throw new \Typecho\Widget\Exception('对不起，验证码错误，请重试。',200);
    }
                   break;
               case 'subtraction':
                    switch($sum){
        case $_POST['num1'] - $_POST['num2']:
        break;
        case null:
        throw new \Typecho\Widget\Exception('对不起，请输入验证码。',200);
        break;
        default:
        throw new \Typecho\Widget\Exception('对不起，验证码错误，请重试。',200);
    }
                   break;
           }
       } 
      if ($options['VerifyChoose']== '2-2' && !empty($options['turnstile_key']) && !empty($options['turnstile_secretkey']) && $options['backendVerify_Turnstile'] == true) {
     if(empty($_POST['cf-turnstile-response'])){
         \Typecho\Cookie::set('__typecho_remember_text', $comment['text']);
			throw new \Typecho\Widget\Exception("抱歉，系统检测到您的验证密钥已失效，需重新验证！",200); 
     }
     elseif (isset($_POST['cf-turnstile-response'])) {
$response_data = self::getTurnstileResult($_POST['cf-turnstile-response'], $options['turnstile_secretkey']);
if ($response_data['success']) {
                if ($response_data['action'] == 'comment') {
                    return $comment;
                } else {
                    \Typecho\Cookie::set('__typecho_remember_text', $comment['text']);
                    throw new Typecho_Widget_Exception(_t(self::getTurnstileResultMsg('场景验证失败')),200);
                }
            } else {
                \Typecho\Cookie::set('__typecho_remember_text', $comment['text']);
                throw new Typecho_Widget_Exception(_t(self::getTurnstileResultMsg($response_data)),200);
            }
        } else {
            \Typecho\Cookie::set('__typecho_remember_text', $comment['text']);
            throw new Typecho_Widget_Exception(_t('加载验证码失败, 请检查你的网络'),200);
        }

 }
 
 
        if ($options['VerifyChoose']== '2' && !empty($options['vid']) && !empty($options['vkey']) && $options['backendVerify_Vaptcha'] == true) {
     if(empty($_POST['vaptcha_server']) || empty($_POST['vaptcha_token'])){
         \Typecho\Cookie::set('__typecho_remember_text', $comment['text']);
			throw new \Typecho\Widget\Exception("抱歉，系统检测到您的验证密钥已失效，需重新验证！",200); 
     }
 $url = $_POST['vaptcha_server'];
 $data = array(
     'id'=> $options['vid'],
     'secretkey' => $options['vkey'],
     'scene'=> '1',
     'token'=> $_POST['vaptcha_token'],
     'ip'=> $comment['ip']
     );
    $ch = curl_init();  
    curl_setopt($ch,CURLOPT_URL,$url);
    curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);  
    curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,3);  
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type'=>'application/json'));
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    $handles = curl_exec($ch);  
    curl_close($ch);  
    $result = json_decode($handles,true);
    if($result['success'] == 0){
        \Typecho\Cookie::set('__typecho_remember_text', $comment['text']);
			throw new \Typecho\Widget\Exception("抱歉，您未通过人机验证，原因:".$result['msg'],200);
    }
 }
  if ($options['VerifyChoose']== '2-3' && !empty($options['geeid']) && !empty($options['geekey']) && $options['backendVerify_Geetest'] == true) {
      $captcha_id = $options['geeid'];
$captcha_key = $options['geekey'];
$api_server = "https://gcaptcha4.geetest.com";
$lot_number = $_POST['lot_number'];
$captcha_output = $_POST['captcha_output'];
$pass_token = $_POST['pass_token'];
$gen_time = $_POST['gen_time'];
$sign_token = hash_hmac('sha256', $lot_number, $captcha_key);
$query = array(
    "lot_number" => $lot_number,
    "captcha_output" => $captcha_output,
    "pass_token" => $pass_token,
    "gen_time" => $gen_time,
    "sign_token" => $sign_token
);
$url = sprintf($api_server . "/validate" . "?captcha_id=%s", $captcha_id);
$res = self::post_request($url,$query);
$obj = json_decode($res,true);

if($obj['status'] == 'success' && $obj['result'] == 'fail'){
    \Typecho\Cookie::set('__typecho_remember_text', $comment['text']);
			throw new \Typecho\Widget\Exception("抱歉，您未通过人机验证，原因:".$obj['reason'],200);
}
if($obj['status'] == 'error' && $obj['code']){
    \Typecho\Cookie::set('__typecho_remember_text', $comment['text']);
			throw new \Typecho\Widget\Exception("抱歉，您未通过人机验证，错误码:".$obj['code']."，原因:".$obj['msg'],200);
}

  }
 if ($options['VerifyChoose']== '2-1' && !empty($options['dx_domain']) && !empty($options['dx_appId']) && !empty($options['dx_appSecret']) && $options['backendVerify_Dingxiang'] == true) {
     
     if(empty($_POST['dx_token'])){
         \Typecho\Cookie::set('__typecho_remember_text', $comment['text']);
			throw new \Typecho\Widget\Exception("抱歉，系统检测到您的验证密钥已失效，需重新验证！",200); 
     }
   $appId = $options['dx_appId'];
$appSecret = $options['dx_appSecret'];
$client = new CaptchaClient($appId,$appSecret);
$client->setTimeOut(2);
$client->setCaptchaUrl("https://".$options['dx_domain']."/api/tokenVerify");
$response = $client->verifyToken($_POST['dx_token']);
if(!$response->result){
    \Typecho\Cookie::set('__typecho_remember_text', $comment['text']);
			throw new \Typecho\Widget\Exception("抱歉，您未通过人机验证，请返回重试。",200);
}
 }
 
            \Typecho\Cookie::delete('__typecho_remember_text');
            if(empty($comment['mail'])){
                $comment['mail'] = self::GetRandStr(17).'@privatemail.com';
                \Typecho\Cookie::set('__typecho_remember_mail', $comment['mail']);
            }
            if($_POST['privatecomment'] == 'on'){
                $comment['text'] = '@私密@'.$comment['text'];
            }
            
 }


        return $comment;
 }

public static function filter2($comment)
    {
        $options = get_option('bearsimple');
      $db = \Typecho\Db::get();
 if ($options['Comment_ipget'] == true && $options['Comment_ipget_data'] == true){
     $ip = array(
                'coid' => $comment->coid,
                'location' => self::getIpLocation($comment->ip),
            );
            $db->query($db->insert('table.bscore_iplocation_data')->rows($ip));
     
 }
 if(($_POST['vtype'] ?? '') == 'friendcircle'){
     if(($_POST['getRead'] ?? '') == 'on'){
         $private = true;
     }
     else{
         $private = false;
     }
     if(($_POST['resources'] ?? '') !== ''){
      $_POST['resources'] = json_encode(explode(',',$_POST['resources']));
     }
     $circle_data = array(
                'coid' => $comment->coid,
                'location' => $_POST['getMapInput'] ?? '',
                'private' => $private,
                'resources' => $_POST['resources'],
            );
    $db->query($db->insert('table.bscore_friendcircle_data')->rows($circle_data));
 }
return true; 

           
    }
    private static function getTurnstileResult($turnstile_response, $secretKey)
    {
        $beforeload = array('secret' => $secretKey, 'response' => $turnstile_response);
        $beforeload['remoteip'] = $_SERVER['REMOTE_ADDR'];
        $stream = stream_context_create(array(
            'http' => array(
                'method' => 'POST',
                'content' => http_build_query($beforeload)
            )
        ));
        $response = file_get_contents("https://challenges.cloudflare.com/turnstile/v0/siteverify", false, $stream);
        $response = json_decode($response, true);
        return $response;
    }

    private static function getTurnstileResultMsg($resp)
    {
        if ($resp['success'] == true) {
            return '验证通过';
        } else {
            switch (strtolower($resp['error-codes'][0])) {
                case 'missing-input-response':
                    return '请先完成验证';

                case 'invalid-input-response':
                    return '验证无效或已过期';

                case 'timeout-or-duplicate':
                    return '验证密钥已被使用, 请重新验证';

                case 'bad-request':
                    return '呜呜呜验证失败了,要不 再试一次?';

                case 'internal-error':
                    return '验证服务器寄了, 再试一次吧';

                case 'missing-input-secret':
                case 'invalid-input-secret':
                    return '未设置或设置了无效的 secretKey';

                default:
                    return '还请再试一次！';
            }
        }
    }
    
 //文件缓存
 
public static function getCache_file(){
        $req = \Typecho\Request::getInstance();
        $url = $req->getRequestUri();


        if(!$req->isGet()){
            return;
        }
        if(strstr($url,'/action/') !== false || strstr($url,'/admin/') !== false){
            
            return;
        }

        $hash = md5($url);

        @$settings = bsOptions::getInstance()::get_option( 'bearsimple' );
        if(!$settings) return;


        $cache_timeout = intval($settings['cache_timeout']);

        $cache_root = $settings['cache_dir'];
        if(strstr($cache_root, '/') !== 0 ){
            $cache_root = __TYPECHO_ROOT_DIR__.'/usr/'.$cache_root;
        }

        if($cache_timeout <= 0){
            $cache_timeout = 60; //1min
        }

        $file = self::hash2dir($hash,$cache_root).$hash.".gz";
        if(file_exists($file)){
            $filetime = filemtime($file);
            if($filetime !== false && time() - $filetime < $cache_timeout){
                $fh = fopen($file, "rb");
                $content = fread($fh,filesize ($file));
                fclose($fh);
                $html = gzuncompress($content);
                echo $html;
                exit(0);
            }
        }else{
        
        }
    }

    public static function setCache_file(){
        $req = \Typecho\Request::getInstance();
        $url = $req->getRequestUri();
        
        if(!$req->isGet()){
            return;
        }
        if(strstr($url,'/action/') !== false || strstr($url,'/admin/') !== false){
            return;
        }
       $_routingTable = Helper::options()->routingTable;
        $post_regx = $_routingTable[0]['post']['regx'];
        if (array_key_exists('TePass', \Typecho\Plugin::export()['activated'])){
        if (preg_match($post_regx,$path,$arr)){
            if ($arr[1] and !empty($arr[1])){
                $db = \Typecho\Db::get();
                try {
		    $database = $db->getConfig($db::READ)['database'];
                    $tepass_exist = $db->fetchRow($db->select()->from('information_schema.TABLES')->where('TABLE_NAME = ?',$db->getPrefix().'tepass_posts')->where('TABLE_SCHEMA = ?',$database));
                    if (isset($tepass_exist) and count($tepass_exist) > 0){
                          $p_id = $db->fetchObject($db->select('id')->from('table.tepass_posts')->where('post_id = ?',$arr[1]))->id;
                          if ($p_id) return;
                    }

                }catch (Typecho_Db_Query_Exception $e){
                  
                }

            }
        }
        }
        
        @$settings = bsOptions::getInstance()::get_option( 'bearsimple' );
        if(!$settings) return;
        $cache_root = $settings['cache_dir'];
        if(strstr($cache_root, '/') !== 0 ){
            $cache_root = __TYPECHO_ROOT_DIR__.'/usr/'.$cache_root;
        }

        $hash = md5($url);
        $file = self::hash2dir($hash,$cache_root).$hash.".gz";
        $dir = dirname($file);

        if(!file_exists($dir)){
            $ret = mkdir($dir,0777,true);
            if(!$ret){
                return;
            }
        }

        $html = ob_get_contents();
        $html_gz = gzcompress($html);
        $fp = fopen($file, 'w');
        fwrite($fp, $html_gz);
        fclose($fp);
    }

    private static function hash2dir($hash,$base_dir=""){
        $dir="";
        for($i = 0; $i < strlen($hash) ; $i+=2){
            $dir = $dir."/".substr($hash, $i, 2);
        }
        return rtrim($base_dir,'/').$dir.'/';
    }
 
 
 //代码高亮
     public static function headlink($cssUrl) {
        
        $options = Helper::options();
    
        $dir = $options->themeUrl.'/';
   
    if(bsOptions::getInstance()::get_option('bearsimple' )['Codehightlight'] == true){
        $style = bsOptions::getInstance()::get_option('bearsimple' )['code_style'];
        if(empty($style)){
            $style = 'coy.css';
        }
        else{
       $style = bsOptions::getInstance()::get_option( 'bearsimple' )['code_style'];
        }
        $cssUrl = $dir.'modules/codehightlight/static/styles/' . $style.'?v=2.9.9';

        echo '<link rel="stylesheet" type="text/css" href="' . $cssUrl . '" />';

    }
    // ponytail: 播放器 CSS/JS 移至 footlink 按需注入（仅页面含 .dplayer/音频短代码时加载，省 350KB）；
    // 此声明必须无条件输出——正文音频短代码会 push APlayerOptions，原先藏在 Codehightlight 条件内是隐藏缺陷
    echo '<script>var APlayers = [];var APlayerOptions = [];</script>';
    }

    /**
     * 底部脚本
     *
     * @access public
     * @param unknown $footlink
     * @return unknown
     */
    public static function footlink() {
      $options = Helper::options();
        $dir = $options->themeUrl.'/';


  if(bsOptions::getInstance()::get_option( 'bearsimple' )['article_videoExt'] !== '' && is_array(bsOptions::getInstance()::get_option( 'bearsimple' )['article_videoExt'])  && in_array('ext_hls',bsOptions::getInstance()::get_option( 'bearsimple' )['article_videoExt'])){
      echo <<<HTML
  <script src="{$dir}assets/vendors/player/hls.min.js"></script>
HTML;
  }
  if(bsOptions::getInstance()::get_option( 'bearsimple' )['article_videoExt'] !== '' && is_array(bsOptions::getInstance()::get_option( 'bearsimple' )['article_videoExt']) && in_array('ext_flv',bsOptions::getInstance()::get_option( 'bearsimple' )['article_videoExt'])){
      echo <<<HTML
  <script src="{$dir}assets/vendors/player/flv.min.js"></script>
HTML;
  }
  echo <<<HTML
  <script type="text/javascript">
  // ponytail: 播放器按需加载——仅当页面存在 .dplayer 容器或音频短代码 push 过 APlayerOptions 时注入，pjax 幂等
  function bsLoadPlayers() {
    var head = document.head;
    function injectCss(href) {
      if (!head.querySelector('link[href="' + href + '"]')) {
        var l = document.createElement('link');
        l.rel = 'stylesheet';
        l.href = href;
        head.appendChild(l);
      }
    }
    function loadJs(src, cb) {
      var s = document.createElement('script');
      s.src = src;
      s.onload = cb;
      head.appendChild(s);
    }
    if (document.querySelector('.dplayer') && typeof DPlayer === 'undefined' && !window.bsDPlayerLoading) {
      window.bsDPlayerLoading = true;
      injectCss('{$dir}assets/vendors/player/player.min.css?v=2.3.4');
      loadJs('{$dir}assets/vendors/player/player.min.js?v=2.3.4', function () {
        if (typeof loadDPlayer === 'function') { loadDPlayer(); }
        else { loadJs('{$dir}assets/vendors/player/load-player.js', function () { loadDPlayer(); }); }
      });
    } else if (typeof loadDPlayer === 'function' && !window.bsDPlayerLoading) {
      loadDPlayer();
    }
    if (window.APlayerOptions && window.APlayerOptions.length && typeof APlayer === 'undefined' && !window.bsAPlayerLoading) {
      window.bsAPlayerLoading = true;
      injectCss('{$dir}assets/vendors/mp3/mp3.min.css?v=2.3.4');
      loadJs('{$dir}assets/vendors/mp3/mp3.min.js?v=2.3.4', function () {
        if (typeof loadAPlayer === 'function') { loadAPlayer(); }
        else { loadJs('{$dir}assets/vendors/mp3/load-mp3.js', function () { loadAPlayer(); }); }
      });
    } else if (typeof loadAPlayer === 'function' && !window.bsAPlayerLoading) {
      loadAPlayer();
    }
  }
  $(document).on('pjax:complete', bsLoadPlayers);
  if (document.readyState !== 'loading') { bsLoadPlayers(); }
  else { document.addEventListener('DOMContentLoaded', bsLoadPlayers); }
  </script>
HTML;

if(bsOptions::getInstance()::get_option('bearsimple')['seo_push'] == true && bsOptions::getInstance()::get_option('bearsimple')['seotabs']['baidu_token'] !== ''){
        echo PHP_EOL.'<script>
(function(){
  var bp = document.createElement("script");
  var curProtocol = window.location.protocol.split(":")[0];
  if (curProtocol === "https"){
    bp.src = "https://zz.bdstatic.com/linksubmit/push.js";
  }else{
    bp.src = "http://push.zhanzhang.baidu.com/push.js";
  }
    var s = document.getElementsByTagName("script")[0];
    s.parentNode.insertBefore(bp, s);
})();
</script>'.PHP_EOL;
}
if(bsOptions::getInstance()::get_option('bearsimple')['AdControl'] == true && bsOptions::getInstance()::get_option('bearsimple')['AdControl_Google'] == true){
echo '<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"></script>';
}
    }
    
    
    /**解析表情**/
public static function getEmo($comment){
    $options = Helper::options();
    $Temoptions = get_option('bearsimple');
        $opts = array(
  'http'=>array(
   'method' => 'GET',
          'header' => 'Content-type: application/json',
          'timeout' => 60 * 10,
       'Connection'=>"close"
  )
);
$context = stream_context_create($opts);

        if($Temoptions['Emoji_HideDefault'] == false || $Temoptions['Emoji_HideDefault'] == ''){
        // ponytail: 表情图标已本地化到主题 assets，JSON 内存相对子路径，输出前拼站点主题 URL（原为第三方 CDN 绝对链接）
        // ponytail: str_replace("/plugins","") 在 Windows 反斜杠路径下不匹配导致路径错位，改用 dirname(__DIR__,2) 直接定位 usr 目录
        $rawJson = (string)file_get_contents(dirname(__DIR__, 2).'/themes/bearsimple/assets/vendors/bs-emoji/bs-emoji.json', false, $context);
        $res = json_decode(str_replace('assets/vendors/bs-emoji/emoji/', $options->themeUrl.'/assets/vendors/bs-emoji/emoji/', $rawJson),true);
        $emo = $res;
        }
        if($Temoptions['Emoji_Diy'] == true && $Temoptions['Emoji_DiyUrl'] !== ''){
        $res2 = json_decode(file_get_contents($Temoptions['Emoji_DiyUrl'], false, $context),true);
        if($Temoptions['Emoji_HideDefault'] == false || $Temoptions['Emoji_HideDefault'] == ''){
        $emo = array_merge($res,$res2);
        }
        else{
        $emo = $res2;    
        }
        }
        
        


    foreach ($emo as $v){
        if($v['category'] !== ''){
                if($type == 'comment'){
                $comment = str_replace($v['data'], '<img width="30" height="30" src='.$v['icon'] .''.' loading="lazy">', $comment);
                }
                elseif($type == 'reply'){
                $comment = str_replace($v['data'], '<img width="20" height="20" src='.$v['icon'] .''.' loading="lazy">', $comment);
                }
                else{
                $comment = str_replace($v['data'], '<img width="30" height="30" src='.$v['icon'] .''.' loading="lazy">', $comment);                    
                }
        }
    }
    //当评论没有私密评论时去除私密评论识别标签
    $comment = str_replace('@私密@', '', $comment);
    return $comment;
}

    //邮件
    public static function parseCommentMark($comments,$comment,$status)
    {
        $options = \Typecho\Widget::widget('Widget_Options');
        $Temoptions = get_option('bearsimple');
        if($status == 'approved'){
        $cfg = array(
            'siteTitle' => $options->title,
            'timezone'  => $options->timezone,
            'cid'       => $comment->cid,
            'coid'      => $comment->coid,
            'created'   => $comment->created,
            'author'    => $comment->author,
            'authorId'  => $comment->authorId,
            'ownerId'   => $comment->ownerId,
            'mail'      => $comment->mail,
            'ip'        => $comment->ip,
            'title'     => $comment->title,
            'text'      => self::getEmo($comment->text),
            'permalink' => $comment->permalink,
            'status'    => $status,
            'parent'    => $comment->parent,
            'manage'    => $options->adminUrl . "/manage-comments.php"
        );
    self::$_isMailLog = in_array('4', $Temoptions['CommentNotify__OtherSetting']) ? true : false;
        if($_POST['accept'] == 'on'){
            $cfg['banMail'] = 1;
        }
        else{
            $cfg['banMail'] = 0;
        }

        $fileName = \Typecho\Common::randString(12);
        $cfg      = (object)$cfg;
        file_put_contents(dirname(__FILE__) . '/cache/' . $fileName, serialize($cfg));
        $url = ($options->rewrite) ? $options->siteUrl : $options->siteUrl . 'index.php';
        $url = rtrim($url, '/') . '/action/' . self::$action . '?send=' . $fileName;

        $date = new \Typecho\Date(\Typecho\Date::gmtTime());
        $time = $date->format('Y-m-d H:i:s');

        self::saveLog("{$time} 开始发送请求：{$comment->parent}{$url}\n");
        self::asyncRequest($url);
    }
    }
    /**
     * 获取邮件内容
     *
     * @access public
     * @param $comment 调用参数
     * @return void
     */
    public static function parseComment($comment)
    {
        $options = \Typecho\Widget::widget('Widget_Options');
        $Temoptions = get_option('bearsimple');
        if($_POST['vtype'] == 'friendcircle'){
         return true;   
        }
        $cfg = array(
            'siteTitle' => $options->title,
            'timezone'  => $options->timezone,
            'cid'       => $comment->cid,
            'coid'      => $comment->coid,
            'created'   => $comment->created,
            'author'    => $comment->author,
            'authorId'  => $comment->authorId,
            'ownerId'   => $comment->ownerId,
            'mail'      => $comment->mail,
            'ip'        => $comment->ip,
            'title'     => $comment->title,
            'text'      => self::getEmo($comment->text),
            'permalink' => $comment->permalink,
            'status'    => $comment->status,
            'parent'    => $comment->parent,
            'manage'    => $options->adminUrl . "/manage-comments.php"
        );

      
 self::$_isMailLog = in_array('4', $Temoptions['CommentNotify__OtherSetting']) ? true : false;
        if($_POST['accept'] == 'on'){
            $cfg['banMail'] = 1;
        }
        else{
            $cfg['banMail'] = 0;
        }

        $fileName = \Typecho\Common::randString(12);
        $cfg      = (object)$cfg;
        file_put_contents(dirname(__FILE__) . '/cache/' . $fileName, serialize($cfg));
        $url = ($options->rewrite) ? $options->siteUrl : $options->siteUrl . 'index.php';
        $url = rtrim($url, '/') . '/action/' . self::$action . '?send=' . $fileName;

        $date = new \Typecho\Date(\Typecho\Date::gmtTime());
        $time = $date->format('Y-m-d H:i:s');
       
        self::saveLog("{$time} 开始发送请求：{$url}\n");
        self::asyncRequest($url);
    }



    public static function asyncRequest($url)
{
    self::isAvailable();
        self::$_adapter == 'Socket' ? self::socket($url) : self::curl($url);
}

    /**
     * Socket 请求
     * @param $url
     * @return bool
     */
    public static function socket($url)
    {
        $params = parse_url($url);
        $path = $params['path'] . '?' . $params['query'];
        $host = $params['host'];
        $port = 80;
        $scheme = '';

        if ('https' == $params['scheme']) {
            $port = 443;
            $scheme = 'ssl://';
        }

        if (function_exists('fsockopen')) {
            $fp = @fsockopen($scheme . $host, $port, $errno, $errstr, 30);
        } elseif (function_exists('pfsockopen')) {
            $fp = @pfsockopen($scheme . $host, $port, $errno, $errstr, 30);
        } else {
            $fp = stream_socket_client($scheme . $host . ":$port", $errno, $errstr, 30);
        }

        if ($fp === false) {
            self::saveLog("SOCKET错误," . $errno . ':' . $errstr);
            return false;
        }

        $out = "GET " . $path . " HTTP/1.1\r\n";
        $out .= "Host: $host\r\n";
        $out .= "Connection: Close\r\n\r\n";

        self::saveLog("Socket 方式发送\r\n");

        fwrite($fp, $out);
        sleep(1);
        fclose($fp);
        self::saveLog("请求结束\r\n");
    }

    /**
     * Curl 请求
     * @param $url
     */
    public static function curl($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTPGET, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);  // 将curl_exec()获取的信息以文件流的形式返回,不直接输出。  
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);  // 连接等待时间  
        curl_setopt($ch, CURLOPT_TIMEOUT, 1);         // curl允许执行时间

        self::saveLog("Curl 方式发送\r\n");

        curl_exec($ch);
        curl_close($ch);
        self::saveLog("请求结束\r\n");
    }

    /**
     * 检测 适配器
     * @return string
     */
    public static function isAvailable()
    {
        function_exists('ini_get') && ini_get('allow_url_fopen') && (self::$_adapter = 'Socket');
        false == self::$_adapter && function_exists('curl_version') && (self::$_adapter = 'Curl');

        return self::$_adapter;
    }

    /**
     * 检测 是否可写
     * @param $file
     * @return bool
     */
    public static function isWritable($file)
    {
        if (is_dir($file)) {
            $dir = $file;
            if ($fp = @fopen("$dir/check_writable", 'w')) {
                @fclose($fp);
                @unlink("$dir/check_writable");
                $writeable = true;
            } else {
                $writeable = false;
            }
        } else {
            if ($fp = @fopen($file, 'a+')) {
                @fclose($fp);
                $writeable = true;
            } else {
                $writeable = false;
            }
        }

        return $writeable;
    }
    /**
     * 写入记录
     * @param $content
     * @return bool
     */
    public static function saveLog($content)
    {
        if (!self::$_isMailLog) {
            return false;
        }

        file_put_contents(dirname(__FILE__) . '/log/mailer_log.txt', $content, FILE_APPEND);
    }
    
    public function sign($msg, $key)
    {
        $signature = '';
        $success = openssl_sign($msg, $signature, $key, 'SHA256');
        if(!$success){
            return false;
        }
        return $signature;
    }


    public function urlEncode($input)
    {
        return str_replace('=', '', strtr(base64_encode($input), '+/', '-_'));
    }
    public function encode($payload, $key, $alg = 'RS256')
    {
        $header = array('typ' => 'JWT', 'alg' => $alg);
        $segments = array();
        $segments[] = $this->urlEncode(json_encode($header));
        $segments[] = $this->urlEncode(json_encode($payload));
        $signing_input = implode('.', $segments);
        $signature = $this->sign($signing_input, $key);
        $segments[] = $this->urlEncode($signature);
        return implode('.', $segments);
    }
    
    //百度推送
    public static function seo_publishpush($content, $edit)
    {
        $bd_api = get_option('bearsimple')['seotabs']['baidu_token'];
        $sm_api = get_option('bearsimple')['seotabs']['shenma_token'];
        $bing_api = get_option('bearsimple')['seotabs']['bing_token'];
        $toutiao_api = get_option('bearsimple')['seotabs']['toutiao_token'];
        $indexnow_api = get_option('bearsimple')['seotabs']['indexnow_token'];
        $siteUrl = Helper::options()->siteUrl;
         $adminUrl = Helper::options()->adminUrl;
         $db = \Typecho\Db::get();
        $content['cid'] = $edit->cid;
        $content['slug'] = $edit->slug;
        
        //获取分类缩略名
        $content['category'] = urlencode(current(\Typecho\Common::arrayFlatten($db->fetchAll($db->select()->from('table.metas')
            ->join('table.relationships', 'table.relationships.mid = table.metas.mid')
            ->where('table.relationships.cid = ?', $content['cid'])
            ->where('table.metas.type = ?', 'category')
            ->order('table.metas.order', \Typecho\Db::SORT_ASC)), 'slug')));

        //获取并格式化文章创建时间
        $content['created'] = $edit->created;
        $created = new \Typecho\Date($content['created']);
        $content['year'] = $created->year; $content['month'] = $created->month; $content['day'] = $created->day;

        //生成URL
        $url = $edit->permalink;
         $urls = array(0=>$url);
        if($content['created'] >  Helper::options()->time) exit('<script>location.href="'.$adminUrl.'/manage-posts.php";</script>');
        
        if($bd_api !== ''){
        if(strpos($bd_api, 'data.zz.baidu.com') !== 7) exit('<script>alert("请配置正确的百度收录推送接口调用地址");location.href="'.$adminUrl.'/manage-posts.php";</script>');
        //发送请求
        $ch = curl_init();
        $options =  array(
            CURLOPT_URL => $bd_api,
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS => $url,
            CURLOPT_HTTPHEADER => array('Content-Type: text/plain'),
        );
        curl_setopt_array($ch, $options);
        $result = curl_exec($ch);
        
        $res = json_decode($result, true);
        //if(isset($res['error'])) exit('<script>alert("链接提交百度过程中出现错误，错误代码：'.$res['error'].'，错误信息：'.$res['message'].'。");location.href="'.$adminUrl.'/manage-posts.php";</script>');
        
        }
        
        if($sm_api !== ''){
           //发送请求
        $ch = curl_init();
        $options =  array(
            CURLOPT_URL => $sm_api,
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS => $url,
            CURLOPT_HTTPHEADER => array('Content-Type: text/plain'),
        );
        curl_setopt_array($ch, $options);
        $result = curl_exec($ch);
        
        $res = json_decode($result, true);
        //if($res['returnCode'] !== 200) exit('<script>alert("链接提交神马过程中出现错误，错误代码：'.$res['returnCode'].'，错误信息：'.$res['errorMsg'].'。");location.href="'.$adminUrl.'/manage-posts.php";</script>');  
        }
        
        if($bing_api !== ''){
        $submit_url = array();
        $home_url = Helper::options()->siteUrl;
        $len = strlen($home_url);
        foreach($urls as $url){
            $url = trim($url);
            if(empty($url)){
                continue;
            }
            if(substr($url,0,$len)!=$home_url){
                continue;
            }
            $submit_url[] = $url;
        }
           //发送请求
        $ch = curl_init();
        $options =  array(
            CURLOPT_URL => 'https://ssl.bing.com/webmaster/api.svc/json/SubmitUrlbatch?apikey='.$bing_api,
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS => json_encode(array('siteUrl'=>$home_url,'urlList'=>$submit_url)),
            CURLOPT_HTTPHEADER => array('Content-Type: text/json; charset=utf-8'),
        );
        curl_setopt_array($ch, $options);
        $result = curl_exec($ch);
        
        $res = json_decode($result, true);
       /*
        if(!$res)
            exit('<script>alert("链接提交Bing过程中响应解析出错");location.href="'.$adminUrl.'/manage-posts.php";</script>'); 
        
        if(isset($res['ErrorCode']))
           exit('<script>alert("链接提交Bing过程中出现错误，错误代码：'.$res['ErrorCode'].'，错误信息：'.$res['Message'].'。");location.href="'.$adminUrl.'/manage-posts.php";</script>'); 
        */
        }
        
        if($toutiao_api !== ''){
            $url = urlencode($url);
           //发送请求
        $ch = curl_init();
        $options =  array(
            CURLOPT_URL => 'https://zhanzhang.toutiao.com/s.gif?url='.$url.'&token='.$toutiao_api,
            CURLOPT_POST => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => array('Content-Type: text/json; charset=utf-8'),
        );
        curl_setopt_array($ch, $options);
        $result = curl_exec($ch);
        
        $res = json_decode($result, true);
        //if($res['code'] == 8) exit('<script>alert("链接提交头条过程中出现错误，错误代码：'.$res['code'].'，错误信息：'.$res['msg'].'。");location.href="'.$adminUrl.'/manage-posts.php";</script>');  
        }
        
          if($indexnow_api !== ''){
           //发送请求
        $ch = curl_init();
        $options =  array(
            CURLOPT_URL => 'https://www.bing.com/indexnow?key='.$indexnow_api.'&url='.$url,
            CURLOPT_POST => FALSE,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_HTTPHEADER => array('Content-Type: application/json; charset=utf-8'),
        );
        curl_setopt_array($ch, $options);
        $result = curl_exec($ch);
        $resCode = curl_getinfo($ch,CURLINFO_HTTP_CODE);
         curl_close($ch);
         /*
         if($resCode !== 200){
            $msg  = [
                202=>'URL校验正常，IndexNow密钥已激活，该通知或许仅会显示这一次:).',
                400=>'非法格式',
                403=>'在密钥无效的情况下出现错误（例如，没有找到密钥，找到文件但密钥内容不在文件中）。',
                422=>'如果URL不属于主机，或者密钥与协议中的模式不匹配，就会出现这种情况',
                429=>'请求过多(被限制了)',
            ];
            $err = $msg[$resCode] ? $msg[$resCode] : 'api error,response code['.$resCode.']';
            exit('<script>alert("链接提交IndexNow过程中出现错误，原因：'.$err.'。");location.href="'.$adminUrl.'/manage-posts.php";</script>');
        }
        */
        
        
        }
        
    }
    
      public static function seo_autopush()
    {
        echo PHP_EOL.'<script>
(function(){
  var bp = document.createElement("script");
  var curProtocol = window.location.protocol.split(":")[0];
  if (curProtocol === "https"){
    bp.src = "https://zz.bdstatic.com/linksubmit/push.js";
  }else{
    bp.src = "http://push.zhanzhang.baidu.com/push.js";
  }
    var s = document.getElementsByTagName("script")[0];
    s.parentNode.insertBefore(bp, s);
})();
</script>'.PHP_EOL;
    }
   
    
    public static function tags_bottom(){
        ?><style>.autotags a{cursor: pointer; padding: 0px 6px; margin: 2px 0;display: inline-block;border-radius: 2px;text-decoration: none;}
.autotags a:hover{background: #ccc;color: #fff;}
</style>
<script> $(document).ready(function(){
    $('#tags').after('<div style="margin-top: 20px;" class="autotags"><label for="token-input-tags" class="typecho-label">点击标签自动插入</label><ul style="list-style: none;border: 1px solid #D9D9D6;padding: 6px 12px; max-height: 240px;overflow: auto;background-color: #FFF;border-radius: 2px;"><?php
$i=0;
\Typecho\Widget::widget('Widget_Metas_Tag_Cloud', 'sort=count&desc=1&limit=200')->to($tags);
while ($tags->next()) {
    if (strpos($tags->name, "'") == false) {
echo "<a id=".$i." onclick=\"$(\'#tags\').tokenInput(\'add\', {id: \'".$tags->name."\', tags: \'".$tags->name."\'});\">".$tags->name."</a>";
$i++;
}
}
?></ul></div>');
  });</script>
<?php
    }
    
    /* ponytail: 实现体已拆至 classes/upload.php（BsUpload），此处按需加载转发 */
    public static function uploadHandle(array $file)
    {
        require_once __DIR__ . '/classes/upload.php';
        return BsUpload::uploadHandle(...func_get_args());
    }

    /* ponytail: 实现体已拆至 classes/upload.php（BsUpload），此处按需加载转发 */
    public static function modifyHandle(array $content, array $file)
    {
        require_once __DIR__ . '/classes/upload.php';
        return BsUpload::modifyHandle(...func_get_args());
    }

    /* ponytail: 实现体已拆至 classes/upload.php（BsUpload），此处按需加载转发 */
    public static function deleteHandle(array $content)
    {
        require_once __DIR__ . '/classes/upload.php';
        return BsUpload::deleteHandle(...func_get_args());
    }

    /* ponytail: 实现体已拆至 classes/upload.php（BsUpload），此处按需加载转发 */
    public static function attachmentHandle(array $content)
    {
        require_once __DIR__ . '/classes/upload.php';
        return BsUpload::attachmentHandle(...func_get_args());
    }

    /* ponytail: 实现体已拆至 classes/upload.php（BsUpload），此处按需加载转发 */
    public static function attachmentDataHandle(array $content)
    {
        require_once __DIR__ . '/classes/upload.php';
        return BsUpload::attachmentDataHandle(...func_get_args());
    }

    /* ponytail: 实现体已拆至 classes/upload.php（BsUpload），此处按需加载转发 */
    public static function Widget_Archive_beforeRender()
    {
        require_once __DIR__ . '/classes/upload.php';
        return BsUpload::Widget_Archive_beforeRender(...func_get_args());
    }

    /* ponytail: 实现体已拆至 classes/upload.php（BsUpload），此处按需加载转发 */
    public static function beforeRender($text)
    {
        require_once __DIR__ . '/classes/upload.php';
        return BsUpload::beforeRender(...func_get_args());
    }

    /* ponytail: 实现体已拆至 classes/upload.php（BsUpload），此处按需加载转发 */
    public static function CosInit($options = '')
    {
        require_once __DIR__ . '/classes/upload.php';
        return BsUpload::CosInit(...func_get_args());
    }

    /* ponytail: 实现体已拆至 classes/upload.php（BsUpload），此处按需加载转发 */
    public static function doesBucketExist($opt)
    {
        require_once __DIR__ . '/classes/upload.php';
        return BsUpload::doesBucketExist(...func_get_args());
    }

    /* ponytail: 实现体已拆至 classes/upload.php（BsUpload），此处按需加载转发 */
    public static function doesObjectExist($key)
    {
        require_once __DIR__ . '/classes/upload.php';
        return BsUpload::doesObjectExist(...func_get_args());
    }

    /* ponytail: 实现体已拆至 classes/upload.php（BsUpload），此处按需加载转发 */
    public static function ali_uploadHandle($file)
    {
        require_once __DIR__ . '/classes/upload.php';
        return BsUpload::ali_uploadHandle(...func_get_args());
    }

    /* ponytail: 实现体已拆至 classes/upload.php（BsUpload），此处按需加载转发 */
    public static function ali_modifyHandle($content, $file)
    {
        require_once __DIR__ . '/classes/upload.php';
        return BsUpload::ali_modifyHandle(...func_get_args());
    }

    /* ponytail: 实现体已拆至 classes/upload.php（BsUpload），此处按需加载转发 */
    public static function ali_deleteHandle(array $content)
    {
        require_once __DIR__ . '/classes/upload.php';
        return BsUpload::ali_deleteHandle(...func_get_args());
    }

    /* ponytail: 实现体已拆至 classes/upload.php（BsUpload），此处按需加载转发 */
    public static function ali_attachmentHandle(array $content)
    {
        require_once __DIR__ . '/classes/upload.php';
        return BsUpload::ali_attachmentHandle(...func_get_args());
    }

    /* ponytail: 实现体已拆至 classes/upload.php（BsUpload），此处按需加载转发 */
    public static function ali_attachmentDataHandle(array $content)
    {
        require_once __DIR__ . '/classes/upload.php';
        return BsUpload::ali_attachmentDataHandle(...func_get_args());
    }

    /* ponytail: 实现体已拆至 classes/upload.php（BsUpload），此处按需加载转发 */
    public static function ossInit($options = '')
    {
        require_once __DIR__ . '/classes/upload.php';
        return BsUpload::ossInit(...func_get_args());
    }

    /* ponytail: 实现体已拆至 classes/upload.php（BsUpload），此处按需加载转发 */
    public static function qiniu_uploadHandle($file)
    {
        require_once __DIR__ . '/classes/upload.php';
        return BsUpload::qiniu_uploadHandle(...func_get_args());
    }

    /* ponytail: 实现体已拆至 classes/upload.php（BsUpload），此处按需加载转发 */
    public static function qiniu_modifyHandle($content, $file)
    {
        require_once __DIR__ . '/classes/upload.php';
        return BsUpload::qiniu_modifyHandle(...func_get_args());
    }

    /* ponytail: 实现体已拆至 classes/upload.php（BsUpload），此处按需加载转发 */
    public static function qiniu_deleteHandle(array $content)
    {
        require_once __DIR__ . '/classes/upload.php';
        return BsUpload::qiniu_deleteHandle(...func_get_args());
    }

    /* ponytail: 实现体已拆至 classes/upload.php（BsUpload），此处按需加载转发 */
    public static function qiniu_attachmentHandle(array $content)
    {
        require_once __DIR__ . '/classes/upload.php';
        return BsUpload::qiniu_attachmentHandle(...func_get_args());
    }

    /* ponytail: 实现体已拆至 classes/upload.php（BsUpload），此处按需加载转发 */
    public static function qiniu_attachmentDataHandle($content)
    {
        require_once __DIR__ . '/classes/upload.php';
        return BsUpload::qiniu_attachmentDataHandle(...func_get_args());
    }

    /* ponytail: 实现体已拆至 classes/upload.php（BsUpload），此处按需加载转发 */
    public static function qiniu_getAuth()
    {
        require_once __DIR__ . '/classes/upload.php';
        return BsUpload::qiniu_getAuth(...func_get_args());
    }

    /* ponytail: 实现体已拆至 classes/upload.php（BsUpload），此处按需加载转发 */
    public static function qiniu_getAuthToken($key = null)
    {
        require_once __DIR__ . '/classes/upload.php';
        return BsUpload::qiniu_getAuthToken(...func_get_args());
    }

    /* ponytail: 实现体已拆至 classes/upload.php（BsUpload），此处按需加载转发 */
    public static function upyun_uploadHandle($file)
    {
        require_once __DIR__ . '/classes/upload.php';
        return BsUpload::upyun_uploadHandle(...func_get_args());
    }

    /* ponytail: 实现体已拆至 classes/upload.php（BsUpload），此处按需加载转发 */
    public static function upyun_modifyHandle($content, $file)
    {
        require_once __DIR__ . '/classes/upload.php';
        return BsUpload::upyun_modifyHandle(...func_get_args());
    }

    /* ponytail: 实现体已拆至 classes/upload.php（BsUpload），此处按需加载转发 */
    public static function upyun_deleteHandle(array $content)
    {
        require_once __DIR__ . '/classes/upload.php';
        return BsUpload::upyun_deleteHandle(...func_get_args());
    }

    /* ponytail: 实现体已拆至 classes/upload.php（BsUpload），此处按需加载转发 */
    public static function upyun_attachmentHandle(array $content)
    {
        require_once __DIR__ . '/classes/upload.php';
        return BsUpload::upyun_attachmentHandle(...func_get_args());
    }

    /* ponytail: 实现体已拆至 classes/upload.php（BsUpload），此处按需加载转发 */
    public static function upyun_attachmentDataHandle(array $content)
    {
        require_once __DIR__ . '/classes/upload.php';
        return BsUpload::upyun_attachmentDataHandle(...func_get_args());
    }

    /* ponytail: 实现体已拆至 classes/upload.php（BsUpload），此处按需加载转发 */
    public static function upyunInit()
    {
        require_once __DIR__ . '/classes/upload.php';
        return BsUpload::upyunInit(...func_get_args());
    }

    /* ponytail: 实现体已拆至 classes/upload.php（BsUpload），此处按需加载转发 */
    public static function hw_uploadHandle($file)
    {
        require_once __DIR__ . '/classes/upload.php';
        return BsUpload::hw_uploadHandle(...func_get_args());
    }

    /* ponytail: 实现体已拆至 classes/upload.php（BsUpload），此处按需加载转发 */
    public static function hw_modifyHandle($content, $file)
    {
        require_once __DIR__ . '/classes/upload.php';
        return BsUpload::hw_modifyHandle(...func_get_args());
    }

    /* ponytail: 实现体已拆至 classes/upload.php（BsUpload），此处按需加载转发 */
    public static function hw_deleteHandle(array $content)
    {
        require_once __DIR__ . '/classes/upload.php';
        return BsUpload::hw_deleteHandle(...func_get_args());
    }

    /* ponytail: 实现体已拆至 classes/upload.php（BsUpload），此处按需加载转发 */
    public static function hw_attachmentHandle(array $content)
    {
        require_once __DIR__ . '/classes/upload.php';
        return BsUpload::hw_attachmentHandle(...func_get_args());
    }

    /* ponytail: 实现体已拆至 classes/upload.php（BsUpload），此处按需加载转发 */
    public static function hw_attachmentDataHandle(array $content)
    {
        require_once __DIR__ . '/classes/upload.php';
        return BsUpload::hw_attachmentDataHandle(...func_get_args());
    }

    /* ponytail: 实现体已拆至 classes/upload.php（BsUpload），此处按需加载转发 */
    public static function bd_uploadHandle($file)
    {
        require_once __DIR__ . '/classes/upload.php';
        return BsUpload::bd_uploadHandle(...func_get_args());
    }

    /* ponytail: 实现体已拆至 classes/upload.php（BsUpload），此处按需加载转发 */
    public static function bd_modifyHandle($content, $file)
    {
        require_once __DIR__ . '/classes/upload.php';
        return BsUpload::bd_modifyHandle(...func_get_args());
    }

    /* ponytail: 实现体已拆至 classes/upload.php（BsUpload），此处按需加载转发 */
    public static function bd_deleteHandle(array $content)
    {
        require_once __DIR__ . '/classes/upload.php';
        return BsUpload::bd_deleteHandle(...func_get_args());
    }

    /* ponytail: 实现体已拆至 classes/upload.php（BsUpload），此处按需加载转发 */
    public static function bd_attachmentHandle(array $content)
    {
        require_once __DIR__ . '/classes/upload.php';
        return BsUpload::bd_attachmentHandle(...func_get_args());
    }

    /* ponytail: 实现体已拆至 classes/upload.php（BsUpload），此处按需加载转发 */
    public static function bd_attachmentDataHandle(array $content)
    {
        require_once __DIR__ . '/classes/upload.php';
        return BsUpload::bd_attachmentDataHandle(...func_get_args());
    }

    /* ponytail: 实现体已拆至 classes/upload.php（BsUpload），此处按需加载转发 */
    public static function tinypng_uploadHandle($file)
    {
        require_once __DIR__ . '/classes/upload.php';
        return BsUpload::tinypng_uploadHandle(...func_get_args());
    }

    /* ponytail: 实现体已拆至 classes/upload.php（BsUpload），此处按需加载转发 */
    public static function lsky_uploadHandle($file)
    {
        require_once __DIR__ . '/classes/upload.php';
        return BsUpload::lsky_uploadHandle(...func_get_args());
    }

    /* ponytail: 实现体已拆至 classes/upload.php（BsUpload），此处按需加载转发 */
    public static function lsky_deleteHandle(array $content): bool
    {
        require_once __DIR__ . '/classes/upload.php';
        return BsUpload::lsky_deleteHandle(...func_get_args());
    }

    /* ponytail: 实现体已拆至 classes/upload.php（BsUpload），此处按需加载转发 */
    public static function lsky_modifyHandle($content, $file)
    {
        require_once __DIR__ . '/classes/upload.php';
        return BsUpload::lsky_modifyHandle(...func_get_args());
    }

    /* ponytail: 实现体已拆至 classes/upload.php（BsUpload），此处按需加载转发 */
    public static function lsky_attachmentHandle(array $content): string
    {
        require_once __DIR__ . '/classes/upload.php';
        return BsUpload::lsky_attachmentHandle(...func_get_args());
    }

    /* ponytail: 实现体已拆至 classes/upload.php（BsUpload），此处按需加载转发 */
    public static function lsky_attachmentDataHandle(array $content)
    {
        require_once __DIR__ . '/classes/upload.php';
        return BsUpload::lsky_attachmentDataHandle(...func_get_args());
    }

    /* ponytail: 实现体已拆至 classes/upload.php（BsUpload），此处按需加载转发 */
    public static function getTypechoMethod($methodName, $inParams)
    {
        require_once __DIR__ . '/classes/upload.php';
        return BsUpload::getTypechoMethod(...func_get_args());
    }

    /* ponytail: 实现体已拆至 classes/upload.php（BsUpload），此处按需加载转发 */
    private static function getDomain()
    {
        require_once __DIR__ . '/classes/upload.php';
        return BsUpload::getDomain(...func_get_args());
    }
    public static function tinypng_bottom($post)

    {

        $options = Helper::options();

        $statusPath = \Typecho\Common::url("/action/bs-ajax?do=tinypng", $options->index);



        echo <<<EOT

        <script>

$("#tab-files").append("<div id=\"upload-panel\" class=\"p\">     <div class=\"upload-area tinypngstatus\" draggable=\"true\" title=\"点击更新\" style=\"position: relative;cursor: pointer;\">图片压缩可用数正在查询中...</div> </div>");

function statusfind() {

	$.ajax({

		url: "{$statusPath}",

		async: true,

		dataType: "text",

		global: true,

		success: function(result) {

		    //如果正常返回数字

		    if(!isNaN(result)){

		        $(".tinypngstatus").text("图片压缩可用："+result+"张");

		    }else{

		          var sear=new RegExp('Provide an API key');

		          var unknownerror = true;

　　              if(sear.test(result)){

                      unknownerror = false;

    　　             $(".tinypngstatus").html("<span style=\"color:#df4068;\">TinyPNG APIKEY需要设置</span>");

　　              }

                  //未知错误

                  if(unknownerror){

                    $(".tinypngstatus").html("<span style=\"color:#df4068;\">TinyPNG出现错误</span>");

                  }

		    }	    

		},

		error:function(result) {

			$(".tinypngstatus").html("<span style=\"color:#df4068;\">TinyPNG状态获取失败，网络故障或者配置错误</span>");

		}

	});

}

$("#tab-files-btn").click(statusfind);

function statusfindsession(){

     $(".tinypngstatus").text("图片压缩可用数查询中...");

    statusfind();

}

$(".tinypngstatus").click(statusfindsession);

</script>\n

EOT;

    }
public static function getSecurity($typeName,$name,$value = null){
    if($typeName == 'get'){
        $value = \Typecho\Cookie::get($name);
        return $value;
    }
    elseif($typeName == 'set'){
        \Typecho\Cookie::set($name, $value);
    }
    else{
        \Typecho\Cookie::delete($name, $value);
    }
}


}