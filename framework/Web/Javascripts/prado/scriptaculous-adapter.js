
/**
 * Similar to bindAsEventLister, but takes additional arguments.
 */
Function.prototype.bindEvent = function()
{
	var __method = this, args = $A(arguments), object = args.shift();
	return function(event)
	{
		return __method.apply(object, [event || window.event].concat(args));
	}
}

/**
 * Creates a new function by copying function definition from
 * the <tt>base</tt> and optional <tt>definition</tt>.
 * @param function a base function to copy from.
 * @param array additional definition
 * @param function return a new function with definition from both
 * <tt>base</tt> and <tt>definition</tt>.
 */
Class.extend = function(base, definition)
{
		var component = Class.create();
		Object.extend(component.prototype, base.prototype);
		if(definition)
			Object.extend(component.prototype, definition);
		return component;
}

/*
	Base, version 1.0.2
	Copyright 2006, Dean Edwards
	License: http://creativecommons.org/licenses/LGPL/2.1/
*/

var Base = function() {
	if (arguments.length) {
		if (this == window) { // cast an object to this class
			Base.prototype.extend.call(arguments[0], arguments.callee.prototype);
		} else {
			this.extend(arguments[0]);
		}
	}
};

Base.version = "1.0.2";

Base.prototype = {
	extend: function(source, value) {
		var extend = Base.prototype.extend;
		if (arguments.length == 2) {
			var ancestor = this[source];
			// overriding?
			if ((ancestor instanceof Function) && (value instanceof Function) &&
				ancestor.valueOf() != value.valueOf() && /\bbase\b/.test(value)) {
				var method = value;
			//	var _prototype = this.constructor.prototype;
			//	var fromPrototype = !Base._prototyping && _prototype[source] == ancestor;
				value = function() {
					var previous = this.base;
				//	this.base = fromPrototype ? _prototype[source] : ancestor;
					this.base = ancestor;
					var returnValue = method.apply(this, arguments);
					this.base = previous;
					return returnValue;
				};
				// point to the underlying method
				value.valueOf = function() {
					return method;
				};
				value.toString = function() {
					return String(method);
				};
			}
			return this[source] = value;
		} else if (source) {
			var _prototype = {toSource: null};
			// do the "toString" and other methods manually
			var _protected = ["toString", "valueOf"];
			// if we are prototyping then include the constructor
			if (Base._prototyping) _protected[2] = "constructor";
			for (var i = 0; (name = _protected[i]); i++) {
				if (source[name] != _prototype[name]) {
					extend.call(this, name, source[name]);
				}
			}
			// copy each of the source object's properties to this object
			for (var name in source) {
				if (!_prototype[name]) {
					extend.call(this, name, source[name]);
				}
			}
		}
		return this;
	},

	base: function() {
		// call this method from any other method to invoke that method's ancestor
	}
};

Base.extend = function(_instance, _static) {
	var extend = Base.prototype.extend;
	if (!_instance) _instance = {};
	// build the prototype
	Base._prototyping = true;
	var _prototype = new this;
	extend.call(_prototype, _instance);
	var constructor = _prototype.constructor;
	_prototype.constructor = this;
	delete Base._prototyping;
	// create the wrapper for the constructor function
	var klass = function() {
		if (!Base._prototyping) constructor.apply(this, arguments);
		this.constructor = klass;
	};
	klass.prototype = _prototype;
	// build the class interface
	klass.extend = this.extend;
	klass.implement = this.implement;
	klass.toString = function() {
		return String(constructor);
	};
	extend.call(klass, _static);
	// single instance
	var object = constructor ? klass : _prototype;
	// class initialisation
	if (object.init instanceof Function) object.init();
	return object;
};

Base.implement = function(_interface) {
	if (_interface instanceof Function) _interface = _interface.prototype;
	this.prototype.extend(_interface);
};

/**
 * Performs a post-back using javascript
 *
 */
