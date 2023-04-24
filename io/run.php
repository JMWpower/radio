<?php
header('Content-Type: text/html;charset=utf-8');
//header('Access-Control-Allow-Origin:*'); // *代表允许任何网址请求
ini_set("error_reporting","E_ALL & ~E_NOTICE");//屏蔽错误信息
date_default_timezone_set('PRC');//设置北京时间

use Workerman\Worker;
use Workerman\Timer;
use Workerman\Connection\TcpConnection;

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/wyy.php';//引入网易云接口Api
require_once __DIR__ . '/qq.php';//引入QQ音乐接口Api

//连接本地的 Redis 服务
$redis = new Redis();
$redis->connect('127.0.0.1', 6379);
echo "redis Server is running: " . $redis->ping() . PHP_EOL;

$codes = [];//切歌验证数组
$root_music_list = [];//临时歌曲列表数组

// 创建一个Worker监听?端口，使用websocket协议通讯
$ws_worker = new Worker("websocket://0.0.0.0:34567");

// 进程数设置为?
$ws_worker->count = 1;

// 新增加一个属性，用来保存uid到connection的映射(uid是用户id或者客户端唯一标识)
$ws_worker->uidConnections = array();

// 当有客户端连接事件时 => 连接进入时初始化
$ws_worker->onConnect = function($connection) use ($ws_worker) {
    $uids = uniqid();// 为连接设置 UID
    $connection->uid = $uids;
    $ws_worker->uidConnections[$connection->uid] = $connection;
    $response = json_encode(array("code" => "uid","message" => '连接初始化参数','uid'=>$uids), JSON_UNESCAPED_UNICODE);
    $connection->send($response);
    echo "有新的客户端服务链接拨通,来自IP:" . $connection->getRemoteIp() . "\n";
};

// 当有客户端连接断开时
$ws_worker->onClose = function(TcpConnection $connection) use ($redis,$ws_worker) {
	if (isset($connection->uid)) {// 连接断开时删除映射
	    $rooms = $redis->hGetAll('rooms');// 读取所有房间信息
	    if (count($rooms) > 0) {  // 确认$rooms不为空
	        foreach ($rooms as $roomId => $roomStr) {// 循环处理每个房间
	            $room = json_decode($roomStr, true);
	            if (in_array($connection->uid, $room['userid'])) {// 从userid数组中删除该用户
	                $key = array_search($connection->uid, $room['userid']);
	                unset($room['userid'][$key]);
	                $room['number'] = count($room['userid']); // 更新房间人数
	                $redis->hSet('rooms', $roomId, json_encode($room));
	            }
	        }
	    }
		unset($ws_worker->uidConnections[$connection->uid]);// 从所有房间中删除该用户
	}
    echo "有客户端服务链接断开了,来自IP:" . $connection->getRemoteIp() . "\n";
};

//当客户端的连接上发生错误时触发$code=错误码,$msg=错误消息
$ws_worker->onError = function($connection, $code, $msg){
	echo "有客户端服务链接拨通失败,来自IP:" . $connection->getRemoteIp() . " \ 错误码:$code \ 错误消息:$msg\n";
};

