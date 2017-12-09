/*! PRADO TAccordion javascript file | github.com/pradosoft/prado */

/* Based on:
 *
 * Simple Accordion Script
 * Requires Prototype and Script.aculo.us Libraries
 * By: Brian Crescimanno <brian.crescimanno@gmail.com>
 * http://briancrescimanno.com
 *
 * Adapted to Prado & minor improvements: Gabor Berczi <gabor.berczi@devworx.hu>
 * jQuery port by Bas Fabio <ctrlaltca@gmail.com>
 *
 * This work is licensed under the Creative Commons Attribution-Share Alike 3.0
 * http://creativecommons.org/licenses/by-sa/3.0/us/
 */

Prado.WebUI.TAccordion = jQuery.klass(Prado.WebUI.Control,
{
    	onInit : function(options)
	{
		this.accordion = jQuery('#'+options.ID).get(0);
		this.options = options;
		this.hiddenField = jQuery('#'+options.ID+'_1').get(0);

		if (this.options.maxHeight)
		{
			this.maxHeight = this.options.maxHeight;
		} else {
			this.maxHeight = 0;
			this.checkMaxHeight();
		}

		this.currentView = null;
		this.oldView = null;

		var i = 0;
		for(var view in this.options.Views)
		{
			var header = jQuery('#'+view+'_0').get(0);
			if(header)
			{
				this.observe(header, "click", jQuery.proxy(this.elementClicked,this,view));
				if(this.hiddenField.value == i)
				{
					this.currentView = view;
					if(jQuery('#'+this.currentView).height() != this.maxHeight)
						jQuery('#'+this.currentView).css({height: this.maxHeight+"px"});
				}
			}
			i++;
		}
	},

	checkMaxHeight: function()
	{
		for(var viewID in this.options.Views)
		{
			var view = jQuery('#'+viewID);
			if(view.height() > this.maxHeight)
 				this.maxHeight = view.height();
		}
	},

	elementClicked : function(viewID, event)
	{
		var i = 0;
		for(var index in this.options.Views)
		{
			if (jQuery('#'+index).get(0))
			{
				var header = jQuery('#'+index+'_0').get(0);
				if(index == viewID)
				{
					this.oldView = this.currentView;
					this.currentView = index;

					this.hiddenField.value=i;
				}
			}
			i++;
		}
		if(this.oldView != this.currentView)
		{
			if(this.options.Duration > 0)
			{
				this.animate();
			} else {
				jQuery('#'+this.currentView).css({ height: this.maxHeight+"px" });
				jQuery('#'+this.currentView).show();
				jQuery('#'+this.oldView).hide();

				jQuery('#'+this.oldView+'_0').removeClass().addClass(this.options.HeaderCssClass);
				jQuery('#'+this.currentView+'_0').removeClass().addClass(this.options.ActiveHeaderCssClass);
			}
		}
	},

	animate: function() {
		jQuery('#'+this.oldView+'_0').removeClass().addClass(this.options.HeaderCssClass);
		jQuery('#'+this.currentView+'_0').removeClass().addClass(this.options.ActiveHeaderCssClass);

		jQuery('#'+this.oldView).animate(
			{height: 0},
			this.options.Duration,
			function() {
				jQuery(this).hide()
			}
		);
		jQuery('#'+this.currentView).css({height: 0}).show().animate(
			{height: this.maxHeight+'px'},
			this.options.Duration
		);
	}
});