Prado.PostBack = function(event,options)
{
	var form = $(options['FormID']);
	var canSubmit = true;

	if(options['CausesValidation'] && typeof(Prado.Validation) != "undefined")
	{
		if(!Prado.Validation.validate(options['FormID'], options['ValidationGroup'], $(options['ID'])))
			return Event.stop(event);
	}

	if(options['PostBackUrl'] && options['PostBackUrl'].length > 0)
		form.action = options['PostBackUrl'];

	if(options['TrackFocus'])
	{
		var lastFocus = $('PRADO_LASTFOCUS');
		if(lastFocus)
		{
			var active = document.activeElement; //where did this come from
			if(active)
				lastFocus.value = active.id;
			else
				lastFocus.value = options['EventTarget'];
		}
	}

	$('PRADO_POSTBACK_TARGET').value = options['EventTarget'];
	$('PRADO_POSTBACK_PARAMETER').value = options['EventParameter'];
	/**
	 * Since google toolbar prevents browser default action,
	 * we will always disable default client-side browser action
	 */
	/*if(options['StopEvent']) */
		Event.stop(event);
	Event.fireEvent(form,"submit");
}

Prado.Element =
{
	/**
	 * Set the value of a particular element.
	 * @param string element id
	 * @param string new element value.
	 */
	setValue : function(element, value)
	{
		var el = $(element);
		if(el && typeof(el.value) != "undefined")
			el.value = value;
	},

	select : function(element, method, value, total)
	{
		var el = $(element);
		if(!el) return;
		var selection = Prado.Element.Selection;
		if(typeof(selection[method]) == "function")
		{
			control = selection.isSelectable(el) ? [el] : selection.getListElements(element,total);
			selection[method](control, value);
		}
	},

	click : function(element)
	{
		var el = $(element);
		if(el)
			Event.fireEvent(el,'click');
	},

	setAttribute : function(element, attribute, value)
	{
		var el = $(element);
		if(!el) return;
		if((attribute == "disabled" || attribute == "multiple") && value==false)
			el.removeAttribute(attribute);
		else if(attribute.match(/^on/i)) //event methods
		{
			try
			{
				eval("(func = function(event){"+value+"})");
				el[attribute] = func;
			}
			catch(e)
			{
				throw "Error in evaluating '"+value+"' for attribute "+attribute+" for element "+element.id;
			}
		}
		else
			el.setAttribute(attribute, value);
	},

	setOptions : function(element, options)
	{
		var el = $(element);
		if(!el) return;
		if(el && el.tagName.toLowerCase() == "select")
		{
			el.options.length = options.length;
			for(var i = 0; i<options.length; i++)
				el.options[i] = new Option(options[i][0],options[i][1]);
		}
	},

	/**
	 * A delayed focus on a particular element
	 * @param {element} element to apply focus()
	 */
	focus : function(element)
	{
		var obj = $(element);
		if(typeof(obj) != "undefined" && typeof(obj.focus) != "undefined")
			setTimeout(function(){ obj.focus(); }, 100);
		return false;
	},

	replace : function(element, method, content, boundary)
	{
		if(boundary)
		{
			result = Prado.Element.extractContent(this.transport.responseText, boundary);
			if(result != null)
				content = result;
		}
		if(typeof(element) == "string")
		{
			if($(element))
				method.toFunction().apply(this,[element,""+content]);
		}
		else
		{
			method.toFunction().apply(this,[""+content]);
		}
	},

	extractContent : function(text, boundary)
	{
		var f = RegExp('(<!--'+boundary+'-->)([\\s\\S\\w\\W]*)(<!--//'+boundary+'-->)',"m");
		var result = text.match(f);
		if(result && result.length >= 2)
			return result[2];
		else
			return null;
	},

	evaluateScript : function(content)
	{
		content.evalScripts();
	}
}

