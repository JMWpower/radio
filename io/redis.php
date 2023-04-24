<?php
header('Content-Type: text/html;charset=utf-8');
//header('Access-Control-Allow-Origin:*'); // *代表允许任何网址请求
//ini_set("error_reporting","E_ALL & ~E_NOTICE");//屏蔽错误信息
date_default_timezone_set('PRC');//设置北京时间

//连接本地的 Redis 服务
$redis = new Redis();
$redis->connect('127.0.0.1', 6379);
echo "redis Server is running: " . $redis->ping() . PHP_EOL;

$redis->flushall();//清空内容写入初始化内容
$room = [
	'id'  => '666',//房间id
    'name' => 'Jmw`Radio - 吹水大厅',//名称
    'msg' => '请遵守法律法规,文明聊天!',//简介说明
	'user' => 'Jmw`Radio',//创建者
	'state' => true,//true=永久,false=临时
	'lock' => false,//true=密码房,false=开放房
	'power' => false,//true=禁止游客点歌发言,false=允许游客点歌发言
	'password' => '',//房间密码
	'number' => 0,//房间人数
	'time' => 0,//删除房间判断
	'userid' => [],//用户ID
	'music_list' => [],//点播歌曲列表
	'music' => [//电台播放信息参数如下
		'music_type' => 'root',// root 系统播放，user 用户点歌
		'music_name' => '',//当前播放歌曲名称
		'music_singer' => '',//当前播放歌曲歌手名
		'music_url' => '',//当前播放歌曲链接
		'music_img' => '',//当前播放歌曲封面
		'music_user' => '',//当前播放歌曲点歌人
		'music_time' => 30,//初始化歌曲总时长30秒
		'music_times' => 0,//当前播放歌曲播放时长
		'music_adjust' => false, // 初始化总时长校准事件,因为QQ音乐没有获取到歌曲总时长
		'music_lyc' => '',//当前播放歌曲歌词
	],
	'ban' => [],//黑名单,用户ip
];
$redis->hSet('rooms', '666', json_encode($room, JSON_UNESCAPED_UNICODE));
$redis->hSet('roomid', 'id', 0);
?>