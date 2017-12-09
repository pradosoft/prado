/*! PRADO TInPlaceTextBox javascript file | github.com/pradosoft/prado */

Prado.WebUI.TInPlaceTextBox = jQuery.klass(Prado.WebUI.Control,
{
	onInit : function(options)
	{

		this.isSaving = false;
		this.isEditing = false;
		this.editField = null;
		this.readOnly = options.ReadOnly;

		this.options = jQuery.extend(
		{
			LoadTextFromSource : false,
			TextMode : 'SingleLine'

		}, options || {});
		this.element = jQuery('#'+this.options.ID).get(0);
		Prado.WebUI.TInPlaceTextBox.register(this);
		this.createEditorInput();
		this.initializeListeners();
	},

	/**
	 * Initialize the listeners.
	 */
	initializeListeners : function()
	{
		this.onclickListener = this.enterEditMode.bind(this);
		this.observe(this.element, 'click', this.onclickListener);
		if (this.options.ExternalControl)
			this.observe(jQuery('#'+this.options.ExternalControl).get(0), 'click', this.onclickListener);
	},

	/**
	 * Changes the panel to an editable input.
	 * @param {Event} evt event source
	 */
	enterEditMode :  function(evt)
	{
	    if (this.isSaving || this.isEditing || this.readOnly) return;
	    this.isEditing = true;
		this.onEnterEditMode();
		this.createEditorInput();
		this.showTextBox();
		this.editField.disabled = false;
		if(this.options.LoadTextOnEdit)
			this.loadExternalText();
		jQuery(this.editField).focus();
		if (evt)
			evt.preventDefault();
    	return false;
	},

	exitEditMode : function(evt)
	{
		this.isEditing = false;
		this.isSaving = false;
		this.editField.disabled = false;
		this.element.innerHTML = this.editField.value;
		this.showLabel();
	},

	showTextBox : function()
	{
		jQuery(this.element).hide();
		jQuery(this.editField).show();
	},

	showLabel : function()
	{
		jQuery(this.element).show();
		jQuery(this.editField).hide();
	},

	/**
	 * Create the edit input field.
	 */
	createEditorInput : function()
	{
		if(this.editField == null)
			this.createTextBox();

		this.editField.value = this.getText();
	},

	loadExternalText : function()
	{
		this.editField.disabled = true;
		this.onLoadingText();
		var options = new Array('__InlineEditor_loadExternalText__', this.getText());
		var request = new Prado.CallbackRequest(this.options.EventTarget, this.options);
		request.setCausesValidation(false);
		request.setCallbackParameter(options);
		request.options.onSuccess = this.onloadExternalTextSuccess.bind(this);
		request.options.onFailure = this.onloadExternalTextFailure.bind(this);
		request.dispatch();
	},

	/**
	 * Create a new input textbox or textarea
	 */
	createTextBox : function()
	{
		var cssClass= this.element.className || '';
		var inputName = this.options.EventTarget;

		if(this.options.TextMode == 'SingleLine')
		{
			this.editField = document.createElement("input");
			if(this.options.MaxLength > 0)
				this.editField.maxlength = this.options.MaxLength;
			if(this.options.Columns > 0)
				this.editField.size = this.options.Columns;
		}
		else
		{
			this.editField = document.createElement("textarea");
			if(this.options.Rows > 0)
				this.editField.rows = this.options.Rows;
			if(this.options.Columns > 0)
				this.editField.cols = this.options.Columns;
			if(this.options.Wrap)
				this.editField.wrap = 'off';
		}

		this.editField.className = cssClass;
		this.editField.name = inputName;
		this.editField.id = this.options.TextBoxID;
		this.editField.style.display="none";
		this.element.parentNode.insertBefore(this.editField, this.element)

		//handle return key within single line textbox
		if(this.options.TextMode == 'SingleLine')
		{
			this.observe(this.editField, "keydown", function(e)
			{
				 if(e.keyCode == 13) //KEY_RETURN
		        {
					var target = e.target;
					if(target)
					{
						jQuery(target).trigger("blur");
						e.preventDefault();
					}
				}
			});
		}

		this.observe(this.editField, "blur", this.onTextBoxBlur.bind(this));
		this.observe(this.editField, "keypress", this.onKeyPressed.bind(this));
	},

	/**
	 * @return {String} panel inner html text.
	 */
	getText: function()
	{
    	return this.element.innerHTML;
  	},

	/**
	 * Edit mode entered, calls optional event handlers.
	 */
	onEnterEditMode : function()
	{
		if(typeof(this.options.onEnterEditMode) == "function")
			this.options.onEnterEditMode(this,null);
	},

	onTextBoxBlur : function(e)
	{
		var text = this.element.innerHTML;
		if(this.options.AutoPostBack && text != this.editField.value)
		{
			if(this.isEditing)
				this.onTextChanged(text);
		}
		else
		{
			this.element.innerHTML = this.editField.value;
			this.isEditing = false;
			if(this.options.AutoHide)
				this.showLabel();
		}
	},

	onKeyPressed : function(e)
	{
		if (e.keyCode == 27) //KEY_ESC
		{
			this.editField.value = this.getText();
			this.isEditing = false;
			if(this.options.AutoHide)
				this.showLabel();
		}
		else if (e.keyCode == 13 // KEY_RETURN
			&& this.options.TextMode != 'MultiLine')
			e.preventDefault()
	},

	/**
	 * When the text input value has changed.
	 * @param {String} original text
	 */
	onTextChanged : function(text)
	{
		var request = new Prado.CallbackRequest(this.options.EventTarget, this.options);
		request.setCallbackParameter(text);
		request.options.onSuccess = this.onTextChangedSuccess.bind(this);
		request.options.onFailure = this.onTextChangedFailure.bind(this);
		if(request.dispatch())
		{
			this.isSaving = true;
			this.editField.disabled = true;
		}
	},

	/**
	 * When loading external text.
	 */
	onLoadingText : function()
	{
		//Logger.info("on loading text");
	},

	onloadExternalTextSuccess : function(request, parameter)
	{
		this.isEditing = true;
		this.editField.disabled = false;
		this.editField.value = this.getText();
		jQuery(this.editField).focus();
		if(typeof(this.options.onSuccess)=="function")
			this.options.onSuccess(sender,parameter);
	},

	onloadExternalTextFailure : function(request, parameter)
	{
		this.isSaving = false;
		this.isEditing = false;
		this.showLabel();
		if(typeof(this.options.onFailure)=="function")
			this.options.onFailure(sender,parameter);
	},

	/**
	 * Text change successfully.
	 * @param {Object} sender
	 * @param {Object} parameter
	 */
	onTextChangedSuccess : function(sender, parameter)
	{
		this.isSaving = false;
		this.isEditing = false;
		if(this.options.AutoHide)
			this.showLabel();
		this.element.innerHTML = parameter == null ? this.editField.value : parameter;
		this.editField.disabled = false;
		if(typeof(this.options.onSuccess)=="function")
			this.options.onSuccess(sender,parameter);
	},

	onTextChangedFailure : function(sender, parameter)
	{
		this.editField.disabled = false;
		this.isSaving = false;
		this.isEditing = false;
		if(typeof(this.options.onFailure)=="function")
			this.options.onFailure(sender,parameter);
	}
});


jQuery.extend(Prado.WebUI.TInPlaceTextBox,
{
	//class methods

	textboxes : {},

	register : function(obj)
	{
		Prado.WebUI.TInPlaceTextBox.textboxes[obj.options.TextBoxID] = obj;
	},

	setDisplayTextBox : function(id,value)
	{
		var textbox = Prado.WebUI.TInPlaceTextBox.textboxes[id];
		if(textbox)
		{
			if(value)
				textbox.enterEditMode(null);
			else
			{
				textbox.exitEditMode(null);
			}
		}
	},

	setReadOnly : function(id, value)
	{
		var textbox = Prado.WebUI.TInPlaceTextBox.textboxes[id];
		if (textbox)
		{
			textbox.readOnly=value;
		}
	}
});