-- Oracle SqlMap test database schema.
-- Statements separated by semicolons for DefaultScriptRunner compatibility.
-- Requires Oracle 21c+ for DROP TABLE IF EXISTS / DROP SEQUENCE IF EXISTS.
-- CI uses Oracle 23.6 which satisfies this requirement.

DROP TABLE IF EXISTS LineItems;
DROP TABLE IF EXISTS Orders;
DROP TABLE IF EXISTS Accounts;
DROP TABLE IF EXISTS Categories;
DROP TABLE IF EXISTS Documents;
DROP TABLE IF EXISTS Enumerations;
DROP TABLE IF EXISTS Others;
DROP TABLE IF EXISTS Users;
DROP TABLE IF EXISTS A;
DROP TABLE IF EXISTS B;
DROP TABLE IF EXISTS C;
DROP TABLE IF EXISTS D;
DROP TABLE IF EXISTS E;
DROP TABLE IF EXISTS F;
DROP SEQUENCE IF EXISTS categories_seq;

CREATE TABLE C (
    ID VARCHAR2(50) NOT NULL PRIMARY KEY,
    C_Libelle VARCHAR2(50)
);
INSERT INTO C VALUES ('c', 'ccc');

CREATE TABLE D (
    ID VARCHAR2(50) NOT NULL PRIMARY KEY,
    D_Libelle VARCHAR2(50)
);
INSERT INTO D VALUES ('d', 'ddd');

CREATE TABLE B (
    ID VARCHAR2(50) NOT NULL PRIMARY KEY,
    C_ID VARCHAR2(50),
    D_ID VARCHAR2(50),
    B_Libelle VARCHAR2(50)
);
INSERT INTO B VALUES ('b', 'c', NULL, 'bbb');

CREATE TABLE E (
    ID VARCHAR2(50) NOT NULL PRIMARY KEY,
    E_Libelle VARCHAR2(50)
);
INSERT INTO E VALUES ('e', 'eee');

CREATE TABLE F (
    ID VARCHAR2(50) NOT NULL PRIMARY KEY,
    F_Libelle VARCHAR2(50)
);
INSERT INTO F VALUES ('f', 'fff');

CREATE TABLE A (
    ID VARCHAR2(50) NOT NULL PRIMARY KEY,
    B_ID VARCHAR2(50),
    E_ID VARCHAR2(50),
    F_ID VARCHAR2(50),
    A_Libelle VARCHAR2(50)
);
INSERT INTO A VALUES ('a', 'b', 'e', NULL, 'aaa');

CREATE TABLE Accounts (
    Account_Id NUMBER(10) NOT NULL PRIMARY KEY,
    Account_FirstName VARCHAR2(32) NOT NULL,
    Account_LastName VARCHAR2(32) NOT NULL,
    Account_Email VARCHAR2(128),
    Account_Banner_Option VARCHAR2(255),
    Account_Cart_Option NUMBER(10)
);
INSERT INTO Accounts VALUES (1, 'Joe', 'Dalton', 'Joe.Dalton@somewhere.com', 'Oui', 200);
INSERT INTO Accounts VALUES (2, 'Averel', 'Dalton', 'Averel.Dalton@somewhere.com', 'Oui', 200);
INSERT INTO Accounts VALUES (3, 'William', 'Dalton', NULL, 'Non', 100);
INSERT INTO Accounts VALUES (4, 'Jack', 'Dalton', 'Jack.Dalton@somewhere.com', 'Non', 100);
INSERT INTO Accounts VALUES (5, 'Gilles', 'Bayon', NULL, 'Oui', 100);

CREATE SEQUENCE categories_seq START WITH 1 INCREMENT BY 1;
CREATE TABLE Categories (
    Category_Id NUMBER(10) DEFAULT categories_seq.NEXTVAL NOT NULL PRIMARY KEY,
    Category_Name VARCHAR2(32),
    Category_Guid VARCHAR2(36)
);

