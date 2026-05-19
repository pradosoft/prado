<html>
<com:THead />
<body>

<com:TForm>

<h1><%= $this->Page->Title %></h1>

<com:TContentPlaceHolder ID="Main" />

<hr/>
<ul>
<li><a id="pageHome" href="<%=$this->Service->constructUrl('Home')%>">Home</a></li>
<li><a id="pageAdminHome" href="<%=$this->Service->constructUrl('admin.Home')%>">admin.Home</a></li>
<li><a id="pageAdminHome2" href="<%=$this->Service->constructUrl('admin.Home2')%>">admin.Home2</a></li>
<li><a id="pageAdminUsersHome" href="<%=$this->Service->constructUrl('admin.users.Home')%>">admin.users.Home</a></li>
<li><a id="pageAdminUsersHome2" href="<%=$this->Service->constructUrl('admin.users.Home2')%>">admin.users.Home2</a></li>
<li><a id="pageContentHome" href="<%=$this->Service->constructUrl('content.Home')%>">content.Home</a></li>
</ul>
<hr/>
<a href="<%=$this->Service->constructUrl('UserLogin')%>">Login</a> |
<com:TLinkButton ID="Logout" Text="Logout" OnClick="logout" /> (<%= $this->User->Name %>)
</com:TForm>

</body>
</html>