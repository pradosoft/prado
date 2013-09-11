
/*
 * 
 * HtmlArea (tinyMCE 4) wrapper
 *
 * @author Gabor Berczi <gabor.berczi@devworx.hu>
 *
*/


Prado.WebUI.THtmlArea4 = Class.create(Prado.WebUI.Control,
{
	initialize: function($super, options)
	{
		options.ID = options.EditorOptions.elements;
		$super(options);
	},

    onInit : function(options)
	{
		this.options = options;
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
		//	var curtext = $(this.ID).html();
			tinyMCE.execCommand('mceRemoveControl', false, this.ID);
		//	$(this.ID).html(curtext);
		}
		catch (e) 
		{
			// suppress error here in case editor can't be properly removed
			// (happens when <textarea> has been removed from DOM tree without deinitialzing the tinyMCE editor first)
		}

		// doublecheck editor instance here and remove manually from tinyMCE-registry if neccessary
		this.removePreviousInstance();
	}
});
