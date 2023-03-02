DROP DATABASE IF EXISTS `prado_unitest`;
CREATE DATABASE `prado_unitest`;
CREATE USER 'prado_unitest'@'localhost' identified by 'prado_unitest';
GRANT ALL ON `prado_unitest`.* TO 'prado_unitest'@'localhost';
FLUSH PRIVILEGES;

USE `prado_unitest`;

DROP TABLE IF EXISTS `departments`;
CREATE TABLE `departments` (
  `department_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `description` TEXT NULL,
  `active` TINYINT(1) NOT NULL DEFAULT 0,
  `order` SMALLINT(3) NOT NULL DEFAULT 0,
  PRIMARY KEY (`department_id`)
)
AUTO_INCREMENT=1
ENGINE = INNODB
CHARACTER SET utf8 COLLATE utf8_general_ci;

INSERT INTO `departments` (`department_id`, `name`, `description`, `active`, `order`) VALUES
(1, 'Facilities', NULL, 0, 1),
(2, 'Marketing', NULL, 1, 2),
(3, 'Sales', NULL, 0, 3),
(4, 'Human resources', NULL, 1, 4),
(5, '+GX Service', NULL, 1, 5),
(6, 'Services', NULL, 1, 6),
(7, 'Logistics', NULL, 1, 7),
(8, 'Research and Development', NULL, 1, 8);

DROP TABLE IF EXISTS `department_sections`;
CREATE TABLE `department_sections` (
  `department_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `section_id` BIGINT UNSIGNED NOT NULL,
  `order` SMALLINT(3) NOT NULL DEFAULT 0,
  PRIMARY KEY (`department_id`, `section_id`)
)
AUTO_INCREMENT=1
ENGINE = INNODB
CHARACTER SET utf8 COLLATE utf8_general_ci;

INSERT INTO `department_sections` (`department_id`, `section_id`, `order`) VALUES
(1, 1, 1),
(1, 2, 2),
(2, 3, 3),
(2, 4, 4),
(2, 5, 5);

DROP TABLE IF EXISTS `simple_users`;
CREATE TABLE `simple_users` (
  `username` VARCHAR(255) NOT NULL,
  PRIMARY KEY (`username`)
)
AUTO_INCREMENT=1
ENGINE = INNODB
CHARACTER SET utf8 COLLATE utf8_general_ci;

INSERT INTO `simple_users` VALUES
('tom'),
('matt'),
('greg'),
('mickey'),
('brad'),
('zach'),
('ian'),
('lola'),
('david'),
('sam');

DROP TABLE IF EXISTS `blogs`;
CREATE TABLE `blogs` (
  `blog_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `blog_name` VARCHAR(255) NOT NULL,
  `blog_author` VARCHAR(255) NOT NULL,
  PRIMARY KEY (`blog_id`)
)
AUTO_INCREMENT=1
ENGINE = INNODB
CHARACTER SET utf8 COLLATE utf8_general_ci;

INSERT INTO blogs (blog_id, blog_name, blog_author) VALUES
(1, 'personal blog', 'personal blog');

DROP TABLE IF EXISTS `baserecordtest`;
CREATE TABLE `baserecordtest` (
  `baserecordtest_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`baserecordtest_id`)
)
AUTO_INCREMENT=1
ENGINE = INNODB
CHARACTER SET utf8 COLLATE utf8_general_ci;

DROP TABLE IF EXISTS `address`;
CREATE TABLE `address` (
  `username` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(255) NOT NULL,
  `field1_boolean` TINYINT(1) NOT NULL DEFAULT 0,
  `field2_date` DATE NOT NULL DEFAULT '2000-01-01',
  `field3_double` DOUBLE NOT NULL DEFAULT 0,
  `field4_integer` INT(10) NOT NULL DEFAULT 0,
  `field5_text` TEXT NULL,
  `field6_time` TIME NOT NULL DEFAULT 0,
  `field7_timestamp` TIMESTAMP NOT NULL DEFAULT '2000-01-01 00:00:00',
  `field8_money` DECIMAL(19,4) NOT NULL DEFAULT 0,
  `field9_numeric` NUMERIC NOT NULL DEFAULT 0,
  `int_fk1` INT(10) NOT NULL DEFAULT 0,
  `int_fk2` INT(10) NOT NULL DEFAULT 0,
  PRIMARY KEY (`username`)
)
AUTO_INCREMENT=1
ENGINE = INNODB
CHARACTER SET utf8 COLLATE utf8_general_ci;

INSERT INTO address (username, phone) VALUES
('wei', '1111111'),
('fabio', '2222222');

