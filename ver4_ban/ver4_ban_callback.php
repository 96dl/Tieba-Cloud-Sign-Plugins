<?php if (!defined('SYSTEM_ROOT')) {
    die('Insufficient Permissions');
}
/**
 * 安装插件时会被调用
 */
function callback_install()
{
    global $m;
    $m->query('
        CREATE TABLE IF NOT EXISTS `' . DB_PREFIX . 'ver4_ban_list` (
         `id` int NOT NULL AUTO_INCREMENT,
         `uid` int NOT NULL,
         `pid` int NOT NULL,
         `name` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
         `name_show` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
         `portrait` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
         `tieba` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
         `stime` int NOT NULL,
         `etime` int NOT NULL,
         `log` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
         `date` int NOT NULL DEFAULT \'0\',
         PRIMARY KEY (`id`)
        ) CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
    ');
    $m->query('
        CREATE TABLE IF NOT EXISTS `' . DB_PREFIX . 'ver4_ban_userset` (
         `id` int NOT NULL AUTO_INCREMENT,
         `uid` int NOT NULL,
         `c` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
         PRIMARY KEY (`id`)
        ) CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
    ');
}

/**
 * 激活插件时会被调用
 */
function callback_init()
{
    option::set('ver4_ban_id', 0);
    option::set('ver4_ban_limit', 20);
    option::set('ver4_ban_break_check', '0');
    cron::set('ver4_ban_dopost', 'plugins/ver4_ban/cron/dopost.php', 0, 0, 0);
}

/**
 * 禁用插件时会被调用
 */
function callback_inactive()
{
    option::del('ver4_ban_id');
    option::del('ver4_ban_limit');
    option::del('ver4_ban_break_check');
    cron::del('ver4_ban_dopost');
}

/**
 * 卸载插件时会被调用
 * 卸载插件前，如果插件是激活的，会自动禁用并调用 callback_inactive()
 */
function callback_remove()
{
    //在这里做点事
    global $m;
    $m->query("DROP TABLE IF EXISTS `".DB_PREFIX."ver4_ban_userset`");
    $m->query("DROP TABLE IF EXISTS `".DB_PREFIX."ver4_ban_list`");
}

/**
 * 升级插件时会被调用
 * 系统会传入当前数据库的版本号、当前插件文件中说明的版本号
 * 必须有返回值，如果返回新的版本号，新版本号由系统记录到数据库；如果返回false，将终止操作且不记录到数据库
 */
function callback_update($ver1, $ver2)
{
    //ver1 是当前数据库的版本号
    //ver2 是当前插件文件中说明的版本号，即 插件名_desc.php 的 ['plugin']['version'] 的值
    //在这里做点事
    return false; //我不干了！
}

/**
 * 插件自定义保存设置函数
 * 插件调用方法：setting.php?mod=setplugin:插件名称
 * 然后系统会调用 插件名_callback.php 的 callback_setting()
 */
function callback_setting()
{
    //在这里做点事
    //dump($_POST); //看看前端给我POST了什么东西
}
