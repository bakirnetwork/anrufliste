<?php

define(
	'SQL_CALLS',
	'CREATE TABLE `' . DB_PREFIX . DB_CALLS . '` (
	  `id` smallint(10) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
	  `create_datetime` datetime NOT NULL,
	  `create_person` int(11) NOT NULL,
	  `call_date` datetime NOT NULL,
	  `call_subject` varchar(100) NOT NULL,
	  `call_notes` longtext,
	  `contact_forname` varchar(100) NOT NULL,
	  `contact_lastname` varchar(100) NOT NULL,
	  `contact_phone` varchar(100) NOT NULL,
	  `done_date` datetime DEFAULT NULL,
	  `done_person` int(11) DEFAULT NULL
	) ENGINE=MyISAM DEFAULT CHARSET=utf8;'
);
