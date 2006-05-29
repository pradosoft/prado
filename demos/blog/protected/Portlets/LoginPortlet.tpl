<div class="portlet">

<h2 class="portlet-title">Login</h2>

<com:TPanel CssClass="portlet-content" DefaultButton="LoginButton">
Username
<com:TRequiredFieldValidator
	ControlToValidate="Username"
	ValidationGroup="login"
	Text="...is required"
	Display="Dynamic"/>
<br/>
<com:TTextBox ID="Username" />
<br/>

Password
<com:TCustomValidator
	ControlToValidate="Password"
	ValidationGroup="login"
	Text="...is invalid"
	Display="Dynamic"
	OnServerValidate="validateUser" />
<br/>
<com:TTextBox ID="Password" TextMode="Password" />

<br/>
<com:TLinkButton
	ID="LoginButton"
	Text="Login"
	ValidationGroup="login"
	OnClick="loginButtonClicked" />
| <a href="<%= $this->Service->constructUrl('Users.NewUser') %>">Register</a>

</com:TPanel><!-- end of portlet-content -->

</div><!-- end of portlet -->
