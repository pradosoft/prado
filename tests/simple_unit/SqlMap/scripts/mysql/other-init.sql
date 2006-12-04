
use IBatisNet;

drop table if exists Others;
drop table if exists A;
drop table if exists B;
drop table if exists C;
drop table if exists D;
drop table if exists E;
drop table if exists F;

create table Others
(
   Other_Int                      int,
   Other_Long                     bigint,
   Other_Bit					  bit not null default 0,
   Other_String		              varchar(32) not null
) TYPE=INNODB;

CREATE TABLE F (
	ID							varchar(50) NOT NULL ,
	F_Libelle					varchar(50) NULL ,
   primary key (ID)
) TYPE=INNODB;

CREATE TABLE E (
	ID							varchar(50) NOT NULL ,
	E_Libelle					varchar(50) NULL ,
   primary key (ID)
) TYPE=INNODB;

CREATE TABLE D (
	ID							varchar(50) NOT NULL ,
	D_Libelle					varchar(50) NULL ,
   primary key (ID)
) TYPE=INNODB;

CREATE TABLE C (
	ID							varchar(50) NOT NULL ,
	C_Libelle					varchar(50) NULL ,
   primary key (ID)
) TYPE=INNODB;


CREATE TABLE B (
	ID							varchar(50) NOT NULL ,
	C_ID						varchar(50) NULL ,
	D_ID						varchar(50) NULL ,
	B_Libelle					varchar(50) NULL ,
   primary key (ID)
) TYPE=INNODB;

ALTER TABLE B ADD CONSTRAINT FK_B_C FOREIGN KEY FK_B_C (C_ID)
    REFERENCES C (ID)
    ON DELETE RESTRICT
    ON UPDATE RESTRICT,
 ADD CONSTRAINT FK_B_D FOREIGN KEY FK_B_D (D_ID)
    REFERENCES D (ID)
    ON DELETE RESTRICT
    ON UPDATE RESTRICT;

CREATE TABLE A (
	ID							varchar(50) NOT NULL ,
	B_ID						varchar(50)  NULL ,
	E_ID						varchar(50)  NULL ,
	F_ID						varchar(50)  NULL ,
	A_Libelle					varchar(50) NULL ,
   primary key (ID)
) TYPE=INNODB;

ALTER TABLE A ADD CONSTRAINT FK_A_B FOREIGN KEY FK_A_B (B_ID)
    REFERENCES B (ID)
    ON DELETE RESTRICT
    ON UPDATE RESTRICT,
 ADD CONSTRAINT FK_A_E FOREIGN KEY FK_A_E (E_ID)
    REFERENCES E (ID)
    ON DELETE RESTRICT
    ON UPDATE RESTRICT,
 ADD CONSTRAINT FK_A_F FOREIGN KEY FK_A_F (F_ID)
    REFERENCES F (ID)
    ON DELETE RESTRICT;

INSERT INTO Others VALUES(1, 8888888, 0, 'Oui');
INSERT INTO Others VALUES(2, 9999999999, 1, 'Non');

INSERT INTO F VALUES('f', 'fff');
INSERT INTO E VALUES('e', 'eee');
INSERT INTO D VALUES('d', 'ddd');
INSERT INTO C VALUES('c', 'ccc');
INSERT INTO B VALUES('b', 'c', null, 'bbb');
INSERT INTO A VALUES('a', 'b', 'e', null, 'aaa');