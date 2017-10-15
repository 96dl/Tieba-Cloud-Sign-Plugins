<?php if (!defined('SYSTEM_ROOT')) {
    die('Insufficient Permissions');
}

function ver4_post_nav()
{
    echo '<li ';
    if (isset($_GET['plugin']) && $_GET['plugin'] == 'ver4_post') {
        echo 'class="active"';
    }
    echo '><a href="index.php?plugin=ver4_post"><span class="glyphicon glyphicon-check"></span> 贴吧云灌水</a></li>';
}


addAction('navi_1', 'ver4_post_nav');
addAction('navi_7', 'ver4_post_nav');

/*
 * 生成时间间隔随机数
 * */
function randNum($n)
{
    return rand($n, $n + ceil($n / 4));
}

/*
 * 从url中分离tid
 * */
function getTid($url){
    preg_match('/\.com\/p\/(?<tid>\d+)/', $url, $tids);
    return $tids ['tid'];
}

/*
 * 贴吧post参数整合
 * */
function getParameter($data){
    $sign_str = '';
    foreach ($data as $k => $v) $sign_str .= $k . '=' . $v;
    $sign = strtoupper(md5($sign_str . 'tiebaclient!!!'));
    $data['sign'] = $sign;
    return $data;
}

/*
 * 根据BDUSS生成固定位数数字
 * */
function findNum($str=''){
    $str = sha1(md5($str));
    $str = trim($str);
    if(empty($str)){return '';}
    $temp=array('1','2','3','4','5','6','7','8','9','0');
    $result='';
    for($i=0;$i<strlen($str);$i++){
        if(in_array($str[$i],$temp)){
            $result.=$str[$i];
        }
    }
    if (strlen($result) < 10){
        return (int)$result + 1000000000;
    } else {
        return $result;
    }
}

/*
 * 获取fid
 * */
function getFid($tname)
{
    $x = wcurl::xget("http://tieba.baidu.com/i/data/get_fid_by_fname?fname={$tname}");
    $r = json_decode($x,true);
    return $r['data']['fid'];
}


/*
 * 获取帖子详细内容
 * 返回fid、tid、tname、pname
 * */
function getPage($tid){
    $tl = new wcurl('http://c.tieba.baidu.com/c/f/pb/page');
    $data = array(
        '_client_type'    => 2,
        '_client_version' => '6.0.0',
        '_phone_imei'     => '867600020777420',
        'from'            => 'tiebawap_bottom',
        'kz'              => $tid,
        'pn'              => 1,
        'rn'              => 10,
        'timestamp'       => time() . '516'
    );
    $tl->set(CURLOPT_RETURNTRANSFER, true);
    $rt = $tl->post(getParameter($data));
    $result = json_decode($rt,true);
    $r = array(
        "fid"   => $result['forum']['id'],
        "tid"   => $tid,
        "tname" => $result['forum']['name'],
        "pname" => $result['post_list'][0]['title'],
    );
    return $r;
}


/*
 * 获得帖子第一页内容
 * */
function getFirstPageTid($name){
    $tid = array();
    $tl = new wcurl('http://c.tieba.baidu.com/c/f/frs/page');
    $data = array(
        '_client_id'      => 'wappc_1470896832265_330',
        '_client_type'    => 2,
        '_client_version' => '5.1.3',
        '_phone_imei'     => '867600020777420',
        'from'            => 'baidu_appstore',
        'kw'              => $name,
        'model'           => 'HUAWEI MT7-TL10',
        'pn'              => 1,
        'rn'              => 33,
        'st_type'         => 'tb_forumlist',
        'timestamp'       => time() . '516'
    );
    $tl->set(CURLOPT_RETURNTRANSFER, true);
    $rt = $tl->post(getParameter($data));
    $result = json_decode($rt, true)['thread_list'];
    foreach ($result as $v) $tid[] = $v['id'];
    unset($tid[0],$tid[1],$tid[2]);
    return $tid;
}

