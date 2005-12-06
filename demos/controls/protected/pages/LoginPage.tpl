<%@ MasterClass="Pages.master.MasterPage" %>
<com:TContent id="body" >
<com:TForm>
<div>
<com:TPanel DefaultButton="defButton" GroupingText="Login" Width="270px" HorizontalAlign="center" ScrollBars="Both">
<com:TLabel Text="Username" AssociatedControlID="username"/>
<com:TTextBox ID="username" /><br/>
<com:TLabel Text="Password" AssociatedControlID="password"/>
<com:TTextBox ID="password" TextMode="Password" /><br/>
<com:TButton Text="Login" Click="login" />
<com:TLabel ID="error" />
<com:TButton ID="defButton" Text="Default Button" Click="defaultClicked" />
</com:TPanel>
</div>
</com:TForm>
</com:TContent>