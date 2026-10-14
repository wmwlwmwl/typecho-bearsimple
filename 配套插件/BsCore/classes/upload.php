<?php
/* ponytail: 自 Plugin.php 机械拆分的上传簇（本地/COS/阿里/七牛/又拍/华为/百度/TinyPNG/Lsky 四件套 + 共享辅助，共 74 方法）。
   Plugin.php 保留同签名转发器（activate() 钩子注册不变），按需 require。
   差异化改写仅两处：self::UPLOAD_DIR → Plugin::UPLOAD_DIR；getDomain 由 private 提升为 public（beforeRender 需经转发器调用） */
namespace TypechoPlugin\BsCore;

use Utils\Helper;
use Typecho\Plugin\PluginInterface;
use Typecho\Widget\Helper\Form;
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
use CaptchaClient;
use OSS\OssClient;
use OSS\Core\OssException;
use Qiniu\Auth;
use Qiniu\Storage\BucketManager;
use Qiniu\Storage\UploadManager;
use Upyun\Upyun;
use Upyun\Config;
use Obs\ObsClient;
use BaiduBce\Services\Bos\BosClient;
use BaiduBce\Services\Bos\BosOptions;

if (!defined('__TYPECHO_ROOT_DIR__')) exit;

class BsUpload
{
    /**
   * @description: 上传文件处理函数
   * @param {array} $file
   * @return {*}
   */
  public static function uploadHandle(array $file)
  {
   $boptions = get_option('bearsimple');
        $opt = Helper::options();
        
        //文件名是否空
        if (empty($file['name'])) {
            return false;
        }
        //文件名安全性检测
        $ext = self::getSafeName($file['name']);
        if (!self::getTypechoMethod("checkFileType", $ext) || \Typecho\Common::isAppEngine()) {
            return false;
        }
        //判断格式
        $ext = strtolower($ext);
        
        if (empty($date)) {
            $date = new \Typecho\Date($opt->gmtTime);
        }
        $filePath = self::getUploadDir()  . $date->year . '/' . $date->month;

        if (!isset($file['tmp_name'])) {
            return false;
        }
        
        //创建上传目录
        if (!is_dir($filePath)) {

            if (!self::getTypechoMethod("makeUploadDir", $filePath)) {

                return false;
            }
        }

        $fileName = sprintf('%u', crc32(uniqid())) . '.' . $ext;
        $path = $filePath . '/' . $fileName;
        
        if (isset($file['tmp_name'])) {
            $content = file_get_contents($file['tmp_name']);
        } else if (isset($file['bytes'])) {
            $content = $file['bytes'];
        } else {
            return false;
        }
        
        
        //判断是否开启tinypng
        if($boptions['Tinypng_open'] == true){
        
        $isimage = in_array($ext, array("jpg", "png", "jpeg"));

        
        
        if ($isimage) {
            $apikey = trim(bsOptions::getInstance()::get_option( 'bearsimple' )['Tinypng_apikey']);
            \Tinify\setKey($apikey);
            $source = \Tinify\fromFile($file['tmp_name']);
        }
        
        if (!$isimage) {
            if (isset($file['tmp_name'])) {
                if (!@move_uploaded_file($file['tmp_name'], $path)) {
                    return false;
                }
            } else if (isset($file['bytes'])) {
                if (!file_put_contents($path, $file['bytes'])) {
                    return false;
                }
            } else {
                return false;
            }
        }
        if ($isimage) {
            $source->toFile($path);
            $file['size'] = filesize($path);
        } else if (!isset($file['size'])) {
            $file['size'] = filesize($path);
    }         
    $file['tmp_name'] = $path;
    }
    $uploadfile = self::getUploadFile($file);

    /* 上传到COS */
    #初始化COS
    $cosClient = self::CosInit();
    try {
      #判断是否存在重名文件，重名则重新生成
      $times = 10;
      while ($times > 0 && self::doesObjectExist($path)) {
        $fileName = sprintf('%u', crc32(uniqid($times--))) . '.' . $ext;
        $path = $fileDir . '/' . $fileName;
      }

      $cosClient->upload(
        $bucket = $boptions['bucket_name'],
        $key = $path,
        $body = fopen($uploadfile, 'rb'),
        $options = array(
          "ACL" => 'public-read',
          'CacheControl' => 'private'
        )
      );
    } catch (Exception $e) {
      echo "$e\n";
      return false;
    }

    if (!isset($file['size'])) {
      $fileInfo = $cosClient->headObject(array('Bucket' => $boptions['bucket_name'], 'Key' => $path))->toArray();
      $file['size'] = $fileInfo['ContentLength'];
    }
    
    if ($boptions['bucket_localsave'] == true && self::makeUploadDir($filePath)) {
      #本地存储一份
      @move_uploaded_file($uploadfile, $path);
    }

    #返回相对存储路径
    return array(
      'name' => $file['name'],
      'path' => $path,
      'size' => $file['size'],
      'type' => $ext,
      'mime' => @\Typecho\Common::mimeContentType($path)
    );
  }

  /**
   * @description: 修改文件处理函数
   * @param {array} $content 旧文件
   * @param {array} $file 新文件
   * @return {*}
   */
  public static function modifyHandle(array $content, array $file)
  {
    if (empty($file['name'])) {
      return false;
    }
if ($opts['bucket_other'] !== '') {
      $bucket_other = $opts['bucket_other'];
    }
    #获取扩展名
    $ext = self::getSafeName($file['name']);
    #判定是否是允许的文件类型
    if ($content['attachment']->type != $ext || \Typecho\Common::isAppEngine()) {
      return false;
    }
    #获取设置参数
    $opt = get_option('bearsimple');
    #获取文件路径
    $path = $content['attachment']->path;
    #获得上传文件
    $uploadfile = self::getUploadFile($file);
    #如果没有临时文件，则退出
    if (!isset($uploadfile)) {
      return false;
    }

    /* 上传到COS */
    $cosClient = self::CosInit();
    try {
      $cosClient->upload(
        $bucket = $opt['bucket'],
        $key = $path,
        $body = fopen($uploadfile, 'rb'),
        $options = array(
          "ACL" => 'public-read',
          'CacheControl' => 'private'
        )
      );
    } catch (Exception $e) {
      echo "$e\n";
      return false;
    }

    if (!isset($file['size'])) {
      $fileInfo = $cosClient->headObject(array('Bucket' => $opt['bucket_name'], 'Key' => $path))->toArray();
      $file['size'] = $fileInfo['ContentLength'];
    }

    if ($opt['bucket_localsave'] == true) {
      #本地存储一份
      @move_uploaded_file($uploadfile, $path);
    }


    #返回相对存储路径
    return array(
      'name' => $content['attachment']->name,
      'path' => $content['attachment']->path,
      'size' => $file['size'],
      'type' => $content['attachment']->type,
      'mime' => $content['attachment']->mime
    );
  }

  /**
   * @description: 删除文件
   * @param {array} $content
   * @return {*}
   */
  public static function deleteHandle(array $content)
  {
    #获取设置参数
    $opt = get_option('bearsimple');

    #删除本地文件
    if ($opt['bucket_localsave'] == true && $opt['bucket_localdelete']== true) {
      @unlink($content['attachment']->path);
    }
    # 开启同步删除COS文件则执行
    if ($opt['bucket_sync']==true) {
      #初始化COS
      $cosClient = self::CosInit();
      try {
        $result = $cosClient->deleteObject(array(
          #bucket的命名规则为{name}-{appid} ，此处填写的存储桶名称必须为此格式
          'Bucket' => $opt['bucket_name'],
          'Key' => $content['attachment']->path
        ));
      } catch (Exception $e) {
        echo "$e\n";
        return false;
      }
    }
    return true;
  }

