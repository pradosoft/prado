<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" >

<com:THead Title=<%$ SiteName %> >
<meta http-equiv="Expires" content="Fri, Jan 01 1900 00:00:00 GMT"/>
<meta http-equiv="Pragma" content="no-cache"/>
<meta http-equiv="Cache-Control" content="no-cache"/>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<meta http-equiv="content-language" content="en"/>
</com:THead>

<body>
<com:TForm>

<com:TContentPlaceHolder ID="Main" />

<div id="footer">
Copyright &copy; <%= date('Y') %> <%$ SiteOwner %>.<br/>
<%= Prado::poweredByPrado() %>

</com:TForm>
</body>
</html>