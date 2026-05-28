/*! PRADO THtmlArea5 javascript file | github.com/pradosoft/prado */

/*
 *
 * HtmlArea (tinyMCE 5) wrapper
 *
 * @author Gabor Berczi <gabor.berczi@devworx.hu>
 *
*/

Prado.WebUI.THtmlArea5 = Prado.Class(Prado.WebUI.Control,
{
	initialize($super, options) {
		$super(options);
	},

    onInit(options) {
		this.options = options;
		this.registerAjaxHook();
		tinyMCE.init(this.options.EditorOptions);
	},

	removePreviousInstance() {
		for(let i=0;i<tinyMCE.editors.length;i++)
			if (tinyMCE.editors[i].id==this.ID)
			{
				tinyMCE.editors.splice(i,1); // ugly hack, but works
				i--;
			}
	},

	checkInstance() {
		if (!document.getElementById(this.ID))
			this.deinitialize();
	},

	registerAjaxHook() {
		this._ajaxHandler = this.ajaxresponder.bind(this);
		document.addEventListener('prado:ajaxComplete', this._ajaxHandler);
	},


	deRegisterAjaxHook() {
		if (this._ajaxHandler) {
			document.removeEventListener('prado:ajaxComplete', this._ajaxHandler);
			this._ajaxHandler = null;
		}
	},

	ajaxresponder(request) {
		this.checkInstance();
	},

	onDone() {
		// check for previous tinyMCE registration, and try to remove it gracefully first
		const prev = tinyMCE.get(this.ID);
		if (prev)
		{
			tinyMCE.execCommand('mceFocus', false, this.ID);
			// when removed, tinyMCE restores its content to the textarea. If the textarea content has been
			// updated in this same callback, it will be overwritten with the old content. Workaround this.
			const ta = document.getElementById(this.ID);
			const curtext = ta.value;
			prev.remove();
			ta.value = curtext;
		}

		// doublecheck editor instance here and remove manually from tinyMCE-registry if neccessary
		this.removePreviousInstance();
		this.deRegisterAjaxHook();
	}
});
