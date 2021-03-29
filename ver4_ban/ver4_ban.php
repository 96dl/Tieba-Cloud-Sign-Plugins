<?php if (!defined('SYSTEM_ROOT')) {
    die('Insufficient Permissions');
}

function ver4_ban_nav()
{
    echo '<li ';
    if (isset($_GET['plugin']) && $_GET['plugin'] == 'ver4_ban') {
        echo 'class="active"';
    }
    echo '><a href="index.php?plugin=ver4_ban"><span class="glyphicon glyphicon-ban-circle"></span> 贴吧云封禁[吧务]</a></li>';
}

addAction('navi_1', 'ver4_ban_nav');
addAction('navi_7', 'ver4_ban_nav');


/*
 * 执行封禁操作
 * */
function ver4_ban($pid, $portrait, $name, $tieba, $reason, int $day = 1)
{
    $bduss = misc::getCookie($pid);
    $r = empty($reason) ? '您因为违反吧规，已被吧务封禁，如有疑问请联系吧务！' : $reason;
    $tl = new wcurl('http://c.tieba.baidu.com/c/c/bawu/commitprison');
    $data = array(
        'BDUSS'  => $bduss,
        'day'    => $day, // 1 7 10 封禁时长
        'fid'    => misc::getFid($tieba),
        'ntn'    => 'banid',
        'portrait' => $portrait,
        'reason' => $r,
        'tbs'    => misc::getTbs(0, $bduss),
        'un'     => $name,
        'word'   => $tieba,
        'z'      => 4623534287 // 随便打的, 不要应该也行
    );
    $sign_str = '';
    foreach ($data as $k => $v) {
        $sign_str .= $k . '=' . $v;
    }
    $sign = strtoupper(md5($sign_str . 'tiebaclient!!!'));
    $data['sign'] = $sign;
    $tl->set(CURLOPT_RETURNTRANSFER, true);
    $rt = $tl->post($data);
    return $rt;
}
/**
 * 获取任职信息
 *
 * @param $bduss
 * @param $tieba_name
 * @return bool|string
 */
function ver4_get_manager_web_backstage(string $bduss, string $tieba_name)
{
    try {
        $tl = new Wcurl('http://tieba.baidu.com/bawu2/platform/index?ie=utf-8&word=' . $tieba_name);
        $tl->addCookie('BDUSS=' . $bduss);
        $tl->set(CURLOPT_RETURNTRANSFER, true);
        $rt = $tl->get();
        $tl->close();

        //遍码转换
        $rt = mb_convert_encoding($rt, "utf-8", "gbk");

        return $rt;
    } catch (Exception $exception) {
        return '';
    }
}

//某个pid下帐号是否为吧务
function ver4_is_manager($pid, string $tieba_name): bool {
    return preg_match('/<p class="forum_list_position">(.*?)<\/p>/', ver4_get_manager_web_backstage(misc::getCookie($pid), $tieba_name));
    //TODO 大概以后还需要细分职能? 反正现在不需要
    //return !(!isset($role[1]) || empty($role[1]));
}
