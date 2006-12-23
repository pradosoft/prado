
/**
 * Prado client-side javascript validation fascade.
 *
 * There are 4 basic classes, Validation, ValidationManager, ValidationSummary
 * and TBaseValidator, that interact together to perform validation.
 * The <tt>Prado.Validation</tt> class co-ordinates together the
 * validation scheme and is responsible for maintaining references
 * to ValidationManagers.
 *
 * The ValidationManager class is responsible for maintaining refereneces
 * to individual validators, validation summaries and their associated
 * groupings.
 *
 * The ValidationSummary take cares of display the validator error messages
 * as html output or an alert output.
 *
 * The TBaseValidator is the base class for all validators and contains
 * methods to interact with the actual inputs, data type conversion.
 *
 * An instance of ValidationManager must be instantiated first for a
 * particular form before instantiating validators and summaries.
 *
 * Usage example: adding a required field to a text box input with
 * ID "input1" in a form with ID "form1".
 * <code>
 * <script type="text/javascript" src="../prado.js"></script>
 * <script type="text/javascript" src="../validator.js"></script>
 * <form id="form1" action="...">
 * <div>
 * 	<input type="text" id="input1" />
 *  <span id="validator1" style="display:none; color:red">*</span>
 *  <input type="submit text="submit" />
 * <script type="text/javascript">
 * new Prado.ValidationManager({FormID : 'form1'});
 * var options =
 * {
 *		ID :				'validator1',
 *		FormID :			'form1',
 *		ErrorMessage :		'*',
 *		ControlToValidate : 'input1'
 *	}
 * new Prado.WebUI.TRequiredFieldValidator(options);
 * new Prado.WebUI.TValidationSummary({ID:'summary1',FormID:'form1'});
 *
 * //watch the form onsubmit event, check validators, stop if not valid.
 * Event.observe("form1", "submit" function(ev)
 * {
 * 	 if(Prado.WebUI.Validation.isValid("form1") == false)
 * 		Event.stop(ev);
 * });
 * </script>
 * </div>
 * </form>
 * </code>
 */
Prado.Validation =  Class.create();

/**
 * A global validation manager.
 * To validate the inputs of a particular form, call
 * <code>Prado.Validation.validate(formID, groupID)</code>
 * where <tt>formID</tt> is the HTML form ID, and the optional
 * <tt>groupID</tt> if present will only validate the validators
 * in a particular group.
 */
Object.extend(Prado.Validation,
{
	managers : {},

	/**
	 * Validate the validators (those that <strong>DO NOT</strong>
	 * belong to a particular group) the form specified by the
	 * <tt>formID</tt> parameter. If <tt>groupID</tt> is specified
	 * then only validators belonging to that group will be validated.
	 * @param string ID of the form to validate
	 * @param string ID of the group to validate.
	 * @param HTMLElement element that calls for validation
	 */
	validate : function(formID, groupID, invoker)
	{
		if(this.managers[formID])
		{
			return this.managers[formID].validate(groupID, invoker);
		}
		else
		{
			throw new Error("Form '"+form+"' is not registered with Prado.Validation");
		}
	},

	/**
	 * @return string first form ID.
	 */
	getForm : function()
	{
		var keys = $H(this.managers).keys();
		return keys[0];
	},

	/**
	 * Check if the validators are valid for a particular form (and group).
	 * The validators states will not be changed.
	 * The <tt>validate</tt> function should be called first.
	 * @param string ID of the form to validate
	 * @param string ID of the group to validate.
	 */
	isValid : function(formID, groupID)
	{
		if(this.managers[formID])
			return this.managers[formID].isValid(groupID);
		return true;
	},

	/**
	 * Add a new validator to a particular form.
	 * @param string the form that the validator belongs.
	 * @param object a validator
	 * @return object the manager
	 */
	addValidator : function(formID, validator)
	{
		if(this.managers[formID])
			this.managers[formID].addValidator(validator);
		else
			throw new Error("A validation manager for form '"+formID+"' needs to be created first.");
		return this.managers[formID];
	},

	/**
	 * Add a new validation summary.
	 * @param string the form that the validation summary belongs.
	 * @param object a validation summary
	 * @return object manager
	 */
	addSummary : function(formID, validator)
	{
		if(this.managers[formID])
			this.managers[formID].addSummary(validator);
		else
			throw new Error("A validation manager for form '"+formID+"' needs to be created first.");
		return this.managers[formID];
	},

	setErrorMessage : function(validatorID, message)
	{
		$H(Prado.Validation.managers).each(function(manager)
		{
			manager[1].validators.each(function(validator)
			{
				if(validator.options.ID == validatorID)
				{
					validator.options.ErrorMessage = message;
					$(validatorID).innerHTML = message;
				}
			});
		});
	}
});