  /**
   * @description: 获取对象访问Url
   * @param {array} $content
   * @return {*}
   */
  public static function attachmentHandle(array $content)
  {
      $options = get_option('bearsimple');
         if (preg_match('/\.(jpg|jepg|png|ico|bmp|gif|tiff|webp)/i', $content['attachment']->path)) {
        $diy_style  = $options['bucket_other'];
        }
        return \Typecho\Common::url($content['attachment']->path, self::tx_getDomain()).$diy_style;
        
  }

  /**
   * @description: 获取对象访问Url
   * @param array $content
   * @return {*}
   */
  public static function attachmentDataHandle(array $content)
  {
    return file_get_contents(self::attachmentHandle($content));
  }

  /**
   * @description: 更新正文中的对象网址钩子
   * @return {*}
   */
  public static function Widget_Archive_beforeRender()
  {
    ob_start();
    $html = ob_get_contents();
    ob_clean();
    echo self::beforeRender($html);
    ob_end_flush();
  }

  /**
   * @description: 更新正文中的对象网址（获得新的sign）
   * @param string $text 页面html
   * @return string
   */
  public static function beforeRender($text)
  {
    $opt = get_option('bearsimple');
    $cosClient = self::CosInit();
    if ($opt['bucket_sign'] == 1) {
      return preg_replace_callback(
        '/https?:\/\/[-A-Za-z0-9+&@#\/\%?=~_|!:,.;]+[-A-Za-z0-9+&@#\/\%=~_|]/i',
        function ($matches) use ($opt, $cosClient) {
          $url = $matches[0];
          if (strpos($url, self::getDomain()) !== false) {
            $expTime = explode('q-key-time%3D', $url);
            if (sizeof($expTime) > 1) {
              @$expTime = explode('%26q', $expTime[1])[0];
              @$expTime = explode('%3B', $expTime)[1];
              #未过期，不更新
              if ($expTime > time()) {
                return $url;
              }
            }
            $path = str_replace(self::getDomain(), '', $url);
            $url = $cosClient->getObjectUrl($opt['bucket_name'], explode('?', $path)[0], '+10 minutes');
          }
          return $url;
        },
        $text
      );
    }
    return $text;
  }

  /**
   * @description: COS初始化
   * @param object $options 设置信息
   * @return {*}
   */
  public static function CosInit($options = '')
  {
    try {
      if (!$options) {
        $options = get_option('bearsimple');
      }
    } catch (Exception $e) {
    }
    
      require_once 'phar://' . __DIR__ . '/modules/tencent_cos/phar/cos-sdk-v5-7.phar/vendor/autoload.php';
    
   if(empty($options['bucket_secret_id'])||empty($options['bucket_secret_key'])){
       return new \Qcloud\Cos\Client(array(
      'region' => 'ap-beijing',
      'schema' => 'https', #协议头部，默认为http
      'credentials' => array(
        'secretId' => 'fix',
        'secretKey' => 'fix'
      )
    ));
   }
   else{
    return new \Qcloud\Cos\Client(array(
      'region' => $options['bucket_region'],
      'schema' => 'https', #协议头部，默认为http
      'credentials' => array(
        'secretId' => $options['bucket_secret_id'],
        'secretKey' => $options['bucket_secret_key']
      )
    ));
   }
  }

  /**
   * @description: 判断存储桶是否存在
   * @param {*} $opt
   * @return {*}
   */
  public static function doesBucketExist($opt)
  {
    #初始化COS
    $cosClient = self::CosInit($opt);
    try {
      $result = $cosClient->doesBucketExist(
        $opt['bucket_name']
      );
      if (!$result) {
        return false;
      }
    } catch (Exception $e) {
      return false;
    }
    return true;
  }

  /**
   * @description: 判断对象是否已存在
   * @param {*} $key
   * @return {*}
   */
  public static function doesObjectExist($key)
  {
    #获取设置参数
    $opt = get_option('bearsimple');
    #初始化COS
    $cosClient = self::CosInit();
    try {
      $result = $cosClient->doesObjectExist(
        $opt['bucket_name'],
        $key
      );
      if ($result) {
        return true;
      }
    } catch (Exception $e) {
      return true;
    }
    return false;
  }
  
  
  
  
  //阿里处理方法
  
  /**
     * 上传文件处理函数
     *
     * @access public
     * @param array $file 上传的文件
     * @return mixed
     */
    public static function ali_uploadHandle($file) {
         $options = get_option('bearsimple');
        $opt = Helper::options();
        
        //文件名是否空
        if (empty($file['name'])) {
            return false;
        }
        //文件名安全性检测
        $ext = self::getSafeName($file['name']);
        if (!self::getTypechoMethod("checkFileType", $ext) || \Typecho\Common::isAppEngine()) {
            return false;
        }
        //判断格式
        $ext = strtolower($ext);
        
        if (empty($date)) {
            $date = new \Typecho\Date($opt->gmtTime);
        }
        $filePath = self::ali_getUploadDir()  . $date->year . '/' . $date->month;

        if (!isset($file['tmp_name'])) {
            return false;
        }
        
        //创建上传目录
        if (!is_dir($filePath)) {

            if (!self::getTypechoMethod("makeUploadDir", $filePath)) {

                return false;
            }
        }

        $fileName = sprintf('%u', crc32(uniqid())) . '.' . $ext;
        $path = $filePath . '/' . $fileName;
        
        if (isset($file['tmp_name'])) {
            $content = file_get_contents($file['tmp_name']);
        } else if (isset($file['bytes'])) {
            $content = $file['bytes'];
        } else {
            return false;
        }
        
        
        //判断是否开启tinypng
        if($options['Tinypng_open'] == true){
        
        $isimage = in_array($ext, array("jpg", "png", "jpeg"));

        
        
        if ($isimage) {
            $apikey = trim(bsOptions::getInstance()::get_option( 'bearsimple' )['Tinypng_apikey']);
            \Tinify\setKey($apikey);
            $source = \Tinify\fromFile($file['tmp_name']);
        }
        
        if (!$isimage) {
            if (isset($file['tmp_name'])) {
                if (!@move_uploaded_file($file['tmp_name'], $path)) {
                    return false;
                }
            } else if (isset($file['bytes'])) {
                if (!file_put_contents($path, $file['bytes'])) {
                    return false;
                }
            } else {
                return false;
            }
        }
        if ($isimage) {
            $source->toFile($path);
            $file['size'] = filesize($path);
        } else if (!isset($file['size'])) {
            $file['size'] = filesize($path);
    }         
    $file['tmp_name'] = $path;
    }
    


      
        $uploadfile = self::getUploadFile($file);

        if (!isset($uploadfile)) {
            return false;
        }

        $oss_client = self::ossInit();
        try {
            $oss_client->doesBucketExist($options['ali_bucket_name']);
        } catch (Exception $e) {
            return false;
        }
       try {
            $oss_client->putObject($options['ali_bucket_name'], $path, $content);
            
        } catch (Exception $e) {
            return false;
        }

 if ($options['ali_bucket_localsave'] == true && self::makeUploadDir($filePath)) {
      #本地存储一份
      @move_uploaded_file($uploadfile, $path);
    }
    else{
        @unlink($path);
    }
    
             return array(
            'name' => $file['name'],
            'path' => $path,
            'size' => $file['size'],
            'type' => $ext,
            'mime' => @\Typecho\Common::mimeContentType($path)
        );
            
        
    }

