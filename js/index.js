$(document).ready(function() {
    $('#music_lists').empty();
    $('#search_music_list').empty();
    $('#search_music_list_gd').empty();
    $('#rooms_div').empty();
   get_room_id = $.cookie('room_id');
   get_room_password = $.cookie('room_password');
   if (!get_room_id) {
       get_room_id = '666';
   }
   if (!get_room_password) {
       get_room_password = null;
   }
});
readyState = false;
Socket_uid = '';
lyricArray = [];

function Socket_io() {
    layer.msg('<div class="text-danger">正在链接服务器```</div>', {
        icon: 16,
        shade: 0.01
    });
    if ("WebSocket" in window) {
        Socket = new WebSocket("ws://1.116.97.75:34567"); // 打开一个 web socket
        Socket.onopen = function() { //监听是否连接成功
            console.log('ws连接状态：' + Socket.readyState);
            if (Socket.readyState == 1) { //连接成功
                $('#network').attr('class', 'badge spinner-grow text-success');
                layer.closeAll();
                $('.logo').attr("title", "当前服务器链接正常");
                readyState = true;
            }
        }

        Socket.onmessage = function(e) { // 接听服务器发回的信息并处理展示
            var received_msg = e.data;
            received_msg = JSON.parse(received_msg);
            console.log(received_msg);
            switch (received_msg.code) {
                case 'uid': //返回用户UID
                    Socket_uid = received_msg.uid;
                    Socket_data = {
                        'email': user_email,
                        'name': user_name,
                        'token': user_token,
                        'uid': Socket_uid,
                        'type': "加入房间",
                        'room_id': get_room_id,
                        'room_password': get_room_password
                    };
                    Socket.send(JSON.stringify(Socket_data));
                    break;
                case '建房成功': //返回用户UID
                    $('#add_rooms_divs').modal('hide');
                    layer.msg('建房成功,即将为您转入新房间!');
                    Socket_data = {
                        'email': user_email,
                        'name': user_name,
                        'token': user_token,
                        'uid': Socket_uid,
                        'type': "加入房间",
                        'room_id': received_msg.id,
                        'room_password': received_msg.ps
                    };
                    Socket.send(JSON.stringify(Socket_data));
                    $.cookie('room_id', received_msg.id);
                    $.cookie('room_password', received_msg.ps);
                    $('.sidebar-close').click();
                    break;
                case '加入房间回传':
                    $('#chat_messages').empty();
                    layer.msg('进入房间成功,请文明聊天!');
                    $('#chat_messages').append(`<div class="message-item messages-divider" data-label="Jmw\`Radio 欢迎您"></div>`);
                    $('#chat_messages').append(`<div class="message-item messages-divider" data-label="请遵守法律法规,文明发言!"></div>`);
                    scrollToBottom(); //有新消息下拉到底部
                    get_room_id = received_msg.room_id;
                    get_room_password = received_msg.room_password;
                    $.cookie('room_id', received_msg.room_id);
                    $.cookie('room_password', received_msg.room_password);
                    renew_room(received_msg.rooms.rooms); //更新房间列表
                    renew_rooms(received_msg.rooms.room); //更新房间信息
                    renew_music(received_msg.rooms.music); //更新房间当前播放歌曲信息
                    renew_music_list(received_msg.rooms.music.music_list); //更新房间已点播放歌曲列表
                    break;
                case '刷新':
                    Refresh(received_msg); //刷新事件
                    break;
                case '搜索回传':
                    append_search_list(received_msg);
                    break;
                case '歌单搜索回传':
                    append_regeb_list(received_msg);
                    break;
                case '歌单详情':
                    $('#dg_nav').click();
                    append_search_list(received_msg);
                    break;
                case '热歌榜回传':
                    append_regeb_list(received_msg);
                    break;
                case 'VIP':
                    $('#chat_messages').append(`<div class="message-item messages-divider" data-label="${received_msg.message}"></div>`);
                    scrollToBottom(); //有新消息下拉到底部
                    break;
                case '点歌刷新':
                    $('#chat_messages').append(`<div class="message-item messages-divider" data-label="${received_msg.message}"></div>`);
                    scrollToBottom(); //有新消息下拉到底部
                    renew_music_list(received_msg.music_list); //更新房间已点播放歌曲列表
                    break;
                case '顶歌刷新':
                    $('#chat_messages').append(`<div class="message-item messages-divider" data-label="${received_msg.message}"></div>`);
                    scrollToBottom(); //有新消息下拉到底部
                    renew_music_list(received_msg.music_list); //更新房间已点播放歌曲列表
                    break;
                case '切歌刷新':
                    $('#chat_messages').append(`<div class="message-item messages-divider" data-label="${received_msg.message}"></div>`);
                    scrollToBottom(); //有新消息下拉到底部
                    renew_music(received_msg.music); //更新房间当前播放歌曲信息
                    renew_music_list(received_msg.music_list); //更新房间已点播放歌曲列表
                    break;
                case '投票切歌':
                    if (received_msg.user != user_name) {
                        layer.msg(`${received_msg.user} 表示对当前这首歌不是很感冒!<br>同意切歌请点击下方按钮!`, {
                            offset: 'rt', // 显示在右上角
                            time: 5000, // 5秒后关闭
                            btn: ['同意切歌', '才不要切'], // 添加两个按钮
                            yes: function(index) { // 监听第一个按钮点击事件
                                layer.close(index);
                                qiege2(received_msg.token);
                            },
                            btn2: function(index) { // 监听第二个按钮点击事件
                                layer.close(index);
                            }
                        });
                    }
                    break;
                case '播放校时':
                    var duration = myAudio.duration;
                    if (isNaN(duration)) {
                        duration = 1;
                    }
                    Socket_data = {
                        'name': user_name,
                        'uid': Socket_uid,
                        'type': "播放校时",
                        'room_id': get_room_id,
                        'time': duration
                    };
                    Socket.send(JSON.stringify(Socket_data));
                    break;
                case '消息':
                    append_msg(received_msg);
                    break;
                case '000': //回传提示信息
                    layer.alert('<div class="text-body">' + received_msg.message + '</div>', {
                        title: "Jmw`Radio (提醒)"
                    });
                    break;
                default:
                    break;
            }
        }

        Socket.onclose = function() { // 关闭 websocket
            $('#network').attr('class', 'badge spinner-grow text-danger');
            $('.logo').attr("title", "点击链接服务器");
            readyState = false;
            document.getElementById("audio-player").pause();
            layer.alert('<div class="text-body">WebSocket 掉线或链接失败,请刷新重连!</div>', {
                title: "WebSocket 链接掉线"
            });
        };

        // 监听并处理error事件，如果出现连接、处理、接收、发送数据失败的时候触发onerror事件
        Socket.onerror = function(error) {
            console.log(error);
        }
    } else { // 浏览器不支持 WebSocket
        layer.alert('<div class="text-body">很抱歉,您的浏览器不支持 WebSocket!</div>', {
            title: "浏览器不支持 WebSocket"
        });
    }
}

