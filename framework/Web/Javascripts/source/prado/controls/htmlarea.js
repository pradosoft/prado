
/*
 * 
 * HtmlArea (tinyMCE) wrapper
 *
 * @author Gabor Berczi <gabor.berczi@devworx.hu>
 *
*/


Prado.WebUI.THtmlArea = Class.create(Prado.WebUI.Control,
{
	initialize: function($super, options)
	{
		options.ID = options.elements;
		$super(options);
	},

    	onInit : function(options)
	{
		if (typeof(tinyMCE)=='undefined') 
			throw "TinyMCE libraries must be loaded first";

		this.options = options;

		tinyMCE.init(options);

		var obj = this;
		this.ajaxresponder = {
			onComplete : function(request) 
			{
				if(request && (request instanceof Prado.AjaxRequest))
					obj.checkInstance();
			}
		};
		this.registerAjaxHook();
	},

	checkInstance: function()
	{
		if (!document.getElementById(this.ID))
			this.deinitialize();
	},

	removePreviousInstance: function()
	{
		for(var i=0;i<tinyMCE.editors.length;i++)
			if (tinyMCE.editors[i].id==this.ID)
			{
				tinyMCE.editors.splice(i,1); // ugly hack, but works
				this.deRegisterAjaxHook();
				this.deregister();
				i--;
			}
	},

	registerAjaxHook: function()
	{
		if (typeof(Ajax)!="undefined")
			if (typeof(Ajax.Responders)!="undefined")
				Ajax.Responders.register(this.ajaxresponder);
	},


	deRegisterAjaxHook: function()
	{
		if (typeof(Ajax)!="undefined")
			if (typeof(Ajax.Responders)!="undefined")
				Ajax.Responders.unregister(this.ajaxresponder);
	},

	onDone: function()
	{
		// check for previous tinyMCE registration, and try to remove it gracefully first
		var prev = tinyMCE.get(this.ID);
		if (prev)
		try
		{
			tinyMCE.execCommand('mceFocus', false, this.ID); 
			tinyMCE.execCommand('mceRemoveControl', false, this.ID);
		}
		catch (e) 
		{
			// suppress error here in case editor can't be properly removed
			// (happens when <textarea> has been removed from DOM tree without deinitialzing the tinyMCE editor first)
		}

		// doublecheck editor instance here and remove manually from tinyMCE-registry if neccessary
		this.removePreviousInstance();

		this.deRegisterAjaxHook();
	}
});

