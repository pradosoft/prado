Prado.WebUI.TTabPanel = Class.create();
Prado.WebUI.TTabPanel.prototype =
{
	initialize : function(options)
	{
		this.element = $(options.ID);
		this.onInit(options);
		Prado.Registry.set(options.ID, this);
	},

	onInit : function(options)
	{
		this.views = options.Views;
		this.hiddenField = $(options.ID+'_1');
		this.activeCssClass = options.ActiveCssClass;
		this.normalCssClass = options.NormalCssClass;
		var i = 0;
		for(var index in options.Views)
		{
			var element = $(index+'_0');
			if (options.Views[index])
			{
				Event.observe(element, "click", this.elementClicked.bindEvent(this,index));
			}
			if(element)
			{
				if(this.hiddenField.value == i)
				{
					element.className=this.activeCssClass;
					$(index).show();
				}
				else
				{
					element.className=this.normalCssClass;
					$(index).hide();
				}
			}
			i++;
		}
	},

	elementClicked : function(event,viewID)
	{
		var i = 0;
		for(var index in this.views)
		{
			if ($(index))
			{
				if(index == viewID)
				{
					$(index+'_0').className=this.activeCssClass;
					$(index).show();
					this.hiddenField.value=i;
				}
				else
				{
					$(index+'_0').className=this.normalCssClass;
					$(index).hide();
				}
			}
			i++;
		}
	}
};