// 获取 DOM 元素
const myAudio = document.getElementById('audio-player'); //音乐播放容器
const musicIng = document.getElementById('music_ing'); //当前播放时间进度
const musicEnd = document.getElementById('music_end'); //当前歌曲时长
const progressBar = document.querySelector('.progress-bar'); //进度条容器
const music_lycs = document.getElementById("music_lycs"); //歌词容器
const volumeControl = document.getElementById('volume_control'); //音量显示容器
const volumeValue = document.getElementById('volume_value'); //音量调节容器
const radioImg = document.querySelector('.cd');
volumeValue.value = 50; // 初始化音量
volumeValue.addEventListener('input', function() { // 监听音量滑块值的变化
    volumeControl.textContent = this.value; // 更新音量数值显示
    myAudio.volume = this.value / 100; // 更新音量大小
});

function formatTime(time) { // 格式化时间，将秒数转为分秒格式
    const minutes = Math.floor(time / 60);
    const seconds = Math.floor(time % 60);
    const formattedMinutes = (minutes < 10 ? '0' : '') + minutes;
    const formattedSeconds = (seconds < 10 ? '0' : '') + seconds;
    return `${formattedMinutes}:${formattedSeconds}`;
}
setInterval(updateProgress, 1000); // 每秒钟更新一次播放进度和已播放时间
function updateProgress() { // 更新播放进度和已播放时间及歌词
    if (myAudio.paused) { //不在播放状态,跳出
        radioImg.classList.remove('rotate');
        return;
    } else {
        radioImg.classList.add('rotate');
    }
    const currentTime = myAudio.currentTime;
    const formattedCurrentTime = formatTime(currentTime);
    musicIng.textContent = formattedCurrentTime;
    const duration = myAudio.duration;
    const formattedDuration = formatTime(duration);
    musicEnd.textContent = formattedDuration;
    if (duration) {
        const progress = (currentTime / duration) * 100;
        progressBar.style.width = progress + '%';
    }
    if (lyricArray) {
        const currentLyricObj = lyricArray.find(obj => obj.time > (currentTime - 3)); //莫名偏移修正
        if (currentLyricObj) {
            music_lycs.textContent = currentLyricObj.lyric;
        }
    } else {
        music_lycs.textContent = '未获取到歌词信息';
    }
}

