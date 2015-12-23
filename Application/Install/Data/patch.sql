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

ALTER TABLE `ocenter_rank_price`
ADD UNIQUE `rank_id_time_num_time_unit` (`rank_id`, `time_num`, `time_unit`);

INSERT INTO `ocenter_menu` (`id`, `title`, `pid`, `sort`, `url`, `hide`, `tip`, `group`, `is_dev`, `icon`, `module`) VALUES
(NULL, '表情设置', 74, 4, 'Expression/index', 0, '', '表情设置', 0, '', ''),
(NULL, '添加表情包', 74, 5, 'Expression/add', 1, '', '表情设置', 0, '', ''),
(NULL, '表情包列表', 74, 6, 'Expression/package', 0, '', '表情设置', 0, '', ''),
(NULL, '表情列表', 74, 7, 'Expression/expressionList', 1, '', '表情设置', 0, '', ''),
(NULL, '删除表情包', 74, 8, 'Expression/delPackage', 1, '', '表情设置', 0, '', ''),
(NULL, '编辑表情包', 74, 9, 'Expression/editPackage', 1, '', '表情设置', 0, '', ''),
(NULL, '删除表情', 74, 10, 'Expression/delExpression', 1, '', '表情设置', 0, '', ''),
(NULL, '上传表情包', 74, 11, 'Expression/upload', 1, '', '表情设置', 0, '', '');

ALTER TABLE `ocenter_rank`
ADD `label_content` varchar(50) NOT NULL DEFAULT '' COMMENT '标签内容' AFTER `logo`,
ADD `label_bg` varchar(50) NOT NULL DEFAULT '' COMMENT '标签背景色' AFTER `label_content`,
ADD `label_color` char(7) NOT NULL DEFAULT '' COMMENT '标签前景色' AFTER `label_bg`;
