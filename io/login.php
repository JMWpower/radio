<?php
header('Content-type: application/json');
//header('Access-Control-Allow-Origin:*'); // *代表允许任何网址请求
ini_set("error_reporting","E_ALL & ~E_NOTICE");//屏蔽错误信息
date_default_timezone_set('PRC');//设置北京时间

$jsoncallback = $_REQUEST['jsoncallback'] ?? '';
$email = trim($_GET['email'] ?? '');
$password = trim($_GET['password'] ?? '');

if (empty($email) || empty($password)) {
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

$filename='./user/'.$email.'.json';

if(file_exists($filename)){
	$json_string = file_get_contents($filename);
	$datas = json_decode($json_string, true);
	$user = $datas['user'];
	$passwords = $datas['password'];
	if ($passwords==$password) {//密码正确，返回数据
		$token = getRandomString(18);
		$error = array(
			'status' => true,
			'user_name' => $user,
			'user_email' => $email,
			'user_token' => $token
		);
		echo "$jsoncallback(" . json_encode($error, JSON_UNESCAPED_UNICODE) . ")";
		exit;
	}else{//密码不正确，返回数据
		$error = array(
			'status' => false,
			'error' => '登陆失败，您键入的密码不正确！'
		);
		echo "$jsoncallback(" . json_encode($error, JSON_UNESCAPED_UNICODE) . ")";
		exit;
	}
}else{//echo "当前目录中，文件".$filename."不存在";
	$error = array(
		'status' => false,
		'error' => '登陆失败，您键入的账号不存在！'
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