function lyricText(lyricText) { // 解析歌词字符串，将时间和歌词分离
    if (lyricText && lyricText != '获取歌词文件失败') {
        lyricArray = lyricText.split('\n').map(line => {
            const timeAndLyric = line.split(']');
            const timeString = timeAndLyric[0].substring(1);
            const lyric = timeAndLyric[1] ? timeAndLyric[1].trim() : '';
            const minutes = parseInt(timeString.substring(0, 2));
            const seconds = parseFloat(timeString.substring(3));
            const time = minutes * 60 + seconds;
            return {
                time,
                lyric
            };
        });
    } else {
        lyricArray = [];
        $('#music_lycs').html('未获取到歌词信息');
    }
}
//表情处理 start
pattern = ["[亲亲]", "[冷汗]", "[吐]", "[哼]", "[大哭]", "[大笑]", "[奸笑]", "[委屈]", "[害羞]", "[帅气]", "[怒骂]", "[思考]", "[惊吓]", "[捂脸哭]", "[柠檬]", "[棒]", "[没眼看]", "[流鼻涕]", "[滑稽]", "[狗头]", "[猫头]", "[生气]", "[疑问]", "[白眼]", "[笑哭]", "[色]", "[鄙视]"];
const newPattern = pattern.map(str => str.replace(/\[|\]/g, ''));
$('#emoji_div').empty();
for (var i = 0; i < pattern.length; i++) {
    $('#emoji_div').append('<img src="./css/emoji/' + newPattern[i] + '.png" alt="error" title="' + newPattern[i] + '" class="emoji" onclick="add_emoji(\'' + pattern[i] + '\')" />');
}

function add_emoji(emoji) {
    $("#str").val($("#str").val() + emoji);
    $("#str").focus();
}
//表情处理 end

//登录切换事件 start
function login_div() {
    $("#login_title").html('账户登录');
    $("#login-div").show();
    $("#register-div").hide();
    $("#password-div").hide();
}

function register_div() {
    $("#login_title").html('账户注册');
    $("#register-div").show();
    $("#login-div").hide();
    $("#password-div").hide();
}

function password_div() {
    $("#login_title").html('修改密码');
    $("#password-div").show();
    $("#login-div").hide();
    $("#register-div").hide();
}
//登录切换事件 end

document.getElementById("str").addEventListener("keydown", function(event) { //点击ctrl+回车键激活发送按钮
    if (event.ctrlKey && event.keyCode == 13) {
        event.preventDefault(); // 防止换行
        document.getElementById("msg_btn").click(); // 触发发送按钮的点击事件
    }
});

