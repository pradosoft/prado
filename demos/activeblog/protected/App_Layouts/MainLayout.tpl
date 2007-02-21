<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" >

<com:THead Title=<%$ SiteName %> >
</com:THead>

<body>
<com:TForm>
<div class="container">
	<div class="header"><h1>Header</h1></div>
	<div class="wrapper">	
		<div class="content">
			<com:TContentPlaceHolder ID="Main" />
		</div>
	</div>
	<div class="navigation">
		<p><strong>2) Navigation here.</strong> long long fill filler very fill column column silly filler very filler fill fill filler text fill very silly fill text filler silly silly filler fill very make fill column text column very very column fill fill very silly column silly silly fill fill long filler </p>
	</div>
	<div class="extra">
		<p><strong>3) More stuff here.</strong> very text make long column make filler fill make column column silly filler text silly column fill silly fill column text filler make text silly filler make filler very silly make text very very text make long filler very make column make silly column fill silly column long make silly filler column filler silly long long column fill silly column very </p>
	</div>
	<div class="footer">
		<p>Copyright &copy; <%= date('Y') %> <%$ SiteOwner %>.<br/><%= Prado::poweredByPrado() %></p>
	</div>
</div>
</com:TForm>
</body>
</html>