<?php if (!defined('SYSTEM_ROOT')) {
    die('Insufficient Permissions');
}
loadhead();
global $m;
$uid = UID;
$b = $m->fetch_array($m->query("SELECT count(id) AS `c`FROM `" . DB_NAME . "`.`" . DB_PREFIX . "baiduid` WHERE `uid` = {$uid}"));
if ($b['c'] < 1) {
    echo '<div class="alert alert-warning">您需要先绑定至少一个百度ID才可以使用本功能</div>';
    die;
}

?>
<h2>贴吧云封禁</h2>
<br>
<?php
if (isset($_GET['success'])) {
    echo '<div class="alert alert-success">' . htmlspecialchars($_GET['success']) . '</div>';
}
if (isset($_GET['error'])) {
    echo '<div class="alert alert-danger">' . htmlspecialchars($_GET['error']) . '</div>';
}

$us = $m->fetch_array($m->query("SELECT * FROM `" . DB_NAME . "`.`" . DB_PREFIX . "ver4_ban_userset` WHERE `uid` = {$uid}"));

if (isset($_GET['save'])) {
    $con = isset($_POST['ban_c']) ? sqladds($_POST['ban_c']) : '';
    $open = isset($_POST['open']) ? $_POST['open'] : 0;
    if (!empty($open)) {
        option::uset('ver4_ban_open', 1, $uid);
    } else {
        option::uset('ver4_ban_open', 0, $uid);
    }
    if (empty($us['uid'])) {
        $m->query("INSERT INTO `" . DB_NAME . "`.`" . DB_PREFIX . "ver4_ban_userset` (`uid`,`c`) VALUES ({$uid},'{$con}')");
    } else {
        $m->query("UPDATE `" . DB_NAME . "`.`" . DB_PREFIX . "ver4_ban_userset` SET `c` = '{$con}' WHERE `uid` = {$uid}");
    }
    redirect('index.php?plugin=ver4_ban&success=' . urlencode('您的设置已成功保存'));
}