Prado.ValidationManager = Class.create();
/**
 * Validation manager instances. Manages validators for a particular
 * HTML form. The manager contains references to all the validators
 * summaries, and their groupings for a particular form.
 * Generally, <tt>Prado.Validation</tt> methods should be called rather
 * than calling directly the ValidationManager.
 */
Prado.ValidationManager.prototype =
{
	/**
	 * <code>
	 * options['FormID']*	The ID of HTML form to manage.
	 * </code>
	 */
	initialize : function(options)
	{
		this.validators = []; // list of validators
		this.summaries = []; // validation summaries
		this.groups = []; // validation groups
		this.options = {};

		this.options = options;
		Prado.Validation.managers[options.FormID] = this;
	},

	/**
	 * Validate the validators managed by this validation manager.
	 * @param string only validate validators belonging to a group (optional)
	 * @param HTMLElement element that calls for validation
	 * @return boolean true if all validators are valid, false otherwise.
	 */
	validate : function(group, invoker)
	{
		if(group)
			return this._validateGroup(group, invoker);
		else
			return this._validateNonGroup(invoker);
	},

	/**
	 * Validate a particular group of validators.
	 * @param string ID of the form
	 * @param HTMLElement element that calls for validation
	 * @return boolean false if group is not valid, true otherwise.
	 */
	_validateGroup: function(groupID, invoker)
	{
		var valid = true;
		if(this.groups.include(groupID))
		{
			this.validators.each(function(validator)
			{
				if(validator.group == groupID)
					valid = valid & validator.validate(invoker);
				else
					validator.hide();
			});
		}
		this.updateSummary(groupID, true);
		return valid;
	},

	/**
	 * Validate validators that doesn't belong to any group.
	 * @return boolean false if not valid, true otherwise.
	 * @param HTMLElement element that calls for validation
	 */
	_validateNonGroup : function(invoker)
	{
		var valid = true;
		this.validators.each(function(validator)
		{
			if(!validator.group)
				valid = valid & validator.validate(invoker);
			else
				validator.hide();
		});
		this.updateSummary(null, true);
		return valid;
	},

	/**
	 * Gets the state of all the validators, true if they are all valid.
	 * @return boolean true if the validators are valid.
	 */
	isValid : function(group)
	{
		if(group)
			return this._isValidGroup(group);
		else
			return this._isValidNonGroup();
	},

	/**
	 * @return boolean true if all validators not belonging to a group are valid.
	 */
	_isValidNonGroup : function()
	{
		var valid = true;
		this.validators.each(function(validator)
		{
			if(!validator.group)
				valid = valid & validator.isValid;
		});
		return valid;
	},

	/**
	 * @return boolean true if all validators belonging to the group are valid.
	 */
	_isValidGroup : function(groupID)
	{
		var valid = true;
		if(this.groups.include(groupID))
		{
			this.validators.each(function(validator)
			{
				if(validator.group == groupID)
					valid = valid & validator.isValid;
			});
		}
		return valid;
	},

	/**
	 * Add a validator to this manager.
	 * @param Prado.WebUI.TBaseValidator a new validator
	 */
	addValidator : function(validator)
	{
		this.validators.push(validator);
		if(validator.group && !this.groups.include(validator.group))
			this.groups.push(validator.group);
	},

	/**
	 * Add a validation summary.
	 * @param Prado.WebUI.TValidationSummary validation summary.
	 */
	addSummary : function(summary)
	{
		this.summaries.push(summary);
	},

	/**
	 * Gets all validators that belong to a group or that the validator
	 * group is null and the validator validation was false.
	 * @return array list of validators with error.
	 */
	getValidatorsWithError : function(group)
	{
		var validators = this.validators.findAll(function(validator)
		{
			var notValid = !validator.isValid;
			var inGroup = group && validator.group == group;
			var noGroup = validator.group == null;
			return notValid && (inGroup || noGroup);
		});
		return validators;
	},

	/**
	 * Update the summary of a particular group.
	 * @param string validation group to update.
	 */
	updateSummary : function(group, refresh)
	{
		var validators = this.getValidatorsWithError(group);
		this.summaries.each(function(summary)
		{
			var inGroup = group && summary.group == group;
			var noGroup = !group && !summary.group;
			if(inGroup || noGroup)
				summary.updateSummary(validators, refresh);
			else
				summary.hideSummary(true);
		});
	}
};

/**
 * TValidationSummary displays a summary of validation errors inline on a Web page,
 * in a message box, or both. By default, a validation summary will collect
 * <tt>ErrorMessage</tt> of all failed validators on the page. If
 * <tt>ValidationGroup</tt> is not empty, only those validators who belong
 * to the group will show their error messages in the summary.
 *
 * The summary can be displayed as a list, as a bulleted list, or as a single
 * paragraph based on the <tt>DisplayMode</tt> option.
 * The messages shown can be prefixed with <tt>HeaderText</tt>.
 *
 * The summary can be displayed on the Web page and in a message box by setting
 * the <tt>ShowSummary</tt> and <tt>ShowMessageBox</tt>
 * options, respectively.
 */
