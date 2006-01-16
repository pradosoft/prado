
Prado.Validation.TRequiredFieldValidator=function(){
    var inputType = this.control.getAttribute("type");
    if(inputType == 'file'){
        return true;
    }
    else{
        var a= Prado.Validation.trim($F(this.control));
        var b= Prado.Validation.trim(this.attr.initialvalue);
        return(a != b);
    }
}


Prado.Validation.TRegularExpressionValidator = function()
{
	var value = Prado.Validation.trim($F(this.control));
    if (value == "") return true;
    var rx = new RegExp(this.attr.validationexpression);
    var matches = rx.exec(value);
    return (matches != null && value == matches[0]);
}

Prado.Validation.TEmailAddressValidator = Prado.Validation.TRegularExpressionValidator;

Prado.Validation.TCustomValidator = function()
{
	var value = isNull(this.control) ? null : $F(this.control);
    var func = this.attr.clientvalidationfunction;
	eval("var validate = "+func);
    return validate && isFunction(validate) ? validate(this, value) : true;
}

Prado.Validation.TRangeValidator = function()
{
	var value = Prado.Validation.trim($F(this.control));
    if (value == "") return true;

    var minval = this.attr.minimumvalue;
    var maxval = this.attr.maximumvalue;

	if (undef(minval) && undef(maxval))
        return true;

    if (minval == "") minval = 0;
	if (maxval == "") maxval = 0;
	
	var dataType = this.attr.type;

	if(undef(dataType))
	    return (parseFloat(value) >= parseFloat(minval)) && (parseFloat(value) <= parseFloat(maxval));

	//now do datatype range check.
	var min = this.convert(dataType, minval);
	var max = this.convert(dataType, maxval);
	value = this.convert(dataType, value);	
	return value >= min && value <= max;
}

Prado.Validation.TCompareValidator = function()
{
    var value = Prado.Validation.trim($F(this.control));
    if (value.length == 0) return true;

    var compareTo;

    var comparee = $(this.attr.controlhookup);;

	if(comparee)
		compareTo = Prado.Validation.trim($F(comparee));
	else
	{
		compareTo = isString(this.attr.valuetocompare) ? this.attr.valuetocompare : "";
	}

	var compare = Prado.Validation.TCompareValidator.compare;

    var isValid =  compare.bind(this)(value, compareTo);

	//update the comparee control css class name and add onchange event once.
	if(comparee)
	{
		var className = this.attr.controlcssclass;
		if(isString(className) && className.length>0)
			Element.condClassName(comparee, className, !isValid);
		if(undef(this.observingComparee))
		{
			Event.observe(comparee, "change", this.validate.bind(this));
			this.observingComparee = true;
		}
	}
	return isValid;
}

/**
 * Compare the two values, also performs data type check.
 * @param {string} value to compare with
 * @param {string} value to compare
 * @type {boolean} true if comparison or type check is valid, false otherwise.
 */
Prado.Validation.TCompareValidator.compare = function(operand1, operand2)
{
	var op1, op2;
	if ((op1 = this.convert(this.attr.type, operand1)) == null)
		return false;
	if (this.attr.operator == "DataTypeCheck")
        return true;
	if ((op2 = this.convert(this.attr.type, operand2)) == null)
        return true;
    switch (this.attr.operator) 
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

Prado.Validation.TRequiredListValidator = function()
{
	var min = undef(this.attr.min) ? Number.NEGATIVE_INFINITY : parseInt(this.attr.min);
	var max = undef(this.attr.max) ? Number.POSITIVE_INFINITY : parseInt(this.attr.max);

	var elements = document.getElementsByName(this.attr.selector);

	if(elements.length <= 0)
		elements = document.getElementsBySelector(this.attr.selector);

	if(elements.length <= 0)
		return true;
	
	var required = new Array();
	if(isString(this.attr.required) && this.attr.required.length > 0)
		required = this.attr.required.split(/,\s* /);

	var isValid = true;

	var validator = Prado.Validation.TRequiredListValidator;

	switch(elements[0].type)
	{
		case 'radio':
		case 'checkbox':
			isValid = validator.IsValidRadioList(elements, min, max, required);
			break;
		case 'select-multiple':
			isValid = validator.IsValidSelectMultipleList(elements, min, max, required);
			break;
	}

	var className = this.attr.elementcssclass;
	if(isString(className) && className.length>0)
		map(elements, function(element){ condClass(element, className, !isValid); });
	if(undef(this.observingRequiredList))
	{
		Event.observe(elements, "change", this.validate.bind(this));
		this.observingRequiredList = true;
	}
	return isValid;
}

//radio group selection
Prado.Validation.TRequiredListValidator.IsValidRadioList = function(elements, min, max, required)
{
	var checked = 0;
	var values = new Array();
	for(var i = 0; i < elements.length; i++)
	{
		if(elements[i].checked)
		{
			checked++;
			values.push(elements[i].value);
		}
	}
	return Prado.Validation.TRequiredListValidator.IsValidList(checked, values, min, max, required);
}

//multiple selection check
Prado.Validation.TRequiredListValidator.IsValidSelectMultipleList = function(elements, min, max, required)
{
	var checked = 0;
	var values = new Array();
	for(var i = 0; i < elements.length; i++)
	{
		var selection = elements[i];
		for(var j = 0; j < selection.options.length; j++)
		{
			if(selection.options[j].selected)
			{
				checked++;
				values.push(selection.options[j].value);
			}
		}
	}
	return Prado.Validation.TRequiredListValidator.IsValidList(checked, values, min, max, required);
}

//check if the list was valid
Prado.Validation.TRequiredListValidator.IsValidList = function(checkes, values, min, max, required)
{
	var exists = true;

	if(required.length > 0)
	{
		//required and the values must at least be have same lengths
		if(values.length < required.length)
			return false;
		for(var k = 0; k < required.length; k++)
			exists = exists && values.contains(required[k]);
	}
	
	return exists && checkes >= min && checkes <= max;
}
