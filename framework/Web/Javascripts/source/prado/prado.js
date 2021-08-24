/*! PRADO main js file | github.com/pradosoft/prado */

/*
 * Polyfill for ECMAScript5's bind() function.
 * ----------
 * Adds compatible .bind() function; needed for Internet Explorer < 9
 * Source: https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/Function/bind
 */

if (!Function.prototype.bind) {
  Function.prototype.bind = function (oThis) {
    if (typeof this !== "function") {
      // closest thing possible to the ECMAScript 5 internal IsCallable function
      throw new TypeError("Function.prototype.bind - what is trying to be bound is not callable");
    }

    var aArgs = Array.prototype.slice.call(arguments, 1),
        fToBind = this,
        fNOP = function () {},
        fBound = function () {
          return fToBind.apply(this instanceof fNOP && oThis
                                 ? this
                                 : oThis,
                               aArgs.concat(Array.prototype.slice.call(arguments)));
        };

    fNOP.prototype = this.prototype;
    fBound.prototype = new fNOP();

    return fBound;
  };
}

/*
 * Low Pro JQ
 * ----------
 *
 * Author: Dan Webb (dan@danwebb.net)
 * GIT: github.com:danwrong/low-pro-for-jquery.git
 * Download: http://github.com/danwrong/low-pro-for-jquery/tree/master/src/lowpro.jquery.js?raw=true
 *
 * A jQuery port of the Low Pro behavior framework that was originally written for Prototype.
 *
 * Prado actually uses it as a base to emulate OOP subclassing, inheritance and contructor events.
 * The "behaviour" and the "Remote" bits are not used and have been commented out.
 */