Prado.WebUI.TValidationSummary = Class.create();
Prado.WebUI.TValidationSummary.prototype =
{
	/**
	 * <code>
	 * options['ID']*				Validation summary ID, i.e., an HTML element ID
	 * options['FormID']*			HTML form that this summary belongs.
	 * options['ShowMessageBox']	True to show the summary in an alert box.
	 * options['ShowSummary']		True to show the inline summary.
	 * options['HeaderText']		Summary header text
	 * options['DisplayMode']		Summary display style, 'BulletList', 'List', 'SingleParagraph'
	 * options['Refresh']			True to update the summary upon validator state change.
	 * options['ValidationGroup']	Validation summary group
	 * options['Display']			Display mode, 'None', 'Fixed', 'Dynamic'.
	 * options['ScrollToSummary']	True to scroll to the validation summary upon refresh.
	 * </code>
	 */
	initialize : function(options)
	{
		this.options = options;
		this.group = options.ValidationGroup;
		this.messages = $(options.ID);
		this.visible = this.messages.style.visibility != "hidden"
		this.visible = this.visible && this.messages.style.display != "none";
		Prado.Validation.addSummary(options.FormID, this);
	},

	/**
	 * Update the validation summary to show the error message from
	 * validators that failed validation.
	 * @param array list of validators that failed validation.
	 * @param boolean update the summary;
	 */
	updateSummary : function(validators, update)
	{
		if(validators.length <= 0)
		{
			if(update || this.options.Refresh != false)
			{
				return this.hideSummary(validators);
			}
			return;
		}

		var refresh = update || this.visible == false || this.options.Refresh != false;

		if(this.options.ShowSummary != false && refresh)
		{
			this.updateHTMLMessages(this.getMessages(validators));
			this.showSummary(validators);
		}

		if(this.options.ScrollToSummary != false && refresh)
			window.scrollTo(this.messages.offsetLeft-20, this.messages.offsetTop-20);

		if(this.options.ShowMessageBox == true && refresh)
		{
			this.alertMessages(this.getMessages(validators));
			this.visible = true;
		}
	},

	/**
	 * Display the validator error messages as inline HTML.
	 */
	updateHTMLMessages : function(messages)
	{
		while(this.messages.childNodes.length > 0)
			this.messages.removeChild(this.messages.lastChild);
		new Insertion.Bottom(this.messages, this.formatSummary(messages));
	},

	/**
	 * Display the validator error messages as an alert box.
	 */
	alertMessages : function(messages)
	{
		var text = this.formatMessageBox(messages);
		setTimeout(function(){ alert(text); },20);
	},

	/**
	 * @return array list of validator error messages.
	 */
	getMessages : function(validators)
	{
		var messages = [];
		validators.each(function(validator)
		{
			var message = validator.getErrorMessage();
			if(typeof(message) == 'string' && message.length > 0)
				messages.push(message);
		})
		return messages;
	},

	/**
	 * Hides the validation summary.
	 */
	hideSummary : function(validators)
	{	if(typeof(this.options.OnHideSummary) == "function")
		{
			this.messages.style.visibility="visible";
			this.options.OnHideSummary(this,validators)
		}
		else
		{
			this.messages.style.visibility="hidden";
			if(this.options.Display == "None" || this.options.Display == "Dynamic")
				this.messages.hide();
		}
		this.visible = false;
	},

	/**
	 * Shows the validation summary.
	 */
	showSummary : function(validators)
	{
		this.messages.style.visibility="visible";
		if(typeof(this.options.OnShowSummary) == "function")
			this.options.OnShowSummary(this,validators);
		else
			this.messages.show();
		this.visible = true;
	},

	/**
	 * Return the format parameters for the summary.
	 * @param string format type, "List", "SingleParagraph" or "BulletList"
	 * @type array formatting parameters
	 */
	formats : function(type)
	{
		switch(type)
		{
			case "List":
				return { header : "<br />", first : "", pre : "", post : "<br />", last : ""};
			case "SingleParagraph":
				return { header : " ", first : "", pre : "", post : " ", last : "<br />"};
			case "BulletList":
			default:
				return { header : "", first : "<ul>", pre : "<li>", post : "</li>", last : "</ul>"};
		}
	},

	/**
	 * Format the message summary.
	 * @param array list of error messages.
	 * @type string formatted message
	 */
	formatSummary : function(messages)
	{
		var format = this.formats(this.options.DisplayMode);
		var output = this.options.HeaderText ? this.options.HeaderText + format.header : "";
		output += format.first;
		messages.each(function(message)
		{
			output += message.length > 0 ? format.pre + message + format.post : "";
		});
//		for(var i = 0; i < messages.length; i++)
	//		output += (messages[i].length>0) ? format.pre + messages[i] + format.post : "";
		output += format.last;
		return output;
	},
	/**
	 * Format the message alert box.
	 * @param array a list of error messages.
	 * @type string format message for alert.
	 */
	formatMessageBox : function(messages)
	{
		var output = this.options.HeaderText ? this.options.HeaderText + "\n" : "";
		for(var i = 0; i < messages.length; i++)
		{
			switch(this.options.DisplayMode)
			{
				case "List":
					output += messages[i] + "\n";
					break;
				case "BulletList":
                default:
					output += "  - " + messages[i] + "\n";
					break;
				case "SingleParagraph":
					output += messages[i] + " ";
					break;
			}
		}
		return output;
	}
};