/*
 * 发表回复(支持楼中楼)
 * */
function sendIt($b,$u,$t,$c){
    $tp = new wcurl('http://c.tieba.baidu.com/c/c/post/add');
    $data = array(
        'BDUSS'           => $b,
        '_client_id'      => 'wappc_147' . substr(findNum($b),0,10) . '_' . substr(findNum($b),5,3),
        '_client_type'    => $u['cat'] == 5 ? rand(1, 4) : $u['cat'],
        '_client_version' => '7.9.2',
        '_phone_imei'     => md5($b),
        'anonymous'       => 1,
        'content'         => $c,
        'fid'             => $t ['fid'],
        'from'            => 'appstore',
        'is_ad'           => 0,
        'kw'              => $t ['tname'],
        'model'           => 'HUAWEI MT7-TL10',
        'new_vcode'       => 1,
        'quote_id'        => !empty($t['qid']) ? $t['qid'] : '',
        'tbs'             => misc::getTbs(0,$b),
        'tid'             => $t['tid'],
        'timestamp'       => time() . '516',
        'vcode_tag'       => 12,
    );
    $tp->set(CURLOPT_RETURNTRANSFER, true);
    $rt = $tp->post(getParameter($data));
    $re = json_decode($rt, true);
    if (!$re) return array(0, 'JSON 解析错误');
    if ($re ['error_code'] == 0) return array(2, "使用第" . $u['cat'] . '种客户端发帖成功');
    else if ($re ['error_code'] == 5) return array(5, "需要输入验证码，请检查你是否已经关注该贴吧。");
    else if ($re ['error_code'] == 220034) return array(220034, "您的操作太频繁了！");
    else if ($re ['error_code'] == 340016) return array(340016, "您已经被封禁");
    else if ($re ['error_code'] == 232007) return array(232007, "您输入的内容不合法，请修改后重新提交。");
    else return array($re ['error_code'], "未知错误，错误代码：" . $re ['error_code']);
}

/*
 * 获取帖子指定楼层信息
 * */
function getFloorInfo($tid,$pn,$floor){
    $tl = new wcurl('http://c.tieba.baidu.com/c/f/pb/page');
    $data = array(
        '_client_type'    => 2,
        '_client_version' => '6.0.0',
        '_phone_imei'     => '867600020777420',
        'from'            => 'tiebawap_bottom',
        'kz'              => $tid,
        'pn'              => $pn,
        'rn'              => 30,
        'timestamp'       => time() . '516'
    );
    $tl->set(CURLOPT_RETURNTRANSFER, true);
    $rt = $tl->post(getParameter($data));
    $result = json_decode($rt,true)['post_list'];
    $pid = 0;
    foreach ($result as $v){
        if ($v['floor'] == $floor) $pid = $v['id'];
    }
    return $pid;
}

/*
 * 获取图灵机器人内容
 * */
function getTuLing(){
    $re = wcurl::xget('http://tuling123.tbsign.cn/index.php?mod=index');
    $r = json_decode($re,true);
    if ($r['code'] != 100000) {
        $content = getSysContent();
    } else {
        $content = $r['text'];
    }
    return $content;
}

/*
 * 获取系统内置回复内容
 * */