CREATE TABLE Documents (
    Document_Id NUMBER(10) NOT NULL PRIMARY KEY,
    Document_Title VARCHAR2(32),
    Document_Type VARCHAR2(32),
    Document_PageNumber NUMBER(10),
    Document_City VARCHAR2(32)
);
INSERT INTO Documents VALUES (1, 'The World of Null-A', 'Book', 55, NULL);
INSERT INTO Documents VALUES (2, 'Le Progres de Lyon', 'Newspaper', NULL, 'Lyon');
INSERT INTO Documents VALUES (3, 'Lord of the Rings', 'Book', 3587, NULL);
INSERT INTO Documents VALUES (4, 'Le Canard enchaine', 'Tabloid', NULL, 'Paris');
INSERT INTO Documents VALUES (5, 'Le Monde', 'Broadsheet', NULL, 'Paris');
INSERT INTO Documents VALUES (6, 'Foundation', 'Monograph', 557, NULL);

CREATE TABLE Enumerations (
    Enum_Id NUMBER(10) NOT NULL,
    Enum_Day NUMBER(10) NOT NULL,
    Enum_Color NUMBER(10) NOT NULL,
    Enum_Month NUMBER(10)
);
INSERT INTO Enumerations VALUES (1, 1, 1, 128);
INSERT INTO Enumerations VALUES (2, 2, 2, 2048);
INSERT INTO Enumerations VALUES (3, 3, 4, 256);
INSERT INTO Enumerations VALUES (4, 4, 8, NULL);

CREATE TABLE Orders (
    Order_Id NUMBER(10) NOT NULL PRIMARY KEY,
    Account_Id NUMBER(10),
    Order_Date TIMESTAMP,
    Order_CardType VARCHAR2(32),
    Order_CardNumber VARCHAR2(32),
    Order_CardExpiry VARCHAR2(32),
    Order_Street VARCHAR2(32),
    Order_City VARCHAR2(32),
    Order_Province VARCHAR2(32),
    Order_PostalCode VARCHAR2(32),
    Order_FavouriteLineItem NUMBER(10)
);
INSERT INTO Orders VALUES (1,  1,  TIMESTAMP '2003-02-15 08:15:00', 'VISA', '999999999999', '05/03', '11 This Street',  'Victoria', 'BC', 'C4B 4F4', 2);
INSERT INTO Orders VALUES (2,  4,  TIMESTAMP '2003-02-15 08:15:00', 'MC',   '888888888888', '06/03', '222 That Street', 'Edmonton', 'AB', 'X4K 5Y4', 1);
INSERT INTO Orders VALUES (3,  3,  TIMESTAMP '2003-02-15 08:15:00', 'AMEX', '777777777777', '07/03', '333 Other Street', 'Regina',  'SK', 'Z4U 6Y4', 2);
INSERT INTO Orders VALUES (4,  2,  TIMESTAMP '2003-02-15 08:15:00', 'MC',   '666666666666', '08/03', '444 His Street',   'Toronto', 'ON', 'K4U 3S4', 1);
INSERT INTO Orders VALUES (5,  5,  TIMESTAMP '2003-02-15 08:15:00', 'VISA', '555555555555', '09/03', '555 Her Street',   'Calgary', 'AB', 'J4J 7S4', 2);
INSERT INTO Orders VALUES (6,  5,  TIMESTAMP '2003-02-15 08:15:00', 'VISA', '999999999999', '10/03', '6 Their Street',   'Victoria','BC', 'T4H 9G4', 1);
INSERT INTO Orders VALUES (7,  4,  TIMESTAMP '2003-02-15 08:15:00', 'MC',   '888888888888', '11/03', '77 Lucky Street',  'Edmonton','AB', 'R4A 0Z4', 2);
INSERT INTO Orders VALUES (8,  3,  TIMESTAMP '2003-02-15 08:15:00', 'AMEX', '777777777777', '12/03', '888 Our Street',   'Regina',  'SK', 'S4S 7G4', 1);
INSERT INTO Orders VALUES (9,  2,  TIMESTAMP '2003-02-15 08:15:00', 'MC',   '666666666666', '01/04', '999 Your Street',  'Toronto', 'ON', 'G4D 9F4', 2);
INSERT INTO Orders VALUES (10, 1,  TIMESTAMP '2003-02-15 08:15:00', 'VISA', '555555555555', '02/04', '99 Some Street',   'Calgary', 'AB', 'W4G 7A4', 1);
INSERT INTO Orders VALUES (11, NULL,TIMESTAMP '2003-02-15 08:15:00','VISA', '555555555555', '02/04', 'Null order',       'Calgary', 'ZZ', 'XXX YYY', 1);

