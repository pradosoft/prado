<div class="scaffold_view">
<com:TPanel ID="_panForSearch">
	<com:TScaffoldSearch ID="_search" ListViewID="_listView" />
</com:TPanel>
<com:TScaffoldListView ID="_listView" EditViewID="_editView" />
<com:TPanel ID="_panForNewButton" CssClass="auxilary-button buttons">
	<com:TButton ID="_newButton" Text="Add new record" CssClass="new-button" CommandName="new" />
</com:TPanel>

<com:TScaffoldEditView ID="_editView"  Visible="false"/>
</div>