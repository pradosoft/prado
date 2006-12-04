use IBatisNet;

drop table if exists Accounts;

create table Accounts
(
   Account_Id                     int                            not null,
   Account_FirstName              varchar(32)                    not null,
   Account_LastName               varchar(32)                    not null,
   Account_Email                  varchar(128),
   Account_Banner_Option		  varchar(255),
   Account_Cart_Option			  int,
   primary key (Account_Id)
) TYPE=INNODB;

INSERT INTO Accounts VALUES(1,'Joe', 'Dalton', 'Joe.Dalton@somewhere.com', 'Oui', 200);
INSERT INTO Accounts VALUES(2,'Averel', 'Dalton', 'Averel.Dalton@somewhere.com', 'Oui', 200);
INSERT INTO Accounts VALUES(3,'William', 'Dalton', null, 'Non', 100);
INSERT INTO Accounts VALUES(4,'Jack', 'Dalton', 'Jack.Dalton@somewhere.com', 'Non', 100);
INSERT INTO Accounts VALUES(5,'Gilles', 'Bayon', null, 'Oui', 100);