    /**
     * 修改文件处理函数
     *
     * @access public
     * @param array $content 老文件
     * @param array $file 新上传的文件
     * @return mixed
     */
    public static function ali_modifyHandle($content, $file)
    {
        
         $options = get_option('bearsimple');
       if (isset($file['tmp_name'])) {
            $contents = file_get_contents($file['tmp_name']);
        } else if (isset($file['bytes'])) {
            $contents = $file['bytes'];
        } else {
            return false;
        }
        if (empty($file['name'])) {
            return false;
        }

        $ext = self::getSafeName($file['name']);

       if (!\Widget\Upload::checkFileType($ext) || \Typecho\Common::isAppEngine()) {
            return false;
        }

       $uploadfile = self::getUploadFile($file);

        if (!isset($uploadfile)) {
            return false;
        }

        $oss_client = self::ossInit();
         
         try{
            $ali_response = $oss_client->putObject($options['ali_bucket_name'], $content['attachment']->path, $contents);
        } catch(OssException $e) {
             
            return false;
        }
        
      if ($options['ali_bucket_localsave'] == true) {
      #本地存储一份
      @move_uploaded_file($uploadfile, $content['attachment']->path);
    }
    

        return array(
            'name' => $content['attachment']->name,
            'path' => $content['attachment']->path,
            'size' => intval($ali_response['oss-requestheaders']['Content-Length']),
            'type' => $ext,
            'mime' => $ali_response['oss-requestheaders']['Content-Type']
        );
    }

    /**
     * 删除文件
     *
     * @access public
     * @param array $content 文件相关信息
     * @return string
     */
    public static function ali_deleteHandle(array $content)
    {
         $options = get_option('bearsimple');
        $oss_client = self::ossInit();
#删除本地文件
    if ($options['ali_bucket_localsave'] == true && $options['ali_bucket_localdelete']== true) {
      @unlink($content['attachment']->path);
    }
    if ($options['ali_bucket_sync']==true) {
        try {
            $oss_client->deleteObject($options['bd_bucket_name'],$content['attachment']->path);
            return true;
        } catch (Exception $e) {
            return false;
        } 
    }
    return true;
    }

    /**
     * 获取实际文件绝对访问路径
     *
     * @access public
     * @param array $content 文件相关信息
     * @return string
     */
    public static function ali_attachmentHandle(array $content) {
         $options = get_option('bearsimple');
         if (preg_match('/\.(jpg|jepg|png|ico|bmp|gif|tiff|webp)/i', $content['attachment']->path)) {
        $diy_style  = $options['ali_bucket_other'];
        }
        return \Typecho\Common::url($content['attachment']->path, self::ali_getDomain()).$diy_style;
    
       
    }

    /**
     * 获取实际文件数据
     *
     * @access public
     * @param array $content
     * @return string
     */
    public static function ali_attachmentDataHandle(array $content)
    {
        return file_get_contents(self::ali_attachmentHandle($content));
    }
    
    /**
   * @description: OSS初始化
   * @param object $options 设置信息
   * @return {*}
   */
  public static function ossInit($options = '')
  {
      
    try {
      if (!$options) {
        $options = get_option('bearsimple');
      }
    } catch (Exception $e) {
    }
    $end_point   = 'http://' . (($options['ali_bucket_region'] === "other") ?
                $options['ali_bucket_other_region'] :
                $options['ali_bucket_region'] . $options['ali_bucket_connect_type']);
   if(empty($options['ali_bucket_accesskey_id'])||empty($options['ali_bucket_accesskey_secret'])){
       return new OssClient('fix', 'fix', $end_point);
   }
   else{
    return new OssClient($options['ali_bucket_accesskey_id'], $options['ali_bucket_accesskey_secret'], $end_point);
   }
  }
  
  
  //七牛云Kodo
  
  public static function qiniu_uploadHandle($file)
    {
        $options = get_option('bearsimple');
        $opt = Helper::options();
        
        //文件名是否空
        if (empty($file['name'])) {
            return false;
        }
        //文件名安全性检测
        $ext = self::getSafeName($file['name']);
        if (!self::getTypechoMethod("checkFileType", $ext) || \Typecho\Common::isAppEngine()) {
            return false;
        }
        //判断格式
        $ext = strtolower($ext);
        
        if (empty($date)) {
            $date = new \Typecho\Date($opt->gmtTime);
        }
        $filePath = self::qiniu_getUploadDir()  . $date->year . '/' . $date->month;

        if (!isset($file['tmp_name'])) {
            return false;
        }
        
        //创建上传目录
        if (!is_dir($filePath)) {

            if (!self::getTypechoMethod("makeUploadDir", $filePath)) {

                return false;
            }
        }

        $fileName = sprintf('%u', crc32(uniqid())) . '.' . $ext;
        $path = $filePath . '/' . $fileName;
        
        if (isset($file['tmp_name'])) {
            $content = file_get_contents($file['tmp_name']);
        } else if (isset($file['bytes'])) {
            $content = $file['bytes'];
        } else {
            return false;
        }
        
        
        //判断是否开启tinypng
        if($options['Tinypng_open'] == true){
        
        $isimage = in_array($ext, array("jpg", "png", "jpeg"));

        
        
        if ($isimage) {
            $apikey = trim(bsOptions::getInstance()::get_option( 'bearsimple' )['Tinypng_apikey']);
            \Tinify\setKey($apikey);
            $source = \Tinify\fromFile($file['tmp_name']);
        }
        
        if (!$isimage) {
            if (isset($file['tmp_name'])) {
                if (!@move_uploaded_file($file['tmp_name'], $path)) {
                    return false;
                }
            } else if (isset($file['bytes'])) {
                if (!file_put_contents($path, $file['bytes'])) {
                    return false;
                }
            } else {
                return false;
            }
        }
        if ($isimage) {
            $source->toFile($path);
            $file['size'] = filesize($path);
        } else if (!isset($file['size'])) {
            $file['size'] = filesize($path);
    }         
    $file['tmp_name'] = $path;
    }
        $uploadfile = self::getUploadFile($file);

        $uploadMgr = new UploadManager();
        list($ret, $err) = $uploadMgr->putFile(self::qiniu_getAuthToken(), ltrim($path, "/"), $uploadfile);
        if ($err !== null) {
            return false;
        }
        if (!isset($file['size'])) {
            $file['size'] = filesize($uploadfile);
        }
        
    if ($options['qiniu_bucket_localsave'] == true && self::makeUploadDir($filePath)) {
      #本地存储一份
      @move_uploaded_file($uploadfile, $path);
    }
    else{
        @unlink($path);
    }
        return array(
            'name' => $file['name'],
            'path' => $path,
            'size' => $file['size'],
            'type' => $ext,
            'mime' => @\Typecho\Common::mimeContentType($path)
        );
    }

    /**
     * 七牛修改文件
     *
     * @access public
     * @param array $content 老文件
     * @param array $file 新上传的文件
     * @return mixed
     */
    public static function qiniu_modifyHandle($content, $file)
    {
        if (empty($file['name'])) {
            return false;
        }

        //获取扩展名
        $ext = self::getSafeName($file['name']);
        //判定是否是允许的文件类型
        if ($content['attachment']->type != $ext || \Typecho\Common::isAppEngine()) {
            return false;
        }
        //获取文件路径
        $path = $content['attachment']->path;
        //获得上传文件
        $uploadfile = self::getUploadFile($file);
        //如果没有临时文件，则退出
        if (!isset($uploadfile)) {
            return false;
        }

        $uploadMgr = new UploadManager();
        $kodo_path = ltrim($path, "/");
        list($ret, $err) = $uploadMgr->putFile(self::qiniu_getAuthToken($kodo_path), $kodo_path, $uploadfile, null,
            'application/octet-stream',
            false,
            $resumeFile,
            $version,
            $partSize);
        if ($err !== null) {
            return false;
        }

        if (!isset($file['size'])) {
            $file['size'] = filesize($uploadfile);
        }

if ($options['qiniu_bucket_localsave'] == true) {
      #本地存储一份
      @move_uploaded_file($uploadfile, $content['attachment']->path);
    }
    
        //返回相对存储路径
        return array(
            'name' => $content['attachment']->name,
            'path' => $content['attachment']->path,
            'size' => $file['size'],
            'type' => $content['attachment']->type,
            'mime' => $content['attachment']->mime
        );
    }

