<?php
$qq_api = 'http://服务器地址:3300';
function search_song_qq($search_str) {//搜索歌曲
	global $qq_api;
    $search_url = $qq_api."/search?key=".urlencode($search_str)."&pageNo=1&pageSize=30";
    $search_response = get_curl($search_url);
	//echo json_encode($search_response, JSON_UNESCAPED_UNICODE);  // 不编码中文
    if ($search_response['result'] !== 100 || empty($search_response['data']['list'])) {
        return ['code' => 500];
    }
    $songs_lists = array();
    for ($i = 0; $i < count($search_response['data']['list']); $i++) {
        $id = $search_response['data']['list'][$i]['songmid'];
        $name = $search_response['data']['list'][$i]['songname'];
		$singer = $search_response['data']['list'][$i]['singer'][0]['name'];
		$img = 'https://y.gtimg.cn/music/photo_new/T002R300x300M000'.$search_response['data']['list'][$i]['albummid'].'.jpg';
		$cd = $search_response['data']['list'][$i]['albumname'];
        if (!empty($id) or !empty($name) or !empty($singer)) {
            $songs_lists[$i] = array('id' => $id, 'name' => $name, 'singer' => $singer, 'cd' => $cd, 'img' => $img, 'source' => 'QQ');
        }
    }
	//echo PHP_EOL.json_encode($songs_lists, JSON_UNESCAPED_UNICODE).PHP_EOL;  // 不编码中文
    return ['code' => 200, 'search' => 'QQ', 'list' => $songs_lists];
}

function search_gd_qq($search_str) {//搜索歌单
	global $qq_api;
    $search_url = $qq_api."/search?key=".urlencode($search_str)."&t=2";
    $search_response = get_curl($search_url);
    //echo json_encode($search_response, JSON_UNESCAPED_UNICODE);  // 不编码中文
    if ($search_response['result'] !== 100 || empty($search_response['data']['list'])) {
        return ['code' => 500];
    }
	$gedans_lists = array();
	for ($i = 0; $i < count($search_response['data']['list']); $i++) {
	    $id = $search_response['data']['list'][$i]['dissid'];
	    $name = $search_response['data']['list'][$i]['dissname'];
		$img = $search_response['data']['list'][$i]['imgurl'];
		$description = $search_response['data']['list'][$i]['introduction'];
	    if (!empty($id) or !empty($name)) {
	        $gedans_lists[$i] = array('id' => $id, 'name' => $name, 'cd' => $img, 'description' => $description, 'source' => 'QQ');
	    }
	}
	//echo PHP_EOL.json_encode($gedans_lists, JSON_UNESCAPED_UNICODE).PHP_EOL;  // 不编码中文
	return ['code' => 200, 'search' => 'QQ', 'list' => $gedans_lists];
}

function song_url_qq($songid) {//点歌获取QQ音乐播放链接
	global $qq_api;
	if ( empty($songid) ) { return ['code' => 500]; }
    $search_url = $qq_api."/song/urls?id=".$songid;
    $song_urls = get_curl($search_url);
    //echo json_encode($song_urls, JSON_UNESCAPED_UNICODE);  // 不编码中文
	if ($song_urls['result'] !== 100 || empty($song_urls['data'])) {
	    return ['code' => 500];
	}else{
		$song_url = $song_urls['data'][$songid];
		if ( empty($song_url)) {
			return ['code' => 500];
		}else{
			$lyc = song_lyc_qq($songid);
			return ['code' => 200,'url' => $song_url,'lyc' => $lyc];
		}
	}
}

function song_lyc_qq($songid) {//搜索歌词
    global $qq_api;
    $search_url = $qq_api."/lyric?songmid=".$songid;
    $lyc_url = get_curl($search_url);
    if ($lyc_url['result'] !== 100 || empty($lyc_url['data']['lyric'])) {
        return '获取歌词文件失败';
    }else{
		return $lyc_url['data']['lyric'];
	}
}