Prado.Element.Selection =
{
	isSelectable : function(el)
	{
		if(el && el.type)
		{
			switch(el.type.toLowerCase())
			{
				case 'checkbox':
				case 'radio':
				case 'select':
				case 'select-multiple':
				case 'select-one':
				return true;
			}
		}
		return false;
	},

	inputValue : function(el, value)
	{
		switch(el.type.toLowerCase())
		{
			case 'checkbox':
			case 'radio':
			return el.checked = value;
		}
	},

	selectValue : function(elements, value)
	{
		elements.each(function(el)
		{
			$A(el.options).each(function(option)
			{
				if(typeof(value) == "boolean")
					options.selected = value;
				else if(option.value == value)
					option.selected = true;
			});
		})
	},

	selectValues : function(elements, values)
	{
		selection = this;
		values.each(function(value)
		{
			selection.selectValue(elements,value);
		})
	},

	selectIndex : function(elements, index)
	{
		elements.each(function(el)
		{
			if(el.type.toLowerCase() == 'select-one')
				el.selectedIndex = index;
			else
			{
				for(var i = 0; i<el.length; i++)
				{
					if(i == index)
						el.options[i].selected = true;
				}
			}
		})
	},

	selectAll : function(elements)
	{
		elements.each(function(el)
		{
			if(el.type.toLowerCase() != 'select-one')
			{
				$A(el.options).each(function(option)
				{
					option.selected = true;
				})
			}
		})
	},

	selectInvert : function(elements)
	{
		elements.each(function(el)
		{
			if(el.type.toLowerCase() != 'select-one')
			{
				$A(el.options).each(function(option)
				{
					option.selected = !options.selected;
				})
			}
		})
	},

	selectIndices : function(elements, indices)
	{
		selection = this;
		indices.each(function(index)
		{
			selection.selectIndex(elements,index);
		})
	},

	selectClear : function(elements)
	{
		elements.each(function(el)
		{
			el.selectedIndex = -1;
		})
	},

	getListElements : function(element, total)
	{
		elements = new Array();
		for(i = 0; i < total; i++)
		{
			el = $(element+"_c"+i);
			if(el)
				elements.push(el);
		}
		return elements;
	},

	checkValue : function(elements, value)
	{
		elements.each(function(el)
		{
			if(typeof(value) == "boolean")
				el.checked = value;
			else if(el.value == value)
				el.checked = true;
		});
	},

	checkValues : function(elements, values)
	{
		selection = this;
		values.each(function(value)
		{
			selection.checkValue(elements, value);
		})
	},

	checkIndex : function(elements, index)
	{
		for(var i = 0; i<elements.length; i++)
		{
			if(i == index)
				elements[i].checked = true;
		}
	},

	checkIndices : function(elements, indices)
	{
		selection = this;
		indices.each(function(index)
		{
			selection.checkIndex(elements, index);
		})
	},

	checkClear : function(elements)
	{
		elements.each(function(el)
		{
			el.checked = false;
		});
	},

	checkAll : function(elements)
	{
		elements.each(function(el)
		{
			el.checked = true;
		})
	},

	checkInvert : function(elements)
	{
		elements.each(function(el)
		{
			el.checked != el.checked;
		})
	}
};


Prado.Element.Insert =
{
	append: function(element, content)
	{
		new Insertion.Bottom(element, content);
	},

	prepend: function(element, content)
	{
		new Insertion.Top(element, content);
	},

	after: function(element, content)
	{
		new Insertion.After(element, content);
	},

	before: function(element, content)
	{
		new Insertion.Before(element, content);
	}
}


/**
 * Export scripaculous builder utilities as window[functions]
 */
Object.extend(Builder,
{
	exportTags:function()
	{
		var tags=["BUTTON","TT","PRE","H1","H2","H3","BR","CANVAS","HR","LABEL","TEXTAREA","FORM","STRONG","SELECT","OPTION","OPTGROUP","LEGEND","FIELDSET","P","UL","OL","LI","TD","TR","THEAD","TBODY","TFOOT","TABLE","TH","INPUT","SPAN","A","DIV","IMG", "CAPTION"];
		tags.each(function(tag)
		{
			window[tag]=function()
			{
				var args=$A(arguments);
				if(args.length==0)
					return Builder.node(tag,null);
				if(args.length==1)
					return Builder.node(tag,args[0]);
				if(args.length>1)
					return Builder.node(tag,args.shift(),args);

			};
		});
	}
});

Builder.exportTags();

/**
 * @class String extensions
 */
