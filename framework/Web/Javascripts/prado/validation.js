
/**
 * Prado client-side javascript validation class.
 */
Prado.Validation = Class.create();

/**
 * Utilities for validation. Static class.
 */
Prado.Validation.Util = Class.create();

/** 
 * Convert a string into integer, returns null if not integer.
 * @param {string} the string to convert to integer
 * @type {integer|null} null if string does not represent an integer.
 */
Prado.Validation.Util.toInteger = function(value)
{
	var exp = /^\s*[-\+]?\d+\s*$/;
	if (value.match(exp) == null)
		return null;
	var num = parseInt(value, 10);
	return (isNaN(num) ? null : num);
}

/** 
 * Convert a string into a double/float value. <b>Internationalization 
 * is not supported</b>
 * @param {string} the string to convert to double/float
 * @param {string} the decimal character
 * @return {float|null} null if string does not represent a float value
 */
Prado.Validation.Util.toDouble = function(value, decimalchar)
{
	decimalchar = undef(decimalchar) ? "." : decimalchar;
	var exp = new RegExp("^\\s*([-\\+])?(\\d+)?(\\" + decimalchar + "(\\d+))?\\s*$");
    var m = value.match(exp);
    if (m == null)	
		return null;
	var cleanInput = m[1] + (m[2].length>0 ? m[2] : "0") + "." + m[4];
    var num = parseFloat(cleanInput);
    return (isNaN(num) ? null : num);
}

/**
 * Convert strings that represent a currency value (e.g. a float with grouping 
 * characters) to float. E.g. "10,000.50" will become "10000.50". The number 
 * of dicimal digits, grouping and decimal characters can be specified.
 * <i>The currency input format is <b>very</b> strict, null will be returned if
 * the pattern does not match</i>.
 * @param {string} the currency value
 * @param {string} the grouping character, default is ","
 * @param {int} number of decimal digits
 * @param {string} the decimal character, default is "."
 * @type {float|null} the currency value as float.
 */
Prado.Validation.Util.toCurrency = function(value, groupchar, digits, decimalchar)
{
	groupchar = undef(groupchar) ? "," : groupchar;
	decimalchar = undef(decimalchar) ? "." : decimalchar;
	digits = undef(digits) ? 2 : digits;

	var exp = new RegExp("^\\s*([-\\+])?(((\\d+)\\" + groupchar + ")*)(\\d+)"
		+ ((digits > 0) ? "(\\" + decimalchar + "(\\d{1," + digits + "}))?" : "")
        + "\\s*$");
	var m = value.match(exp);
	if (m == null)
		return null;
	var intermed = m[2] + m[5] ;
    var cleanInput = m[1] + intermed.replace(
			new RegExp("(\\" + groupchar + ")", "g"), "") 
							+ ((digits > 0) ? "." + m[7] : "");
	var num = parseFloat(cleanInput);
	return (isNaN(num) ? null : num);
}

/**
 * Get the date from string using the prodivided date format string.
 * The format notations are
 * # day -- %d or %e
 * # month -- %m
 * # year -- %y or %Y
 * # hour -- %H, %I, %k, or %l
 * # minutes -- %M
 * # P.M. -- %p or %P
 * @param {string} the formatted date string
 * @param {string} the date format
 * @type {Date} the date represented in the string
 */
Prado.Validation.Util.toDate = function(value, format)
{
	var y = 0;
	var m = -1;
	var d = 0;
	var a = value.split(/\W+/);
	var b = format.match(/%./g);
	var i = 0, j = 0;
	var hr = 0;
	var min = 0;
	for (i = 0; i < a.length; ++i) {
		if (!a[i])
			continue;
		switch (b[i]) {
		    case "%d":
		    case "%e":
			d = parseInt(a[i], 10);
			break;

		    case "%m":
			m = parseInt(a[i], 10) - 1;
			break;

		    case "%Y":
		    case "%y":
			y = parseInt(a[i], 10);
			(y < 100) && (y += (y > 29) ? 1900 : 2000);
			break;

		    case "%H":
		    case "%I":
		    case "%k":
		    case "%l":
			hr = parseInt(a[i], 10);
			break;

		    case "%P":
		    case "%p":
			if (/pm/i.test(a[i]) && hr < 12)
				hr += 12;
			break;

		    case "%M":
			min = parseInt(a[i], 10);
			break;
		}
	}
	if (y != 0 && m != -1 && d != 0)
	{
		var date = new Date(y, m, d, hr, min, 0);
		return (isObject(date)
				&& y == date.getFullYear() 
				&& m == date.getMonth() 
				&& d == date.getDate()) ? date.valueOf() : null;
	}
	return null;
}