// 进程启动后设置?个每?运行一次的定时器
$ws_worker->onWorkerStart = function ($ws_worker) use ($redis) {
	Timer::add(10, function () use ($redis) {
	    $rooms = $redis->hGetAll('rooms');
	    foreach ($rooms as $roomId => $roomStr) {
	        $room = json_decode($roomStr, true);
	        if (isset($room['userid'])) {
	            $roomss = get_rooms($redis, $roomId);
	            $response = json_encode(array("code" => "刷新","message" => "定时刷新","rooms" => $roomss), JSON_UNESCAPED_UNICODE);
	            $online_users = array();
	            foreach ($room['userid'] as $userid) {
	                if (isUidOnline($userid)) {
	                    $online_users[] = $userid;
	                } else {
	                    $room['number']--; // 删除该用户后，更新房间人数
	                }
	            }
	            $room['userid'] = $online_users;
	            $redis->hSet('rooms', $roomId, json_encode($room));
	            foreach ($online_users as $userid) {
	                sendMessageByUid($userid, $response);
	            }
	        }
	    }
	});

    Timer::add(1, function() use ($redis) {// 计时器每秒执行一次
        $rooms = $redis->hGetAll('rooms');// 读取所有房间信息
        foreach ($rooms as $roomId => $roomStr) {// 循环处理每个房间的播放信息
            $room = json_decode($roomStr, true);
            if ($room['number'] > 0) {// 如果房间人数大于0
                if ($room['music']['music_times'] >= $room['music']['music_time']) {// 如果计时器大于等于歌曲长度，播放下一首歌曲
                    play_next_song($redis,$roomId,'Jmw`Radio');//带上房间id,执行播放下一首切歌操作
                } else {// 当前歌曲未播放完成，更新播放时间
                    $room['music']['music_times'] += 1;
					$room['time'] = 0; // 重置时间为 0
                    $redis->hSet('rooms', $roomId, json_encode($room));
                }
                if ($room['music']['music_adjust'] == false && !empty($room['userid']) && $room['music']['music_times'] > 5) {
                    $random_uid = $room['userid'][array_rand($room['userid'])];// 从当前房间的userid中随机选取一位用户
                    $response = json_encode(array("code" => "播放校时","message" => '服务器请求返回歌曲总时长'), JSON_UNESCAPED_UNICODE);
                    sendMessageByUid($random_uid, $response);// 对时没有结果随机选取一个用户返回获取歌曲时间
                }elseif($room['music']['music_adjust'] == false && $room['music']['music_times'] > 10){//10秒没有用户回传对时,可能播放链接有问题
					play_next_song($redis,$roomId,'Jmw`Radio');//带上房间id,执行播放下一首切歌操作
				}
            } else {
	            $room['time'] += 1; // 时间累加 10
	            if ($room['time'] > 3600 && !$room['state']) { // 如果时间大于 ? 秒且为临时房，删除房间
	                $redis->hDel('rooms', $roomId);
	            } else {
	                $redis->hSet('rooms', $roomId, json_encode($room, JSON_UNESCAPED_UNICODE)); // 更新房间信息
	            }
	        }
        }
    });
};
function isUidOnline($uid){//判断用户是否在线
	global $ws_worker;
	return isset($ws_worker->uidConnections[$uid]);
}
// 当有客户端发来消息时执行的回调函数
$ws_worker->onMessage = function(TcpConnection $connection, $data) use ($redis) {
    $data = json_decode($data, true);
	$recv_uid = $data["uid"];
	$recv_ip = $connection->getRemoteIp();
	$roomId = $data['room_id'];
	if (empty($data["type"]) || empty($data["name"]) || empty($roomId)) {
	    $response = json_encode(array("code" => "000","message" => '关键数据验证失败,请刷新后重试!'), JSON_UNESCAPED_UNICODE);
	    return sendMessageByUid($recv_uid, $response);
	}
	
	if (empty($data["email"]) && empty($data["token"]) && $data['type'] != "播放校时" && $data['type'] != "加入房间") {
	    $last_request_time = $redis->get($recv_ip);
	    if (!empty($last_request_time)) {// 判断上一次请求是否超过 1 分钟
	        $diff = time() - intval($last_request_time);
	        if ($diff < 5) {
	            $response = json_encode(array("code" => "000","message" => '未登录游客权限不足, 当前技能冷却中，请 ' . (5 - $diff) . ' 秒后再试!'), JSON_UNESCAPED_UNICODE);
	            return sendMessageByUid($recv_uid, $response);
	        }
	    }
	    $redis->set($recv_ip, time());// 记录当前请求时间
	}
	
	$room = json_decode($redis->hGet('rooms', $roomId), true);
	
	if (empty($room)) {
	    $message = '此房间不存在或已失效，无法操作！';
	    $response = json_encode(array("code" => "000", "message" => $message), JSON_UNESCAPED_UNICODE);
	    return sendMessageByUid($recv_uid, $response);
	}
	
	if ( $room['power'] == true && $data['type'] != "播放校时" && empty($data["email"]) && empty($data["token"]) ) {
	    $message = '当前房间禁止游客操作,请登录账号后重试！';
	    $response = json_encode(array("code" => "000", "message" => $message), JSON_UNESCAPED_UNICODE);
	    return sendMessageByUid($recv_uid, $response);
	}
	
	if( $room['power'] == true ){//验证登录权限
		$serverToken = $redis->get($data['email']);
		if ($serverToken === null || $serverToken !== $data['email']) {// token 不存在或者不正确
		    $response = json_encode(array("code" => "000","message" => '权限校验不通过,请重新登录后重试!'), JSON_UNESCAPED_UNICODE);
		    return sendMessageByUid($recv_uid, $response);
		}
	}
	
	if (!empty($room['ban']) && in_array($recv_ip, $room['ban'])) {
	    $message = '您被列入此房间黑名单了，无法操作！';
	    $response = json_encode(array("code" => "000", "message" => $message), JSON_UNESCAPED_UNICODE);
	    return sendMessageByUid($recv_uid, $response);
	}
	
	switch ($data['type']) {
	    case '加入房间':
			if (!empty($room['userid']) && in_array($recv_uid, $room['userid'])) {
				$message = '你已经在这个房间了，请不要重复加入！';
				$response = json_encode(array("code" => "000", "message" => $message), JSON_UNESCAPED_UNICODE);
				return sendMessageByUid($recv_uid, $response);
			} elseif ($room['lock'] && $room['password'] !== $data['room_password']) {
				$message = '房间密码错误，无法加入此房间！';
				$response = json_encode(array("code" => "000", "message" => $message), JSON_UNESCAPED_UNICODE);
				return sendMessageByUid($recv_uid, $response);
			}
			$roomss = $redis->hGetAll('rooms');// 读取所有房间信息
			foreach ($roomss as $roomIds => $roomStr) {// 循环处理每个房间
				$roomm = json_decode($roomStr, true);
				if (is_array($roomm['userid'])) {
				    if (in_array($connection->uid, $roomm['userid'])) {// 从userid数组中删除该用户
				    	$key = array_search($connection->uid, $roomm['userid']);
				    	unset($roomm['userid'][$key]);
				    	$roomm['number'] = count($roomm['userid']); // 更新房间人数
				    	$redis->hSet('rooms', $roomIds, json_encode($roomm));
				    	if (count($roomm['userid']) <= 1) {//只有一个新加入的用户
				    	    get_root_music_list();//Get系统播放列表
                			sleep(1);  // 暂停 1 秒后执行切歌
                			play_next_song($redis,$roomIds,$data['name']);//带上房间id,执行播放下一首切歌操作
				    	}
				    }
				}
			}
			array_push($room['userid'], $recv_uid);
			$room['number'] = count($room['userid']);
			$redis->hSet('rooms', $roomId, json_encode($room));
			$rooms = get_rooms($redis,$roomId);//获取房间数据
			$response = json_encode(array("code" => "加入房间回传","message" => '加入房间成功!',"room_id" => $roomId,"room_password" => $data["room_password"],"rooms" => $rooms), JSON_UNESCAPED_UNICODE);
			return sendMessageByUid($recv_uid, $response);
			break;
		case '播放校时':
			if (!empty($data['time'])) {//如果时长为空忽略
				$room['music']['music_time'] = $data['time'];
				$room['music']['music_adjust'] = true; // 更新总时长校准事件,避免重复请求
				$redis->hSet('rooms', $roomId, json_encode($room));
			}
			break;
		case '切歌':
			global $codes;
			if (!empty($data['change_song_codes'])) {//投票切歌
				$key = array_search($data['change_song_codes'], $codes);
				if ($key !== false) {// 验证码存在,执行切歌
				    unset($codes[$key]);  // 删除验证码
				    play_next_song($redis,$roomId,$data['name']); 
				}
			}else {
				if ($room['number'] > 4) { // 如果房间人数大于4,返回投票
				    if (isset($room['userid'])) {// 检查该房间是否具有在线用户
						$code = date('ymdhis');  // 使用 date() 生成唯一码
						array_push($codes, $code);//将验证码存入数组
				    	$response = json_encode(array("code" => "投票切歌",'user' => $data['name'],'token' => $code), JSON_UNESCAPED_UNICODE);
				    	$online_users = $room['userid'];// 获取当前房间在线用户列表
				    	foreach ($online_users as $userid) {
				    		sendMessageByUid($userid, $response);
				    	}
				    }
				}else {
					play_next_song($redis,$roomId,$data['name']);
				}
			}
			echo ('用户点击切歌按钮,执行切歌操作'.PHP_EOL);
			break;
		case '单曲搜索':
			if (!empty($data['search_strs'])) {
				if ($data['search'] == 'wy') {
					$search_song = search_song_wyy($data['search_strs']);
				}else{
					$search_song = search_song_qq($data['search_strs']);
				}
				echo ('用户点击搜索按钮,执行搜歌操作'.PHP_EOL);
				$response = json_encode(array("code" => "搜索回传",'search_list'=>$search_song), JSON_UNESCAPED_UNICODE);
				return sendMessageByUid($recv_uid, $response);
			}else{
				$message = '关键词获取失败，请检查或稍后重试！';
				$response = json_encode(array("code" => "000", "message" => $message), JSON_UNESCAPED_UNICODE);
				return sendMessageByUid($recv_uid, $response);
			}
			break;
		case '歌单搜索':
			if (!empty($data['search_str'])) {
				if ($data['search'] == 'wy') {
					$search_list = search_gd_wyy($data['search_str']);
				}else{
					$search_list = search_gd_qq($data['search_str']);
				}
				echo ('用户点击歌单搜索按钮,执行搜索操作'.PHP_EOL);
				$response = json_encode(array("code" => "歌单搜索回传",'search_list'=>$search_list), JSON_UNESCAPED_UNICODE);
				return sendMessageByUid($recv_uid, $response);
			}else{
				$message = '关键词获取失败，请检查或稍后重试！';
				$response = json_encode(array("code" => "000", "message" => $message), JSON_UNESCAPED_UNICODE);
				return sendMessageByUid($recv_uid, $response);
			}
			break;
		case '热歌榜':
			if ($data['search'] == 'wy') {
				$search_list = search_bangdan_wyy();
			}else{
				$search_list = search_bangdan_qq();
			}
			echo ('用户点击搜索热歌按钮,执行搜歌操作'.PHP_EOL);
			$response = json_encode(array("code" => "热歌榜回传",'search_list'=>$search_list), JSON_UNESCAPED_UNICODE);
			return sendMessageByUid($recv_uid, $response);
			break;
		case '歌单列表':
			if (!empty($data['gedan_id'])) {
				if ($data['gedan_source'] == '网易云') {
					$search_song = get_wyygdlist( $data['gedan_id'] );
				}else{
					if ($data['gedan_source'] == 'QQrg') {//QQ热歌榜获取链接不一样
						$search_song = get_qqgdlist_rg( $data['gedan_id'] );
					}else{
						$search_song = get_qqgdlist( $data['gedan_id'] );
					}
				}
				echo ('用户点击歌单详情按钮,执行查询操作'.PHP_EOL);
				$response = json_encode(array("code" => "歌单详情",'search_list'=>$search_song), JSON_UNESCAPED_UNICODE);
				return sendMessageByUid($recv_uid, $response);
			}
			break;
		case '点歌':
			$music = $room['music'];
			if ( ($music['music_name'].$music['music_singer']) == ($data['song_name'].$data['song_singer']) ) {
				$message = '用户点播列表已存在同名歌曲，点播歌曲失败！';
				$response = json_encode(array("code" => "000",'message'=>$message), JSON_UNESCAPED_UNICODE);
				return sendMessageByUid($recv_uid, $response);
			} elseif (!empty($room['music_list'])) {
				$song_ids = is_array($data['song_id']) ? $data['song_id'] : explode(',', $data['song_id']);
				$existing_keys = array_intersect(array_column($room['music_list'], 'music_id'), $song_ids);
				if (!empty($existing_keys)) {
					$message = '用户点播列表已存在同名歌曲,点播歌曲失败!';
					$response = json_encode(array("code" => "000",'message'=>$message), JSON_UNESCAPED_UNICODE);
					return sendMessageByUid($recv_uid, $response);
				}
			}
			$room['music_list'][] = [//开始执行新增歌曲到列表
				'music_id' => $data["song_id"],//点播歌曲ID
				'music_name' => $data["song_name"],//点播歌曲名称
				'music_singer' => $data["song_singer"],//点播歌曲歌手名
				'music_img' => $data["song_img"],//点播歌曲封面
				'music_source' => $data["song_source"],//点播歌曲平台
				'music_user' => $data["name"],//点播歌曲点歌人
			];
			$redis->hSet('rooms', $roomId, json_encode($room, JSON_UNESCAPED_UNICODE));
			$response = json_encode(array("code" => "000","message" => "歌曲点播成功!"), JSON_UNESCAPED_UNICODE);
			sendMessageByUid($recv_uid, $response);
			if ($room['music']['music_type'] == 'root') {//如果当前是自动播放的,那么直接切换到用户点的歌
				play_next_song($redis,$roomId,$data['name']);
				echo "系统播放模式,现在为您切歌" . PHP_EOL;
			}
			if (isset($room['userid'])) {// 检查该房间是否具有在线用户
				$response = json_encode(array("code" => "点歌刷新","message" => $data['name']." 点歌 《".$data['song_name']." - ".$data['song_singer']."》","music_list" => $room['music_list']), JSON_UNESCAPED_UNICODE);
				$online_users = $room['userid'];// 获取当前房间在线用户列表
				foreach ($online_users as $userid) {
					sendMessageByUid($userid, $response);
				}
			}
			break;
		case '顶歌':
			if ( !empty($data["song_id"]) && !empty($room['music_list'])) {
			    $key = array_search($data['song_id'], array_column($room['music_list'], 'music_id'));
			    if ($key !== false) {
			        $song = $room['music_list'][$key];
			        array_splice($room['music_list'], $key, 1);
			        array_unshift($room['music_list'], $song);
			        $redis->hSet('rooms', $roomId, json_encode($room, JSON_UNESCAPED_UNICODE));
			        $message = '已将此歌曲置顶！请稍后查看已点播歌曲列表!';
			        $response = json_encode(array("code" => "000", 'message' => $message), JSON_UNESCAPED_UNICODE);
			        sendMessageByUid($recv_uid, $response);
					if (isset($room['userid'])) {// 检查该房间是否具有在线用户
						$response = json_encode(array("code" => "顶歌刷新","message" => $data['name']." 顶歌 《".$song['music_name']." - ".$song['music_singer']."》","music_list" => $room['music_list']), JSON_UNESCAPED_UNICODE);
						$online_users = $room['userid'];// 获取当前房间在线用户列表
						foreach ($online_users as $userid) {
							sendMessageByUid($userid, $response);
						}
					}
					return;
			    }
			}
			$message = '未知错误,顶歌失败!';
			$response = json_encode(array("code" => "000",'message'=>$message), JSON_UNESCAPED_UNICODE);
			return sendMessageByUid($recv_uid, $response);
			break;
		case '消息':
			if ( !empty($data["msg"]) ) {//判断是否是空消息
				$recv_ips = query_ip_location($recv_ip);//获取用户ip地址
				$sanitized = DoFilterWords($data["msg"]);//违禁字
				$response = json_encode(array("code" => "消息","message" => $sanitized,"user" => $data["name"],"date" => date("Y-m-d H:i:s"),"ip" => $recv_ips), JSON_UNESCAPED_UNICODE);
				if (isset($room['userid'])) {// 检查该房间是否具有在线用户
					$online_users = $room['userid'];// 获取当前房间在线用户列表
					foreach ($online_users as $userid) {//对房间用户广播新消息
						sendMessageByUid($userid, $response);
					}
				}
			}
			break;
		case '新建房间':
			//var_dump($data);
			if (empty($data['add_rooms_id']) || empty($data['add_rooms_msg'])) {
				$response = json_encode(array("code" => "000","message" => '缺失关键数据,建房失败!'), JSON_UNESCAPED_UNICODE);
				return sendMessageByUid($recv_uid, $response);
			}
			if(empty($data['email']) || empty($data['token'])){
				$response = json_encode(array("code" => "000","message" => '登录用户才能建房哦,建房失败!'), JSON_UNESCAPED_UNICODE);
				return sendMessageByUid($recv_uid, $response);
			}
			/*$serverToken = $redis->get($data['email']);
			if ($serverToken === null || $serverToken !== $data['email']) {// token 不存在或者不正确
			    $response = json_encode(array("code" => "000","message" => '权限校验不通过,请重新登录后重试!'), JSON_UNESCAPED_UNICODE);
			    return sendMessageByUid($recv_uid, $response);
			}*/
			$roomKey = "room:{$data['name']}";// 判断用户是否已经创建房间
			if ($redis->setnx($roomKey, 1) === 0) {
				$response = json_encode(array("code" => "000","message" => '一个用户只能创建一个房间,建房失败!'), JSON_UNESCAPED_UNICODE);
				return sendMessageByUid($recv_uid, $response);
			}
			$rooms = $redis->hGetAll('rooms');// 判断是否存在同名房间
			foreach ($rooms as $roomId => $roomStr) {
				$room = json_decode($roomStr, true);
				if ($room['name'] == $data['add_rooms_id']) {
					$response = json_encode(array("code" => "000","message" => '已存在同名房间,建房失败!'), JSON_UNESCAPED_UNICODE);
					return sendMessageByUid($recv_uid, $response);
				}
			}
			$addroomid = ($redis->hGet('roomid', 'id')) + 1;
			$redis->hSet('roomid', 'id', $addroomid);//更新id
			$addroomid = sprintf("%03d", $addroomid);
			empty($data['add_rooms_ps']) ? $add_rooms_ps_lock = false : $add_rooms_ps_lock = true;
			$room = [
				'id'  => $addroomid,//房间id
				'name' => $data['add_rooms_id'],//名称
				'msg' => $data['add_rooms_msg'],//简介说明
				'user' => $data['name'],//创建者
				'state' => false,//true=永久,false=临时
				'lock' => $add_rooms_ps_lock,//true=密码房,false=开放房
				'power' => $data['add_rooms_power'],//true=禁止游客点歌发言,false=允许游客点歌发言
				'password' => $data['add_rooms_ps'],//房间密码
				'number' => 0,//房间人数
				'time' => 0,//删除房间判断
				'userid' => [],//用户ID
				'music_list' => [],//点播歌曲列表
				'music' => [//电台播放信息参数如下
					'music_type' => 'root',// root 系统播放，user 用户点歌
					'music_time' => 30,//初始化歌曲总时长30秒
					'music_times' => 0,//当前播放歌曲播放时长
					'music_adjust' => false, // 初始化总时长校准事件,因为QQ音乐没有获取到歌曲总时长
				],
				'ban' => [],//黑名单,用户ip
			];
			$redis->hSet('rooms', $addroomid, json_encode($room, JSON_UNESCAPED_UNICODE));
			$response = json_encode(array("code" => "建房成功","id" => $addroomid,"ps" => $data['add_rooms_ps']), JSON_UNESCAPED_UNICODE);
			sendMessageByUid($recv_uid, $response);
			sleep(1);//延迟1秒后切歌
			return play_next_song($redis,$addroomid,$data['name']);//带上房间id,执行播放下一首切歌操作
			break;
			
		default:
		    break;
	}
};
    