function getSysContent(){
    $text = <<<EOF
没有一个人能读懂另一个人。
我的世界不允许你的消失，不管结局是否完美。
这个世界在变，唯一不变的是一直在变。
我没把希望放在任何一个人身上毕竟我不是废物。
在那些最好的时光里，我们在生命中写下彼此的名字，那么便不怕，终有一日会分开。
生活就像淋浴，方向转错，水深火热。
有人喜欢你披着头发的样子，有人喜欢你扎起头发的样子，可你忘记了喜欢你的人喜欢你所有的样子。
遇见不论早晚，真心才能相伴；朋友不论远近，懂得才有温暖。轰轰烈烈的，未必是真心；默默无声的，未必是无心。
那年阳光很好，我们如此遇见，觉得你是另一个我。
最伤人的就是，昨天还让你觉得自己意义非凡的人，今天就让你觉得自己可有可无。
有些话，适合藏在心里，有些痛苦，适合无声无息的忘记。当经历过，你成长了，自己知道就好。很多改变，不需要自己说，别人会看得到。
每一个不懂爱旳人，都会遇到一个懂爱的人。然后经历一场撕心裂肺旳爱情，然后分开。后来不懂爱的人慢慢懂了。懂爱旳人，却不敢再爱了。
很多时候，你不说，我也不说，就这样，说着说着就变了，想着想着就算了。
不是山，却需要攀登的是人生；不是深渊，却需要跨越的是自己。关于距离，最害怕的就是你不知道那个人是在想念你，还是已经忘了你。
有些事，不经意也会想起；有些回忆，白发苍苍也无法忘记；有些伤口，别人永远看不见，因为它就在你的心里深藏。
不是谁都可以对我乱发脾气，先搞清楚你跟我的关系，你是我的谁。
不要以为你放不下的人同样会放不下你，鱼没有水会死，水没有鱼却会更清澈。
I was smiling yesterday, I am smiling today and I will smile tomorrow. Simply because life is too short to cry for anything. 
我们终究会长大，然后，丢失了那份最初的纯真。
世界这么大，我们还是遇见了；世界这么小，我们还是走丢了。
大千世界，我们并不是缺少一个说话的朋友，而是渴望一个理解自己读懂自己的伙伴。
人潮拥挤 你再无惦念 愿有太阳一样的人暖你一生
柔软的地方会发生柔软的事 柔软的心灵会开出柔软的花
有的时候觉得自己真的不能改变什么,认识你是我最美丽的意外。
重新来过吗 你当这是游戏吗还能满血复活吗。
不管发生了多糟糕的事情还是多开心的事情，脑海里出现的第一句话都是“要是你在就好了”，那么这才是爱你的定义。
我不知道你是不是也像我一样，很遗憾以后的许多年不能一起成长一起像从前一样又二逼又热情的消磨时光。
别面带微笑内心滴血地感谢伤害过你的人们啦，要唾弃他们。你该感谢的是挺过来并且更加牛逼的自己。
若是你我之间得失无法互补，哪怕你说要离开，我也会精心为你挑一匹快马。
人最好挑一个最喜欢的学，做一个在自己的领域中最出色的人，否则最后会一事无成。
爱ta们，爱得自己心里发疼，一想到如果以后，他们会在这个世界上消失，我就感觉非常的恐惧……
一句话，留下只有自己能懂的心事……
第一个称赞女人是鲜花的是天才，第二个就是蠢材了
不放弃，放弃的话，就当场结束了；不哭泣，哭泣的话，只会招惹别人同情你，想哭的时候，就笑；
我们仰望着天空，却看着不同的地方
．小熊问小白兔:"你掉毛吗?"小白兔说:"不掉",小熊又问:"你真的掉毛吗?"小白兔说:"真的不掉",于是小熊拿小白兔擦屁股.
于自习室见一女生挺着肚子在看考研资料，敬佩之情油然而生:都快当妈妈了，还认真学习!没过多久,她从衣服里拽出热水袋一枚，奔向教室门外……
见一女生很像初恋，百般纠缠终于好上了，最后去她家一看，是初恋的妹妹。。。你妹啊。。。
生活就像一个强奸犯，蹂躏了你我一遍又一遍，可我们却无力反抗，不是吗？
下决心要走的人，你把心掏出来给它，它都嫌你恶心。
孤单并不可怕，可怕的是你甘愿一直孤单下去，没有了选择快乐的勇气。
生活就像强奸一样 既然无法抵抗 那不如好好享受吧
命运这种东西，生来就是要被踏在足下的，当你还未能反抗时，只需怀着勇气等待。
你的心思我永远猜不透，可我的心思你永远不肯猜。
心若相知无言也默契；情若相眷不语也怜惜。最真的拥有是我在你亦在；最美的感情是我懂，你更懂……
不乱于心，不困于情，不畏将来，不念过往，如此，安好。
成熟是给陌生人看的，幼稚的一面才是给最爱的人看的。
他居然不知道自己己经喜欢上她了，她也不知道他喜欢上自己了。
我不需要多么完美的爱情，我只需要有一个人永远不会放弃。生活告诉我们：在喜欢你的人那里，去热爱生活；在不喜欢你的人那里，去看清世界。
她不知道她的一句话牵动着我的内心，她的一句话让我彻夜难眠。
我很努力的坚守着自己的内心防线，可还是有那么一个人走进来了。
这是男人与男人 之间的对话，这也是男人与男人之间的无奈，很多时候我们都喜欢用沉默来代替所有的语言。
有些东西我真的给不了，对不起，我真的试着去给你，可我骗不了自己。
既然来到这个世上，就要活得漂亮；既然选择了远方，就要走得倔强。
最孤独的不是一个人逛街一个人吃饭一个人睡觉，而是做什么都能想到的人现在已经不在身边了，说句话都是奢侈。
人和人之间的好感就这么脆弱 你关门的声音大了些 我就感觉你讨厌我了 真的
本就不该有这么一场赌局，但我们都要放手一搏，结果我输了，你走了。
越来越不敢找你 因为你的冷漠开始让我觉得主动是那么的廉价
一个人在真正无可奈何的时候 除了微笑也只好微笑了
我是个很识相的人 感觉到你话语里有一点不耐烦了我就会走开了
很多时候 微笑不能说明我是快乐的 但证明我是坚强的
不是我不联系你，而是你给我的感觉，像是我在打扰你。
“后来砸碎了酒瓶也没换来清醒，弄脏了自己也没换来爱情。
等与不等，我都等了，在与不在乎，我都已经在乎了，剩下的，靠命运吧。
慢慢的，慢慢的总要变成形单影只，我们各怀心事，谁也安慰不了谁，谁也救赎不了谁，
快乐幸福不可能时时相伴，情感的河流难免不起波澜。别让一时的困惑迷惑了自己的双眼。
我走下楼梯 发现一个身影很像你 却恍然发觉 在我的校园里 没有你
有些事，有些人，有些风景，一旦入眼入心，即便刹那，也是永恒。
始终相信，一些真诚，经历风雨，才知可遇不可求。一些对白，不是刻意，却温暖万千。
情是两颗心之间的习惯。不愿、不忍、不肯的执守；无怨、无悔、无求的付出。因为爱着，所以与共着；因为爱着，所以不肯走开了。
爱一个人 只希望对方可以看见；守一个人不言放弃，只希望可以始终不远不近一直都在。
是谁把光阴剪成了烟花，一瞬间，看尽繁华。一树繁花，只一眼，便是天涯。
刻薄嘴欠和幽默是两回事，口无遮拦和坦率是两回事，没有教养和随性是两回事，轻重不分和耿直是两回事。
当有人突然从你的生命中消失，不用问为什么，只是他或她到了该走的时候了，你只需要接受就好，不论朋友，还是恋人。 所谓成熟，就是知道有些事情终究无能为力。
你见过男生送女生上出租车并叮嘱到家回一个短信 但是你很少知道还有男生会记下出租的车牌号。
争气比生气重要，做事比做人重要，有人在背后骂你别难过，不要停止变强，你的生活才能另有所想。
既不回头，何必不忘。既然无缘，何需誓言。昨日种种，似水无痕。明夕何夕，君已陌路。
别相信太多，别爱得太多，别希望太多，因为这些“太多”最终会让你伤得很多。
心软是一种不公平的善良，成全了别人，委屈了自己，却被别人当成了傻逼！
有时候你把什么放下了，不是因为突然就舍得了，而是因为期限到了，任性够了，成熟多了， 也就知道这一页该翻过去了。
其实，许多事从一开始就已料到了结局，往后所有的折腾，都不过只是为了拖延散场的时间。
有些事，想多了头疼，想通了心疼。。所以，该睡就睡，该玩就玩。不必遗憾，若是美好，叫做精彩，若是糟糕，叫做经历。
人生的路，难与易都得走；世间的情，冷与暖总会有。别喊累，因为没人替你分担；别言苦，因为没人替你品尝；别脆弱，因为没人替你坚强。
在你没有任何喜欢的人的时候，你过得是最轻松快乐的，尽管偶尔会觉得孤单了点 。
慢慢的，我也学着放下了。不是我变了，是我真的无能为力了，我认输了，我折腾不动了。
很多微笑，明知道虚伪却还强挤着笑容；很多回忆，明知道痛心却还是无法释怀。
人的脆弱和坚强都超乎自己的想象。有时，我们可能脆弱得一句话就泪流满面；有时，也发现自己咬着牙走了很长的路。
世界上最大的幸福就是有人愿意等你，信你，陪着你，无时无刻的照顾着你。
有时候，心里会突然冒出一种厌倦的情绪，觉得自己很累很累。只想放纵自己一回，希望能痛痛快快歇斯底里地疯一次。
永远不要怪别人不帮你，也永远别怪他人不关心你。人生路上，真正能帮你的，永远只有你自己。
有时候，明明心如刀割，却要灿烂的微笑，明明很脆弱，却表现的如此坚强，眼泪在眼里打转，却告诉每个人我很好。
问别人为什么 多问自己凭什么
天再高又怎样，踮起脚尖就更接近阳光。
如果你总是在没有人陪的时候想起我 对不起 我真的不缺你
我们都不擅长表达 以至于我们习惯了揣测 去肯定 去否定 反反复复 后来我们就变得敏感而脆弱
不愿意依赖别人，自己却又不争气，我大概就是这样的人。
从来我都这样 不懂挽留不会说话 再多舍不得也会咽下去 因为你离开 是为了寻找更好的自己。
你要堕落，神仙也救不了； 你要成长，绝处也能逢生。 ————好励志的树！
很多的烦恼源于做什么都要顾及别人的感受，你总顾及别人，那谁来顾及你。
感情不需要诺言，协议与条件。它只需要两个人：一个能够信任的人，与一个愿意理解的人。
不要离开，不要伤害。不要欺骗，不要背叛，只想陪在身边，就这样，一直走下去，就算偶尔有小吵小闹，也不要分开。我想要的，就这么简单 。
鱼那么喜欢水，水却把鱼煮了，就像不是所有的喜欢都可以有结果。学着看淡一些事情，也许才是对自己最好的保护！
岁月很长，人海茫茫，别回头也别将就，自然会有良人来爱你
不要去听别人的忽悠，你人生的每一步都必须靠自己的能力完成。自己肚子里没有料，手上没本事，认识再多人也没用。人脉只会给你机会，但抓住机会还是要靠真本事。所以啊，修炼自己，比到处逢迎别人重要的多。
如果心累了，在宁静的夜晚，沏一杯清茶，放一曲淡淡的音乐，让自己溶化在袅袅的清香和悠扬的音乐中……人生，该说的要说，该哑的要哑，是一种聪明。
所有的玩笑里，都藏着认真的话，而那些看似没有听懂的回应，大概就是再委婉不过的拒绝。
不要去追一匹马 。用追马的时间种草 ，待到来年春暖花开之时 ，就会有一批骏马任你选择 。
偶会想念童年时光，不知愁滋味，不用担责备，不会为别人心碎，不知人间苦累。
不开心时，做个深呼吸，不过是糟糕的一天而已，又不是糟糕一辈子 。
你越是费劲心思的去取悦一个人，那个人就越有可能让你痛彻心扉。期待，是所有心痛的根源，心不动则不痛。
没必要刻意遇见谁，也不急于拥有谁，更不勉强留住谁 。一切顺其自然，最好的自己留给最后的人 。
“人生中三大悲剧:熟得没法做情侣 饿得不知吃什么 困得就是睡不着 ”
如果有一天，我不再对你笑。 请不要说：你变了。 请记得你曾经也没有问我过的快不快乐。
泪是当你无法用嘴来解释你的心碎的时候，表达情绪的唯一方式。
改变不了的事就别太在意，留不住的人就试着学会放弃，受了伤的心就尽力自愈，除了生死，都是小事，别难为了自己！
别因为太过在意别人的看法 而使自己活得畏手畏脚 ，你要相信 你真的没有那么多观众。
有时候我们总是身不由己 总是违背着自己的意愿 做着自己不喜欢的事 过着自己不想要的生活。
珍惜高中给你讲过题的每一个人，因为以后的岁月里你再也不会遇见一个与你真心分享的人。
有些东西，并不是越浓越好，要恰到好处。深深的话我们浅浅地说，长长的路我们慢慢地走。
想法多了 失望就多 失望多了 想法就更多
一个转发说说的人,都带有某种心情想要传达给某个人,可惜某个人不懂.
不想放弃所以一直坚持 ， 不想流泪所以一直装笑 不想被丢下所以宁愿独自一人.
有的人把心都掏给你了，你却假装没看见，因为你不喜欢。有的人把你的心都掏了，你还假装不疼，因为你爱。
人常常都是这么误会自己的 以为自己恋旧 以为自己长情 其实只是现在过得不好而已
越长大越不敢依赖别人，怕人心会变，怕承诺不兑现，以至于只相信这世上只有自己才能给足自己安全感。
我不希望有人选择我是因为我的好，我需要有人看见我的不好，而仍旧想要我。
感情淡了我们再培养，无话可说了我们就再去找话题。觉得腻了我们重新认识，要是累了就给彼此空间。人潮汹涌的，遇到你也不容易，也不想再推开了。
只有在你最落魄时，才会知道谁是为你担心的笨蛋，谁是形同陌路的混蛋。真想知道，如果有一天，我失去了一切，变的一无所有 。 谁会站在我身边对说一句：没事，还有我。
为了一个你，我和多少人淡了关系 。 结果，你走了，他们也没了 。
总有那么一个人，一句话就可以把你伤的很彻底。 其实，每一个爱傻笑的人，心里都有着放不下的痛。
一个人就算再好，但不愿陪你走下去，那他就是过客；一个人再有缺点，但能处处忍让你，陪你到最后，就是终点。
命运，总是要你先好好善待身边的人，才会让你遇见对你最好的那个人。
EOF;
    $contents = explode("\n", $text);
    $content = $contents[array_rand($contents)];
    return $content;
}


