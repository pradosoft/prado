CREATE PROCEDURE dbo.[ps_InsertAccount]
@Account_ID  [int], 
@Account_FirstName [nvarchar] (40),
@Account_LastName [varchar] (32),
@Account_Email [varchar] (128),
@Account_Banner_Option  [varchar] (255),
@Account_Cart_Option [int]
AS
insert into Accounts  
			(Account_ID, Account_FirstName, Account_LastName, Account_Email, Account_Banner_Option, Account_Cart_Option) 
values 
			(@Account_ID, @Account_FirstName, @Account_LastName, @Account_Email, @Account_Banner_Option, @Account_Cart_Option)
