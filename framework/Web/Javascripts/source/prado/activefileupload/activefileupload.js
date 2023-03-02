/*! PRADO TActiveFileUpload javascript file | github.com/pradosoft/prado */

Prado.WebUI.TActiveFileUpload = jQuery.klass(Prado.WebUI.Control,
{
	onInit : function(options)
	{
		this.options = options || {};
		Prado.WebUI.TActiveFileUpload.register(this);

		this.input = jQuery('#'+options.inputID).get(0);
		this.flag = jQuery('#'+options.flagID).get(0);
		this.form = jQuery('#'+options.formID).get(0);

		this.indicator = jQuery('#'+options.indicatorID).get(0);
		this.complete = jQuery('#'+options.completeID).get(0);
		this.error = jQuery('#'+options.errorID).get(0);

		// set up events
		if (options.autoPostBack){
			this.observe(this.input,"change",this.fileChanged.bind(this));
		}
	},

	fileChanged : function(){
		// ie11 fix
		if(this.input.value=='') return;
		// show the upload indicator, and hide the complete and error indicators (if they areSn't already).
		this.flag.value = '1';
		this.complete.style.display = 'none';
		this.error.style.display = 'none';
		this.indicator.style.display = '';

		// set the form to submit in the iframe, submit it, and then reset it.
		this.oldtargetID = this.form.target;
		this.oldFormAction = this.form.action;
		this.oldFormMethod = this.form.method;
		this.oldFormEnctype = this.form.enctype;

		this.form.action += (this.form.action.indexOf('?')!=-1 ? '&' : '?')+'TActiveFileUpload_InputId='+this.options.inputID+'&TActiveFileUpload_TargetId='+this.options.targetID;
		this.form.target = this.options.targetID;
		this.form.method = 'POST';
		this.form.enctype = 'multipart/form-data';
		this.form.submit();

		this.form.action = this.oldFormAction;
		this.form.target = this.oldtargetID;
		this.form.method = this.oldFormMethod;
		this.form.enctype = this.oldFormEnctype;
	},

	finishUpload : function(options){

		if (this.options.targetID == options.targetID)
		{
			this.finishoptions = options;
			var e = this;
			var callback =
			{
				'CallbackParameter' : options || '',
				'onSuccess' : function() { e.finishCallBack(true); },
				'onFailure' : function() { e.finishCallBack(false); }
			};

			jQuery.extend(callback, this.options);

			var request = new Prado.CallbackRequest(this.options.EventTarget, callback);
			request.dispatch();
		}
		else
			this.finishCallBack(true);

	},

	finishCallBack : function(success){
		// hide the display indicator.
		this.flag.value = '';
		this.indicator.style.display = 'none';
			// show the complete indicator.
			if (/^[0\[\],]+$/.test(this.finishoptions.errorCode) && success) {
				this.complete.style.display = '';
				this.input.value = '';
			} else {
				this.error.style.display = '';
			}
	}

});

jQuery.extend(Prado.WebUI.TActiveFileUpload,
{
	//class methods

	controls : {},

	register : function(control)
	{
		Prado.WebUI.TActiveFileUpload.controls[control.options.ID] = control;
	},

	onFileUpload : function(options)
	{
		Prado.WebUI.TActiveFileUpload.controls[options.clientID].finishUpload(options);
	},

	fileChanged : function(controlID){
		Prado.WebUI.TActiveFileUpload.controls[controlID].fileChanged();
	}
});
