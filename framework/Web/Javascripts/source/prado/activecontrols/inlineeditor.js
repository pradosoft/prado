/*! PRADO in-place editor javascript file | github.com/pradosoft/prado */

/**
 * Base class for in-place editor controls. An in-place control renders a
 * label element that swaps to an edit element when the label, or an optional
 * external control, is clicked. Subclasses supply the edit element through
 * createEditorInput() and the changed-value predicate that starts a save.
 * @since 4.4.0
 */
Prado.WebUI.TInPlaceControlBase = Prado.Class(Prado.WebUI.Control,
{
	onInit(options) {

		this.isSaving = false;
		this.isEditing = false;
		this.editField = null;
		this.readOnly = options.ReadOnly;

		this.options = Object.assign(this.getDefaultOptions(), options || {});
		this.element = document.getElementById(this.options.ID);
		Prado.WebUI.TInPlaceControlBase.register(this);
		this.createEditorInput();
		this.initializeListeners();
		if(this.options.DisplayEditor)
			this.enterEditMode(null, true);
	},

	/**
	 * @return {String} registry key of this control, the editor client ID.
	 */
	getRegistryKey() {
		return this.options.EditorID;
	},

	/**
	 * @return {Object} default options merged under the server options.
	 */
	getDefaultOptions() {
		return {};
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
	 * Changes the label to the edit element.
	 * @param {Event} evt event source
	 * @param {Boolean} noFocus true to leave the focus untouched
	 */
	enterEditMode(evt, noFocus) {
		if (this.isSaving || this.isEditing || this.readOnly) return;
		this.isEditing = true;
		this.onEnterEditMode();
		this.createEditorInput();
		this.showEditor();
		this.editField.disabled = false;
		this.loadOnEdit();
		if(!noFocus)
			this.editField.focus();
		if (evt)
			evt.preventDefault();
		return false;
	},

	exitEditMode(_evt) {
		this.isEditing = false;
		this.isSaving = false;
		this.editField.disabled = false;
		this.refreshLabel();
		this.showLabel();
	},

	showEditor() {
		this.element.style.display = 'none';
		this.editField.style.display = '';
	},

	showLabel() {
		this.element.style.display = '';
		this.editField.style.display = 'none';
	},

	/**
	 * Subclass hook: create or locate the edit element in this.editField.
	 */
	createEditorInput() {
	},

	/**
	 * Subclass hook: dispatch the load-on-edit callback when configured.
	 */
	loadOnEdit() {
	},

	/**
	 * Subclass hook: called after a save completes successfully.
	 */
	onAfterSave() {
	},

	/**
	 * @return {Boolean} whether the editor holds no value.
	 */
	isEditorEmpty() {
		return this.editField.value === '';
	},

	/**
	 * @return {String} the editor value as label inner html.
	 */
	getEditorDisplayText() {
		return Prado.htmlEncode(this.editField.value);
	},

	/**
	 * Writes the label content and records whether that content is the empty
	 * placeholder, so the placeholder is never read back as a value.
	 * @param {String} html label content
	 * @param {Boolean} isEmpty true when the content is options.EmptyDisplayText
	 */
	setLabelContent(html, isEmpty) {
		this.element.innerHTML = html;
		if(isEmpty)
			this.element.setAttribute(Prado.WebUI.TInPlaceControlBase.EMPTY_ATTRIBUTE, '1');
		else
			this.element.removeAttribute(Prado.WebUI.TInPlaceControlBase.EMPTY_ATTRIBUTE);
	},

	/**
	 * Sets the label from a value, showing options.EmptyDisplayText when the value
	 * is empty.
	 * @param {String} html label content for a value that is not empty
	 */
	setLabelValue(html) {
		const isEmpty = (html === '' || html === null || html === undefined);
		this.setLabelContent(isEmpty ? (this.options.EmptyDisplayText || '') : html, isEmpty);
	},

	/**
	 * Refreshes the label from the editor's current state.
	 */
	refreshLabel() {
		if(this.isEditorEmpty())
			this.setLabelContent(this.options.EmptyDisplayText || '', true);
		else
			this.setLabelContent(this.getEditorDisplayText(), false);
	},

	/**
	 * @return {Boolean} whether the label shows the empty placeholder.
	 */
	isShowingEmptyDisplayText() {
		return this.element.hasAttribute(Prado.WebUI.TInPlaceControlBase.EMPTY_ATTRIBUTE);
	},

	/**
	 * Changes the empty placeholder. A label already showing the old
	 * placeholder is refreshed to the new one.
	 * @param {String} value placeholder html
	 */
	setEmptyDisplayText(value) {
		this.options.EmptyDisplayText = value;
		if(this.isShowingEmptyDisplayText())
			this.setLabelContent(value || '', true);
	},

	/**
	 * @return {String} label inner html; the empty placeholder reads as an
	 *   empty string.
	 */
	getText() {
		return this.isShowingEmptyDisplayText() ? '' : this.element.innerHTML;
	},

	/**
	 * Posts the editor state through a callback request.
	 * @param {mixed} parameter callback parameter
	 */
	dispatchChange(parameter) {
		const request = new Prado.CallbackRequest(this.options.EventTarget, this.options);
		request.setCallbackParameter(parameter);
		request.options.onSuccess = (sender, param) => this.onChangeSuccess(sender, param);
		request.options.onFailure = (sender, param) => this.onChangeFailure(sender, param);
		// CallbackRequest.dispatch() returns false only when validation blocks
		// the request; on a dispatched request it returns undefined. Treat any
		// non-false result as dispatched so the save is marked in flight. The
		// field is not disabled here: the request serializes the form lazily
		// from the ajax queue, so disabling now would drop the field's value
		// from a POST that a queued request sends after this returns.
		if(request.dispatch() !== false)
		{
			this.isSaving = true;
		}
	},

	/**
	 * Save dispatch succeeded. Subclasses route their own named handler here.
	 * @param {Object} sender
	 * @param {Object} parameter
	 */
	onChangeSuccess(sender, parameter) {
		this.applySaveSuccess(sender, parameter);
	},

	/**
	 * Save dispatch failed. Subclasses route their own named handler here.
	 * @param {Object} sender
	 * @param {Object} parameter
	 */
	onChangeFailure(sender, parameter) {
		this.applySaveFailure(sender, parameter);
	},

	/**
	 * Applies a successful save: the label shows the new state and editing ends.
	 * @param {Object} sender
	 * @param {Object} parameter response text for the label, null to use the editor
	 */
	applySaveSuccess(sender, parameter) {
		this.isSaving = false;
		this.isEditing = false;
		if(this.options.AutoHide)
			this.showLabel();
		if(parameter == null)
			this.refreshLabel();
		else
			this.setLabelValue(parameter);
		this.editField.disabled = false;
		this.onAfterSave();
		if(typeof(this.options.onSuccess)=="function")
			this.options.onSuccess(sender,parameter);
	},

	/**
	 * Applies a failed save: editing ends and the editor is usable again.
	 * @param {Object} sender
	 * @param {Object} parameter
	 */
	applySaveFailure(sender, parameter) {
		this.editField.disabled = false;
		this.isSaving = false;
		this.isEditing = false;
		if(typeof(this.options.onFailure)=="function")
			this.options.onFailure(sender,parameter);
	},

	/**
	 * Edit mode entered, calls optional event handlers.
	 */
	onEnterEditMode() {
		if(typeof(this.options.onEnterEditMode) == "function")
			this.options.onEnterEditMode(this,null);
	}
});


Object.assign(Prado.WebUI.TInPlaceControlBase,
{
	//class methods

	// Marks a label element whose content is the empty placeholder.
	EMPTY_ATTRIBUTE : 'data-prado-empty',

	// Every in-place control, keyed by its editor client ID.
	instances : {},

	register(obj) {
		Prado.WebUI.TInPlaceControlBase.instances[obj.getRegistryKey()] = obj;
	},

	/**
	 * @param {String} id editor client ID
	 * @return {Object} the registered in-place control, undefined when unknown
	 */
	get(id) {
		return Prado.WebUI.TInPlaceControlBase.instances[id];
	},

	setDisplayEditor(id, value) {
		const control = Prado.WebUI.TInPlaceControlBase.get(id);
		if(control)
		{
			if(value)
				control.enterEditMode(null);
			else
				control.exitEditMode(null);
		}
	},

	setReadOnly(id, value) {
		const control = Prado.WebUI.TInPlaceControlBase.get(id);
		if(control)
			control.readOnly = value;
	},

	setLabelText(id, value) {
		const control = Prado.WebUI.TInPlaceControlBase.get(id);
		if(control)
			control.setLabelValue(value);
	},

	setEmptyDisplayText(id, value) {
		const control = Prado.WebUI.TInPlaceControlBase.get(id);
		if(control)
			control.setEmptyDisplayText(value);
	}
});


/**
 * Builds the backward compatible static block for an in-place control class:
 * the aliased view of the shared registry plus delegators to the base class
 * methods. Subclasses assign it and add only class-specific statics.
 * @param {String} alias legacy registry property name for the class
 * @return {Object} the static members to assign onto the class
 */
Prado.WebUI.TInPlaceControlBase.aliasStatics = function(alias) {
	const base = Prado.WebUI.TInPlaceControlBase;
	const statics = {
		register(obj) { base.register(obj); },
		setDisplayEditor(id, value) { base.setDisplayEditor(id, value); },
		setReadOnly(id, value) { base.setReadOnly(id, value); },
		setLabelText(id, value) { base.setLabelText(id, value); },
		setEmptyDisplayText(id, value) { base.setEmptyDisplayText(id, value); }
	};
	statics[alias] = base.instances;
	return statics;
};


Prado.WebUI.TInPlaceTextBox = Prado.Class(Prado.WebUI.TInPlaceControlBase,
{
	getDefaultOptions() {
		return {
			TextMode : 'SingleLine'
		};
	},

	/**
	 * Backward compatible alias of showEditor().
	 */
	showTextBox() {
		this.showEditor();
	},

	/**
	 * The label of this control holds the text unencoded.
	 * @return {String} the editor value as label inner html.
	 */
	getEditorDisplayText() {
		return this.editField.value;
	},

	/**
	 * Create the edit input field.
	 */
	createEditorInput() {
		if(this.editField == null)
			this.createTextBox();

		this.editField.value = this.getText();
	},

	loadOnEdit() {
		if(this.options.LoadTextOnEdit)
			this.loadExternalText();
	},

	loadExternalText() {
		this.editField.disabled = true;
		this.onLoadingText();
		const options = ['__InlineEditor_loadExternalText__', this.getText()];
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

		if(this.options.TextMode == 'MultiLine')
		{
			this.editField = document.createElement("textarea");
			if(this.options.Rows > 0)
				this.editField.rows = this.options.Rows;
			if(this.options.Columns > 0)
				this.editField.cols = this.options.Columns;
			if(!this.options.Wrap)
				this.editField.wrap = 'off';
		}
		else
		{
			this.editField = document.createElement("input");
			this.editField.type = Prado.WebUI.TInPlaceTextBox.INPUT_TYPES[this.options.TextMode] || 'text';
			if(this.options.MaxLength > 0)
				this.editField.maxlength = this.options.MaxLength;
			if(this.options.Columns > 0)
				this.editField.size = this.options.Columns;
		}

		this.editField.className = cssClass;
		this.editField.name = inputName;
		this.editField.id = this.options.TextBoxID;
		this.editField.style.display="none";
		this.element.parentNode.insertBefore(this.editField, this.element)

		//handle return key within single line inputs
		if(this.options.TextMode != 'MultiLine')
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

	onTextBoxBlur(_e) {
		const text = this.getText();
		if(this.options.AutoPostBack && text != this.editField.value)
		{
			if(this.isEditing && !this.isSaving)
				this.onTextChanged(text);
		}
		else
		{
			this.refreshLabel();
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
	 * @param {String} text the text before editing
	 */
	onTextChanged(text) {
		this.dispatchChange(text);
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

	onChangeSuccess(sender, parameter) {
		this.onTextChangedSuccess(sender, parameter);
	},

	onChangeFailure(sender, parameter) {
		this.onTextChangedFailure(sender, parameter);
	},

	/**
	 * Text change successfully.
	 * @param {Object} sender
	 * @param {Object} parameter
	 */
	onTextChangedSuccess(sender, parameter) {
		this.applySaveSuccess(sender, parameter);
	},

	/**
	 * Text change failed.
	 * @param {Object} sender
	 * @param {Object} parameter
	 */
	onTextChangedFailure(sender, parameter) {
		this.applySaveFailure(sender, parameter);
	}
});


Object.assign(Prado.WebUI.TInPlaceTextBox, Prado.WebUI.TInPlaceControlBase.aliasStatics('textboxes'),
{
	//class methods

	// TTextBoxMode value => input type attribute, matching TTextBox server rendering.
	INPUT_TYPES : {
		SingleLine: 'text',
		Password: 'password',
		Color: 'color',
		Date: 'date',
		Datetime: 'datetime',
		DatetimeLocal: 'datetime-local',
		Email: 'email',
		Month: 'month',
		Number: 'number',
		Range: 'range',
		Search: 'search',
		Tel: 'tel',
		Time: 'time',
		Url: 'url',
		Week: 'week'
	},

	// Backward compatible alias of setDisplayEditor.
	setDisplayTextBox(id, value) {
		Prado.WebUI.TInPlaceControlBase.setDisplayEditor(id, value);
	}
});


/**
 * In-place drop down list. The edit element is the server-rendered select
 * identified by options.EditorID; the label shows the selected option text.
 * @since 4.4.0
 */
Prado.WebUI.TInPlaceDropDownList = Prado.Class(Prado.WebUI.TInPlaceControlBase,
{
	createEditorInput() {
		if(this.editField == null)
			this.attachDropDown();
	},

	/**
	 * Locate the server-rendered select element and bind its listeners.
	 */
	attachDropDown() {
		this.editField = document.getElementById(this.options.EditorID);
		this.observe(this.editField, "change", this.onSelectionChanged.bind(this));
		this.observe(this.editField, "blur", this.onDropDownBlur.bind(this));
		this.observe(this.editField, "keydown", this.onKeyPressed.bind(this));
	},

	onEnterEditMode($super) {
		this.originalSelection = this.captureSelection();
		$super();
	},

	loadOnEdit() {
		if(this.options.LoadItemsOnEdit)
			this.loadItems();
	},

	loadItems() {
		this.editField.disabled = true;
		const options = ['__InlineEditor_loadItems__', this.getEditSelection()];
		const request = new Prado.CallbackRequest(this.options.EventTarget, this.options);
		request.setCausesValidation(false);
		request.setCallbackParameter(options);
		request.options.onSuccess = this.onLoadItemsSuccess.bind(this);
		request.options.onFailure = this.onLoadItemsFailure.bind(this);
		request.dispatch();
	},

	/**
	 * @return {mixed} the selection sent to the OnLoadingItems server handler.
	 */
	getEditSelection() {
		return this.editField.value;
	},

	/**
	 * @return {String} text of the selected option, empty when none is selected.
	 */
	getSelectedText() {
		const index = this.editField.selectedIndex;
		const option = index >= 0 ? this.editField.options[index] : null;
		return option ? option.text : '';
	},

	/**
	 * @return {mixed} snapshot of the current selection, for revert and dispatch.
	 */
	captureSelection() {
		return this.editField.value;
	},

	/**
	 * Restores a selection snapshot from captureSelection().
	 * @param {mixed} selection snapshot to restore
	 */
	applySelection(selection) {
		this.editField.value = selection;
	},

	isEditorEmpty() {
		return this.getSelectedText() === '';
	},

	getEditorDisplayText() {
		return Prado.htmlEncode(this.getSelectedText());
	},

	/**
	 * A changed selection posts the new value; this is the only save trigger,
	 * since a select fires its change event before losing focus.
	 * @param {Event} _event
	 */
	onSelectionChanged(_event) {
		if(this.options.AutoPostBack && this.isEditing && !this.isSaving)
			this.onValueChanged();
	},

	/**
	 * Losing focus leaves edit mode and shows the current selection.
	 * @param {Event} _e
	 */
	onDropDownBlur(_e) {
		if(this.isSaving || !this.isEditing)
			return;
		this.refreshLabel();
		this.isEditing = false;
		if(this.options.AutoHide)
			this.showLabel();
	},

	onKeyPressed(e) {
		if (e.keyCode == 27) //KEY_ESC
		{
			this.applySelection(this.originalSelection);
			this.refreshLabel();
			this.isEditing = false;
			if(this.options.AutoHide)
				this.showLabel();
		}
		else if (e.keyCode == 13) //KEY_RETURN
		{
			e.preventDefault();
			this.editField.blur();
		}
	},

	/**
	 * When the selection has changed, posts the selection through a callback.
	 */
	onValueChanged() {
		this.dispatchChange(this.originalSelection);
	},

	onAfterSave() {
		this.originalSelection = this.captureSelection();
	},

	onChangeSuccess(sender, parameter) {
		this.onValueChangedSuccess(sender, parameter);
	},

	onChangeFailure(sender, parameter) {
		this.onValueChangedFailure(sender, parameter);
	},

	/**
	 * Selection change successfully.
	 * @param {Object} sender
	 * @param {Object} parameter
	 */
	onValueChangedSuccess(sender, parameter) {
		this.applySaveSuccess(sender, parameter);
	},

	/**
	 * Selection change failed.
	 * @param {Object} sender
	 * @param {Object} parameter
	 */
	onValueChangedFailure(sender, parameter) {
		this.applySaveFailure(sender, parameter);
	},

	onLoadItemsSuccess(request, parameter) {
		this.isEditing = true;
		this.editField.disabled = false;
		this.originalSelection = this.captureSelection();
		this.editField.focus();
		if(typeof(this.options.onSuccess)=="function")
			this.options.onSuccess(request, parameter);
	},

	onLoadItemsFailure(request, parameter) {
		this.isSaving = false;
		this.isEditing = false;
		this.editField.disabled = false;
		this.showLabel();
		if(typeof(this.options.onFailure)=="function")
			this.options.onFailure(request, parameter);
	}
});


Object.assign(Prado.WebUI.TInPlaceDropDownList, Prado.WebUI.TInPlaceControlBase.aliasStatics('dropdowns'));


/**
 * In-place list box. The edit element is the server-rendered select
 * identified by options.EditorID, which may allow multiple selection. The
 * label shows the selected option texts joined by options.SelectionSeparator.
 * @since 4.4.0
 */
Prado.WebUI.TInPlaceListBox = Prado.Class(Prado.WebUI.TInPlaceDropDownList,
{
	/**
	 * Applies fn(option, index) to each selected option and returns the results.
	 * @param {Function} fn mapping applied to each selected option
	 * @return {Array} the mapped results
	 */
	mapSelectedOptions(fn) {
		const result = [];
		const options = this.editField.options;
		for(let i = 0; i < options.length; i++)
		{
			if(options[i].selected)
				result.push(fn(options[i], i));
		}
		return result;
	},

	/**
	 * @return {Array} values of the selected options, sent to OnLoadingItems so
	 *   a multiple selection is fully reported (not just the first value).
	 */
	getEditSelection() {
		return this.mapSelectedOptions(option => option.value);
	},

	/**
	 * @return {Array} texts of the selected options, empty texts skipped to
	 *   match the server label.
	 */
	getSelectedTexts() {
		return this.mapSelectedOptions(option => option.text).filter(text => text !== '');
	},

	/**
	 * @return {String} selected option texts joined by the separator. An empty
	 *   separator is honored, matching the server.
	 */
	getSelectedText() {
		const separator = this.options.SelectionSeparator ?? ', ';
		return this.getSelectedTexts().join(separator);
	},

	/**
	 * The snapshot is the set of selected indices, so restore is exact even
	 * when option values are duplicated.
	 * @return {Array} indices of the selected options.
	 */
	captureSelection() {
		return this.mapSelectedOptions((option, index) => index);
	},

	/**
	 * Restores a selection snapshot, selecting exactly the given indices.
	 * @param {Array} selection indices to select
	 */
	applySelection(selection) {
		const set = new Set(selection);
		const options = this.editField.options;
		for(let i = 0; i < options.length; i++)
			options[i].selected = set.has(i);
	},

	/**
	 * @return {Boolean} whether the selection differs from the edit-start snapshot.
	 */
	selectionChanged() {
		const now = this.captureSelection();
		const original = this.originalSelection || [];
		if(now.length !== original.length)
			return true;
		const set = new Set(original);
		return now.some(index => !set.has(index));
	},

	/**
	 * Multiple selection accumulates across clicks and commits on blur, so an
	 * individual toggle does not post and collapse the editor. A single
	 * selection list box commits on change like the drop down list.
	 * @param {Event} event change event
	 */
	onSelectionChanged($super, event) {
		if(this.editField.multiple)
			return;
		$super(event);
	},

	/**
	 * On blur a multiple selection list box commits the accumulated selection.
	 * A commit whose dispatch does not start (validation failed, or a request
	 * is already in flight) leaves the editor open with the selection intact,
	 * so the user can retry; the label is not touched.
	 * @param {Event} e blur event
	 */
	onDropDownBlur($super, e) {
		if(this.editField.multiple && this.isEditing && !this.isSaving
			&& this.options.AutoPostBack && this.selectionChanged())
		{
			this.onValueChanged();
			return;
		}
		$super(e);
	}
});


Object.assign(Prado.WebUI.TInPlaceListBox, Prado.WebUI.TInPlaceControlBase.aliasStatics('listboxes'));
