<com:TPanel CssClass="sitemap" Visible="true">
<ul class="level1">
	<li class="<com:TPlaceHolder ID="LogMenu" />">
		<a class="menuitem" href="?page=TimeTracker.LogTimeEntry">Log</a>
	</li>
	<com:TPlaceHolder Visible=<%= $this->User->isInRole('manager') %> >
	<li class="<com:TPlaceHolder ID="ReportMenu" />">
		<span class="menuitem">Reports</span>
		<ul class="level2">
			<li><a href="?page=TimeTracker.ReportProject">Project Reports</a></li>
			<li><a href="?page=TimeTracker.ReportResource">Resources Report</a></li>
		</ul>
	</li>
	<li class="<com:TPlaceHolder ID="ProjectMenu" />">
		<span class="menuitem">Projects</span>
		<ul class="level2">
			<li><a href="?page=TimeTracker.ProjectDetails">Create New Project</a></li>
			<li><a href="?page=TimeTracker.ProjectList">List Projects</a></li>
		</ul>
	</li>
	</com:TPlaceHolder>
	<com:TPlaceHolder Visible=<%= $this->User->isInRole('admin') %> >
	<li class="<com:TPlaceHolder ID="AdminMenu" />">
		<span class="menuitem">Adminstration</span>
		<ul class="level2">
			<li><a href="?page=TimeTracker.UserCreate">Create New User</a></li>
			<li><a href="?page=TimeTracker.UserList">List Users</a></li>
		</ul>
	</li>
	</com:TPlaceHolder>
</ul>
<com:TClientScript PradoScripts="prado">
	Event.OnLoad(function()
	{
		menuitems = $$(".menuitem");
		menuitems.each(function(el)
		{
			Event.observe(el, "mouseover", function(ev)
			{	
				menuitems.each(function(item)
				{
					Element.removeClassName(item.parentNode, "active");
				});
				Element.addClassName(Event.element(ev).parentNode, "active");
			});
		});
	});
</com:TClientScript>
</com:TPanel>