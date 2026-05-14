/* Firebird SqlMap test database schema.
   Run via isql-fb after connecting to the prado_unitest.fdb database.
   IF EXISTS prevents errors on a fresh database (Firebird 4.0+ syntax). */

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
DROP SEQUENCE IF EXISTS categories_gen;

CREATE TABLE C (
    ID VARCHAR(50) NOT NULL PRIMARY KEY,
    C_Libelle VARCHAR(50)
);
INSERT INTO C VALUES ('c', 'ccc');

CREATE TABLE D (
    ID VARCHAR(50) NOT NULL PRIMARY KEY,
    D_Libelle VARCHAR(50)
);
INSERT INTO D VALUES ('d', 'ddd');

CREATE TABLE B (
    ID VARCHAR(50) NOT NULL PRIMARY KEY,
    C_ID VARCHAR(50),
    D_ID VARCHAR(50),
    B_Libelle VARCHAR(50)
);
INSERT INTO B VALUES ('b', 'c', NULL, 'bbb');

CREATE TABLE E (
    ID VARCHAR(50) NOT NULL PRIMARY KEY,
    E_Libelle VARCHAR(50)
);
INSERT INTO E VALUES ('e', 'eee');

CREATE TABLE F (
    ID VARCHAR(50) NOT NULL PRIMARY KEY,
    F_Libelle VARCHAR(50)
);
INSERT INTO F VALUES ('f', 'fff');

CREATE TABLE A (
    ID VARCHAR(50) NOT NULL PRIMARY KEY,
    B_ID VARCHAR(50),
    E_ID VARCHAR(50),
    F_ID VARCHAR(50),
    A_Libelle VARCHAR(50)
);
INSERT INTO A VALUES ('a', 'b', 'e', NULL, 'aaa');

CREATE TABLE Accounts (
    Account_Id INTEGER NOT NULL PRIMARY KEY,
    Account_FirstName VARCHAR(32) NOT NULL,
    Account_LastName VARCHAR(32) NOT NULL,
    Account_Email VARCHAR(128),
    Account_Banner_Option VARCHAR(255),
    Account_Cart_Option INTEGER
);
INSERT INTO Accounts VALUES (1, 'Joe', 'Dalton', 'Joe.Dalton@somewhere.com', 'Oui', 200);
INSERT INTO Accounts VALUES (2, 'Averel', 'Dalton', 'Averel.Dalton@somewhere.com', 'Oui', 200);
INSERT INTO Accounts VALUES (3, 'William', 'Dalton', NULL, 'Non', 100);
INSERT INTO Accounts VALUES (4, 'Jack', 'Dalton', 'Jack.Dalton@somewhere.com', 'Non', 100);
INSERT INTO Accounts VALUES (5, 'Gilles', 'Bayon', NULL, 'Oui', 100);

CREATE GENERATOR categories_gen;
SET GENERATOR categories_gen TO 0;
CREATE TABLE Categories (
    Category_Id INTEGER NOT NULL PRIMARY KEY,
    Category_Name VARCHAR(32),
    Category_Guid VARCHAR(36)
);

CREATE TABLE Documents (
    Document_Id INTEGER NOT NULL PRIMARY KEY,
    Document_Title VARCHAR(32),
    Document_Type VARCHAR(32),
    Document_PageNumber INTEGER,
    Document_City VARCHAR(32)
);
INSERT INTO Documents VALUES (1, 'The World of Null-A', 'Book', 55, NULL);
INSERT INTO Documents VALUES (2, 'Le Progres de Lyon', 'Newspaper', NULL, 'Lyon');
INSERT INTO Documents VALUES (3, 'Lord of the Rings', 'Book', 3587, NULL);
INSERT INTO Documents VALUES (4, 'Le Canard enchaine', 'Tabloid', NULL, 'Paris');
INSERT INTO Documents VALUES (5, 'Le Monde', 'Broadsheet', NULL, 'Paris');
INSERT INTO Documents VALUES (6, 'Foundation', 'Monograph', 557, NULL);

