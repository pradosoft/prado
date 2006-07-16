<com:TPanel CssClass="sitemap" Visible="true">
<ul class="level1">
	<li class="active"><a class="menuitem" href="?page=TimeTracker.TimeEntry">Log</a>
	</li>
	<li><span class="menuitem">Reports</span>
		<ul class="level2">
			<li><a href="?page=TimeTracker.ReportProject">Project Reports</a></li>
			<li><a href="?page=TimeTracker.ReportResource">Resources Report</a></li>
		</ul>
	</li>
	<li>
		<span class="menuitem">Projects</span>
		<ul class="level2">
			<li><a href="?page=TimeTracker.ProjectDetails">Create New Project</a></li>
			<li><a href="?page=TimeTracker.ProjectList">List Projects</a></li>
		</ul>
	</li>
	<li>
		<span class="menuitem">Adminstration</span>
		<ul class="level2">
			<li><a href="?page=TimeTracker.UserCreate">Create New User</a></li>
			<li><a href="?page=TimeTracker.UserList">List Users</a></li>
		</ul>
	</li>
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