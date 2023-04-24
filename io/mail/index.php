<?php
header('Content-type: application/json');
header('Access-Control-Allow-Origin:*'); // *代表允许任何网址请求
ini_set("error_reporting","E_ALL & ~E_NOTICE");//屏蔽错误信息
date_default_timezone_set('PRC');//设置北京时间
$jsoncallback = htmlspecialchars($_REQUEST ['jsoncallback']);//获取回调函数名

//获取参数
$email = trim($_GET["email"]); //去除空格

session_start(); // 启动 session
$current_timestamp = time();// 获取当前时间戳
// 获取上一次提交表单的时间戳，如果不存在则设置为0
$last_submit_timestamp = isset($_SESSION['last_submit_timestamp']) ? $_SESSION['last_submit_timestamp'] : 0;
$min_submit_interval = 30;// 设置最小提交时间间隔，例如1秒
// 如果当前时间戳与上一次提交表单的时间戳之间的时间间隔小于最小提交时间间隔，则拒绝该请求
if ($current_timestamp - $last_submit_timestamp < $min_submit_interval) {
    $error = array(// 返回错误信息
        'status' => false,
        'error' => '您提交的太频繁了，请稍后再试！',
        'error_code' => '500',
        'title' => '频繁请求❌',
        'message' => '您提交的太频繁了❌，请稍后再试！'
    );
    echo $jsoncallback . "(" . json_encode($error, JSON_UNESCAPED_UNICODE) . ")";  // 不编码中文回传json数据
    die();
} else {
    $_SESSION['last_submit_timestamp'] = $current_timestamp;// 更新上一次提交表单的时间戳
}


if (empty($email)) {
    $error = array(
        'status' => false,
        'error' => '邮箱账号为空，请检查后重试！',
        'error_code' => '000',
        'title' => '发送失败❌',
        'message' => '发送验证码失败❌，请稍后重试！'
    );
	echo $jsoncallback . "(" . json_encode($error, JSON_UNESCAPED_UNICODE) . ")";  // 不编码中文回传json数据
	die();
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $error = array(
        'status' => false,
        'error' => '邮箱格式不正确，请检查后重试！',
        'error_code' => '001',
        'title' => '发送失败❌',
        'message' => '发送验证码失败❌，请稍后重试！'
    );
    echo $jsoncallback . "(" . json_encode($error, JSON_UNESCAPED_UNICODE) . ")";  // 不编码中文回传json数据
    exit;
}
##生成验证码
$code = rand(100000, 999999);//取随机数字为验证码
$arr['token'] = $code;
$arr['date'] = date('Y-m-d H:i:s');
$json_data = json_encode($arr);
if (!file_put_contents('./token/'.$email.'.json', $json_data)) {
	$error = array(
		'status' => false,
		'error' => '获取验证码发生错误，请稍后重试！',
		'error_code' => '003',
		'title' => '发送失败❌',
		'message' => '发送验证码失败❌，请稍后重试！'
	);
	echo $jsoncallback . "(" . json_encode($error, JSON_UNESCAPED_UNICODE) . ")";  // 不编码中文回传json数据
	die();
}
##验证码生成结束

##验证码邮箱发送开始
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require './lib/Exception.php';
require './lib/PHPMailer.php';
require './lib/SMTP.php';