(function($) {

  var addMethods = function(source) {
    var ancestor   = this.superclass && this.superclass.prototype;
    var properties = $.keys(source);

    if (!$.keys({ toString: true }).length) properties.push("toString", "valueOf");

    for (var i = 0, length = properties.length; i < length; i++) {
      var property = properties[i], value = source[property];
      if (ancestor && $.isFunction(value) && $.argumentNames(value)[0] == "$super") {

        var method = value, value = $.extend($.wrap((function(m) {
          return function() { return ancestor[m].apply(this, arguments) };
        })(property), method), {
          valueOf:  function() { return method },
          toString: function() { return method.toString() }
        });
      }
      this.prototype[property] = value;
    }

    return this;
  };

  $.extend({
    keys: function(obj) {
      var keys = [];
      for (var key in obj) keys.push(key);
      return keys;
    },

    argumentNames: function(func) {
      var names = func.toString().match(/^[\s\(]*function[^(]*\((.*?)\)/)[1].split(/, ?/);
      return names.length == 1 && !names[0] ? [] : names;
    },

    bind: function(func, scope) {
      return function() {
        return func.apply(scope, $.makeArray(arguments));
      };
    },

    wrap: function(func, wrapper) {
      var __method = func;
      return function() {
        return wrapper.apply(this, [$.bind(__method, this)].concat($.makeArray(arguments)));
      };
    },

    klass: function() {
      var parent = null, properties = $.makeArray(arguments);
      if ($.isFunction(properties[0])) parent = properties.shift();

      var klass = function() {
        this.initialize.apply(this, arguments);
      };

      klass.superclass = parent;
      klass.subclasses = [];
      klass.addMethods = addMethods;

      if (parent) {
        var subclass = function() { };
        subclass.prototype = parent.prototype;
        klass.prototype = new subclass;
        parent.subclasses.push(klass);
      }

      for (var i = 0; i < properties.length; i++)
        klass.addMethods(properties[i]);

      if (!klass.prototype.initialize)
        klass.prototype.initialize = function() {};

      klass.prototype.constructor = klass;

      return klass;
    },
    delegate: function(rules) {
      return function(e) {
        var target = $(e.target), parent = null;
        for (var selector in rules) {
          if (target.is(selector) || ((parent = target.parents(selector)) && parent.length > 0)) {
            return rules[selector].apply(this, [parent || target].concat($.makeArray(arguments)));
          }
          parent = null;
        }
      };
    }
  });
/*
  var bindEvents = function(instance) {
    for (var member in instance) {
      if (member.match(/^on(.+)/) && typeof instance[member] == 'function') {
        instance.element.live(RegExp.$1, {'behavior': instance}, instance[member]);
      }
    }
  };

  var behaviorWrapper = function(behavior) {
    return $.klass(behavior, {
      initialize: function($super, element, args) {
        this.element = element;
        if ($super) $super.apply(this, args);
      },
      trigger: function(eventType, extraParameters) {
        var parameters = [this].concat(extraParameters);
        this.element.trigger(eventType, parameters);
      }
    });
  };

  var attachBehavior = function(el, behavior, args) {
      var wrapper = behaviorWrapper(behavior);
      var instance = new wrapper(el, args);

      bindEvents(instance);

      if (!behavior.instances) behavior.instances = [];

      behavior.instances.push(instance);

      return instance;
  };


  $.fn.extend({
    attach: function() {
      var args = $.makeArray(arguments), behavior = args.shift();
      attachBehavior(this, behavior, args);
      return this;
    },
    delegate: function(type, rules) {
      return this.bind(type, $.delegate(rules));
    },
    attached: function(behavior) {
      var instances = [];

      if (!behavior.instances) return instances;

      this.each(function(i, element) {
        $.each(behavior.instances, function(i, instance) {
          if (instance.element.get(0) == element) instances.push(instance);
        });
      });

      return instances;
    },
    firstAttached: function(behavior) {
      return this.attached(behavior)[0];
    }
  });

  Remote = $.klass({
    initialize: function(options) {
      if (this.element.attr('nodeName') == 'FORM') this.element.attach(Remote.Form, options);
      else this.element.attach(Remote.Link, options);
    }
  });

  Remote.Base = $.klass({
    initialize : function(options) {
      this.options = $.extend(true, {}, options || {});
    },
    _makeRequest : function(options) {
      $.ajax(options);
      return false;
    }
  });

  Remote.Link = $.klass(Remote.Base, {
    onclick: function(e) {
      var options = $.extend({
        url: $(this).attr('href'),
        type: 'GET'
      }, this.options);
      return e.data.behavior._makeRequest(e.data.behavior.options);
    }
  });

  Remote.Form = $.klass(Remote.Base, {
    onclick: function(e) {
      var target = e.target;

      if ($.inArray(target.nodeName.toLowerCase(), ['input', 'button']) >= 0 && target.type.match(/submit|image/))
        e.data.behavior._submitButton = target;
    },
    onsubmit: function(e) {
      var elm = $(this), data = elm.serializeArray();

      if (e.data.behavior._submitButton) data.push({
        name: e.data.behavior._submitButton.name,
        value: e.data.behavior._submitButton.value
      });

      var options = $.extend({
        url : elm.attr('action'),
        type : elm.attr('method') || 'GET',
        data : data
      }, e.data.behavior.options);

      e.data.behavior._makeRequest(options);

      return false;
    }
  });

  $.ajaxSetup({
    beforeSend: function(xhr) {
      if (!this.dataType)
        xhr.setRequestHeader("Accept", "text/javascript, text/html, application/xml, text/xml, *\/*");
    }
  });
*/
})(jQuery);


/**
 * Prado base namespace
 * @namespace Prado
 */
var Prado =
{
	/**
	 * Version of Prado clientscripts
	 * @var Version
	 */
	Version: '4.1.2',

	/**
	 * Registry for Prado components
	 * @var Registry
	 */
	Registry: {}
};

Prado.RequestManager =
{
	FIELD_POSTBACK_TARGET : 'PRADO_POSTBACK_TARGET',

	FIELD_POSTBACK_PARAMETER : 'PRADO_POSTBACK_PARAMETER'
};
/**
 * Performs a PostBack using javascript.
 * @function Prado.PostBack
 * @param options - Postback options
 * @param event - Event that triggered this postback
 * @... {string} FormID - Form that should be posted back
 * @... {optional boolean} CausesValidation - Validate before PostBack if true
 * @... {optional string} ValidationGroup - Group to Validate
 * @... {optional string} ID - Validation ID
 * @... {optional string} PostBackUrl - Postback URL
 * @... {optional boolean} TrackFocus - Keep track of focused element if true
 * @... {string} EventTarget - Id of element that triggered PostBack
 * @... {string} EventParameter - EventParameter for PostBack
 */
Prado.PostBack = jQuery.klass(
{
	options : {},

	initialize: function(options, event)
	{
		jQuery.extend(this.options, options || {});
		this.event = event;
		this.doPostBack();
	},

	getForm : function()
	{
		return jQuery("#" + this.options['FormID']).get(0);
	},

	doPostBack : function()
	{
		var form = this.getForm();
		if(this.options['CausesValidation'] && typeof(Prado.Validation) != "undefined")
		{
			if(!Prado.Validation.validate(this.options['FormID'], this.options['ValidationGroup'], jQuery("#" + this.options['ID'])))
				return this.event.preventDefault();
		}

		if(this.options['PostBackUrl'] && this.options['PostBackUrl'].length > 0)
			form.action = this.options['PostBackUrl'];

		if(this.options['TrackFocus'])
		{
			var lastFocus = jQuery('#PRADO_LASTFOCUS');
			if(lastFocus)
			{
				var active = document.activeElement; //where did this come from
				if(active)
					lastFocus.value = active.id;
				else
					lastFocus.value = this.options['EventTarget'];
			}
		}

		var input=null;
		if(this.options.EventTarget)
		{
			input = document.createElement("input");
			input.setAttribute("type", "hidden");
			input.setAttribute("name", Prado.RequestManager.FIELD_POSTBACK_TARGET);
			input.setAttribute("value", this.options.EventTarget);
			form.appendChild(input);
		}
		if(this.options.EventParameter)
		{
			input = document.createElement("input");
			input.setAttribute("type", "hidden");
			input.setAttribute("name", Prado.RequestManager.FIELD_POSTBACK_PARAMETER);
			input.setAttribute("value", this.options.EventParameter);
			form.appendChild(input);
		}

		jQuery(form).trigger('submit');
	}
});

/**
 * Prado utilities to manipulate DOM elements.
 * @object Prado.Element
 */
Prado.Element =
{
	/**
	 * Executes a jQuery method on a particular element.
	 * @function ?
	 * @param {string} element - Element id
	 * @param {string} method - method name
	 * @param {array} value - method parameters
	 */
	j: function(element, method, params)
	{
		var obj=jQuery("#" + element);
		obj[method].apply(obj, params);
	},

	/**
	 * Select options from a selectable element.
	 * @function ?
	 * @param {string} element - Element id
	 * @param {string} method - Name of any {@link Prado.Element.Selection} method
	 * @param {array|boolean|string} value - Values that should be selected
	 * @param {int} total - Number of elements
	 */
	select : function(element, method, value, total)
	{
		var el = jQuery("#" + element).get(0);
		if(!el) return;
		var selection = Prado.Element.Selection;
		if(typeof(selection[method]) == "function")
		{
			var control = selection.isSelectable(el) ? [el] : selection.getListElements(element,total);
			selection[method](control, value);
		}
	},

	/**
	 * Sets an attribute of a DOM element.
	 * @function ?
	 * @param {string} element - Element id
	 * @param {string} attribute - Name of attribute
	 * @param {string} value - Value of attribute
	 */
	setAttribute : function(element, attribute, value)
	{
		var el = jQuery("#" + element);
		if(!el) return;
		if((attribute == "disabled" || attribute == "multiple" || attribute == "readonly" || attribute == "href") && value==false)
			el.removeAttr(attribute);
		else if(attribute.match(/^on/i)) //event methods
		{
			try
			{
				eval("(func = function(event){"+value+"})");
				el.get(0)[attribute] = func;
			}
			catch(e)
			{
				debugger;
				throw "Error in evaluating '"+value+"' for attribute "+attribute+" for element "+element;
			}
		}
		else
			el.attr(attribute, value);
	},

	scrollTo : function(element, options)
	{
		var op = {
			duration : 500,
			offset : 50
		};
		jQuery.extend(op, options || {});
		jQuery('html, body').animate({
			scrollTop: jQuery("#"+element).offset().top - op.offset
		}, op.duration);
	},

	focus : function(element)
	{
		if(jQuery.active > 0)
		{
			setTimeout(function(){
				jQuery("#"+element).focus();
			}, 100);
		} else {
			jQuery("#"+element).focus();
		}
	},

	/**
	 * Sets the options for a select element.
	 * @function ?
	 * @param {string} element - Element id
	 * @param {array[]} options - Array of options, each an array of structure
	 *   [ "optionText" , "optionValue" , "optionGroup" ]
	 */
	setOptions : function(element, options)
	{
		var el = jQuery("#" + element).get(0);
		var previousGroup = null;
		var optGroup=null;
		if(el && el.tagName.toLowerCase() == "select")
		{
			while(el.childNodes.length > 0)
				el.removeChild(el.lastChild);

			var optDom = Prado.Element.createOptions(options);
			for(var i = 0; i < optDom.length; i++)
				el.appendChild(optDom[i]);
		}
	},

	/**
	 * Create opt-group options from an array of options.
	 * @function {array} ?
	 * @param {array[]} options - Array of options, each an array of structure
	 *   [ "optionText" , "optionValue" , "optionGroup" ]
	 * @returns Array of option DOM elements
	 */
	createOptions : function(options)
	{
		var previousGroup = null;
		var optgroup=null;
		var result = [];
		for(var i = 0; i<options.length; i++)
		{
			var option = options[i];
			if(option.length > 2)
			{
				var group = option[2];
				if(group!=previousGroup)
				{
					if(previousGroup!=null && optgroup!=null)
					{
						result.push(optgroup);
						previousGroup=null;
						optgroup=null;
					}
					optgroup = document.createElement('optgroup');
					optgroup.label = group;
					previousGroup = group;
				}
			}
			var opt = document.createElement('option');
			opt.text = option[0];
			opt.innerHTML = option[0];
			opt.value = option[1];
			if(optgroup!=null)
				optgroup.appendChild(opt);
			else
				result.push(opt);
		}
		if(optgroup!=null)
			result.push(optgroup);
		return result;
	},

	/**
	 * Replace a DOM element either with given content or
	 * with content from a CallBack response boundary
	 * using a replacement method.
	 * @function ?
	 * @param {string|element} element - DOM element or element id
	 * @param {optional string} content - New content of element
	 * @param {optional string} boundary - Boundary of new content
	 * @param {optional boolean} self - Whether to replace itself or just the inner content
	 */
	replace : function(element, content, boundary, self)
	{
		if(boundary)
		{
			var result = this.extractContent(boundary);
			if(result != null)
				content = result;
		}
		if(self)
		  jQuery('#'+element).replaceWith(content);
		else
		  jQuery('#'+element).html(content);
	},

	/**
	 * Appends a javascript block to the document.
	 * @function ?
	 * @param {string} boundary - Boundary containing the javascript code
	 */
	appendScriptBlock : function(boundary)
	{
		var content = this.extractContent(boundary);
		if(content == null)
			return;

		var el   = document.createElement("script");
		el.type  = "text/javascript";
		el.id    = 'inline_' + boundary;
		el.text  = content;

		(document.getElementsByTagName('head')[0] || document.documentElement).appendChild(el);
		el.parentNode.removeChild(el);
	},

	/**
	 * Evaluate a javascript snippet from a string.
	 * @function ?
	 * @param {string} content - String containing the script
	 * @param {string} boundary - Boundary containing the script
	 */
	evaluateScript : function(content, boundary)
	{
		if(boundary)
		{
			var result = this.extractContent(boundary);
			if(result != null)
				content = result;
		}

		try
		{
			jQuery.globalEval(content);
		}
		catch(e)
		{
			if(typeof(Logger) != "undefined")
				Logger.error('Error during evaluation of script "'+content+'"');
			else
				debugger;
			throw e;
		}
	}
};

/**
 * Utilities for selections.
 * @object Prado.Element.Selection
 */
Prado.Element.Selection =
{
	/**
	 * Check if an DOM element can be selected.
	 * @function {boolean} ?
	 * @param {element} el - DOM elemet
	 * @returns true if element is selectable
	 */
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

	/**
	 * Set checked attribute of a checkbox or radiobutton to value.
	 * @function {boolean} ?
	 * @param {element} el - DOM element
	 * @param {boolean} value - New value of checked attribute
	 * @returns New value of checked attribute
	 */
	inputValue : function(el, value)
	{
		switch(el.type.toLowerCase())
		{
			case 'checkbox':
			case 'radio':
			return el.checked = value;
		}
	},

	/**
	 * Set selected attribute for elements options by value.
	 * If value is boolean, all elements options selected attribute will be set
	 * to value. Otherwhise all options that have the given value will be selected.
	 * @function ?
	 * @param {element[]} elements - Array of selectable DOM elements
	 * @param {boolean|string} value - Value of options that should be selected or boolean value of selection status
	 */
	selectValue : function(elements, value)
	{
		jQuery.each(elements, function(idx, el)
		{
			jQuery.each(el.options, function(idx, option)
			{
				if(typeof(value) == "boolean")
					option.selected = value;
				else if(option.value == value)
					option.selected = true;
			});
		})
	},

	/**
	 * Set selected attribute for elements options by array of values.
	 * @function ?
	 * @param {element[]} elements - Array of selectable DOM elements
	 * @param {string[]} value - Array of values to select
	 */
	selectValues : function(elements, values)
	{
		var selection = this;
		jQuery.each(values, function(idx, value)
		{
			selection.selectValue(elements,value);
		})
	},

	/**
	 * Set selected attribute for elements options by option index.
	 * @function ?
	 * @param {element[]} elements - Array of selectable DOM elements
	 * @param {int} index - Index of option to select
	 */
	selectIndex : function(elements, index)
	{
		jQuery.each(elements, function(idx, el)
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

	/**
	 * Set selected attribute to true for all elements options.
	 * @function ?
	 * @param {element[]} elements - Array of selectable DOM elements
	 */
	selectAll : function(elements)
	{
		jQuery.each(elements, function(idx, el)
		{
			if(el.type.toLowerCase() != 'select-one')
			{
				jQuery.each(el.options, function(idx, option)
				{
					option.selected = true;
				})
			}
		})
	},

	/**
	 * Toggle the selected attribute for elements options.
	 * @function ?
	 * @param {element[]} elements - Array of selectable DOM elements
	 */
	selectInvert : function(elements)
	{
		jQuery.each(elements, function(idx, el)
		{
			if(el.type.toLowerCase() != 'select-one')
			{
				jQuery.each(el.options, function(idx, option)
				{
					option.selected = !option.selected;
				})
			}
		})
	},

	/**
	 * Set selected attribute for elements options by array of option indices.
	 * @function ?
	 * @param {element[]} elements - Array of selectable DOM elements
	 * @param {int[]} indices - Array of option indices to select
	 */
	selectIndices : function(elements, indices)
	{
		var selection = this;
		jQuery.each(indices, function(idx, index)
		{
			selection.selectIndex(elements,index);
		})
	},

	/**
	 * Unselect elements.
	 * @function ?
	 * @param {element[]} elements - Array of selectable DOM elements
	 */
	selectClear : function(elements)
	{
		jQuery.each(elements, function(idx, el)
		{
			el.selectedIndex = -1;
		})
	},

	/**
	 * Get list elements of an element.
	 * @function {element[]} ?
	 * @param {element[]} elements - Array of selectable DOM elements
	 * @param {int} total - Number of list elements to return
	 * @returns Array of list DOM elements
	 */
	getListElements : function(element, total)
	{
		var elements = new Array();
		var el;
		for(var i = 0; i < total; i++)
		{
			el = jQuery("#"+element+"_c"+i).get(0);
			if(el)
				elements.push(el);
		}
		return elements;
	},

	/**
	 * Set checked attribute of elements by value.
	 * If value is boolean, checked attribute will be set to value.
	 * Otherwhise all elements that have the given value will be checked.
	 * @function ?
	 * @param {element[]} elements - Array of checkable DOM elements
	 * @param {boolean|String} value - Value that should be checked or boolean value of checked status
	 *
	 */
	checkValue : function(elements, value)
	{
		jQuery.each(elements, function(idx, el)
		{
			if(typeof(value) == "boolean")
				el.checked = value;
			else if(el.value == value)
				el.checked = true;
		});
	},

	/**
	 * Set checked attribute of elements by array of values.
	 * @function ?
	 * @param {element[]} elements - Array of checkable DOM elements
	 * @param {string[]} values - Values that should be checked
	 *
	 */
	checkValues : function(elements, values)
	{
		var selection = this;
		jQuery(values).each(function(idx, value)
		{
			selection.checkValue(elements, value);
		})
	},

	/**
	 * Set checked attribute of elements by list index.
	 * @function ?
	 * @param {element[]} elements - Array of checkable DOM elements
	 * @param {int} index - Index of element to set checked
	 */
	checkIndex : function(elements, index)
	{
		for(var i = 0; i<elements.length; i++)
		{
			if(i == index)
				elements[i].checked = true;
		}
	},

	/**
	 * Set checked attribute of elements by array of list indices.
	 * @function ?
	 * @param {element[]} elements - Array of selectable DOM elements
	 * @param {int[]} indices - Array of list indices to set checked
	 */
	checkIndices : function(elements, indices)
	{
		var selection = this;
		jQuery.each(indices, function(idx, index)
		{
			selection.checkIndex(elements, index);
		})
	},

	/**
	 * Uncheck elements.
	 * @function ?
	 * @param {element[]} elements - Array of checkable DOM elements
	 */
	checkClear : function(elements)
	{
		jQuery.each(elements, function(idx, el)
		{
			el.checked = false;
		});
	},

	/**
	 * Set checked attribute of all elements to true.
	 * @function ?
	 * @param {element[]} elements - Array of checkable DOM elements
	 */
	checkAll : function(elements)
	{
		jQuery.each(elements, function(idx, el)
		{
			el.checked = true;
		})
	},

	/**
	 * Toggle the checked attribute of elements.
	 * @function ?
	 * @param {element[]} elements - Array of selectable DOM elements
	 */
	checkInvert : function(elements)
	{
		jQuery.each(elements, function(idx, el)
		{
			el.checked = !el.checked;
		})
	}
};

jQuery.extend(String.prototype, {

	/**
	 * Add padding to string
	 * @function {string} ?
	 * @param {string} side - "left" to pad the string on the left, "right" to pad right.
	 * @param {int} len - Minimum string length.
	 * @param {string} chr - Character(s) to pad
	 * @returns Padded string
	 */
	pad : function(side, len, chr) {
		if (!chr) chr = ' ';
		var s = this;
		var left = side.toLowerCase()=='left';
		while (s.length<len) s = left? chr + s : s + chr;
		return s;
	},

	/**
	 * Add left padding to string
	 * @function {string} ?
	 * @param {int} len - Minimum string length.
	 * @param {string} chr - Character(s) to pad
	 * @returns Padded string
	 */
	padLeft : function(len, chr) {
		return this.pad('left',len,chr);
	},

	/**
	 * Add right padding to string
	 * @function {string} ?
	 * @param {int} len - Minimum string length.
	 * @param {string} chr - Character(s) to pad
	 * @returns Padded string
	 */
	padRight : function(len, chr) {
		return this.pad('right',len,chr);
	},

	/**
	 * Add zeros to the right of string
	 * @function {string} ?
	 * @param {int} len - Minimum string length.
	 * @returns Padded string
	 */
	zerofill : function(len) {
		return this.padLeft(len,'0');
	},

	/**
	 * Remove white spaces from both ends of string.
	 * @function {string} ?
	 * @returns Trimmed string
	 */
	trim : function() {
		return this.replace(/^\s+|\s+$/g,'');
	},

	/**
	 * Remove white spaces from the left side of string.
	 * @function {string} ?
	 * @returns Trimmed string
	 */
	trimLeft : function() {
		return this.replace(/^\s+/,'');
	},

	/**
	 * Remove white spaces from the right side of string.
	 * @function {string} ?
	 * @returns Trimmed string
	 */
	trimRight : function() {
		return this.replace(/\s+$/,'');
	},

	/**
	 * Convert period separated function names into a function reference.
	 * <br />Example:
	 * <pre>
	 * "Prado.AJAX.Callback.Action.setValue".toFunction()
	 * </pre>
	 * @function {function} ?
	 * @returns Reference to the corresponding function
	 */
	toFunction : function()
	{
		var commands = this.split(/\./);
		var command = window;
		jQuery(commands).each(function(idx, action)
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
	 * Convert string into integer, returns null if not integer.
	 * @function {int} ?
	 * @returns Integer, null if string does not represent an integer.
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
	 * Convert string into a double/float value. <b>Internationalization
	 * is not supported</b>
	 * @function {double} ?
	 * @param {string} decimalchar - Decimal character, defaults to "."
	 * @returns Double, null if string does not represent a float value
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
	 * Convert strings that represent a currency value to float.
	 * E.g. "10,000.50" will become "10000.50". The number
	 * of dicimal digits, grouping and decimal characters can be specified.
	 * <i>The currency input format is <b>very</b> strict, null will be returned if
	 * the pattern does not match</i>.
	 * @function {double} ?
	 * @param {string} groupchar - Grouping character, defaults to ","
	 * @param {int} digits - Number of decimal digits
	 * @param {string} decimalchar - Decimal character, defaults to "."
	 * @returns Double, null if string does not represent a currency value
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
	}
});

jQuery.extend(Date.prototype,
{
	/**
	 * SimpleFormat
	 * @function ?
	 * @param {string} format - TODO
	 * @param {string} data - TODO
	 * @returns TODO
	 */
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

	/**
	 * toISODate
	 * @function {string} ?
	 * @returns TODO
	 */
	toISODate : function()
	{
		var y = this.getFullYear();
		var m = String(this.getMonth() + 1).zerofill(2);
		var d = String(this.getDate()).zerofill(2);
		return String(y) + String(m) + String(d);
	}
});

jQuery.extend(Date,
{
	/**
	 * SimpleParse
	 * @function ?
	 * @param {string} format - TODO
	 * @param {string} data - TODO
	 * @returns TODO
	 */
	SimpleParse: function(value, format)
	{
		var val=String(value);
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
