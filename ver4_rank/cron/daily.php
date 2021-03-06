<?php if (!defined('SYSTEM_ROOT')) {
    die('Insufficient Permissions');
}

$d = date('d');
$day = option::get('ver4_rank_daily');
if ($d != $day) {
    global $m;
    $a = 0;
    $m->query("TRUNCATE TABLE `" . DB_NAME . "`.`" . DB_PREFIX . "ver4_rank_list`");
    while (true) {
        $a++;
        $uinfo = wcurl::xget("http://tieba.baidu.com/celebrity/submit/getNpcRank?pn={$a}&ps=999");
        $ju = json_decode($uinfo, true);
        if (!empty($ju['no'])) {
            break;
        }
        foreach ($ju['data']['npc_rank'] as $value) {
            $now = time();
            $v = $value['npc_info'];
            $x = $m->fetch_array($m->query("SELECT * FROM `" . DB_NAME . "`.`" . DB_PREFIX . "ver4_rank_list` WHERE `name` = '{$v['npc_name']}'"));
            if (empty($x['id'])) {
                $m->query("INSERT INTO `" . DB_NAME . "`.`" . DB_PREFIX . "ver4_rank_list` (`fid`,`nid`,`name`,`tieba`,`date`) 
				VALUES ('{$v['npc_forum_id']}','{$v['npc_id']}','{$v['npc_name']}','{$v['npc_forum_name']}',{$now})");
            }
        }
    }
    option::set('ver4_rank_daily', $d);
}
