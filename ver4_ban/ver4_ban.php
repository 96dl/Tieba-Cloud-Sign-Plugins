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
function ver4_ban($pid, $portrait, $name, $name_show, $tieba, $reason, int $day = 1)
{
    $bduss = misc::getCookie($pid);
    $r = empty($reason) ? '您因为违反吧规，已被吧务封禁，如有疑问请联系吧务' : $reason;
    $tl = new wcurl('https://tieba.baidu.com/pmc/blockid', [
        'Connection: keep-alive',
        'Accept: application/json, text/javascript, */*; q=0.01',
        'Accept-Language: zh-CN,zh;q=0.9,en;q=0.8',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/89.0.4389.114 Safari/537.36',
        'Content-Type: application/x-www-form-urlencoded; charset=UTF-8',
        'Origin: https://tieba.baidu.com',
        'Referer: https://tieba.baidu.com/',
        'X-Requested-With: XMLHttpRequest',
    ]);
    $data = array(
        'day'    => $day, // 1 3 10 封禁时长
        'fid'    => misc::getFid($tieba),
        'tbs'    => misc::getTbs(0, $bduss),
        'ie'     => 'utf8',
        'nick_name[]' => $name_show ?? '',
        'pid'    => mt_rand(100000000000, 150000000000),
        'reason' => $r
    );
    $tl->addCookie('BDUSS=' . $bduss);
    $portrait !== null ? $data['portrait[]'] = $portrait : $data['user_name[]'] = $name;
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
