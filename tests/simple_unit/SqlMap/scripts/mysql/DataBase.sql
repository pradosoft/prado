use mysql;

drop database IBatisNet;
create database IBatisNet;

drop database NHibernate;
create database NHibernate;

grant all privileges on IBatisNet.* to IBatisNet@'%' identified by 'test';
grant all privileges on IBatisNet.* to IBatisNet@localhost identified by 'test';
grant all privileges on IBatisNet.* to IBatisNet@localhost.localdomain identified by 'test';

grant all privileges on NHibernate.* to NHibernate@'%' identified by 'test';
grant all privileges on NHibernate.* to NHibernate@localhost identified by 'test';
grant all privileges on NHibernate.* to NHibernate@localhost.localdomain identified by 'test';


/*==============================================================*/
/* Nom de la base :  MYSQL                                      */
/* Nom de SGBD :  MySQL 3.23                                    */
/* Date de cr閍tion :  27/05/2004 20:51:40                      */
/*==============================================================*/

use IBatisNet;

drop table if exists Accounts;

drop table if exists Categories;

drop table if exists Enumerations;

drop table if exists LineItems;

drop table if exists Orders;

drop table if exists Others;

drop table if exists Documents;

/*==============================================================*/
/* Table : Accounts                                             */
/*==============================================================*/
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

/*==============================================================*/
/* Table : Categories                                           */
/*==============================================================*/
create table Categories
(
   Category_Id                    int                            not null AUTO_INCREMENT,
   Category_Name                  varchar(32),
   Category_Guid                  varchar(36),
   primary key (Category_Id)
) TYPE=INNODB;

/*==============================================================*/
/* Table : Enumerations                                         */
/*==============================================================*/
create table Enumerations
(
   Enum_Id                        int                            not null,
   Enum_Day                       int                            not null,
   Enum_Color                     int                            not null,
   Enum_Month                     int,
   primary key (Enum_Id)
) TYPE=INNODB;

/*==============================================================*/
/* Table : LineItems                                            */
/*==============================================================*/
create table LineItems
(
   LineItem_Id                    int                            not null,
   Order_Id                       int                            not null,
   LineItem_Code                  varchar(32)                    not null,
   LineItem_Quantity              int                            not null,
   LineItem_Price                 decimal(18,2),
   LineItem_Picture					blob,
   primary key (Order_Id, LineItem_Id)
) TYPE=INNODB;

/*==============================================================*/
/* Table : Orders                                               */
/*==============================================================*/
create table Orders
(
   Order_Id                       int                            not null,
   Account_Id                     int                            null,
   Order_Date                     datetime,
   Order_CardType                 varchar(32),
   Order_CardNumber               varchar(32),
   Order_CardExpiry               varchar(32),
   Order_Street                   varchar(32),
   Order_City                     varchar(32),
   Order_Province                 varchar(32),
   Order_PostalCode               varchar(32),
   Order_FavouriteLineItem        int,
   primary key (Order_Id)
) TYPE=INNODB;

/*==============================================================*/
/* Table : Others                                               */
/*==============================================================*/
create table Others
(
   Other_Int                       int,
   Other_Long                     bigint,
   Other_Bit		            bit not null default 0,
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

/*==============================================================*/
/* Table : Documents                                            */
/*==============================================================*/
create table Documents
(
   Document_Id                    int                            not null,
   Document_Title                  varchar(32),
   Document_Type                  varchar(32),
   Document_PageNumber				int,
   Document_City					varchar(32),
   primary key (DOCUMENT_ID)
) TYPE=INNODB;



use NHibernate;

drop table if exists Users;

/*==============================================================*/
/* Table : Users                                                */
/*==============================================================*/
create table Users
(
   LogonId                      varchar(20)						not null default '0',
   Name							varchar(40)                     default null,
   Password                     varchar(20)						default null,
   EmailAddress                 varchar(40)						default null,
   LastLogon					datetime						default null,
   primary key (LogonId)
) TYPE=INNODB;
