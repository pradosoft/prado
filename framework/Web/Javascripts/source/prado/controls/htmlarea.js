/*! PRADO THtmlArea javascript file | github.com/pradosoft/prado */

/*
 *
 * HtmlArea (tinyMCE) wrapper
 *
 * @author Gabor Berczi <gabor.berczi@devworx.hu>
 *
*/

Prado.WebUI.THtmlArea = jQuery.klass(Prado.WebUI.Control,
{
	initialize: function($super, options)
	{
		options.ID = options.EditorOptions.elements;
		$super(options);
	},

    onInit : function(options)
	{
		this.options = options;
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
			if (wrapper = Prado.Registry[Prado.WebUI.THtmlArea.pendingRegistrations.pop()])
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
		jQuery(document).on('ajaxComplete', this.ajaxresponder.bind(this));
	},


	deRegisterAjaxHook: function()
	{
		jQuery(document).off('ajaxComplete', this.ajaxresponder.bind(this));
	},

	ajaxresponder: function(request)
	{
		this.checkInstance();
	},

	onDone: function()
	{
		// check for previous tinyMCE registration, and try to remove it gracefully first
		var prev = tinyMCE.get(this.ID);
		if (prev)
		try
		{
			tinyMCE.execCommand('mceFocus', false, this.ID);
			// when removed, tinyMCE restores its content to the textarea. If the textarea content has been
			// updated in this same callback, it will be overwritten with the old content. Workaround this.
			var curtext = jQuery('#'+this.ID).get(0).value;
			tinyMCE.execCommand('mceRemoveControl', false, this.ID);
			jQuery('#'+this.ID).get(0).value = curtext;
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

jQuery.extend(Prado.WebUI.THtmlArea,
{
	pendingRegistrations : [],
	tinyMCELoadState : 0
});