if (isset($_GET['duser'])) {
    $id = isset($_GET['id']) ? sqladds($_GET['id']) : '';
    if (!empty($id)) {
        global $m;
        $m->query("DELETE FROM `" . DB_NAME . "`.`" . DB_PREFIX . "ver4_ban_list` WHERE `id` = '{$id}' AND `uid` = {$uid}");
        redirect('index.php?plugin=ver4_ban&success=' . urlencode('已成功删除该被封禁ID，最迟24小时后该ID不会再被封禁！'));
    } else {
        redirect('index.php?plugin=ver4_ban&error=' . urlencode('ID不合法'));
    }
}
if (isset($_GET['dauser'])) {
    global $m;
    $m->query("DELETE FROM `" . DB_NAME . "`.`" . DB_PREFIX . "ver4_ban_list` WHERE `uid` = {$uid}");
    redirect('index.php?plugin=ver4_ban&success=' . urlencode('循环云封禁列表已成功清空！'));
}
if (isset($_GET['newuser'])) {
    $pid = isset($_POST['pid']) ? sqladds($_POST['pid']) : '';
    $user = isset($_POST['user']) ? sqladds($_POST['user']) : '';
    $tieba = isset($_POST['tieba']) ? sqladds($_POST['tieba']) : '';

    //判定吧务权限
    if (!ver4_is_manager($pid, $tieba)) {
        redirect('index.php?plugin=ver4_ban&error=' . urlencode("您不是 {$tieba}吧 的吧务"));
    }

    $rts = isset($_POST['rts']) && !empty($_POST['rts']) ? sqladds($_POST['rts']) : date('Y-m-d');
    $rte = isset($_POST['rte']) ? sqladds($_POST['rte']) : '2026-12-31';

    $sy = (int)substr($rts, 0, 4);//取得年份
    $sm = (int)substr($rts, 5, 2);//取得月份
    $sd = (int)substr($rts, 8, 2);//取得日期
    $stime = mktime(0, 0, 0, $sm, $sd, $sy);

    $ey = (int)substr($rte, 0, 4);//取得年份
    $em = (int)substr($rte, 5, 2);//取得月份
    $ed = (int)substr($rte, 8, 2);//取得日期
    $etime = mktime(0, 0, 0, $em, $ed, $ey);

    if (empty($pid) || empty($user) || empty($tieba)) {
        redirect('index.php?plugin=ver4_ban&error=' . urlencode('信息不完整，添加失败！'));
    }

    if ($stime > 1988150400 || $etime > 1988150400 || $stime < 0 || $etime < 0) {
        redirect('index.php?plugin=ver4_ban&error=' . urlencode('开始或者结束时间格式不正确！'));
    }

    if (date('Y-m-d', $stime) != $rts || date('Y-m-d', $etime) != $rte) {
        redirect('index.php?plugin=ver4_ban&error=' . urlencode('开始或者结束时间格式不正确！'));
    }

    if ($stime > $etime) {
        redirect('index.php?plugin=ver4_ban&error=' . urlencode('开始时间不能大于结束时间！'));
    }

    global $m;
    $p = $m->fetch_array($m->query("SELECT * FROM `" . DB_NAME . "`.`" . DB_PREFIX . "baiduid` WHERE `id` = '{$pid}'"));
    if ($p['uid'] != UID) {
        redirect('index.php?plugin=ver4_ban&error=' . urlencode('你不能替他人添加帖子'));
    }

    $limit = option::get('ver4_ban_limit');
    $t = $m->fetch_array($m->query("SELECT count(id) AS `c` FROM `" . DB_NAME . "`.`" . DB_PREFIX . "ver4_ban_list` WHERE `uid` = {$uid}"));
    if ($t['c'] >= $limit) {
        redirect('index.php?plugin=ver4_ban&error=' . urlencode("站点设置上限添加{$limit}个百度ID"));
    }
    $ru = explode("\n", $user);
    $notExistList = "";
    foreach ($ru as $k => $v) {
        $v = trim(str_replace(["\r", '@'], '', $v));//去除特殊字符串
        //获取信息
        $banUserInfo = json_decode((new wcurl("https://tieba.baidu.com/home/get/panel?ie=utf-8&" . (preg_match('/^tb\.1\./', $v) ? "id={$v}" : "un={$v}")))->get(), true);
        if ($banUserInfo["no"] === 0) {
            $name = $banUserInfo["data"]["name"];
            $name_show = $banUserInfo["data"]["name_show"];//昵称仅供标记, 谁都不想在没id的号里面看portrait对吧
            $portrait = $banUserInfo["data"]["portrait"];
            $t = $m->fetch_array($m->query("SELECT count(id) AS `c` FROM `" . DB_NAME . "`.`" . DB_PREFIX . "ver4_ban_list` WHERE `uid` = {$uid}"));
            if ($t['c'] < $limit && !empty($v)) {
                $m->query("INSERT IGNORE INTO `" . DB_NAME . "`.`" . DB_PREFIX . "ver4_ban_list` (`uid`,`pid`,`name`,`name_show`,`portrait`,`tieba`,`stime`,`etime`,`date`) VALUES ({$uid},'{$pid}','{$name}','{$name_show}','{$portrait}','{$tieba}','{$stime}','{$etime}',0)");// ON DUPLICATE KEY UPDATE `uid`={$uid},`pid`='{$pid}',`name`='{$name}',`name_show`='{$name_show}',`portrait`='{$portrait}',`tieba`='{$tieba}',`stime`='{$stime}',`etime`='{$etime}'//TODO 插入时更新, 以后说不定用得上
            }
        } else {
            $notExistList .= ", {$v}";//添加不存在之人//某些神秘人无法取得信息
        }
    }
    redirect('index.php?plugin=ver4_ban&success=' . ($notExistList ? urlencode("部分ID添加成功{$notExistList}未能添加成功") : urlencode('所有ID已添加到封禁列表，如超出限制会自动舍弃，系统稍后会进行封禁~~哇咔咔')) . "。昵称仅供标记，对应用户修改后的昵称并不会实时反馈到本页");
}
?>
<h4>基本设置</h4>
<br>
<form action="index.php?plugin=ver4_ban&save" method="post">
    <table class="table table-hover">
        <tbody>
        <tr>
            <td>
                <b>开启云封禁</b><br>
                开启后每天会对列表用户进行封禁处理
            </td>
            <td>
                <input type="radio" name="open"
                       value="1" <?php echo empty(option::uget('ver4_ban_open', $uid)) ? '' : 'checked' ?>> 开启
                <input type="radio" name="open"
                       value="0" <?php echo empty(option::uget('ver4_ban_open', $uid)) ? 'checked' : '' ?>> 关闭
            </td>
        </tr>
        <tr>
            <td>
                <b>封禁提示内容</b><br>
                用户被封禁后消息中心显示的提示内容
            </td>
            <td>
                <input type="text" class="form-control" name="ban_c" value="<?= isset($us['c']) ? $us["c"] : "" ?>"
                       placeholder="请设置用户被封禁提示的内容（留空使用默认"您因为违反吧规，已被吧务封禁，如有疑问请联系吧务！"）">
            </td>
        </tr>
        <tr>
            <td>
                <input type="submit" class="btn btn-primary" value="保存设置">
            </td>
            <td></td>
        </tr>
        </tbody>
    </table>