//获取指定贴吧第一页tie
/*function allTid($name)
{
    $head = array();
    $head[] = 'Content-Type: application/x-www-form-urlencoded';
    $head[] = 'User-Agent: Mozilla/5.0 (SymbianOS/9.3; Series60/3.2 NokiaE72-1/021.021; Profile/MIDP-2.1 Configuration/CLDC-1.1 ) AppleWebKit/525 (KHTML, like Gecko) Version/3.0 BrowserNG/7.1.16352';
    $tl = new wcurl('http://c.tieba.baidu.com/c/f/frs/page', $head);
    $data = array(
        '_client_id'      => 'wappc_1470896832265_330',
        '_client_type'    => 2,
        '_client_version' => '5.1.3',
        '_phone_imei'     => '867600020777420',
        'from'            => 'baidu_appstore',
        'kw'              => $name,
        'model'           => 'HUAWEI MT7-TL10',
        'pn'              => 1,
        'rn'              => 33,
        'st_type'         => 'tb_forumlist',
        'timestamp'       => time() . '516'
    );
    $sign_str = '';
    foreach ($data as $k => $v) $sign_str .= $k . '=' . $v;
    $sign = strtoupper(md5($sign_str . 'tiebaclient!!!'));
    $data['sign'] = $sign;
    $tl->set(CURLOPT_RETURNTRANSFER, true);
    $rt = $tl->post($data);
    $result = json_decode($rt, true)['thread_list'];
    $tid = array();
    foreach ($result as $v) {
        $tid[] = $v['id'];
    }
    unset($tid[0],$tid[1],$tid[2]);
    return $tid;
}*/