    /**
     * 七牛删除文件
     *
     * @access public
     * @param array $content 文件相关信息
     * @return string
     */
    public static function qiniu_deleteHandle(array $content)
    {
        $options = get_option('bearsimple');
        $bucket = $options['qiniu_bucket_name'];
        #删除本地文件
    if ($options['qiniu_bucket_localsave'] == true && $options['qiniu_bucket_localdelete']== true) {
      @unlink($content['attachment']->path);
    }
    if ($options['qiniu_bucket_sync']==true) {
        $bucketManager = new BucketManager(self::qiniu_getAuthToken());
        $err = $bucketManager->delete($bucket, ltrim($content['attachment']->path, "/"));
        if ($err != null) {
            return false;
        }
    }
        return true;
    }

     /**
     * 七牛获取域名
     *
     * @access public
     * @param array $content 文件相关信息
     * @return string
     */
    public static function qiniu_attachmentHandle(array $content)
    {
        $options = get_option('bearsimple');
        if (preg_match('/\.(jpg|jepg|png|ico|bmp|gif|tiff|webp)/i', $content['attachment']->path)) {
        $diy_style  = $options['qiniu_bucket_other'];
        }
        return \Typecho\Common::url($content['attachment']->path, $options['qiniu_bucket_domain']).$diy_style;
    }

    /**
     * 七牛获取实际文件数据
     *
     * @access public
     * @param array $content
     * @return string
     */
    public static function qiniu_attachmentDataHandle($content)
    {
        $options = get_option('bearsimple');
        $bucket = $options['qiniu_bucket_name'];
        $bucketManager = new BucketManager(self::qiniu_getAuthToken());
        list($fileInfo, $err) = $bucketManager->stat($bucket, ltrim($content['attachment']->path, "/"));
        if ($err != null) {
            var_dump($err);
            return false;
        }
        return $fileInfo;
    }
    
    public static function qiniu_getAuth()
    {
        $options = get_option('bearsimple');
        $accessKey = trim($options['qiniu_bucket_accesskey']);
        $secretKey = trim($options['qiniu_bucket_secretKey']);
        return new Auth($accessKey, $secretKey);
    }
    public static function qiniu_getAuthToken($key = null)
    {
        $options = get_option('bearsimple');
return self::qiniu_getAuth()->uploadToken($options['qiniu_bucket_name'], $key);
}
    
  
  //又拍云
  
  
  public static function upyun_uploadHandle($file)
    {
          $options = get_option('bearsimple');
        $opt = Helper::options();
        
        //文件名是否空
        if (empty($file['name'])) {
            return false;
        }
        //文件名安全性检测
        $ext = self::getSafeName($file['name']);
        if (!self::getTypechoMethod("checkFileType", $ext) || \Typecho\Common::isAppEngine()) {
            return false;
        }
        //判断格式
        $ext = strtolower($ext);
        
        if (empty($date)) {
            $date = new \Typecho\Date($opt->gmtTime);
        }
        $filePath = self::upyun_getUploadDir()  . $date->year . '/' . $date->month;

        if (!isset($file['tmp_name'])) {
            return false;
        }
        
        //创建上传目录
        if (!is_dir($filePath)) {

            if (!self::getTypechoMethod("makeUploadDir", $filePath)) {

                return false;
            }
        }

        $fileName = sprintf('%u', crc32(uniqid())) . '.' . $ext;
        $path = $filePath . '/' . $fileName;
        
        if (isset($file['tmp_name'])) {
            $content = file_get_contents($file['tmp_name']);
        } else if (isset($file['bytes'])) {
            $content = $file['bytes'];
        } else {
            return false;
        }
        
        
        //判断是否开启tinypng
        if($options['Tinypng_open'] == true){
        
        $isimage = in_array($ext, array("jpg", "png", "jpeg"));

        
        
        if ($isimage) {
            $apikey = trim(bsOptions::getInstance()::get_option( 'bearsimple' )['Tinypng_apikey']);
            \Tinify\setKey($apikey);
            $source = \Tinify\fromFile($file['tmp_name']);
        }
        
        if (!$isimage) {
            if (isset($file['tmp_name'])) {
                if (!@move_uploaded_file($file['tmp_name'], $path)) {
                    return false;
                }
            } else if (isset($file['bytes'])) {
                if (!file_put_contents($path, $file['bytes'])) {
                    return false;
                }
            } else {
                return false;
            }
        }
        if ($isimage) {
            $source->toFile($path);
            $file['size'] = filesize($path);
        } else if (!isset($file['size'])) {
            $file['size'] = filesize($path);
    }         
    $file['tmp_name'] = $path;
    }

        $uploadfile = self::getUploadFile($file);

        if (!isset($uploadfile)) {
            return false;
        } else {
            //上传文件
            $upyun = self::upyunInit();
            $fh = fopen($uploadfile, 'rb');
            $upyun->write($path, $fh, TRUE);
            if(is_resource($fh)) {
                fclose($fh);
            }
        }

        if (!isset($file['size'])) {
            $fileInfo = $upyun->getFileInfo($path);
            $file['size'] = $fileInfo['x-upyun-file-size'];
        }
if ($options['upyun_bucket_localsave'] == true && self::makeUploadDir($filePath)) {
      #本地存储一份
      @move_uploaded_file($uploadfile, $path);
    }
    else{
       @unlink($path);
    }
        //返回相对存储路径
        return array(
            'name' => $file['name'],
            'path' => $path,
            'size' => $file['size'],
            'type' => $ext,
            'mime' => @\Typecho\Common::mimeContentType($path)
        );
    }

    /**
     * 修改文件处理函数
     *
     * @access public
     * @param array $content 老文件
     * @param array $file 新上传的文件
     * @return mixed
     */
    public static function upyun_modifyHandle($content, $file)
    {
        $options = get_option('bearsimple');
        if (empty($file['name'])) {
            return false;
        }

        //获取扩展名
        $ext = self::getSafeName($file['name']);

        if ($content['attachment']->type != $ext || \Typecho\Common::isAppEngine()) {
            return false;
        }

        //获取文件路径
        $path = $content['attachment']->path;

        $uploadfile = self::getUploadFile($file);

        if (!isset($uploadfile)) {
            return false;
        } else {
            //修改文件
            $upyun = self::upyunInit();
            $fh = fopen($uploadfile, 'rb');
            $upyun->writeFile($path, $fh, TRUE);
            fclose($fh);
        }

        if (!isset($file['size'])) {
            $fileInfo = $upyun->getFileInfo($path);
            $file['size'] = $fileInfo['x-upyun-file-size'];
        }
        
        if ($options['upyun_bucket_localsave'] == true) {
      #本地存储一份
      @move_uploaded_file($uploadfile, $content['attachment']->path);
    }
    
        //返回相对存储路径
        return array(
            'name' => $content['attachment']->name,
            'path' => $content['attachment']->path,
            'size' => $file['size'],
            'type' => $content['attachment']->type,
            'mime' => $content['attachment']->mime
        );
    }

