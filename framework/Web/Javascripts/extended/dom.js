Object.extend(Element, {
	condClassName : function (element, className, cond)
	{
		(cond?Element.addClassName:Element.removeClassName)(element,className);
	}
});