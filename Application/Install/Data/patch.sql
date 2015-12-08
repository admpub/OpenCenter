ALTER TABLE `ocenter_rank_user`
ADD `expire_time` int(11) NOT NULL DEFAULT '0' COMMENT '过期时间' AFTER `create_time`;

DROP TABLE IF EXISTS `ocenter_rank_price`;
CREATE TABLE `ocenter_rank_price` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `rank_id` int(10) unsigned NOT NULL COMMENT '头衔ID',
  `price` decimal(18,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '价格',
  `time_num` smallint(5) unsigned NOT NULL COMMENT '时间数',
  `time_unit` enum('hour','day','week','month','year') NOT NULL DEFAULT 'year' COMMENT '时间单位',
  `price_extra` varchar(255) NOT NULL DEFAULT '' COMMENT '每次购买价格阶梯',
  `memo` varchar(255) NOT NULL DEFAULT '' COMMENT '备注',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='头衔价格';

