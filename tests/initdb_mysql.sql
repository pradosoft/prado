DROP DATABASE IF EXISTS `prado_unitest`;
CREATE DATABASE `prado_unitest`;
CREATE USER 'prado_unitest'@'localhost' identified by 'prado_unitest';
GRANT ALL ON `prado_unitest`.* TO 'prado_unitest'@'localhost';
FLUSH PRIVILEGES;

USE `prado_unitest`;

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