// 向所有验证的用户推送数据
function broadcast($data_json){
    global $ws_worker;
    foreach ($ws_worker->uidConnections as $connection) {
        $connection->send($data_json);
    }
}

// 针对uid推送数据
function sendMessageByUid($uid, $message){
    global $ws_worker;
    if(isset($ws_worker->uidConnections[$uid]) && $ws_worker->uidConnections[$uid]) { 
        $connection = $ws_worker->uidConnections[$uid];
        $connection->send($message);
    }
}

// 播放下一首歌曲 开始
function play_next_song($redis,$room_id,$users){
	$room = json_decode($redis->hGet('rooms', $room_id), true);
	if (!empty($room['music_list'][0]) && count($room['music_list']) > 0) {//用户点播列表不为空
		$first_music = reset($room['music_list']); // 获取第一个元素
		array_shift($room['music_list']); // 删除第一个元素
		$redis->hSet('rooms', $room_id, json_encode($room)); // 更新哈希表
		var_dump($first_music);
		if ($first_music['music_source'] == "网易云") {
			$ls_url = song_url_wyy($first_music['music_id']); // 获取播放链接
		}else{
			$ls_url = song_url_qq($first_music['music_id']); // 获取播放链接
		}
		if ($ls_url['code'] != '200') {
		    play_next_song($redis,$room_id,'Jmw`Radio');//获取临时播放列表失败,执行下一次操作
			if (isset($room['userid'])) {// 检查该房间是否具有在线用户
				$message = $first_music['music_name']." 获取播放权限失败!";
			    $response = json_encode(array("code" => "VIP","message" => $message), JSON_UNESCAPED_UNICODE);
			    $online_users = $room['userid'];// 获取当前房间在线用户列表
			    foreach ($online_users as $userid) {
			        sendMessageByUid($userid, $response);
			    }
			}
			return;
		}
		$room['music'] = [//电台播放信息
			'music_name' => $first_music['music_name'],//当前播放歌曲名称
			'music_singer' => $first_music['music_singer'],//当前播放歌曲歌手名
			'music_source' => $first_music['music_source'],//当前播放歌曲来源平台
			'music_url' => $ls_url['url'],//当前播放歌曲链接
			'music_img' => $first_music['music_img'],//当前播放歌曲封面
			'music_user' => $first_music['music_user'],//当前播放歌曲点歌人
			'music_time' => 30,//初始化歌曲总时长30秒
			'music_times' => 0,//当前播放歌曲播放时长
			'music_type' => 'user',//更新为用户播放模式
			'music_adjust' => false, // 初始化总时长校准事件,因为QQ音乐没有获取到歌曲总时长
			'music_lyc' => $ls_url['lyc'],//当前播放歌曲歌词
		];
		$redis->hSet('rooms', $room_id, json_encode($room, JSON_UNESCAPED_UNICODE));
	} else {
		global $root_music_list;
		if (!empty($root_music_list[0]) && count($root_music_list) > 0) {//系统临时播放列表不为空
			$first_music = reset($root_music_list); // 获取第一个元素
			array_shift($root_music_list); // 删除第一个元素
			var_dump($first_music);
			$ls_url = song_url_wyy($first_music['music_id']); // 获取播放链接
			if ($ls_url['code'] != '200') {
			    play_next_song($redis,$room_id,'Jmw`Radio');//获取临时播放列表失败,执行下一次操作
				if (isset($room['userid'])) {// 检查该房间是否具有在线用户
					$message = $first_music['music_name']." 获取播放权限失败!";
				    $response = json_encode(array("code" => "VIP","message" => $message), JSON_UNESCAPED_UNICODE);
				    $online_users = $room['userid'];// 获取当前房间在线用户列表
				    foreach ($online_users as $userid) {
				        sendMessageByUid($userid, $response);
				    }
				}
				return;
			}
			$room['music'] = [//电台播放信息
				'music_name' => $first_music['music_name'],//当前播放歌曲名称
				'music_singer' => $first_music['music_singer'],//当前播放歌曲歌手名
				'music_source' => $first_music['music_source'],//当前播放歌曲来源平台
				'music_url' => $ls_url['url'],//当前播放歌曲链接
				'music_img' => $first_music['music_img'],//当前播放歌曲封面
				'music_user' => "Jmw`Radio",//当前播放歌曲点歌人
				'music_time' => 30,//初始化歌曲总时长30秒
				'music_times' => 0,//当前播放歌曲播放时长
				'music_type' => 'root',//更新为用户播放模式
				'music_adjust' => false, // 初始化总时长校准事件,因为QQ音乐没有获取到歌曲总时长
				'music_lyc' => $ls_url['lyc'],//当前播放歌曲歌词
			];
			$redis->hSet('rooms', $room_id, json_encode($room, JSON_UNESCAPED_UNICODE));
		}else{
			get_root_music_list();//Get系统播放列表
			sleep(3);  // 暂停 3 秒后执行切歌
			play_next_song($redis,$room_id,'Jmw`Radio');//获取临时播放列表失败,执行下一次操作
			return;
		}
	}
	if (isset($room['userid'])) {// 检查该房间是否具有在线用户
	    $response = json_encode(array("code" => "切歌刷新","message" => $users." 执行了切歌操作","music" => $room['music'],"music_list" => $room['music_list']), JSON_UNESCAPED_UNICODE);
	    $online_users = $room['userid'];// 获取当前房间在线用户列表
	    foreach ($online_users as $userid) {
	        sendMessageByUid($userid, $response);
	    }
	}
}
// 播放下一首歌曲 结束