//更新房间列表 start
function renew_room(room) {
    $('#rooms_div').empty();
    for (var i = 0; i < room.length; i++) {
        if (room[i].state == true) { //true=永久,false=临时
            var state = 'bi-clock';
            var states = '永久房';
        } else {
            var state = 'bi-clock-history';
            var states = '临时房';
        }
        if (room[i].lock == true) { //true=密码房,false=开放房
            var lock = 'bi-house-lock';
            var locks = '密码房';
        } else {
            var lock = 'bi-house-heart';
            var locks = '公开房';
        }
        if (room[i].power == true) { //true=禁止游客点歌发言,false=允许游客点歌发言
            var power = '禁止游客';
        } else {
            var power = '允许游客';
        }
        var room_title = "房间ID: " + room[i].id + "\n\n房间名: " + room[i].name + "\n\n房间介绍: " + room[i].msg + "\n\n房间人数: " + room[i].number + "\n\n创建者: " + room[i].user + "\n\n" + states + " | " + locks + " | " + power;
        var li = `<li class="list-group-item" title="${room_title}" onclick="join_room('${room[i].id}','${room[i].lock}');">
				<div class="musics-list-body">
					<div>
						<h5 class="text-primary">${room[i].name}</h5>
						<p><i class="bi bi-headphones" title="房间人数"></i>&nbsp;<small>${room[i].number}</small>&nbsp;&nbsp;
							<small class="text-light bg-secondary">ID:${room[i].id}</small> ${room[i].msg}</p>
					</div>
					<div class="musics-list-action">
						<div class="music_list_icon">
							<i class="bi ${state}" title="${states}"></i>&nbsp;&nbsp;
							<i class="bi ${lock}" title="${locks}"></i>&nbsp;&nbsp;
						</div>
						<p class="text-secondary" title="创建者">${room[i].user}</p>
					</div>
				</div>
			</li>`;
        $('#rooms_div').append(li);
    }
}
//更新房间列表 end

//加入房间事件 start
function join_room(id, lock) {
    Socket_data = {
        'email': user_email,
        'name': user_name,
        'token': user_token,
        'uid': Socket_uid
    };
    Socket_data["type"] = "加入房间";
    Socket_data["room_id"] = id;
    if (lock == 'true') {
        layer.prompt({
            title: '此房间为密码房,请键入密码并确认',
            formType: 1
        }, function(room_password, index) {
            layer.close(index);
            layer.msg('<div class="text-danger">验证加入房间中,请稍候```</div>', {
                icon: 16,
                shade: 0.01
            });
            Socket_data["room_password"] = room_password;
            Socket.send(JSON.stringify(Socket_data));
        });
    } else {
        layer.msg('<div class="text-danger">验证加入房间中,请稍候```</div>', {
            icon: 16,
            shade: 0.01
        });
        Socket_data["room_password"] = '';
        Socket.send(JSON.stringify(Socket_data));
    }
}
//加入房间事件 end

//更新当前房间信息 start
function renew_rooms(room) {
    $('#room_name').html(room.name);
    $('#room_number').html(room.number);
    $('#room_name').attr("title", "房间ID: " + room.id + "\n\n房间名: " + room.name + "\n\n房间介绍: " + room.msg + "\n\n房间人数: " + room.number + "\n\n创建者: " + room.user);
}
//更新当前房间信息 end

//更新当前播放歌曲事件 start
function renew_music(music) {
    $('#music_name').html(music.music_name);
    $('#singer_name').html(music.music_singer);
    $('#music_name_singer').html(music.music_name + " - " + music.music_singer);
    document.title = music.music_name + " - " + music.music_singer; //更新网页标题
    $('#user_names').html(music.music_user);
    $('#music_ing').html('00:00');
    $('#music_end').html('00:00');
    $("#radio_img").attr("src", music.music_img); //更新歌曲封面
    if (music.music_source == '网易云') { //更新歌曲名字体颜色
        $('#music_name_div').attr("class", 'text-danger');
    } else {
        $('#music_name_div').attr("class", 'text-success');
    }
    $('#list_name').html(music.music_name);
    $('#list_singer').html(music.music_singer);
    $('#list_user').html(music.music_user);
    $("#list_img").attr("src", music.music_img); //更新歌曲封面
    $('#zzbf').attr("title", "歌曲名: " + music.music_name + "\n\n歌手名: " + music.music_singer + "\n\n点播人: " + music.music_user);
    lyricText(music.music_lyc); //解析转码歌词
    $("#audio-player").attr("src", music.music_url);
    myAudio.currentTime = music.music_times;
    myAudio.play();
}
//更新当前播放歌曲事件 end