    /**
     * 删除文件
     *
     * @access public
     * @param array $content 文件相关信息
     * @return string
     */
    public static function upyun_deleteHandle(array $content)
    {
        $options = get_option('bearsimple');
        $upyun = self::upyunInit();
        $path = $content['attachment']->path;
        
        #删除本地文件
    if ($options['upyun_bucket_localsave'] == true && $options['upyun_bucket_localdelete']== true) {
      @unlink($content['attachment']->path);
    }
    if ($options['upyun_bucket_sync']==true) {
        $upyun->delete($path);
    }
    return true;
        
        
    }

    /**
     * 获取实际文件绝对访问路径
     *
     * @access public
     * @param array $content 文件相关信息
     * @return string
     */
    public static function upyun_attachmentHandle(array $content)
    {
        $options = get_option('bearsimple');
        if (preg_match('/\.(jpg|jepg|png|ico|bmp|gif|tiff|webp)/i', $content['attachment']->path)) {
        $diy_style  = $options['upyun_bucket_other'];
        }
        return \Typecho\Common::url($content['attachment']->path, $options['upyun_bucket_domain']).$diy_style;
    }

    /**
     * 获取实际文件数据
     *
     * @access public
     * @param array $content
     * @return string
     */
    public static function upyun_attachmentDataHandle(array $content)
    {
        $upyun = self::upyunInit();
        return $upyun->getFileInfo($content['attachment']->path);
    }


   
    public static function upyunInit()
    {
        $options = get_option('bearsimple');
        return new Upyun(new Config($options['upyun_bucket_name'], $options['upyun_bucket_actionUser'], $options['upyun_bucket_actionKey']));
    }
    
    
    //华为云OBS
    public static function hw_uploadHandle($file)
    {
        $options = get_option('bearsimple');
        $opt = Helper::options();
        if (empty($file['name'])) {
            return false;
        }
        //文件名安全性检测
        $ext = self::getSafeName($file['name']);
        if (!self::getTypechoMethod("checkFileType", $ext) || \Typecho\Common::isAppEngine()) {
            return false;
        }
        //判断格式
        $ext = strtolower($ext);
        
        if (empty($date)) {
            $date = new \Typecho\Date($opt->gmtTime);
        }
        $filePath = self::hw_getUploadDir()  . $date->year . '/' . $date->month;

        if (!isset($file['tmp_name'])) {
            return false;
        }
        
        //创建上传目录
        if (!is_dir($filePath)) {

            if (!self::getTypechoMethod("makeUploadDir", $filePath)) {

                return false;
            }
        }

        $fileName = sprintf('%u', crc32(uniqid())) . '.' . $ext;
        $path = $filePath . '/' . $fileName;
        
        if (isset($file['tmp_name'])) {
            $content = file_get_contents($file['tmp_name']);
        } else if (isset($file['bytes'])) {
            $content = $file['bytes'];
        } else {
            return false;
        }
        
        
        //判断是否开启tinypng
        if($options['Tinypng_open'] == true){
        
        $isimage = in_array($ext, array("jpg", "png", "jpeg"));

        
        
        if ($isimage) {
            $apikey = trim(bsOptions::getInstance()::get_option( 'bearsimple' )['Tinypng_apikey']);
            \Tinify\setKey($apikey);
            $source = \Tinify\fromFile($file['tmp_name']);
        }
        
        if (!$isimage) {
            if (isset($file['tmp_name'])) {
                if (!@move_uploaded_file($file['tmp_name'], $path)) {
                    return false;
                }
            } else if (isset($file['bytes'])) {
                if (!file_put_contents($path, $file['bytes'])) {
                    return false;
                }
            } else {
                return false;
            }
        }
        if ($isimage) {
            $source->toFile($path);
            $file['size'] = filesize($path);
        } else if (!isset($file['size'])) {
            $file['size'] = filesize($path);
    }         
    $file['tmp_name'] = $path;
    }
    
       

        $uploadfile = self::getUploadFile($file);

        if (!isset($uploadfile)) {
            return false;
        }

        $obsClient = self::obsInit();

        try {
            $obsClient->putObject([
                'Bucket' => $options['hw_bucket_name'],
                'Key' => $path,
                'SourceFile' => $uploadfile
            ]);
        } catch (Exception $e) {
            return false;
        } finally {
            $obsClient->close();
        }

if ($options['hw_bucket_localsave'] == true && self::makeUploadDir($filePath)) {
      #本地存储一份
      @move_uploaded_file($uploadfile, $path);
    }
    else{
        @unlink($path);
    }
    
        //返回相对存储路径
        return array(
            'name' => $file['name'],
            'path' => $path,
            'size' => $file['size'],
            'type' => $ext,
            'mime' => @\Typecho\Common::mimeContentType($path)
        );
    }


    public static function hw_modifyHandle($content, $file)
    {
        $options = get_option('bearsimple');
        if (empty($file['name'])) {
            return false;
        }

        $ext = self::getSafeName($file['name']);

       if (!\Widget\Upload::checkFileType($ext) || \Typecho\Common::isAppEngine()) {
            return false;
        }

       $uploadfile = self::getUploadFile($file);

        if (!isset($uploadfile)) {
            return false;
        }

        $obsClient = self::obsInit();

        try {
            $obsClient->putObject([
                'Bucket' => $options['hw_bucket_name'],
                'Key' => $content['attachment']->path,
                'SourceFile' => $uploadfile
            ]);
        } catch (Exception $e) {
            return false;
        } finally {
            $obsClient->close();
        }
if ($options['hw_bucket_localsave'] == true) {
      #本地存储一份
      @move_uploaded_file($uploadfile, $content['attachment']->path);
    }
 
        return array(
            'name' => $content['attachment']->name,
            'path' => $content['attachment']->path,
            'size' => $file['size'],
            'type' => $content['attachment']->type,
            'mime' => $content['attachment']->mime
        );
    }


    public static function hw_deleteHandle(array $content)
    {
        $options = get_option('bearsimple');
        $obsClient = self::obsInit();
if ($options['hw_bucket_localsave'] == true && $options['hw_bucket_localdelete']== true) {
      @unlink($content['attachment']->path);
    }
    if ($options['hw_bucket_sync']==true) {
        try {
            $obsClient->deleteObject([
                'Bucket' => $options['hw_bucket_name'],
                'Key' => $content['attachment']->path
            ]);
            return true;
        } catch (Exception $e) {
            return false;
        } finally {
            $obsClient->close();
        }
    }
    }

   
    public static function hw_attachmentHandle(array $content)
    {
        $options = get_option('bearsimple');
         if (preg_match('/\.(jpg|jepg|png|ico|bmp|gif|tiff|webp)/i', $content['attachment']->path)) {
        $diy_style  = $options['hw_bucket_other'];
        }
        return \Typecho\Common::url($content['attachment']->path, self::hw_getDomain()).$diy_style;
    }


    public static function hw_attachmentDataHandle(array $content)
    {
        $options = get_option('bearsimple');
        return file_get_contents(\Typecho\Common::url($content['attachment']->path, self::hw_getDomain()));
    }

    
    private static function obsInit()
    {
        $options = get_option('bearsimple');
        return new ObsClient([
            'key' => $options['hw_bucket_accesskey_id'],
            'secret' => $options['hw_bucket_accesskey_secret'],
            'endpoint' => $options['hw_bucket_region'].'.myhuaweicloud.com',
            'socket_timeout' => 30,
            'connect_timeout' => 10
        ]);
    }
  
  
  
