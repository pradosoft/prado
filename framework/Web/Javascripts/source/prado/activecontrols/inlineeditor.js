/*! PRADO TInPlaceTextBox javascript file | github.com/pradosoft/prado */

Prado.WebUI.TInPlaceTextBox = Prado.Class(Prado.WebUI.Control,
{
	onInit(options) {

		this.isSaving = false;
		this.isEditing = false;
		this.editField = null;
		this.readOnly = options.ReadOnly;

		this.options = Object.assign(
		{
			LoadTextFromSource : false,
			TextMode : 'SingleLine'

		}, options || {});
		this.element = document.getElementById(this.options.ID);
		Prado.WebUI.TInPlaceTextBox.register(this);
		this.createEditorInput();
		this.initializeListeners();
	},

	/**
	 * Initialize the listeners.
	 */
	initializeListeners() {
		this.onclickListener = this.enterEditMode.bind(this);
		this.observe(this.element, 'click', this.onclickListener);
		if (this.options.ExternalControl)
			this.observe(document.getElementById(this.options.ExternalControl), 'click', this.onclickListener);
	},

	/**
	 * Changes the panel to an editable input.
	 * @param {Event} evt event source
	 */
	enterEditMode(evt) {
	    if (this.isSaving || this.isEditing || this.readOnly) return;
	    this.isEditing = true;
		this.onEnterEditMode();
		this.createEditorInput();
		this.showTextBox();
		this.editField.disabled = false;
		if(this.options.LoadTextOnEdit)
			this.loadExternalText();
		this.editField.focus();
		if (evt)
			evt.preventDefault();
    	return false;
	},

	exitEditMode(evt) {
		this.isEditing = false;
		this.isSaving = false;
		this.editField.disabled = false;
		this.element.innerHTML = this.editField.value;
		this.showLabel();
	},

	showTextBox() {
		this.element.style.display = 'none';
		this.editField.style.display = '';
	},

	showLabel() {
		this.element.style.display = '';
		this.editField.style.display = 'none';
	},

	/**
	 * Create the edit input field.
	 */
	createEditorInput() {
		if(this.editField == null)
			this.createTextBox();

		this.editField.value = this.getText();
	},

	loadExternalText() {
		this.editField.disabled = true;
		this.onLoadingText();
		const options = new Array('__InlineEditor_loadExternalText__', this.getText());
		const request = new Prado.CallbackRequest(this.options.EventTarget, this.options);
		request.setCausesValidation(false);
		request.setCallbackParameter(options);
		request.options.onSuccess = this.onloadExternalTextSuccess.bind(this);
		request.options.onFailure = this.onloadExternalTextFailure.bind(this);
		request.dispatch();
	},

	/**
	 * Create a new input textbox or textarea
	 */
	createTextBox() {
		const cssClass= this.element.className || '';
		const inputName = this.options.EventTarget;

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
			this.observe(this.editField, "keydown", e => {
				 if(e.keyCode == 13) //KEY_RETURN
		        {
					const target = e.target;
					if(target)
					{
						target.blur();
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
	getText() {
    	return this.element.innerHTML;
  	},

	/**
	 * Edit mode entered, calls optional event handlers.
	 */
	onEnterEditMode() {
		if(typeof(this.options.onEnterEditMode) == "function")
			this.options.onEnterEditMode(this,null);
	},

	onTextBoxBlur(e) {
		const text = this.element.innerHTML;
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

	onKeyPressed(e) {
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
	onTextChanged(text) {
		const request = new Prado.CallbackRequest(this.options.EventTarget, this.options);
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
	onLoadingText() {
		//Logger.info("on loading text");
	},

	onloadExternalTextSuccess(request, parameter) {
		this.isEditing = true;
		this.editField.disabled = false;
		this.editField.value = this.getText();
		this.editField.focus();
		if(typeof(this.options.onSuccess)=="function")
			this.options.onSuccess(request, parameter);
	},

	onloadExternalTextFailure(request, parameter) {
		this.isSaving = false;
		this.isEditing = false;
		this.showLabel();
		if(typeof(this.options.onFailure)=="function")
			this.options.onFailure(request, parameter);
	},

	/**
	 * Text change successfully.
	 * @param {Object} sender
	 * @param {Object} parameter
	 */
	onTextChangedSuccess(sender, parameter) {
		this.isSaving = false;
		this.isEditing = false;
		if(this.options.AutoHide)
			this.showLabel();
		this.element.innerHTML = parameter == null ? this.editField.value : parameter;
		this.editField.disabled = false;
		if(typeof(this.options.onSuccess)=="function")
			this.options.onSuccess(sender,parameter);
	},

	onTextChangedFailure(sender, parameter) {
		this.editField.disabled = false;
		this.isSaving = false;
		this.isEditing = false;
		if(typeof(this.options.onFailure)=="function")
			this.options.onFailure(sender,parameter);
	}
});


Object.assign(Prado.WebUI.TInPlaceTextBox,
{
	//class methods

	textboxes : {},

	register(obj) {
		Prado.WebUI.TInPlaceTextBox.textboxes[obj.options.TextBoxID] = obj;
	},

	setDisplayTextBox(id, value) {
		const textbox = Prado.WebUI.TInPlaceTextBox.textboxes[id];
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

	setReadOnly(id, value) {
		const textbox = Prado.WebUI.TInPlaceTextBox.textboxes[id];
		if (textbox)
		{
			textbox.readOnly=value;
		}
	}
});