//添加帖子URL时获取帖子信息
/*function get_tid($url)
{
    $tieurl = $url;
    preg_match('/\.com\/p\/(?<tid>\d+)/', $tieurl, $tids);
    $tid = $tids ['tid'];
    $ch = curl_init('http://tieba.baidu.com/p/' . $tid);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $contents = curl_exec($ch);
    curl_close($ch);

    preg_match('/fname="(.+?)"/', $contents, $fnames);
    preg_match('|<title>(.*?)</title>|s', $contents, $post_names);

    $fid = get_fid($fnames[1]);
    $tid = get_random_tid($url);

    $post_name = str_replace('_' . $fnames[1] . '吧_百度贴吧', '', $post_names[1]);

    $result = json_encode(array(
        "fid"   => $fid,
        "tid"   => $tid,
        "tname" => $fnames[1],
        "pname" => $post_name,
    ), JSON_UNESCAPED_UNICODE);
    return $result;
}*/

//获得fid
/*function get_fid($tname)
{
    $x = wcurl::xget("http://tieba.baidu.com/i/data/get_fid_by_fname?fname={$tname}");
    $r = json_decode();

    $info = file_get_contents('http://tieba.baidu.com/i/data/get_fid_by_fname?fname=' . $tname);
    preg_match('/fid":(.*?)},/', $info, $fids);
    return $fids[1];
}*/