//更新当前房间播放列表 start
function renew_music_list(music_list) {
    console.log(music_list);
    $('#music_lists').empty();
    for (let i = 0; i < music_list.length; i++) {
        const {
            music_id,
            music_name,
            music_singer,
            music_user,
            music_img
        } = music_list[i];
        const music_title = `歌曲名: ${music_name}\n\n歌手名: ${music_singer}\n\n点播人: ${music_user}`;
        const isTop = i > 0;
        const li = `<li class="list-group-item list-group-item-action" title="${music_title}">
	                <picture>
	                  <img src="${music_img}" class="img-fluid img-thumbnail" alt="封面">
	                </picture>&nbsp;&nbsp;
	                <div class="musics-list-body">
	                  <div>
	                    <h5>${music_name}</h5>
	                    <p>${music_singer}</p>
	                  </div>
	                  <div class="musics-list-action">
	                    ${isTop ? `<div class="action-toggle" title="顶歌" onclick="dingge_song('${music_id}')"><i class="bi bi-arrow-up-circle"></i></div>` : `<div class="action-toggle"><i class="bi bi-1-square"></i></div>`}
	                    <p class="text-secondary">${music_user}</p>
	                  </div>
	                </div>
	              </li>`;
        $('#music_lists').append(li);
    }
}
//更新当前房间播放列表 end

//刷新事件 start
function Refresh(data) {
    renew_room(data.rooms.rooms); //更新房间列表
    renew_rooms(data.rooms.room); //更新房间信息
    renew_music_list(data.rooms.music.music_list); //更新房间已点播放歌曲列表
    if ($('#music_name').html() != data.rooms.music.music_name) { //歌曲名称不同,执行更新music信息
        renew_music(data.rooms.music);
    }
    var music_date1 = data.rooms.music.music_times;
    var music_date2 = myAudio.currentTime;
    if (music_date1 - music_date2 > 10 || music_date1 - music_date2 < -10) {
        myAudio.currentTime = music_date1;
        console.log('系统时间:' + music_date1 + " | 前端时间:" + music_date2 + " | 时间差值:" + (music_date1 - music_date2) + "执行时间同步更新操作!");
        myAudio.play();
    }
}
//刷新事件 end

//搜索事件 start
$("#search_btn").click(function() { //搜索事件
    var search_strs = $("#search_str").val();
    if (search_strs.replace(/(^\s*)|(\s*$)/g, "") == "") {
        layer.alert('<div class="text-body">搜索内容不能为空!</div>', {
            title: "发现错误"
        });
        return;
    } else {
        if (readyState == false) {
            layer.alert('<div class="text-body">请先链接服务器!</div>', {
                title: "Jmw`Radio"
            });
            return;
        }
        if (Socket_uid) {
            Socket_data = {
                'email': user_email,
                'name': user_name,
                'token': user_token,
                'uid': Socket_uid,
                'type': "单曲搜索",
                'room_id': get_room_id
            };
            Socket_data["search_strs"] = search_strs;
            Socket_data["search"] = $("input[name='search']:checked").val();
            Socket.send(JSON.stringify(Socket_data));
        } else {
            layer.alert('<div class="text-body">获取用户 ID 失败,请您刷新后重试!</div>', {
                title: "Jmw`Radio"
            });
            return;
        }
    }
});
//搜索事件 end