/**
 * Trim the value, if the value is undefined, empty string is return.
 * @param {string} string to be trimmed.
 * @type {string} trimmed string.
 */
Prado.Validation.trim = function(value)
{
	if(isString(value)) return value.trim();
	return "";
}

/**
 * A delayed focus on a particular element
 * @param {element} element to apply focus()
 */
Prado.Validation.Util.focus = function(element)
{
	var obj = $(element);
	if(isObject(obj) && isdef(obj.focus))
		setTimeout(function(){ obj.focus(); }, 100);
	return false;
}

/**
 * List of validator instances.
 */
Prado.Validation.validators = [];

/**
 * List of forms.
 * @type {int}
 */
Prado.Validation.forms = [];

/**
 * List of summary controls.
 */
Prado.Validation.summaries = [];

/**
 * Validation groups.
 */
Prado.Validation.groups = [];


/**
 * Second type of grouping.
 */
Prado.Validation.TargetGroups = {};


/**
 * Current Target group.
 */
Prado.Validation.CurrentTargetGroup = null;

Prado.Validation.HasTargetGroup = false;

/**
 * Targets that can cause validation.
 */
Prado.Validation.ActiveTarget = null;


/**
 * Determine if group validation is active.
 */
Prado.Validation.IsGroupValidation = false;

/**
 * Add a form for validation.
 * @param {string} form ID
 */
Prado.Validation.AddForm = function(id)
{
	Prado.Validation.forms.push($(id));
}

/**
 * Add a target that causes validation. Only elements that have been added
 * can cause validation.
 * @param {string} target id
 */
Prado.Validation.AddTarget = function(id, group)
{
	var target = $(id);	
	Event.observe(target, "click", function()
	{
		Prado.Validation.ActiveTarget = target;
		Prado.Validation.CurrentTargetGroup = Prado.Validation.TargetGroups[id];
	});
	if(group)
	{
		Prado.Validation.TargetGroups[id] = group;
		Prado.Validation.HasTargetGroup = true;
	}
}

Prado.Validation.SetActiveGroup = function(target, group)
{
	Prado.Validation.ActiveTarget = target;
	Prado.Validation.CurrentTargetGroup = group;
}

/**
 * Associate a list of validators to a particular control element.
 * This essentially allows a set of validators to be grouped to a particular button.
 * @param {list} group array show have, {group : "id", target : "target button"}
 * @param {array} validator ids
 */
Prado.Validation.AddGroup = function(group, validators)
{
	group.active = false; //default active status is false.
	group.target = $(group.target);
	group.validators = validators;
	Prado.Validation.groups.push(group);

	//update the active group when the button is clicked.
	Event.observe(group.target, "click", Prado.Validation.UpdateActiveGroup);
}

/**
 * Update the active group, if call manually it will deactivate all groups.
 * @param {string}
 * @type {int}
 */
Prado.Validation.UpdateActiveGroup = function(ev)
{
	var groups = Prado.Validation.groups;
	for (var i = 0; i < groups.length; i++)
	{
		groups[i].active = (isdef(ev) && groups[i].target == Event.element(ev));
	}
	Prado.Validation.IsGroupValidation = isdef(ev);
}

/**
 * Determine if validation is sucessful. Iterate through the list 
 * of validator instances and call validate(). Only validators that
 * for a particular form are evaluated. Other validators will be disabled.
 * If performing group validation, only active validators are visible.
 * @param {element} the form for the controls to validate.
 * @type {boolean} true is all validators are valid, false otherwise.
 */