//房间数据查询 开始
function get_rooms($redis, $room_ids) {
    $rooms = $redis->hGetAll('rooms');
    $result = []; $filterFields = ['password', 'userid', 'ban', 'music_list', 'music'];//屏蔽输出
    foreach ($rooms as $key => $value) {
        $room = json_decode($value, true);
        $newRoom = [];
        if ($room['id'] == $room_ids) {
			$result['room'] = ['id' => $room['id'],'name' => $room['name'],'msg' => $room['msg'],'user' => $room['user'],'number' => $room['number']];
			$result['music'] = $room['music'];$result['music']['music_list'] = $room['music_list'];
        }
		foreach ($room as $k => $v) {
			if (!in_array($k, $filterFields)) {
				$newRoom[$k] = $v;
			}
		}
        $result['rooms'][] = $newRoom;
    }
    return $result;
}
//房间数据查询 结束

//get 临时播放列表 开始
function get_root_music_list() {
	global $root_music_list;
    $music_list = get_lslist(); // 获取临时播放列表
    $root_music_list = $music_list['list'];
	//var_dump($root_music_list);
}
//get 临时播放列表 结束

//ip地址解析 开始
$ip_datas = []; // 初始化数组存储已查询的ip
function query_ip_location($ip) {
    global $ip_datas;
    if (isset($ip_datas[$ip])) { // 如果已经查询过该ip
        return $ip_datas[$ip]; // 直接返回它的地理位置
    }
    $url = 'https://api.vore.top/api/IPdata?ip=' . $ip;// 否则查询该ip并将结果存入$ip_datas
    $response = file_get_contents($url);
    $result = json_decode($response, true);
	if (isset($result['code']) && $result['code'] == 200) {
	    $ip_info = $result['ipdata']['info1'] . '-' . $result['ipdata']['info2'];
	} else {
	    $ip_info = '未知 - 隐身';
	}
	$ip_datas[$ip] = $ip_info;
    return $ip_info;
}
//ip地址解析 结束

//违禁字屏蔽 开始
$json_content = file_get_contents('mgz.json'); // 读取违禁字json文件内容
$list = json_decode($json_content, true);// 将json字符串解析为数组
function DoFilterWords($string) {
	global $list;
    foreach ($list as $word) {// 遍历违禁词数组，替换为“**”
		$length = mb_strlen($word);
		$redaction = '';
		for ($i = 0; $i < $length; $i++) {
		    $redaction .= '*';  
		}
		$string = str_replace($word, $redaction, $string);
    }
    return $string;// 返回屏蔽后的字符串
}
//违禁字屏蔽 结束

//curl
function get_curl($url){
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); 
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE); 
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch,CURLOPT_HTTPHEADER,array("Content-type:application/json;","Accept:application/json"));
	$output = curl_exec($ch);
	curl_close($ch);
	$output = json_decode($output,true);
	return $output;
}

// 运行worker
Worker::runAll();
?>