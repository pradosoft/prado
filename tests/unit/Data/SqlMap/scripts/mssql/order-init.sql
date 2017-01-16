-- Creating Table

use [IBatisNet]

if exists (select * from dbo.sysobjects where id = object_id(N'[dbo].[Orders]') and OBJECTPROPERTY(id, N'IsUserTable') = 1)
BEGIN
	if exists (select * from dbo.sysobjects where id = object_id(N'[dbo].[FK_LineItems_Orders]') and OBJECTPROPERTY(id, N'IsForeignKey') = 1)
	ALTER TABLE [dbo].[LineItems] DROP CONSTRAINT FK_LineItems_Orders

	drop table [dbo].[Orders]
END

CREATE TABLE [dbo].[Orders] (
	[Order_ID] [int] NOT NULL ,
	[Account_ID] [int] NULL ,
	[Order_Date] [datetime] NULL ,
	[Order_CardType] [varchar] (32) NULL ,
	[Order_CardNumber] [varchar] (32)  NULL ,
	[Order_CardExpiry] [varchar] (32)  NULL ,
	[Order_Street] [varchar] (32)  NULL ,
	[Order_City] [varchar] (32)  NULL ,
	[Order_Province] [varchar] (32)  NULL ,
	[Order_PostalCode] [varchar] (32)  NULL ,
	[Order_FavouriteLineItem] [int] NULL 
) ON [PRIMARY]

ALTER TABLE [dbo].[Orders] WITH NOCHECK ADD 
	CONSTRAINT [PK_Orders] PRIMARY KEY  CLUSTERED 
	(
		[Order_ID]
	)  ON [PRIMARY] 


ALTER TABLE [dbo].[Orders] ADD 
	CONSTRAINT [FK_Orders_Accounts] FOREIGN KEY 
	(
		[Account_ID]
	) REFERENCES [dbo].[Accounts] (
		[Account_ID]
	)
-- Creating Test Data -- 2003-02-15 8:15:00/ 2003-02-15 8:15:00

INSERT INTO [dbo].[Orders] VALUES (1, 1, '2003-02-15 8:15:00', 'VISA', '999999999999', '05/03', '11 This Street', 'Victoria', 'BC', 'C4B 4F4',2);
INSERT INTO [dbo].[Orders] VALUES (2, 4, '2003-02-15 8:15:00', 'MC', '888888888888', '06/03', '222 That Street', 'Edmonton', 'AB', 'X4K 5Y4',1);
INSERT INTO [dbo].[Orders] VALUES (3, 3, '2003-02-15 8:15:00', 'AMEX', '777777777777', '07/03', '333 Other Street', 'Regina', 'SK', 'Z4U 6Y4',2);
INSERT INTO [dbo].[Orders] VALUES (4, 2, '2003-02-15 8:15:00', 'MC', '666666666666', '08/03', '444 His Street', 'Toronto', 'ON', 'K4U 3S4',1);
INSERT INTO [dbo].[Orders] VALUES (5, 5, '2003-02-15 8:15:00', 'VISA', '555555555555', '09/03', '555 Her Street', 'Calgary', 'AB', 'J4J 7S4',2);
INSERT INTO [dbo].[Orders] VALUES (6, 5, '2003-02-15 8:15:00', 'VISA', '999999999999', '10/03', '6 Their Street', 'Victoria', 'BC', 'T4H 9G4',1);
INSERT INTO [dbo].[Orders] VALUES (7, 4, '2003-02-15 8:15:00', 'MC', '888888888888', '11/03', '77 Lucky Street', 'Edmonton', 'AB', 'R4A 0Z4',2);
INSERT INTO [dbo].[Orders] VALUES (8, 3, '2003-02-15 8:15:00', 'AMEX', '777777777777', '12/03', '888 Our Street', 'Regina', 'SK', 'S4S 7G4',1);
INSERT INTO [dbo].[Orders] VALUES (9, 2, '2003-02-15 8:15:00', 'MC', '666666666666', '01/04', '999 Your Street', 'Toronto', 'ON', 'G4D 9F4',2);
INSERT INTO [dbo].[Orders] VALUES (10, 1, '2003-02-15 8:15:00', 'VISA', '555555555555', '02/04', '99 Some Street', 'Calgary', 'AB', 'W4G 7A4',1);
INSERT INTO [dbo].[Orders] VALUES (11, null, '2003-02-15 8:15:00', 'VISA', '555555555555', '02/04', 'Null order', 'Calgary', 'ZZ', 'XXX YYY',1);

