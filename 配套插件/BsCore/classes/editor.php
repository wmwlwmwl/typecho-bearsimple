<?php
/* ponytail: 自 Plugin.php 机械拆分（btbutton/Editor 实现体，约 3300 行）。
   仅后台编辑页经 Plugin::btbutton()/Editor() 转发器按需 require（签名与 activate() 注册不变）；
   两方法为纯静态无 self/$this 依赖，类移动不影响行为。use 块整组保留以防引用漂移 */
namespace TypechoPlugin\BsCore;

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
use CURLFile;
use ReflectionClass;

if (!defined('__TYPECHO_ROOT_DIR__')) exit;

class Editor
{
public static function btbutton(){
     $options = Helper::options();
    
        $dir = $options->pluginUrl.'/BsCore/assets/vendors/';
     if(get_option('bearsimple')['Editors'] == '2'){
		if (strpos( $_SERVER['REQUEST_URI'],'write-post.php') !== false)
{
    $url = 'post';
\Typecho\Widget::widget('Widget_Contents_Post_Edit')->to($post);
}
if (strpos( $_SERVER['REQUEST_URI'],'write-page.php') !== false)
{
    $url = 'page';
\Typecho\Widget::widget('Widget_Contents_Page_Edit')->to($page);
}
if (isset($post) || isset($page)) {
    $cid = isset($post) ? $post->cid : $page->cid;
    
    if ($cid) {
        \Typecho\Widget::widget('Widget_Contents_Attachment_Related', 'parentId=' . $cid)->to($attachment);
    } else {
        \Typecho\Widget::widget('Widget_Contents_Attachment_Unattached')->to($attachment);
    }
}
		?><style>.wmd-button-row {
    height: auto;
}
</style>
 <?php $fontawesomeUrl = $dir.'fontawesome/font-awesome.min.css?v=1';?>
<link rel="stylesheet" href=" <?php echo $fontawesomeUrl; ?>" />
<link rel="stylesheet" href="<?php echo $options->pluginUrl;?>/BsCore/assets/vendors/sweetalert2/sweetalert2.min.css" />
<link href="<?php echo $dir;?>sweetalert2/bootstrap-4.css" rel="stylesheet">
        <script src="<?php echo $options->pluginUrl;?>/BsCore/assets/vendors/sweetalert2/sweetalert2.min.js"></script>
<link rel="stylesheet" href="<?php echo $options->pluginUrl;?>/BsCore/assets/vendors/bootstrap-colorpicker/bootstrap-colorpicker.min.css" />
<script type="text/javascript" src="<?php echo $options->pluginUrl;?>/BsCore/assets/vendors/bootstrap-colorpicker/bootstrap-colorpicker.min.js"></script>

        <link href="<?php echo Helper::options()->pluginUrl ?>/BsCore/modules/bs-emoji/bs-emoji.css?v=1" rel="stylesheet" type="text/css">
<script src="<?php echo Helper::options()->pluginUrl ?>/BsCore/modules/bs-emoji/bs-emoji.js?v=2"></script>
<?php if(get_option('bearsimple')['Cache'] == true && (get_option('bearsimple')['Cache_choose'] == 'memcached'||get_option('bearsimple')['Cache_choose'] == 'redis'||get_option('bearsimple')['Cache_choose'] == 'apcu'||get_option('bearsimple')['Cache_choose'] == 'sqlite'||get_option('bearsimple')['Cache_choose'] == 'memory'||get_option('bearsimple')['Cache_choose'] == 'flysystem') &&  get_option('bearsimple')['enable_markcache'] == true): ?>
<script>
            $(document).ready(function(){
                $('#wmd-button-row').append('<li class="wmd-button" id="wmd-TePass-button" title="插入不缓存标签"><span style="background: none;margin-top:5px;font-size: large;text-align: center;color: #999999;font-family: serif;"><i class="fa fa-times"></i></span></li>');
                if($('#wmd-button-row').length !== 0){
                    $('#wmd-TePass-button').click(function(){
                        var rs = "\r\n<!--no-cache-->\r\n";
                        var myField = $('#text')[0];
                        insertAtCursor(myField,rs);
                    })
                }
                function insertAtCursor(myField, myValue) {
                    if (document.selection) {
                        myField.focus();
                        sel = document.selection.createRange();
                        sel.text = myValue;
                        sel.select();
                    }
                    else if (myField.selectionStart || myField.selectionStart === '0') {
                        var startPos = myField.selectionStart;
                        var endPos = myField.selectionEnd;
                        var restoreTop = myField.scrollTop;
                        myField.value = myField.value.substring(0, startPos) + myValue + myField.value.substring(endPos, myField.value.length);
                        if (restoreTop > 0) {myField.scrollTop = restoreTop;}
                        myField.selectionStart = startPos + myValue.length;
                        myField.selectionEnd = startPos + myValue.length;
                        myField.focus();
                    } else {
                        myField.value += myValue;
                        myField.focus();
                    }
                }
            });
        </script>
        <?php endif; ?>
        
       
<script>
          $(document).ready(function(){
          	$('#wmd-button-row').append('<li class="wmd-button" id="wmd-loginkj-button" title="登录后可见"><span style="background: none;margin-top:5px;font-size: large;text-align: center;color: #999999;font-family: serif;"><i class="fa fa-user-circle"></i></span></li>');
				if($('#wmd-button-row').length !== 0){
					$('#wmd-loginkj-button').click(function(){
						var rs = "[bslogin]隐藏内容[/bslogin]";
						loginkj(rs);
					})
				}


				function loginkj(tag) {
					var myField;
					if (document.getElementById('text') && document.getElementById('text').type == 'textarea') {
						myField = document.getElementById('text');
					} else {
						return false;
					}
					if (document.selection) {
						myField.focus();
						sel = document.selection.createRange();
						sel.text = tag;
						myField.focus();
					}
					else if (myField.selectionStart || myField.selectionStart == '0') {
						var startPos = myField.selectionStart;
						var endPos = myField.selectionEnd;
						var cursorPos = startPos;
						myField.value = myField.value.substring(0, startPos)
						+ tag
						+ myField.value.substring(endPos, myField.value.length);
						cursorPos += tag.length;
						myField.focus();
						myField.selectionStart = cursorPos;
						myField.selectionEnd = cursorPos;
					} else {
						myField.value += tag;
						myField.focus();
					}
				}

				

			});
</script>
		<script> 
          $(document).ready(function(){
          	$('#wmd-button-row').append('<li class="wmd-button" id="wmd-hfkj-button" title="回复或登录后可见"><span style="background: none;margin-top:5px;font-size: large;text-align: center;color: #999999;font-family: serif;"><i class="fa fa-commenting"></i></span></li>');
				if($('#wmd-button-row').length !== 0){
					$('#wmd-hfkj-button').click(function(){
						var rs = "[bshide]隐藏内容[/bshide]";
						hfkj(rs);
					})
				}


				function hfkj(tag) {
					var myField;
					if (document.getElementById('text') && document.getElementById('text').type == 'textarea') {
						myField = document.getElementById('text');
					} else {
						return false;
					}
					if (document.selection) {
						myField.focus();
						sel = document.selection.createRange();
						sel.text = tag;
						myField.focus();
					}
					else if (myField.selectionStart || myField.selectionStart == '0') {
						var startPos = myField.selectionStart;
						var endPos = myField.selectionEnd;
						var cursorPos = startPos;
						myField.value = myField.value.substring(0, startPos)
						+ tag
						+ myField.value.substring(endPos, myField.value.length);
						cursorPos += tag.length;
						myField.focus();
						myField.selectionStart = cursorPos;
						myField.selectionEnd = cursorPos;
					} else {
						myField.value += tag;
						myField.focus();
					}
				}

				

			});
</script>

<script> 
          $(document).ready(function(){
          	$('#wmd-button-row').append('<li class="wmd-button" id="wmd-todocheck-button" title="打勾已完成"><span style="background: none;margin-top:5px;font-size: large;text-align: center;color: #999999;font-family: serif;"><i class="fa fa-check-square"></i></span></li>');
				if($('#wmd-button-row').length !== 0){
					$('#wmd-todocheck-button').click(function(){
						var rs = "[todo-t]Todolist已完成的内容[/todo-t]";
						todocheck(rs);
					})
				}


				function todocheck(tag) {
					var myField;
					if (document.getElementById('text') && document.getElementById('text').type == 'textarea') {
						myField = document.getElementById('text');
					} else {
						return false;
					}
					if (document.selection) {
						myField.focus();
						sel = document.selection.createRange();
						sel.text = tag;
						myField.focus();
					}
					else if (myField.selectionStart || myField.selectionStart == '0') {
						var startPos = myField.selectionStart;
						var endPos = myField.selectionEnd;
						var cursorPos = startPos;
						myField.value = myField.value.substring(0, startPos)
						+ tag
						+ myField.value.substring(endPos, myField.value.length);
						cursorPos += tag.length;
						myField.focus();
						myField.selectionStart = cursorPos;
						myField.selectionEnd = cursorPos;
					} else {
						myField.value += tag;
						myField.focus();
					}
				}
			});
</script>
<script> 
          $(document).ready(function(){
          	$('#wmd-button-row').append('<li class="wmd-button" id="wmd-todonotcheck-button" title="打叉未完成"><span style="background: none;margin-top:5px;font-size: large;text-align: center;color: #999999;font-family: serif;"><i class="fa fa-square"></i></span></li>');
				if($('#wmd-button-row').length !== 0){
					$('#wmd-todonotcheck-button').click(function(){
						var rs = "[todo-f]Todolist待完成的内容[/todo-f]";
						todonotcheck(rs);
					})
				}


				function todonotcheck(tag) {
					var myField;
					if (document.getElementById('text') && document.getElementById('text').type == 'textarea') {
						myField = document.getElementById('text');
					} else {
						return false;
					}
					if (document.selection) {
						myField.focus();
						sel = document.selection.createRange();
						sel.text = tag;
						myField.focus();
					}
					else if (myField.selectionStart || myField.selectionStart == '0') {
						var startPos = myField.selectionStart;
						var endPos = myField.selectionEnd;
						var cursorPos = startPos;
						myField.value = myField.value.substring(0, startPos)
						+ tag
						+ myField.value.substring(endPos, myField.value.length);
						cursorPos += tag.length;
						myField.focus();
						myField.selectionStart = cursorPos;
						myField.selectionEnd = cursorPos;
					} else {
						myField.value += tag;
						myField.focus();
					}
				}
			});
</script>
<script> 
          $(document).ready(function(){
          	$('#wmd-button-row').append('<li class="wmd-button" id="wmd-codes-button" title="代码高亮"><span style="background: none;margin-top:5px;font-size: large;text-align: center;color: #999999;font-family: serif;"><svg t="1632126906909" class="icon" viewBox="0 0 1024 1024" version="1.1" xmlns="http://www.w3.org/2000/svg" p-id="2425" width="16" height="16"><path d="M298.900577 778.338974c-7.070023 7.070023-17.974373 7.070023-25.043373 0L20.039405 524.521175c-7.070023-7.070023-7.070023-17.974373 0-25.043373l253.8178-253.8178c7.070023-7.070023 17.974373-7.070023 25.043373 0l27.242458 27.242458c7.070023 7.070023 7.070023 17.974373 0 25.043373L112.089891 512l214.053144 214.053144c7.070023 7.070023 7.070023 17.974373 0 25.043373L298.900577 778.338974zM444.87316 873.098151c-2.726088 9.269108-12.522198 14.702863-21.24486 11.995195l-33.767058-9.269108c-9.250688-2.726088-14.702863-12.522198-11.976776-21.790282l203.148793-703.132108c2.726088-9.269108 12.522198-14.702863 21.24486-11.995195l33.767058 9.269108c9.250688 2.726088 14.702863 12.522198 11.976776 21.790282L444.87316 873.098151zM752.049215 778.338974c-7.070023 7.070023-17.974373 7.070023-25.043373 0l-27.242458-27.242458c-7.070023-7.070023-7.070023-17.974373 0-25.043373l214.053144-214.053144L699.763384 297.946856c-7.070023-7.070023-7.070023-17.974373 0-25.043373l27.242458-27.242458c7.070023-7.070023 17.974373-7.070023 25.043373 0l253.8178 253.8178c7.070023 7.070023 7.070023 17.974373 0 25.043373L752.049215 778.338974z" p-id="2426" fill="#707070"></path></svg></span></li>');
				if($('#wmd-button-row').length !== 0){
					$('#wmd-codes-button').click(function(){
						var rs = "```编程语言\n这里是内容\n```";
						codes(rs);
					})
				}


				function codes(tag) {
					var myField;
					if (document.getElementById('text') && document.getElementById('text').type == 'textarea') {
						myField = document.getElementById('text');
					} else {
						return false;
					}
					if (document.selection) {
						myField.focus();
						sel = document.selection.createRange();
						sel.text = tag;
						myField.focus();
					}
					else if (myField.selectionStart || myField.selectionStart == '0') {
						var startPos = myField.selectionStart;
						var endPos = myField.selectionEnd;
						var cursorPos = startPos;
						myField.value = myField.value.substring(0, startPos)
						+ tag
						+ myField.value.substring(endPos, myField.value.length);
						cursorPos += tag.length;
						myField.focus();
						myField.selectionStart = cursorPos;
						myField.selectionEnd = cursorPos;
					} else {
						myField.value += tag;
						myField.focus();
					}
				}
			});
</script>
<script> 
          $(document).ready(function(){
          	$('#wmd-button-row').append('<li class="wmd-button" id="wmd-fontcolor-button" title="字体颜色"><span style="background: none;margin-top:5px;font-size: large;text-align: center;color: #999999;font-family: serif;"><i class="fa fa-font"></i></span></li>');
				if($('#wmd-button-row').length !== 0){
					$('#wmd-fontcolor-button').click(function(){
						Swal.fire({
  title: '字体颜色选择器面板',
  html: "<div id=\"color\"></div><br>选择完颜色后点击下面的直接插入按钮进行应用，本功能实现的效果仅在前台文章内页可见。",
  icon: 'info',
showCancelButton: true,
  confirmButtonColor: '#3085d6',
  cancelButtonColor: '#d33',
  cancelButtonText: "取消", 
  confirmButtonText: '直接插入'
}).then((result) => {
  if (result.isConfirmed) {
  var colorss = document.getElementById("colors").value;
  if(!colorss){
      alert('未选择颜色，不能直接插入!');
  }
  else{
    fontcolor('{bs-font color="'+ colorss + '"}内容{/bs-font}');
  }
  }
})
       $('#color').colorpicker({
        popover: false,
        inline: true,
        container: '#color',
template: '<div class="colorpicker">' +
          '<div class="colorpicker-saturation"><i class="colorpicker-guide"></i></div>' +
          '<div class="colorpicker-hue"><i class="colorpicker-guide"></i></div>' +
          '<div class="colorpicker-alpha">' +
          '   <div class="colorpicker-alpha-color"></div>' +
          '   <i class="colorpicker-guide"></i>' +
          '</div>' +
          '<div class="colorpicker-bar">' +
          '   <div class="input-group">' +
          '       <input id="colors" class="form-control input-block color-io" />' +
          '   </div>' +
          '</div>' +
          '</div>'
        })
        .on('colorpickerCreate', function (e) {
          var io = e.colorpicker.element.find('.color-io');
          io.val(e.color.string());
          io.on('change keyup', function () {
            e.colorpicker.setValue(io.val());
          });
        })
        .on('colorpickerChange', function (e) {
          var io = e.colorpicker.element.find('.color-io');
          if (e.value === io.val() || !e.color || !e.color.isValid()) {
            return;
          }
          io.val(e.color.string());
        });
						
					})
				}


				function fontcolor(tag) {
					var myField;
					if (document.getElementById('text') && document.getElementById('text').type == 'textarea') {
						myField = document.getElementById('text');
					} else {
						return false;
					}
					if (document.selection) {
						myField.focus();
						sel = document.selection.createRange();
						sel.text = tag;
						myField.focus();
					}
					else if (myField.selectionStart || myField.selectionStart == '0') {
						var startPos = myField.selectionStart;
						var endPos = myField.selectionEnd;
						var cursorPos = startPos;
						myField.value = myField.value.substring(0, startPos)
						+ tag
						+ myField.value.substring(endPos, myField.value.length);
						cursorPos += tag.length;
						myField.focus();
						myField.selectionStart = cursorPos;
						myField.selectionEnd = cursorPos;
					} else {
						myField.value += tag;
						myField.focus();
					}
				}
			});
</script>
<script> 
          $(document).ready(function(){
          	$('#wmd-button-row').append('<li class="wmd-button" id="wmd-iframe-button" title="插入iframe"><span style="background: none;margin-top:5px;font-size: large;text-align: center;color: #999999;font-family: serif;"><i class="fa fa-window-maximize"></i></span></li>');
				if($('#wmd-button-row').length !== 0){
					$('#wmd-iframe-button').click(function(){
						var rs = "{bs-iframe}iframe地址{/bs-iframe}";
						iframe(rs);
					})
				}


				function iframe(tag) {
					var myField;
					if (document.getElementById('text') && document.getElementById('text').type == 'textarea') {
						myField = document.getElementById('text');
					} else {
						return false;
					}
					if (document.selection) {
						myField.focus();
						sel = document.selection.createRange();
						sel.text = tag;
						myField.focus();
					}
					else if (myField.selectionStart || myField.selectionStart == '0') {
						var startPos = myField.selectionStart;
						var endPos = myField.selectionEnd;
						var cursorPos = startPos;
						myField.value = myField.value.substring(0, startPos)
						+ tag
						+ myField.value.substring(endPos, myField.value.length);
						cursorPos += tag.length;
						myField.focus();
						myField.selectionStart = cursorPos;
						myField.selectionEnd = cursorPos;
					} else {
						myField.value += tag;
						myField.focus();
					}
				}
			});
</script>
<script> 
          $(document).ready(function(){
          	$('#wmd-button-row').append('<li class="wmd-button" id="wmd-bframe-button" title="插入Bframe"><span style="background: none;margin-top:5px;font-size: large;text-align: center;color: #999999;font-family: serif;"><i class="fa fa-external-link-square"></i></span></li>');
				if($('#wmd-button-row').length !== 0){
					$('#wmd-bframe-button').click(function(){
						var rs = '[bsfra image="占位封面图直链" url="页面地址"]';
						bframe(rs);
					})
				}


				function bframe(tag) {
					var myField;
					if (document.getElementById('text') && document.getElementById('text').type == 'textarea') {
						myField = document.getElementById('text');
					} else {
						return false;
					}
					if (document.selection) {
						myField.focus();
						sel = document.selection.createRange();
						sel.text = tag;
						myField.focus();
					}
					else if (myField.selectionStart || myField.selectionStart == '0') {
						var startPos = myField.selectionStart;
						var endPos = myField.selectionEnd;
						var cursorPos = startPos;
						myField.value = myField.value.substring(0, startPos)
						+ tag
						+ myField.value.substring(endPos, myField.value.length);
						cursorPos += tag.length;
						myField.focus();
						myField.selectionStart = cursorPos;
						myField.selectionEnd = cursorPos;
					} else {
						myField.value += tag;
						myField.focus();
					}
				}
			});
</script>
<script> 
          $(document).ready(function(){
          	$('#wmd-button-row').append('<li class="wmd-button" id="wmd-audio-button" title="插入音频"><span style="background: none;margin-top:5px;font-size: large;text-align: center;color: #999999;font-family: serif;"><i class="fa fa-file-audio-o"></i></span></li>');
				if($('#wmd-button-row').length !== 0){
					$('#wmd-audio-button').click(function(){
						var rs = "[bsaudio]音频地址[/bsaudio]";
						audio(rs);
					})
				}


				function audio(tag) {
					var myField;
					if (document.getElementById('text') && document.getElementById('text').type == 'textarea') {
						myField = document.getElementById('text');
					} else {
						return false;
					}
					if (document.selection) {
						myField.focus();
						sel = document.selection.createRange();
						sel.text = tag;
						myField.focus();
					}
					else if (myField.selectionStart || myField.selectionStart == '0') {
						var startPos = myField.selectionStart;
						var endPos = myField.selectionEnd;
						var cursorPos = startPos;
						myField.value = myField.value.substring(0, startPos)
						+ tag
						+ myField.value.substring(endPos, myField.value.length);
						cursorPos += tag.length;
						myField.focus();
						myField.selectionStart = cursorPos;
						myField.selectionEnd = cursorPos;
					} else {
						myField.value += tag;
						myField.focus();
					}
				}
			});
</script>
<script> 
          $(document).ready(function(){
          	$('#wmd-button-row').append('<li class="wmd-button" id="wmd-musiclist-button" title="插入音乐歌单"><span style="background: none;margin-top:5px;font-size: large;text-align: center;color: #999999;font-family: serif;"><i class="fa fa-music"></i></span></li>');
				if($('#wmd-button-row').length !== 0){
					$('#wmd-musiclist-button').click(function(){
						var rs = "[bsmusic]网易云音乐歌单ID[/bsmusic]";
						musiclist(rs);
					})
				}


				function musiclist(tag) {
					var myField;
					if (document.getElementById('text') && document.getElementById('text').type == 'textarea') {
						myField = document.getElementById('text');
					} else {
						return false;
					}
					if (document.selection) {
						myField.focus();
						sel = document.selection.createRange();
						sel.text = tag;
						myField.focus();
					}
					else if (myField.selectionStart || myField.selectionStart == '0') {
						var startPos = myField.selectionStart;
						var endPos = myField.selectionEnd;
						var cursorPos = startPos;
						myField.value = myField.value.substring(0, startPos)
						+ tag
						+ myField.value.substring(endPos, myField.value.length);
						cursorPos += tag.length;
						myField.focus();
						myField.selectionStart = cursorPos;
						myField.selectionEnd = cursorPos;
					} else {
						myField.value += tag;
						myField.focus();
					}
				}
			});
</script>

<script> 
          $(document).ready(function(){
          	$('#wmd-button-row').append('<li class="wmd-button" id="wmd-star-button" title="插入评星"><span style="background: none;margin-top:5px;font-size: large;text-align: center;color: #999999;font-family: serif;"><i class="fa fa-star-half-o"></i></span></li>');
				if($('#wmd-button-row').length !== 0){
					$('#wmd-star-button').click(function(){
						Swal.fire({
  title: '第一步 选择评星样式',
  input: 'select',
  inputOptions: {
      'star': '星星',
      'heart': '红心',
  },
  inputPlaceholder: '选择一款样式',
  showCancelButton: true,
cancelButtonText: "取消", 
  confirmButtonText: '下一步',
  inputValidator: (valueone) => {
    return new Promise((resolve) => {
      if (valueone !== '') {

                Swal.fire({
  title: '第二步 输入评星数目',
  input: 'number',
inputLabel: '输入评星数目，最高五颗星',
inputAttributes:{
  min:1,
  max:5,
  
},
  inputPlaceholder: '输入评星数目',
  showCancelButton: true,
cancelButtonText: "取消", 
  confirmButtonText: '插入',
  inputValidator: (valuetwo) => {
    return new Promise((resolve) => {
      if (valuetwo !== '') {
var text = '[bseva type="'+valueone+'"]'+valuetwo+'[/bseva]';
      star(text);
        
        resolve() 
      } 
      else{
         resolve('您必须输入评星数目~~') 
      }
    })
  }
})

        
    
        resolve() 
      } 
      else{
         resolve('您必须选择一款评星样式~~') 
      }
    })
  }
})
					})
				}


				function star(tag) {
					var myField;
					if (document.getElementById('text') && document.getElementById('text').type == 'textarea') {
						myField = document.getElementById('text');
					} else {
						return false;
					}
					if (document.selection) {
						myField.focus();
						sel = document.selection.createRange();
						sel.text = tag;
						myField.focus();
					}
					else if (myField.selectionStart || myField.selectionStart == '0') {
						var startPos = myField.selectionStart;
						var endPos = myField.selectionEnd;
						var cursorPos = startPos;
						myField.value = myField.value.substring(0, startPos)
						+ tag
						+ myField.value.substring(endPos, myField.value.length);
						cursorPos += tag.length;
						myField.focus();
						myField.selectionStart = cursorPos;
						myField.selectionEnd = cursorPos;
					} else {
						myField.value += tag;
						myField.focus();
					}
				}
			});
</script>
<script> 
          $(document).ready(function(){
          	$('#wmd-button-row').append('<li class="wmd-button" id="wmd-accord1-button" title="插入线性手风琴"><span style="background: none;margin-top:5px;font-size: large;text-align: center;color: #999999;font-family: serif;"><i class="fa fa-plus-square"></i></span></li>');
				if($('#wmd-button-row').length !== 0){
					$('#wmd-accord1-button').click(function(){
						var rs = "{bs-accord style=line title=线性手风琴标题}线性手风琴内容{/bs-accord}";
						accord1(rs);
					})
				}


				function accord1(tag) {
					var myField;
					if (document.getElementById('text') && document.getElementById('text').type == 'textarea') {
						myField = document.getElementById('text');
					} else {
						return false;
					}
					if (document.selection) {
						myField.focus();
						sel = document.selection.createRange();
						sel.text = tag;
						myField.focus();
					}
					else if (myField.selectionStart || myField.selectionStart == '0') {
						var startPos = myField.selectionStart;
						var endPos = myField.selectionEnd;
						var cursorPos = startPos;
						myField.value = myField.value.substring(0, startPos)
						+ tag
						+ myField.value.substring(endPos, myField.value.length);
						cursorPos += tag.length;
						myField.focus();
						myField.selectionStart = cursorPos;
						myField.selectionEnd = cursorPos;
					} else {
						myField.value += tag;
						myField.focus();
					}
				}
			});
</script>
<script> 
          $(document).ready(function(){
          	$('#wmd-button-row').append('<li class="wmd-button" id="wmd-accord2-button" title="插入普通手风琴"><span style="background: none;margin-top:5px;font-size: large;text-align: center;color: #999999;font-family: serif;"><i class="fa fa-plus-square-o"></i></span></li>');
				if($('#wmd-button-row').length !== 0){
					$('#wmd-accord2-button').click(function(){
						var rs = "{bs-accord style=common title=普通手风琴标题}普通手风琴内容{/bs-accord}";
						accord2(rs);
					})
				}


				function accord2(tag) {
					var myField;
					if (document.getElementById('text') && document.getElementById('text').type == 'textarea') {
						myField = document.getElementById('text');
					} else {
						return false;
					}
					if (document.selection) {
						myField.focus();
						sel = document.selection.createRange();
						sel.text = tag;
						myField.focus();
					}
					else if (myField.selectionStart || myField.selectionStart == '0') {
						var startPos = myField.selectionStart;
						var endPos = myField.selectionEnd;
						var cursorPos = startPos;
						myField.value = myField.value.substring(0, startPos)
						+ tag
						+ myField.value.substring(endPos, myField.value.length);
						cursorPos += tag.length;
						myField.focus();
						myField.selectionStart = cursorPos;
						myField.selectionEnd = cursorPos;
					} else {
						myField.value += tag;
						myField.focus();
					}
				}
			});
</script>
<script> 
          $(document).ready(function(){
          	$('#wmd-button-row').append('<li class="wmd-button" id="wmd-message-button" title="插入提示框"><span style="background: none;margin-top:5px;font-size: large;text-align: center;color: #999999;font-family: serif;"><i class="fa fa-sticky-note-o"></i></span></li>');
				if($('#wmd-button-row').length !== 0){
					$('#wmd-message-button').click(function(){
						Swal.fire({
  title: '第一步 选择提示框样式',
  input: 'select',
  inputOptions: {
      'common': '普通提示框',
      'basic': '阴影提示框',
      'commonclose': '普通可关闭提示框',
      'basicclose':'阴影可关闭提示框',
  },
  inputPlaceholder: '选择一款提示框',
  showCancelButton: true,
cancelButtonText: "取消", 
  confirmButtonText: '下一步',
  inputValidator: (valueone) => {
    return new Promise((resolve) => {
      if (valueone !== '') {

                Swal.fire({
  title: '第二步 选择提示框颜色',
  input: 'select',
  inputOptions: {
      'standard': '淡色',
      'info':'淡青色',
      'primary': '蓝色',
      'secondary': '黑色',
      'red': '红色',
      'orange': '橙色',
      'yellow': '黄色',
      'olive': '淡绿色',
      'green': '深绿色',
      'teal': '青色',
      'violet': '紫罗兰色',
      'purple': '基佬紫色',
      'pink': '粉色',
      'brown': '土黄色',
      'grey': '灰色',
  },
  inputPlaceholder: '选择一款颜色',
  showCancelButton: true,
cancelButtonText: "取消", 
  confirmButtonText: '下一步',
  inputValidator: (valuetwo) => {
    return new Promise((resolve) => {
      if (valuetwo !== '') {
        
        

  Swal.fire({
      title: '第三步 填写提示框标题',
  input: 'text',
  inputLabel: '提示框标题',
  inputPlaceholder: '填写提示框标题',
    showCancelButton: true,
cancelButtonText: "取消", 
  confirmButtonText: '下一步',
  inputValidator: (valuethree) => {
    return new Promise((resolve) => {
      if (valuethree !== '') {
        
        
        Swal.fire({
  title: '第四步 填写提示框内容',
  input: 'textarea',
  inputLabel: '提示框内容',
  inputPlaceholder: '请填写提示框内容',
  showCancelButton: true,
  cancelButtonText: "取消", 
  confirmButtonText: '下一步',
  inputValidator: (valuefour) => {
    if (!valuefour) {
      return '您必须填写提示框内容'
    }
    else{
        
        
        
        Swal.fire({
  title: '第五步 填写提示框图标名',
  input: 'text',
  inputLabel: '提示框图标名(若不需要图标可留空)',
  'html':'<a href="https://bs-icon.typecho.ru/" target="_blank">图标获取请戳这里</a>',
  inputPlaceholder: '请填写提示框图标名',
  showCancelButton: true,
  cancelButtonText: "取消", 
  confirmButtonText: '插入',
  inputValidator: (valuefive) => {
      if(!valuefive){
          var icon = '';
      }
      else{
          var icon = ' icon="'+valuefive+'"';
      }
      var text = '[bsmessage type="'+valueone+'" color="'+valuetwo+'" title="'+valuethree+'"'+icon+']'+valuefour+'[/bsmessage]';
      message(text);
    resolve()
  }
})
        
        
        
        resolve()
    }
  }
})
        
        
        
        resolve() 
      } 
      else{
         resolve('您必须填写提示框标题~~') 
      }
    })
  }
})

        
        
        
        
        
        resolve() 
      } 
      else{
         resolve('您必须选择一款颜色~~') 
      }
    })
  }
})

        
        
        
        
        
        
        
        
        resolve() 
      } 
      else{
         resolve('您必须选择一款提示框~~') 
      }
    })
  }
})
					})
				}


				function message(tag) {
					var myField;
					if (document.getElementById('text') && document.getElementById('text').type == 'textarea') {
						myField = document.getElementById('text');
					} else {
						return false;
					}
					if (document.selection) {
						myField.focus();
						sel = document.selection.createRange();
						sel.text = tag;
						myField.focus();
					}
					else if (myField.selectionStart || myField.selectionStart == '0') {
						var startPos = myField.selectionStart;
						var endPos = myField.selectionEnd;
						var cursorPos = startPos;
						myField.value = myField.value.substring(0, startPos)
						+ tag
						+ myField.value.substring(endPos, myField.value.length);
						cursorPos += tag.length;
						myField.focus();
						myField.selectionStart = cursorPos;
						myField.selectionEnd = cursorPos;
					} else {
						myField.value += tag;
						myField.focus();
					}
				}
			});
</script>
<script> 
          $(document).ready(function(){
          	$('#wmd-button-row').append('<li class="wmd-button" id="wmd-button-button" title="插入按钮"><span style="background: none;margin-top:5px;font-size: large;text-align: center;color: #999999;font-family: serif;"><i class="fa fa-circle-o-notch"></i></span></li>');
				if($('#wmd-button-row').length !== 0){
					$('#wmd-button-button').click(function(){
						Swal.fire({
  title: '第一步 选择按钮样式',
  input: 'select',
  inputOptions: {
      'common': '普通按钮',
      'basic': '光圈按钮',
      'animated': '动画按钮',
  },
  inputPlaceholder: '选择一款按钮',
  showCancelButton: true,
cancelButtonText: "取消", 
  confirmButtonText: '下一步',
  inputValidator: (valueone) => {
    return new Promise((resolve) => {
      if (valueone !== '') {

                Swal.fire({
  title: '第二步 选择按钮颜色',
  input: 'select',
  inputOptions: {
      'standard': '淡色',
      'primary': '蓝色',
      'secondary': '黑色',
      'red': '红色',
      'orange': '橙色',
      'yellow': '黄色',
      'olive': '淡绿色',
      'green': '深绿色',
      'teal': '青色',
      'violet': '紫罗兰色',
      'purple': '基佬紫色',
      'pink': '粉色',
      'brown': '土黄色',
      'grey': '灰色',
  },
  inputPlaceholder: '选择一款颜色',
  showCancelButton: true,
cancelButtonText: "取消", 
  confirmButtonText: '下一步',
  inputValidator: (valuetwo) => {
    return new Promise((resolve) => {
      if (valuetwo !== '') {
        
        

  Swal.fire({
      title: '第三步 填写按钮点击链接',
  input: 'url',
  inputLabel: '按钮点击跳转链接',
  inputPlaceholder: '填写按钮点击跳转链接，需带http(s)://',
    showCancelButton: true,
cancelButtonText: "取消", 
  confirmButtonText: '下一步',
  inputValidator: (valuethree) => {
    return new Promise((resolve) => {
      if (valuethree !== '') {
        
        
        Swal.fire({
  title: '第四步 填写按钮名称',
  input: 'text',
  inputLabel: '按钮名称(若为动画按钮，两个名称请使用|隔开)',
  inputPlaceholder: '请填写按钮名称',
  showCancelButton: true,
  cancelButtonText: "取消", 
  confirmButtonText: '下一步',
  inputValidator: (valuefour) => {
    if (!valuefour) {
      return '您必须填写按钮名称'
    }
    else{
        
        
        
        Swal.fire({
  title: '第五步 填写按钮图标名',
  input: 'text',
  inputLabel: '按钮图标名(若不需要图标可留空)',
  'html':'<a href="https://bs-icon.typecho.ru/" target="_blank">图标获取请戳这里</a>',
  inputPlaceholder: '请填写按钮图标名',
  showCancelButton: true,
  cancelButtonText: "取消", 
  confirmButtonText: '插入',
  inputValidator: (valuefive) => {
      if(!valuefive){
          var icon = '';
      }
      else{
          var icon = ' icon="'+valuefive+'"';
      }
      var text = '[bsbtn type="'+valueone+'" color="'+valuetwo+'" url="'+valuethree+'"'+icon+']'+valuefour+'[/bsbtn]';
      button(text);
    resolve()
  }
})
        
        
        
        resolve()
    }
  }
})
        
        
        
        resolve() 
      } 
      else{
         resolve('您必须填写跳转链接~~') 
      }
    })
  }
})

        
        
        
        
        
        resolve() 
      } 
      else{
         resolve('您必须选择一款颜色~~') 
      }
    })
  }
})

        
        
        
        
        
        
        
        
        resolve() 
      } 
      else{
         resolve('您必须选择一款按钮~~') 
      }
    })
  }
})
					})
				}


				function button(tag) {
					var myField;
					if (document.getElementById('text') && document.getElementById('text').type == 'textarea') {
						myField = document.getElementById('text');
					} else {
						return false;
					}
					if (document.selection) {
						myField.focus();
						sel = document.selection.createRange();
						sel.text = tag;
						myField.focus();
					}
					else if (myField.selectionStart || myField.selectionStart == '0') {
						var startPos = myField.selectionStart;
						var endPos = myField.selectionEnd;
						var cursorPos = startPos;
						myField.value = myField.value.substring(0, startPos)
						+ tag
						+ myField.value.substring(endPos, myField.value.length);
						cursorPos += tag.length;
						myField.focus();
						myField.selectionStart = cursorPos;
						myField.selectionEnd = cursorPos;
					} else {
						myField.value += tag;
						myField.focus();
					}
				}
			});
</script>
 <script> 
          $(document).ready(function(){
          	$('#wmd-button-row').append('<li class="wmd-button" id="wmd-progress-button" title="插入进度条"><span style="background: none;margin-top:5px;font-size: large;text-align: center;color: #999999;font-family: serif;"><i class="fa fa-product-hunt"></i></span></li>');
				if($('#wmd-button-row').length !== 0){
				    $('#wmd-progress-button').click(function(){
				    Swal.fire({
  title: '选择进度条颜色',
  input: 'select',
  inputOptions: {
      'normal':'蓝色',
      'secondary': '黑色',
      'default': '灰色',
      'warning': '黄色',
      'success': '绿色',
      'info': '天蓝色',
      'warning': '黄色',
      'danger': '红色'
  },
  inputPlaceholder: '选择一款颜色',
  showCancelButton: true,
cancelButtonText: "取消", 
  confirmButtonText: '下一步',
  inputValidator: (valueone) => {
    return new Promise((resolve) => {
      if (valueone !== '') {
      
      Swal.fire({
      title: '第二步 填写进度条标题',
  input: 'text',
  inputLabel: '进度条标题[将显示在进度条上方]',
  inputPlaceholder: '填写进度条标题',
    showCancelButton: true,
cancelButtonText: "取消", 
  confirmButtonText: '下一步',
  inputValidator: (valuetwo) => {
    return new Promise((resolve) => {
      if (valuetwo !== '') {
             Swal.fire({
      title: '第三步 填写进度条数值',
  input: 'text',
  inputLabel: '进度条数值[如40%就填写40]',
  inputPlaceholder: '填写进度条数值',
    showCancelButton: true,
cancelButtonText: "取消", 
  confirmButtonText: '插入',
  inputValidator: (valuethree) => {
    return new Promise((resolve) => {
      if (valuethree !== '') {
                var text = '[bsprog color="'+valueone+'" number="'+valuethree+'"]'+valuetwo+'[/bsprog]';
      progress(text);
    resolve()
      }
      else{
       resolve('您必须填写进度条数值~')    
      }
    })
  }
})
    resolve()
      }
      else{
       resolve('您必须填写进度条标题~')    
      }
    })
  }
})
      
      
      
      
      } 
      else{
         resolve('您必须选择一款颜色~') 
      }
    })
  }
})

				})
				}


				function progress(tag) {
					var myField;
					if (document.getElementById('text') && document.getElementById('text').type == 'textarea') {
						myField = document.getElementById('text');
					} else {
						return false;
					}
					if (document.selection) {
						myField.focus();
						sel = document.selection.createRange();
						sel.text = tag;
						myField.focus();
					}
					else if (myField.selectionStart || myField.selectionStart == '0') {
						var startPos = myField.selectionStart;
						var endPos = myField.selectionEnd;
						var cursorPos = startPos;
						myField.value = myField.value.substring(0, startPos)
						+ tag
						+ myField.value.substring(endPos, myField.value.length);
						cursorPos += tag.length;
						myField.focus();
						myField.selectionStart = cursorPos;
						myField.selectionEnd = cursorPos;
					} else {
						myField.value += tag;
						myField.focus();
					}
				}
			});
</script>
<script> 
          $(document).ready(function(){
          	$('#wmd-button-row').append('<li class="wmd-button" id="wmd-fujian-button" title="插入所有附件"><span style="background: none;margin-top:5px;font-size: large;text-align: center;color: #999999;font-family: serif;"><i class="fa fa-paperclip"></i></span></li>');
				if($('#wmd-button-row').length !== 0){
					$('#wmd-fujian-button').click(function(){
                 function getData() {
                    $.ajax({
                        type: "GET",
                        url: "<?php getAttachFile(); ?>",
                        data: {
                            "cid": "<?php echo $cid; ?>",
                             "url": "<?php echo $url; ?>",
                        },
                        dateType: "json",
                        success: function(json) {
                            json = JSON.parse(json);
                            content(json.list);
                        },
                        complete: function() {
                        },
                        error: function() {
                            alert("数据获取错误");
                        }
                    });
                }
                getData();
                function content(list) {
                    var filename = " ";
                    var fileurl = " ";
                    for(var i in list) {
                        if(list[i]['type'] == 'img'){
                        filename += '![' + list[i]['title'] + '][' + list[i]['cid'] + ']\n'
fileurl += '\n  [' + list[i]['cid'] + ']:' + list[i]['url']
}
                        if(list[i]['type'] == 'other'){
                        filename += '[' + list[i]['title'] + '][' + list[i]['cid'] + ']\n'
fileurl += '\n  [' + list[i]['cid'] + ']:' + list[i]['url']
}
}
fujian(filename + fileurl);
}
					})
				}


				function fujian(tag) {
					var myField;
					if (document.getElementById('text') && document.getElementById('text').type == 'textarea') {
						myField = document.getElementById('text');
					} else {
						return false;
					}
					if (document.selection) {
						myField.focus();
						sel = document.selection.createRange();
						sel.text = tag;
						myField.focus();
					}
					else if (myField.selectionStart || myField.selectionStart == '0') {
						var startPos = myField.selectionStart;
						var endPos = myField.selectionEnd;
						var cursorPos = startPos;
						myField.value = myField.value.substring(0, startPos)
						+ tag
						+ myField.value.substring(endPos, myField.value.length);
						cursorPos += tag.length;
						myField.focus();
						myField.selectionStart = cursorPos;
						myField.selectionEnd = cursorPos;
					} else {
						myField.value += tag;
						myField.focus();
					}
				}
			});
</script>

<script> 
          $(document).ready(function(){
          	$('#wmd-button-row').append('<li class="wmd-button" id="wmd-bscardgithub-button" title="Github仓库"><span style="background: none;margin-top:5px;font-size: large;text-align: center;color: #999999;font-family: serif;"><i class="fa fa-github"></i></span></li>');
				if($('#wmd-button-row').length !== 0){
					$('#wmd-bscardgithub-button').click(function(){
						var rs = '[bsgit user="Github仓库拥有者，如whitebearcode"]Github项目名，如typecho-bearsimple[/bsgit]';
						githubcard(rs);
					})
				}


				function githubcard(tag) {
					var myField;
					if (document.getElementById('text') && document.getElementById('text').type == 'textarea') {
						myField = document.getElementById('text');
					} else {
						return false;
					}
					if (document.selection) {
						myField.focus();
						sel = document.selection.createRange();
						sel.text = tag;
						myField.focus();
					}
					else if (myField.selectionStart || myField.selectionStart == '0') {
						var startPos = myField.selectionStart;
						var endPos = myField.selectionEnd;
						var cursorPos = startPos;
						myField.value = myField.value.substring(0, startPos)
						+ tag
						+ myField.value.substring(endPos, myField.value.length);
						cursorPos += tag.length;
						myField.focus();
						myField.selectionStart = cursorPos;
						myField.selectionEnd = cursorPos;
					} else {
						myField.value += tag;
						myField.focus();
					}
				}
			});
</script>
<script> 
          $(document).ready(function(){
          	$('#wmd-button-row').append('<li class="wmd-button" id="wmd-gallery-button" title="相册"><span style="background: none;margin-top:5px;font-size: large;text-align: center;color: #999999;font-family: serif;"><i class="fa fa-file-image-o"></i></span></li>');
				if($('#wmd-button-row').length !== 0){
					$('#wmd-gallery-button').click(function(){
						var rs = '[bsgallery title="相册名"]\n[bsimg title="图片标题1"]图片地址1[/bsimg]\n[bsimg title="图片标题2"]图片地址2[/bsimg]\n[bsimg title="图片标题3"]图片地址3[/bsimg]\n[/bsgallery]';
						gallery(rs);
					})
				}


				function gallery(tag) {
					var myField;
					if (document.getElementById('text') && document.getElementById('text').type == 'textarea') {
						myField = document.getElementById('text');
					} else {
						return false;
					}
					if (document.selection) {
						myField.focus();
						sel = document.selection.createRange();
						sel.text = tag;
						myField.focus();
					}
					else if (myField.selectionStart || myField.selectionStart == '0') {
						var startPos = myField.selectionStart;
						var endPos = myField.selectionEnd;
						var cursorPos = startPos;
						myField.value = myField.value.substring(0, startPos)
						+ tag
						+ myField.value.substring(endPos, myField.value.length);
						cursorPos += tag.length;
						myField.focus();
						myField.selectionStart = cursorPos;
						myField.selectionEnd = cursorPos;
					} else {
						myField.value += tag;
						myField.focus();
					}
				}
			});
</script>
<script> 
          $(document).ready(function(){
          	$('#wmd-button-row').append('<li class="wmd-button" id="wmd-time-button" title="时间计划"><span style="background: none;margin-top:5px;font-size: large;text-align: center;color: #999999;font-family: serif;"><i class="fa fa-microchip"></i></span></li>');
				if($('#wmd-button-row').length !== 0){
					$('#wmd-time-button').click(function(){
						var rs = '[bstimes title="时间计划名，必填!"]\n[bstime time="时间"]计划内容[/bstime]\n[bstime time="时间2"]计划内容2[/bstime]\n[bstime time="时间3"]计划内容3[/bstime]\n[/bstimes]';
						time(rs);
					})
				}


				function time(tag) {
					var myField;
					if (document.getElementById('text') && document.getElementById('text').type == 'textarea') {
						myField = document.getElementById('text');
					} else {
						return false;
					}
					if (document.selection) {
						myField.focus();
						sel = document.selection.createRange();
						sel.text = tag;
						myField.focus();
					}
					else if (myField.selectionStart || myField.selectionStart == '0') {
						var startPos = myField.selectionStart;
						var endPos = myField.selectionEnd;
						var cursorPos = startPos;
						myField.value = myField.value.substring(0, startPos)
						+ tag
						+ myField.value.substring(endPos, myField.value.length);
						cursorPos += tag.length;
						myField.focus();
						myField.selectionStart = cursorPos;
						myField.selectionEnd = cursorPos;
					} else {
						myField.value += tag;
						myField.focus();
					}
				}
			});
</script>
<script> 
          $(document).ready(function(){
          	$('#wmd-button-row').append('<li class="wmd-button" id="wmd-quotepost-button" title="引用文章"><span style="background: none;margin-top:5px;font-size: large;text-align: center;color: #999999;font-family: serif;"><i class="fa fa-newspaper-o"></i></span></li>');
				if($('#wmd-button-row').length !== 0){
					$('#wmd-quotepost-button').click(function(){
						var rs = '[bspost cid="文章CID"]';
						quotepost(rs);
					})
				}


				function quotepost(tag) {
					var myField;
					if (document.getElementById('text') && document.getElementById('text').type == 'textarea') {
						myField = document.getElementById('text');
					} else {
						return false;
					}
					if (document.selection) {
						myField.focus();
						sel = document.selection.createRange();
						sel.text = tag;
						myField.focus();
					}
					else if (myField.selectionStart || myField.selectionStart == '0') {
						var startPos = myField.selectionStart;
						var endPos = myField.selectionEnd;
						var cursorPos = startPos;
						myField.value = myField.value.substring(0, startPos)
						+ tag
						+ myField.value.substring(endPos, myField.value.length);
						cursorPos += tag.length;
						myField.focus();
						myField.selectionStart = cursorPos;
						myField.selectionEnd = cursorPos;
					} else {
						myField.value += tag;
						myField.focus();
					}
				}
			});
</script>
<script> 
          $(document).ready(function(){
          	$('#wmd-button-row').append('<li class="wmd-button" id="wmd-postmark-button" title="标注文字"><span style="background: none;margin-top:5px;font-size: large;text-align: center;color: #999999;font-family: serif;"><i class="fa fa-bookmark-o"></i></span></li>');
				if($('#wmd-button-row').length !== 0){
					$('#wmd-postmark-button').click(function(){
						var rs = '[bsmark]要标注的内容[/bsmark]';
						postmark(rs);
					})
				}


				function postmark(tag) {
					var myField;
					if (document.getElementById('text') && document.getElementById('text').type == 'textarea') {
						myField = document.getElementById('text');
					} else {
						return false;
					}
					if (document.selection) {
						myField.focus();
						sel = document.selection.createRange();
						sel.text = tag;
						myField.focus();
					}
					else if (myField.selectionStart || myField.selectionStart == '0') {
						var startPos = myField.selectionStart;
						var endPos = myField.selectionEnd;
						var cursorPos = startPos;
						myField.value = myField.value.substring(0, startPos)
						+ tag
						+ myField.value.substring(endPos, myField.value.length);
						cursorPos += tag.length;
						myField.focus();
						myField.selectionStart = cursorPos;
						myField.selectionEnd = cursorPos;
					} else {
						myField.value += tag;
						myField.focus();
					}
				}
			});
</script>
<script> 
          $(document).ready(function(){
          	$('#wmd-button-row').append('<li class="wmd-button" id="wmd-postruby-button" title="文字带拼音"><span style="background: none;margin-top:5px;font-size: large;text-align: center;color: #999999;font-family: serif;"><i class="fa fa-file-word-o"></i></span></li>');
				if($('#wmd-button-row').length !== 0){
					$('#wmd-postruby-button').click(function(){
						var rs = '[bsruby]要带拼音的汉字[/bsruby]';
						postruby(rs);
					})
				}


				function postruby(tag) {
					var myField;
					if (document.getElementById('text') && document.getElementById('text').type == 'textarea') {
						myField = document.getElementById('text');
					} else {
						return false;
					}
					if (document.selection) {
						myField.focus();
						sel = document.selection.createRange();
						sel.text = tag;
						myField.focus();
					}
					else if (myField.selectionStart || myField.selectionStart == '0') {
						var startPos = myField.selectionStart;
						var endPos = myField.selectionEnd;
						var cursorPos = startPos;
						myField.value = myField.value.substring(0, startPos)
						+ tag
						+ myField.value.substring(endPos, myField.value.length);
						cursorPos += tag.length;
						myField.focus();
						myField.selectionStart = cursorPos;
						myField.selectionEnd = cursorPos;
					} else {
						myField.value += tag;
						myField.focus();
					}
				}
			});
</script>
<script> 
          $(document).ready(function(){
          	$('#wmd-button-row').append('<li class="wmd-button" id="wmd-everydaypaper-button" title="每日60s早报"><span style="background: none;margin-top:5px;font-size: large;text-align: center;color: #999999;font-family: serif;"><i class="fa fa-calendar-o"></i></span></li>');
				if($('#wmd-button-row').length !== 0){
					$('#wmd-everydaypaper-button').click(function(){
						var rs = '[bspaper image="true"]';
						everydaypaper(rs);
					})
				}


				function everydaypaper(tag) {
					var myField;
					if (document.getElementById('text') && document.getElementById('text').type == 'textarea') {
						myField = document.getElementById('text');
					} else {
						return false;
					}
					if (document.selection) {
						myField.focus();
						sel = document.selection.createRange();
						sel.text = tag;
						myField.focus();
					}
					else if (myField.selectionStart || myField.selectionStart == '0') {
						var startPos = myField.selectionStart;
						var endPos = myField.selectionEnd;
						var cursorPos = startPos;
						myField.value = myField.value.substring(0, startPos)
						+ tag
						+ myField.value.substring(endPos, myField.value.length);
						cursorPos += tag.length;
						myField.focus();
						myField.selectionStart = cursorPos;
						myField.selectionEnd = cursorPos;
					} else {
						myField.value += tag;
						myField.focus();
					}
				}
			});
</script>
<script> 
          $(document).ready(function(){
          	$('#wmd-button-row').append('<li class="wmd-button" id="wmd-emoji-button" title="表情"><span style="background: none;margin-top:5px;font-size: large;text-align: center;color: #999999;font-family: serif;"><i class="fa fa-paw"></i></span></li>');
				if($('#wmd-button-row').length !== 0){
					$('#wmd-emoji-button').click(function(){
					    Swal.fire({
  title: '表情面板',
  html: '<button id=\"emoji\" class="swal2-confirm swal2-styled">点击显示表情面板</button><br><div id="emoemo"></div><br>Tips:点击表情后将直接插入到文章中',
  icon: 'info',
showCancelButton: false,
showConfirmButton: false,

});
		$('#emoji').BearsimpleEmoji($('#text'));
		$.fn.insertText = function(text){
		    Swal.close();
			var rs = text+' ';
						emoji(rs);     
			return this;
		}
		
						
					})
				}


				function emoji(tag) {
					var myField;
					if (document.getElementById('text') && document.getElementById('text').type == 'textarea') {
						myField = document.getElementById('text');
					} else {
						return false;
					}
					if (document.selection) {
						myField.focus();
						sel = document.selection.createRange();
						sel.text = tag;
						myField.focus();
					}
					else if (myField.selectionStart || myField.selectionStart == '0') {
						var startPos = myField.selectionStart;
						var endPos = myField.selectionEnd;
						var cursorPos = startPos;
						myField.value = myField.value.substring(0, startPos)
						+ tag
						+ myField.value.substring(endPos, myField.value.length);
						cursorPos += tag.length;
						myField.focus();
						myField.selectionStart = cursorPos;
						myField.selectionEnd = cursorPos;
					} else {
						myField.value += tag;
						myField.focus();
					}
				}
			});
</script>
<script> 
          $(document).ready(function(){
          	$('#wmd-button-row').append('<li class="wmd-button" id="wmd-mermaid-button" title="流程图"><span style="background: none;margin-top:5px;font-size: large;text-align: center;color: #999999;font-family: serif;"><i class="fa fa-random"></i></span></li>');
				if($('#wmd-button-row').length !== 0){
					$('#wmd-mermaid-button').click(function(){
						var rs = '```mermaid\nMermaid内容\n```';
						mermaid(rs);
					})
				}


				function mermaid(tag) {
					var myField;
					if (document.getElementById('text') && document.getElementById('text').type == 'textarea') {
						myField = document.getElementById('text');
					} else {
						return false;
					}
					if (document.selection) {
						myField.focus();
						sel = document.selection.createRange();
						sel.text = tag;
						myField.focus();
					}
					else if (myField.selectionStart || myField.selectionStart == '0') {
						var startPos = myField.selectionStart;
						var endPos = myField.selectionEnd;
						var cursorPos = startPos;
						myField.value = myField.value.substring(0, startPos)
						+ tag
						+ myField.value.substring(endPos, myField.value.length);
						cursorPos += tag.length;
						myField.focus();
						myField.selectionStart = cursorPos;
						myField.selectionEnd = cursorPos;
					} else {
						myField.value += tag;
						myField.focus();
					}
				}
			});
</script>
<script> 
          $(document).ready(function(){
          	$('#wmd-button-row').append('<li class="wmd-button" id="wmd-mindmap-button" title="思维导图"><span style="background: none;margin-top:5px;font-size: large;text-align: center;color: #999999;font-family: serif;"><i class="fa fa-map-signs"></i></span></li>');
				if($('#wmd-button-row').length !== 0){
					$('#wmd-mindmap-button').click(function(){
						var rs = '```mermaid\nmindmap\n思维导图内容\n```';
						mindmap(rs);
					})
				}


				function mindmap(tag) {
					var myField;
					if (document.getElementById('text') && document.getElementById('text').type == 'textarea') {
						myField = document.getElementById('text');
					} else {
						return false;
					}
					if (document.selection) {
						myField.focus();
						sel = document.selection.createRange();
						sel.text = tag;
						myField.focus();
					}
					else if (myField.selectionStart || myField.selectionStart == '0') {
						var startPos = myField.selectionStart;
						var endPos = myField.selectionEnd;
						var cursorPos = startPos;
						myField.value = myField.value.substring(0, startPos)
						+ tag
						+ myField.value.substring(endPos, myField.value.length);
						cursorPos += tag.length;
						myField.focus();
						myField.selectionStart = cursorPos;
						myField.selectionEnd = cursorPos;
					} else {
						myField.value += tag;
						myField.focus();
					}
				}
			});
</script>
<script> 
          $(document).ready(function(){
          	$('#wmd-button-row').append('<li class="wmd-button" id="wmd-dateprogress-button" title="插入倒计时进度条"><span style="background: none;margin-top:5px;font-size: large;text-align: center;color: #999999;font-family: serif;"><i class="fa fa-calendar"></i></span></li>');
				if($('#wmd-button-row').length !== 0){
				    $('#wmd-dateprogress-button').click(function(){
				    Swal.fire({
  title: '选择倒计时进度条颜色',
  input: 'select',
  inputOptions: {
      'blue':'蓝色',
      'green': '绿色',
      'cyan': '青色',
      'yellow': '黄色',
      'red': '红色',
      'orange': '橙色',
      'black': '黑色',
  },
  inputPlaceholder: '选择一款颜色',
  showCancelButton: true,
cancelButtonText: "取消", 
  confirmButtonText: '下一步',
  inputValidator: (valueone) => {
    return new Promise((resolve) => {
      if (valueone !== '') {
      
      Swal.fire({
      title: '第二步 填写倒计时进度条标题',
  input: 'text',
  inputLabel: '倒计时进度条标题[将显示在倒计时进度条上方]',
  inputPlaceholder: '填写倒计时进度条标题',
    showCancelButton: true,
cancelButtonText: "取消", 
  confirmButtonText: '下一步',
  inputValidator: (valuetwo) => {
    return new Promise((resolve) => {
      if (valuetwo !== '') {
             Swal.fire({
      title: '第三步 填写倒计时结束日期',
  input: 'text',
  inputLabel: '按照年-月-日 时:分:秒来填写，如2022-12-12 22:12:12',
  inputPlaceholder: '填写倒计时结束日期',
    showCancelButton: true,
cancelButtonText: "取消", 
  confirmButtonText: '插入',
  inputValidator: (valuethree) => {
    return new Promise((resolve) => {
      if (valuethree !== '') {

                var text = '[bsdate color="'+valueone+'" end="'+valuethree+'"]'+valuetwo+'[/bsdate]';
      dateprogress(text);
    resolve()
     
      }
      else{
       resolve('您必须填写结束日期~')    
      }
    })
  }
})
    resolve()
      }
      else{
       resolve('您必须填写倒计时进度条标题~')    
      }
    })
  }
})
      
      
      
      
      } 
      else{
         resolve('您必须选择一款颜色~') 
      }
    })
  }
})

				})
				}


				function dateprogress(tag) {
					var myField;
					if (document.getElementById('text') && document.getElementById('text').type == 'textarea') {
						myField = document.getElementById('text');
					} else {
						return false;
					}
					if (document.selection) {
						myField.focus();
						sel = document.selection.createRange();
						sel.text = tag;
						myField.focus();
					}
					else if (myField.selectionStart || myField.selectionStart == '0') {
						var startPos = myField.selectionStart;
						var endPos = myField.selectionEnd;
						var cursorPos = startPos;
						myField.value = myField.value.substring(0, startPos)
						+ tag
						+ myField.value.substring(endPos, myField.value.length);
						cursorPos += tag.length;
						myField.focus();
						myField.selectionStart = cursorPos;
						myField.selectionEnd = cursorPos;
					} else {
						myField.value += tag;
						myField.focus();
					}
				}
			});
</script>
<script> 
          $(document).ready(function(){
          	$('#wmd-button-row').append('<li class="wmd-button" id="wmd-copytext-button" title="点击复制内容"><span style="background: none;margin-top:5px;font-size: large;text-align: center;color: #999999;font-family: serif;"><i class="fa fa-copy"></i></span></li>');
				if($('#wmd-button-row').length !== 0){
					$('#wmd-copytext-button').click(function(){
						var rs = '[bscopy text="点击所复制的内容文字"]显示的内容文字[/bscopy]';
						copytext(rs);
					})
				}


				function copytext(tag) {
					var myField;
					if (document.getElementById('text') && document.getElementById('text').type == 'textarea') {
						myField = document.getElementById('text');
					} else {
						return false;
					}
					if (document.selection) {
						myField.focus();
						sel = document.selection.createRange();
						sel.text = tag;
						myField.focus();
					}
					else if (myField.selectionStart || myField.selectionStart == '0') {
						var startPos = myField.selectionStart;
						var endPos = myField.selectionEnd;
						var cursorPos = startPos;
						myField.value = myField.value.substring(0, startPos)
						+ tag
						+ myField.value.substring(endPos, myField.value.length);
						cursorPos += tag.length;
						myField.focus();
						myField.selectionStart = cursorPos;
						myField.selectionEnd = cursorPos;
					} else {
						myField.value += tag;
						myField.focus();
					}
				}
			});
</script>
<script> 
          $(document).ready(function(){
          	$('#wmd-button-row').append('<li class="wmd-button" id="wmd-tag-button" title="文章标签"><span style="background: none;margin-top:5px;font-size: large;text-align: center;color: #999999;font-family: serif;"><i class="fa fa-tag"></i></span></li>');
				if($('#wmd-button-row').length !== 0){
					$('#wmd-tag-button').click(function(){
						var rs = '[tag]要调用的标签名[/tag]';
						tag(rs);
					})
				}


				function tag(tag) {
					var myField;
					if (document.getElementById('text') && document.getElementById('text').type == 'textarea') {
						myField = document.getElementById('text');
					} else {
						return false;
					}
					if (document.selection) {
						myField.focus();
						sel = document.selection.createRange();
						sel.text = tag;
						myField.focus();
					}
					else if (myField.selectionStart || myField.selectionStart == '0') {
						var startPos = myField.selectionStart;
						var endPos = myField.selectionEnd;
						var cursorPos = startPos;
						myField.value = myField.value.substring(0, startPos)
						+ tag
						+ myField.value.substring(endPos, myField.value.length);
						cursorPos += tag.length;
						myField.focus();
						myField.selectionStart = cursorPos;
						myField.selectionEnd = cursorPos;
					} else {
						myField.value += tag;
						myField.focus();
					}
				}
			});
</script>
<script> 
          $(document).ready(function(){
          	$('#wmd-button-row').append('<li class="wmd-button" id="wmd-video-button" title="插入视频"><span style="background: none;margin-top:5px;font-size: large;text-align: center;color: #999999;font-family: serif;"><i class="fa fa-video-camera"></i></span></li>');
				if($('#wmd-button-row').length !== 0){
					$('#wmd-video-button').click(function(){
						var rs = '[bsplayer url="视频文件地址" image="视频封面图地址"]\n';
						video(rs);
					})
				}


				function video(tag) {
					var myField;
					if (document.getElementById('text') && document.getElementById('text').type == 'textarea') {
						myField = document.getElementById('text');
					} else {
						return false;
					}
					if (document.selection) {
						myField.focus();
						sel = document.selection.createRange();
						sel.text = tag;
						myField.focus();
					}
					else if (myField.selectionStart || myField.selectionStart == '0') {
						var startPos = myField.selectionStart;
						var endPos = myField.selectionEnd;
						var cursorPos = startPos;
						myField.value = myField.value.substring(0, startPos)
						+ tag
						+ myField.value.substring(endPos, myField.value.length);
						cursorPos += tag.length;
						myField.focus();
						myField.selectionStart = cursorPos;
						myField.selectionEnd = cursorPos;
					} else {
						myField.value += tag;
						myField.focus();
					}
				}
			});
</script>
<script> 
          $(document).ready(function(){
          	$('#wmd-button-row').append('<li class="wmd-button" id="wmd-mp3-button" title="插入mp3音频文件"><span style="background: none;margin-top:5px;font-size: large;text-align: center;color: #999999;font-family: serif;"><i class="fa fa-file-sound-o"></i></span></li>');
				if($('#wmd-button-row').length !== 0){
					$('#wmd-mp3-button').click(function(){
						var rs = '单首歌曲使用\n[bsmp3 url="音频文件地址" singer="歌手" name="歌曲名" image="歌曲封面图地址"]\n多首歌曲使用\n[bsmp3]\n[mp3 url="音频文件地址" singer="歌手" name="歌曲名" image="歌曲封面图地址"]\n[mp3 url="音频文件地址" singer="歌手" name="歌曲名" image="歌曲封面图地址"]\n[/bsmp3]\n';
						mp3(rs);
					})
				}


				function mp3(tag) {
					var myField;
					if (document.getElementById('text') && document.getElementById('text').type == 'textarea') {
						myField = document.getElementById('text');
					} else {
						return false;
					}
					if (document.selection) {
						myField.focus();
						sel = document.selection.createRange();
						sel.text = tag;
						myField.focus();
					}
					else if (myField.selectionStart || myField.selectionStart == '0') {
						var startPos = myField.selectionStart;
						var endPos = myField.selectionEnd;
						var cursorPos = startPos;
						myField.value = myField.value.substring(0, startPos)
						+ tag
						+ myField.value.substring(endPos, myField.value.length);
						cursorPos += tag.length;
						myField.focus();
						myField.selectionStart = cursorPos;
						myField.selectionEnd = cursorPos;
					} else {
						myField.value += tag;
						myField.focus();
					}
				}
			});
</script>
<script> 
          $(document).ready(function(){
          	$('#wmd-button-row').append('<li class="wmd-button" id="wmd-tabs-button" title="插入标签卡"><span style="background: none;margin-top:5px;font-size: large;text-align: center;color: #999999;font-family: serif;"><i class="fa fa-tablet"></i></span></li>');
				if($('#wmd-button-row').length !== 0){
					$('#wmd-tabs-button').click(function(){
						var rs = '[bstabs]\n[bstab name="标签1" active="true"]第一个标签卡的内容[/bstab]\n[bstab name="标签2"]第二个标签卡的内容[/bstab]\n[/bstabs]\n';
						tabs(rs);
					})
				}


				function tabs(tag) {
					var myField;
					if (document.getElementById('text') && document.getElementById('text').type == 'textarea') {
						myField = document.getElementById('text');
					} else {
						return false;
					}
					if (document.selection) {
						myField.focus();
						sel = document.selection.createRange();
						sel.text = tag;
						myField.focus();
					}
					else if (myField.selectionStart || myField.selectionStart == '0') {
						var startPos = myField.selectionStart;
						var endPos = myField.selectionEnd;
						var cursorPos = startPos;
						myField.value = myField.value.substring(0, startPos)
						+ tag
						+ myField.value.substring(endPos, myField.value.length);
						cursorPos += tag.length;
						myField.focus();
						myField.selectionStart = cursorPos;
						myField.selectionEnd = cursorPos;
					} else {
						myField.value += tag;
						myField.focus();
					}
				}
			});
</script>
<script> 
          $(document).ready(function(){
          	$('#wmd-button-row').append('<li class="wmd-button" id="wmd-textopc-button" title="插入文字显隐"><span style="background: none;margin-top:5px;font-size: large;text-align: center;color: #999999;font-family: serif;"><i class="fa fa-shower"></i></span></li>');
				if($('#wmd-button-row').length !== 0){
					$('#wmd-textopc-button').click(function(){
						var rs = '[bsopc]需要显隐的文字内容[/bsopc]\n';
						textopc(rs);
					})
				}


				function textopc(tag) {
					var myField;
					if (document.getElementById('text') && document.getElementById('text').type == 'textarea') {
						myField = document.getElementById('text');
					} else {
						return false;
					}
					if (document.selection) {
						myField.focus();
						sel = document.selection.createRange();
						sel.text = tag;
						myField.focus();
					}
					else if (myField.selectionStart || myField.selectionStart == '0') {
						var startPos = myField.selectionStart;
						var endPos = myField.selectionEnd;
						var cursorPos = startPos;
						myField.value = myField.value.substring(0, startPos)
						+ tag
						+ myField.value.substring(endPos, myField.value.length);
						cursorPos += tag.length;
						myField.focus();
						myField.selectionStart = cursorPos;
						myField.selectionEnd = cursorPos;
					} else {
						myField.value += tag;
						myField.focus();
					}
				}
			});
</script>
<script> 
          $(document).ready(function(){
          	$('#wmd-button-row').append('<li class="wmd-button" id="wmd-texttip-button" title="插入文字提示"><span style="background: none;margin-top:5px;font-size: large;text-align: center;color: #999999;font-family: serif;"><i class="fa fa-gittip"></i></span></li>');
				if($('#wmd-button-row').length !== 0){
					$('#wmd-texttip-button').click(function(){
						var rs = '[bstip text="悬浮提示文字内容"]显示的文字内容[/bstip]\n';
						texttip(rs);
					})
				}


				function texttip(tag) {
					var myField;
					if (document.getElementById('text') && document.getElementById('text').type == 'textarea') {
						myField = document.getElementById('text');
					} else {
						return false;
					}
					if (document.selection) {
						myField.focus();
						sel = document.selection.createRange();
						sel.text = tag;
						myField.focus();
					}
					else if (myField.selectionStart || myField.selectionStart == '0') {
						var startPos = myField.selectionStart;
						var endPos = myField.selectionEnd;
						var cursorPos = startPos;
						myField.value = myField.value.substring(0, startPos)
						+ tag
						+ myField.value.substring(endPos, myField.value.length);
						cursorPos += tag.length;
						myField.focus();
						myField.selectionStart = cursorPos;
						myField.selectionEnd = cursorPos;
					} else {
						myField.value += tag;
						myField.focus();
					}
				}
			});
</script>
<script> 
          $(document).ready(function(){
          	$('#wmd-button-row').append('<li class="wmd-button" id="wmd-textkbd-button" title="插入键盘文本短代码"><span style="background: none;margin-top:5px;font-size: large;text-align: center;color: #999999;font-family: serif;"><i class="fa fa-keyboard-o"></i></span></li>');
				if($('#wmd-button-row').length !== 0){
					$('#wmd-textkbd-button').click(function(){
						var rs = '[bskbd style="颜色，dark、gray或者white"]键盘文本内容，如CTRL[/bskbd]\n';
						textkbd(rs);
					})
				}


				function textkbd(tag) {
					var myField;
					if (document.getElementById('text') && document.getElementById('text').type == 'textarea') {
						myField = document.getElementById('text');
					} else {
						return false;
					}
					if (document.selection) {
						myField.focus();
						sel = document.selection.createRange();
						sel.text = tag;
						myField.focus();
					}
					else if (myField.selectionStart || myField.selectionStart == '0') {
						var startPos = myField.selectionStart;
						var endPos = myField.selectionEnd;
						var cursorPos = startPos;
						myField.value = myField.value.substring(0, startPos)
						+ tag
						+ myField.value.substring(endPos, myField.value.length);
						cursorPos += tag.length;
						myField.focus();
						myField.selectionStart = cursorPos;
						myField.selectionEnd = cursorPos;
					} else {
						myField.value += tag;
						myField.focus();
					}
				}
			});
</script>

<?php
}
}

/**
     * 插入编辑器
     */
     public static function Editor()
    {
        $options = Helper::options();
    
        $dir = $options->pluginUrl.'/BsCore/assets/vendors/';
        $cssUrl = $dir.'easymde/easymde.min.css';
        $jsUrl = $dir.'easymde/easymde.min.js';
        
        
	if (strpos( $_SERVER['REQUEST_URI'],'write-post.php') !== false)
{
    $url = 'post';
\Typecho\Widget::widget('Widget_Contents_Post_Edit')->to($post);
}
if (strpos( $_SERVER['REQUEST_URI'],'write-page.php') !== false)
{
    $url = 'page';
\Typecho\Widget::widget('Widget_Contents_Page_Edit')->to($page);
}

if (isset($post) || isset($page)) {
    $cid = isset($post) ? $post->cid : $page->cid;
    
    if ($cid) {
        \Typecho\Widget::widget('Widget_Contents_Attachment_Related', 'parentId=' . $cid)->to($attachment);
    } else {
        \Typecho\Widget::widget('Widget_Contents_Attachment_Unattached')->to($attachment);
    }
}

        ?>
      <?php $fontawesomeUrl = $dir.'fontawesome/font-awesome.min.css?v=1';?>
<link rel="stylesheet" href=" <?php echo $fontawesomeUrl; ?>" />
        <link rel="stylesheet" href="<?php echo $cssUrl; ?>?v=1" />
<link rel="stylesheet" href="<?php echo $options->pluginUrl;?>/BsCore/assets/vendors/sweetalert2/sweetalert2.min.css" />
        <script type="text/javascript" src="<?php echo $jsUrl; ?>?v=1"></script>
<link href="<?php echo $dir;?>sweetalert2/bootstrap-4.css" rel="stylesheet">
        <script src="<?php echo $options->pluginUrl;?>/BsCore/assets/vendors/sweetalert2/sweetalert2.min.js"></script>
<link rel="stylesheet" href="<?php echo $options->pluginUrl;?>/BsCore/assets/vendors/bootstrap-colorpicker/bootstrap-colorpicker.min.css" />
<script type="text/javascript" src="<?php echo $options->pluginUrl;?>/BsCore/assets/vendors/bootstrap-colorpicker/bootstrap-colorpicker.min.js"></script>
	<link href="<?php echo $options->pluginUrl;?>/BsCore/assets/vendors/toastr.js/toastr.min.css" rel="stylesheet">
	<script src="<?php echo $options->pluginUrl;?>/BsCore/assets/vendors/toastr.js/toastr.min.js"></script>
<script src="<?php echo $options->pluginUrl;?>/BsCore/assets/vendors/turndown/turndown.min.js"></script>
<style>
    .editor-statusbar .lines:before{content:'行数: '}
    .editor-statusbar .words:before{content:'字数: '}
    .editor-statusbar .lines:after{content:' 行'}
    .editor-statusbar .words:after{content:' 个字'}
    .editor-toolbar{
        background:#2d333b;
    }
   .fullscreen{
       background:#2d333b;
   }
    .editor-toolbar i,
    .editor-toolbar button::after{
        color:#adbac7;
    }
    .editor-toolbar button:hover{
        background:#444c56;
    }
    .editor-toolbar button.active{
                background:#444c56;
                
    }
    .cm-s-easymde{
       background:#22272e;
        color:#adbac7;
    }

.CodeMirror {
  --selection-bg: #316dca;
  --selection-active-bg: #3d8ef7;
  --selection-text: white;
}

.CodeMirror-selected {
  background: var(--selection-bg) !important;
}

.CodeMirror-selectedtext {
  color: var(--selection-text) !important;
}

.CodeMirror-focused .CodeMirror-selected {
  background: var(--selection-active-bg) !important;
}

.CodeMirror-selection-secondary {
  background: #ffd33d !important;
}

.cm-s-easymde .CodeMirror-selected {
  background: #80cbc4 !important;
}

.CodeMirror-cursor {
  border-left: 2px solid #fff !important; 
}

.CodeMirror-focused .CodeMirror-cursor {
  border-left-color: #fff !important;
}


</style>

<link href="<?php echo Helper::options()->pluginUrl ?>/BsCore/modules/bs-emoji/bs-emoji.css?v=1" rel="stylesheet" type="text/css">
<script src="<?php echo Helper::options()->pluginUrl ?>/BsCore/modules/bs-emoji/bs-emoji.js?v=2"></script>
        <script>
        

            $(document).ready(function() {
                     
                $.fn.autoHeight = function () {
        function autoHeight(elem) {
            elem.style.height = 'auto';
            elem.scrollTop = 0;
            elem.style.height = elem.scrollHeight + 'px';
        }

        this.each(function () {
            autoHeight(this);
            $(this).on('keyup', function () {
                autoHeight(this);
            });
        });
    }

  $("#excerpt-0-18").autoHeight();
                $('#excerpt-0-18').css({'width':'100%'});
                new EasyMDE({
        placeholder:'您现在可以开始撰写文章啦~~~',
        autoDownloadFontAwesome: false,
        spellChecker:false,
        nativeSpellcheck:false,
        sideBySideFullscreen:false,
        maxHeight:"400px",
        showIcons: ['strikethrough', 'code', 'table', 'redo', 'heading', 'undo', 'heading-bigger', 'heading-smaller', 'heading-1', 'heading-2', 'heading-3', 'clean-block', 'horizontal-rule','side-by-side'],
        <?php if(bsOptions::getInstance()::get_option( 'bearsimple' )['EditorsAutosave'][0] == '1'):?>
        autosave: {
            enabled: true,
            delay: 1000,
            uniqueId: 'text',
            timeFormat:{locale:'zh-CN'},
            text:'自动保存时间：'
        },
        <?php endif; ?>
        toolbar: [ 
<?php if(get_option('bearsimple')['Cache'] == true && (get_option('bearsimple')['Cache_choose'] == 'memcached'||get_option('bearsimple')['Cache_choose'] == 'redis'||get_option('bearsimple')['Cache_choose'] == 'apcu'||get_option('bearsimple')['Cache_choose'] == 'sqlite'||get_option('bearsimple')['Cache_choose'] == 'memory'||get_option('bearsimple')['Cache_choose'] == 'flysystem') &&  get_option('bearsimple')['enable_markcache'] == true): ?>
{ name: "nocache", className: "fa fa-ban", action:nocache,title: "插入不缓存标签"},
<?php endif; ?>
{ name: "bold", action:EasyMDE.toggleBold, className: "fa fa-bold",title: "加粗"}, 
{ name: "italic", action:EasyMDE.toggleItalic, className: "fa fa-italic",title: "字体倾斜"}, 
{ name: "strikethrough", action:EasyMDE.toggleStrikethrough, className: "fa fa-strikethrough",title: "删除线"},
{ name: "heading-smaller", action:EasyMDE.toggleHeadingSmaller, className: "fa fa-font",title: "字体缩小"}, 
{ name: "heading-bigger", action:EasyMDE.toggleHeadingBigger, className: "fa fa-font",title: "字体放大"},
{ name: "heading-1", action:EasyMDE.toggleHeading1, className: "fa fa-header header-1",title: "H1字号"},
{ name: "heading-2", action:EasyMDE.toggleHeading2, className: "fa fa-header header-2",title: "H2字号"},
{ name: "heading-3", action:EasyMDE.toggleHeading3, className: "fa fa-header header-3",title: "H3字号"}, 
{ name: "code", action:EasyMDE.toggleCodeBlock, className: "fa fa-code",title: "代码"}, 
{ name: "quote", action:EasyMDE.toggleBlockquote, className: "fa fa-quote-left",title: "引用"}, 
{ name: "unordered-list", action:EasyMDE.toggleUnorderedList, className: "fa fa-list-ul",title: "无序列表"}, 
{ name: "ordered-list", action:EasyMDE.toggleOrderedList, className: "fa fa-list-ol",title: "有序列表"}, 
{ name: "clean-block", action:EasyMDE.cleanBlock, className: "fa fa-eraser",title: "擦除代码块"}, 
{ name: "link", action:EasyMDE.drawLink, className: "fa fa-link",title: "插入链接"}, 
{ name: "image", action:EasyMDE.drawImage, className: "fa fa-picture-o",title: "插入图片"}, 
{ name: "table", action:EasyMDE.drawTable, className: "fa fa-table",title: "插入表格"}, 
{ name: "color", className: "fa fa-tint", action:colorpc,title: "字体颜色选择器"},
{ name: "button", className: "fa fa-square-o", action:button,title: "按钮"},
{ name: "message", className: "fa fa-comment-o", action:message,title: "提示框"},
{ name: "iframe", className: "fa fa-window-restore", action:iframe,title: "插入iframe"},
{ name: "bframe", className: "fa fa-external-link", action:bframe,title: "插入bframe"},
{ name: "audio", className: "fa fa-volume-up", action:audio,title: "插入音频"},
{ name: "musiclist", className: "fa fa-music", action:musiclist,title: "插入音乐歌单"},
{ name: "hide", className: "fa fa-eye-slash", action:hide,title: "回复或登录后可见"},
{ name: "login", className: "fa fa-user-secret", action:login,title: "登录后可见"},
{ name: "inserts", className: "fa fa-paperclip", action:attachInsertEvent,title: "插入所有附件"},
{ name: "insertper", className: "fa fa-file-o", action:insertper,title: "插入单个附件"},
{ name: "copyupload", className: "fa fa-clone", action:copyupload,title: "复制粘贴上传"},
{ name: "todolist1", className: "fa fa-check-square-o", action:todolist1,title: "已完成列表"},
{ name: "todolist2", className: "fa fa-square-o", action:todolist2,title: "未完成列表"},
{ name: "star", className: "fa fa-star-half-o", action:star,title: "评星"},
{ name: "accord1", className: "fa fa-bars", action:accord1,title: "线性手风琴"},
{ name: "accord2", className: "fa fa-list-alt", action:accord2,title: "普通手风琴"},
{ name: "quotepost", className: "fa fa-quote-right", action:quotepost,title: "引用文章"},
{ name: "postmark", className: "fa fa-bookmark", action:postmark,title: "标注文字"},
{ name: "postruby", className: "fa fa-font", action:postruby,title: "文字带拼音"},
{ name: "everydaypaper", className: "fa fa-newspaper-o", action:everydaypaper,title: "每日60s早报"},
{ name: "emoji", className: "fa fa-smile-o", action:emoji,title: "表情"},
{ name: "hr", className: "fa fa-minus", action:hr,title: "分割线"},
{ name: "mermaid", className: "fa fa-share-alt", action:mermaid,title: "流程图"},
{ name: "mindmap", className: "fa fa-sitemap", action:mindmap,title: "思维导图"},
{ name: "gallery", className: "fa fa-th", action:gallery,title: "相册"},
{ name: "time", className: "fa fa-clock-o", action:time,title: "时间计划"},
{ name: "githubcard", className: "fa fa-github", action:githubcard,title: "Github仓库"},
{ name: "autosaves", className: "fa fa-floppy-o", action:autosave,title: "自动保存"},
{ name: "progress", className: "fa fa-spinner", action:progress,title: "进度条"},
{ name: "dateprogress", className: "fa fa-hourglass-half", action:dateprogress,title: "倒计时进度条"},
{ name: "copytext", className: "fa fa-clipboard", action:copytext,title: "点击复制内容"},
{ name: "tag", className: "fa fa-tags", action:tag,title: "文章标签"},
{ name: "video", className: "fa fa-film", action:video,title: "插入视频"},
{ name: "mp3", className: "fa fa-file-audio-o", action:mp3,title: "插入mp3音频文件"},
{ name: "tabs", className: "fa fa-folder-o", action:tabs,title: "插入标签卡"},
{ name: "textopc", className: "fa fa-lightbulb-o", action:textopc,title: "插入文字显隐"},
{ name: "texttip", className: "fa fa-info-circle", action:texttip,title: "插入文字提示"},
{ name: "textkbd", className: "fa fa-keyboard-o", action:textkbd,title: "插入键盘文本短代码"}
],
        promptURLs:true,
        promptTexts:{
            image:"请填写图片直链",
            link:"请填写网址链接",
        },
        element: document.getElementById('text'),
    });
            });
       function dateprogress(editor){
           Swal.fire({
  title: '选择倒计时进度条颜色',
  input: 'select',
  inputOptions: {
      'blue':'蓝色',
      'green': '绿色',
      'cyan': '青色',
      'yellow': '黄色',
      'red': '红色',
      'orange': '橙色',
      'black': '黑色',
  },
  inputPlaceholder: '选择一款颜色',
  showCancelButton: true,
cancelButtonText: "取消", 
  confirmButtonText: '下一步',
  inputValidator: (valueone) => {
    return new Promise((resolve) => {
      if (valueone !== '') {
      
      Swal.fire({
      title: '第二步 填写倒计时进度条标题',
  input: 'text',
  inputLabel: '倒计时进度条标题[将显示在倒计时进度条上方]',
  inputPlaceholder: '填写倒计时进度条标题',
    showCancelButton: true,
cancelButtonText: "取消", 
  confirmButtonText: '下一步',
  inputValidator: (valuetwo) => {
    return new Promise((resolve) => {
      if (valuetwo !== '') {
             Swal.fire({
      title: '第三步 填写倒计时结束日期',
  input: 'text',
  inputLabel: '按照年-月-日 时:分:秒来填写，如2022-12-12 22:12:12',
  inputPlaceholder: '填写倒计时结束日期',
    showCancelButton: true,
cancelButtonText: "取消", 
  confirmButtonText: '插入',
  inputValidator: (valuethree) => {
    return new Promise((resolve) => {
      if (valuethree !== '') {

                var text = '[bsdate color="'+valueone+'" end="'+valuethree+'"]'+valuetwo+'[/bsdate]';
   editor.codemirror.doc.replaceSelection(text); 
    resolve()
     
      }
      else{
       resolve('您必须填写结束日期~')    
      }
    })
  }
})
    resolve()
      }
      else{
       resolve('您必须填写倒计时进度条标题~')    
      }
    })
  }
})
      
      
      
      
      } 
      else{
         resolve('您必须选择一款颜色~') 
      }
    })
  }
})



    
       }
      
       function copyupload(editor){
 $(document).on('paste',function(event){
                    let defaultValue = editor.codemirror.doc.getValue();
                        event = event.originalEvent;
                        var cbd = event.clipboardData;
                        var ua = window.navigator.userAgent;
                        var uploadURL = '<?php Helper::security()->index('/action/upload?cid=CID'); ?>';
                        
                        if (!(event.clipboardData && event.clipboardData.items)) {
                            return;
                        }

                        if (cbd.items && cbd.items.length === 2 && cbd.items[0].kind === "string" && cbd.items[1].kind === "file" &&
                            cbd.types && cbd.types.length === 2 && cbd.types[0] === "text/plain" && cbd.types[1] === "Files" &&
                            ua.match(/Macintosh/i) && Number(ua.match(/Chrome\/(\d{2})/i)[1]) < 49){
                            return;
                        }

                        var itemLength = cbd.items.length;
       
                        if (itemLength == 0) {
                            return;
                        }

                        if (itemLength == 1 && cbd.items[0].kind == 'string') {
                            return;
                        }

                        if ((itemLength == 1 && cbd.items[0].kind == 'file')
                                || itemLength > 1
                            ) {
                            for (var i = 0; i < cbd.items.length; i++) {
                                var item = cbd.items[i];

                                if(item.kind == "file") {
                                    var blob = item.getAsFile();
                                    if (blob.size === 0) {
                                        return;
                                    }
                                    var ext = blob.name.substring(blob.name.lastIndexOf(".")+1);
                                    var formData = new FormData();
                                    formData.append('blob', blob, Math.floor(new Date().getTime() / 1000) + '.' + ext);
                                    var uploadFailText = '文件上传失败(' + blob.name + ')'
                                    toastr.success('检测到正在复制粘贴图片，熊宝正在努力上传中~~');
                                    $.ajax({
                                        method: 'post',
                                        url: uploadURL.replace('CID', $('input[name="cid"]').val()),
                                        data: formData,
                                        contentType: false,
                                        processData: false,
                                        success: function(data) {
                                
                                            if (data[0]) {
                                       
                                                var filehz = data[0].substring(data[0].lastIndexOf(".")+1);
                                                switch(filehz){
                                                 case 'gif':
                                                 editor.codemirror.doc.replaceSelection('![](' + data[0] + ')\n');
                                                 break;
                                                 case 'jpg':
                                                 editor.codemirror.doc.replaceSelection('![](' + data[0] + ')\n');
                                                 break;
                                                 case 'jpeg':
                                                 editor.codemirror.doc.replaceSelection('![](' + data[0] + ')\n');
                                                 break;
                                                 case 'gif':
                                                 editor.codemirror.doc.replaceSelection('![](' + data[0] + ')\n');
                                                 break;
                                                 case 'png':
                                                 editor.codemirror.doc.replaceSelection('![](' + data[0] + ')\n');
                                                 break;
                                                 case 'tiff':
                                                 editor.codemirror.doc.replaceSelection('![](' + data[0] + ')\n');
                                                 break;
                                                 case 'bmp':
                                                 editor.codemirror.doc.replaceSelection('![](' + data[0] + ')\n');
                                                 break;
                                                 case 'svg':
                                                 editor.codemirror.doc.replaceSelection('![](' + data[0] + ')\n');
                                                 break;
                                                 case 'ico':
                                                 editor.codemirror.doc.replaceSelection('![](' + data[0] + ')\n');
                                                 break;
                                                 default:
                                                 editor.codemirror.doc.replaceSelection('['+data[1]['cid']+']['+data[1]['cid']+']\n['+data[1]['cid']+']: ' + data[0]+'\n');
                                                }
                                            
                                            } else {
                                               editor.codemirror.doc.replaceSelection(uploadFailText);
                                            }
                                         
                                        },
                                        error: function() {
                                            editor.codemirror.doc.replaceSelection(uploadFailText);
                                        }
                                    });
                                }
                            }

                        }

			});
       }
       
              setTimeout(function() {
                var button = $(".copyupload");
                button.click();
            }, 2000);
       
       
       
            
            function star(editor){
               Swal.fire({
  title: '第一步 选择评星样式',
  input: 'select',
  inputOptions: {
      'star': '星星',
      'heart': '红心',
  },
  inputPlaceholder: '选择一款样式',
  showCancelButton: true,
cancelButtonText: "取消", 
  confirmButtonText: '下一步',
  inputValidator: (valueone) => {
    return new Promise((resolve) => {
      if (valueone !== '') {

                Swal.fire({
  title: '第二步 输入评星数目',
  input: 'number',
inputLabel: '输入评星数目，最高五颗星',
inputAttributes:{
  min:1,
  max:5,
  
},
  inputPlaceholder: '输入评星数目',
  showCancelButton: true,
cancelButtonText: "取消", 
  confirmButtonText: '插入',
  inputValidator: (valuetwo) => {
    return new Promise((resolve) => {
      if (valuetwo !== '') {
var text = '[bseva type="'+valueone+'"]'+valuetwo+'[/bseva]';
      editor.codemirror.doc.replaceSelection(text);
        
        resolve() 
      } 
      else{
         resolve('您必须输入评星数目~~') 
      }
    })
  }
})

        
    
        resolve() 
      } 
      else{
         resolve('您必须选择一款评星样式~~') 
      }
    })
  }
}) 
            }
            function autosave(editor){
          <?php if(bsOptions::getInstance()::get_option( 'bearsimple' )['EditorsAutosave'][0] == '11'||bsOptions::getInstance()::get_option( 'bearsimple' )['EditorsAutosave'][1] == '11'):?>
            //$("textarea:eq(1)").bind("input propertychange", autosaves); //废弃方法
        setInterval(function(){
         var submitted = false, form = $('form[name=write_post],form[name=write_page]').submit(function () {
        submitted = true;
    }), formAction = form.attr('action'),
        idInput = $('input[name=cid]'),
        cid = idInput.val(),
        draft = $('input[name=draft]'),
        draftId = draft.length > 0 ? draft.val() : 0,
        btnSave = $('#btn-save').removeAttr('name').removeAttr('value'),
        btnSubmit = $('#btn-submit').removeAttr('name').removeAttr('value'),
        btnPreview = $('#btn-preview'),
        //doAction = $('<input type="hidden" name="do" value="publish" />').appendTo(form),
        locked = false,
        changed = false,
        autoSave = $('<span id="auto-save-message" class="left"></span>').prependTo('.submit'),
        lastSaveTime = null;
        var data = new FormData(form.get(0));
        data.set("text",editor.value());
        var localcid = "<?php echo $cid;?>";
if(localcid == '' && localStorage.getItem('autosavecid') && localStorage.getItem('autosavecid') !== ''){
data.set("cid",localStorage.getItem('autosavecid'));
}
else{
    data.set("cid",localcid);
}
     
        function callback(o) {
toastr.success('已自动保存<br>保存时间:'+o.time);   
localStorage.setItem('autosavecid',o.cid);
$('input[name=cid]').val(o.cid);
        }
                data.append('do', 'save');

            
  $.ajax({
                url: formAction,
                processData: false,
                contentType: false,
                type: 'POST',
                data: data,
                success: callback
            });    
   },15000); 
   <?php endif; ?>
}
            
                setTimeout(function() {
                var buttons = $(".autosaves");
                buttons.click();
            }, 2000);
            

            setTimeout(function() {
                var button = $(".insertper");
                button.click();
            }, 2000);
        function insertper (editor) {
        
  $('#file-list').on('mousedown', '.insert', function(){
      var s=$(this).parents("li").data('url');
   if(s.match(/\.(jpg|jepg|png|ico|bmp|gif|tiff|webp)/i)){
        var fileurl="!["+$(this).text()+"]("+$(this).parents("li").data('url')+")\n";
   }
   else{
       var fileurl="["+$(this).text()+"]("+$(this).parents("li").data('url')+")\n";
   }
                 editor.codemirror.doc.replaceSelection(fileurl); 
});
    }

             function attachInsertEvent (editor) {
                 function getData() {
                    $.ajax({
                        type: "GET",
                        url: "<?php getAttachFile(); ?>",
                        data: {
                            "cid": "<?php echo $cid; ?>",
                             "url": "<?php echo $url; ?>",
                        },
                        dateType: "json",
                        success: function(json) {
                            json = JSON.parse(json);
                            content(json.list);
                        },
                        complete: function() {
                        },
                        error: function() {
                            alert("数据获取错误");
                        }
                    });
                }
                getData();
                function content(list) {
                    var filename = " ";
                    var fileurl = " ";
                    for(var i in list) {
                        if(list[i]['type'] == 'img'){
                        fileurl += '![' + list[i]['title'] + ']('+ list[i]['url'] +')\n'
}
                        if(list[i]['type'] == 'other'){
                        fileurl += '[' + list[i]['title'] + ']('+ list[i]['url'] +')\n'
}
}
editor.codemirror.doc.replaceSelection(fileurl);
}
    }
    function iframe(editor){
    editor.codemirror.doc.replaceSelection('{bs-iframe}iframe地址，可为video等{/bs-iframe}'); 
            }
            function tag(editor){
    editor.codemirror.doc.replaceSelection('[tag]要调用的标签名[/tag]'); 
            }
            function everydaypaper(editor){
    editor.codemirror.doc.replaceSelection('[bspaper image="true"]'); 
            }
            function mermaid(editor){
    editor.codemirror.doc.replaceSelection('```mermaid\nMermaid内容\n```'); 
            }
             function video(editor){
    editor.codemirror.doc.replaceSelection('[bsplayer url="视频文件地址" image="视频封面图地址"]'); 
            }
            function mp3(editor){
    editor.codemirror.doc.replaceSelection('单个音频使用\n[bsmp3 url="音频文件地址" singer="歌手" name="歌曲名" image="歌曲封面图地址"]\n多个音频使用\n[bsmp3]\n[mp3 url="音频文件地址" singer="歌手" name="歌曲名" image="歌曲封面图地址"]\n[mp3 url="音频文件地址" singer="歌手" name="歌曲名" image="歌曲封面图地址"]\n[/bsmp3]\n'); 
            }
            function tabs(editor){
    editor.codemirror.doc.replaceSelection('[bstabs]\n[bstab name="标签1" active="true"]第一个标签卡的内容[/bstab]\n[bstab name="标签2"]第二个标签卡的内容[/bstab]\n[/bstabs]\n'); 
            }
            function mindmap(editor){
    editor.codemirror.doc.replaceSelection('```mermaid\nmindmap\n思维导图内容\n```'); 
            }
            function textopc(editor){
    editor.codemirror.doc.replaceSelection('[bsopc]需要显隐的文字内容[/bsopc]\n'); 
            }
            function texttip(editor){
    editor.codemirror.doc.replaceSelection('[bstip text="悬浮提示文字内容"]显示的文字内容[/bstip]\n'); 
            }
            function textkbd(editor){
    editor.codemirror.doc.replaceSelection('[bskbd style="颜色，dark、gray或者white"]键盘文本内容，如CTRL[/bskbd]\n'); 
            }
            function githubcard(editor){
    editor.codemirror.doc.replaceSelection('[bsgit user="Github仓库拥有者，如whitebearcode"]Github项目名，如typecho-bearsimple[/bsgit]'); 
            }
            
                function hr(editor){
    editor.codemirror.doc.replaceSelection('----------'); 
            }
            function copytext(editor){
    editor.codemirror.doc.replaceSelection('[bscopy text="点击所复制的内容文字"]显示的内容文字[/bscopy]'); 
            }
            function musiclist(editor){
    editor.codemirror.doc.replaceSelection('[bsmusic]网易云音乐歌单ID[/bsmusic]'); 
            }
            function hide(editor){
    editor.codemirror.doc.replaceSelection('[bshide]隐藏内容[/bshide]'); 
            }
            function gallery(editor){
    editor.codemirror.doc.replaceSelection('[bsgallery title="相册名"]\n[bsimg title="图片标题1"]图片地址1[/bsimg]\n[bsimg title="图片标题2"]图片地址2[/bsimg]\n[bsimg title="图片标题3"]图片地址3[/bsimg]\n[/bsgallery]'); 
            }
            function time(editor){
    editor.codemirror.doc.replaceSelection('[bstimes title="时间计划名，必填!"]\n[bstime time="时间"]计划内容[/bstime]\n[bstime time="时间2"]计划内容2[/bstime]\n[bstime time="时间3"]计划内容3[/bstime]\n[/bstimes]'); 
            }
            function login(editor){
    editor.codemirror.doc.replaceSelection('[bslogin]隐藏内容[/bslogin]'); 
            }
            function nocache(editor){
    editor.codemirror.doc.replaceSelection("\r\n<!--no-cache-->\r\n"); 
            }
            function audio(editor){
    editor.codemirror.doc.replaceSelection('[bsaudio]音频地址[/bsaudio]'); 
            }
            function accord1(editor){
    editor.codemirror.doc.replaceSelection('{bs-accord style=line title=线性手风琴标题}线性手风琴内容{/bs-accord}'); 
            }
            function accord2(editor){
    editor.codemirror.doc.replaceSelection('{bs-accord style=common title=普通手风琴标题}普通手风琴内容{/bs-accord}'); 
            }
            function quotepost(editor){
    editor.codemirror.doc.replaceSelection('[bspost cid="文章CID"]'); 
            }
            function postmark(editor){
    editor.codemirror.doc.replaceSelection('[bsmark]要标注的内容[/bsmark]'); 
            }
            function postruby(editor){
    editor.codemirror.doc.replaceSelection('[bsruby]要带拼音的汉字[/bsruby]'); 
            }
             function todolist1(editor){
    editor.codemirror.doc.replaceSelection('[todo-t]Todolist已完成的内容[/todo-t]'); 
            }
             function todolist2(editor){
    editor.codemirror.doc.replaceSelection('[todo-f]Todolist待完成的内容[/todo-f]'); 
            }
             function bframe(editor){
    editor.codemirror.doc.replaceSelection('[bsfra image="占位封面图直链" url="页面地址"]'); 
            }


function button(editor){
         Swal.fire({
  title: '第一步 选择按钮样式',
  input: 'select',
  inputOptions: {
      'common': '普通按钮',
      'basic': '光圈按钮',
      'animated': '动画按钮',
  },
  inputPlaceholder: '选择一款按钮',
  showCancelButton: true,
cancelButtonText: "取消", 
  confirmButtonText: '下一步',
  inputValidator: (valueone) => {
    return new Promise((resolve) => {
      if (valueone !== '') {

                Swal.fire({
  title: '第二步 选择按钮颜色',
  input: 'select',
  inputOptions: {
      'standard': '淡色',
      'primary': '蓝色',
      'secondary': '黑色',
      'red': '红色',
      'orange': '橙色',
      'yellow': '黄色',
      'olive': '淡绿色',
      'green': '深绿色',
      'teal': '青色',
      'violet': '紫罗兰色',
      'purple': '基佬紫色',
      'pink': '粉色',
      'brown': '土黄色',
      'grey': '灰色',
  },
  inputPlaceholder: '选择一款颜色',
  showCancelButton: true,
cancelButtonText: "取消", 
  confirmButtonText: '下一步',
  inputValidator: (valuetwo) => {
    return new Promise((resolve) => {
      if (valuetwo !== '') {
        
        

  Swal.fire({
      title: '第三步 填写按钮点击链接',
  input: 'url',
  inputLabel: '按钮点击跳转链接',
  inputPlaceholder: '填写按钮点击跳转链接，需带http(s)://',
    showCancelButton: true,
cancelButtonText: "取消", 
  confirmButtonText: '下一步',
  inputValidator: (valuethree) => {
    return new Promise((resolve) => {
      if (valuethree !== '') {
        
        
        Swal.fire({
  title: '第四步 填写按钮名称',
  input: 'text',
  inputLabel: '按钮名称(若为动画按钮，两个名称请使用|隔开)',
  inputPlaceholder: '请填写按钮名称',
  showCancelButton: true,
  cancelButtonText: "取消", 
  confirmButtonText: '下一步',
  inputValidator: (valuefour) => {
    if (!valuefour) {
      return '您必须填写按钮名称'
    }
    else{
        
        
        
        Swal.fire({
  title: '第五步 填写按钮图标名',
  input: 'text',
  inputLabel: '按钮图标名(若不需要图标可留空)',
  'html':'<a href="https://bs-icon.typecho.ru/" target="_blank">图标获取请戳这里</a>',
  inputPlaceholder: '请填写按钮图标名',
  showCancelButton: true,
  cancelButtonText: "取消", 
  confirmButtonText: '插入',
  inputValidator: (valuefive) => {
      if(!valuefive){
          var icon = '';
      }
      else{
          var icon = ' icon="'+valuefive+'"';
      }
      var text = '[bsbtn type="'+valueone+'" color="'+valuetwo+'" url="'+valuethree+'"'+icon+']'+valuefour+'[/bsbtn]';
      editor.codemirror.doc.replaceSelection(text);
    resolve()
  }
})
        
        
        
        resolve()
    }
  }
})
        
        
        
        resolve() 
      } 
      else{
         resolve('您必须填写跳转链接~~') 
      }
    })
  }
})

        
        
        
        
        
        resolve() 
      } 
      else{
         resolve('您必须选择一款颜色~~') 
      }
    })
  }
})

        
        
        
        
        
        
        
        
        resolve() 
      } 
      else{
         resolve('您必须选择一款按钮~~') 
      }
    })
  }
})

      }
      
      function progress(editor){
         Swal.fire({
  title: '选择进度条颜色',
  input: 'select',
  inputOptions: {
       'normal':'蓝色',
      'secondary': '黑色',
      'default': '灰色',
      'warning': '黄色',
      'success': '绿色',
      'info': '天蓝色',
      'warning': '黄色',
      'danger': '红色'
  },
  inputPlaceholder: '选择一款颜色',
  showCancelButton: true,
cancelButtonText: "取消", 
  confirmButtonText: '下一步',
  inputValidator: (valueone) => {
    return new Promise((resolve) => {
      if (valueone !== '') {
      
      Swal.fire({
      title: '第二步 填写进度条标题',
  input: 'text',
  inputLabel: '进度条标题[将显示在进度条上方]',
  inputPlaceholder: '填写进度条标题',
    showCancelButton: true,
cancelButtonText: "取消", 
  confirmButtonText: '下一步',
  inputValidator: (valuetwo) => {
    return new Promise((resolve) => {
      if (valuetwo !== '') {
             Swal.fire({
      title: '第三步 填写进度条数值',
  input: 'text',
  inputLabel: '进度条数值[如40%就填写40]',
  inputPlaceholder: '填写进度条数值',
    showCancelButton: true,
cancelButtonText: "取消", 
  confirmButtonText: '插入',
  inputValidator: (valuethree) => {
    return new Promise((resolve) => {
      if (valuethree !== '') {
                var text = '[bsprog color="'+valueone+'" number="'+valuethree+'"]'+valuetwo+'[/bsprog]';
      editor.codemirror.doc.replaceSelection(text);
    resolve()
      }
      else{
       resolve('您必须填写进度条数值~')    
      }
    })
  }
})
    resolve()
      }
      else{
       resolve('您必须填写进度条标题~')    
      }
    })
  }
})
      
      
      
      
      } 
      else{
         resolve('您必须选择一款颜色~') 
      }
    })
  }
})

      }
      
      function message(editor){
         Swal.fire({
  title: '第一步 选择提示框样式',
  input: 'select',
  inputOptions: {
      'common': '普通提示框',
      'basic': '阴影提示框',
      'commonclose': '普通可关闭提示框',
      'basicclose':'阴影可关闭提示框',
  },
  inputPlaceholder: '选择一款提示框',
  showCancelButton: true,
cancelButtonText: "取消", 
  confirmButtonText: '下一步',
  inputValidator: (valueone) => {
    return new Promise((resolve) => {
      if (valueone !== '') {

                Swal.fire({
  title: '第二步 选择提示框颜色',
  input: 'select',
  inputOptions: {
      'standard': '淡色',
      'info':'淡青色',
      'primary': '蓝色',
      'secondary': '黑色',
      'red': '红色',
      'orange': '橙色',
      'yellow': '黄色',
      'olive': '淡绿色',
      'green': '深绿色',
      'teal': '青色',
      'violet': '紫罗兰色',
      'purple': '基佬紫色',
      'pink': '粉色',
      'brown': '土黄色',
      'grey': '灰色',
  },
  inputPlaceholder: '选择一款颜色',
  showCancelButton: true,
cancelButtonText: "取消", 
  confirmButtonText: '下一步',
  inputValidator: (valuetwo) => {
    return new Promise((resolve) => {
      if (valuetwo !== '') {
        
        

  Swal.fire({
      title: '第三步 填写提示框标题',
  input: 'text',
  inputLabel: '提示框标题',
  inputPlaceholder: '填写提示框标题',
    showCancelButton: true,
cancelButtonText: "取消", 
  confirmButtonText: '下一步',
  inputValidator: (valuethree) => {
    return new Promise((resolve) => {
      if (valuethree !== '') {
        
        
        Swal.fire({
  title: '第四步 填写提示框内容',
  input: 'textarea',
  inputLabel: '提示框内容',
  inputPlaceholder: '请填写提示框内容',
  showCancelButton: true,
  cancelButtonText: "取消", 
  confirmButtonText: '下一步',
  inputValidator: (valuefour) => {
    if (!valuefour) {
      return '您必须填写提示框内容'
    }
    else{
        
        
        
        Swal.fire({
  title: '第五步 填写提示框图标名',
  input: 'text',
  inputLabel: '提示框图标名(若不需要图标可留空)',
  'html':'<a href="https://bs-icon.typecho.ru/" target="_blank">图标获取请戳这里</a>',
  inputPlaceholder: '请填写提示框图标名',
  showCancelButton: true,
  cancelButtonText: "取消", 
  confirmButtonText: '插入',
  inputValidator: (valuefive) => {
      if(!valuefive){
          var icon = '';
      }
      else{
          var icon = ' icon="'+valuefive+'"';
      }
      var text = '[bsmessage type="'+valueone+'" color="'+valuetwo+'" title="'+valuethree+'"'+icon+']'+valuefour+'[/bsmessage]';
      editor.codemirror.doc.replaceSelection(text);
    resolve()
  }
})
        
        
        
        resolve()
    }
  }
})
        
        
        
        resolve() 
      } 
      else{
         resolve('您必须填写提示框标题~~') 
      }
    })
  }
})

        
        
        
        
        
        resolve() 
      } 
      else{
         resolve('您必须选择一款颜色~~') 
      }
    })
  }
})

        
        
        
        
        
        
        
        
        resolve() 
      } 
      else{
         resolve('您必须选择一款提示框~~') 
      }
    })
  }
})

      }
      function emoji(editor){
       Swal.fire({
  title: '表情面板',
  html: '<button id=\"emoji\" class="swal2-confirm swal2-styled">点击显示表情面板</button><br><div id="emoemo"></div><br>Tips:点击表情后将直接插入到文章中',
  icon: 'info',
showCancelButton: false,
showConfirmButton: false,

});
		$('#emoji').BearsimpleEmoji($('#text'));
		$.fn.insertText = function(text){
		    editor.codemirror.doc.replaceSelection(text+' ');
		    Swal.close();
			this.each(function() {
				if(this.tagName !== 'INPUT' && this.tagName !== 'TEXTAREA') {return;}
				if (document.selection) {
					this.focus();
					var cr = document.selection.createRange();
					cr.text = text;
					cr.collapse();
					cr.select();
				}else if (this.selectionStart || this.selectionStart == '0') {
					var 
					start = this.selectionStart,
					end = this.selectionEnd;
					this.value = this.value.substring(0, start)+ text+ this.value.substring(end, this.value.length);
					this.selectionStart = this.selectionEnd = start+text.length;
				}else {
					this.value += text;
				}
			});        
			return this;
		}
		

}

       function colorpc(editor){
       Swal.fire({
  title: '字体颜色选择器面板',
  html: "<div id=\"color\"></div><br>选择完颜色后点击下面的直接插入按钮进行应用，本功能实现的效果仅在前台文章内页可见。",
  icon: 'info',
showCancelButton: true,
  confirmButtonColor: '#3085d6',
  cancelButtonColor: '#d33',
  cancelButtonText: "取消", 
  confirmButtonText: '直接插入'
}).then((result) => {
  if (result.isConfirmed) {
  var colorss = document.getElementById("colors").value;
  if(!colorss){
      alert('未选择颜色，不能直接插入!');
  }
  else{
    editor.codemirror.doc.replaceSelection('{bs-font color="'+ colorss + '"}内容{/bs-font}');
  }
  }
})
       $('#color').colorpicker({
        popover: false,
        inline: true,
        container: '#color',
template: '<div class="colorpicker">' +
          '<div class="colorpicker-saturation"><i class="colorpicker-guide"></i></div>' +
          '<div class="colorpicker-hue"><i class="colorpicker-guide"></i></div>' +
          '<div class="colorpicker-alpha">' +
          '   <div class="colorpicker-alpha-color"></div>' +
          '   <i class="colorpicker-guide"></i>' +
          '</div>' +
          '<div class="colorpicker-bar">' +
          '   <div class="input-group">' +
          '       <input id="colors" class="form-control input-block color-io" />' +
          '   </div>' +
          '</div>' +
          '</div>'
        })
        .on('colorpickerCreate', function (e) {
          var io = e.colorpicker.element.find('.color-io');
          io.val(e.color.string());
          io.on('change keyup', function () {
            e.colorpicker.setValue(io.val());
          });
        })
        .on('colorpickerChange', function (e) {
          var io = e.colorpicker.element.find('.color-io');
          if (e.value === io.val() || !e.color || !e.color.isValid()) {
            return;
          }
          io.val(e.color.string());
        });
       }
       $(document).ready(function() {
     
           $(".copyupload").attr("style","display:none;");
               $(".insertper").attr("style","display:none;");
               $(".autosaves").attr("style","display:none;");
               localStorage.setItem('autosavecid','');
               $("#wmd-button-bar").attr("style","display:none;");
        
       });
     

      
        </script>
        <?php
    }
    
    
    
    //腾讯云COS
    
}
