<?php
$post_data = "配置你的QQ音乐COOKIE";
$url = "http://服务器地址:3300/user/setCookie";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(array('data' => $post_data)));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json"));
$result = curl_exec($ch);
curl_close($ch);

echo $result;
?>