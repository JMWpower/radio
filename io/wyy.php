<?php
$wyy_url = 'http://服务器地址:3000';
function search_song_wyy($search_str) {//搜索歌曲
	global $wyy_url;
    $search_url = $wyy_url."/search?keywords=".urlencode($search_str);
    $search_response = get_curl($search_url);
    if ($search_response['code'] !== 200 || empty($search_response['result']['songs'])) {
        return ['code' => 500];
    }
    $songs_lists = array();
    for ($i = 0; $i < count($search_response['result']['songs']); $i++) {
        $id = $search_response['result']['songs'][$i]['id'];
        $name = $search_response['result']['songs'][$i]['name'];
		$singer = $search_response['result']['songs'][$i]['artists'][0]['name'];
		$img = $search_response['result']['songs'][$i]['album']['artist']['img1v1Url'];
		$cd = $search_response['result']['songs'][$i]['album']['name'];
        if (!empty($id) or !empty($name) or !empty($singer)) {
            $songs_lists[$i] = array('id' => $id, 'name' => $name, 'singer' => $singer, 'cd' => $cd, 'img' => $img, 'source' => '网易云');
        }
    }
	//echo PHP_EOL.json_encode($songs_lists, JSON_UNESCAPED_UNICODE).PHP_EOL;  // 不编码中文
    return ['code' => 200, 'search' => '网易云','list' => $songs_lists];
}

function search_gd_wyy($search_str) {//搜索歌单
	global $wyy_url;
    $search_url = $wyy_url."/search?keywords=".urlencode($search_str)."&type=1000";
    $search_response = get_curl($search_url);
    if ($search_response['code'] !== 200 || empty($search_response['result']['playlists'])) {
        return ['code' => 500];
    }
	$gedans_lists = array();
	for ($i = 0; $i < count($search_response['result']['playlists']); $i++) {
	    $id = $search_response['result']['playlists'][$i]['id'];
	    $name = $search_response['result']['playlists'][$i]['name'];
		$img = $search_response['result']['playlists'][$i]['coverImgUrl'];
		$description = $search_response['result']['playlists'][$i]['description'];
	    if (!empty($id) or !empty($name)) {
	        $gedans_lists[$i] = array('id' => $id, 'name' => $name, 'cd' => $img, 'description' => $description, 'source' => '网易云');
	    }
	}
	//echo PHP_EOL.json_encode($gedans_lists, JSON_UNESCAPED_UNICODE).PHP_EOL;  // 不编码中文
	return ['code' => 200, 'search' => '网易云', 'list' => $gedans_lists];
}

function get_lslist() {//获取推荐歌曲
	global $wyy_url;
    $playlist_url = $wyy_url."/personalized?limit=10";
    $playlist_response = get_curl($playlist_url);
	$random_num = rand(0, 9);//取随机一个歌单
    if ($playlist_response['code'] !== 200 || empty($playlist_response['result'][$random_num]['id'])) {
        return ['code' => 500];
    }
    $playlist_id = $playlist_response['result'][$random_num]['id'];
    $tracks_url = $wyy_url."/playlist/track/all?id=".$playlist_id."&limit=10&offset=1";
    $tracks_response = get_curl($tracks_url);
    if ($tracks_response['code'] !== 200) {
        return ['code' => 500];
    }
    $songs_lists = array();
    for ($i = 0; $i < count($tracks_response['songs']); $i++) {
        $id = $tracks_response['songs'][$i]['id'];
        $name = $tracks_response['songs'][$i]['name'];
    	$singer = $tracks_response['songs'][$i]['ar'][0]['name'];
    	$img = $tracks_response['songs'][$i]['al']['picUrl'];
    	$cd = $tracks_response['songs'][$i]['al']['name'];
        if (!empty($id) or !empty($name) or !empty($singer)) {
            $songs_lists[$i] = array('music_id' => $id, 'music_name' => $name, 'music_singer' => $singer,'music_source' => "网易云", 'cd' => $cd, 'music_img' => $img);
        }
    }
    //echo PHP_EOL.json_encode($songs_lists, JSON_UNESCAPED_UNICODE).PHP_EOL;  // 不编码中文
    return ['code' => 200, 'list' => $songs_lists];
}

