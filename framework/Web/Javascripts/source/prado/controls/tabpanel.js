Prado.WebUI.TTabPanel = Class.create();
Prado.WebUI.TTabPanel.prototype =
{
	initialize : function(options)
	{
		this.element = $(options.ID);
		this.onInit(options);
	},

	onInit : function(options)
	{
		this.views = options.Views;
		this.hiddenField = $(options.ID+'_1');
		this.activeCssClass = options.ActiveCssClass;
		this.normalCssClass = options.NormalCssClass;
		var length = options.Views.length;
		for(var i = 0; i<length; i++)
		{
			var item = options.Views[i];
			var element = $(item+'_0');
			if (element)
			{
				Event.observe(element, "click", this.elementClicked.bindEvent(this,item));
			}
		}
	},

	elementClicked : function(event,viewID)
	{
		var length = this.views.length;
		for(var i = 0; i<length; i++)
		{
			var item = this.views[i];
			if ($(item))
			{
				if(item == viewID)
				{
					$(item+'_0').className=this.activeCssClass;
					$(item).show();
					this.hiddenField.value=i;
				}
				else
				{
					$(item+'_0').className=this.normalCssClass;
					$(item).hide();
				}
			}
		}
	}
};