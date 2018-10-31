# ************************************************************
# Sequel Pro SQL dump
# Version 4541
#
# http://www.sequelpro.com/
# https://github.com/sequelpro/sequelpro
#
# Host: 127.0.0.1 (MySQL 5.7.18)
# Database: 2
# Generation Time: 2017-07-29 06:28:10 +0000
# ************************************************************


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


-- ----------------------------
-- Table structure for `ss_node`
-- ----------------------------
CREATE TABLE `ss_node` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(128) NOT NULL DEFAULT '' COMMENT '名称',
  `group_id` INT(11) NOT NULL DEFAULT '0' COMMENT '所属分组',
  `country_code` CHAR(5) NULL DEFAULT '' COMMENT '国家代码',
  `server` VARCHAR(128) NULL DEFAULT '' COMMENT '服务器域名地址',
  `ip` CHAR(15) NULL DEFAULT '' COMMENT '服务器IPV4地址',
  `ipv6` CHAR(128) NULL DEFAULT '' COMMENT '服务器IPV6地址',
  `desc` VARCHAR(255) NULL DEFAULT '' COMMENT '节点简单描述',
  `method` VARCHAR(32) NOT NULL DEFAULT 'aes-192-ctr' COMMENT '加密方式',
  `protocol` VARCHAR(128) NOT NULL DEFAULT 'auth_chain_a' COMMENT '协议',
  `protocol_param` VARCHAR(128) NULL DEFAULT '' COMMENT '协议参数',
  `obfs` VARCHAR(128) NOT NULL DEFAULT 'tls1.2_ticket_auth' COMMENT '混淆',
  `obfs_param` VARCHAR(128) NULL DEFAULT '' COMMENT '混淆参数',
  `traffic_rate` FLOAT NOT NULL DEFAULT '1.00' COMMENT '流量比率',
  `bandwidth` INT(11) NOT NULL DEFAULT '100' COMMENT '出口带宽，单位M',
  `traffic` BIGINT(20) NOT NULL DEFAULT '1000' COMMENT '每月可用流量，单位G',
  `monitor_url` VARCHAR(255) NULL DEFAULT NULL COMMENT '监控地址',
  `is_subscribe` TINYINT(4) NULL DEFAULT '1' COMMENT '是否允许用户订阅该节点：0-否、1-是',
  `ssh_port` SMALLINT(6) UNSIGNED NOT NULL DEFAULT '22' COMMENT 'SSH端口',
  `is_tcp_check` TINYINT(4) NOT NULL DEFAULT '1' COMMENT '是否开启检测: 0-不开启、1-开启',
  `icmp` TINYINT(4) NOT NULL DEFAULT '1' COMMENT 'ICMP检测：-2-内外都不通、-1-内不通外通、0-外不通内通、1-内外都通',
  `tcp` TINYINT(4) NOT NULL DEFAULT '1' COMMENT 'TCP检测：-2-内外都不通、-1-内不通外通、0-外不通内通、1-内外都通',
  `udp` TINYINT(4) NOT NULL DEFAULT '1' COMMENT 'ICMP检测：-2-内外都不通、-1-内不通外通、0-外不通内通、1-内外都通',
  `compatible` TINYINT(4) NULL DEFAULT '0' COMMENT '兼容SS',
  `single` TINYINT(4) NULL DEFAULT '0' COMMENT '单端口多用户：0-否、1-是',
  `single_force` TINYINT(4) NULL DEFAULT NULL COMMENT '模式：0-兼容模式、1-严格模式',
  `single_port` VARCHAR(50) NULL DEFAULT '' COMMENT '端口号，用,号分隔',
  `single_passwd` VARCHAR(50) NULL DEFAULT '' COMMENT '密码',
  `single_method` VARCHAR(50) NULL DEFAULT '' COMMENT '加密方式',
  `single_protocol` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '协议',
  `single_obfs` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '混淆',
  `sort` INT(11) NOT NULL DEFAULT '0' COMMENT '排序值，值越大越靠前显示',
  `status` TINYINT(4) NOT NULL DEFAULT '1' COMMENT '状态：0-维护、1-正常',
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_group` (`group_id`),
	INDEX `idx_sub` (`is_subscribe`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='节点信息表';


-- ----------------------------
-- Table structure for `ss_node_info`
-- ----------------------------
CREATE TABLE `ss_node_info` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `node_id` int(11) NOT NULL DEFAULT '0' COMMENT '节点ID',
  `uptime` float NOT NULL COMMENT '更新时间',
  `load` varchar(32) NOT NULL COMMENT '负载',
  `log_time` int(11) NOT NULL COMMENT '记录时间',
  PRIMARY KEY (`id`),
  INDEX `idx_node_id` (`node_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='节点负载信息';


-- ----------------------------
-- Table structure for `ss_node_online_log`
-- ----------------------------
CREATE TABLE `ss_node_online_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `node_id` int(11) NOT NULL COMMENT '节点ID',
  `online_user` int(11) NOT NULL COMMENT '在线用户数',
  `log_time` int(11) NOT NULL COMMENT '记录时间',
  PRIMARY KEY (`id`),
  INDEX `idx_node_id` (`node_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='节点在线信息';