function song_url_wyy($songid) {//点歌获取网易云播放链接
	global $wyy_url;
	if ( empty($songid) ) { return ['code' => 500]; }
    $search_url = $wyy_url."/song/url/v1?id=".$songid."&level=standard";
    $song_urls = get_curl($search_url);
    if ($song_urls['code'] !== 200 || empty($song_urls['data'])) {
        return ['code' => 500];
    }else{
		$song_url = $song_urls['data'][0]['url'];
		if ( empty($song_url)) {
			return ['code' => 500];
		}else{
			$lyc = song_lyc_wyy($songid);
			return ['code' => 200,'url' => $song_url,'lyc' => $lyc];
		}
	}
}

function song_lyc_wyy($songid) {//搜索歌词
    global $wyy_url;
    $search_url = $wyy_url."/lyric?id=".$songid;
    $lyc_url = get_curl($search_url);
    if ($lyc_url['code'] !== 200 || empty($lyc_url['lrc']['lyric'])) {
        return '获取歌词文件失败';
    }else{
		return $lyc_url['lrc']['lyric'];
	}
}

function search_bangdan_wyy() {//获取网易云热歌榜
    global $wyy_url;
    $search_url = $wyy_url."/toplist";
    $search_response = get_curl($search_url);
    if ($search_response['code'] !== 200 || empty($search_response['list'])) {
        return ['code' => 500];
    }
	$gedans_lists = array();
	for ($i = 0; $i < count($search_response['list']); $i++) {
	    $id = $search_response['list'][$i]['id'];
	    $name = $search_response['list'][$i]['name'];
		$img = $search_response['list'][$i]['coverImgUrl'];
		$description = $search_response['list'][$i]['description'];
	    if (!empty($id) or !empty($name)) {
	        $gedans_lists[$i] = array('id' => $id, 'name' => $name, 'cd' => $img, 'description' => $description, 'source' => '网易云');
	    }
	}
	//echo PHP_EOL.json_encode($gedans_lists, JSON_UNESCAPED_UNICODE).PHP_EOL;  // 不编码中文
	return ['code' => 200, 'search' => '网易云', 'list' => $gedans_lists];
}

function get_wyygdlist($gdid) {//获取歌单歌曲
    global $wyy_url;
    $playlist_url = $wyy_url."/playlist/track/all?id=".$gdid."&limit=50&offset=1";
    $playlist_response = get_curl($playlist_url);//echo PHP_EOL.json_encode($playlist_response, JSON_UNESCAPED_UNICODE).PHP_EOL;  // 不编码中文
    if ($playlist_response['code'] !== 200 || empty($playlist_response['songs'])) {
        return ['code' => 500];
    }
    $songs_lists = array();
    for ($i = 0; $i < count($playlist_response['songs']); $i++) {
        $id = $playlist_response['songs'][$i]['id'];
        $name = $playlist_response['songs'][$i]['name'];
    	$singer = $playlist_response['songs'][$i]['ar'][0]['name'];
    	$img = $playlist_response['songs'][$i]['al']['picUrl'];
    	$cd = $playlist_response['songs'][$i]['al']['name'];
        if (!empty($id) or !empty($name) or !empty($singer)) {
            $songs_lists[$i] = array('id' => $id, 'name' => $name, 'singer' => $singer, 'cd' => $cd, 'img' => $img, 'source' => '网易云');
        }
    }
    //echo PHP_EOL.json_encode($songs_lists, JSON_UNESCAPED_UNICODE).PHP_EOL;  // 不编码中文
    return ['code' => 200, 'search' => '网易云','list' => $songs_lists];
}
?>