</form>
<br>
<h4>用户日志</h4>
<br>


<div class="bs-example bs-example-tabs" data-example-id="togglable-tabs">
    <?php
    $a = 0;
    $bid = $m->query("SELECT * FROM `" . DB_NAME . "`.`" . DB_PREFIX . "baiduid` WHERE `uid` = {$uid}");
    ?>
    <ul id="myTabs" class="nav nav-tabs" role="tablist">
        <?php
        while ($x = $m->fetch_array($bid)) {
            ?>
            <li role="presentation" class="<?= empty($a) ? 'active' : '' ?>"><a href="#b<?= $x['id'] ?>" role="tab"
                                                                                data-toggle="tab"><?= $x['name'] ?></a>
            </li>
            <?php
            $a++;
        }
        ?>
    </ul>
    <div id="myTabContent" class="tab-content">
        <?php
        $b = 0;
        $bid = $m->query("SELECT * FROM `" . DB_NAME . "`.`" . DB_PREFIX . "baiduid` WHERE `uid` = {$uid}");
        while ($r = $m->fetch_array($bid)) {
            ?>
            <div role="tabpanel" class="tab-pane fade <?= empty($b) ? 'active in' : '' ?>" id="b<?= $r['id'] ?>">
                <table class="table table-striped">
                    <thead>
                    <tr>
                        <td>序号</td>
                        <td>贴吧</td>
                        <td>被封ID</td>
                        <td>昵称</td>
                        <td>Portrait</td>
                        <td>开始时间</td>
                        <td>结束时间</td>
                        <td>上次封禁</td>
                        <td>日志</td>
                        <td>操作</td>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    $a = 0;
            $uu = $m->query("SELECT * FROM `" . DB_NAME . "`.`" . DB_PREFIX . "ver4_ban_list` WHERE `pid` = {$r['id']}");
            while ($r1 = $m->fetch_array($uu)) {
                $a++; ?>
                        <tr>
                            <td><?= $r1['id'] ?></td>
                            <td><a href="http://tieba.baidu.com/f?kw=<?= $r1['tieba'] ?>"
                                   target="_blank"><?= $r1['tieba'] ?></a></td>
                            <td><a href="http://tieba.baidu.com/home/main/?ie=utf-8&un=<?= $r1['name'] ?>"
                                   target="_blank"><?= $r1['name'] ?></a></td>
                            <td><?= $r1['name_show'] ?></td>
                            <td><a href="http://tieba.baidu.com/home/main/?ie=utf-8&id=<?= $r1['portrait'] ?>"
                                   target="_blank"><?= $r1['portrait'] ?></a></td>
                            <td><?= date('Y-m-d', $r1['stime']) ?></td>
                            <td><?= date('Y-m-d', $r1['etime']) ?></td>
                            <td><?= date('Y-m-d', $r1['date']) ?></td>
                            <td>
                                <a class="btn btn-info" href="javascript:;" data-toggle="modal"
                                   data-target="#LogUser<?= $r1['id'] ?>">查看</a>
                            </td>
                            <td>
                                <a class="btn btn-danger" href="javascript:;" data-toggle="modal"
                                   data-target="#DelUser<?= $r1['id'] ?>">删除</a>
                            </td>
                        </tr>
                        <div class="modal fade" id="LogUser<?= $r1['id'] ?>" tabindex="-1" role="dialog"
                             aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <button type="button" class="close" data-dismiss="modal"><span
                                                aria-hidden="true">&times;</span><span
                                                class="sr-only">Close</span></button>
                                        <h4 class="modal-title">日志详情</h4>
                                    </div>
                                    <div class="modal-body">
                                        <div class="input-group">
                                            <?= empty($r1['log']) ? '暂无日志' : $r1['log'] ?>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-default" data-dismiss="modal">取消</button>
                                    </div>
                                </div><!-- /.modal-content -->
                            </div><!-- /.modal-dialog -->
                        </div><!-- /.modal -->

                        <div class="modal fade" id="DelUser<?= $r1['id'] ?>" tabindex="-1" role="dialog"
                             aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <button type="button" class="close" data-dismiss="modal"><span
                                                aria-hidden="true">&times;</span><span
                                                class="sr-only">Close</span></button>
                                        <h4 class="modal-title">温馨提示</h4>
                                    </div>
                                    <div class="modal-body">
                                        <form action="index.php?plugin=ver4_ban&duser&id=<?= $r1['id'] ?>"
                                              method="post">
                                            <div class="input-group">
                                                您确定要删除这个被封禁用户嘛(删除后无法恢复)？
                                            </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-default" data-dismiss="modal">取消</button>
                                        <button type="submit" class="btn btn-primary">确定</button>
                                    </div>
                                    </form>
                                </div><!-- /.modal-content -->
                            </div><!-- /.modal-dialog -->
                        </div><!-- /.modal -->
                        <?php
            }
            if (empty($a)) {
                echo '<tr><td>暂无需要封禁的用户</td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>';
            } ?>
                    </tbody>
                </table>
            </div>
            <?php
            $b++;
        }
        ?>
    </div>