-- ----------------------------
-- Table structure for `ss_node_label`
-- ----------------------------
CREATE TABLE `ss_node_label` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `node_id` int(11) NOT NULL DEFAULT '0' COMMENT '用户ID',
  `label_id` int(11) NOT NULL DEFAULT '0' COMMENT '标签ID',
  PRIMARY KEY (`id`),
  INDEX `idx` (`node_id`,`label_id`),
  INDEX `idx_node_id` (`node_id`),
  INDEX `idx_label_id` (`label_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='节点标签';


-- ----------------------------
-- Table structure for `user`
-- ----------------------------
CREATE TABLE `user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(128) NOT NULL DEFAULT '' COMMENT '用户名',
  `password` varchar(64) NOT NULL DEFAULT '' COMMENT '密码',
  `ssruuid` varchar(128) NOT NULL DEFAULT '' COMMENT 'UUID',
  `port` int(11) NOT NULL DEFAULT '0' COMMENT 'SS端口',
  `passwd` varchar(16) NOT NULL DEFAULT '' COMMENT 'SS密码',
  `transfer_enable` bigint(20) NOT NULL DEFAULT '1073741824000' COMMENT '可用流量，单位字节，默认1TiB',
  `u` bigint(20) NOT NULL DEFAULT '0' COMMENT '已上传流量，单位字节',
  `d` bigint(20) NOT NULL DEFAULT '0' COMMENT '已下载流量，单位字节',
  `t` int(11) NOT NULL DEFAULT '0' COMMENT '最后使用时间',
  `enable` tinyint(4) NOT NULL DEFAULT '1' COMMENT 'SS状态',
  `method` varchar(30) NOT NULL DEFAULT 'aes-192-ctr' COMMENT '加密方式',
  `protocol` varchar(30) NOT NULL DEFAULT 'auth_chain_a' COMMENT '协议',
  `protocol_param` varchar(255) DEFAULT '' COMMENT '协议参数',
  `obfs` varchar(30) NOT NULL DEFAULT 'tls1.2_ticket_auth' COMMENT '混淆',
  `obfs_param` varchar(255) DEFAULT '' COMMENT '混淆参数',
  `speed_limit_per_con` int(255) NOT NULL DEFAULT '204800' COMMENT '单连接限速，默认200M，单位KB',
  `speed_limit_per_user` int(255) NOT NULL DEFAULT '204800' COMMENT '单用户限速，默认200M，单位KB',
  `gender` tinyint(4) NOT NULL DEFAULT '1' COMMENT '性别：0-女、1-男',
  `wechat` varchar(30) DEFAULT '' COMMENT '微信',
  `qq` varchar(20) DEFAULT '' COMMENT 'QQ',
  `usage` VARCHAR(10) NOT NULL DEFAULT '4' COMMENT '用途：1-手机、2-电脑、3-路由器、4-其他',
  `pay_way` tinyint(4) NOT NULL DEFAULT '0' COMMENT '付费方式：0-免费、1-季付、2-月付、3-半年付、4-年付',
  `balance` int(11) NOT NULL DEFAULT '0' COMMENT '余额，单位分',
  `score` int(11) NOT NULL DEFAULT '0' COMMENT '积分',
  `enable_time` datetime DEFAULT NULL COMMENT '开通日期',
  `expire_time` datetime NOT NULL DEFAULT '2099-01-01 0:0:0' COMMENT '过期时间',
  `ban_time` int(11) NOT NULL DEFAULT '0' COMMENT '封禁到期时间',
  `remark` text COMMENT '备注',
  `level` tinyint(4) NOT NULL DEFAULT '1' COMMENT '等级：可定义名称',
  `is_admin` tinyint(4) NOT NULL DEFAULT '0' COMMENT '是否管理员：0-否、1-是',
  `reg_ip` varchar(20) NOT NULL DEFAULT '127.0.0.1' COMMENT '注册IP',
  `last_login` int(11) NOT NULL DEFAULT '0' COMMENT '最后登录时间',
  `referral_uid` int(11) NOT NULL DEFAULT '0' COMMENT '邀请人',
  `traffic_reset_day` tinyint(4) NOT NULL DEFAULT '0' COMMENT '流量自动重置日，0表示不重置',
  `status` tinyint(4) NOT NULL DEFAULT '0' COMMENT '状态：-1-禁用、0-未激活、1-正常',
  `remember_token` varchar(256) DEFAULT '',
  `remember_token_ios` varchar(256) DEFAULT '',
  `remember_token_mac` varchar(256) DEFAULT '',
  `remember_token_android` varchar(256) DEFAULT '',
  `remember_token_win` varchar(256) DEFAULT '',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_search` (`enable`, `status`)
) ENGINE=InnoDB AUTO_INCREMENT=10000 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户';


LOCK TABLES `user` WRITE;
/*!40000 ALTER TABLE `user` DISABLE KEYS */;

INSERT INTO `user` (`id`, `username`, `password`, `port`, `passwd`, `transfer_enable`, `u`, `d`, `t`, `enable`, `method`, `protocol`, `protocol_param`, `obfs`, `obfs_param`, `speed_limit_per_con`, `speed_limit_per_user`, `wechat`, `qq`, `usage`, `pay_way`, `balance`, `enable_time`, `expire_time`, `remark`, `is_admin`, `reg_ip`, `created_at`, `updated_at`)
VALUES (1,'admin','e10adc3949ba59abbe56e057f20f883e',10000,'@123',1073741824000,0,0,0,1,'aes-192-ctr','auth_chain_a','','tls1.2_ticket_auth','',204800,204800,'','',1,3,0.00,NULL,'2099-01-01 0:0:0',NULL,1,'127.0.0.1',NULL,NULL);

/*!40000 ALTER TABLE `user` ENABLE KEYS */;
UNLOCK TABLES;


-- ----------------------------
-- Table structure for `level`
-- ----------------------------
CREATE TABLE `level` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `level` int(11) NOT NULL DEFAULT '1' COMMENT '等级',
  `level_name` varchar(100) NOT NULL DEFAULT '' COMMENT '等级名称',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='等级';


-- ----------------------------
-- Records of `level`
-- ----------------------------
INSERT INTO `level` VALUES (1, '1', '青铜', '2017-10-26 15:56:52', '2017-10-26 15:38:58');
INSERT INTO `level` VALUES (2, '2', '白银', '2017-10-26 15:57:30', '2017-10-26 12:37:51');
INSERT INTO `level` VALUES (3, '3', '黄金', '2017-10-26 15:41:31', '2017-10-26 15:41:31');
INSERT INTO `level` VALUES (4, '4', '铂金', '2017-10-26 15:41:38', '2017-10-26 15:41:38');
INSERT INTO `level` VALUES (5, '5', '钻石', '2017-10-26 15:41:47', '2017-10-26 15:41:47');
INSERT INTO `level` VALUES (6, '6', '星耀', '2017-10-26 15:41:56', '2017-10-26 15:41:56');
INSERT INTO `level` VALUES (7, '7', '王者', '2017-10-26 15:42:02', '2017-10-26 15:42:02');


-- ----------------------------
-- Table structure for `user_traffic_log`
-- ----------------------------
CREATE TABLE `user_traffic_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL DEFAULT '0' COMMENT '用户ID',
  `u` int(11) NOT NULL DEFAULT '0' COMMENT '上传流量',
  `d` int(11) NOT NULL DEFAULT '0' COMMENT '下载流量',
  `node_id` int(11) NOT NULL DEFAULT '0' COMMENT '节点ID',
  `rate` float NOT NULL COMMENT '流量比例',
  `traffic` varchar(32) NOT NULL COMMENT '产生流量',
  `log_time` int(11) NOT NULL COMMENT '记录时间',
  PRIMARY KEY (`id`),
  INDEX `idx_user` (`user_id`),
  INDEX `idx_node` (`node_id`),
  INDEX `idx_user_node` (`user_id`,`node_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户流量日志';


-- ----------------------------
-- Table structure for `ss_config`
-- ----------------------------
DROP TABLE IF EXISTS `ss_config`;
CREATE TABLE `ss_config` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '配置名' COLLATE 'utf8mb4_unicode_ci',
  `type` TINYINT(4) NOT NULL DEFAULT '1' COMMENT '类型：1-加密方式、2-协议、3-混淆',
  `is_default` TINYINT(4) NOT NULL DEFAULT '0' COMMENT '是否默认：0-不是、1-是',
  `sort` INT(11) NOT NULL DEFAULT '0' COMMENT '排序：值越大排越前',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='通用配置';

-- ----------------------------
-- Records of ss_config
-- ----------------------------
INSERT INTO `ss_config` VALUES ('1', 'none', '1', '0', '0');
INSERT INTO `ss_config` VALUES ('2', 'rc4', '1', '0', '0');
INSERT INTO `ss_config` VALUES ('3', 'rc4-md5', '1', '0', '0');
INSERT INTO `ss_config` VALUES ('4', 'rc4-md5-6', '1', '0', '0');
INSERT INTO `ss_config` VALUES ('5', 'bf-cfb', '1', '0', '0');
INSERT INTO `ss_config` VALUES ('6', 'aes-128-cfb', '1', '0', '0');
INSERT INTO `ss_config` VALUES ('7', 'aes-192-cfb', '1', '0', '0');
INSERT INTO `ss_config` VALUES ('8', 'aes-256-cfb', '1', '1', '0');
INSERT INTO `ss_config` VALUES ('9', 'aes-128-ctr', '1', '0', '0');
INSERT INTO `ss_config` VALUES ('10', 'aes-192-ctr', '1', '0', '0');
INSERT INTO `ss_config` VALUES ('11', 'aes-256-ctr', '1', '0', '0');
INSERT INTO `ss_config` VALUES ('12', 'camellia-128-cfb', '1', '0', '0');
INSERT INTO `ss_config` VALUES ('13', 'camellia-192-cfb', '1', '0', '0');
INSERT INTO `ss_config` VALUES ('14', 'camellia-256-cfb', '1', '0', '0');
INSERT INTO `ss_config` VALUES ('15', 'salsa20', '1', '0', '0');
INSERT INTO `ss_config` VALUES ('16', 'xsalsa20', '1', '0', '0');
INSERT INTO `ss_config` VALUES ('17', 'chacha20', '1', '0', '0');
INSERT INTO `ss_config` VALUES ('18', 'xchacha20', '1', '0', '0');
INSERT INTO `ss_config` VALUES ('19', 'chacha20-ietf', '1', '0', '0');
INSERT INTO `ss_config` VALUES ('20', 'chacha20-ietf-poly1305', '1', '0', '0');
INSERT INTO `ss_config` VALUES ('21', 'chacha20-poly1305', '1', '0', '0');
INSERT INTO `ss_config` VALUES ('22', 'xchacha-ietf-poly1305', '1', '0', '0');
INSERT INTO `ss_config` VALUES ('23', 'aes-128-gcm', '1', '0', '0');
INSERT INTO `ss_config` VALUES ('24', 'aes-192-gcm', '1', '0', '0');
INSERT INTO `ss_config` VALUES ('25', 'aes-256-gcm', '1', '0', '0');
INSERT INTO `ss_config` VALUES ('26', 'sodium-aes-256-gcm', '1', '0', '0');
INSERT INTO `ss_config` VALUES ('27', 'origin', '2', '1', '0');
INSERT INTO `ss_config` VALUES ('28', 'auth_sha1_v4', '2', '0', '0');
INSERT INTO `ss_config` VALUES ('29', 'auth_aes128_md5', '2', '0', '0');
INSERT INTO `ss_config` VALUES ('30', 'auth_aes128_sha1', '2', '0', '0');
INSERT INTO `ss_config` VALUES ('31', 'auth_chain_a', '2', '0', '0');
INSERT INTO `ss_config` VALUES ('32', 'auth_chain_b', '2', '0', '0');
INSERT INTO `ss_config` VALUES ('33', 'plain', '3', '1', '0');
INSERT INTO `ss_config` VALUES ('34', 'http_simple', '3', '0', '0');
INSERT INTO `ss_config` VALUES ('35', 'http_post', '3', '0', '0');
INSERT INTO `ss_config` VALUES ('36', 'tls1.2_ticket_auth', '3', '0', '0');
INSERT INTO `ss_config` VALUES ('37', 'tls1.2_ticket_fastauth', '3', '0', '0');
INSERT INTO `ss_config` VALUES ('38', 'auth_chain_c', '2', '0', '0');
INSERT INTO `ss_config` VALUES ('39', 'auth_chain_d', '2', '0', '0');
INSERT INTO `ss_config` VALUES ('40', 'auth_chain_e', '2', '0', '0');
INSERT INTO `ss_config` VALUES ('41', 'auth_chain_f', '2', '0', '0');


-- ----------------------------
-- Table structure for `config`
-- ----------------------------
CREATE TABLE `config` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL DEFAULT '' COMMENT '配置名',
  `value` TEXT NULL COMMENT '配置值',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='系统配置';


-- ----------------------------
-- Records of config
-- ----------------------------
INSERT INTO `config` VALUES ('1', 'is_rand_port', 0);
INSERT INTO `config` VALUES ('2', 'is_user_rand_port', 0);
INSERT INTO `config` VALUES ('3', 'invite_num', 3);
INSERT INTO `config` VALUES ('4', 'is_register', 1);
INSERT INTO `config` VALUES ('5', 'is_invite_register', 1);
INSERT INTO `config` VALUES ('6', 'website_name', 'Patatas');
INSERT INTO `config` VALUES ('7', 'is_reset_password', 1);
INSERT INTO `config` VALUES ('8', 'reset_password_times', 3);
INSERT INTO `config` VALUES ('9', 'website_url', 'https://patatas.ssrosx.com');
INSERT INTO `config` VALUES ('10', 'is_active_register', 1);
INSERT INTO `config` VALUES ('11', 'active_times', 3);
INSERT INTO `config` VALUES ('12', 'login_add_score', 1);
INSERT INTO `config` VALUES ('13', 'min_rand_score', 1);
INSERT INTO `config` VALUES ('14', 'max_rand_score', 100);
INSERT INTO `config` VALUES ('15', 'wechat_qrcode', '');
INSERT INTO `config` VALUES ('16', 'alipay_qrcode', '');
INSERT INTO `config` VALUES ('17', 'login_add_score_range', 1440);
INSERT INTO `config` VALUES ('18', 'referral_traffic', 1024);
INSERT INTO `config` VALUES ('19', 'referral_percent', 0.2);
INSERT INTO `config` VALUES ('20', 'referral_money', 100);
INSERT INTO `config` VALUES ('21', 'referral_status', 1);
INSERT INTO `config` VALUES ('22', 'default_traffic', 1024);
INSERT INTO `config` VALUES ('23', 'traffic_warning', 0);
INSERT INTO `config` VALUES ('24', 'traffic_warning_percent', 80);
INSERT INTO `config` VALUES ('25', 'expire_warning', 0);
INSERT INTO `config` VALUES ('26', 'expire_days', 15);
INSERT INTO `config` VALUES ('27', 'reset_traffic', 1);
INSERT INTO `config` VALUES ('28', 'default_days', 7);
INSERT INTO `config` VALUES ('29', 'subscribe_max', 3);
INSERT INTO `config` VALUES ('30', 'min_port', 10000);
INSERT INTO `config` VALUES ('31', 'max_port', 20000);
INSERT INTO `config` VALUES ('32', 'is_captcha', 0);
INSERT INTO `config` VALUES ('33', 'is_traffic_ban', 1);
INSERT INTO `config` VALUES ('34', 'traffic_ban_value', 10);
INSERT INTO `config` VALUES ('35', 'traffic_ban_time', 60);
INSERT INTO `config` VALUES ('36', 'is_clear_log', 1);
INSERT INTO `config` VALUES ('37', 'is_node_crash_warning', 0);
INSERT INTO `config` VALUES ('38', 'crash_warning_email', '');
INSERT INTO `config` VALUES ('39', 'is_server_chan', 0);
INSERT INTO `config` VALUES ('40', 'server_chan_key', '');
INSERT INTO `config` VALUES ('41', 'is_subscribe_ban', 1);
INSERT INTO `config` VALUES ('42', 'subscribe_ban_times', 20);
INSERT INTO `config` VALUES ('43', 'paypal_status', 0);
INSERT INTO `config` VALUES ('44', 'paypal_client_id', '');
INSERT INTO `config` VALUES ('45', 'paypal_client_secret', '');
INSERT INTO `config` VALUES ('46', 'is_free_code', 0);
INSERT INTO `config` VALUES ('47', 'is_forbid_robot', 0);
INSERT INTO `config` VALUES ('48', 'subscribe_domain', '');
INSERT INTO `config` VALUES ('49', 'auto_release_port', 1);
INSERT INTO `config` VALUES ('50', 'is_youzan', 0);
INSERT INTO `config` VALUES ('51', 'youzan_client_id', '');
INSERT INTO `config` VALUES ('52', 'youzan_client_secret', '');
INSERT INTO `config` VALUES ('53', 'kdt_id', '');
INSERT INTO `config` VALUES ('54', 'initial_labels_for_user', '');
INSERT INTO `config` VALUES ('55', 'website_analytics', '');
INSERT INTO `config` VALUES ('56', 'website_customer_service', '');
INSERT INTO `config` VALUES ('57', 'register_ip_limit', 5);
INSERT INTO `config` VALUES ('58', 'goods_purchase_limit_strategy', 'none');
INSERT INTO `config` VALUES ('59', 'is_push_bear', 0);
INSERT INTO `config` VALUES ('60', 'push_bear_send_key', '');
INSERT INTO `config` VALUES ('61', 'push_bear_qrcode', '');
INSERT INTO `config` VALUES ('62', 'is_ban_status', 0);
INSERT INTO `config` VALUES ('63', 'is_namesilo', 0);
INSERT INTO `config` VALUES ('64', 'namesilo_key', '');
INSERT INTO `config` VALUES ('65', 'website_logo', '');
INSERT INTO `config` VALUES ('66', 'website_home_logo', '');
INSERT INTO `config` VALUES ('67', 'ads_add_traffic', 1);
INSERT INTO `config` VALUES ('68', 'ads_add_traffic_range', 10);
INSERT INTO `config` VALUES ('69', 'min_rand_traffic', 10);
INSERT INTO `config` VALUES ('70', 'max_rand_traffic', 20);
INSERT INTO `config` VALUES ('71', 'ads_daily_count', 10);
INSERT INTO `config` VALUES ('72', 'is_tcp_check', 0);
INSERT INTO `config` VALUES ('73', 'tcp_check_warning_times', 3);
INSERT INTO `config` VALUES ('74', 'is_forbid_china', 0);
INSERT INTO `config` VALUES ('75', 'is_forbid_oversea', 0);
INSERT INTO `config` VALUES ('76', 'is_open_shop', 1);
INSERT INTO `config` VALUES ('77', 'is_open_ticket', 1);

-- ----------------------------
-- Table structure for `article`
-- ----------------------------
CREATE TABLE `article` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(100) NOT NULL DEFAULT '' COMMENT '标题',
  `author` varchar(50) DEFAULT '' COMMENT '作者',
  `content` text COMMENT '内容',
  `type` tinyint(4) DEFAULT '1' COMMENT '类型：1-文章、2-公告',
  `is_del` tinyint(4) NOT NULL DEFAULT '0' COMMENT '是否删除',
  `sort` int(11) NOT NULL DEFAULT '0' COMMENT '排序',
  `created_at` datetime DEFAULT NULL COMMENT '创建时间',
  `updated_at` datetime DEFAULT NULL COMMENT '最后更新时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='文章';


-- ----------------------------
-- Table structure for `invite`
-- ----------------------------
CREATE TABLE `invite` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL DEFAULT '0' COMMENT '邀请人ID',
  `fuid` int(11) NOT NULL DEFAULT '0' COMMENT '受邀人ID',
  `code` char(32) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '邀请码',
  `status` tinyint(4) NOT NULL DEFAULT '0' COMMENT '邀请码状态：0-未使用、1-已使用、2-已过期',
  `dateline` datetime DEFAULT NULL COMMENT '有效期至',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='邀请码表';


-- ----------------------------
-- Table structure for `label`
-- ----------------------------
CREATE TABLE `label` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '名称',
  `sort` int(11) NOT NULL DEFAULT '0' COMMENT '排序值',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='标签';


-- ----------------------------
-- Records of label
-- ----------------------------
INSERT INTO `label` VALUES ('1', '电信', '0');
INSERT INTO `label` VALUES ('2', '联通', '0');
INSERT INTO `label` VALUES ('3', '移动', '0');
INSERT INTO `label` VALUES ('4', '教育网', '0');
INSERT INTO `label` VALUES ('5', '其他网络', '0');
INSERT INTO `label` VALUES ('6', '免费体验', '0');


-- ----------------------------
-- Table structure for `verify`
-- ----------------------------
CREATE TABLE `verify` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL COMMENT '用户ID',
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '用户名',
  `token` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '校验token',
  `status` tinyint(4) NOT NULL DEFAULT '0' COMMENT '状态：0-未使用、1-已使用、2-已失效',
  `created_at` datetime DEFAULT NULL COMMENT '创建时间',
  `updated_at` datetime DEFAULT NULL COMMENT '最后更新时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='邮件地址';


-- ----------------------------
-- Table structure for `ss_group`
-- ----------------------------
CREATE TABLE `ss_group` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '分组名称',
  `level` tinyint(4) NOT NULL DEFAULT '1' COMMENT '分组级别',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='节点分组';


-- ----------------------------
-- Table structure for `ss_group_node`
-- ----------------------------
CREATE TABLE `ss_group_node` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `group_id` int(11) NOT NULL DEFAULT '0' COMMENT '分组ID',
  `node_id` int(11) NOT NULL DEFAULT '0' COMMENT '节点ID',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='分组节点关系表';


-- ----------------------------
-- Table structure for `goods`
-- ----------------------------
CREATE TABLE `goods` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sku` varchar(15) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '商品服务SKU',
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '商品名称',
  `logo` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '商品图片地址',
  `traffic` bigint(20) NOT NULL DEFAULT '0' COMMENT '商品内含多少流量，单位Mib',
  `score` int(11) NOT NULL DEFAULT '0' COMMENT '商品价值多少积分',
  `type` tinyint(4) NOT NULL DEFAULT '1' COMMENT '商品类型：1-流量包、2-套餐、3-广告流量、4-余额充值',
  `price` int(11) NOT NULL DEFAULT '0' COMMENT '商品售价，单位分',
  `desc` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '商品描述',
  `bundle` VARCHAR(50) NOT NULL DEFAULT '' COMMENT 'BUNDLE',
  `days` int(11) NOT NULL DEFAULT '30' COMMENT '有效期',
  `color` VARCHAR(50) NOT NULL DEFAULT 'green' COMMENT '商品颜色',
  `sort` int(11) NOT NULL DEFAULT '0' COMMENT '排序',
  `is_hot` TINYINT(4) NOT NULL DEFAULT '0' COMMENT '是否热销：0-否、1-是',
  `is_del` tinyint(4) NOT NULL DEFAULT '0' COMMENT '是否已删除：0-否、1-是',
  `status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '状态：0-下架、1-上架',
  `created_at` datetime DEFAULT NULL COMMENT '创建时间',
  `updated_at` datetime DEFAULT NULL COMMENT '最后更新时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='商品';


-- ----------------------------
-- Table structure for `coupon`
-- ----------------------------
CREATE TABLE `coupon` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '优惠券名称',
  `logo` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '优惠券LOGO',
  `sn` char(8) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '优惠券码',
  `type` tinyint(4) NOT NULL DEFAULT '1' COMMENT '类型：1-现金券、2-折扣券、3-充值券',
  `usage` tinyint(4) NOT NULL DEFAULT '1' COMMENT '用途：1-仅限一次性使用、2-可重复使用',
  `amount` bigint(20) NOT NULL DEFAULT '0' COMMENT '金额，单位分',
  `discount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '折扣',
  `available_start` int(11) NOT NULL DEFAULT '0' COMMENT '有效期开始',
  `available_end` int(11) NOT NULL DEFAULT '0' COMMENT '有效期结束',
  `is_del` tinyint(4) NOT NULL DEFAULT '0' COMMENT '是否已删除：0-未删除、1-已删除',
  `status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '状态：0-未使用、1-已使用、2-已失效',
  `created_at` datetime DEFAULT NULL COMMENT '创建时间',
  `updated_at` datetime DEFAULT NULL COMMENT '最后更新时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='优惠券';


-- ----------------------------
-- Table structure for `coupon_log`
-- ----------------------------
CREATE TABLE `coupon_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `coupon_id` int(11) NOT NULL DEFAULT '0' COMMENT '优惠券ID',
  `goods_id` int(11) NOT NULL DEFAULT '0' COMMENT '商品ID',
  `order_id` int(11) NOT NULL DEFAULT '0' COMMENT '订单ID',
  `desc` varchar(50) NOT NULL DEFAULT '' COMMENT '备注',
  `created_at` datetime DEFAULT NULL COMMENT '创建时间',
  `updated_at` datetime DEFAULT NULL COMMENT '最后更新时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='优惠券使用日志';


-- ----------------------------
-- Table structure for `order`
-- ----------------------------
CREATE TABLE `order` (
  `oid` int(11) NOT NULL AUTO_INCREMENT,
  `order_sn` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '订单编号',
  `user_id` int(11) NOT NULL DEFAULT '0' COMMENT '操作人',
  `goods_id` int(11) NOT NULL DEFAULT '0' COMMENT '商品ID',
  `coupon_id` int(11) NOT NULL DEFAULT '0' COMMENT '优惠券ID',
  `email` varchar(255) DEFAULT NULL COMMENT '邮箱',
  `origin_amount` int(11) NOT NULL DEFAULT '0' COMMENT '订单原始总价，单位分',
  `amount` int(11) NOT NULL DEFAULT '0' COMMENT '订单总价，单位分',
  `expire_at` datetime DEFAULT NULL COMMENT '过期时间',
  `traffic` int(11) NOT NULL DEFAULT '0' COMMENT '流量值（单位：M）',
  `is_expire` tinyint(4) NOT NULL DEFAULT '0' COMMENT '是否已过期：0-未过期、1-已过期',
  `pay_way` tinyint(4) NOT NULL DEFAULT '1' COMMENT '支付方式：1-余额支付、2-有赞云支付、3-Apple支付、4-Google支付、5-广告领取',
  `status` tinyint(4) NOT NULL DEFAULT '0' COMMENT '订单状态：-1-已关闭、0-待支付、1-已支付待确认、2-已完成',
  `created_at` datetime DEFAULT NULL COMMENT '创建时间',
  `updated_at` datetime DEFAULT NULL COMMENT '最后一次更新时间',
  PRIMARY KEY (`oid`),
  INDEX `idx_order_search` (`user_id`, `goods_id`, `is_expire`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='订单';


-- ----------------------------
-- Table structure for `order_goods`
-- ----------------------------
CREATE TABLE `order_goods` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `oid` int(11) NOT NULL DEFAULT '0' COMMENT '订单ID',
  `order_sn` varchar(20) NOT NULL DEFAULT '' COMMENT '订单编号',
  `user_id` int(11) NOT NULL DEFAULT '0' COMMENT '用户ID',
  `goods_id` int(11) NOT NULL DEFAULT '0' COMMENT '商品ID',
  `num` int(11) NOT NULL DEFAULT '0' COMMENT '商品数量',
  `origin_price` int(11) NOT NULL DEFAULT '0' COMMENT '商品原价，单位分',
  `price` int(11) NOT NULL DEFAULT '0' COMMENT '商品实际价格，单位分',
  `is_expire` tinyint(4) NOT NULL DEFAULT '0' COMMENT '是否已过期：0-未过期、1-已过期',
  `created_at` datetime DEFAULT NULL COMMENT '创建时间',
  `updated_at` datetime DEFAULT NULL COMMENT '最后更新时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='订单商品';


-- ----------------------------
-- Table structure for `ticket`
-- ----------------------------
CREATE TABLE `ticket` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL DEFAULT '0',
  `title` varchar(255) NOT NULL DEFAULT '' COMMENT '标题',
  `content` text NOT NULL COMMENT '内容',
  `status` tinyint(4) NOT NULL DEFAULT '0' COMMENT '状态：0-待处理、1-已处理未关闭、2-已关闭',
  `created_at` datetime DEFAULT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='工单';


-- ----------------------------
-- Table structure for `ticket_reply`
-- ----------------------------
CREATE TABLE `ticket_reply` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `ticket_id` int(11) NOT NULL DEFAULT '0' COMMENT '工单ID',
  `user_id` int(11) NOT NULL COMMENT '回复人ID',
  `content` text NOT NULL COMMENT '回复内容',
  `created_at` datetime DEFAULT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='工单回复';


-- ----------------------------
-- Table structure for `user_score_log`
-- ----------------------------
CREATE TABLE `user_score_log` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL DEFAULT '0' COMMENT '账号ID',
  `before` int(11) NOT NULL DEFAULT '0' COMMENT '发生前积分',
  `after` int(11) NOT NULL DEFAULT '0' COMMENT '发生后积分',
  `score` int(11) NOT NULL DEFAULT '0' COMMENT '发生积分',
  `desc` varchar(50) DEFAULT '' COMMENT '描述',
  `created_at` datetime DEFAULT NULL COMMENT '创建日期',
  PRIMARY KEY (`id`),
  INDEX `idx` (`user_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户积分变动日志';


-- ----------------------------
-- Table structure for `user_balance_log`
-- ----------------------------
CREATE TABLE `user_balance_log` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL DEFAULT '0' COMMENT '账号ID',
  `order_id` int(11) NOT NULL DEFAULT '0' COMMENT '订单ID',
  `before` int(11) NOT NULL DEFAULT '0' COMMENT '发生前余额，单位分',
  `after` int(11) NOT NULL DEFAULT '0' COMMENT '发生后金额，单位分',
  `amount` int(11) NOT NULL DEFAULT '0' COMMENT '发生金额，单位分',
  `desc` varchar(255) DEFAULT '' COMMENT '操作描述',
  `created_at` datetime DEFAULT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户余额变动日志';


-- ----------------------------
-- Table structure for `user_traffic_modify_log`
-- ----------------------------
CREATE TABLE `user_traffic_modify_log` (
	`id` INT(11) NOT NULL AUTO_INCREMENT,
	`user_id` INT(11) NOT NULL DEFAULT '0' COMMENT '用户ID',
	`order_id` INT(11) NOT NULL DEFAULT '0' COMMENT '发生的订单ID',
	`before` INT(11) NOT NULL DEFAULT '0',
	`after` INT(11) NOT NULL DEFAULT '0',
	`desc` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '描述',
	`created_at` DATETIME NOT NULL,
	`updated_at` DATETIME NOT NULL,
	PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户流量变动日志';


-- ----------------------------
-- Table structure for `referral_apply`
-- ----------------------------
CREATE TABLE `referral_apply` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL DEFAULT '0' COMMENT '用户ID',
  `before` int(11) NOT NULL DEFAULT '0' COMMENT '操作前可提现金额，单位分',
  `after` int(11) NOT NULL DEFAULT '0' COMMENT '操作后可提现金额，单位分',
  `amount` int(11) NOT NULL DEFAULT '0' COMMENT '本次提现金额，单位分',
  `link_logs` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '关联返利日志ID，例如：1,3,4',
  `status` tinyint(4) NOT NULL DEFAULT '0' COMMENT '状态：-1-驳回、0-待审核、1-审核通过待打款、2-已打款',
  `created_at` datetime DEFAULT NULL COMMENT '创建时间',
  `updated_at` datetime DEFAULT NULL COMMENT '最后更新时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='提现申请';


-- ----------------------------
-- Table structure for `referral_log`
-- ----------------------------
CREATE TABLE `referral_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL DEFAULT '0' COMMENT '用户ID',
  `ref_user_id` int(11) NOT NULL DEFAULT '0' COMMENT '推广人ID',
  `order_id` int(11) NOT NULL DEFAULT '0' COMMENT '关联订单ID',
  `amount` int(11) NOT NULL DEFAULT '0' COMMENT '消费金额，单位分',
  `ref_amount` int(11) NOT NULL DEFAULT '0' COMMENT '返利金额',
  `status` tinyint(4) NOT NULL DEFAULT '0' COMMENT '状态：0-未提现、1-审核中、2-已提现',
  `created_at` datetime DEFAULT NULL COMMENT '创建时间',
  `updated_at` datetime DEFAULT NULL COMMENT '最后更新时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='消费返利日志';


-- ----------------------------
-- Table structure for `email_log`
-- ----------------------------
CREATE TABLE `email_log` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL DEFAULT '0' COMMENT '接收者ID',
  `title` varchar(255) DEFAULT '' COMMENT '邮件标题',
  `content` text COMMENT '邮件内容',
  `status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '状态：1-发送成功、2-发送失败',
  `error` text COMMENT '发送失败抛出的异常信息',
  `created_at` datetime DEFAULT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='邮件投递记录';


-- ----------------------------
-- Table structure for `sensitive_words`
-- ----------------------------
CREATE TABLE `sensitive_words` (
	`id` INT(11) NOT NULL AUTO_INCREMENT,
	`words` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '敏感词',
	PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='敏感词';


-- ----------------------------
-- Records of label
-- ----------------------------
INSERT INTO `sensitive_words` (`words`) VALUES ('chacuo.com');
INSERT INTO `sensitive_words` (`words`) VALUES ('chacuo.net');
INSERT INTO `sensitive_words` (`words`) VALUES ('1766258.com');
INSERT INTO `sensitive_words` (`words`) VALUES ('3202.com');
INSERT INTO `sensitive_words` (`words`) VALUES ('4057.com');
INSERT INTO `sensitive_words` (`words`) VALUES ('4059.com');
INSERT INTO `sensitive_words` (`words`) VALUES ('a7996.com');
INSERT INTO `sensitive_words` (`words`) VALUES ('bccto.me');
INSERT INTO `sensitive_words` (`words`) VALUES ('bnuis.com');
INSERT INTO `sensitive_words` (`words`) VALUES ('chaichuang.com');
INSERT INTO `sensitive_words` (`words`) VALUES ('cr219.com');
INSERT INTO `sensitive_words` (`words`) VALUES ('cuirushi.org');
INSERT INTO `sensitive_words` (`words`) VALUES ('dawin.com');
INSERT INTO `sensitive_words` (`words`) VALUES ('jiaxin8736.com');
INSERT INTO `sensitive_words` (`words`) VALUES ('lakqs.com');
INSERT INTO `sensitive_words` (`words`) VALUES ('urltc.com');
INSERT INTO `sensitive_words` (`words`) VALUES ('027168.com');
INSERT INTO `sensitive_words` (`words`) VALUES ('10minutemail.net');
INSERT INTO `sensitive_words` (`words`) VALUES ('11163.com');
INSERT INTO `sensitive_words` (`words`) VALUES ('1shivom.com');
INSERT INTO `sensitive_words` (`words`) VALUES ('auoie.com');
INSERT INTO `sensitive_words` (`words`) VALUES ('bareed.ws');
INSERT INTO `sensitive_words` (`words`) VALUES ('bit-degree.com');
INSERT INTO `sensitive_words` (`words`) VALUES ('cjpeg.com');
INSERT INTO `sensitive_words` (`words`) VALUES ('cool.fr.nf');
INSERT INTO `sensitive_words` (`words`) VALUES ('courriel.fr.nf');
INSERT INTO `sensitive_words` (`words`) VALUES ('disbox.net');
INSERT INTO `sensitive_words` (`words`) VALUES ('disbox.org');
INSERT INTO `sensitive_words` (`words`) VALUES ('fidelium10.com');
INSERT INTO `sensitive_words` (`words`) VALUES ('get365.pw');
INSERT INTO `sensitive_words` (`words`) VALUES ('ggr.la');
INSERT INTO `sensitive_words` (`words`) VALUES ('grr.la');
INSERT INTO `sensitive_words` (`words`) VALUES ('guerrillamail.biz');
INSERT INTO `sensitive_words` (`words`) VALUES ('guerrillamail.com');
INSERT INTO `sensitive_words` (`words`) VALUES ('guerrillamail.de');
INSERT INTO `sensitive_words` (`words`) VALUES ('guerrillamail.net');
INSERT INTO `sensitive_words` (`words`) VALUES ('guerrillamail.org');
INSERT INTO `sensitive_words` (`words`) VALUES ('guerrillamailblock.com');
INSERT INTO `sensitive_words` (`words`) VALUES ('hubii-network.com');
INSERT INTO `sensitive_words` (`words`) VALUES ('hurify1.com');
INSERT INTO `sensitive_words` (`words`) VALUES ('itoup.com');
INSERT INTO `sensitive_words` (`words`) VALUES ('jetable.fr.nf');
INSERT INTO `sensitive_words` (`words`) VALUES ('jnpayy.com');
INSERT INTO `sensitive_words` (`words`) VALUES ('juyouxi.com');
INSERT INTO `sensitive_words` (`words`) VALUES ('mail.bccto.me');
INSERT INTO `sensitive_words` (`words`) VALUES ('www.bccto.me');
INSERT INTO `sensitive_words` (`words`) VALUES ('mega.zik.dj');
INSERT INTO `sensitive_words` (`words`) VALUES ('moakt.co');
INSERT INTO `sensitive_words` (`words`) VALUES ('moakt.ws');
INSERT INTO `sensitive_words` (`words`) VALUES ('molms.com');
INSERT INTO `sensitive_words` (`words`) VALUES ('moncourrier.fr.nf');
INSERT INTO `sensitive_words` (`words`) VALUES ('monemail.fr.nf');
INSERT INTO `sensitive_words` (`words`) VALUES ('monmail.fr.nf');
INSERT INTO `sensitive_words` (`words`) VALUES ('nomail.xl.cx');
INSERT INTO `sensitive_words` (`words`) VALUES ('nospam.ze.tc');
INSERT INTO `sensitive_words` (`words`) VALUES ('pay-mon.com');
INSERT INTO `sensitive_words` (`words`) VALUES ('poly-swarm.com');
INSERT INTO `sensitive_words` (`words`) VALUES ('sgmh.online');
INSERT INTO `sensitive_words` (`words`) VALUES ('sharklasers.com');
INSERT INTO `sensitive_words` (`words`) VALUES ('shiftrpg.com');
INSERT INTO `sensitive_words` (`words`) VALUES ('spam4.me');
INSERT INTO `sensitive_words` (`words`) VALUES ('speed.1s.fr');
INSERT INTO `sensitive_words` (`words`) VALUES ('tmail.ws');
INSERT INTO `sensitive_words` (`words`) VALUES ('tmails.net');
INSERT INTO `sensitive_words` (`words`) VALUES ('tmpmail.net');
INSERT INTO `sensitive_words` (`words`) VALUES ('tmpmail.org');
INSERT INTO `sensitive_words` (`words`) VALUES ('travala10.com');
INSERT INTO `sensitive_words` (`words`) VALUES ('yopmail.com');
INSERT INTO `sensitive_words` (`words`) VALUES ('yopmail.fr');
INSERT INTO `sensitive_words` (`words`) VALUES ('yopmail.net');
INSERT INTO `sensitive_words` (`words`) VALUES ('yuoia.com');
INSERT INTO `sensitive_words` (`words`) VALUES ('zep-hyr.com');
INSERT INTO `sensitive_words` (`words`) VALUES ('zippiex.com');
INSERT INTO `sensitive_words` (`words`) VALUES ('lrc8.com');
INSERT INTO `sensitive_words` (`words`) VALUES ('1otc.com');
INSERT INTO `sensitive_words` (`words`) VALUES ('emailna.co');
INSERT INTO `sensitive_words` (`words`) VALUES ('mailinator.com');
INSERT INTO `sensitive_words` (`words`) VALUES ('nbzmr.com');
INSERT INTO `sensitive_words` (`words`) VALUES ('awsoo.com');
INSERT INTO `sensitive_words` (`words`) VALUES ('zhcne.com');
INSERT INTO `sensitive_words` (`words`) VALUES ('0box.eu');
INSERT INTO `sensitive_words` (`words`) VALUES ('contbay.com');
INSERT INTO `sensitive_words` (`words`) VALUES ('damnthespam.com');
INSERT INTO `sensitive_words` (`words`) VALUES ('kurzepost.de');
INSERT INTO `sensitive_words` (`words`) VALUES ('objectmail.com');
INSERT INTO `sensitive_words` (`words`) VALUES ('proxymail.eu');
INSERT INTO `sensitive_words` (`words`) VALUES ('rcpt.at');
INSERT INTO `sensitive_words` (`words`) VALUES ('trash-mail.at');
INSERT INTO `sensitive_words` (`words`) VALUES ('trashmail.at');
INSERT INTO `sensitive_words` (`words`) VALUES ('trashmail.com');
INSERT INTO `sensitive_words` (`words`) VALUES ('trashmail.io');
INSERT INTO `sensitive_words` (`words`) VALUES ('trashmail.me');
INSERT INTO `sensitive_words` (`words`) VALUES ('trashmail.net');
INSERT INTO `sensitive_words` (`words`) VALUES ('wegwerfmail.de');
INSERT INTO `sensitive_words` (`words`) VALUES ('wegwerfmail.net');
INSERT INTO `sensitive_words` (`words`) VALUES ('wegwerfmail.org');
INSERT INTO `sensitive_words` (`words`) VALUES ('nwytg.net');
INSERT INTO `sensitive_words` (`words`) VALUES ('despam.it');
INSERT INTO `sensitive_words` (`words`) VALUES ('spambox.us');
INSERT INTO `sensitive_words` (`words`) VALUES ('spam.la');
INSERT INTO `sensitive_words` (`words`) VALUES ('mytrashmail.com');
INSERT INTO `sensitive_words` (`words`) VALUES ('mt2014.com');
INSERT INTO `sensitive_words` (`words`) VALUES ('mt2015.com');
INSERT INTO `sensitive_words` (`words`) VALUES ('thankyou2010.com');
INSERT INTO `sensitive_words` (`words`) VALUES ('trash2009.com');
INSERT INTO `sensitive_words` (`words`) VALUES ('mt2009.com');
INSERT INTO `sensitive_words` (`words`) VALUES ('trashymail.com');
INSERT INTO `sensitive_words` (`words`) VALUES ('tempemail.net');
INSERT INTO `sensitive_words` (`words`) VALUES ('slopsbox.com');
INSERT INTO `sensitive_words` (`words`) VALUES ('mailnesia.com');


-- ----------------------------
-- Table structure for `user_subscribe`
-- ----------------------------
CREATE TABLE `user_subscribe` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL DEFAULT '0' COMMENT '用户ID',
  `code` char(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT '' COMMENT '订阅地址唯一识别码',
  `times` int(11) NOT NULL DEFAULT '0' COMMENT '地址请求次数',
  `status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '状态：0-禁用、1-启用',
  `ban_time` int(11) NOT NULL DEFAULT '0' COMMENT '封禁时间',
  `ban_desc` varchar(50) NOT NULL DEFAULT '' COMMENT '封禁理由',
  `created_at` datetime DEFAULT NULL COMMENT '创建时间',
  `updated_at` datetime DEFAULT NULL COMMENT '最后更新时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户订阅';


-- ----------------------------
-- Table structure for `user_subscribe_log`
-- ----------------------------
CREATE TABLE `user_subscribe_log` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `sid` int(11) DEFAULT NULL COMMENT '对应user_subscribe的id',
  `request_ip` varchar(20) DEFAULT NULL COMMENT '请求IP',
  `request_time` datetime DEFAULT NULL COMMENT '请求时间',
  `request_header` text COMMENT '请求头部信息',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户订阅访问日志';


-- ----------------------------
-- Table structure for `user_traffic_daily`
-- ----------------------------
CREATE TABLE `user_traffic_daily` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL DEFAULT '0' COMMENT '用户ID',
  `node_id` int(11) NOT NULL DEFAULT '0' COMMENT '节点ID，0表示统计全部节点',
  `u` bigint(20) NOT NULL DEFAULT '0' COMMENT '上传流量',
  `d` bigint(20) NOT NULL DEFAULT '0' COMMENT '下载流量',
  `total` bigint(20) NOT NULL DEFAULT '0' COMMENT '总流量',
  `traffic` varchar(255) DEFAULT '' COMMENT '总流量（带单位）',
  `created_at` datetime DEFAULT NULL COMMENT '创建时间',
  `updated_at` datetime DEFAULT NULL COMMENT '最后更新时间',
  PRIMARY KEY (`id`),
  INDEX `idx_user` (`user_id`) USING BTREE,
  INDEX `idx_user_node` (`user_id`,`node_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户每日流量统计';


-- ----------------------------
-- Table structure for `user_traffic_hourly`
-- ----------------------------
CREATE TABLE `user_traffic_hourly` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL DEFAULT '0' COMMENT '用户ID',
  `node_id` int(11) NOT NULL DEFAULT '0' COMMENT '节点ID，0表示统计全部节点',
  `u` bigint(20) NOT NULL DEFAULT '0' COMMENT '上传流量',
  `d` bigint(20) NOT NULL DEFAULT '0' COMMENT '下载流量',
  `total` bigint(20) NOT NULL DEFAULT '0' COMMENT '总流量',
  `traffic` varchar(255) DEFAULT '' COMMENT '总流量（带单位）',
  `created_at` datetime DEFAULT NULL COMMENT '创建时间',
  `updated_at` datetime DEFAULT NULL COMMENT '最后更新时间',
  PRIMARY KEY (`id`),
  INDEX `idx_user` (`user_id`) USING BTREE,
  INDEX `idx_user_node` (`user_id`,`node_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户每小时流量统计';


-- ----------------------------
-- Table structure for `node_traffic_daily`
-- ----------------------------
CREATE TABLE `ss_node_traffic_daily` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `node_id` int(11) NOT NULL DEFAULT '0' COMMENT '节点ID',
  `u` bigint(20) NOT NULL DEFAULT '0' COMMENT '上传流量',
  `d` bigint(20) NOT NULL DEFAULT '0' COMMENT '下载流量',
  `total` bigint(20) NOT NULL DEFAULT '0' COMMENT '总流量',
  `traffic` varchar(255) DEFAULT '' COMMENT '总流量（带单位）',
  `created_at` datetime DEFAULT NULL COMMENT '创建时间',
  `updated_at` datetime DEFAULT NULL COMMENT '最后更新时间',
  PRIMARY KEY (`id`),
  INDEX `idx_node_id` (`node_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='节点每日流量统计';


-- ----------------------------
-- Table structure for `node_traffic_hourly`
-- ----------------------------
CREATE TABLE `ss_node_traffic_hourly` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `node_id` int(11) NOT NULL DEFAULT '0' COMMENT '节点ID',
  `u` bigint(20) NOT NULL DEFAULT '0' COMMENT '上传流量',
  `d` bigint(20) NOT NULL DEFAULT '0' COMMENT '下载流量',
  `total` bigint(20) NOT NULL DEFAULT '0' COMMENT '总流量',
  `traffic` varchar(255) DEFAULT '' COMMENT '总流量（带单位）',
  `created_at` datetime DEFAULT NULL COMMENT '创建时间',
  `updated_at` datetime DEFAULT NULL COMMENT '最后更新时间',
  PRIMARY KEY (`id`),
  INDEX `idx_node_id` (`node_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='节点每小时流量统计';


-- ----------------------------
-- Table structure for `user_ban_log`
-- ----------------------------
CREATE TABLE `user_ban_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL DEFAULT '0' COMMENT '用户ID',
  `minutes` int(11) NOT NULL DEFAULT '0' COMMENT '封禁账号时长，单位分钟',
  `desc` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '操作描述',
  `status` tinyint(4) NOT NULL DEFAULT '0' COMMENT '状态：0-未处理、1-已处理',
  `created_at` datetime DEFAULT NULL COMMENT ' 创建时间',
  `updated_at` datetime DEFAULT NULL COMMENT '最后更新时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户封禁日志';


-- ----------------------------
-- Table structure for `user_label`
-- ----------------------------
CREATE TABLE `user_label` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL DEFAULT '0' COMMENT '用户ID',
  `label_id` int(11) NOT NULL DEFAULT '0' COMMENT '标签ID',
  PRIMARY KEY (`id`),
  INDEX `idx` (`user_id`,`label_id`),
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_label_id` (`label_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户标签';


-- ----------------------------
-- Table structure for `goods_label`
-- ----------------------------
CREATE TABLE `goods_label` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `goods_id` INT(11) NOT NULL DEFAULT '0' COMMENT '商品ID',
  `label_id` INT(11) NOT NULL DEFAULT '0' COMMENT '标签ID',
  PRIMARY KEY (`id`),
  INDEX `idx` (`goods_id`, `label_id`),
  INDEX `idx_goods_id` (`goods_id`),
  INDEX `idx_label_id` (`label_id`)
) ENGINE=InnoDB CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='商品标签';


-- ----------------------------
-- Table structure for `country`
-- ----------------------------
CREATE TABLE `country` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `country_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '名称',
  `country_code` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '代码',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='国家代码';


-- ----------------------------
-- Records of `country`
-- ----------------------------
INSERT INTO `country` VALUES ('1', '澳大利亚', 'au');
INSERT INTO `country` VALUES ('2', '巴西', 'br');
INSERT INTO `country` VALUES ('3', '加拿大', 'ca');
INSERT INTO `country` VALUES ('4', '瑞士', 'ch');
INSERT INTO `country` VALUES ('5', '中国', 'cn');
INSERT INTO `country` VALUES ('6', '德国', 'de');
INSERT INTO `country` VALUES ('7', '丹麦', 'dk');
INSERT INTO `country` VALUES ('8', '埃及', 'eg');
INSERT INTO `country` VALUES ('9', '法国', 'fr');
INSERT INTO `country` VALUES ('10', '希腊', 'gr');
INSERT INTO `country` VALUES ('11', '香港', 'hk');
INSERT INTO `country` VALUES ('12', '印度尼西亚', 'id');
INSERT INTO `country` VALUES ('13', '爱尔兰', 'ie');
INSERT INTO `country` VALUES ('14', '以色列', 'il');
INSERT INTO `country` VALUES ('15', '印度', 'in');
INSERT INTO `country` VALUES ('16', '伊拉克', 'iq');
INSERT INTO `country` VALUES ('17', '伊朗', 'ir');
INSERT INTO `country` VALUES ('18', '意大利', 'it');
INSERT INTO `country` VALUES ('19', '日本', 'jp');
INSERT INTO `country` VALUES ('20', '韩国', 'kr');
INSERT INTO `country` VALUES ('21', '墨西哥', 'mx');
INSERT INTO `country` VALUES ('22', '马来西亚', 'my');
INSERT INTO `country` VALUES ('23', '荷兰', 'nl');
INSERT INTO `country` VALUES ('24', '挪威', 'no');
INSERT INTO `country` VALUES ('25', '纽西兰', 'nz');
INSERT INTO `country` VALUES ('26', '菲律宾', 'ph');
INSERT INTO `country` VALUES ('27', '俄罗斯', 'ru');
INSERT INTO `country` VALUES ('28', '瑞典', 'se');
INSERT INTO `country` VALUES ('29', '新加坡', 'sg');
INSERT INTO `country` VALUES ('30', '泰国', 'th');
INSERT INTO `country` VALUES ('31', '土耳其', 'tr');
INSERT INTO `country` VALUES ('32', '台湾', 'tw');
INSERT INTO `country` VALUES ('33', '英国', 'uk');
INSERT INTO `country` VALUES ('34', '美国', 'us');
INSERT INTO `country` VALUES ('35', '越南', 'vn');
INSERT INTO `country` VALUES ('36', '波兰', 'pl');
INSERT INTO `country` VALUES ('37', '哈萨克斯坦', 'kz');
INSERT INTO `country` VALUES ('38', '乌克兰', 'ua');
INSERT INTO `country` VALUES ('39', '罗马尼亚', 'ro');
INSERT INTO `country` VALUES ('40', '阿联酋', 'ae');
INSERT INTO `country` VALUES ('41', '南非', 'za');
INSERT INTO `country` VALUES ('42', '缅甸', 'mm');
INSERT INTO `country` VALUES ('43', '冰岛', 'is');
INSERT INTO `country` VALUES ('44', '芬兰', 'fi');
INSERT INTO `country` VALUES ('45', '卢森堡', 'lu');
INSERT INTO `country` VALUES ('46', '比利时', 'be');
INSERT INTO `country` VALUES ('47', '保加利亚', 'bg');
INSERT INTO `country` VALUES ('48', '立陶宛', 'lt');
INSERT INTO `country` VALUES ('49', '哥伦比亚', 'co');
INSERT INTO `country` VALUES ('50', '澳门', 'mo');
INSERT INTO `country` VALUES ('51', '肯尼亚', 'ke');
INSERT INTO `country` VALUES ('52', '捷克', 'cz');
INSERT INTO `country` VALUES ('53', '摩尔多瓦', 'md');
INSERT INTO `country` VALUES ('54', '西班牙', 'es');
INSERT INTO `country` VALUES ('55', '巴基斯坦', 'pk');
INSERT INTO `country` VALUES ('56', '葡萄牙', 'pt');
INSERT INTO `country` VALUES ('57', '匈牙利', 'hu');
INSERT INTO `country` VALUES ('58', '阿根廷', 'ar');


-- ----------------------------
-- Table structure for `payment`
-- ----------------------------
CREATE TABLE `payment` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sn` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `user_id` int(11) NOT NULL COMMENT '用户ID',
  `oid` int(11) DEFAULT NULL COMMENT '本地订单ID',
  `order_sn` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '本地订单长ID',
  `pay_way` TINYINT(4) NOT NULL DEFAULT '1' COMMENT '支付方式：1-微信、2-支付宝',
  `amount` int(11) NOT NULL DEFAULT '0' COMMENT '金额，单位分',
  `qr_id` int(11) NOT NULL DEFAULT '0' COMMENT '有赞生成的支付单ID',
  `qr_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '有赞生成的支付二维码URL',
  `qr_code` text COLLATE utf8mb4_unicode_ci COMMENT '有赞生成的支付二维码图片base64',
  `qr_local_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '支付二维码的本地存储URL',
  `status` int(11) NOT NULL DEFAULT '0' COMMENT '状态：-1-支付失败、0-等待支付、1-支付成功',
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='支付单';


-- ----------------------------
-- Table structure for `payment_callback`
-- ----------------------------
CREATE TABLE `payment_callback` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `client_id` varchar(50) DEFAULT NULL,
  `yz_id` varchar(50) DEFAULT NULL,
  `kdt_id` varchar(50) DEFAULT NULL,
  `kdt_name` varchar(50) DEFAULT NULL,
  `mode` tinyint(4) DEFAULT NULL,
  `msg` text,
  `sendCount` int(11) DEFAULT NULL,
  `sign` varchar(32) DEFAULT NULL,
  `status` varchar(30) DEFAULT NULL,
  `test` tinyint(4) DEFAULT NULL,
  `type` varchar(50) DEFAULT NULL,
  `version` varchar(50) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='有赞云回调日志';


-- ----------------------------
-- Table structure for `marketing`
-- ----------------------------
CREATE TABLE `marketing` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `type` TINYINT(4) NOT NULL COMMENT '类型：1-邮件群发、2-订阅渠道群发',
  `receiver` TEXT NOT NULL COMMENT '接收者' COLLATE 'utf8mb4_unicode_ci',
  `title` VARCHAR(255) NOT NULL COMMENT '标题' COLLATE 'utf8mb4_unicode_ci',
  `content` TEXT NOT NULL COMMENT '内容' COLLATE 'utf8mb4_unicode_ci',
  `error` VARCHAR(255) NULL COMMENT '错误信息' COLLATE 'utf8mb4_unicode_ci',
  `status` TINYINT(4) NOT NULL COMMENT '状态：-1-失败、0-待发送、1-成功',
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='营销';


-- ----------------------------
-- Table structure for `user_login_log`
-- ----------------------------
CREATE TABLE `user_login_log` (
	`id` INT(11) NOT NULL AUTO_INCREMENT,
	`user_id` INT(11) NOT NULL DEFAULT '0',
	`ip` CHAR(20) NOT NULL,
	`country` CHAR(20) NOT NULL,
	`province` CHAR(20) NOT NULL,
	`city` CHAR(20) NOT NULL,
	`county` CHAR(20) NOT NULL,
	`isp` CHAR(20) NOT NULL,
	`area` CHAR(20) NOT NULL,
	`created_at` DATETIME NOT NULL,
	`updated_at` DATETIME NOT NULL,
	PRIMARY KEY (`id`)
) ENGINE=InnoDB COLLATE='utf8mb4_general_ci' COMMENT='用户登录日志';


/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