/**
 * TBaseValidator serves as the base class for validator controls.
 *
 * Validation is performed when a postback control, such as a TButton,
 * a TLinkButton or a TTextBox (under AutoPostBack mode) is submitting
 * the page and its <tt>CausesValidation</tt> option is true.
 * The input control to be validated is specified by <tt>ControlToValidate</tt>
 * option.
 */
Prado.WebUI.TBaseValidator = Class.create();
Prado.WebUI.TBaseValidator.prototype =
{
	/**
	 * <code>
	 * options['ID']*				Validator ID, e.g. span with message
	 * options['FormID']*			HTML form that the validator belongs
	 * options['ControlToValidate']*HTML form input to validate
	 * options['Display']			Display mode, 'None', 'Fixed', 'Dynamic'
	 * options['ErrorMessage']		Validation error message
	 * options['FocusOnError']		True to focus on validation error
	 * options['FocusElementID']	Element to focus on error
	 * options['ValidationGroup']	Validation group
	 * options['ControlCssClass']	Css class to use on the input upon error
	 * options['OnValidate']		Function to call immediately after validation
	 * options['OnValidationSuccess']			Function to call upon after successful validation
	 * options['OnValidationError']			Function to call upon after error in validation.
	 * options['ObserveChanges'] 	True to observe changes in input
	 * </code>
	 */
	initialize : function(options)
	{
	/*	options.OnValidate = options.OnValidate || Prototype.emptyFunction;
		options.OnSuccess = options.OnSuccess || Prototype.emptyFunction;
		options.OnError = options.OnError || Prototype.emptyFunction;
	*/

		this.enabled = true;
		this.visible = false;
		this.isValid = true;
		this._isObserving = {};
		this.group = null;
		this.requestDispatched = false;
		
		this.options = options;
		this.control = $(options.ControlToValidate);
		this.message = $(options.ID);
		this.group = options.ValidationGroup;

		this.manager = Prado.Validation.addValidator(options.FormID, this);
	},

	/**
	 * @return string validation error message.
	 */
	getErrorMessage : function()
	{
		return this.options.ErrorMessage;
	},

	/**
	 * Update the validator span, input CSS class, and focus particular
	 * element. Updating the validator control will set the validator
	 * <tt>visible</tt> property to true.
	 */
	updateControl: function(focus)
	{
		this.refreshControlAndMessage();

		if(this.options.FocusOnError && !this.isValid )
			Prado.Element.focus(this.options.FocusElementID);

		this.visible = true;
	},

	refreshControlAndMessage : function()
	{
		this.visible = true;
		if(this.message)
		{
			if(this.options.Display == "Dynamic")
				this.isValid ? this.message.hide() : this.message.show();
			this.message.style.visibility = this.isValid ? "hidden" : "visible";
		}
		if(this.control)
			this.updateControlCssClass(this.control, this.isValid);
	},

	/**
	 * Add a css class to the input control if validator is invalid,
	 * removes the css class if valid.
	 * @param object html control element
	 * @param boolean true to remove the css class, false to add.
	 */
	updateControlCssClass : function(control, valid)
	{
		var CssClass = this.options.ControlCssClass;
		if(typeof(CssClass) == "string" && CssClass.length > 0)
		{
			if(valid)
				control.removeClassName(CssClass);
			else
				control.addClassName(CssClass);
		}
	},

	/**
	 * Hides the validator messages and remove any validation changes.
	 */
	hide : function()
	{
		this.isValid = true;
		this.updateControl();
		this.visible = false;
	},

	/**
	 * Calls evaluateIsValid() function to set the value of isValid property.
	 * Triggers onValidate event and onSuccess or onError event.
	 * @param HTMLElement element that calls for validation
	 * @return boolean true if valid.
	 */
	validate : function(invoker)
	{
		//try to find the control.
		if(!this.control)
			this.control = $(this.options.ControlToValidate);

		if(!this.control)
		{
			this.isValid = true;
			return this.isValid;
		}

		if(typeof(this.options.OnValidate) == "function")
		{
			if(this.requestDispatched == false)
				this.options.OnValidate(this, invoker);
		}

		if(this.enabled)
			this.isValid = this.evaluateIsValid();
		else
			this.isValid = true;

		if(this.isValid)
		{
			if(typeof(this.options.OnValidationSuccess) == "function")
			{
				if(this.requestDispatched == false)
				{
					this.refreshControlAndMessage();
					this.options.OnValidationSuccess(this, invoker);
				}
			}
			else
				this.updateControl();
		}
		else
		{
			if(typeof(this.options.OnValidationError) == "function")
			{
				if(this.requestDispatched == false)
				{
					this.refreshControlAndMessage();
					this.options.OnValidationError(this, invoker)
				}
			}
			else
				this.updateControl();
		}

		this.observeChanges(this.control);

		return this.isValid;
	},

	/**
	 * Observe changes to the control input, re-validate upon change. If
	 * the validator is not visible, no updates are propagated.
	 * @param HTMLElement element that calls for validation
	 */
	observeChanges : function(control)
	{
		if(!control) return;

		var canObserveChanges = this.options.ObserveChanges != false;
		var currentlyObserving = this._isObserving[control.id+this.options.ID];

		if(canObserveChanges && !currentlyObserving)
		{
			var validator = this;

			Event.observe(control, 'change', function()
			{
				if(validator.visible)
				{
					validator.validate();
					validator.manager.updateSummary(validator.group);
				}
			});
			this._isObserving[control.id+this.options.ID] = true;
		}
	},

	/**
	 * @return string trims the string value, empty string if value is not string.
	 */
	trim : function(value)
	{
		return typeof(value) == "string" ? value.trim() : "";
	},

	/**
	 * Convert the value to a specific data type.
	 * @param {string} the data type, "Integer", "Double", "Date" or "String"
	 * @param {string} the value to convert.
	 * @type {mixed|null} the converted data value.
	 */
	convert : function(dataType, value)
	{
		if(typeof(value) == "undefined")
			value = this.getValidationValue();
		var string = new String(value);
		switch(dataType)
		{
			case "Integer":
				return string.toInteger();
			case "Double" :
			case "Float" :
				return string.toDouble(this.options.DecimalChar);
			case "Date":
				if(typeof(value) != "string")
					return value;
				else
				{
					var value = string.toDate(this.options.DateFormat);
					if(value && typeof(value.getTime) == "function")
						return value.getTime();
					else
						return null;
				}
			case "String":
				return string.toString();
		}
		return value;
	},

	/**
	 * The ControlType property comes from TBaseValidator::getClientControlClass()
	 * Be sure to update the TBaseValidator::$_clientClass if new cases are added.
	 * @return mixed control value to validate
	 */
	 getValidationValue : function(control)
	 {
	 	if(!control)
	 		control = this.control
	 	switch(this.options.ControlType)
	 	{
	 		case 'TDatePicker':
	 			if(control.type == "text")
	 			{
	 				value = this.trim($F(control));

					if(this.options.DateFormat)
	 				{
	 					date = value.toDate(this.options.DateFormat);
	 					return date == null ? value : date;
	 				}
	 				else
		 				return value;
	 			}
	 			else
	 			{
	 				this.observeDatePickerChanges();

	 				return Prado.WebUI.TDatePicker.getDropDownDate(control).getTime();
	 			}
	 		case 'THtmlArea':
	 			if(typeof tinyMCE != "undefined")
					tinyMCE.triggerSave();
				return this.trim($F(control));
			case 'TRadioButton':
				if(this.options.GroupName)
					return this.getRadioButtonGroupValue();
	 		default:
	 			if(this.isListControlType())
	 				return this.getFirstSelectedListValue();
	 			else
		 			return this.trim($F(control));
	 	}
	 },

	getRadioButtonGroupValue : function()
	{
		name = this.control.name;
		value = "";
		$A(document.getElementsByName(name)).each(function(el)
		{
			if(el.checked)
				value =  el.value;
		});
		return value;
	},

	 /**
	  * Observe changes in the drop down list date picker, IE only.
	  */
	 observeDatePickerChanges : function()
	 {
	 	if(Prado.Browser().ie)
	 	{
	 		var DatePicker = Prado.WebUI.TDatePicker;
	 		this.observeChanges(DatePicker.getDayListControl(this.control));
			this.observeChanges(DatePicker.getMonthListControl(this.control));
			this.observeChanges(DatePicker.getYearListControl(this.control));
	 	}
	 },

	/**
	 * Gets numeber selections and their values.
	 * @return object returns selected values in <tt>values</tt> property
	 * and number of selections in <tt>checks</tt> property.
	 */
	getSelectedValuesAndChecks : function(elements, initialValue)
	{
		var checked = 0;
		var values = [];
		var isSelected = this.isCheckBoxType(elements[0]) ? 'checked' : 'selected';
		elements.each(function(element)
		{
			if(element[isSelected] && element.value != initialValue)
			{
				checked++;
				values.push(element.value);
			}
		});
		return {'checks' : checked, 'values' : values};
	},

	/**
	 * Gets an array of the list control item input elements, for TCheckBoxList
	 * checkbox inputs are returned, for TListBox HTML option elements are returned.
	 * @return array list control option elements.
	 */
	getListElements : function()
	{
		switch(this.options.ControlType)
		{
			case 'TCheckBoxList': case 'TRadioButtonList':
				var elements = [];
				for(var i = 0; i < this.options.TotalItems; i++)
				{
					var element = $(this.options.ControlToValidate+"_c"+i);
					if(this.isCheckBoxType(element))
						elements.push(element);
				}
				return elements;
			case 'TListBox':
				var elements = [];
				var element = $(this.options.ControlToValidate);
				if(element && (type = element.type.toLowerCase()))
				{
					if(type == "select-one" || type == "select-multiple")
						elements = $A(element.options);
				}
				return elements;
			default:
				return [];
		}
	},

	/**
	 * @return boolean true if element is of checkbox or radio type.
	 */
	isCheckBoxType : function(element)
	{
		if(element && element.type)
		{
			var type = element.type.toLowerCase();
			return type == "checkbox" || type == "radio";
		}
		return false;
	},

	/**
	 * @return boolean true if control to validate is of some of the TListControl type.
	 */
	isListControlType : function()
	{
		var list = ['TCheckBoxList', 'TRadioButtonList', 'TListBox'];
		return list.include(this.options.ControlType);
	},

	/**
	 * @return string gets the first selected list value, initial value if none found.
	 */
	getFirstSelectedListValue : function()
	{
		var initial = "";
		if(typeof(this.options.InitialValue) != "undefined")
			initial = this.options.InitialValue;
		var elements = this.getListElements();
		var selection = this.getSelectedValuesAndChecks(elements, initial);
		return selection.values.length > 0 ? selection.values[0] : initial;
	}
}


