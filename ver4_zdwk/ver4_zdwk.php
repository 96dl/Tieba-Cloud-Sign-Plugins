<?php if (!defined('SYSTEM_ROOT')) {
    die('Insufficient Permissions');
}

function ver4_zdwk_nav()
{
    echo '<li ';
    if (isset($_GET['plugin']) && $_GET['plugin'] == 'ver4_zdwk') {
        echo 'class="active"';
    }
    echo '><a href="index.php?plugin=ver4_zdwk"><span class="glyphicon glyphicon-book"></span> 百度知道签到</a></li>';
}

addAction('navi_1', 'ver4_zdwk_nav');
addAction('navi_7', 'ver4_zdwk_nav');

function zdsign($bduss)
{
    $c = new wcurl('https://zhidao.baidu.com/msubmit/signin');
    $c->addCookie(array('BDUSS' => $bduss));
    $c->get();
}
/*function zdsign($bduss){
    $c = new wcurl('https://zhidao.baidu.com/mmisc/ajaxsigninfo');
    $c->addCookie('BDUSS=' . $bduss);
    $stoken = $c->get();
    $c->close();
    $stoken = textMiddle($stoken, '"stoken":"', '",');
    if ($stoken != "") {
        $c = new wcurl('http://zhidao.baidu.com/submit/user');
        $c->addCookie('BDUSS=' . $bduss);
        $c->post(array('cm' => '100509', 'utdata' => '90,90,102,96,107,101,99,97,96,90,98,103,103,99,127,106,99,99,14138554765830', 'stoken' => $stoken));
    }
}*/

/*function wksign($bduss){
	$head = array();
	$head[] = 'User-Agent: Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/50.0.2661.102 Safari/537.36';
	$head[] = 'Referer: http://wenku.baidu.com/task/browse/daily';
	$c = new wcurl('http://wenku.baidu.com/task/submit/signin',$head);
	$c->addCookie('BDUSS=' . $bduss);
	$c->get();
}*/