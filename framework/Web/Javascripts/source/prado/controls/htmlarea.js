
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
		options.ID = options.EditorOptions.elements;
		$super(options);
	},

    onInit : function(options)
	{
		this.options = options;

		var obj = this;
		this.ajaxresponder = {
			onComplete : function(request) 
			{
				if(request && (request instanceof Prado.AjaxRequest))
					obj.checkInstance();
			}
		};
		this.registerAjaxHook();

		this.registerInstance();
	},
	
	registerInstance: function()
	{
		if (typeof tinyMCE_GZ == 'undefined')
			{
				if (typeof tinyMCE == 'undefined')
					{
						if (typeof Prado.CallbackRequest != 'undefined')
							if (typeof Prado.CallbackRequest.transport != 'undefined')
							{
								// we're in a callback
								// try it again in some time, as tinyMCE is most likely still loading
								this.setTimeout(this.registerInstance.bind(this), 50); 
								return;
							}
						throw "TinyMCE libraries must be loaded first";
					}
				Prado.WebUI.THtmlArea.tinyMCELoadState = 255;
				this.initInstance();
			}
		else
			if (Prado.WebUI.THtmlArea.tinyMCELoadState==255)
				this.initInstance();
			else
				{
					Prado.WebUI.THtmlArea.pendingRegistrations.push(this.options.ID);
					if (Prado.WebUI.THtmlArea.tinyMCELoadState==0)
					{
						Prado.WebUI.THtmlArea.tinyMCELoadState = 1;
						tinyMCE_GZ.init(
							this.options.CompressionOptions,
							this.compressedScriptsLoaded.bind(this)
						);
					}
				}
	},
	
	compressedScriptsLoaded: function()
	{
		Prado.WebUI.THtmlArea.tinyMCELoadState = 255;
		var wrapper;
		while(Prado.WebUI.THtmlArea.pendingRegistrations.length>0)
			if (wrapper = Prado.Registry.get(Prado.WebUI.THtmlArea.pendingRegistrations.pop()))
				wrapper.initInstance();
	},

	initInstance: function()
	{
		tinyMCE.init(this.options.EditorOptions);
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

Object.extend(Prado.WebUI.THtmlArea, 
{
	pendingRegistrations : [],
	tinyMCELoadState : 0
});


