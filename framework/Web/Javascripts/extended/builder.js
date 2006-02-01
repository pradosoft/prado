Object.extend(Builder,
{
	exportTags:function()
	{
		var tags=["BUTTON","TT","PRE","H1","H2","H3","BR","CANVAS","HR","LABEL","TEXTAREA","FORM","STRONG","SELECT","OPTION","OPTGROUP","LEGEND","FIELDSET","P","UL","OL","LI","TD","TR","THEAD","TBODY","TFOOT","TABLE","TH","INPUT","SPAN","A","DIV","IMG"];
		tags.each(function(tag)
		{
			window[tag]=function()
			{
				var args=$A(arguments);
				if(args.length==0)
					return Builder.node(tag,null);
				if(args.length==1)
					return Builder.node(tag,args[1]);
				if(args.length>1)
					return Builder.node(tag,args.shift(),args);

			};
		});
	}
});

Builder.exportTags();
