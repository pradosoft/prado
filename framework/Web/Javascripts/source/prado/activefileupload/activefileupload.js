Prado.WebUI.TActiveFileUpload = Base.extend(
{
	constructor : function(options)
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
		Event.observe(this.input,"change",this.fileChanged.bind(this));
	},
	
	fileChanged:function(){
		// show the upload indicator, and hide the complete and error indicators (if they areSn't already).
		this.flag.value = '1';
		this.complete.style.display = 'none';
		this.error.style.display = 'none';
		this.indicator.style.display = '';
		
		// set the form to submit in the iframe, submit it, and then reset it.
		this.oldtargetID = this.form.target;
		this.form.target = this.options.targetID;
		this.form.submit();
		this.form.target = this.oldtargetID;
	},
	
	finishUpload:function(options){
		// hide the display indicator.
		this.flag.value = '';
		this.indicator.style.display = 'none';
		if (this.options.targetID == options.targetID){
			// show the complete indicator.
			if (options.errorCode == 0){
				this.complete.style.display = '';
				this.input.value = '';
			} else {
				this.error.style.display = '';
			}
			Prado.Callback(this.options.EventTarget, options, null, this.options);
		}
	}
},
{
// class methods
	controls : {},

	register : function(control)
	{
		Prado.WebUI.TActiveFileUpload.controls[control.options.ID] = control;
	},
	
	onFileUpload: function(options)
	{
		Prado.WebUI.TActiveFileUpload.controls[options.clientID].finishUpload(options);
	}
});