  public static function bd_uploadHandle($file) {
      $options = get_option('bearsimple');
        $opt = Helper::options();
        
        //文件名是否空
        if (empty($file['name'])) {
            return false;
        }
        //文件名安全性检测
        $ext = self::getSafeName($file['name']);
        if (!self::getTypechoMethod("checkFileType", $ext) || \Typecho\Common::isAppEngine()) {
            return false;
        }
        //判断格式
        $ext = strtolower($ext);
        
        if (empty($date)) {
            $date = new \Typecho\Date($opt->gmtTime);
        }
        $filePath = self::bd_getUploadDir()  . $date->year . '/' . $date->month;

        if (!isset($file['tmp_name'])) {
            return false;
        }
        
        //创建上传目录
        if (!is_dir($filePath)) {

            if (!self::getTypechoMethod("makeUploadDir", $filePath)) {

                return false;
            }
        }

        $fileName = sprintf('%u', crc32(uniqid())) . '.' . $ext;
        $path = $filePath . '/' . $fileName;
        
        if (isset($file['tmp_name'])) {
            $content = file_get_contents($file['tmp_name']);
        } else if (isset($file['bytes'])) {
            $content = $file['bytes'];
        } else {
            return false;
        }
        
        
        //判断是否开启tinypng
        if($options['Tinypng_open'] == true){
        
        $isimage = in_array($ext, array("jpg", "png", "jpeg"));

        
        
        if ($isimage) {
            $apikey = trim(bsOptions::getInstance()::get_option( 'bearsimple' )['Tinypng_apikey']);
            \Tinify\setKey($apikey);
            $source = \Tinify\fromFile($file['tmp_name']);
        }
        
        if (!$isimage) {
            if (isset($file['tmp_name'])) {
                if (!@move_uploaded_file($file['tmp_name'], $path)) {
                    return false;
                }
            } else if (isset($file['bytes'])) {
                if (!file_put_contents($path, $file['bytes'])) {
                    return false;
                }
            } else {
                return false;
            }
        }
        if ($isimage) {
            $source->toFile($path);
            $file['size'] = filesize($path);
        } else if (!isset($file['size'])) {
            $file['size'] = filesize($path);
    }         
    $file['tmp_name'] = $path;
    }
    


      
        $uploadfile = self::getUploadFile($file);

        if (!isset($uploadfile)) {
            return false;
        }

        $bosClient = self::bosInit();
        try {
            $bosClient->doesBucketExist($options['bd_bucket_name']);
        } catch (Exception $e) {
            return false;
        }
       try {
            $bosClient->putObjectFromString($options['bd_bucket_name'], $path, $content, array(BosOptions::CONTENT_TYPE => $file['type']));
            
        } catch (Exception $e) {
            return false;
        }

 if ($options['bd_bucket_localsave'] == true && self::makeUploadDir($filePath)) {
      #本地存储一份
      @move_uploaded_file($uploadfile, $path);
    }
    else{
        @unlink($path);
    }
    
    
    
        //返回相对存储路径
        return array(
            'name' => $file['name'],
            'path' => $path,
            'size' => $file['size'],
            'type' => $ext,
            'mime' => @\Typecho\Common::mimeContentType($path)
        );
    }


    public static function bd_modifyHandle($content, $file)
    {
       $options = get_option('bearsimple');
       if (isset($file['tmp_name'])) {
            $contents = file_get_contents($file['tmp_name']);
        } else if (isset($file['bytes'])) {
            $contents = $file['bytes'];
        } else {
            return false;
        }
        if (empty($file['name'])) {
            return false;
        }

        $ext = self::getSafeName($file['name']);

       if (!\Widget\Upload::checkFileType($ext) || \Typecho\Common::isAppEngine()) {
            return false;
        }

       $uploadfile = self::getUploadFile($file);

        if (!isset($uploadfile)) {
            return false;
        }

        $bosClient = self::bosInit();

        try {
            $bosClient->putObjectFromString($options['bd_bucket_name'], $content['attachment']->path, $contents, array(BosOptions::CONTENT_TYPE => $file['type']));
        } catch (Exception $e) {
            return false;
        } 
      if ($options['bd_bucket_localsave'] == true) {
      #本地存储一份
      @move_uploaded_file($uploadfile, $content['attachment']->path);
    }
    
 
        return array(
            'name' => $content['attachment']->name,
            'path' => $content['attachment']->path,
            'size' => $file['size'],
            'type' => $content['attachment']->type,
            'mime' => $content['attachment']->mime
        );
    }


    public static function bd_deleteHandle(array $content)
    {
       $options = get_option('bearsimple');
        $bosClient = self::bosInit();
#删除本地文件
    if ($options['bd_bucket_localsave'] == true && $options['bd_bucket_localdelete']== true) {
      @unlink($content['attachment']->path);
    }
    if ($options['bd_bucket_sync']==true) {
        try {
            $bosClient->deleteObject($options['bd_bucket_name'],$content['attachment']->path);
            return true;
        } catch (Exception $e) {
            return false;
        } 
    }
    return true;
    }


    public static function bd_attachmentHandle(array $content) {
       $options = get_option('bearsimple');
         if (preg_match('/\.(jpg|jepg|png|ico|bmp|gif|tiff|webp)/i', $content['attachment']->path)) {
        $diy_style  = $options['bd_bucket_other'];
        }
        return \Typecho\Common::url($content['attachment']->path, self::bd_getDomain()).$diy_style;
    }


    public static function bd_attachmentDataHandle(array $content)
    {
        return file_get_contents(self::bd_attachmentHandle($content));
    }
    
    
  private static function bosInit()
    {
        $options = get_option('bearsimple');
        return new BosClient(array(
                'credentials' => array(
                    'accessKeyId' => $options['bd_bucket_accesskey'],
                    'secretAccessKey' => $options['bd_bucket_secretkey'],
                ),
                'endpoint' => 'http://' .$options['bd_bucket_region'],
            ));
    }
  
  private static function tx_getDomain()
  {
    $options = get_option('bearsimple');
        $domain = $options['bucket_domain'];
        if (empty($domain)) $domain = 'https://' .$options['bucket_name'] . '.cos.' . $options['bucket_region'].'.myqcloud.com';

        return $domain;
  }  
  
  private static function ali_getDomain()
  {
    $options = get_option('bearsimple');
        $domain = $options['ali_bucket_domain'];
        $bucket_name = $options['ali_bucket_name'];
            $end_point   = ($options['ali_bucket_region'] === "other") ? 
                            $options['ali_bucket_other_region'] : 
                            $options['ali_bucket_region'];
        if (empty($domain)) $domain = 'https://' .$bucket_name . '.' . $end_point.'.aliyuncs.com';

        return $domain;
  }  
  
  private static function bd_getDomain()
  {
    $options = get_option('bearsimple');
        $domain = $options['bd_bucket_domain'];
        if (empty($domain)) $domain = 'https://' .$options['bd_bucket_name'] . '.' . $options['bd_bucket_region'];

        return $domain;
  }  
  
  private static function hw_getDomain()
  {
    $options = get_option('bearsimple');
        $domain = $options['hw_bucket_domain'];
        if (empty($domain)) $domain = 'https://' .$options['hw_bucket_name'] . '.' . $options['hw_bucket_region']. '.myhuaweicloud.com';

        return $domain;
  }  

