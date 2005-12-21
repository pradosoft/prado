<%@ MasterClass="Pages.master.MasterPage" Title="Main Page" %>
<com:TContent id="body" >
<com:TForm>
<div>
<com:THiddenField Value="test" />
<h1>Welcome! <%=$this->User->Name %></h1>

<com:TJavascriptLogger />

<com:TLiteral Text="<literal>" Encode="true"/>

<com:TTextBox
	ID="TextBox"
	Text="textbox"
	AutoPostBack="true"
	CausesValidation="false" />

<com:TLabel
	Text="test"
	AssociatedControlID="checkbox" /><br/>

<com:System.Web.UI.WebControls.TButton
	text="Toggle Button"
	ForeColor="red"
	Font.size="18"
	Click="testClick" /> (requires membership)<br/>

<com:TCheckBox
	Text="Checkbox"
	ID="checkbox"
	AutoPostBack="true" /><br/>

<com:TRadioButton
	Text="Radiobutton"
	ID="radiobutton"
	AutoPostBack="true" /><br/>

<com:TImage
	ImageUrl=<%~/fungii_logo.gif %> />

<com:TImageButton
	ImageUrl=<%~/fungii_logo.gif %>
	Click="clickImage"/><br/>

<com:THyperLink
	Text="Visit a 'classless' page"
	NavigateUrl="?sp=page.plain" /> |

<com:THyperLink
	Text="Visit member only page"
	NavigateUrl="?sp=page.private.member" />

<com:TLinkButton
	Text="Click Me"
	Click="linkClicked"
	onclick="javascript:alert('you hit me')"/>


<com:TListBox SelectionMode="Single" SelectedIndexChanged="testClick" AutoPostBack="true">
	<com:TListItem Text="a" Selected="true" />
	<com:TListItem Text=<%$ adminEmail %> />
	<com:TListItem Text="c" />
	<com:TListItem Text="d" Selected="true" />
</com:TListBox>

<com:TDropDownList>
	<com:TListItem Text="a" />
	<com:TListItem Text="b" />
	<com:TListItem Text="c" Selected="true" />
	<com:TListItem Text="d" />
</com:TDropDownList>

<%# $this->Page->TextBox->Text %>
</div>
</com:TForm>
</com:TContent>