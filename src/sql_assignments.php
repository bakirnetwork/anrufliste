<?php

define( 'SQL_ASSIGNMENTS', '
CREATE TABLE `'.DB_PREFIX.DB_ASSIGNMENTS.'` (
  `id` smallint(10) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `call_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;');

?>
