<com:TForm>
<com:TPanel GroupingText="Login" Width="270px" HorizontalAlign="center">
<com:TLabel Text="Username" AssociatedControlID="username"/>
<com:TTextBox ID="username" /><br/>
<com:TLabel Text="Password" AssociatedControlID="password"/>
<com:TTextBox ID="password" TextMode="Password" /><br/>
<com:TButton Text="Login" Click="login" />
<com:TLabel ID="error" />
</com:TPanel>
</com:TForm>