Object.extend(String.prototype, {
	/**
	 * @param {String} "left" to pad the string on the left, "right" to pad right.
	 * @param {Number} minimum string length.
	 * @param {String} character(s) to pad
	 * @return {String} padded character(s) on the left or right to satisfy minimum string length
	 */

	pad : function(side, len, chr) {
		if (!chr) chr = ' ';
		var s = this;
		var left = side.toLowerCase()=='left';
		while (s.length<len) s = left? chr + s : s + chr;
		return s;
	},

	/**
	 * @param {Number} minimum string length.
	 * @param {String} character(s) to pad
	 * @return {String} padded character(s) on the left to satisfy minimum string length
	 */
	padLeft : function(len, chr) {
		return this.pad('left',len,chr);
	},

	/**
	 * @param {Number} minimum string length.
	 * @param {String} character(s) to pad
	 * @return {String} padded character(s) on the right to satisfy minimum string length
	 */
	padRight : function(len, chr) {
		return this.pad('right',len,chr);
	},

	/**
	 * @param {Number} minimum string length.
	 * @return {String} append zeros to the left to satisfy minimum string length.
	 */
	zerofill : function(len) {
		return this.padLeft(len,'0');
	},

	/**
	 * @return {String} removed white spaces from both ends.
	 */
	trim : function() {
		return this.replace(/^\s+|\s+$/g,'');
	},

	/**
	 * @return {String} removed white spaces from the left end.
	 */
	trimLeft : function() {
		return this.replace(/^\s+/,'');
	},

	/**
	 * @return {String} removed white spaces from the right end.
	 */
	trimRight : function() {
		return this.replace(/\s+$/,'');
	},

	/**
	 * Convert period separated function names into a function reference.
	 * e.g. "Prado.AJAX.Callback.Action.setValue".toFunction() will return
	 * the actual function Prado.AJAX.Callback.Action.setValue()
	 * @return {Function} the corresponding function represented by the string.
	 */
	toFunction : function()
	{
		var commands = this.split(/\./);
		var command = window;
		commands.each(function(action)
		{
			if(command[new String(action)])
				command=command[new String(action)];
		});
		if(typeof(command) == "function")
			return command;
		else
		{
			if(typeof Logger != "undefined")
				Logger.error("Missing function", this);

			throw new Error	("Missing function '"+this+"'");
		}
	},

	/**
	 * Convert a string into integer, returns null if not integer.
	 * @return {Number} null if string does not represent an integer.
	 */
	toInteger : function()
	{
		var exp = /^\s*[-\+]?\d+\s*$/;
		if (this.match(exp) == null)
			return null;
		var num = parseInt(this, 10);
		return (isNaN(num) ? null : num);
	},

	/**
	 * Convert a string into a double/float value. <b>Internationalization
	 * is not supported</b>
	 * @param {String} the decimal character
	 * @return {Double} null if string does not represent a float value
	 */
	toDouble : function(decimalchar)
	{
		if(this.length <= 0) return null;
		decimalchar = decimalchar || ".";
		var exp = new RegExp("^\\s*([-\\+])?(\\d+)?(\\" + decimalchar + "(\\d+))?\\s*$");
		var m = this.match(exp);

		if (m == null)
			return null;
		m[1] = m[1] || "";
		m[2] = m[2] || "0";
		m[4] = m[4] || "0";

		var cleanInput = m[1] + (m[2].length>0 ? m[2] : "0") + "." + m[4];
		var num = parseFloat(cleanInput);
		return (isNaN(num) ? null : num);
	},

	/**
	 * Convert strings that represent a currency value (e.g. a float with grouping
	 * characters) to float. E.g. "10,000.50" will become "10000.50". The number
	 * of dicimal digits, grouping and decimal characters can be specified.
	 * <i>The currency input format is <b>very</b> strict, null will be returned if
	 * the pattern does not match</i>.
	 * @param {String} the grouping character, default is ","
	 * @param {Number} number of decimal digits
	 * @param {String} the decimal character, default is "."
	 * @type {Double} the currency value as float.
	 */
	toCurrency : function(groupchar, digits, decimalchar)
	{
		groupchar = groupchar || ",";
		decimalchar = decimalchar || ".";
		digits = typeof(digits) == "undefined" ? 2 : digits;

		var exp = new RegExp("^\\s*([-\\+])?(((\\d+)\\" + groupchar + ")*)(\\d+)"
			+ ((digits > 0) ? "(\\" + decimalchar + "(\\d{1," + digits + "}))?" : "")
			+ "\\s*$");
		var m = this.match(exp);
		if (m == null)
			return null;
		var intermed = m[2] + m[5] ;
		var cleanInput = m[1] + intermed.replace(
				new RegExp("(\\" + groupchar + ")", "g"), "")
								+ ((digits > 0) ? "." + m[7] : "");
		var num = parseFloat(cleanInput);
		return (isNaN(num) ? null : num);
	},

	/**
	 * Converts the string to a date by finding values that matches the
	 * date format pattern.
	 * @param string date format pattern, e.g. MM-dd-yyyy
	 * @return {Date} the date extracted from the string
	 */
	toDate : function(format)
	{
		return Date.SimpleParse(this, format);
	}
});

