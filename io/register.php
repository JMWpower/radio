<?php
header('Content-type: application/json');
//header('Access-Control-Allow-Origin:*'); // *代表允许任何网址请求
ini_set("error_reporting","E_ALL & ~E_NOTICE");//屏蔽错误信息
date_default_timezone_set('PRC');//设置北京时间

$jsoncallback = $_REQUEST['jsoncallback'] ?? '';
$userid = strtoupper(trim($_GET['user'] ?? ''));
$email = trim($_GET['email'] ?? '');
$code = trim($_GET['Code'] ?? '');
$password = trim($_GET['password'] ?? '');

if (empty($userid) || empty($email) || empty($code) || empty($password)) {
    $error = array(
        'status' => false,
        'error' => '关键数据获取失败，请检查后重试！'
    );
    echo "$jsoncallback(" . json_encode($error, JSON_UNESCAPED_UNICODE) . ")";
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $error = array(
        'status' => false,
        'error' => '邮箱格式不正确，请检查后重试！'
    );
    echo "$jsoncallback(" . json_encode($error, JSON_UNESCAPED_UNICODE) . ")";
    exit;
}

if (strlen($userid) > 24) {
    $error = array(
        'status' => false,
        'error' => '用户名不能超过12个字符，请检查后重试！'
    );
    echo json_encode($error, JSON_UNESCAPED_UNICODE);
    exit;
}

$filename = "./mail/token/$email.json";
if (!file_exists($filename)) {
    $error = array(
        'status' => false,
        'error' => '验证码数据验证失败，请稍后重试！'
    );
    echo "$jsoncallback(" . json_encode($error, JSON_UNESCAPED_UNICODE) . ")";
    exit;
}

$data = json_decode(file_get_contents($filename), true);
$token = $data['token'] ?? '';
$date = $data['date'] ?? '';

if (!$token || !$date) {
    $error = array(
        'status' => false,
        'error' => '验证码数据验证失败，请稍后重试！'
    );
    echo "$jsoncallback(" . json_encode($error, JSON_UNESCAPED_UNICODE) . ")";
    exit;
}

$minute = floor((strtotime(date('Y-m-d H:i:s')) - strtotime($date)) % 86400 / 60);
if ($minute > 5) {
    $error = array(
        'status' => false,
        'error' => "验证码超时[$minute 分钟]，请重新获取！"
    );
    echo "$jsoncallback(" . json_encode($error, JSON_UNESCAPED_UNICODE) . ")";
    exit;
}

if ($token != $code) {
    $error = array(
        'status' => false,
        'error' => '验证码不正确，请检查后重试！'
    );
    echo "$jsoncallback(" . json_encode($error, JSON_UNESCAPED_UNICODE) . ")";
    exit;
}

$arr = array(
    'user' => $userid,
    'password' => $password,
    'email' => $email
);

if (!file_put_contents("./user/$email.json", json_encode($arr))) {
    $error = array(
        'status' => false,
        'error' => '写入账户信息失败！'
    );
    echo "$jsoncallback(" . json_encode($error, JSON_UNESCAPED_UNICODE) . ")";
    exit;
}else{
	$token = getRandomString(18);
	$error = array(
	    'status' => true,
	    'user_name' => $userid,
	    'user_email' => $email,
	    'user_token' => $token
	);
	echo "$jsoncallback(" . json_encode($error, JSON_UNESCAPED_UNICODE) . ")";
	exit;
}

function getRandomString($len, $chars=null){
    if (is_null($chars)) {
        $chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz";
    }
    mt_srand(10000000*(double)microtime());
    for ($i = 0, $str = '', $lc = strlen($chars)-1; $i < $len; $i++) {
        $str .= $chars[mt_rand(0, $lc)];
    }
    return $str;
}
?>