<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" >

<com:THead Title="My Personal WebSite" />

<body>

<com:TForm>

<div class="header">
<h1>Your Name Here</h1>
<h2>My Personal Site</h2>
<div class="nav">
<a href="<%=$this->Service->constructUrl('Home') %>" >HOME</a> |
<a href="<%=$this->Service->constructUrl('Blog') %>" >BLOG</a> |
<a href="<%=$this->Service->constructUrl('Albums') %>" >ALBUMS</a> |
<a href="<%=$this->Service->constructUrl('Links') %>" >LINKS</a> |
<a href="<%=$this->Service->constructUrl('Settings') %>" >SETTINGS</a> |
<com:THyperLink
	NavigateUrl=<%=$this->Service->constructUrl('UserLogin') %>
	Text="LOGIN"
	Visible=<%= $this->User->IsGuest %>
	/>
<com:TLinkButton
	Text="LOGOUT"
	Visible=<%= !$this->User->IsGuest %>
	OnClick="logout"
	/>
</div>
</div>
<hr/>
<div class="main">
<com:TContentPlaceHolder ID="main" />
</div>
<hr/>
<div class="footer">
  Copyright &copy; 2006 Your Name here.<br/>
  Powered by <a href="http://www.pradosoft.com/">PRADO</a>.
</div>

</com:TForm>

</body>
</html>