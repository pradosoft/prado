
/**
 * Prado client-side javascript validation manager.
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
	 */
	validate : function(formID, groupID)
	{
		if(this.managers[formID])
		{
			return this.managers[formID].validate(groupID);
		}
		else
		{
			throw new Error("Form '"+form+"' is not registered with Prado.Validation");
		}
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
	 */
	addValidator : function(formID, validator)
	{
		if(this.managers[formID])
			this.managers[formID].addValidator(validator);
		else
			throw new Error("A validation manager for form '"+formID+"' needs to be created first.");
	},
	
	/**
	 * Add a new validation summary.
	 * @param string the form that the validation summary belongs.
	 * @param object a validation summary
	 */
	addSummary : function(formID, validator)
	{
		if(this.managers[formID])
			this.managers[formID].addSummary(validator);
		else
			throw new Error("A validation manager for form '"+formID+"' needs to be created first.");		
	}
});

/**
 * Validation manager instances. Manages validators for a particular 
 * HTML form.
 */
Prado.Validation.prototype =
{
	validators : [], // list of validators
	summaries : [], // validation summaries
	groups : [], // validation groups
	options : {},
	
	/**
	 * <code>
	 * options['FormID']*	The ID of HTML form to manage.
	 * </code>
	 */
	initialize : function(options)
	{
		this.options = options;
		Prado.Validation.managers[options.FormID] = this;
	},
	
	/**
	 * Validate the validators managed by this validation manager.
	 * @param string only validate validators belonging to a group (optional)
	 * @return boolean true if all validators are valid, false otherwise.
	 */
	validate : function(group)
	{
		if(group)
			return this._validateGroup(group);
		else
			return this._validateNonGroup();	
	},
	
	/**
	 * Validate a particular group of validators.
	 * @param string ID of the form 
	 * @return boolean false if group is not valid, true otherwise.
	 */
	_validateGroup: function(groupID)
	{
		var valid = true;
		var manager = this;
		if(this.groups.include(groupID))
		{
			this.validators.each(function(validator)
			{
				if(validator.group == groupID)
					valid = valid & validator.validate(manager);
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
	 */
	_validateNonGroup : function()
	{
		var valid = true;
		var manager = this;
		this.validators.each(function(validator)
		{
			if(!validator.group)
				valid = valid & validator.validate(manager);
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
	group : null,	
	options : {},
	visible : false,
	summary : null,
	
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
	 * options['Display']			Display mode, 'None', 'Static', 'Dynamic'.
	 * options['ScrollToSummary']	True to scroll to the validation summary upon refresh.
	 * </code>
	 */	
	initialize : function(options)
	{
		this.options = options;
		this.group = options.ValidationGroup;
		this.summary = $(options.ID);
		this.visible = this.summary.style.visibility != "hidden"
		this.visible = this.visible && this.summary.style.display != "none";
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
			return this.hideSummary(update);

		var refresh = update || this.visible == false || this.options.Refresh != false;
				
		if(this.options.ShowSummary != false && refresh)
		{
			this.displayHTMLMessages(this.getMessages(validators));
			this.visible = true;
		}
		
		if(this.options.ScrollToSummary != false)
			window.scrollTo(this.summary.offsetLeft-20, this.summary.offsetTop-20);
		
		if(this.options.ShowMessageBox == true && refresh)
		{
			this.alertMessages(this.getMessages(validators));
			this.visible = true;
		}
	},
	
	/**
	 * Display the validator error messages as inline HTML.
	 */
	displayHTMLMessages : function(messages)
	{
		this.summary.show();
		this.summary.style.visibility = "visible";
		while(this.summary.childNodes.length > 0)
			this.summary.removeChild(this.summary.lastChild);
		new Insertion.Bottom(this.summary, this.formatSummary(messages));	
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
	 * Hides the validation summary if options['Refresh'] is not false.
	 * @param boolean true to always hide the summary
	 */
	hideSummary : function(refresh)
	{
		if(refresh || this.options.Refresh != false)
		{
			if(this.options.Display == "None" || this.options.Display == "Dynamic")
				this.summary.hide();
			this.summary.style.visibility="hidden";
			this.visible = false;
		}		
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
	enabled : true, 
	visible : false,
	isValid : true, 
	options : {},
	_isObserving : false,
	group : null,
	
	/**
	 * <code>
	 * options['ID']*				Validator ID, e.g. span with message
	 * options['FormID']*			HTML form that the validator belongs
	 * options['ControlToValidate']*HTML form input to validate
	 * options['Display']			Display mode, 'None', 'Static', 'Dynamic'
	 * options['ErrorMessage']		Validation error message
	 * options['FocusOnError']		True to focus on validation error
	 * options['FocusElementID']	Element to focus on error
	 * options['ValidationGroup']	Validation group
	 * options['ControlCssClass']	Css class to use on the input upon error
	 * options['OnValidate']		Function to call immediately after validation
	 * options['OnSuccess']			Function to call upon after successful validation
	 * options['OnError']			Function to call upon after error in validation.
	 * options['ObserveChanges'] 	True to observe changes in input
	 * </code>
	 */
	initialize : function(options)
	{
		options.OnValidate = options.OnValidate || Prototype.emptyFunction;
		options.OnSuccess = options.OnSuccess || Prototype.emptyFunction;
		options.OnError = options.OnError || Prototype.emptyFunction;
		
		this.options = options;
		this.control = $(options.ControlToValidate);
		this.message = $(options.ID);
		this.group = options.ValidationGroup;
		
		Prado.Validation.addValidator(options.FormID, this);
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
	updateControl: function()
	{
		if(this.message)
		{
			if(this.options.Display == "Dynamic")
				this.isValid ? this.message.hide() : this.message.show();
			this.message.style.visibility = this.isValid ? "hidden" : "visible";
		}
		
		this.updateControlCssClass(this.control, this.isValid);	
		
		if(this.options.FocusOnError && !this.isValid)
			Prado.Element.focus(this.options.FocusElementID);
		
		this.visible = true;
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
	 * @param Validation manager
	 * @return boolean true if valid.
	 */
	validate : function(manager)
	{
		if(this.enabled)
			this.isValid = this.evaluateIsValid(manager);
		
		this.options.OnValidate(this, manager);
		
		this.updateControl();
		
		if(this.isValid)
			this.options.OnSuccess(this, manager);
		else
			this.options.OnError(this, manager);
		
		this.observeChanges(manager);
					
		return this.isValid;
	},
	
	/**
	 * Observe changes to the control input, re-validate upon change. If
	 * the validator is not visible, no updates are propagated.
	 */
	observeChanges : function(manager)
	{
		if(this.options.ObserveChanges != false && !this._isObserving)
		{
			var validator = this;
			Event.observe(this.control, 'change', function()
			{
				if(validator.visible)
				{
					validator.validate(manager);
					manager.updateSummary(validator.group);
				}
			});
			this._isObserving = true;
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
	 * @param {string} the data type, "Integer", "Double", "Currency", "Date" or "String"
	 * @param {string} the value to convert.
	 * @type {mixed|null} the converted data value.
	 */
	convert : function(dataType, value)
	{
		if(typeof(value) == "undefined")
			value = $F(this.control);
		var string = new String(value);
		switch(dataType)
		{
			case "Integer":
				return string.toInteger();
			case "Double" :
			case "Float" :
				return string.toDouble(this.options.DecimalChar);
			case "Currency" :
				return string.toCurrency(this.options.GroupChar, this.options.Digits, this.options.DecimalChar);
			case "Date":
				var value = string.toDate(this.options.DateFormat);	
				if(value && typeof(value.getTime) == "function")
					return value.getTime();
				else
					return null;
			case "String":
				return string.toString();		
		}
		return value;
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
        	var a = this.trim($F(this.control));
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
 * - <b>Currency</b> A decimal data type that can contain currency symbols.
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
	_observingComparee : false,
	
	/**
	 * Compares the input to another input or a given value.
	 */
	evaluateIsValid : function(manager)
	{
		var value = this.trim($F(this.control));
	    if (value.length <= 0) 
	    	return true;

    	var comparee = $(this.options.ControlToCompare);

		if(comparee)
			var compareTo = this.trim($F(comparee));
		else
			var compareTo = this.options.ValueToCompare || "";
		
	    var isValid =  this.compare(value, compareTo);
	    
		if(comparee)
		{
			this.updateControlCssClass(comparee, isValid);				
			this.observeComparee(comparee, manager);
		}	
		return isValid;		
	},
	
	/**
	 * Observe the comparee input element for changes. 
	 * @param object HTML input element to observe
	 * @param object Validation manager.
	 */
	observeComparee : function(comparee, manager)
	{
		if(this.options.ObserveChanges != false && !this._observingComparee)
		{
			var validator = this;	
			Event.observe(comparee, "change", function()
			{
				if(validator.visible)
				{
					validator.validate(manager);
					manager.updateSummary(validator.group);
				}
			});
			this._observingComparee = true;
		}
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
	evaluateIsValid : function(manager)
	{
		var value = $F(this.control);
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
 * - <b>Currency</b> A decimal data type that can contain currency symbols.
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
	 * Returns true if the value is empty, returns false if conversion fails.
	 */
	evaluateIsValid : function(manager)
	{
		var value = this.trim($F(this.control));
		if(value.length <= 0)
			return true;		
		if(typeof(this.options.DataType) == "undefined")
			this.options.DataType = "String";
		
		var min = this.convert(this.options.DataType, this.options.MinValue || null);
		var max = this.convert(this.options.DataType, this.options.MaxValue || null);
		value = this.convert(this.options.DataType, value);
		
		Logger.warn(min+" <= "+value+" <= "+max);
		
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
	evaluateIsValid : function(master)
	{
		var value = this.trim($F(this.control));
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