/**
 * TRequiredFieldValidator makes the associated input control a required field.
 * The input control fails validation if its value does not change from
 * the <tt>InitialValue<tt> option upon losing focus.
 * <code>
 * options['InitialValue']		Validation fails if control input equals initial value.
 * </code>
 */
Prado.WebUI.TRequiredFieldValidator = Class.extend(Prado.WebUI.TBaseValidator,
{
	/**
	 * @return boolean true if the input value is not empty nor equal to the initial value.
	 */
	evaluateIsValid : function()
	{
		var inputType = this.control.getAttribute("type");
    	if(inputType == 'file')
    	{
        	return true;
    	}
	    else
	    {
        	var a = this.getValidationValue();
        	var b = this.trim(this.options.InitialValue);
        	return(a != b);
    	}
	}
});


/**
 * TCompareValidator compares the value entered by the user into an input
 * control with the value entered into another input control or a constant value.
 * To compare the associated input control with another input control,
 * set the <tt>ControlToCompare</tt> option to the ID path
 * of the control to compare with. To compare the associated input control with
 * a constant value, specify the constant value to compare with by setting the
 * <tt>ValueToCompare</tt> option.
 *
 * The <tt>DataType</tt> property is used to specify the data type
 * of both comparison values. Both values are automatically converted to this data
 * type before the comparison operation is performed. The following value types are supported:
 * - <b>Integer</b> A 32-bit signed integer data type.
 * - <b>Float</b> A double-precision floating point number data type.
 * - <b>Date</b> A date data type. The format can be by the <tt>DateFormat</tt> option.
 * - <b>String</b> A string data type.
 *
 * Use the <tt>Operator</tt> property to specify the type of comparison
 * to perform. Valid operators include Equal, NotEqual, GreaterThan, GreaterThanEqual,
 * LessThan and LessThanEqual.
 * <code>
 * options['ControlToCompare']
 * options['ValueToCompare']
 * options['Operator']
 * options['Type']
 * options['DateFormat']
 * </code>
 */
