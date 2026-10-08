<?php
use Typecho\Db;
header("HTTP/1.1 200 OK");
    header("Access-Control-Allow-Origin: *");
    date_default_timezone_set('PRC');
    \Typecho\Widget::widget('Widget\User')->to($user);
    if (bs_same_origin_check() && $user->hasLogin() && $user->pass('administrator', true)) {
        $db = \Typecho\Db::get();
        $searchQuery = '%' . str_replace(' ', '%', $_GET['q']) . '%';
        $usersearch = $db->fetchAll($db->select()->from('table.users')->where('name LIKE ? ', htmlspecialchars($searchQuery,ENT_QUOTES,'UTF-8')));
        foreach($usersearch as $sear){
       $data[] = array(
                'id'    =>  $sear['uid'],
                'name'  =>  $sear['name']
            );

          
        }
        echo json_encode($data);
       }
   // }