//发表帖子回复
/*function client_rppost($bduss, $tieba, $content)
{
    global $m;
    $uid = $tieba['uid'];
    $s = $m->fetch_array($m->query("SELECT * FROM `" . DB_NAME . "`.`" . DB_PREFIX . "ver4_post_userset` WHERE `uid` = {$uid}"));
    if ($s ['cat'] == 5) {
        $s ['cat'] = rand(1, 4);
    }
    $head = array();
    $head[] = 'Content-Type: application/x-www-form-urlencoded';
    $head[] = 'User-Agent: Mozilla/5.0 (SymbianOS/9.3; Series60/3.2 NokiaE72-1/021.021; Profile/MIDP-2.1 Configuration/CLDC-1.1 ) AppleWebKit/525 (KHTML, like Gecko) Version/3.0 BrowserNG/7.1.16352';
    $tp = new wcurl('http://c.tieba.baidu.com/c/c/post/add',$head);
    $formdata = array(
        'BDUSS'           => $bduss,
        '_client_id'      => 'wappc_147' . substr(findNum($bduss),0,10) . '_' . substr(findNum($bduss),5,3),
        '_client_type'    => $s['cat'],
        '_client_version' => '6.6.9',
        '_phone_imei'     => md5($bduss),
        'anonymous'       => 1,
        'content'         => $content,
        'fid'             => $tieba ['fid'],
        'from'            => 'appstore',
        'is_ad'           => 0,
        'kw'              => $tieba ['tname'],
        'model'           => 'HUAWEI MT7-TL10',
        'new_vcode'       => 1,
        'quote_id'        => !empty($tieba ['qid']) ? $tieba ['qid'] : '',
        'tbs'             => misc::getTbs(0,$bduss),
        'tid'             => $tieba ['tid'],
        'vcode_tag'       => 11,
    );
    $adddata = '';
    foreach ($formdata as $k => $v)
        $adddata .= $k . '=' . $v;
    $sign = strtoupper(md5($adddata . 'tiebaclient!!!'));
    $formdata ['sign'] = $sign;
    $tp->set(CURLOPT_RETURNTRANSFER, true);
    $rt = $tp->post($formdata);
    $re = json_decode($rt, true);
    switch ($s ['cat']) {
        case '1' :
            $client_res = "iphone";
            break;
        case '2' :
            $client_res = "android";
            break;
        case '3' :
            $client_res = "WindowsPhone";
            break;
        case '4' :
            $client_res = "Windows8";
            break;
    }
    if (!$re) return array(0, 'JSON 解析错误');
    if ($re ['error_code'] == 0) return array(2, "使用" . $client_res . '客户端发帖成功');
    else if ($re ['error_code'] == 5) return array(5, "需要输入验证码，请检查你是否已经关注该贴吧。");
    else if ($re ['error_code'] == 7) return array(7, "您的操作太频繁了！");
    else if ($re ['error_code'] == 8) return array(8, "您已经被封禁");
    else return array($re ['error_code'], "未知错误，错误代码：" . $re ['error_code']);
}*/