</div>
<a class="btn btn-success" href="javascript:;" data-toggle="modal" data-target="#AddUser">添加用户</a>
<a class="btn btn-danger" href="javascript:;" data-toggle="modal" data-target="#DelUser">清空列表</a>


<div class="modal fade" id="DelUser" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><span
                        aria-hidden="true">&times;</span><span
                        class="sr-only">Close</span></button>
                <h4 class="modal-title">温馨提示</h4>
            </div>
            <div class="modal-body">
                <form action="index.php?plugin=ver4_ban&dauser" method="post">
                    <div class="input-group">
                        您确定要清空列表（该执行后无法恢复）？
                    </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">取消</button>
                <button type="submit" class="btn btn-primary">确定</button>
            </div>
            </form>
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div><!-- /.modal -->

<div class="modal fade" id="AddUser" tabindex="-1" role="dialog" aria-labelledby="AddUser" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span
                        class="sr-only">Close</span></button>
                <h4 class="modal-title">添加被封禁用户信息</h4>
            </div>
            <div class="modal-body">
                <form action="index.php?plugin=ver4_ban&newuser" method="post">
                    <div class="input-group">
                        <span class="input-group-addon">请选择对应账号</span>
                        <select name="pid" required="" class="form-control">
                            <?php
                            global $m;
                            $b = $m->query("SELECT * FROM `" . DB_NAME . "`.`" . DB_PREFIX . "baiduid` WHERE `uid` = {$uid}");
                            while ($x = $m->fetch_array($b)) {
                                echo '<option value="' . $x['id'] . '">' . $x['name'] . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <br>
                    <div class="input-group">
                        <span class="input-group-addon">开始时间(日期)</span>
                        <input type="text" class="form-control" name="rts" placeholder="日期格式：yyyy-mm-dd,留空默认立即开始">
                    </div>
                    <br>
                    <div class="input-group">
                        <span class="input-group-addon">结束时间(日期)
                            <input type="checkbox" aria-label="Auto end mode" id="AutoEndMode">
                        </span>
                        <input type="text" class="form-control" name="rte" value="2026-12-31"
                               placeholder="日期格式：yyyy-mm-dd" required>
                    </div>
                    <br>
                    <div class="input-group">
                        <span class="input-group-addon">贴吧</span>
                        <input type="text" class="form-control" min="60" max="99999" name="tieba" placeholder="输入贴吧名（不带末尾吧字）"
                               required>
                    </div>
                    <br>
                    <div class="modal-body">
                        <textarea id="banUserList" name="user" class="form-control" rows="10"
                                  placeholder="输入待封禁的 用户名 或 Portrait，一行一个；用户名支持某些软件生成的例如：@AAA 格式 (自动清除@)，Portrait仅支持新版portrait，即 tb.1.xxx.xxxxx 格式，粘贴个人页链接会自动处理"></textarea>
                    </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">取消</button>
                <button type="submit" class="btn btn-primary">提交</button>
            </div>
            </form>
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
    <script>
      $('#banUserList').bind('input propertychange', function(){
        if($(this).val() != ""){
          $(this).val(Array.from(new Set($(this).val().split("\n").map(x => {
            x = x.replace(/@|\r/, "")
            let testPortrait = /tb.1.[\w-~]{8}.[\w-~]{22}/.exec(x)//检测portrait
            if (testPortrait !== null) {
              x = testPortrait[0]
            }
            return x
          }))).join("\n"))
        }
      })
    </script>
</div><!-- /.modal -->
