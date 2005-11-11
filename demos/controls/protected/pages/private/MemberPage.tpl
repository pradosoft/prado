<com:TForm>
Congratulations, <com:TLabel Text=<%=$this->User->Name%> />!<br/>
<%=
 $this->Application->User->Name;
%>
You have reached this member-only area.
<com:TButton Text="Logout" Click="logout" />
</com:TForm>