//搜索回传事件 start
function append_search_list(received_msg) {
    if (received_msg.search_list.code == 200) {
        const list = received_msg.search_list.list;
        $('#search_music_list').empty();
        for (let i = 0; i < list.length; i++) {
            const item = list[i];
            $('#search_music_list').append(`
				<li class="list-group-item list-group-item-action" onclick="diange_song('${item.id}','${item.source}','${item.name}','${item.singer}','${item.img}')">
				    <div class="musics-list-body">
						<div>
							<h5>${item.name}</h5>
							<p>${item.singer}</p>
						</div>
				        <div class="musics-list-action">
				            <div class="action-toggle" title="点歌"><i class="bi bi-plus-lg"></i></div>
				            <p class="text-secondary">${received_msg.search_list.search}</p>
				        </div>
				    </div>
				</li>`);
        }
        $('#search_music_list_DIV').scrollTop(0);
    } else {
        layer.alert('<div class="text-body">搜索返回错误,请您稍后重试!</div>', {
            title: "Jmw`Radio"
        });
    }
}
//搜索回传事件 end

//热歌榜回传事件 start
function append_regeb_list(received_msg) {
    if (received_msg.search_list.code == 200) {
        const list = received_msg.search_list.list;
        $('#search_music_list_gd').empty();
        for (let i = 0; i < list.length; i++) {
            const item = list[i];
            $('#search_music_list_gd').append(`
				<li class="list-group-item list-group-item-action" onclick="bangdan_list('${item.id}','${item.source}')" title="点击查看歌单内歌曲">
					<div class="musics-list-body">
						<div>
							<h5>${item.name}</h5>
							<p>${item.description}</p>
						</div>
						<div class="musics-list-action">
							<div class="action-toggle"><i class="bi bi-file-earmark-music"></i></div>
							<p class="text-secondary">${item.source}</p>
						</div>
					</div>
				</li>`);
        }
        $('#search_music_list_gd').scrollTop(0);
    } else {
        layer.alert('<div class="text-body">搜索返回错误,请您稍后重试!</div>', {
            title: "Jmw`Radio"
        });
    }
}
//热歌榜回传事件 end

//查看歌单内容事件 start
function bangdan_list(id, source) {
	if (!readyState) {
	    layer.alert('<div class="text-body">请先链接服务器!</div>', {
	        title: "Jmw`Radio"
	    });
	    return;
	}
    layer.msg('<div class="text-body">正在为您查询歌单内容,请稍候!</div>', {
        icon: 16,
        shade: 0.01
    });
    Socket_data = {
        'email': user_email,
        'name': user_name,
        'token': user_token,
        'uid': Socket_uid,
        'type': "歌单列表",
        'room_id': get_room_id
    };
    Socket_data["gedan_id"] = id;
    Socket_data["gedan_source"] = source;
    Socket.send(JSON.stringify(Socket_data));
}
//查看歌单内容事件 end

//点歌事件 start
function diange_song(id, source, name, singer, img) {
	if (!readyState) {
	    layer.alert('<div class="text-body">请先链接服务器!</div>', {
	        title: "Jmw`Radio"
	    });
	    return;
	}
    layer.msg('<div class="text-body">正在为您点播中,请稍候!</div>', {
        icon: 16,
        shade: 0.01
    });
    Socket_data = {
        'email': user_email,
        'name': user_name,
        'token': user_token,
        'uid': Socket_uid,
        'type': "点歌",
        'room_id': get_room_id
    };
    Socket_data["song_id"] = id;
    Socket_data["song_source"] = source;
    Socket_data["song_name"] = name;
    Socket_data["song_singer"] = singer;
    Socket_data["song_img"] = img;
    Socket.send(JSON.stringify(Socket_data));
}
//点歌事件 end

//切歌事件 start
$("#qiege_btn").click(function() {
	if (!readyState) {
	    layer.alert('<div class="text-body">请先链接服务器!</div>', {
	        title: "Jmw`Radio"
	    });
	    return;
	}
    Socket_data = {
        'email': user_email,
        'name': user_name,
        'token': user_token,
        'uid': Socket_uid,
        'type': "切歌",
        'room_id': get_room_id
    };
    layer.msg(`亲~这边建议不要轻易切别人的歌哦!<br>确认切歌请点击下方按钮!`, {
        offset: 'rt', // 显示在右上角
        time: 5000, // 5秒后关闭
        btn: ['我要切歌', '我点错了'], // 添加两个按钮
        yes: function(index) { // 监听第一个按钮点击事件
            layer.close(index);
            Socket.send(JSON.stringify(Socket_data));
        },
        btn2: function(index) { // 监听第二个按钮点击事件
            layer.close(index);
        }
    });
});