  public static function getDomain()
  {
    $opt = get_option('bearsimple');
    $domain = $opt['bucket_domain'];
    if (empty($domain))  $domain = 'https://' . $opt['bucket_name'] . '.cos.' . $opt['bucket_region'] . '.myqcloud.com';
    return $domain;
  }
  
  
  
 
  
private static function checkFileType($ext)
    {
        $options = \Typecho\Widget::widget('Widget_Options');
        return in_array($ext, $options->allowedAttachmentTypes);
    }
 
 
  private static function makeUploadDir(string $path)
  {
    $path = preg_replace("/\\\+/", '/', $path);
    $current = rtrim($path, '/');
    $last = $current;

    while (!is_dir($current) && false !== strpos($path, '/')) {
      $last = $current;
      $current = dirname($current);
    }

    if ($last == $current) {
      return true;
    }

    if (!@mkdir($last, 0755)) {
      return false;
    }

    return self::makeUploadDir($path);
  }
  
  private static function makeUploadaDir($path)
    {
        $path = preg_replace("/\\\+/", '/', $path);
        $current = rtrim($path, '/');
        $last = $current;

        while (!is_dir($current) && false !== strpos($path, '/')) {
            $last = $current;
            $current = dirname($current);
        }

        if ($last == $current) {
            return true;
        }

        if (!@mkdir($last)) {
            return false;
        }

        $stat = @stat($last);
        $perms = $stat['mode'] & 0007777;
        @chmod($last, $perms);

        return self::makeUploadaDir($path);
    }

  private static function getUploadDir()
  {
    $opt = get_option('bearsimple');
    if ($opt['bucket_path']) {
      return $opt['bucket_path'];
    } else if (defined('__TYPECHO_UPLOAD_DIR__')) {
      return __TYPECHO_UPLOAD_DIR__;
    } else {
      return Plugin::UPLOAD_DIR;
    }
  }
  
  private static function ali_getUploadDir()
  {
    $opt = get_option('bearsimple');
    if ($opt['ali_bucket_path']) {
      return $opt['ali_bucket_path'];
    } else if (defined('__TYPECHO_UPLOAD_DIR__')) {
      return __TYPECHO_UPLOAD_DIR__;
    } else {
      return Plugin::UPLOAD_DIR;
    }
  }
  
  private static function qiniu_getUploadDir()
  {
    $opt = get_option('bearsimple');
    if ($opt['qiniu_bucket_path']) {
      return $opt['qiniu_bucket_path'];
    } else if (defined('__TYPECHO_UPLOAD_DIR__')) {
      return __TYPECHO_UPLOAD_DIR__;
    } else {
      return Plugin::UPLOAD_DIR;
    }
  }
  
  private static function upyun_getUploadDir()
  {
    $opt = get_option('bearsimple');
    if ($opt['upyun_bucket_path']) {
      return $opt['upyun_bucket_path'];
    } else if (defined('__TYPECHO_UPLOAD_DIR__')) {
      return __TYPECHO_UPLOAD_DIR__;
    } else {
      return Plugin::UPLOAD_DIR;
    }
  }
  
  private static function hw_getUploadDir()
  {
    $opt = get_option('bearsimple');
    if ($opt['hw_bucket_path']) {
      return $opt['hw_bucket_path'];
    } else if (defined('__TYPECHO_UPLOAD_DIR__')) {
      return __TYPECHO_UPLOAD_DIR__;
    } else {
      return Plugin::UPLOAD_DIR;
    }
  }
  
  private static function bd_getUploadDir()
  {
    $opt = get_option('bearsimple');
    if ($opt['bd_bucket_path']) {
      return $opt['bd_bucket_path'];
    } else if (defined('__TYPECHO_UPLOAD_DIR__')) {
      return __TYPECHO_UPLOAD_DIR__;
    } else {
      return Plugin::UPLOAD_DIR;
    }
  }
  
  private static function getUploadFile($file)
  {
    return isset($file['tmp_name']) ? $file['tmp_name'] : (isset($file['bytes']) ? $file['bytes'] : (isset($file['bits']) ? $file['bits'] : ''));
  }

  
  private static function getSafeName(&$file)
  {
    $file = str_replace(array('"', '<', '>'), '', $file);
    $file = str_replace('\\', '/', $file);
    $file = false === strpos($file, '/') ? ('a' . $file) : str_replace('/', '/a', $file);
    $info = pathinfo($file);
    $file = substr($info['basename'], 1);
    return isset($info['extension']) ? strtolower($info['extension']) : '';
  }
  private static function getSafeaName(&$name) {
        $name = str_replace(array('"', '<', '>'), '', $name);
        $name = str_replace('\\', '/', $name);
        $name = false === strpos($name, '/') ? ('a' . $name) : str_replace('/', '/a', $name);
        $info = pathinfo($name);
        $name = substr($info['basename'], 1);
        return $name;
    }
  
  private static function getExtentionName(&$name) {
        $info = pathinfo($name);
        return isset($info['extension']) ? strtolower($info['extension']) : '';
    }
    

     /*

     * 文件上传

     * @param $file

     *

     */

    public static function tinypng_uploadHandle($file)
    {
        //文件名是否空
        if (empty($file['name'])) {
            return false;
        }
        //文件名安全性检测
        $ext = self::getTypechoMethod("getSafeName", $file['name']);
        if (!self::getTypechoMethod("checkFileType", $ext) || \Typecho\Common::isAppEngine()) {
            return false;
        }
        //判断格式
        $ext = strtolower($ext);
        $isimage = in_array($ext, array("jpg", "png", "jpeg"));

        $options = Helper::options();
        if (empty($date)) {
            $date = new \Typecho\Date($options->gmtTime);
        }
        $path = \Typecho\Common::url(defined('__TYPECHO_UPLOAD_DIR__') ? __TYPECHO_UPLOAD_DIR__ : \Widget\Upload::UPLOAD_DIR,

                defined('__TYPECHO_UPLOAD_ROOT_DIR__') ? __TYPECHO_UPLOAD_ROOT_DIR__ : __TYPECHO_ROOT_DIR__)

            . '/' . $date->year . '/' . $date->month;

        if (!isset($file['tmp_name'])) {
            return false;
        }
        if ($isimage) {
            $apikey = trim(bsOptions::getInstance()::get_option( 'bearsimple' )['Tinypng_apikey']);
            \Tinify\setKey($apikey);
            $source = \Tinify\fromFile($file['tmp_name']);
        }
        //创建上传目录
        if (!is_dir($path)) {

            if (!self::getTypechoMethod("makeUploadDir", $path)) {

                return false;
            }
        }

        $fileName = sprintf('%u', crc32(uniqid())) . '.' . $ext;
        $path = $path . '/' . $fileName;
        if (!$isimage) {
            if (isset($file['tmp_name'])) {
                if (!@move_uploaded_file($file['tmp_name'], $path)) {
                    return false;
                }
            } else if (isset($file['bytes'])) {
                if (!file_put_contents($path, $file['bytes'])) {
                    return false;
                }
            } else {
                return false;
            }
        }
        if ($isimage) {
            $source->toFile($path);
            $file['size'] = filesize($path);
        } else if (!isset($file['size'])) {
            $file['size'] = filesize($path);
        }

        return array(
            'name' => $file['name'],
            'path' => (defined('__TYPECHO_UPLOAD_DIR__') ? __TYPECHO_UPLOAD_DIR__ : \Widget\Upload::UPLOAD_DIR)
                . '/' . $date->year . '/' . $date->month . '/' . $fileName,
            'size' => $file['size'],
            'type' => $ext,
            'mime' => \Typecho\Common::mimeContentType($path)
        );
    }
    
    
    public static function lsky_uploadHandle($file)
    {
        $options = bsOptions::getInstance()::get_option( 'bearsimple' );
        if (empty($file['name'])) {

            return false;
        }

        $ext = self::_getSafeName($file['name']);

        if (!Upload::checkFileType($ext) || Common::isAppEngine()) {

            return false;
        }
        
        if (self::_isImage($ext)) {

            return self::_uploadImg($file, $ext);
        }
   
        
    
        return self::_uploadOtherFile($file, $ext);
    }

