CREATE DATABASE `prado_system_data_sqlmap`
	DEFAULT CHARACTER SET utf8
	COLLATE utf8_general_ci;

GRANT ALL ON `prado_system_data_sqlmap`.*
	TO 'prado_unitest'@'localhost'
	IDENTIFIED BY 'prado_system_data_sqlmap_unitest';

DROP TABLE IF EXISTS `dynamicparametertest1`;
CREATE TABLE `dynamicparametertest1` (
	`testname` varchar(50) NOT NULL,
	`teststring` varchar(50) NOT NULL,
	`testinteger` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `dynamicparametertest2`;
CREATE TABLE `dynamicparametertest2` (
	`testname` varchar(50) NOT NULL,
	`teststring` varchar(50) NOT NULL,
	`testinteger` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