CREATE TABLE Enumerations (
    Enum_Id INTEGER NOT NULL,
    Enum_Day INTEGER NOT NULL,
    Enum_Color INTEGER NOT NULL,
    Enum_Month INTEGER
);
INSERT INTO Enumerations VALUES (1, 1, 1, 128);
INSERT INTO Enumerations VALUES (2, 2, 2, 2048);
INSERT INTO Enumerations VALUES (3, 3, 4, 256);
INSERT INTO Enumerations VALUES (4, 4, 8, NULL);

CREATE TABLE Orders (
    Order_Id INTEGER NOT NULL PRIMARY KEY,
    Account_Id INTEGER,
    Order_Date TIMESTAMP,
    Order_CardType VARCHAR(32),
    Order_CardNumber VARCHAR(32),
    Order_CardExpiry VARCHAR(32),
    Order_Street VARCHAR(32),
    Order_City VARCHAR(32),
    Order_Province VARCHAR(32),
    Order_PostalCode VARCHAR(32),
    Order_FavouriteLineItem INTEGER
);
INSERT INTO Orders VALUES (1,  1,  '2003-02-15 08:15:00', 'VISA', '999999999999', '05/03', '11 This Street',  'Victoria', 'BC', 'C4B 4F4', 2);
INSERT INTO Orders VALUES (2,  4,  '2003-02-15 08:15:00', 'MC',   '888888888888', '06/03', '222 That Street', 'Edmonton', 'AB', 'X4K 5Y4', 1);
INSERT INTO Orders VALUES (3,  3,  '2003-02-15 08:15:00', 'AMEX', '777777777777', '07/03', '333 Other Street','Regina',   'SK', 'Z4U 6Y4', 2);
INSERT INTO Orders VALUES (4,  2,  '2003-02-15 08:15:00', 'MC',   '666666666666', '08/03', '444 His Street',  'Toronto',  'ON', 'K4U 3S4', 1);
INSERT INTO Orders VALUES (5,  5,  '2003-02-15 08:15:00', 'VISA', '555555555555', '09/03', '555 Her Street',  'Calgary',  'AB', 'J4J 7S4', 2);
INSERT INTO Orders VALUES (6,  5,  '2003-02-15 08:15:00', 'VISA', '999999999999', '10/03', '6 Their Street',  'Victoria', 'BC', 'T4H 9G4', 1);
INSERT INTO Orders VALUES (7,  4,  '2003-02-15 08:15:00', 'MC',   '888888888888', '11/03', '77 Lucky Street', 'Edmonton', 'AB', 'R4A 0Z4', 2);
INSERT INTO Orders VALUES (8,  3,  '2003-02-15 08:15:00', 'AMEX', '777777777777', '12/03', '888 Our Street',  'Regina',   'SK', 'S4S 7G4', 1);
INSERT INTO Orders VALUES (9,  2,  '2003-02-15 08:15:00', 'MC',   '666666666666', '01/04', '999 Your Street', 'Toronto',  'ON', 'G4D 9F4', 2);
INSERT INTO Orders VALUES (10, 1,  '2003-02-15 08:15:00', 'VISA', '555555555555', '02/04', '99 Some Street',  'Calgary',  'AB', 'W4G 7A4', 1);
INSERT INTO Orders VALUES (11, NULL,'2003-02-15 08:15:00','VISA', '555555555555', '02/04', 'Null order',      'Calgary',  'ZZ', 'XXX YYY', 1);

CREATE TABLE LineItems (
    LineItem_Id INTEGER NOT NULL,
    Order_Id INTEGER NOT NULL,
    LineItem_Code VARCHAR(32) NOT NULL,
    LineItem_Quantity INTEGER NOT NULL,
    LineItem_Price DECIMAL(18,2),
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
    Other_Int INTEGER,
    Other_Long BIGINT,
    Other_Bit SMALLINT DEFAULT 0 NOT NULL,
    Other_String VARCHAR(32) NOT NULL
);
INSERT INTO Others VALUES (1, 8888888,    0, 'Oui');
INSERT INTO Others VALUES (2, 9999999999, 1, 'Non');

CREATE TABLE Users (
    LogonId VARCHAR(20) DEFAULT '0' NOT NULL PRIMARY KEY,
    Name VARCHAR(40),
    Password VARCHAR(20),
    EmailAddress VARCHAR(40),
    LastLogon TIMESTAMP
);

COMMIT;