    public static function lsky_deleteHandle(array $content): bool
    {
    $ext = $content['attachment']->type;

        if (self::_isImage($ext)) {

            return self::_deleteImg($content);
        }

        return unlink($content['attachment']->path);
    }

    public static function lsky_modifyHandle($content, $file)
    {
        $options = bsOptions::getInstance()::get_option( 'bearsimple' );
        if (empty($file['name'])) {

            return false;
        }
        $ext = self::_getSafeName($file['name']);
        if ($content['attachment']->type != $ext || Common::isAppEngine()) {

            return false;
        }

        if (!self::_getUploadFile($file)) {

            return false;
        }
       $uploadfile = self::_getUploadFile($file);
        if (self::_isImage($ext)) {
            self::_deleteImg($content);

            return self::_uploadImg($file, $ext);
        }
      
        return self::_uploadOtherFile($file, $ext);
    }

    public static function lsky_attachmentHandle(array $content): string
    {
		$arr = unserialize($content['text']);
		$text = strstr($content['text'],'.');
		$ext = substr($text,1,3);
        if (self::_isImage($ext)) {

            return $content['attachment']->path ?? '';
        }

        $ret = explode('/'.Plugin::UPLOAD_DIR, $arr['path']);
        return Common::url('/'.Plugin::UPLOAD_DIR . @$ret[1], Options::alloc()->siteUrl);
    }
   
   public static function lsky_attachmentDataHandle(array $content)
    {
        return file_get_contents(self::lsky_attachmentHandle($content));
    }
    
    private static function _getUploadDir($ext = ''): string
    {
        if (self::_isImage($ext)) {
            $url = parse_url(Options::alloc()->siteUrl);
            $DIR = str_replace('.', '_', $url['host']);
            return __TYPECHO_ROOT_DIR__ . '/'.Plugin::UPLOAD_DIR;
        } elseif (defined('__TYPECHO_UPLOAD_DIR__')) {
            return __TYPECHO_UPLOAD_DIR__;
        } else {
            $path = Common::url('/'.Plugin::UPLOAD_DIR, __TYPECHO_ROOT_DIR__);
            return $path;
        }
    }

    private static function _getUploadFile($file): string
    {
        return $file['tmp_name'] ?? ($file['bytes'] ?? ($file['bits'] ?? ''));
    }

    private static function _getSafeName(&$name): string
    {
        $name = str_replace(array('"', '<', '>'), '', $name);
        $name = str_replace('\\', '/', $name);
        $name = false === strpos($name, '/') ? ('a' . $name) : str_replace('/', '/a', $name);
        $info = pathinfo($name);
        $name = substr($info['basename'], 1);

        return isset($info['extension']) ? strtolower($info['extension']) : '';
    }

    private static function _makeUploadDir($path): bool
    {
        $path    = preg_replace("/\\\+/", '/', $path);
        $current = rtrim($path, '/');
        $last    = $current;

        while (!is_dir($current) && false !== strpos($path, '/')) {
            $last    = $current;
            $current = dirname($current);
        }

        if ($last == $current) {
            return true;
        }

        if (!@mkdir($last)) {
            return false;
        }

        $stat  = @stat($last);
        $perms = $stat['mode'] & 0007777;
        @chmod($last, $perms);

        return self::_makeUploadDir($path);
    }

    private static function _isImage($ext): bool
    {
        $img_ext_arr = array('gif','jpg','jpeg','png','tiff','bmp','ico','psd','webp','JPG','BMP','GIF','PNG','JPEG','ICO','PSD','TIFF','WEBP');
        return in_array($ext, $img_ext_arr);
    }

    private static function _uploadOtherFile($file, $ext)
    {
        $dir = self::_getUploadDir($ext) . '/' . date('Y') . '/' . date('m');
        if (!self::_makeUploadDir($dir)) {

            return false;
        }

        $path = sprintf('%s/%u.%s', $dir, crc32(uniqid()), $ext);
        if (!isset($file['tmp_name']) || !@move_uploaded_file($file['tmp_name'], $path)) {

            return false;
       }
   
 

        return [
            'name' => $file['name'],
            'path' => $path,
            'size' => $file['size'] ?? filesize($path),
            'type' => $ext,
            'mime' => @Common::mimeContentType($path)
        ];
    }


    private static function _uploadImg($file, $ext)
    {
       
    
        $options = bsOptions::getInstance()::get_option( 'bearsimple' );
        $api     = $options['lskypro_api'] . '/api/v1/upload';
		$token   = 'Bearer '.$options['lskypro_token'];
        $strategyId = $options['lskypro_id'];
        
        $tmp     = self::_getUploadFile($file);
        if (empty($tmp)) {

            return false;
        }

		$img = $file['name'];
        if (!rename($tmp, $img)) {

            return false;
        }
        $params = ['file' => new CURLFile($img)];
        if ($strategyId) {
            $params['strategy_id'] = $strategyId;
        }

        $res = self::_curlPost($api, $params, $token);
          
          
        unlink($img);

        if (!$res) {

            return false;
        }
      

        $json = json_decode($res, true);
     
        
        if ($json['status'] === false) {
            return false;
        }
        
        $data = $json['data'];
         $data = $json['data'];
        return [
            'img_key' => $data['key'],
            'img_id' => $data['md5'],
            'name'   => $data['origin_name'],
            'path'   => $data['links']['url'],
            'size'   => $data['size']*1024,
            'type'   => $data['extension'],
            'mime'   => $data['mimetype'],
			'description'  => $data['mimetype'],
        ];
    }

    private static function _deleteImg(array $content): bool
    {
      
        $options = bsOptions::getInstance()::get_option( 'bearsimple' );
        $api     = $options['lskypro_api'] . '/api/v1/upload';
		$token   = 'Bearer '.$options['lskypro_token'];
     

        $id = $content['attachment']->img_key;
        
        if (empty($id)) {
            return false;
        }
        
        $res  = self::_curlDelete($api . '/' . $id, ['key' => $id], $token);
        $json = json_decode($res, true);
    
        if (!is_array($json)) {

            return false;
        }

        return true;
    }

    private static function _curlDelete($api, $post, $token)
    {
        $headers = array(
            "Content-Type: multipart/form-data",
            "Accept: application/json",
            "Authorization: ".$token,
            );
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $api);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/85.0.4183.102 Safari/537.36');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        $res = curl_exec($ch);
        curl_close($ch);

        return $res;
    }
    
    private static function _curlPost($api, $post, $token)
    {
        $headers = array(
            "Content-Type: multipart/form-data",
            "Accept: application/json",
            "Authorization: ".$token,
            );
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $api);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/85.0.4183.102 Safari/537.36');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        $res = curl_exec($ch);
        curl_close($ch);

        return $res;
    }
    

    public static function getTypechoMethod($methodName, $inParams)

    {

        if (empty($objReflectClass)) {

            $objReflectClass = new ReflectionClass("Widget_Upload");

        }

        $method = $objReflectClass->getMethod($methodName);

        $method->setAccessible(true);

        if ("getSafeName" == $methodName) {

            $result = $method->invokeArgs(null, array(&$inParams));

        } else {

            $result = $method->invokeArgs(null, array($inParams));

        }

        return $result;

    }



    /**

     * 异步更新可用压缩数

     * @param $post

     */

}
