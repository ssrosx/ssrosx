INSERT INTO `config` VALUES ('67', 'ads_add_traffic', 1);
INSERT INTO `config` VALUES ('68', 'ads_add_traffic_range', 5);
INSERT INTO `config` VALUES ('69', 'min_rand_traffic', 10);
INSERT INTO `config` VALUES ('70', 'max_rand_traffic', 20);

CREATE TABLE `ss_ads_traffic` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL COMMENT '用户ID',
  `ads_sn` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '广告订单ID',
  `traffic` int(11) NOT NULL DEFAULT '0' COMMENT '流量值（单位：M）',
  `created_at` datetime DEFAULT NULL COMMENT '创建时间',
  `updated_at` datetime DEFAULT NULL COMMENT '最后一起更新时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
