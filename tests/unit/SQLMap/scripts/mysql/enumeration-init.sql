
use IBatisNet;

drop table if exists Enumerations;

create table Enumerations
(
   Enum_Id                        int                            not null,
   Enum_Day                       int                            not null,
   Enum_Color                     int                            not null,
   Enum_Month                     int,
   primary key (Enum_Id)
) TYPE=INNODB;

INSERT INTO Enumerations VALUES(1, 1, 1, 128);
INSERT INTO Enumerations VALUES(2, 2, 2, 2048);
INSERT INTO Enumerations VALUES(3, 3, 4, 256);
INSERT INTO Enumerations VALUES(4, 4, 8, null);