/**
 * @class Event extensions.
 */
Object.extend(Event,
{
	/**
	 * Register a function to be executed when the page is loaded.
	 * Note that the page is only loaded if all resources (e.g. images)
	 * are loaded.
	 *
	 * Example: Show an alert box with message "Page Loaded!" when the
	 * page finished loading.
	 * <code>
	 * Event.OnLoad(function(){ alert("Page Loaded!"); });
	 * </code>
	 *
	 * @param {Function} function to execute when page is loaded.
	 */
	OnLoad : function (fn)
	{
		// opera onload is in document, not window
		var w = document.addEventListener &&
					!window.addEventListener ? document : window;
		Event.observe(w,'load',fn);
	},

	/**
	 * @param {Event} a keyboard event
	 * @return {Number} the Unicode character code generated by the key
	 * that was struck.
	 */
	keyCode : function(e)
	{
	   return e.keyCode != null ? e.keyCode : e.charCode
	},

	/**
	 * @param {String} event type or event name.
	 * @return {Boolean} true if event type is of HTMLEvent, false
	 * otherwise
	 */
	isHTMLEvent : function(type)
	{
		var events = ['abort', 'blur', 'change', 'error', 'focus',
					'load', 'reset', 'resize', 'scroll', 'select',
					'submit', 'unload'];
		return events.include(type);
	},

	/**
	 * @param {String} event type or event name
	 * @return {Boolean} true if event type is of MouseEvent,
	 * false otherwise
	 */
	isMouseEvent : function(type)
	{
		var events = ['click', 'mousedown', 'mousemove', 'mouseout',
					'mouseover', 'mouseup'];
		return events.include(type);
	},

	/**
	 * Dispatch the DOM event of a given <tt>type</tt> on a DOM
	 * <tt>element</tt>. Only HTMLEvent and MouseEvent can be
	 * dispatched, keyboard events or UIEvent can not be dispatch
	 * via javascript consistently.
	 * For the "submit" event the submit() method is called.
	 * @param {Object} element id string or a DOM element.
	 * @param {String} event type to dispatch.
	 */
	fireEvent : function(element,type)
	{
		element = $(element);
		if(type == "submit")
			return element.submit();
		if(document.createEvent)
        {
			if(Event.isHTMLEvent(type))
			{
				var event = document.createEvent('HTMLEvents');
	            event.initEvent(type, true, true);
			}
			else if(Event.isMouseEvent(type))
			{
				var event = document.createEvent('MouseEvents');
				if (event.initMouseEvent)
		        {
					event.initMouseEvent(type,true,true,
						document.defaultView, 1, 0, 0, 0, 0, false,
								false, false, false, 0, null);
		        }
		        else
		        {
		            // Safari
		            // TODO we should be initialising other mouse-event related attributes here
		            event.initEvent(type, true, true);
		        }
			}
            element.dispatchEvent(event);
        }
        else if(document.createEventObject)
        {
        	var evObj = document.createEventObject();
            element.fireEvent('on'+type, evObj);
        }
        else if(typeof(element['on'+type]) == "function")
            element['on'+type]();
	}
});



