
use IBatisNet;

drop table if exists Categories;

create table Categories
(
   Category_Id                    int                            not null AUTO_INCREMENT,
   Category_Name                  varchar(32),
   Category_Guid                  varchar(36),
   primary key (Category_Id)
) TYPE=INNODB;
