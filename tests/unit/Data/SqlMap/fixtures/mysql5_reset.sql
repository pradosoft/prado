TRUNCATE TABLE `prado_system_data_sqlmap`.`dynamicparametertest1`;
TRUNCATE TABLE `prado_system_data_sqlmap`.`dynamicparametertest2`;

INSERT INTO `prado_system_data_sqlmap`.`dynamicparametertest1` (
	`testname` ,
	`teststring` ,
	`testinteger`
)
VALUES
('staticsql', 'staticsql1', '1'),
('dynamictable', 'dynamictableparametertest1', '1')
;

INSERT INTO `prado_system_data_sqlmap`.`dynamicparametertest2` (
	`testname` ,
	`teststring` ,
	`testinteger`
)
VALUES
('staticsql', 'staticsql2', '2'),
('dynamictable', 'dynamictableparametertest2', '2')
;
