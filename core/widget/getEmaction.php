<?php
ob_clean();
header("HTTP/1.1 200 OK");
    header("Access-Control-Allow-Origin: *");
    date_default_timezone_set('PRC');
    header('Content-type: application/json');
    \Typecho\Widget::widget('Widget\User')->to($user);
    if (bs_same_origin_check()) {
   $db = \Typecho\Db::get();
   $data = json_decode(file_get_contents('php://input'),true);
  switch($data['action']){
      case 'getReact':
    $targetId = $data['targetId'];
    $result = $db->fetchAll($db->select()->from('table.bscore_emaction_data')->where('target_id = ?', $targetId));
    $newResult = [];
    foreach ($result as $row) {
        $newRow = ['reaction_name' => $row['reaction_name'], 'count' => $row['diff']];
        array_push($newResult, $newRow);
    }
    exit(json_encode([
        'code' => 200,
        'msg' => 'success',
        'data' => ['reactionsGot' => $newResult],
    ]));
  break;
      
      case 'updateReact':
	                   $targetId = $data['targetId'];
    $reactionName = $data['reaction_name'];
    $diff = $data['diff'];
    // ponytail: cookie 去重仅防普通重复点击，清 cookie 可绕过；升级路径：插件级 IP 限流
    $markKey = 'emaction_' . md5((string)$targetId . '|' . (string)$reactionName);
    if ((int)$diff === -1) {
        if (\Typecho\Cookie::get($markKey) != '1') {
            exit(json_encode(['code' => 500, 'msg' => '您尚未点过该表情，无法取消']));
        }
    } else {
        if (\Typecho\Cookie::get($markKey) == '1') {
            exit(json_encode(['code' => 500, 'msg' => '您已点过该表情，请勿重复点击']));
        }
        \Typecho\Cookie::set($markKey, '1');
    }
   if (!in_array($diff, [1, -1])) {
        $diff = $diff > 0 ? 1 : -1;
    }
    $reaction_data = $db->fetchRow($db->select()->from('table.bscore_emaction_data')->where('target_id = ?', $targetId)->where('reaction_name = ?', $reactionName));
    if(!$reaction_data){
        $datas = array(
                'target_id' => $targetId,
                'reaction_name' => $reactionName,
                'diff' => (int)$diff,
            );
            $db->query($db->insert('table.bscore_emaction_data')->rows($datas));
            
    }
    else{
        if($data['diff'] == '-1'){
            $diff = (int)$reaction_data['diff'] -1;
        }
        else{
            $diff = (int)$reaction_data['diff'] +1;
        }
        if($data['diff'] == '-1' && $reaction_data['diff'] == 0){
            $diff = 0;
        }
        $db->query($db->update('table.bscore_emaction_data')->rows(array('diff' => $diff))->where('target_id = ?', $targetId)->where('reaction_name = ?', $reactionName));
    }
    $reaction_sdata = $db->fetchAll($db->select()->from('table.bscore_emaction_data')->where('target_id = ?', $targetId)->where('reaction_name = ?', $reactionName));
    exit(json_encode([
      'code' => 200,
      'msg' => 'success',
      'data' => ['reactionsGot' => $reaction_sdata]
    ]));
 

          break;
}
}
  


   
   

    
    