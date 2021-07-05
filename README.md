# Tieba-Cloud-Sign-Plugins

## 插件列表
### 云封禁
吧务专用，可循环封禁指定账号

**更新1.1版后请执行**
```sql
ALTER TABLE `tc_ver4_ban_list` CONVERT TO CHARACTER SET `utf8mb4` COLLATE `utf8mb4_general_ci`;
ALTER TABLE `tc_ver4_ban_userset` CONVERT TO CHARACTER SET `utf8mb4` COLLATE `utf8mb4_general_ci`;
ALTER TABLE `tc_ver4_ban_userset` CHANGE `c` `c` TEXT CHARACTER SET `utf8mb4` COLLATE `utf8mb4_general_ci`; 
ALTER TABLE `tc_ver4_ban_list`
  CHANGE `name` `name` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL,
  CHANGE `tieba` `tieba` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  CHANGE `log` `log` LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL,
  ADD `name_show` TEXT NULL AFTER `name`,
  ADD `portrait` TEXT NULL AFTER `name_show`;
UPDATE `tc_ver4_ban_list` SET `log` = REPLACE(`log`, "<br>", "<br>\n");
```
其中 `tc_` 是默认表名前缀，如有过修改请自行修改表名前缀

### 云知道抽奖
自动完成知道抽奖，每日

### 云回贴
强大的自定义回帖功能（半失效）

### 名人堂
每日自动助攻贴吧名人堂

**更新1.1版后请执行**
```sql
ALTER TABLE `tc_ver4_rank_list`
  CHANGE `nid` `nid` varchar(15) COLLATE 'utf8mb4_general_ci' NOT NULL AFTER `fid`,
  CHANGE `name` `name` varchar(255) COLLATE 'utf8mb4_general_ci' NOT NULL AFTER `nid`,
  CHANGE `tieba` `tieba` varchar(255) COLLATE 'utf8mb4_general_ci' NOT NULL AFTER `name`;
```
其中 `tc_` 是默认表名前缀，如有过修改请自行修改表名前缀

### 自动刷新贴吧列表
完全自动每日刷新贴吧列表

### 贴吧云审查
吧务专用，审查贴吧内不符合规范的帖子

### 云签AmazeUI
云签的一款UI产品

### 知道文库签到
自动签到百度知道，文库已废
