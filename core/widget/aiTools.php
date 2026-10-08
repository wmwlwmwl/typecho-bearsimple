<?php
header("HTTP/1.1 200 OK");
    header("Access-Control-Allow-Origin: *");
    date_default_timezone_set('PRC');
    use Typecho\Db;
use Typecho\Common;
use Widget\Options;

    $options = Helper::options();
    $temoptions = array(
        'AIService_Blacklist' => Bsoptions('AIService_Blacklist'),
        'AIService_Blacklist_Page' => Bsoptions('AIService_Blacklist_Page'),
    );
    $removeChar = ["https://", "http://"]; 
    if (bs_same_origin_check()) {
$data = [];

$i = 0;
if(Bsoptions('AIService_Blacklist') !== '' && is_array(Bsoptions('AIService_Blacklist'))){
foreach($temoptions['AIService_Blacklist'] as $val){
$this->widget('Widget\Archive@aitools'.$i.'post', 'pageSize=1&type=post', 'cid='.$val)->to($arr);
$data['blackurls'][] = $arr->permalink;
$i++;
}
}
if(Bsoptions('AIService_Blacklist_Page') !== '' && is_array(Bsoptions('AIService_Blacklist_Page'))){
foreach($temoptions['AIService_Blacklist_Page'] as $val){
$this->widget('Widget\Archive@aitools'.$i.'page', 'pageSize=1&type=page', 'cid='.$val)->to($arrs);
$data['blackurls'][] = $arrs->permalink;
$i++;
}
}
echo json_encode($data);
}