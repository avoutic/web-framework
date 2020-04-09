CREATE TABLE `rights` (
  `id` int(11) NOT NULL auto_increment,
  `short_name` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO rights set short_name='admin', name='Administrator';
INSERT INTO rights set short_name='user_management', name='User Management';
INSERT INTO rights set short_name='grab_identity', name='Grab Identity';
INSERT INTO rights set short_name='debug', name='Debug information';

CREATE TABLE `users` (
  `id` int(11) NOT NULL auto_increment,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL DEFAULT '',
  `solid_password` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL DEFAULT '',
  `email` varchar(255) NOT NULL,
  `registered` INT(11) NOT NULL,
  `verified` tinyint(1) NOT NULL,
  `failed_login` INT(11) NOT NULL DEFAULT '0',
  `terms_accepted` INT(11) NOT NULL DEFAULT '0',
  `last_login` INT(11) NOT NULL DEFAULT '0',
  PRIMARY KEY  (`id`),
  CONSTRAINT `username_unique` UNIQUE(`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `user_rights` (
  `id` int(11) NOT NULL auto_increment,
  `user_id` int(11) NOT NULL,
  `right_id` int(11) NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `user_id` (`user_id`),
  KEY `right_id` (`right_id`),
  CONSTRAINT `user_rights_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `user_rights_ibfk_2` FOREIGN KEY (`right_id`) REFERENCES `rights` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `config_values` (
  `id` int(11) NOT NULL auto_increment,
  `module` VARCHAR(45) NOT NULL,
  `name` VARCHAR(45) NOT NULL,
  `value` VARCHAR(45) NOT NULL,
  PRIMARY KEY  (`id`),
  CONSTRAINT `config_values_unique` UNIQUE (`module`, `name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO config_values set module='db', name='version', value='1';

CREATE TABLE `user_config_values` (
  `id` int(11) NOT NULL auto_increment,
  `user_id` int(11) NOT NULL,
  `module` VARCHAR(45) NOT NULL,
  `name` VARCHAR(45) NOT NULL,
  `value` VARCHAR(45) NOT NULL,
  PRIMARY KEY  (`id`),
  CONSTRAINT `user_config_values_unique` UNIQUE (`user_id`, `module`, `name`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `user_config_values_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `ip_list` (
  `ip` int unsigned not null,
  `hits` int unsigned not null,
  `last_hit` timestamp,
  PRIMARY KEY (`ip`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE sessions (
    id INT NOT NULL AUTO_INCREMENT,
    user_id INT NOT NULL,
    session_id VARCHAR(255) NOT NULL,
    `start` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_active DATETIME NOT NULL,
    PRIMARY KEY(id),
    CONSTRAINT `sessions_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
