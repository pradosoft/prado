<div class="portlet">

<h2 class="portlet-title">
Categories
<com:THyperLink
	Text="[+]"
	Tooltip="Create a new category"
	NavigateUrl=<%= $this->Service->constructUrl('Posts.NewCategory') %>
	Visible=<%= $this->User->isInRole('admin') %> />
</h2>

<div class="portlet-content">
<ul>
<com:TRepeater ID="CategoryList" EnableViewState="false">
	<prop:ItemTemplate>
	<li>
	<a href="<%# $this->Service->constructUrl('Posts.ListPost',array('cat'=>$this->DataItem->ID)) %>"><%# $this->DataItem->Name . ' (' . $this->DataItem->PostCount . ')' %></a>
	</li>
	</prop:ItemTemplate>
</com:TRepeater>
</ul>
</div><!-- end of portlet-content -->

</div><!-- end of portlet -->