Prado.WebUI.TCompareValidator = Class.extend(Prado.WebUI.TBaseValidator,
{
	//_observingComparee : false,

	/**
	 * Compares the input to another input or a given value.
	 */
	evaluateIsValid : function()
	{
		var value = this.getValidationValue();
	    if (value.length <= 0)
	    	return true;

    	var comparee = $(this.options.ControlToCompare);

		if(comparee)
			var compareTo = this.getValidationValue(comparee);
		else
			var compareTo = this.options.ValueToCompare || "";

	    var isValid =  this.compare(value, compareTo);

		if(comparee)
		{
			this.updateControlCssClass(comparee, isValid);
			this.observeChanges(comparee);
		}
		return isValid;
	},

	/**
	 * Compares two values, their values are casted to type defined
	 * by <tt>DataType</tt> option. False is returned if the first
	 * operand converts to null. Returns true if the second operand
	 * converts to null. The comparision is done based on the
	 * <tt>Operator</tt> option.
	 */
	compare : function(operand1, operand2)
	{
		var op1, op2;
		if((op1 = this.convert(this.options.DataType, operand1)) == null)
			return false;
		if ((op2 = this.convert(this.options.DataType, operand2)) == null)
        	return true;
    	switch (this.options.Operator)
		{
	        case "NotEqual":
	            return (op1 != op2);
	        case "GreaterThan":
	            return (op1 > op2);
	        case "GreaterThanEqual":
	            return (op1 >= op2);
	        case "LessThan":
	            return (op1 < op2);
	        case "LessThanEqual":
	            return (op1 <= op2);
	        default:
	            return (op1 == op2);
	    }
	}
});

