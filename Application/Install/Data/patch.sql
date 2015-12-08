ALTER TABLE `ocenter_rank_user`
ADD `expire_time` int(11) NOT NULL DEFAULT '0' COMMENT '过期时间' AFTER `create_time`;

