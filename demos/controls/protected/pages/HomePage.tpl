<com:TForm>
<h1>Welcome! <%=$this->User->Name %></h1>
<com:TLiteral Text="literal" />
<com:TTextBox Text="textbox" />
<com:TLabel Text="label" /><br/>
<com:System.Web.UI.WebControls.TButton text="Toggle Button" ForeColor="red" Font.size="18" Click="testClick" /> (requires membership)<br/>
<com:TCheckBox Text="Checkbox" /><br/>
<com:TImage ImageUrl=<%=$this->Page->getAsset('fungii_logo.gif') %> />
<com:TImageButton ImageUrl=<%=$this->Page->getAsset('fungii_logo.gif')%> /><br/>
<com:THyperLink Text="Visit a 'classless' page" NavigateUrl="?sp=page.plain" /> |
<com:THyperLink Text="Visit member only page" NavigateUrl="?sp=page.private.member" />
</com:TForm>