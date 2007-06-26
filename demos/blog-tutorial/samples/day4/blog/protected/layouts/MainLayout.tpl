<html>
<com:THead />
<body>
<com:TForm>

<div id="header">
<h1>My PRADO Blog</h1>
</div>

<div id="main">
<com:TContentPlaceHolder ID="Main" />
</div>

<div id="footer">

<a href="<%= $this->Service->DefaultPageUrl %>">Home</a>

<com:THyperLink Text="New Post"
	NavigateUrl="<%= $this->Service->constructUrl('posts.NewPost') %>"
	Visible="<%= !$this->User->IsGuest %>" />

<com:THyperLink Text="New User"
	NavigateUrl="<%= $this->Service->constructUrl('users.NewUser') %>"
	Visible="<%= $this->User->IsAdmin %>" />

<com:THyperLink Text="Login"
	NavigateUrl="<%= $this->Service->constructUrl('users.LoginUser') %>"
	Visible="<%= $this->User->IsGuest %>" />

<com:TLinkButton Text="Logout"
	OnClick="logoutButtonClicked"
	Visible="<%= !$this->User->IsGuest %>" />

<br/>
<%= PRADO::poweredByPrado() %>
</div>

</com:TForm>
</body>
</html>