
/*
 * 
 * HtmlArea (tinyMCE) wrapper
 *
 * @author Gabor Berczi <gabor.berczi@devworx.hu>
 *
*/


Prado.WebUI.THtmlArea = Class.create();
Prado.WebUI.THtmlArea.prototype =
{
	initialize : function(options)
	{
		this.onInit(options);
	},

    	onInit : function(options)
	{
		if (typeof(tinyMCE)=='undefined') 
			throw "TinyMCE libraries must be loaded first";

		this.options = options;
		this.id = options.elements;

		var p = Prado.Registry.get(this.id);
		if (p) p.deinitialize();

		tinyMCE.init(options);

		Prado.Registry.set(this.id, this);

		var obj = this;
		this.ajaxresponder = {
			onComplete : function(request) 
			{
				if(request && request instanceof Prado.AjaxRequest)
					obj.checkInstance();
			}
		};
		this.registerAjaxHook();
	},

	checkInstance: function()
	{
		if (!document.getElementById(this.id))
			this.deinitialize();
	},

	removePreviousInstance: function()
	{
		for(var i=0;i<tinyMCE.editors.length;i++)
			if (tinyMCE.editors[i].id==this.id)
			{
				tinyMCE.editors = tinyMCE.editors.slice(0,i-1).concat(tinyMCE.editors.slice(i+1)); // ugly hack, but works
				this.deRegisterAjaxHook();
				Prado.Registry.unset(this.id);
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

	deinitialize: function()
	{
		// check for previous tinyMCE registration, and try to remove it gracefully first
		var prev = tinyMCE.get(this.id);
		if (prev)
		try
		{
			tinyMCE.execCommand('mceFocus', false, this.id); 
			tinyMCE.execCommand('mceRemoveControl', false, this.id);
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
}

