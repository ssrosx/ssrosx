-- ios token
ALTER TABLE `user`
ADD COLUMN `remember_token_ios` varchar(255) NOT NULL DEFAULT '' COMMENT 'iOS_Token' AFTER `remember_token`;

-- mac token
ALTER TABLE `user`
ADD COLUMN `remember_token_mac` varchar(255) NOT NULL DEFAULT '' COMMENT 'Mac_Token' AFTER `remember_token_ios`;

-- android token
ALTER TABLE `user`
ADD COLUMN `remember_token_android` varchar(255) NOT NULL DEFAULT '' COMMENT 'Android_Token' AFTER `remember_token_mac`;

-- win token
ALTER TABLE `user`
ADD COLUMN `remember_token_win` varchar(255) NOT NULL DEFAULT '' COMMENT 'Win_Token' AFTER `remember_token_android`;