Prado.Validation.IsValid = function(form)
{
	var valid = true;
	var validators = Prado.Validation.validators;
	
	for(var i = 0; i < validators.length; i++)
	{
		//prevent validating multiple forms
		validators[i].enabled = !validators[i].control || undef(validators[i].control.form) || validators[i].control.form == form;
		//when group validation, only validators in the active group are visible.
		validators[i].visible = Prado.Validation.IsGroupValidation ? validators[i].inActiveGroup() : true;

		if(Prado.Validation.HasTargetGroup)
		{
			if(validators[i].group != Prado.Validation.CurrentTargetGroup)
				validators[i].enabled = false;
		}

		valid &= validators[i].validate();
	}

	//show the summary including the alert box
	Prado.Validation.ShowSummary(form);
	//reset all the group active status to false
	Prado.Validation.UpdateActiveGroup();
	return valid;
}

/**
 * Base validator class. Supply a different validation function
 * to obtain a different validator. E.g. to use the RequiredFieldValidator
 * <code>new Prado.Validation(Prado.Validation.RequiredFieldValidator, options);</code>
 * or to use the CustomValidator, 
 * <code>new Prado.Validation(Prado.Validation.CustomValidator, options);</code>
 */
Prado.Validation.prototype = 
{
	/**
	 * Initialize the validator.
	 * @param {function} the function to call to evaluate if 
	 * the validator is valid
	 * @param {string|element} the control ID or element
	 * @param {array} the list of attributes for the validator
	 */
	initialize : function(validator, attr)
	{
		this.evaluateIsValid = validator;
		this.attr = undef(attr) ? [] : attr;
		this.message = $(attr.id);
		this.control = $(attr.controltovalidate);
		this.enabled = isdef(attr.enabled) ? attr.enabled : true;
		this.visible = isdef(attr.visible) ? attr.visible : true;
		this.group = isdef(attr.validationgroup) ? attr.validationgroup : null;
		this.isValid = true;
		Prado.Validation.validators.push(this);
		if(this.evaluateIsValid)
			this.evaluateIsValid.bind(this);
	},

	/**
	 * Evaluate the validator only when visible and enabled.
	 * @type {boolean} true if valid, false otherwise.
	 */
	validate : function()
	{		
		if(this.visible && this.enabled && this.evaluateIsValid)
			this.isValid = this.evaluateIsValid();
		else
			this.isValid = true;
		
		this.observe(); //watch for changes to the control values
		this.update(); //update the validation messages
		return this.isValid;
	},

	/**
	 * Hide or show the error messages for "Dynamic" displays.
	 */
	update : function()
	{
		if(this.attr.display == "Dynamic")
			this.isValid ? Element.hide(this.message) : Element.show(this.message);
		
		if(this.message)
			this.message.style.visibility = this.isValid ? "hidden" : "visible";

		//update the control css class name
		var className = this.attr.controlcssclass;
		if(this.control && isString(className) && className.length>0)
			Element.condClassName(this.control, className, !this.isValid);
		Prado.Validation.ShowSummary();

		var focus = this.attr.focusonerror;
		var hasGroup = Prado.Validation.HasTargetGroup;
		var inGroup = this.group == Prado.Validation.CurrentTargetGroup;

		if(focus && (!hasGroup || (hasGroup && inGroup)))
			Prado.Element.focus(this.attr.focuselementid);
	},

	/**
	 * Change the validity of the validator, calls update().
	 * @param {boolean} change the isValid state of the validator.
	 */
	setValid : function(valid)
	{
		this.isValid = valid;
		this.update();
	},

	/**
	 * Observe changes to the control values, add "onchange" event to the control once.
	 */
	observe : function()
	{
		if(undef(this.observing))
		{
			if(this.control && this.control.form)
				Event.observe(this.control, "change", this.validate.bind(this));
			this.observing = true;
		}
	},

	/**
	 * Convert the value of the control to a specific data type.
	 * @param {string} the data type, "Integer", "Double", "Currency" or "Date".
	 * @param {string} the value to convert, null to get the value from the control.
	 * @type {mixed|null} the converted data value.
	 */
	convert : function(dataType, value)
	{
		if(undef(value))
			value = Form.Element.getValue(this.control);
		switch(dataType)
		{
			case "Integer":
				return Prado.Validation.Util.toInteger(value);
			case "Double" :
			case "Float" :
				return Prado.Validation.Util.toDouble(value, this.attr.decimalchar);
			case "Currency" :
				return Prado.Validation.Util.toCurrency(
					value, this.attr.groupchar, this.attr.digits, this.attr.decimalchar);
			case "Date":
				return Prado.Validation.Util.toDate(value, this.attr.dateformat);			
		}
		return value.toString();
	},

	/**
	 * Determine if the current validator is part of a active validation group.
	 * @type {boolean} true if part of active validation group, false otherwise.
	 */
	inActiveGroup : function()
	{
		var groups = Prado.Validation.groups;
		for (var i = 0; i < groups.length; i++)
		{
			if(groups[i].active && groups[i].validators.contains(this.attr.id))
				return true;
		}
		return false;
	}
}

