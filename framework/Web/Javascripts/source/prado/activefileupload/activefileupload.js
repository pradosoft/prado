Prado.WebUI.TActiveFileUpload = Class.create(Prado.WebUI.Control,
{
	onInit : function(options)
	{
		this.options = options || {};
		Prado.WebUI.TActiveFileUpload.register(this);
		
		this.input = $(options.inputID);
		this.flag = $(options.flagID);
		this.form = $(options.formID);
		
		this.indicator = $(options.indicatorID);
		this.complete = $(options.completeID);
		this.error = $(options.errorID);
		
		// set up events
		if (options.autoPostBack){
			this.observe(this.input,"change",this.fileChanged.bind(this));
		}
	},
	
	fileChanged : function(){
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

         			Object.extend(callback, this.options);

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
       		if ((this.finishoptions.errorCode == 0) && (success)) {
       			this.complete.style.display = '';
       			this.input.value = '';
       		} else {
       			this.error.style.display = '';
       		}
	}

});

Object.extend(Prado.WebUI.TActiveFileUpload, 
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
