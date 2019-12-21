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
function ban($pid, $name, $tieba, $reason)
{
    $bduss = misc::getCookie($pid);
    $r = empty($reason) ? '您因为违反吧规，已被吧务封禁，如有疑问请联系吧务！' : $reason;
    $tl = new wcurl('http://c.tieba.baidu.com/c/c/bawu/commitprison');
    $data = array(
        'BDUSS'  => $bduss,
        'day'    => 10,
        'fid'    => misc::getFid($tieba),
        'ntn'    => 'banid',
        'reason' => $r,
        'tbs'    => misc::getTbs(0, $bduss),
        'un'     => $name,
        'word'   => $tieba,
        'z'      => 4623534287
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
