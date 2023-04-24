$(document).ready(function(){
	user_name = $.cookie('user_name');
	user_email = $.cookie('user_email');
	user_token = $.cookie('user_token');
	if (!user_name || !user_email || !user_token) {
		layer.confirm('<div class="text-secondary">当前未登录,建议您登录账号解锁更多功能!<br>本程序仅供实现效果参考,请勿用作其他用途!</div>', {btn: ['确定'],title:"Jmw`Radio _ 欢迎您!"}, function(){
			layer.closeAll();
			Socket_io();
			user_name = generateRandomNickname(9);
		});
	}else{
		$('#login_0').hide();
		$('#login_1').show();
		layer.confirm('<div class="text-secondary">本程序仅供实现效果参考,请勿用作其他用途!</div>', {btn: ['确定'],title:"Jmw`Radio _ 欢迎您!"}, function(){
			layer.closeAll();
			Socket_io();
		});
	}
	const encodedSVG = 'data:image/svg+xml;base64,' +btoa(encodeURIComponent(multiavatar(user_name)).replace(/%([0-9A-F]{2})/g, (match, p1) => String.fromCharCode('0x' + p1)));// 将SVG字符串编码为Base64
	$("#user_img").attr("src", encodedSVG);
	$("#user_img2").attr("src", encodedSVG);
	$.cookie('user_name', user_name);
	$('#user_name_title').html(user_name);
	console.log(encodedSVG);
});


function generateRandomNickname(length) {
    var cnNames = ["兔斯基", "小樱", "猫咪公主", "克拉莉丝", "美少女战士", "比比琳", "阿克曼", "绫波丽", "可可萝", "三千院凪", "柚子羽", "花泽香菜", "鸣人", "银时", "八重樱", "嘉然", "阿良良木", "藤田咲", "神乐", "小町", "千反田", "铃村爱里", "由比滨结衣", "伊吹萃香", "钉宫理惠", "比企谷八幡", "小岛秀夫", "黑子", "加藤惠", "黑岩射手", "速水奏", "妮可", "莫妮卡", "洛天依", "咕噜", "小兔", "蛋黄哥", "皮卡丘", "可达鸭", "妙蛙种子", "小火龙", "杰尼龟", "皮卡", "小智", "小霞", "绿巨人", "钢铁侠", "蝙蝠侠", "超人", "小红帽", "灰姑娘", "白雪公主", "长发公主", "睡美人", "花木兰", "香港猫", "喵星人", "汤姆猫", "托马斯", "熊大", "海绵宝宝", "派大星", "米老鼠", "唐老鸭", "黄猫警长", "史努比", "芝麻街", "发条橙", "刀剑神域", "冰菓", "CLANNAD", "天空之城", "风之谷", "死神", "七龙珠", "火影忍者", "海贼王", "进击的巨人", "东京喰种", "魔法少女小圆", "命运石之门", "刀剑乱舞", "游戏王", "银魂", "名侦探柯北", "小熊维尼", "米老鼠", "唐老鸭", "大力水手", "神龟忍者", "海绵宝宝", "派大星", "多啦A梦", "葫芦娃", "三眼神童", "哆啦贝", "哆啦A梦", "樱桃小丸子", "蜡笔小新", "爱丽丝", "宝莲灯", "西游记", "流浪地球", "钢铁侠", "超人", "蝙蝠侠", "奥特曼", "铁甲小宝", "猫和老鼠", "熊出没", "小羊肖恩", "猴岛传奇", "熊本熊", "妙手小厨师", "布袋和尚", "天书奇谭", "奇幻人生", "阿拉丁", "花木兰", "哪吒", "赛尔号", "喜羊羊", "灰太狼", "疯狂动物城", "功夫熊猫", "冰雪奇缘", "小鸟游六花", "柯南", "城市猎人", "龙珠", "火影忍者", "航海王", "进击的巨人", "东京食尸鬼", "鬼灭之刃", "命运石之门", "未闻花名", "虫师", "海贼王", "银魂", "死神", "灌篮高手", "足球小将", "犬夜叉", "斗罗大陆", "神奇宝贝", "圣斗士星矢", "魔法少女小圆", "Fate/Zero", "刀剑神域", "进击的巨人", "东京食尸鬼", "鬼灭之刃", "罗马浴场", "百变小樱", "千与千寻", "天空之城", "龙猫", "虫师", "罗小黑战记", "极黑的布伦希尔特", "狼与香辛料", "噬血狂袭", "公主恋人", "笑死人不偿命", "逗比大佬", "蒟蒻专业户", "无敌逗比", "幽默小能手", "段子手", "说笑话的小王子", "笑料百出", "调皮宝贝", "搞笑达人", "开心果", "爆笑王子", "逗比小丑", "笑傻了的小妞", "笑嘻嘻", "逗比狂人", "幽默之神", "吐槽大师", "搞笑小天使", "捧腹大笑", "笑料专家", "快乐之源", "笑容满面", "小逗比", "搞笑鬼才", "笑嗨了的小哥", "逗比无双", "欢乐小精灵", "笑翻天", "幽默天使", "快乐小丑", "笑嘻嘻的小姐", "逗比女神", "段子王", "笑料风暴", "吐槽之王", "开心果儿", "爆笑女王", "逗比小贱", "笑傻了的小子", "笑得停不下来", "逗比疯子", "幽默大师", "搞笑达人", "快乐使者", "笑容可掬", "欢笑之源", "小鬼头", "逗比之王", "笑翻全场", "幽默之王", "快乐小精灵", "搞笑妹纸", "笑料无限", "逗比大妈", "段子高手", "开心果仁", "爆笑大妞", "逗比小花", "笑出腹肌", "搞笑狂人", "笑嗨了的小姐", "幽默女神", "逗比男孩", "欢乐小丑", "笑嘻嘻的小妹", "逗比女魔头", "段子机器", "笑料之王"];
    var enChar = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'.split('');
    var nickname = '';
    var cnIndex = Math.floor(Math.random() * cnNames.length);
    var cnName = cnNames[cnIndex];
    nickname += cnName + '_';
    for (var i = 0; i < length; i++) {
        var index = Math.floor(Math.random() * enChar.length);
        nickname += enChar[index];
    }
    return nickname;
}