CREATE TABLE LineItems (
    LineItem_Id NUMBER(10) NOT NULL,
    Order_Id NUMBER(10) NOT NULL,
    LineItem_Code VARCHAR2(32) NOT NULL,
    LineItem_Quantity NUMBER(10) NOT NULL,
    LineItem_Price NUMBER(18,2),
    LineItem_Picture BLOB
);
INSERT INTO LineItems VALUES (1, 10, 'ESM-34', 1,  45.43, NULL);
INSERT INTO LineItems VALUES (2, 10, 'QSM-98', 8,   8.40, NULL);
INSERT INTO LineItems VALUES (1,  9, 'DSM-78', 2,  45.40, NULL);
INSERT INTO LineItems VALUES (2,  9, 'TSM-12', 2,  32.12, NULL);
INSERT INTO LineItems VALUES (1,  8, 'DSM-16', 4,  41.30, NULL);
INSERT INTO LineItems VALUES (2,  8, 'GSM-65', 1,   2.20, NULL);
INSERT INTO LineItems VALUES (1,  7, 'WSM-27', 7,  52.10, NULL);
INSERT INTO LineItems VALUES (2,  7, 'ESM-23', 2, 123.34, NULL);
INSERT INTO LineItems VALUES (1,  6, 'QSM-39', 9,  12.12, NULL);
INSERT INTO LineItems VALUES (2,  6, 'ASM-45', 6,  78.77, NULL);
INSERT INTO LineItems VALUES (1,  5, 'ESM-48', 3,  43.87, NULL);
INSERT INTO LineItems VALUES (2,  5, 'WSM-98', 7,   5.40, NULL);
INSERT INTO LineItems VALUES (1,  4, 'RSM-57', 2,  78.90, NULL);
INSERT INTO LineItems VALUES (2,  4, 'XSM-78', 9,   2.34, NULL);
INSERT INTO LineItems VALUES (1,  3, 'DSM-59', 3,   5.70, NULL);
INSERT INTO LineItems VALUES (2,  3, 'DSM-53', 3,  98.78, NULL);
INSERT INTO LineItems VALUES (1,  2, 'DSM-37', 4,   7.80, NULL);
INSERT INTO LineItems VALUES (2,  2, 'FSM-12', 2,  55.78, NULL);
INSERT INTO LineItems VALUES (1,  1, 'ESM-48', 8,  87.60, NULL);
INSERT INTO LineItems VALUES (2,  1, 'ESM-23', 1,  55.40, NULL);

CREATE TABLE Others (
    Other_Int NUMBER(10),
    Other_Long NUMBER(20),
    Other_Bit NUMBER(1) DEFAULT 0 NOT NULL,
    Other_String VARCHAR2(32) NOT NULL
);
INSERT INTO Others VALUES (1, 8888888,    0, 'Oui');
INSERT INTO Others VALUES (2, 9999999999, 1, 'Non');

CREATE TABLE Users (
    LogonId VARCHAR2(20) DEFAULT '0' NOT NULL PRIMARY KEY,
    Name VARCHAR2(40),
    Password VARCHAR2(20),
    EmailAddress VARCHAR2(40),
    LastLogon TIMESTAMP
);

COMMIT;