function search_bangdan_qq() {//获取QQ热歌榜
    global $qq_api;
    $search_url = $qq_api."/top/category";
    $search_response = get_curl($search_url);
    if ($search_response['result'] !== 100 || empty($search_response['data'])) {
        return ['code' => 500];
    }
	$gedans_lists = array();
	$x = 0;
	for ($i = 0; $i < count($search_response['data']); $i++) {
		foreach ($search_response['data'][$i]['list'] as $item) {
			$id = $item['topId'];
			$name = $item['label'];
			$img = $item['picUrl'];
			$description = 'QQ音乐 - '.$item['label'];
			if (!empty($id) or !empty($name)) {
			    $gedans_lists[$x] = array('id' => $id, 'name' => $name, 'cd' => $img, 'description' => $description, 'source' => 'QQrg');
				$x++;
			}
		}
	}
	//echo PHP_EOL.json_encode($gedans_lists, JSON_UNESCAPED_UNICODE).PHP_EOL;  // 不编码中文
	return ['code' => 200, 'search' => 'QQ', 'list' => $gedans_lists];
}

function get_qqgdlist_rg($gdid) {//获取热歌榜歌曲
	global $qq_api;
    $search_url = $qq_api."/top?id=".$gdid;
    $search_response = get_curl($search_url);
	//echo json_encode($search_response, JSON_UNESCAPED_UNICODE);  // 不编码中文
    if ($search_response['result'] !== 100 || empty($search_response['data']['list'])) {
        return ['code' => 500];
    }
    $songs_lists = array();
    for ($i = 0; $i < count($search_response['data']['list']); $i++) {
        $id = $search_response['data']['list'][$i]['mid'];
        $name = $search_response['data']['list'][$i]['name'];
		$singer = $search_response['data']['list'][$i]['singerName'];
		$img = $search_response['data']['list'][$i]['cover'];
		$cd = 'QQ - 热歌榜';
        if (!empty($id) or !empty($name) or !empty($singer)) {
            $songs_lists[$i] = array('id' => $id, 'name' => $name, 'singer' => $singer, 'cd' => $cd, 'img' => $img, 'source' => 'QQ');
        }
    }
	//echo PHP_EOL.json_encode($songs_lists, JSON_UNESCAPED_UNICODE).PHP_EOL;  // 不编码中文
    return ['code' => 200, 'search' => 'QQ', 'list' => $songs_lists];
}

function get_qqgdlist($gdid) {//获取歌单歌曲
	global $qq_api;
    $search_url = $qq_api."/songlist?id=".$gdid;
    $search_response = get_curl($search_url);
	echo json_encode($search_response, JSON_UNESCAPED_UNICODE);  // 不编码中文
	if ($search_response['result'] !== 100 || empty($search_response['data']['songlist'])) {
	    return ['code' => 500];
	}
    $songs_lists = array();
    for ($i = 0; $i < count($search_response['data']['songlist']); $i++) {
        $id = $search_response['data']['songlist'][$i]['songmid'];
        $name = $search_response['data']['songlist'][$i]['songname'];
		$singer = $search_response['data']['songlist'][$i]['singer'][0]['name'];
		$img = 'https://y.gtimg.cn/music/photo_new/T002R300x300M000'.$search_response['data']['songlist'][$i]['albummid'].'.jpg';
		$cd = $search_response['data']['songlist'][$i]['albumname'];
        if (!empty($id) or !empty($name) or !empty($singer)) {
            $songs_lists[$i] = array('id' => $id, 'name' => $name, 'singer' => $singer, 'cd' => $cd, 'img' => $img, 'source' => 'QQ');
        }
    }
	//echo PHP_EOL.json_encode($songs_lists, JSON_UNESCAPED_UNICODE).PHP_EOL;  // 不编码中文
    return ['code' => 200, 'search' => 'QQ', 'list' => $songs_lists];
}
?>