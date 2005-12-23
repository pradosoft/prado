<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" >

<com:THead Title="PRADO QuickStart Tutorial">
<meta http-equiv="content-language" content="en"/>
</com:THead>

<body>
<com:TForm>
<div id="header">
Prado QuickStart Tutorial
</div>

<div id="menu">
<a href="?">Home</a> |
<a href="http://www.pradosoft.com">PradoSoft.com</a> |
<com:TLinkButton Text="Hide TOC" Click="toggleTopicPanel" />
</div>

<com:Pages.TopicList ID="TopicPanel" />

<div id="content">
<com:TContentPlaceHolder ID="body" />
</div>

<div id="footer">
Copyright &copy; 2005 <a href="http://www.pradosoft.com">PradoSoft</a>.
</div>

</com:TForm>
</body>
</html>