function qiege2(code) {
    Socket_data = {
        'email': user_email,
        'name': user_name,
        'token': user_token,
        'uid': Socket_uid,
        'type': "切歌",
        'room_id': get_room_id,
        'change_song_codes': code
    };
    Socket.send(JSON.stringify(Socket_data));
}
//切歌事件 end

//顶歌事件 start
function dingge_song(id) {
	if (!readyState) {
	    layer.alert('<div class="text-body">请先链接服务器!</div>', {
	        title: "Jmw`Radio"
	    });
	    return;
	}
    Socket_data = {
        'email': user_email,
        'name': user_name,
        'token': user_token,
        'uid': Socket_uid,
        'type': "顶歌",
        'room_id': get_room_id,
        "song_id": id
    };
    Socket.send(JSON.stringify(Socket_data));
}
//顶歌事件 end

//发送消息 start 
$("#msg_btn").click(function() {
	if (!readyState) {
	    layer.alert('<div class="text-body">请先链接服务器!</div>', {
	        title: "Jmw`Radio"
	    });
	    return;
	}
    var msg_str = $('#str').val();
    msg_str = msg_str.replace(/\/\*[\s\S]*?\*\/|\/\/.*/g, ''); // 删除注释
    msg_str = msg_str.replace(/\s+/g, ' ');
    Socket_data = {
        'email': user_email,
        'name': user_name,
        'token': user_token,
        'uid': Socket_uid,
        'type': "消息",
        'room_id': get_room_id,
        'msg': msg_str
    };
    Socket.send(JSON.stringify(Socket_data));
    $("#str").val("");
});
//发送消息 end

//接收消息 start 
function append_msg(received_msg) {
    if (received_msg.user == user_name) {
        var add_class1 = 'message message-item';
        var add_class2 = 'message-content bg-primary-subtle';
    } else {
        var add_class1 = 'message self message-item outgoing-message';
        var add_class2 = 'message-content bg-primary';
    }
    var user_SVG = 'data:image/svg+xml;base64,' + btoa(encodeURIComponent(multiavatar(received_msg.user)).replace(/%([0-9A-F]{2})/g, (match, p1) => String.fromCharCode('0x' + p1))); // 将SVG字符串编码为Base64
    message = replaceEmoji(received_msg.message);
    message = message.replace(/<script>/g, "《script》"); //防止js代码被执行
    $('#chat_messages').append(`<div class="${add_class1}">
							<div class="message-wrapper">
								<div class="${add_class2}">
									<span> ${message} <br><p>${received_msg.date}</p></span>
								</div>
							</div>
							<div class="message-options">
								<div class="avatar avatar-sm"><img class="img" alt="头像" src="${user_SVG}"></div>
								<p>[${received_msg.ip}] ${received_msg.user}</p>
							</div>
						</div>`);
    scrollToBottom(); //有新消息下拉到底部
}
const replaceEmoji = (str) => { //处理emoji
    return str.replace(/\[(.*?)\]/g, (match, p1) => {
        if (pattern.includes(match)) {
            return `<img src="./css/emoji/${p1}.png" alt="error" title="${p1}" class="emoji2" />`;
        }
        return match;
    });
};
var msgDiv = document.querySelector('.chat-body');

function scrollToBottom() {
    msgDiv.scrollTop = msgDiv.scrollHeight;
}
//接收消息 end

//静音
$('#jinyin').click(function() { // 添加按钮的点击事件
    myAudio.muted = !myAudio.muted; // 切换静音状态
    if (myAudio.muted) { // 更新按钮的文本和图标
		$('#jinyin').html(`<small class="badge rounded-pill text-bg-danger"><i class="bi bi-volume-mute-fill"></i> 静音</small>`);
		layer.msg('已静音,再次点击取消静音');
    } else {
		$('#jinyin').html(`<small class="badge rounded-pill text-bg-secondary"><i class="bi bi-volume-mute"></i> 静音</small>`);
		layer.msg('已恢复,再次点击静音');
    }
});
//静音

