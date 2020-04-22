CREATE TABLE `novaseo_meta` (
  `objectattribute_id` bigint(20) unsigned NOT NULL,
  `meta_name` varchar(255) NOT NULL,
  `meta_content` text NOT NULL,
  `objectattribute_version` int(10) unsigned NOT NULL,
  PRIMARY KEY (`objectattribute_id`,`objectattribute_version`,`meta_name`),
  KEY `novaseo_idx_content` (`objectattribute_id`,`objectattribute_version`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `novaseo_redirect_import_history` (
	`id` INT AUTO_INCREMENT NOT NULL,
	`name_file` VARCHAR (255) NOT NULL,
	`date` DATETIME NOT NULL,
	`path` VARCHAR (255) NOT NULL,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
