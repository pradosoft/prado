/*! PRADO TTabPanel javascript file | github.com/pradosoft/prado */

Prado.WebUI.TTabPanel = jQuery.klass(Prado.WebUI.Control,
{
	onInit : function(options)
	{
		this.views = options.Views;
		this.viewsvis = options.ViewsVis;
		this.hiddenField = jQuery("#"+options.ID+'_1').get(0);
		this.activeCssClass = options.ActiveCssClass;
		this.normalCssClass = options.NormalCssClass;
		var length = options.Views.length;
		for(var i = 0; i<length; i++)
		{
			var item = options.Views[i];
			var element = jQuery("#"+item+'_0').get(0);
			if (element && options.ViewsVis[i])
			{
				this.observe(element, "click", jQuery.proxy(this.elementClicked,this,item));
				if (options.AutoSwitch)
					this.observe(element, "mouseenter", jQuery.proxy(this.elementClicked,this,item));
			}

			if(element)
			{
				var view = jQuery("#"+options.Views[i]).get(0);
				if (view)
					if(this.hiddenField.value == i)
					{
						jQuery(element).addClass(this.activeCssClass).removeClass(this.normalCssClass);
						jQuery(view).show();
					} else {
						jQuery(element).addClass(this.normalCssClass).removeClass(this.activeCssClass);
						jQuery(view).hide();
					}
			}
		}
	},

	elementClicked : function(viewID, event)
	{
		var length = this.views.length;
		for(var i = 0; i<length; i++)
		{
			var item = this.views[i];
			if (jQuery("#"+item))
			{
				if(item == viewID)
				{
					jQuery("#"+item+'_0').removeClass(this.normalCssClass).addClass(this.activeCssClass);
					jQuery("#"+item).show();
					this.hiddenField.value=i;
				}
				else
				{
					jQuery("#"+item+'_0').removeClass(this.activeCssClass).addClass(this.normalCssClass);
					jQuery("#"+item).hide();
				}
			}
		}
	}
});
