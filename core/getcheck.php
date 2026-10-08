<?php
function GetCheck()
{
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, Bsoptions('Assets_Custom').'/assets/check.json');
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_POST, false);
    curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($curl, CURLOPT_TIMEOUT, 15);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
    $data = curl_exec($curl);
    curl_close($curl);
    $output = json_decode($data,true);
    if ($output && $output['message'] == 'success'){
    return '<a style="color:green">连接成功,该自定义储存库可用!</a>';
    }
    else{
        return '<a style="color:red">连接失败,该自定义储存库不可用!若已更改为正确的地址，请刷新页面后查看检测结果</a>';
    }
}