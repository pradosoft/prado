CREATE   PROCEDURE dbo.[ps_swap_email_address]
@First_Email  [nvarchar] (64) output, 
@Second_Email [nvarchar] (64) output
AS

Declare @ID1 int
Declare @ID2 int

Declare @Email1  [nvarchar] (64)
Declare @Email2  [nvarchar] (64)

  SELECT @ID1 = Account_ID, @Email1 = Account_Email
  from Accounts
  where Account_Email = @First_Email

  SELECT @ID2 = Account_ID, @Email2 = Account_Email
  from Accounts
  where Account_Email = @Second_Email

  UPDATE Accounts
  set Account_Email = @Email2
  where Account_ID = @ID1

  UPDATE Accounts
  set Account_Email = @Email1
  where Account_ID = @ID2

  SELECT @First_Email = Account_Email
  from Accounts
  where Account_ID = @ID1

  SELECT @Second_Email = Account_Email
  from Accounts
  where Account_ID = @ID2