Object.extend(Date.prototype,
{
	SimpleFormat: function(format, data)
	{
		data = data || {};
		var bits = new Array();
		bits['d'] = this.getDate();
		bits['dd'] = String(this.getDate()).zerofill(2);

		bits['M'] = this.getMonth()+1;
		bits['MM'] = String(this.getMonth()+1).zerofill(2);
		if(data.AbbreviatedMonthNames)
			bits['MMM'] = data.AbbreviatedMonthNames[this.getMonth()];
		if(data.MonthNames)
			bits['MMMM'] = data.MonthNames[this.getMonth()];
		var yearStr = "" + this.getFullYear();
		yearStr = (yearStr.length == 2) ? '19' + yearStr: yearStr;
		bits['yyyy'] = yearStr;
		bits['yy'] = bits['yyyy'].toString().substr(2,2);

		// do some funky regexs to replace the format string
		// with the real values
		var frm = new String(format);
		for (var sect in bits)
		{
			var reg = new RegExp("\\b"+sect+"\\b" ,"g");
			frm = frm.replace(reg, bits[sect]);
		}
		return frm;
	},

	toISODate : function()
	{
		var y = this.getFullYear();
		var m = String(this.getMonth() + 1).zerofill(2);
		var d = String(this.getDate()).zerofill(2);
		return String(y) + String(m) + String(d);
	}
});

Object.extend(Date,
{
	SimpleParse: function(value, format)
	{
		val=String(value);
		format=String(format);

		if(val.length <= 0) return null;

		if(format.length <= 0) return new Date(value);

		var isInteger = function (val)
		{
			var digits="1234567890";
			for (var i=0; i < val.length; i++)
			{
				if (digits.indexOf(val.charAt(i))==-1) { return false; }
			}
			return true;
		};

		var getInt = function(str,i,minlength,maxlength)
		{
			for (var x=maxlength; x>=minlength; x--)
			{
				var token=str.substring(i,i+x);
				if (token.length < minlength) { return null; }
				if (isInteger(token)) { return token; }
			}
			return null;
		};

		var i_val=0;
		var i_format=0;
		var c="";
		var token="";
		var token2="";
		var x,y;
		var now=new Date();
		var year=now.getFullYear();
		var month=now.getMonth()+1;
		var date=1;

		while (i_format < format.length)
		{
			// Get next token from format string
			c=format.charAt(i_format);
			token="";
			while ((format.charAt(i_format)==c) && (i_format < format.length))
			{
				token += format.charAt(i_format++);
			}

			// Extract contents of value based on format token
			if (token=="yyyy" || token=="yy" || token=="y")
			{
				if (token=="yyyy") { x=4;y=4; }
				if (token=="yy")   { x=2;y=2; }
				if (token=="y")    { x=2;y=4; }
				year=getInt(val,i_val,x,y);
				if (year==null) { return null; }
				i_val += year.length;
				if (year.length==2)
				{
					if (year > 70) { year=1900+(year-0); }
					else { year=2000+(year-0); }
				}
			}

			else if (token=="MM"||token=="M")
			{
				month=getInt(val,i_val,token.length,2);
				if(month==null||(month<1)||(month>12)){return null;}
				i_val+=month.length;
			}
			else if (token=="dd"||token=="d")
			{
				date=getInt(val,i_val,token.length,2);
				if(date==null||(date<1)||(date>31)){return null;}
				i_val+=date.length;
			}
			else
			{
				if (val.substring(i_val,i_val+token.length)!=token) {return null;}
				else {i_val+=token.length;}
			}
		}

		// If there are any trailing characters left in the value, it doesn't match
		if (i_val != val.length) { return null; }

		// Is date valid for month?
		if (month==2)
		{
			// Check for leap year
			if ( ( (year%4==0)&&(year%100 != 0) ) || (year%400==0) ) { // leap year
				if (date > 29){ return null; }
			}
			else { if (date > 28) { return null; } }
		}

		if ((month==4)||(month==6)||(month==9)||(month==11))
		{
			if (date > 30) { return null; }
		}

		var newdate=new Date(year,month-1,date, 0, 0, 0);
		return newdate;
	}
});