/**
 * TCustomValidator performs user-defined client-side validation on an
 * input component.
 *
 * To create a client-side validation function, add the client-side
 * validation javascript function to the page template.
 * The function should have the following signature:
 * <code>
 * <script type="text/javascript"><!--
 * function ValidationFunctionName(sender, parameter)
 * {
 *    // if(parameter == ...)
 *    //    return true;
 *    // else
 *    //    return false;
 * }
 * -->
 * </script>
 * </code>
 * Use the <tt>ClientValidationFunction</tt> option
 * to specify the name of the client-side validation script function associated
 * with the TCustomValidator.
 * <code>
 * options['ClientValidationFunction']	custom validation function.
 * </code>
 */
Prado.WebUI.TCustomValidator = Class.extend(Prado.WebUI.TBaseValidator,
{
	/**
	 * Calls custom validation function.
	 */
	evaluateIsValid : function()
	{
		var value = this.getValidationValue();
		var clientFunction = this.options.ClientValidationFunction;
		if(typeof(clientFunction) == "string" && clientFunction.length > 0)
		{
			validate = clientFunction.toFunction();
			return validate(this, value);
		}
		return true;
	}
});

/**
 * Uses callback request to perform validation.
 */
Prado.WebUI.TActiveCustomValidator = Class.extend(Prado.WebUI.TBaseValidator,
{
	validatingValue : null,

	/**
	 * Calls custom validation function.
	 */
	evaluateIsValid : function()
	{
		value = this.getValidationValue();
		if(!this.requestDispatched && value != this.validatingValue)
		{
			this.validatingValue = value;
			request = new Prado.CallbackRequest(this.options.EventTarget, this.options);
			request.setCallbackParameter(value);
			request.setCausesValidation(false);
			request.options.onSuccess = this.callbackOnSuccess.bind(this);
			request.options.onFailure = this.callbackOnFailure.bind(this);
			request.dispatch();
			this.requestDispatched = true;
			return false;
		}
		return this.isValid;
	},

	callbackOnSuccess : function(request, data)
	{
		this.isValid = data;
		this.requestDispatched = false;
		if(typeof(this.options.onSuccess) == "function")
			this.options.onSuccess(request,data);
		Prado.Validation.validate(this.options.FormID, this.group,null);
	},

	callbackOnFailure : function(request, data)
	{
		this.requestDispatched = false;
		if(typeof(this.options.onFailure) == "function")
			this.options.onFailure(request,data);
	}
});

/**
 * TRangeValidator tests whether an input value is within a specified range.
 *
 * TRangeValidator uses three key properties to perform its validation.
 * The <tt>MinValue</tt> and <tt>MaxValue</tt> options specify the minimum
 * and maximum values of the valid range. The <tt>DataType</tt> option is
 * used to specify the data type of the value and the minimum and maximum range values.
 * These values are converted to this data type before the validation
 * operation is performed. The following value types are supported:
 * - <b>Integer</b> A 32-bit signed integer data type.
 * - <b>Float</b> A double-precision floating point number data type.
 * - <b>Date</b> A date data type. The date format can be specified by
 *   setting <tt>DateFormat</tt> option, which must be recognizable
 *   by <tt>Date.SimpleParse</tt> javascript function.
 * - <b>String</b> A string data type.
 * <code>
 * options['MinValue']		Minimum range value
 * options['MaxValue']		Maximum range value
 * options['DataType']		Value data type
 * options['DateFormat']	Date format for date data type.
 * </code>
 */
