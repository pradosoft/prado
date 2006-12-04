CREATE PROCEDURE dbo.[ps_SelectAccount]
@Account_ID  [int]
AS
select
	Account_ID as Id,
	Account_FirstName as FirstName,
	Account_LastName as LastName,
	Account_Email as EmailAddress
from Accounts
where Account_ID = @Account_ID