//加入房间按钮
$('#room_add_btn').click(function() {
    if (!readyState) {
        layer.alert('<div class="text-body">请先链接服务器!</div>', {
            title: "Jmw`Radio"
        });
        return;
    }
	room_add_id = $('#room_add_id').val();
	room_add_ps = $('#room_add_ps').val();
	if (!room_add_id) {
		layer.alert('<div class="text-body">房间ID不能为空!</div>', {
			title: "发现错误"
		});
		return;
	} else {
		Socket_data = {
			'email': user_email,
			'name': user_name,
			'token': user_token,
			'uid': Socket_uid
		};
		Socket_data["type"] = "加入房间";
		Socket_data["room_id"] = room_add_id;
		Socket_data["room_password"] = room_add_ps;
		Socket.send(JSON.stringify(Socket_data));
	}
});
//加入房间按钮

//新建房间按钮
$('#add_room_btn').click(function() {
	if (!readyState) {
	    layer.alert('<div class="text-body">请先链接服务器!</div>', {
	        title: "Jmw`Radio"
	    });
	    return;
	}
    add_rooms_id = $('#add_rooms_id').val();
    add_rooms_msg = $('#add_rooms_msg').val();
    add_rooms_ps = $('#add_rooms_ps').val();
    add_rooms_tk = $('#add_rooms_tk').val();
    var add_rooms_power = $("#add_rooms_power");
    if (add_rooms_power.prop("checked")) {
        add_rooms_power = true; //true=禁止游客点歌发言,false=允许游客点歌发言
    } else {
        add_rooms_power = false; //true=禁止游客点歌发言,false=允许游客点歌发言
    }
    if (!add_rooms_id || !add_rooms_msg) {
        layer.alert('<div class="text-body">房间名或简介不能为空!</div>', {
            title: "发现错误"
        });
        return;
    } else {
        Socket_data = {
            'email': user_email,
            'name': user_name,
            'token': user_token,
            'uid': Socket_uid,
            'room_id': get_room_id
        };
        Socket_data["type"] = "新建房间";
        Socket_data["add_rooms_id"] = add_rooms_id;
        Socket_data["add_rooms_msg"] = add_rooms_msg;
        Socket_data["add_rooms_ps"] = add_rooms_ps;
        Socket_data["add_rooms_tk"] = add_rooms_tk;
        Socket_data["add_rooms_power"] = add_rooms_power;
        Socket.send(JSON.stringify(Socket_data));
    }
});
//新建房间按钮

//热歌榜单查询
$("#rege_btn").click(function() {
    if (!readyState) {
        layer.alert('<div class="text-body">请先链接服务器!</div>', {
            title: "Jmw`Radio"
        });
        return;
    }
    Socket_data = {
        'email': user_email,
        'name': user_name,
        'token': user_token,
        'uid': Socket_uid,
        'room_id': get_room_id,
        "type": "热歌榜"
    };
    Socket_data["search"] = $("input[name='Search_platform']:checked").val();
    Socket.send(JSON.stringify(Socket_data));
});
//热歌榜单查询

//个单查询
$("#gedan_btn").click(function() {
    if (!readyState) {
        layer.alert('<div class="text-body">请先链接服务器!</div>', {
            title: "Jmw`Radio"
        });
        return;
    }
    var search_gd_str = $("#search_gd_str").val();
    if (search_gd_str.replace(/(^\s*)|(\s*$)/g, "") == "") {
        layer.alert('<div class="text-body">搜索内容不能为空!</div>', {
            title: "发现错误"
        });
        return;
    }
    Socket_data = {
        'email': user_email,
        'name': user_name,
        'token': user_token,
        'uid': Socket_uid,
        'room_id': get_room_id,
        "type": "歌单搜索"
    };
    Socket_data["search"] = $("input[name='Search_platform']:checked").val();
    Socket_data["search_str"] = search_gd_str;
    Socket.send(JSON.stringify(Socket_data));
});
//歌单查询