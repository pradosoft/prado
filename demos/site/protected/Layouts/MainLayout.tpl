<!DOCTYPE html>
<html>
<com:THead Title="PRADO PHP Framework" >
	<com:TMetaTag HttpEquiv="Content-Language" Content="en" />
	<com:TMetaTag HttpEquiv="Content-Type" Content="text/html; charset=utf-8" />
	<com:TMetaTag Name="Keywords" Content="PHP framework, PRADO" />
	<com:TMetaTag Name="Description" Content="PRADO is a component-based and event-driven framework for Web application development in PHP 5." />
	<com:TMetaTag Name="Author" Content="Qiang Xue, Fabio Bas" />
	<com:TMetaTag Name="Subject" Content="PHP framework, Web programming" />
	<com:TMetaTag Name="Language" Content="en" />
	<link rel="Shortcut Icon" href="<%=$this->Page->Theme->BaseUrl%>/favicon.ico">
</com:THead>
<body>
<com:TForm>
<div id="page">
	<div id="header">
		<div id="logo">
			<com:THyperLink
				NavigateUrl="<%= $this->Service->constructUrl($this->Service->DefaultPage) %>"
				Text="PRADO Component Framework for PHP 5"
				ImageUrl="<%=$this->Page->Theme->BaseUrl%>/imgs/pradoheader.gif"
			/>
		</div>
		<div id="mainmenu">
			<com:SimpleMenu ID="menu">
				<com:SimpleMenuItem Path="Home" Text="Home" />
				<com:SimpleMenuItem Path="About" Text="About" />
				<!---<com:SimpleMenuItem Path="Testimonials" Text="Testimonials" /> --->
				<com:SimpleMenuItem Path="Demos" Text="Demos" />
				<com:SimpleMenuItem Path="Download" Text="Download" />
				<com:SimpleMenuItem Path="Documentation" Text="Documentation" />
				<!--- <com:SimpleMenuItem Path="Forum" Text="Forum" /> --->
				<com:SimpleMenuItem Url="http://github.com/pradosoft/prado" Text="Development" Target="_blank"/>
				<!---  --->
			</com:SimpleMenu>
		</div>
	</div>
	<com:TContentPlaceHolder ID="Main" />
	<div id="footer">
		<com:THyperLink
			NavigateUrl="<%= $this->Service->constructUrl('Tos') %>"
			Text="Terms of Service"
		/> |
		<com:THyperLink
			NavigateUrl="<%= $this->Service->constructUrl('License') %>"
			Text="License"
		/>
		<br>
		Copyright © 2004-<%=date('Y')%> by the PRADO Group.<br>
		<br>
		<%= Prado::poweredByPrado(1); %>
	</div>
</div>
</com:TForm>
</body>
</html>