Prado.WebUI.TRangeValidator = Class.extend(Prado.WebUI.TBaseValidator,
{
	/**
	 * Compares the input value with a minimum and/or maximum value.
	 * @return boolean true if the value is empty, returns false if conversion fails.
	 */
	evaluateIsValid : function()
	{
		var value = this.getValidationValue();
		if(value.length <= 0)
			return true;
		if(typeof(this.options.DataType) == "undefined")
			this.options.DataType = "String";

		if(this.options.DataType != "StringLength")
		{
			var min = this.convert(this.options.DataType, this.options.MinValue || null);
			var max = this.convert(this.options.DataType, this.options.MaxValue || null);
			value = this.convert(this.options.DataType, value);
		}
		else
		{
			var min = this.options.MinValue || 0;
			var max = this.options.MaxValue || Number.POSITIVE_INFINITY;
			value = value.length;
		}

		if(value == null)
			return false;

		var valid = true;

		if(min != null)
			valid = valid && value >= min;
		if(max != null)
			valid = valid && value <= max;
		return valid;
	}
});

/**
 * TRegularExpressionValidator validates whether the value of an associated
 * input component matches the pattern specified by a regular expression.
 * <code>
 * options['ValidationExpression']	regular expression to match against.
 * </code>
 */
Prado.WebUI.TRegularExpressionValidator = Class.extend(Prado.WebUI.TBaseValidator,
{
	/**
	 * Compare the control input against a regular expression.
	 */
	evaluateIsValid : function()
	{
		var value = this.getValidationValue();
	    if (value.length <= 0)
	    	return true;

	    var rx = new RegExp(this.options.ValidationExpression);
	    var matches = rx.exec(value);
	    return (matches != null && value == matches[0]);
	}
});

/**
 * TEmailAddressValidator validates whether the value of an associated
 * input component is a valid email address.
 */
Prado.WebUI.TEmailAddressValidator = Prado.WebUI.TRegularExpressionValidator;


/**
 * TListControlValidator checks the number of selection and their values
 * for a TListControl that allows multiple selections.
 */
Prado.WebUI.TListControlValidator = Class.extend(Prado.WebUI.TBaseValidator,
{
	/**
	 * @return true if the number of selections and/or their values
	 * match the requirements.
	 */
	evaluateIsValid : function()
	{
		var elements = this.getListElements();
		if(elements && elements.length <= 0)
			return true;

		this.observeListElements(elements);

		var selection = this.getSelectedValuesAndChecks(elements);
		return this.isValidList(selection.checks, selection.values);
	},

	/**
	 * Observe list elements for IE browsers of changes
	 */
	 observeListElements : function(elements)
	 {
		if(Prado.Browser().ie && this.isCheckBoxType(elements[0]))
		{
			var validator = this;
			elements.each(function(element)
			{
				validator.observeChanges(element);
			});
		}
	 },

	/**
	 * Determine if the number of checked and the checked values
	 * satisfy the required number of checks and/or the checked values
	 * equal to the required values.
	 * @return boolean true if checked values and number of checks are satisfied.
	 */
	isValidList : function(checked, values)
	{
		var exists = true;

		//check the required values
		var required = this.getRequiredValues();
		if(required.length > 0)
		{
			if(values.length < required.length)
				return false;
			required.each(function(requiredValue)
			{
				exists = exists && values.include(requiredValue);
			});
		}

		var min = typeof(this.options.Min) == "undefined" ?
					Number.NEGATIVE_INFINITY : this.options.Min;
		var max = typeof(this.options.Max) == "undefined" ?
					Number.POSITIVE_INFINITY : this.options.Max;
		return exists && checked >= min && checked <= max;
	},

	/**
	 * @return array list of required options that must be selected.
	 */
	getRequiredValues : function()
	{
		var required = [];
		if(this.options.Required && this.options.Required.length > 0)
			required = this.options.Required.split(/,\s*/);
		return required;
	}
});


/**
 * TDataTypeValidator verifies if the input data is of the type specified
 * by <tt>DataType</tt> option.
 * The following data types are supported:
 * - <b>Integer</b> A 32-bit signed integer data type.
 * - <b>Float</b> A double-precision floating point number data type.
 * - <b>Date</b> A date data type.
 * - <b>String</b> A string data type.
 * For <b>Date</b> type, the option <tt>DateFormat</tt>
 * will be used to determine how to parse the date string.
 */
Prado.WebUI.TDataTypeValidator = Class.extend(Prado.WebUI.TBaseValidator,
{
        evaluateIsValid : function()
        {
			value = this.getValidationValue();
			if(value.length <= 0)
				return true;
            return this.convert(this.options.DataType, value) != null;
        }
});



