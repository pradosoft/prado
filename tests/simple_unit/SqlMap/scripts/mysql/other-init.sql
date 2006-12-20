
TRUNCATE `Others`;
TRUNCATE `A`;
TRUNCATE `B`;
TRUNCATE `C`;
TRUNCATE `D`;
TRUNCATE `E`;
TRUNCATE `F`;

INSERT INTO Others VALUES(1, 8888888, 0, 'Oui');
INSERT INTO Others VALUES(2, 9999999999, 1, 'Non');

INSERT INTO F VALUES('f', 'fff');
INSERT INTO E VALUES('e', 'eee');
INSERT INTO D VALUES('d', 'ddd');
INSERT INTO C VALUES('c', 'ccc');
INSERT INTO B VALUES('b', 'c', null, 'bbb');
INSERT INTO A VALUES('a', 'b', 'e', null, 'aaa');