/**
 * Validation summary class.
 */
Prado.Validation.Summary = Class.create();
Prado.Validation.Summary.prototype = 
{
	/**
	 * Initialize a validation summary.
	 * @param {array} summary options.
	 */
	initialize : function(attr)
	{
		this.attr = attr;
		this.div = $(attr.id);
		this.visible = false;
		this.enabled = false;
		this.group = isdef(attr.validationgroup) ? attr.validationgroup : null;
		Prado.Validation.summaries.push(this);
	},

	/**
	 * Show the validation summary.
	 * @param {boolean} true to allow alert message
	 */
	show : function(warn)
	{
		var refresh = warn || this.attr.refresh == "1";
		var messages = this.getMessages();	
		if(messages.length <= 0 || !this.visible || !this.enabled) 
		{
			if(refresh)
				Element.hide(this.div); 
			return;
		}

		if(Prado.Validation.HasTargetGroup)
		{
			if(Prado.Validation.CurrentTargetGroup != this.group)
			{
				if(refresh)
					Element.hide(this.div); 
					return;
			}
		}
		
		if(this.attr.showsummary != "False" && refresh)
		{
			//Element.show(this.div);
			this.div.style.display = "block";
			while(this.div.childNodes.length > 0)
				this.div.removeChild(this.div.lastChild);
			new Insertion.Bottom(this.div, this.formatSummary(messages));
		}
		
		if(warn)
			window.scrollTo(this.div.offsetLeft-20, this.div.offsetTop-20);
	
		var summary = this;
		if(warn && this.attr.showmessagebox == "True" && refresh)
			setTimeout(function(){alert(summary.formatMessageBox(messages));},20);
	},

	/**
	 * Get a list of error messages from the validators.
	 * @type {array} list of messages
	 */
	getMessages : function()
	{
		var validators = Prado.Validation.validators;
		var messages = [];
		for(var i = 0; i < validators.length; i++)
		{			
			if(validators[i].isValid == false
				&& isString(validators[i].attr.errormessage)
				&& validators[i].attr.errormessage.length > 0)
			{
					
				messages.push(validators[i].attr.errormessage);
			}
		}
		return messages;
	},

	/**
	 * Return the format parameters for the summary.
	 * @param {string} format type, "List", "SingleParagraph" or "BulletList"
	 * @type {array} formatting parameters
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
	 * @param {array} list of error messages.
	 * @type {string} formatted message
	 */
	formatSummary : function(messages)
	{
		var format = this.formats(this.attr.displaymode);
		var output = isdef(this.attr.headertext) ? this.attr.headertext + format.header : "";
		output += format.first;
		for(var i = 0; i < messages.length; i++)
			output += (messages[i].length>0) ? format.pre + messages[i] + format.post : "";
		output += format.last;
		return output;
	},
	/**
	 * Format the message alert box.
	 * @param {array} a list of error messages.
	 * @type {string} format message for alert.
	 */
	formatMessageBox : function(messages)
	{
		var output = isdef(this.attr.headertext) ? this.attr.headertext + "\n" : "";
		for(var i = 0; i < messages.length; i++)
		{
			switch(this.attr.displaymode)
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
	},

	/**
	 * Determine if this summary belongs to an active group.
	 * @type {boolean} true if belongs to an active group.
	 */
	inActiveGroup : function()
	{
		var groups = Prado.Validation.groups;
		for (var i = 0; i < groups.length; i++)
		{
			if(groups[i].active && groups[i].id == this.attr.group)
				return true;
		}
		return false;
	}
}

/**
 * Show the validation error message summary.
 * @param {element} the form that activated the summary call.
 */
Prado.Validation.ShowSummary = function(form)
{
	var summary = Prado.Validation.summaries;
	for(var i = 0; i < summary.length; i++)
	{
		if(isdef(form))
		{			
			if(Prado.Validation.IsGroupValidation)
			{
				summary[i].visible =  summary[i].inActiveGroup();
			}
			else 
			{				
				summary[i].visible = undef(summary[i].attr.group);
			}

			summary[i].enabled = $(summary[i].attr.form) == form;	
		}
		summary[i].show(form);
	}
}



/**
 * When a form is try to submit, check the validators, submit
 * the form only when all validators are valid.
 * @param {event} form submit event.
 */
Prado.Validation.OnSubmit = function(ev)
{
	//HTML text editor, tigger save first.
	//alert(tinyMCE);
	if(typeof tinyMCE != "undefined")
		tinyMCE.triggerSave();

	//no active target?
	if(!Prado.Validation.ActiveTarget) return true;
	var valid = Prado.Validation.IsValid(Event.element(ev) || ev);

	//not valid? do not submit the form
	if(Event.element(ev) && !valid)
			Event.stop(ev);

	//reset the target
	Prado.Validation.ActiveTarget = null;
	//Prado.Validation.CurrentTargetGroup = null;

	return valid;
}

/**
 * During window onload event, attach onsubmit event for each of the
 * forms in Prado.Validation.forms.
 */
Prado.Validation.OnLoad = function()
{
	Event.observe(Prado.Validation.forms,"submit", Prado.Validation.OnSubmit);
}


/**
 * Validate Validator Groups.
 * @param string ValidatorGroup
 * @return boolean true if valid, false otherwise
 */
Prado.Validation.ValidateValidatorGroup = function(groupId)
{
	var groups = Prado.Validation.groups;
	var group = null;
	for(var i = 0; i < groups.length; i++)
	{
		if(groups[i].id == groupId)
		{
			group = groups[i];
			Prado.Validation.groups[i].active = true;
			Prado.Validation.CurrentTargetGroup = null;
			Prado.Validation.IsGroupValidation = true;
		}
		else
		{
			Prado.Validation.groups[i].active = false;
		}
	}
	if(group)
	{
		return Prado.Validation.IsValid(group.target.form);
	}
	return true;
};

/**
 * Validate ValidationGroup
 * @param string ValidationGroup
 * @return boolean true if valid, false otherwise.
 */
Prado.Validation.ValidateValidationGroup= function(groupId)
{
	var groups = Prado.Validation.TargetGroups;
	for(var id in groups)
	{
		if(groups[id] == groupId)
		{
			var target = $(id);
			Prado.Validation.ActiveTarget = target;
			Prado.Validation.CurrentTargetGroup = groupId;
			Prado.Validation.IsGroupValidation = false;
			return Prado.Validation.IsValid(target.form);
		}
	}
	return true;
};

/**
 * Validate the page
 * @return boolean true if valid, false otherwise.
 */
Prado.Validation.ValidateNonGroup= function(formId)
{
	if(Prado.Validation)
	{
		var form = $(formId);
		form = form || document.forms[0];
		Prado.Validation.ActiveTarget = form;
		Prado.Validation.CurrentTargetGroup = null;
		Prado.Validation.IsGroupValidation = false;
		return Prado.Validation.IsValid(form);
	}
	return true;
};
	


/**
 * Register Prado.Validation.Onload() for window.onload event.
 */
Event.OnLoad(Prado.Validation.OnLoad);