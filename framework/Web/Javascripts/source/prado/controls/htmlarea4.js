/*! PRADO THtmlArea4 javascript file | github.com/pradosoft/prado */

/*
 *
 * HtmlArea (tinyMCE 4) wrapper
 *
 * @author Gabor Berczi <gabor.berczi@devworx.hu>
 *
*/

Prado.WebUI.THtmlArea4 = jQuery.klass(Prado.WebUI.Control,
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
		tinyMCE.init(this.options.EditorOptions);
	},

	removePreviousInstance: function()
	{
		for(var i=0;i<tinyMCE.editors.length;i++)
			if (tinyMCE.editors[i].id==this.ID)
			{
				tinyMCE.editors.splice(i,1); // ugly hack, but works
				i--;
			}
	},

	checkInstance: function()
	{
		if (!document.getElementById(this.ID))
			this.deinitialize();
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
		{
			tinyMCE.execCommand('mceFocus', false, this.ID);
			// when removed, tinyMCE restores its content to the textarea. If the textarea content has been
			// updated in this same callback, it will be overwritten with the old content. Workaround this.
			var curtext = jQuery('#'+this.ID).get(0).value;
			prev.remove();
			jQuery('#'+this.ID).get(0).value = curtext;
		}

		// doublecheck editor instance here and remove manually from tinyMCE-registry if neccessary
		this.removePreviousInstance();
		this.deRegisterAjaxHook();
	}
});
