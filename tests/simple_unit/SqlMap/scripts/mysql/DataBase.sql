-- 
-- Table structure for table `A`
-- 

DROP TABLE IF EXISTS `A`;
CREATE TABLE `A` (
  `ID` varchar(50) NOT NULL,
  `B_ID` varchar(50) default NULL,
  `E_ID` varchar(50) default NULL,
  `F_ID` varchar(50) default NULL,
  `A_Libelle` varchar(50) default NULL,
  PRIMARY KEY  (`ID`),
  KEY `FK_A_B` (`B_ID`),
  KEY `FK_A_E` (`E_ID`),
  KEY `FK_A_F` (`F_ID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table `A`
-- 

INSERT INTO `A` (`ID`, `B_ID`, `E_ID`, `F_ID`, `A_Libelle`) VALUES ('a', 'b', 'e', NULL, 'aaa');

-- --------------------------------------------------------

-- 
-- Table structure for table `Accounts`
-- 

DROP TABLE IF EXISTS `Accounts`;
CREATE TABLE `Accounts` (
  `Account_Id` int(11) NOT NULL,
  `Account_FirstName` varchar(32) NOT NULL,
  `Account_LastName` varchar(32) NOT NULL,
  `Account_Email` varchar(128) default NULL,
  `Account_Banner_Option` varchar(255) default NULL,
  `Account_Cart_Option` int(11) default NULL,
  PRIMARY KEY  (`Account_Id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table `Accounts`
-- 

INSERT INTO `Accounts` (`Account_Id`, `Account_FirstName`, `Account_LastName`, `Account_Email`, `Account_Banner_Option`, `Account_Cart_Option`) VALUES (1, 'Joe', 'Dalton', 'Joe.Dalton@somewhere.com', 'Oui', 200),
(2, 'Averel', 'Dalton', 'Averel.Dalton@somewhere.com', 'Oui', 200),
(3, 'William', 'Dalton', NULL, 'Non', 100),
(4, 'Jack', 'Dalton', 'Jack.Dalton@somewhere.com', 'Non', 100),
(5, 'Gilles', 'Bayon', NULL, 'Oui', 100);

-- --------------------------------------------------------

-- 
-- Table structure for table `B`
-- 

DROP TABLE IF EXISTS `B`;
CREATE TABLE `B` (
  `ID` varchar(50) NOT NULL,
  `C_ID` varchar(50) default NULL,
  `D_ID` varchar(50) default NULL,
  `B_Libelle` varchar(50) default NULL,
  PRIMARY KEY  (`ID`),
  KEY `FK_B_C` (`C_ID`),
  KEY `FK_B_D` (`D_ID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table `B`
-- 

INSERT INTO `B` (`ID`, `C_ID`, `D_ID`, `B_Libelle`) VALUES ('b', 'c', NULL, 'bbb');

-- --------------------------------------------------------

-- 
-- Table structure for table `C`
-- 

DROP TABLE IF EXISTS `C`;
CREATE TABLE `C` (
  `ID` varchar(50) NOT NULL,
  `C_Libelle` varchar(50) default NULL,
  PRIMARY KEY  (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table `C`
-- 

INSERT INTO `C` (`ID`, `C_Libelle`) VALUES ('c', 'ccc');

-- --------------------------------------------------------

-- 
-- Table structure for table `Categories`
-- 

DROP TABLE IF EXISTS `Categories`;
CREATE TABLE `Categories` (
  `Category_Id` int(11) NOT NULL auto_increment,
  `Category_Name` varchar(32) default NULL,
  `Category_Guid` varchar(36) default NULL,
  PRIMARY KEY  (`Category_Id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- 
-- Dumping data for table `Categories`
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table `D`
-- 

DROP TABLE IF EXISTS `D`;
CREATE TABLE `D` (
  `ID` varchar(50) NOT NULL,
  `D_Libelle` varchar(50) default NULL,
  PRIMARY KEY  (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table `D`
-- 

INSERT INTO `D` (`ID`, `D_Libelle`) VALUES ('d', 'ddd');

-- --------------------------------------------------------

-- 
-- Table structure for table `Documents`
-- 

DROP TABLE IF EXISTS `Documents`;
CREATE TABLE `Documents` (
  `Document_Id` int(11) NOT NULL,
  `Document_Title` varchar(32) default NULL,
  `Document_Type` varchar(32) default NULL,
  `Document_PageNumber` int(11) default NULL,
  `Document_City` varchar(32) default NULL,
  PRIMARY KEY  (`Document_Id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table `Documents`
-- 

INSERT INTO `Documents` (`Document_Id`, `Document_Title`, `Document_Type`, `Document_PageNumber`, `Document_City`) VALUES (1, 'The World of Null-A', 'Book', 55, NULL),
(2, 'Le Progres de Lyon', 'Newspaper', NULL, 'Lyon'),
(3, 'Lord of the Rings', 'Book', 3587, NULL),
(4, 'Le Canard enchaine', 'Tabloid', NULL, 'Paris'),
(5, 'Le Monde', 'Broadsheet', NULL, 'Paris'),
(6, 'Foundation', 'Monograph', 557, NULL);

-- --------------------------------------------------------

-- 
-- Table structure for table `E`
-- 

DROP TABLE IF EXISTS `E`;
CREATE TABLE `E` (
  `ID` varchar(50) NOT NULL,
  `E_Libelle` varchar(50) default NULL,
  PRIMARY KEY  (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table `E`
-- 

INSERT INTO `E` (`ID`, `E_Libelle`) VALUES ('e', 'eee');

-- --------------------------------------------------------

-- 
-- Table structure for table `Enumerations`
-- 

DROP TABLE IF EXISTS `Enumerations`;
CREATE TABLE `Enumerations` (
  `Enum_Id` int(11) NOT NULL,
  `Enum_Day` int(11) NOT NULL,
  `Enum_Color` int(11) NOT NULL,
  `Enum_Month` int(11) default NULL,
  PRIMARY KEY  (`Enum_Id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table `Enumerations`
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table `F`
-- 

DROP TABLE IF EXISTS `F`;
CREATE TABLE `F` (
  `ID` varchar(50) NOT NULL,
  `F_Libelle` varchar(50) default NULL,
  PRIMARY KEY  (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table `F`
-- 

INSERT INTO `F` (`ID`, `F_Libelle`) VALUES ('f', 'fff');

-- --------------------------------------------------------

-- 
-- Table structure for table `LineItems`
-- 

DROP TABLE IF EXISTS `LineItems`;
CREATE TABLE `LineItems` (
  `LineItem_Id` int(11) NOT NULL,
  `Order_Id` int(11) NOT NULL,
  `LineItem_Code` varchar(32) NOT NULL,
  `LineItem_Quantity` int(11) NOT NULL,
  `LineItem_Price` decimal(18,2) default NULL,
  `LineItem_Picture` blob,
  PRIMARY KEY  (`Order_Id`,`LineItem_Id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table `LineItems`
-- 

INSERT INTO `LineItems` (`LineItem_Id`, `Order_Id`, `LineItem_Code`, `LineItem_Quantity`, `LineItem_Price`, `LineItem_Picture`) VALUES (1, 1, 'ESM-48', 8, 87.60, NULL),
(2, 1, 'ESM-23', 1, 55.40, NULL),
(1, 2, 'DSM-37', 4, 7.80, NULL),
(2, 2, 'FSM-12', 2, 55.78, NULL),
(1, 3, 'DSM-59', 3, 5.70, NULL),
(2, 3, 'DSM-53', 3, 98.78, NULL),
(1, 4, 'RSM-57', 2, 78.90, NULL),
(2, 4, 'XSM-78', 9, 2.34, NULL),
(1, 5, 'ESM-48', 3, 43.87, NULL),
(2, 5, 'WSM-98', 7, 5.40, NULL),
(1, 6, 'QSM-39', 9, 12.12, NULL),
(2, 6, 'ASM-45', 6, 78.77, NULL),
(1, 7, 'WSM-27', 7, 52.10, NULL),
(2, 7, 'ESM-23', 2, 123.34, NULL),
(1, 8, 'DSM-16', 4, 41.30, NULL),
(2, 8, 'GSM-65', 1, 2.20, NULL),
(1, 9, 'DSM-78', 2, 45.40, NULL),
(2, 9, 'TSM-12', 2, 32.12, NULL),
(1, 10, 'ESM-34', 1, 45.43, NULL),
(2, 10, 'QSM-98', 8, 8.40, NULL);

-- --------------------------------------------------------

-- 
-- Table structure for table `Orders`
-- 

DROP TABLE IF EXISTS `Orders`;
CREATE TABLE `Orders` (
  `Order_Id` int(11) NOT NULL,
  `Account_Id` int(11) default NULL,
  `Order_Date` datetime default NULL,
  `Order_CardType` varchar(32) default NULL,
  `Order_CardNumber` varchar(32) default NULL,
  `Order_CardExpiry` varchar(32) default NULL,
  `Order_Street` varchar(32) default NULL,
  `Order_City` varchar(32) default NULL,
  `Order_Province` varchar(32) default NULL,
  `Order_PostalCode` varchar(32) default NULL,
  `Order_FavouriteLineItem` int(11) default NULL,
  PRIMARY KEY  (`Order_Id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table `Orders`
-- 

INSERT INTO `Orders` (`Order_Id`, `Account_Id`, `Order_Date`, `Order_CardType`, `Order_CardNumber`, `Order_CardExpiry`, `Order_Street`, `Order_City`, `Order_Province`, `Order_PostalCode`, `Order_FavouriteLineItem`) VALUES (1, 1, '2003-02-15 08:15:00', 'VISA', '999999999999', '05/03', '11 This Street', 'Victoria', 'BC', 'C4B 4F4', 2),
(2, 4, '2003-02-15 08:15:00', 'MC', '888888888888', '06/03', '222 That Street', 'Edmonton', 'AB', 'X4K 5Y4', 1),
(3, 3, '2003-02-15 08:15:00', 'AMEX', '777777777777', '07/03', '333 Other Street', 'Regina', 'SK', 'Z4U 6Y4', 2),
(4, 2, '2003-02-15 08:15:00', 'MC', '666666666666', '08/03', '444 His Street', 'Toronto', 'ON', 'K4U 3S4', 1),
(5, 5, '2003-02-15 08:15:00', 'VISA', '555555555555', '09/03', '555 Her Street', 'Calgary', 'AB', 'J4J 7S4', 2),
(6, 5, '2003-02-15 08:15:00', 'VISA', '999999999999', '10/03', '6 Their Street', 'Victoria', 'BC', 'T4H 9G4', 1),
(7, 4, '2003-02-15 08:15:00', 'MC', '888888888888', '11/03', '77 Lucky Street', 'Edmonton', 'AB', 'R4A 0Z4', 2),
(8, 3, '2003-02-15 08:15:00', 'AMEX', '777777777777', '12/03', '888 Our Street', 'Regina', 'SK', 'S4S 7G4', 1),
(9, 2, '2003-02-15 08:15:00', 'MC', '666666666666', '01/04', '999 Your Street', 'Toronto', 'ON', 'G4D 9F4', 2),
(10, 1, '2003-02-15 08:15:00', 'VISA', '555555555555', '02/04', '99 Some Street', 'Calgary', 'AB', 'W4G 7A4', 1),
(11, NULL, '2003-02-15 08:15:00', 'VISA', '555555555555', '02/04', 'Null order', 'Calgary', 'ZZ', 'XXX YYY', 1);

-- --------------------------------------------------------

-- 
-- Table structure for table `Others`
-- 

DROP TABLE IF EXISTS `Others`;
CREATE TABLE `Others` (
  `Other_Int` int(11) default NULL,
  `Other_Long` bigint(20) default NULL,
  `Other_Bit` bit(1) NOT NULL default '\0',
  `Other_String` varchar(32) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table `Others`
-- 

INSERT INTO `Others` (`Other_Int`, `Other_Long`, `Other_Bit`, `Other_String`) VALUES (1, 8888888, '\0', 'Oui'),
(2, 9999999999, '', 'Non'),
(99, 1966, '', 'Non');

-- --------------------------------------------------------

-- 
-- Table structure for table `Users`
-- 

DROP TABLE IF EXISTS `Users`;
CREATE TABLE `Users` (
  `LogonId` varchar(20) NOT NULL default '0',
  `Name` varchar(40) default NULL,
  `Password` varchar(20) default NULL,
  `EmailAddress` varchar(40) default NULL,
  `LastLogon` datetime default NULL,
  PRIMARY KEY  (`LogonId`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table `Users`
-- 


-- 
-- Constraints for dumped tables
-- 

-- 
-- Constraints for table `A`
-- 
ALTER TABLE `A`
  ADD CONSTRAINT `FK_A_B` FOREIGN KEY (`B_ID`) REFERENCES `B` (`ID`),
  ADD CONSTRAINT `FK_A_E` FOREIGN KEY (`E_ID`) REFERENCES `E` (`ID`),
  ADD CONSTRAINT `FK_A_F` FOREIGN KEY (`F_ID`) REFERENCES `F` (`ID`);

-- 
-- Constraints for table `B`
-- 
ALTER TABLE `B`
  ADD CONSTRAINT `FK_B_C` FOREIGN KEY (`C_ID`) REFERENCES `C` (`ID`),
  ADD CONSTRAINT `FK_B_D` FOREIGN KEY (`D_ID`) REFERENCES `D` (`ID`);