$mail = new PHPMailer(true);                              // Passing `true` enables exceptions
try {
    //服务器配置
    $mail->CharSet ="UTF-8";                     //设定邮件编码
    $mail->SMTPDebug = 0;                        // 调试模式输出
    $mail->isSMTP();                             // 使用SMTP
    $mail->Host = 'smtp.163.com';                // SMTP服务器,这里以网易邮箱示例,可以修改为你的邮箱服务商SMTP服务器
    $mail->SMTPAuth = true;                      // 允许 SMTP 认证
    $mail->Username = '设置你的 SMTP 用户名';                // SMTP 用户名  即邮箱的用户名   | 这里需要配置
    $mail->Password = '设置你的 SMTP 密码';             // SMTP 密码  部分邮箱是授权码(例如163邮箱)   | 这里需要配置
    $mail->SMTPSecure = 'ssl';                    // 允许 TLS 或者ssl协议
    $mail->Port = 465;                            // 服务器端口 25 或者465 具体要看邮箱服务器支持

	$mail->setFrom('配置发件人', 'Jmw`Radio | 音乐电台');  //发件人   | 这里需要配置
	$mail->addAddress($email, $email);  // 收件人

    //Content 自己修改想要的效果
    $mail->isHTML(true);    // 是否以HTML文档格式发送  发送后客户端可直接显示对应HTML内容
    $mail->Subject = '为你的新 Jmw`Radio | 音乐电台 帐户验证邮箱 - 欢迎来到 Jmw`Radio | 音乐电台 ！';//邮件标题
    $mail->Body    = '<div style="position: relative;"><div style="width: auto; margin: 0 auto; text-align: center; background-color: #ffffff; margin: 0 auto;"><span style="font-size: 56px;background-image:-webkit-linear-gradient(left,#55ff00,#000000,#ffaa00); -webkit-background-clip:text; -webkit-text-fill-color:transparent;">Welcome</span><br><br><span style="font-size: 32px;background-image:-webkit-linear-gradient(left,#545454,#000000,#172f47); -webkit-background-clip:text; -webkit-text-fill-color:transparent;">欢迎来到&nbsp;&nbsp;[&nbsp;&nbsp;Jmw`Radio&nbsp;&nbsp;|&nbsp;&nbsp;音乐电台&nbsp;&nbsp;]</span></div><br><br><br><br><div style="width: auto; margin: 0 auto; text-align: left; background-color: #ffffff; margin: 0 auto;"><span style="font-size: 18px;">亲爱的&nbsp;&nbsp;[&nbsp;&nbsp;<b>'.$email.'</b>&nbsp;&nbsp;]&nbsp;&nbsp;,&nbsp;&nbsp;你好&nbsp;&nbsp;!</span><br><br><span style="font-size: 18px;">请输入以下验证码来为你的&nbsp;&nbsp;<b>[&nbsp;&nbsp;Jmw`Radio&nbsp;&nbsp;|&nbsp;&nbsp;音乐电台&nbsp;&nbsp;]&nbsp;&nbsp;</b>帐户验证邮箱。</span><br><br><span style="font-size: 18px;"><b>验证码&nbsp;&nbsp;:</b>&nbsp;&nbsp;<small style="color:#757575">[&nbsp;&nbsp;5分钟内有效&nbsp;&nbsp;]</small></span><br><br><table border="0"width="100%"cellspacing="0"cellpadding="0"style="border-collapse: collapse; border-spacing: 0px;"><tbody><tr><td style="-webkit-font-smoothing: subpixel-antialiased;"><div style="text-align: center; background: rgb(241, 244, 247); border-radius: 4px; letter-spacing: 2px; padding: 16px;"><strong style="font-size: 32px;text-decoration:underline;color: #5aa2ff;">'.$code.'</strong></div></td></tr></tbody></table></div><br><br><br><br><br><br><br><div style="width: auto; margin: 0 auto; text-align: center; background-color: #ffffff; margin: 0 auto;"><span style="font-size: 16px;">如果并未申请验证码，你可以忽略这封邮件。</span></div><table width="100%"border="0"cellspacing="0"cellpadding="0"align="center"style="margin: 0 auto; text-align: center;"><tbody><tr><td style="padding: 24px 0 12px;"><p style="padding: 8px 0px 0px; margin: 0px 50px; line-height: 28px;"><span style="color: rgb(153, 153, 153); font-size: 12px;">*&nbsp;&nbsp;这是自动电子邮件服务,请勿回复</span></p><p style="padding: 8px 0px 0px; margin: 0px 50px; line-height: 28px; border-top: 1px solid rgb(228, 228, 228);"><span style="color: rgb(124, 123, 123); font-family: &quot;PingFang SC&quot;; font-size: 14px;">―&nbsp;&nbsp;&nbsp;Jmw`Radio&nbsp;&nbsp;|&nbsp;&nbsp;音乐电台&nbsp;&nbsp;团队&nbsp;&nbsp;&nbsp;―</span></p><p style="padding: 8px 0px 0px; margin: 0px 50px; line-height: 28px; border-top: 1px solid rgb(228, 228, 228);"><br></p></td></tr></tbody></table></div>' . date('Y-m-d H:i:s');
    $mail->AltBody = '验证码 : [ 5 分钟内有效 ] '.$code.' 如果并未申请验证码，你可以忽略这封邮件。';//不支持html格式时显示内容

    $mail->send();
    $success = array(
		'status' => true,
		'error' => '',
		'error_code' => '200',
		'title' => '发送成功✅',
		'message' => '发送验证码成功✅，请查收邮件！'
	);
	echo $jsoncallback . "(" . json_encode($success, JSON_UNESCAPED_UNICODE) . ")";  // 不编码中文回传json数据
	die();
	
} catch (Exception $e) {
	$error = array(
        'status' => false,
        'error' => $mail->ErrorInfo." ".$email,
        'error_code' => '005',
        'title' => '发送失败❌',
        'message' => '发送验证码失败❌,请稍后重试！'
    );
	echo $jsoncallback . "(" . json_encode($error, JSON_UNESCAPED_UNICODE) . ")";  // 不编码中文回传json数据
	die();
}
##验证码邮箱发送结束