DROP TABLE IF EXISTS `Accounts`;
CREATE TABLE `Accounts`
(
  Account_Id INTEGER NOT NULL PRIMARY KEY,
  Account_FirstName VARCHAR(32) NOT NULL,
  Account_LastName VARCHAR(32) NOT NULL,
  Account_Email VARCHAR(128),
  Account_Banner_Option VARCHAR(255),
  Account_Cart_Option INT
);

INSERT INTO Accounts VALUES(1,'Joe', 'Dalton', 'Joe.Dalton@somewhere.com', 'Oui', 200);
INSERT INTO Accounts VALUES(2,'Averel', 'Dalton', 'Averel.Dalton@somewhere.com', 'Oui', 200);
INSERT INTO Accounts VALUES(3,'William', 'Dalton', null, 'Non', 100);
INSERT INTO Accounts VALUES(4,'Jack', 'Dalton', 'Jack.Dalton@somewhere.com', 'Non', 100);
INSERT INTO Accounts VALUES(5,'Gilles', 'Bayon', null, 'Oui', 100);

DROP TABLE IF EXISTS `Users`;
CREATE TABLE `Users` (
  `username` varchar(40) NOT NULL,
  `password` varchar(40) default NULL,
  `email` varchar(40) default NULL,
  `first_name` varchar(40) default NULL,
  `last_name` varchar(40) default NULL,
  `job_title` varchar(40) default NULL,
  `work_phone` varchar(40) default NULL,
  `work_fax` varchar(40) default NULL,
  `active` tinyint(1) default 1,
  `department_id` BIGINT UNSIGNED NULL,
  `salutation` varchar(40) default NULL,
  `hint_question` varchar(40) default NULL,
  `hint_answer` varchar(40) default NULL,
  PRIMARY KEY  (`username`)
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;

INSERT INTO Users VALUES('admin', '123456', 'Joe.Dalton@somewhere.com', 'Joe', 'Dalton', 'Ceo', '+1 234 567890', '+1 234 567890', 1, 1, 'Dear', 'fav color', 'red');

DROP TABLE IF EXISTS `dynamicparametertest1`;
CREATE TABLE `dynamicparametertest1` (
  `testname` varchar(50) NOT NULL,
  `teststring` varchar(50) NOT NULL,
  `testinteger` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `dynamicparametertest2`;
CREATE TABLE `dynamicparametertest2` (
  `testname` varchar(50) NOT NULL,
  `teststring` varchar(50) NOT NULL,
  `testinteger` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `dynamicparametertest1` (
  `testname` ,
  `teststring` ,
  `testinteger`
)
VALUES
('staticsql', 'staticsql1', '1'),
('dynamictable', 'dynamictableparametertest1', '1')
;

INSERT INTO `dynamicparametertest2` (
  `testname` ,
  `teststring` ,
  `testinteger`
)
VALUES
('staticsql', 'staticsql2', '2'),
('dynamictable', 'dynamictableparametertest2', '2')
;

DROP TABLE IF EXISTS `teams`;
CREATE TABLE `teams` (
  `name` varchar(50) NOT NULL,
  `location` varchar(50) NOT NULL,
  PRIMARY KEY  (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `players`;
CREATE TABLE `players` (
  `player_id` bigint(10) NOT NULL AUTO_INCREMENT,
  `age` SMALLINT(3) NOT NULL,
  `team` varchar(50) NOT NULL,
  `skills` bigint(10) NOT NULL,
  `profile` bigint(10) NOT NULL,
  PRIMARY KEY  (`player_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `profiles`;
CREATE TABLE `profiles` (
  `profile_id` bigint(10) NOT NULL AUTO_INCREMENT,
  `salary` SMALLINT(3) NOT NULL,
  `player` bigint(10) NOT NULL ,
  PRIMARY KEY  (`profile_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `skills`;
CREATE TABLE `skills` (
  `skill_id` bigint(10) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  PRIMARY KEY  (`skill_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `table1`;
CREATE TABLE `table1` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(45) NOT NULL,
  `field1` TINYINT(4) NOT NULL,
  `field2_text` TEXT NULL,
  `field3_date` DATE NULL DEFAULT '2007-02-25',
  `field4_float` FLOAT NOT NULL DEFAULT 10,
  `field5_float` FLOAT(5, 4) NOT NULL,
  `field6_double` DOUBLE NOT NULL,
  `field7_datetime` DATETIME NOT NULL,
  `field8_timestamp` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `field9_time` TIME NOT NULL,
  `field10_year` YEAR NOT NULL,
  `field11_enum` ENUM('one', 'two', 'three') NOT NULL DEFAULT 'one',
  `field12_set` SET('blue', 'red', 'green') NOT NULL,
  PRIMARY KEY  (`id`, `name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

