/* Simple Accordion Script 
 * Requires Prototype and Script.aculo.us Libraries
 * By: Brian Crescimanno <brian.crescimanno@gmail.com>
 * http://briancrescimanno.com
 * Adapted to Prado & minor improvements: Gabor Berczi <gabor.berczi@devworx.hu>
 * This work is licensed under the Creative Commons Attribution-Share Alike 3.0
 * http://creativecommons.org/licenses/by-sa/3.0/us/
 */

Prado.WebUI.TAccordion = Class.create(Prado.WebUI.Control,
{
    	onInit : function(options)
	{
		this.accordion = $(options.ID);
		this.options = options;
		this.hiddenField = $(options.ID+'_1');

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
			var header = $(view+'_0');
			if(header)
			{
				this.observe(header, "click", this.elementClicked.bindEvent(this,view));
				if(this.hiddenField.value == i)
				{
					this.currentView = view;
					if($(this.currentView).getHeight() != this.maxHeight)
						$(this.currentView).setStyle({height: this.maxHeight+"px"});
				}
			}
			i++;
		}
	},

	checkMaxHeight: function()
	{
		for(var viewID in this.options.Views)
		{
			var view = $(viewID);
			if(view.getHeight() > this.maxHeight)
 				this.maxHeight = view.getHeight();
		}
	},

	elementClicked : function(event,viewID)
	{
		// dummy effect to force processing of click into the event queue
		// is not actually supposed to change the appearance of the accordion
		var obj = this;
        	new Effect.Opacity(
			this.element,
			{ 
				from: 1.0, to: 1.0, duration: 0.0, 
            			queue: {
		                	position: 'end',
			                scope: 'accordion'
			        },
				afterFinish: function() { obj.processElementClick(event, viewID); } 
			}
		);
	},

	processElementClick : function(event,viewID)
	{
		var i = 0;
		for(var index in this.options.Views)
		{
			if ($(index))
			{
				var header = $(index+'_0');
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
				$(this.currentView).setStyle({ height: this.maxHeight+"px" });
				$(this.currentView).show();
				$(this.oldView).hide();
				
				var oldHeader = $(this.oldView+'_0');
				var currentHeader = $(this.currentView+'_0');
				oldHeader.className=this.options.HeaderCssClass;
				currentHeader.className=this.options.ActiveHeaderCssClass;
			}
		}
	},

	animate: function() {
		var effects = new Array();
		var options = {
			sync: true,
            		queue: {
		                position: 'end',
		                scope: 'accordion'
		        },
			scaleFrom: 0,
			scaleContent: false,
			transition: Effect.Transitions.sinoidal,
			scaleMode: {
				originalHeight: this.maxHeight,
				originalWidth: this.accordion.getWidth()
			},
			scaleX: false,
			scaleY: true
		};

		effects.push(new Effect.Scale(this.currentView, 100, options));

		options = {
			sync: true,
            		queue: {
                		position: 'end',
		                scope: 'accordion'
		        },
			scaleContent: false,
			transition: Effect.Transitions.sinoidal,
			scaleX: false,
			scaleY: true
		};

		effects.push(new Effect.Scale(this.oldView, 0, options));

		var oldHeader = $(this.oldView+'_0');
		var currentHeader = $(this.currentView+'_0');

		new Effect.Parallel(effects, {
			duration: this.options.Duration,
			fps: 35,
			queue: {
				position: 'end',
				scope: 'accordion'
			},
			beforeStart: function() {
				$(this.currentView).setStyle({ height: "0px" });
				$(this.currentView).show();

				oldHeader.className=this.options.HeaderCssClass;
				currentHeader.className=this.options.ActiveHeaderCssClass;
			}.bind(this),
			afterFinish: function() {
				$(this.oldView).hide();
				$(this.currentView).setStyle({ height: this.maxHeight+"px" });
			}.bind(this)
		});
	}
});