//获取指定帖子的楼层信息
/*function getQid($tid,$pn,$floor){
    $tl = new wcurl('http://c.tieba.baidu.com/c/f/pb/page');
    $data = array(
        '_client_type'    => 2,
        '_client_version' => '6.0.0',
        '_phone_imei'     => '867600020777420',
        'from'            => 'tiebawap_bottom',
        'kz'              => $tid,
        'pn'              => $pn,
        'rn'              => 30,
        'timestamp'       => time() . '516'
    );
    $sign_str = '';
    foreach ($data as $k => $v) $sign_str .= $k . '=' . $v;
    $sign = strtoupper(md5($sign_str . 'tiebaclient!!!'));
    $data['sign'] = $sign;
    $tl->set(CURLOPT_RETURNTRANSFER, true);
    $rt = $tl->post($data);
    $result = json_decode($rt,true)['post_list'];
    $pid = 0;
    foreach ($result as $v){
        if ($v['floor'] == $floor) $pid = $v['id'];
    }
    return $pid;
}*/


//获得贴吧帖子tid
/*function get_random_tid($url)
{
    $cu = explode('/p/', $url);
    if (strpos($cu[1], '?')) {
        $tid = textMiddle($url, '/p/', '?');
    } else {
        $tid = $cu[1];
    }
    return $tid;
}*/



/*
 * 随机抽取内容获取接口
 * */
/*function get_random_content()
{
    $ac = rand_array(array(0));
    switch ($ac){
        case 0:
            $content = tuLing();
            break;
        case 1:
            $content = moLi();
            break;
        default:
            $content = tuLing();
            break;
    }
    return $content;
}*/

/*
 * 图灵API接口函数
 * */
/*function tuLing(){
    $apikey = option::get('ver4_post_apikey');
    if (!empty($apikey)){
        $tl = new wcurl('http://www.tuling123.com/openapi/api');
        $info = array('讲个笑话');
        $data = array('key' => $apikey, 'info' => rand_array($info));
        $re = $tl->post($data);
        $r = json_decode($re,true);
    } else {
        $r['code'] = -1;
    }
    if ($r['code'] != 100000) {
        $content = getContent();
    } else {
        $content = $r['text'];
    }
    return $content;
}*/

/*
 * 茉莉机器人API接口函数
 * */
/*function moLi(){
    $ml = new wcurl('http://www.itpk.cn/jsonp/api.php?question=%E7%AC%91%E8%AF%9D');
    $re = $ml->get();
    $r = json_decode($re,true);
    return $r['content'];
}*/