//点击注册按钮事件
$('#register_btn').click(function() {
    var register_id = $.trim($('#register_id').val());
    var register_password = $.trim($('#register_password').val());
    var register_email = $.trim($('#register_email').val());
    var register_token = $.trim($('#register_token').val());
    if (!register_id) {
        layer.msg('账号名称不能为空!');
        return;
    }

    if (register_id.length > 12) {
        layer.msg("用户名不能超过12个字符!", );
        return;
    }

    if (!register_password) {
        layer.msg('账号密码不能为空!');
        return;
    }

    if (!register_email) {
        layer.msg('邮箱账号不能为空!');
        return;
    }

    if (!register_token) {
        layer.msg('验证码不能为空!');
        return;
    }

    if (!isValidEmail(register_email)) {
        layer.msg('邮箱账号格式不正确！');
        return;
    }

    $.getJSON("./io/register.php?jsoncallback=?", {
        user: register_id,
        email: register_email,
        password: register_password,
        Code: register_token
    }, function(data) {
        if (data.status) {
            layer.msg("注册账号成功!", {
                icon: 6
            });
            $.cookie('user_name', data.user_name);
            $.cookie('user_email', data.user_email);
            $.cookie('user_token', data.user_token);
            $(location).attr('href', './index.html');
        } else {
            layer.msg(data.error);
        }
    });
});



$('#login_up').click(function() {
    const user_email = $('#user_email').val().trim();
    const user_password = $('#user_password').val().trim();
    if (!user_email || !user_password) {
        layer.msg('邮箱账号和密码不能为空！');
        return;
    }
    if (!isValidEmail(user_email)) {
        layer.msg('邮箱账号格式不正确！');
        return;
    }
    $.getJSON('./io/login.php?jsoncallback=?', {
        email: user_email,
        password: user_password
    }, function(data) {
        if (data.status === true) {
            layer.msg('登录账号成功！');
            $.cookie('user_name', data.user_name);
            $.cookie('user_email', data.user_email);
            $.cookie('user_token', data.user_token);
            $(location).attr('href', './index.html');
        } else {
            layer.msg(data.error);
        }
    });
});

$('#password_btn').click(function() {
    const password_email = $('#password_email').val().trim();
    const password_password = $('#password_password').val().trim();
    const password_token = $('#password_token').val().trim();
    if (!user_email || !user_password || !password_token) {
        layer.msg('邮箱账号和密码或验证码不能为空！');
        return;
    }
    if (!isValidEmail(password_email)) {
        layer.msg('邮箱账号格式不正确！');
        return;
    }
    $.getJSON("./io/password.php?jsoncallback=?", {
        email: password_email,
        password: password_password,
        Code: password_token
    }, function(data) {
        if (data.status == true) {
            layer.msg("修改密码成功!");
            $.cookie('user_name', data.user_name);
            $.cookie('user_email', data.user_email);
            $.cookie('user_token', data.user_token);
            $(location).attr('href', './index.html');
        } else {
            layer.msg(data.message);
        }
    });
});

function isValidEmail(email) {
    const emailRegexp = /^([a-zA-Z0-9_-])+@([a-zA-Z0-9_-])+((.[a-zA-Z0-9_-]{2,3}){1,2})$/;
    return emailRegexp.test(email);
}

$('#login_1').click(function() {
    $.removeCookie('user_name'); //删除cookie
    $.removeCookie('user_email'); //删除cookie
    $.removeCookie('user_token'); //删除cookie
    $(location).attr('href', './index.html');
});

function sendEmailVerificationCode(btnId, emailInputId) {
    var btn = $(btnId);
    var email = $(emailInputId).val();
    if (btn.prop('disabled')) {
        return false;
    }
    if (email.replace(/(^\s*)|(\s*$)/g, "") == "") {
        layer.msg("邮箱账号不能为空！");
        return;
    } else {
        var reg = /^([a-zA-Z0-9_-])+@([a-zA-Z0-9_-])+((\.[a-zA-Z0-9_-]{2,3}){1,2})$/;
        flag = reg.test(email);
        if (!flag) {
            layer.msg("邮箱账号格式不正确！");
            return false;
        }
    }

    $.getJSON("./io/mail/index.php?jsoncallback=?", {
        email: email
    }, function(data) {
        if (data.status == false) {
            layer.msg(data.message);
        } else {
            var time = 60;
            btn.prop('disabled', true).addClass('disabled');
            btn.text("倒计时 " + time);
            layer.msg(data.message);
            var timer = setInterval(function() {
                time--;
                btn.text("倒计时 " + time);
                if (time == 0) {
                    btn.text("发送验证码 ");
                    clearInterval(timer);
                    btn.prop('disabled', false).removeClass('disabled');
                    time = 60;
                }
            }, 1000);
        }
    });
}

$('#token_1').click(function() {
    sendEmailVerificationCode('#token_1', '#register_email');
});

$('#token_2').click(function() {
    sendEmailVerificationCode('#token_2', '#password_email');
});