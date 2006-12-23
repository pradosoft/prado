/*  Prototype JavaScript framework, version <%= PROTOTYPE_VERSION %>
 *  (c) 2005 Sam Stephenson <sam@conio.net>
 *
 *  Prototype is freely distributable under the terms of an MIT-style license.
 *  For details, see the Prototype web site: http://prototype.conio.net/
 *
/*--------------------------------------------------------------------------*/

var Prototype = {
  Version: '1.50',
  ScriptFragment: '(?:<script.*?>)((\n|\r|.)*?)(?:<\/script>)',
  
  emptyFunction: function() {},
  K: function(x) {return x}
}

/*
<%= include 'base.js', 'string.js' %>

<%= include 'enumerable.js', 'array.js', 'hash.js', 'range.js' %>

<%= include 'ajax.js', 'dom.js', 'selector.js', 'form.js', 'event.js', 'position.js' %>

*/

var Class = {
  create: function() {
    return function() { 
      this.initialize.apply(this, arguments);
    }
  }
}

var Abstract = new Object();

Object.extend = function(destination, source) {
  for (var property in source) {
    destination[property] = source[property];
  }
  return destination;
}

Object.inspect = function(object) {
  try {
    if (object == undefined) return 'undefined';
    if (object == null) return 'null';
    return object.inspect ? object.inspect() : object.toString();
  } catch (e) {
    if (e instanceof RangeError) return '...';
    throw e;
  }
}

Function.prototype.bind = function() {
  var __method = this, args = $A(arguments), object = args.shift();
  return function() {
    return __method.apply(object, args.concat($A(arguments)));
  }
}

Function.prototype.bindAsEventListener = function(object) {
  var __method = this;
  return function(event) {
    return __method.call(object, event || window.event);
  }
}

Object.extend(Number.prototype, {
  toColorPart: function() {
    var digits = this.toString(16);
    if (this < 16) return '0' + digits;
    return digits;
  },

  succ: function() {
    return this + 1;
  },
  
  times: function(iterator) {
    $R(0, this, true).each(iterator);
    return this;
  }
});

var Try = {
  these: function() {
    var returnValue;

    for (var i = 0; i < arguments.length; i++) {
      var lambda = arguments[i];
      try {
        returnValue = lambda();
        break;
      } catch (e) {}
    }

    return returnValue;
  }
}

/*--------------------------------------------------------------------------*/

var PeriodicalExecuter = Class.create();
PeriodicalExecuter.prototype = {
  initialize: function(callback, frequency) {
    this.callback = callback;
    this.frequency = frequency;
    this.currentlyExecuting = false;

    this.registerCallback();
  },

  registerCallback: function() {
    setInterval(this.onTimerEvent.bind(this), this.frequency * 1000);
  },

  onTimerEvent: function() {
    if (!this.currentlyExecuting) {
      try { 
        this.currentlyExecuting = true;
        this.callback(); 
      } finally { 
        this.currentlyExecuting = false;
      }
    }
  }
}



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

/*
 * Signals and Slots for Prototype: Easy custom javascript events
 * http://tetlaw.id.au/view/blog/signals-and-slots-for-prototype-easy-custom-javascript-events
 * Andrew Tetlaw
 * Version 1.2 (2006-06-19)
 *
 * http://creativecommons.org/licenses/by-sa/2.5/
 *
Signal = {
	throwErrors : true,
	MT : function(){ return true },
	connect : function(obj1, func1, obj2, func2, options) {
		var options = Object.extend({
			connectOnce : false,
			before : false,
			mutate : function() {return arguments;}
		}, options || {});
		if(typeof func1 != 'string' || typeof func2 != 'string') return;

		var sigObj = obj1 || window;
		var slotObj = obj2 || window;
		var signame = func1+'__signal_';
		var slotsname = func1+'__slots_';
		if(!sigObj[signame]) {
			// having the slotFunc in a var and setting it by using an anonymous function in this way
			// is apparently a good way to prevent memory leaks in IE if the objects are DOM nodes.
			var slotFunc = function() {
				var args = [];
				for(var x = 0; x < arguments.length; x++){
					args.push(arguments[x]);
				}
				args = options.mutate.apply(null,args)
				var result;
				if(!options.before) result = sigObj[signame].apply(sigObj,arguments); //default: call sign before slot
				sigObj[slotsname].each(function(slot){
					try {
						if(slot && slot[0]) { // testing for null, a disconnect may have nulled this slot
							slot[0][slot[1]].apply(slot[0],args); //[0] = obj, [1] = func name
						}
					} catch(e) {
						if(Signal.throwErrors) throw e;
					}
				});
				if(options.before) result = sigObj[signame].apply(sigObj,arguments); //call slot before sig
				return result; //return sig result
			};
			(function() {
				sigObj[slotsname] = $A([]);
				sigObj[signame] = sigObj[func1] || Signal.MT;
				sigObj[func1] = slotFunc;
			})();
		}
		var con = (sigObj[slotsname].length > 0) ?
					(options.connectOnce ? !sigObj[slotsname].any(function(slot) { return (slot[0] == slotObj && slot[1] == func2) }) : true) :
					true;
		if(con) {
			sigObj[slotsname].push([slotObj,func2]);
		}
	},
	connectOnce : function(obj1, func1, obj2, func2, options) {
		Signal.connect(obj1, func1, obj2, func2, Object.extend(options || {}, {connectOnce : true}))
	},
	disconnect : function(obj1, func1, obj2, func2, options) {
		var options = Object.extend({
			disconnectAll : false
		}, options || {});
		if(typeof func1 != 'string' || typeof func2 != 'string') return;

		var sigObj = obj1 || window;
		var slotObj = obj2 || window;
		var signame = func1+'__signal_';
		var slotsname = func1+'__slots_';

		// I null them in this way so that any currectly active signal will read a null slot,
		// otherwise the slot will be applied even though it's been disconnected
		if(sigObj[slotsname]) {
			if(options.disconnectAll) {
				sigObj[slotsname] = sigObj[slotsname].collect(function(slot) {
					if(slot[0] == slotObj && slot[1] == func2) {
						slot[0] = null;
						return null;
					} else {
						return slot;
					}
				}).compact();
			} else {
				var idx = -1;
				sigObj[slotsname] = sigObj[slotsname].collect(function(slot, index) {
					if(slot[0] == slotObj && slot[1] == func2 && idx < 0) {  //disconnect first match
						idx = index;
						slot[0] = null;
						return null;
					} else {
						return slot;
					}
				}).compact();
			}
		}
	},
	disconnectAll : function(obj1, func1, obj2, func2, options) {
		Signal.disconnect(obj1, func1, obj2, func2, Object.extend(options || {}, {disconnectAll : true}))
	}
}
*/

/*
 Tests

//   1. Simple Test 1 "hello Fred" should trigger "Fred is a stupid head"


      sayHello = function(n) {
      	alert("Hello! " + n);
      }
      moron = function(n) {
      	alert(n + " is a stupid head");
      }
      Signal.connect(null,'sayHello',null,'moron');

      onclick="sayHello('Fred')"


//   2. Simple Test 2 repeated insults about Fred


      Signal.connect(null,'sayHello2',null,'moron2');
      Signal.connect(null,'sayHello2',null,'moron2');
      Signal.connect(null,'sayHello2',null,'moron2');


//   3. Simple Test 3 multiple insults about Fred


      Signal.connect(null,'sayHello3',null,'moron3');
      Signal.connect(null,'sayHello3',null,'bonehead3');
      Signal.connect(null,'sayHello3',null,'idiot3');


//   4. Simple Test 4 3 insults about Fred first - 3 then none


      Signal.connect(null,'sayHello4',null,'moron4');
      Signal.connect(null,'sayHello4',null,'moron4');
      Signal.connect(null,'sayHello4',null,'moron4');
      Signal.disconnect(null,'sayHello4',null,'moron4');
      Signal.disconnect(null,'sayHello4',null,'moron4');
      Signal.disconnect(null,'sayHello4',null,'moron4');


//   5. Simple Test 5 connect 3 insults about Fred first - only one, then none


      Signal.connect(null,'sayHello5',null,'moron5');
      Signal.connect(null,'sayHello5',null,'moron5');
      Signal.connect(null,'sayHello5',null,'moron5');
      Signal.disconnectAll(null,'sayHello5',null,'moron5');


//   6. Simple Test 6 connect 3 insults but only one comes out


      Signal.connectOnce(null,'sayHello6',null,'moron6');
      Signal.connectOnce(null,'sayHello6',null,'moron6');
      Signal.connectOnce(null,'sayHello6',null,'moron6');


//   7. Simple Test 7 connect via objects


      var o = {};
      o.sayHello = function(n) {
      	alert("Hello! " + n + " (from object o)");
      }
      var m = {};
      m.moron = function(n) {
      	alert(n + " is a stupid head (from object m)");
      }

      Signal.connect(o,'sayHello',m,'moron');

      onclick="o.sayHello('Fred')"


//   8. Simple Test 8 connect but the insult comes first using {before:true}


      Signal.connect(null,'sayHello8',null,'moron8', {before:true});


//   9. Simple Test 9 connect but the insult is mutated


      Signal.connect(null,'sayHello9',null,'moron9', {mutate:function() { return ['smelly ' + arguments[0]] }});

 */

Object.extend(String.prototype, {
  gsub: function(pattern, replacement) {
    var result = '', source = this, match;
    replacement = arguments.callee.prepareReplacement(replacement);
    
    while (source.length > 0) {
      if (match = source.match(pattern)) {
        result += source.slice(0, match.index);
        result += (replacement(match) || '').toString();
        source  = source.slice(match.index + match[0].length);
      } else {
        result += source, source = '';
      }
    }
    return result;
  },
  
  sub: function(pattern, replacement, count) {
    replacement = this.gsub.prepareReplacement(replacement);
    count = count === undefined ? 1 : count;
    
    return this.gsub(pattern, function(match) {
      if (--count < 0) return match[0];
      return replacement(match);
    });
  },
  
  scan: function(pattern, iterator) {
    this.gsub(pattern, iterator);
    return this;
  },
  
  truncate: function(length, truncation) {
    length = length || 30;
    truncation = truncation === undefined ? '...' : truncation;
    return this.length > length ? 
      this.slice(0, length - truncation.length) + truncation : this;
  },

  strip: function() {
    return this.replace(/^\s+/, '').replace(/\s+$/, '');
  },
  
  stripTags: function() {
    return this.replace(/<\/?[^>]+>/gi, '');
  },

  stripScripts: function() {
    return this.replace(new RegExp(Prototype.ScriptFragment, 'img'), '');
  },
  
  extractScripts: function() {
    var matchAll = new RegExp(Prototype.ScriptFragment, 'img');
    var matchOne = new RegExp(Prototype.ScriptFragment, 'im');
    return (this.match(matchAll) || []).map(function(scriptTag) {
      return (scriptTag.match(matchOne) || ['', ''])[1];
    });
  },
  
  evalScripts: function() {
    return this.extractScripts().map(function(script) { return eval(script) });
  },

  escapeHTML: function() {
    var div = document.createElement('div');
    var text = document.createTextNode(this);
    div.appendChild(text);
    return div.innerHTML;
  },

  unescapeHTML: function() {
    var div = document.createElement('div');
    div.innerHTML = this.stripTags();
    return div.childNodes[0] ? div.childNodes[0].nodeValue : '';
  },
  
  toQueryParams: function() {
    var pairs = this.match(/^\??(.*)$/)[1].split('&');
    return pairs.inject({}, function(params, pairString) {
      var pair = pairString.split('=');
      params[pair[0]] = pair[1];
      return params;
    });
  },
  
  toArray: function() {
    return this.split('');
  },
  
  camelize: function() {
    var oStringList = this.split('-');
    if (oStringList.length == 1) return oStringList[0];
      
    var camelizedString = this.indexOf('-') == 0
      ? oStringList[0].charAt(0).toUpperCase() + oStringList[0].substring(1) 
      : oStringList[0];
      
    for (var i = 1, len = oStringList.length; i < len; i++) {
      var s = oStringList[i];
      camelizedString += s.charAt(0).toUpperCase() + s.substring(1);
    }
    
    return camelizedString;
  },

  inspect: function() {
    return "'" + this.replace(/\\/g, '\\\\').replace(/'/g, '\\\'') + "'";
  }
});

String.prototype.gsub.prepareReplacement = function(replacement) {
  if (typeof replacement == 'function') return replacement;
  var template = new Template(replacement);
  return function(match) { return template.evaluate(match) };
}

String.prototype.parseQuery = String.prototype.toQueryParams;

var Template = Class.create();
Template.Pattern = /(^|.|\r|\n)(#\{(.*?)\})/;
Template.prototype = {
  initialize: function(template, pattern) {
    this.template = template.toString();
    this.pattern  = pattern || Template.Pattern;
  },
  
  evaluate: function(object) {
    return this.template.gsub(this.pattern, function(match) {
      var before = match[1];
      if (before == '\\') return match[2];
      return before + (object[match[3]] || '').toString();
    });
  }
}


/**
 * @class String extensions
 */
Object.extend(String.prototype, 
{
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

var $break    = new Object();
var $continue = new Object();

var Enumerable = {
  each: function(iterator) {
    var index = 0;
    try {
      this._each(function(value) {
        try {
          iterator(value, index++);
        } catch (e) {
          if (e != $continue) throw e;
        }
      });
    } catch (e) {
      if (e != $break) throw e;
    }
  },
  
  all: function(iterator) {
    var result = true;
    this.each(function(value, index) {
      result = result && !!(iterator || Prototype.K)(value, index);
      if (!result) throw $break;
    });
    return result;
  },
  
  any: function(iterator) {
    var result = true;
    this.each(function(value, index) {
      if (result = !!(iterator || Prototype.K)(value, index)) 
        throw $break;
    });
    return result;
  },
  
  collect: function(iterator) {
    var results = [];
    this.each(function(value, index) {
      results.push(iterator(value, index));
    });
    return results;
  },
  
  detect: function (iterator) {
    var result;
    this.each(function(value, index) {
      if (iterator(value, index)) {
        result = value;
        throw $break;
      }
    });
    return result;
  },
  
  findAll: function(iterator) {
    var results = [];
    this.each(function(value, index) {
      if (iterator(value, index))
        results.push(value);
    });
    return results;
  },
  
  grep: function(pattern, iterator) {
    var results = [];
    this.each(function(value, index) {
      var stringValue = value.toString();
      if (stringValue.match(pattern))
        results.push((iterator || Prototype.K)(value, index));
    })
    return results;
  },
  
  include: function(object) {
    var found = false;
    this.each(function(value) {
      if (value == object) {
        found = true;
        throw $break;
      }
    });
    return found;
  },
  
  inject: function(memo, iterator) {
    this.each(function(value, index) {
      memo = iterator(memo, value, index);
    });
    return memo;
  },
  
  invoke: function(method) {
    var args = $A(arguments).slice(1);
    return this.collect(function(value) {
      return value[method].apply(value, args);
    });
  },
  
  max: function(iterator) {
    var result;
    this.each(function(value, index) {
      value = (iterator || Prototype.K)(value, index);
      if (result == undefined || value >= result)
        result = value;
    });
    return result;
  },
  
  min: function(iterator) {
    var result;
    this.each(function(value, index) {
      value = (iterator || Prototype.K)(value, index);
      if (result == undefined || value < result)
        result = value;
    });
    return result;
  },
  
  partition: function(iterator) {
    var trues = [], falses = [];
    this.each(function(value, index) {
      ((iterator || Prototype.K)(value, index) ? 
        trues : falses).push(value);
    });
    return [trues, falses];
  },
  
  pluck: function(property) {
    var results = [];
    this.each(function(value, index) {
      results.push(value[property]);
    });
    return results;
  },
  
  reject: function(iterator) {
    var results = [];
    this.each(function(value, index) {
      if (!iterator(value, index))
        results.push(value);
    });
    return results;
  },
  
  sortBy: function(iterator) {
    return this.collect(function(value, index) {
      return {value: value, criteria: iterator(value, index)};
    }).sort(function(left, right) {
      var a = left.criteria, b = right.criteria;
      return a < b ? -1 : a > b ? 1 : 0;
    }).pluck('value');
  },
  
  toArray: function() {
    return this.collect(Prototype.K);
  },
  
  zip: function() {
    var iterator = Prototype.K, args = $A(arguments);
    if (typeof args.last() == 'function')
      iterator = args.pop();

    var collections = [this].concat(args).map($A);
    return this.map(function(value, index) {
      return iterator(collections.pluck(index));
    });
  },
  
  inspect: function() {
    return '#<Enumerable:' + this.toArray().inspect() + '>';
  }
}

Object.extend(Enumerable, {
  map:     Enumerable.collect,
  find:    Enumerable.detect,
  select:  Enumerable.findAll,
  member:  Enumerable.include,
  entries: Enumerable.toArray
});


var $A = Array.from = function(iterable) {
  if (!iterable) return [];
  if (iterable.toArray) {
    return iterable.toArray();
  } else {
    var results = [];
    for (var i = 0; i < iterable.length; i++)
      results.push(iterable[i]);
    return results;
  }
}

Object.extend(Array.prototype, Enumerable);

if (!Array.prototype._reverse)
  Array.prototype._reverse = Array.prototype.reverse;

Object.extend(Array.prototype, {
  _each: function(iterator) {
    for (var i = 0; i < this.length; i++)
      iterator(this[i]);
  },
  
  clear: function() {
    this.length = 0;
    return this;
  },
  
  first: function() {
    return this[0];
  },
  
  last: function() {
    return this[this.length - 1];
  },
  
  compact: function() {
    return this.select(function(value) {
      return value != undefined || value != null;
    });
  },
  
  flatten: function() {
    return this.inject([], function(array, value) {
      return array.concat(value && value.constructor == Array ?
        value.flatten() : [value]);
    });
  },
  
  without: function() {
    var values = $A(arguments);
    return this.select(function(value) {
      return !values.include(value);
    });
  },
  
  indexOf: function(object) {
    for (var i = 0; i < this.length; i++)
      if (this[i] == object) return i;
    return -1;
  },
  
  reverse: function(inline) {
    return (inline !== false ? this : this.toArray())._reverse();
  },
  
  inspect: function() {
    return '[' + this.map(Object.inspect).join(', ') + ']';
  }
});


var Hash = {
  _each: function(iterator) {
    for (var key in this) {
      var value = this[key];
      if (typeof value == 'function') continue;

      var pair = [key, value];
      pair.key = key;
      pair.value = value;
      iterator(pair);
    }
  },

  keys: function() {
    return this.pluck('key');
  },

  values: function() {
    return this.pluck('value');
  },

  merge: function(hash) {
    return $H(hash).inject($H(this), function(mergedHash, pair) {
      mergedHash[pair.key] = pair.value;
      return mergedHash;
    });
  },

  toQueryString: function() {
    return this.map(function(pair)
	{
	  //special case for PHP, array post data.
	  if(typeof(pair[1]) == 'object' || typeof(pair[1]) == 'array')
	  {
	  	return $A(pair[1]).collect(function(value)
		{
			return encodeURIComponent(pair[0])+'='+encodeURIComponent(value);
		}).join('&');
	  }
	  else
 	     return pair.map(encodeURIComponent).join('=');
    }).join('&');
  },

  inspect: function() {
    return '#<Hash:{' + this.map(function(pair) {
      return pair.map(Object.inspect).join(': ');
    }).join(', ') + '}>';
  }
}

function $H(object) {
  var hash = Object.extend({}, object || {});
  Object.extend(hash, Enumerable);
  Object.extend(hash, Hash);
  return hash;
}


ObjectRange = Class.create();
Object.extend(ObjectRange.prototype, Enumerable);
Object.extend(ObjectRange.prototype, {
  initialize: function(start, end, exclusive) {
    this.start = start;
    this.end = end;
    this.exclusive = exclusive;
  },
  
  _each: function(iterator) {
    var value = this.start;
    do {
      iterator(value);
      value = value.succ();
    } while (this.include(value));
  },
  
  include: function(value) {
    if (value < this.start) 
      return false;
    if (this.exclusive)
      return value < this.end;
    return value <= this.end;
  }
});

var $R = function(start, end, exclusive) {
  return new ObjectRange(start, end, exclusive);
}

function $() {
  var results = [], element;
  for (var i = 0; i < arguments.length; i++) {
    element = arguments[i];
    if (typeof element == 'string')
      element = document.getElementById(element);
    results.push(Element.extend(element));
  }
  return results.length < 2 ? results[0] : results;
}

document.getElementsByClassName = function(className, parentElement) {
  var children = ($(parentElement) || document.body).getElementsByTagName('*');
  return $A(children).inject([], function(elements, child) {
    if (child.className.match(new RegExp("(^|\\s)" + className + "(\\s|$)")))
      elements.push(Element.extend(child));
    return elements;
  });
}

/*--------------------------------------------------------------------------*/

if (!window.Element)
  var Element = new Object();

Element.extend = function(element) {
  if (!element) return;
  if (_nativeExtensions) return element;
  
  if (!element._extended && element.tagName && element != window) {
    var methods = Element.Methods, cache = Element.extend.cache;
    for (property in methods) {
      var value = methods[property];
      if (typeof value == 'function')
        element[property] = cache.findOrStore(value);
    }
  }
  
  element._extended = true;
  return element;
}

Element.extend.cache = {
  findOrStore: function(value) {
    return this[value] = this[value] || function() {
      return value.apply(null, [this].concat($A(arguments)));
    }
  }
}

Element.Methods = {
  visible: function(element) {
    return $(element).style.display != 'none';
  },
  
  toggle: function() {
    for (var i = 0; i < arguments.length; i++) {
      var element = $(arguments[i]);
      Element[Element.visible(element) ? 'hide' : 'show'](element);
    }
  },

  hide: function() {
    for (var i = 0; i < arguments.length; i++) {
      var element = $(arguments[i]);
      element.style.display = 'none';
    }
  },
  
  show: function() {
    for (var i = 0; i < arguments.length; i++) {
      var element = $(arguments[i]);
      element.style.display = '';
    }
  },

  remove: function(element) {
    element = $(element);
    element.parentNode.removeChild(element);
  },

  update: function(element, html) {
    $(element).innerHTML = html.stripScripts();
    setTimeout(function() {html.evalScripts()}, 10);
  },
  
  replace: function(element, html) {
    element = $(element);
    if (element.outerHTML) {
      element.outerHTML = html.stripScripts();
    } else {
      var range = element.ownerDocument.createRange();
      range.selectNodeContents(element);
      element.parentNode.replaceChild(
        range.createContextualFragment(html.stripScripts()), element);
    }
    setTimeout(function() {html.evalScripts()}, 10);
  },
  
  getHeight: function(element) {
    element = $(element);
    return element.offsetHeight; 
  },
  
  classNames: function(element) {
    return new Element.ClassNames(element);
  },

  hasClassName: function(element, className) {
    if (!(element = $(element))) return;
    return Element.classNames(element).include(className);
  },

  addClassName: function(element, className) {
    if (!(element = $(element))) return;
    return Element.classNames(element).add(className);
  },

  removeClassName: function(element, className) {
    if (!(element = $(element))) return;
    return Element.classNames(element).remove(className);
  },
  
  // removes whitespace-only text node children
  cleanWhitespace: function(element) {
    element = $(element);
    for (var i = 0; i < element.childNodes.length; i++) {
      var node = element.childNodes[i];
      if (node.nodeType == 3 && !/\S/.test(node.nodeValue)) 
        Element.remove(node);
    }
  },
  
  empty: function(element) {
    return $(element).innerHTML.match(/^\s*$/);
  },
  
  childOf: function(element, ancestor) {
    element = $(element), ancestor = $(ancestor);
    while (element = element.parentNode)
      if (element == ancestor) return true;
    return false;
  },
  
  scrollTo: function(element) {
    element = $(element);
    var x = element.x ? element.x : element.offsetLeft,
        y = element.y ? element.y : element.offsetTop;
    window.scrollTo(x, y);
  },
  
  getStyle: function(element, style) {
    element = $(element);
    var value = element.style[style.camelize()];
    if (!value) {
      if (document.defaultView && document.defaultView.getComputedStyle) {
        var css = document.defaultView.getComputedStyle(element, null);
        value = css ? css.getPropertyValue(style) : null;
      } else if (element.currentStyle) {
        value = element.currentStyle[style.camelize()];
      }
    }

    if (window.opera && ['left', 'top', 'right', 'bottom'].include(style))
      if (Element.getStyle(element, 'position') == 'static') value = 'auto';

    return value == 'auto' ? null : value;
  },
  
  setStyle: function(element, style) {
    element = $(element);
    for (var name in style) 
      element.style[name.camelize()] = style[name];
  },
  
  getDimensions: function(element) {
    element = $(element);
    if (Element.getStyle(element, 'display') != 'none')
      return {width: element.offsetWidth, height: element.offsetHeight};
    
    // All *Width and *Height properties give 0 on elements with display none,
    // so enable the element temporarily
    var els = element.style;
    var originalVisibility = els.visibility;
    var originalPosition = els.position;
    els.visibility = 'hidden';
    els.position = 'absolute';
    els.display = '';
    var originalWidth = element.clientWidth;
    var originalHeight = element.clientHeight;
    els.display = 'none';
    els.position = originalPosition;
    els.visibility = originalVisibility;
    return {width: originalWidth, height: originalHeight};    
  },
  
  makePositioned: function(element) {
    element = $(element);
    var pos = Element.getStyle(element, 'position');
    if (pos == 'static' || !pos) {
      element._madePositioned = true;
      element.style.position = 'relative';
      // Opera returns the offset relative to the positioning context, when an
      // element is position relative but top and left have not been defined
      if (window.opera) {
        element.style.top = 0;
        element.style.left = 0;
      }  
    }
  },

  undoPositioned: function(element) {
    element = $(element);
    if (element._madePositioned) {
      element._madePositioned = undefined;
      element.style.position =
        element.style.top =
        element.style.left =
        element.style.bottom =
        element.style.right = '';   
    }
  },

  makeClipping: function(element) {
    element = $(element);
    if (element._overflow) return;
    element._overflow = element.style.overflow;
    if ((Element.getStyle(element, 'overflow') || 'visible') != 'hidden')
      element.style.overflow = 'hidden';
  },

  undoClipping: function(element) {
    element = $(element);
    if (element._overflow) return;
    element.style.overflow = element._overflow;
    element._overflow = undefined;
  }
}

Object.extend(Element, Element.Methods);

var _nativeExtensions = false;

if(!HTMLElement && /Konqueror|Safari|KHTML/.test(navigator.userAgent)) {
  var HTMLElement = {}
  HTMLElement.prototype = document.createElement('div').__proto__;
}

Element.addMethods = function(methods) {
  Object.extend(Element.Methods, methods || {});
  
  if(typeof HTMLElement != 'undefined') {
    var methods = Element.Methods, cache = Element.extend.cache;
    for (property in methods) {
      var value = methods[property];
      if (typeof value == 'function')
        HTMLElement.prototype[property] = cache.findOrStore(value);
    }
    _nativeExtensions = true;
  }
}

Element.addMethods();

var Toggle = new Object();
Toggle.display = Element.toggle;

/*--------------------------------------------------------------------------*/

Abstract.Insertion = function(adjacency) {
  this.adjacency = adjacency;
}

Abstract.Insertion.prototype = {
  initialize: function(element, content) {
    this.element = $(element);
    this.content = content.stripScripts();
    
    if (this.adjacency && this.element.insertAdjacentHTML) {
      try {
        this.element.insertAdjacentHTML(this.adjacency, this.content);
      } catch (e) {
        var tagName = this.element.tagName.toLowerCase();
        if (tagName == 'tbody' || tagName == 'tr') {
          this.insertContent(this.contentFromAnonymousTable());
        } else {
          throw e;
        }
      }
    } else {
      this.range = this.element.ownerDocument.createRange();
      if (this.initializeRange) this.initializeRange();
      this.insertContent([this.range.createContextualFragment(this.content)]);
    }

    setTimeout(function() {content.evalScripts()}, 10);   
  },
  
  contentFromAnonymousTable: function() {
    var div = document.createElement('div');
    div.innerHTML = '<table><tbody>' + this.content + '</tbody></table>';
    return $A(div.childNodes[0].childNodes[0].childNodes);
  }
}

var Insertion = new Object();

Insertion.Before = Class.create();
Insertion.Before.prototype = Object.extend(new Abstract.Insertion('beforeBegin'), {
  initializeRange: function() {
    this.range.setStartBefore(this.element);
  },
  
  insertContent: function(fragments) {
    fragments.each((function(fragment) {
      this.element.parentNode.insertBefore(fragment, this.element);
    }).bind(this));
  }
});

Insertion.Top = Class.create();
Insertion.Top.prototype = Object.extend(new Abstract.Insertion('afterBegin'), {
  initializeRange: function() {
    this.range.selectNodeContents(this.element);
    this.range.collapse(true);
  },
  
  insertContent: function(fragments) {
    fragments.reverse(false).each((function(fragment) {
      this.element.insertBefore(fragment, this.element.firstChild);
    }).bind(this));
  }
});

Insertion.Bottom = Class.create();
Insertion.Bottom.prototype = Object.extend(new Abstract.Insertion('beforeEnd'), {
  initializeRange: function() {
    this.range.selectNodeContents(this.element);
    this.range.collapse(this.element);
  },
  
  insertContent: function(fragments) {
    fragments.each((function(fragment) {
      this.element.appendChild(fragment);
    }).bind(this));
  }
});

Insertion.After = Class.create();
Insertion.After.prototype = Object.extend(new Abstract.Insertion('afterEnd'), {
  initializeRange: function() {
    this.range.setStartAfter(this.element);
  },
  
  insertContent: function(fragments) {
    fragments.each((function(fragment) {
      this.element.parentNode.insertBefore(fragment, 
        this.element.nextSibling);
    }).bind(this));
  }
});

/*--------------------------------------------------------------------------*/

Element.ClassNames = Class.create();
Element.ClassNames.prototype = {
  initialize: function(element) {
    this.element = $(element);
  },

  _each: function(iterator) {
    this.element.className.split(/\s+/).select(function(name) {
      return name.length > 0;
    })._each(iterator);
  },
  
  set: function(className) {
    this.element.className = className;
  },
  
  add: function(classNameToAdd) {
    if (this.include(classNameToAdd)) return;
    this.set(this.toArray().concat(classNameToAdd).join(' '));
  },
  
  remove: function(classNameToRemove) {
    if (!this.include(classNameToRemove)) return;
    this.set(this.select(function(className) {
      return className != classNameToRemove;
    }).join(' '));
  },
  
  toString: function() {
    return this.toArray().join(' ');
  }
}

Object.extend(Element.ClassNames.prototype, Enumerable);


var Field = {
  clear: function() {
    for (var i = 0; i < arguments.length; i++)
      $(arguments[i]).value = '';
  },

  focus: function(element) {
    $(element).focus();
  },

  present: function() {
    for (var i = 0; i < arguments.length; i++)
      if ($(arguments[i]).value == '') return false;
    return true;
  },

  select: function(element) {
    $(element).select();
  },

  activate: function(element) {
    element = $(element);
    element.focus();
    if (element.select)
      element.select();
  }
}

/*--------------------------------------------------------------------------*/

var Form = {
  serialize: function(form) {
    var elements = Form.getElements($(form));
    var queryComponents = new Array();

    for (var i = 0; i < elements.length; i++) {
      var queryComponent = Form.Element.serialize(elements[i]);
      if (queryComponent)
        queryComponents.push(queryComponent);
    }

    return queryComponents.join('&');
  },

  getElements: function(form) {
    form = $(form);
    var elements = new Array();

    for (var tagName in Form.Element.Serializers) {
      var tagElements = form.getElementsByTagName(tagName);
      for (var j = 0; j < tagElements.length; j++)
        elements.push(tagElements[j]);
    }
    return elements;
  },

  getInputs: function(form, typeName, name) {
    form = $(form);
    var inputs = form.getElementsByTagName('input');

    if (!typeName && !name)
      return inputs;

    var matchingInputs = new Array();
    for (var i = 0; i < inputs.length; i++) {
      var input = inputs[i];
      if ((typeName && input.type != typeName) ||
          (name && input.name != name))
        continue;
      matchingInputs.push(input);
    }

    return matchingInputs;
  },

  disable: function(form) {
    var elements = Form.getElements(form);
    for (var i = 0; i < elements.length; i++) {
      var element = elements[i];
      element.blur();
      element.disabled = 'true';
    }
  },

  enable: function(form) {
    var elements = Form.getElements(form);
    for (var i = 0; i < elements.length; i++) {
      var element = elements[i];
      element.disabled = '';
    }
  },

  findFirstElement: function(form) {
    return Form.getElements(form).find(function(element) {
      return element.type != 'hidden' && !element.disabled &&
        ['input', 'select', 'textarea'].include(element.tagName.toLowerCase());
    });
  },

  focusFirstElement: function(form) {
    Field.activate(Form.findFirstElement(form));
  },

  reset: function(form) {
    $(form).reset();
  }
}

Form.Element = {
  serialize: function(element) {
    element = $(element);
    var method = element.tagName.toLowerCase();
    var parameter = Form.Element.Serializers[method](element);

    if (parameter) {
      var key = encodeURIComponent(parameter[0]);
      if (key.length == 0) return;

      if (parameter[1].constructor != Array)
        parameter[1] = [parameter[1]];

      return parameter[1].map(function(value) {
        return key + '=' + encodeURIComponent(value);
      }).join('&');
    }
  },

  getValue: function(element) {
    element = $(element);
    var method = element.tagName.toLowerCase();
    var parameter = Form.Element.Serializers[method](element);

    if (parameter)
      return parameter[1];
  }
}

Form.Element.Serializers = {
  input: function(element) {
  	if(typeof(element.type) == "undefined")
		return false;
    switch (element.type.toLowerCase()) {
      case 'submit':
      case 'hidden':
      case 'password':
      case 'text':
        return Form.Element.Serializers.textarea(element);
      case 'checkbox':
      case 'radio':
        return Form.Element.Serializers.inputSelector(element);
    }
    return false;
  },

  inputSelector: function(element) {
    if (element.checked)
      return [element.name, element.value];
  },

  textarea: function(element) {
    return [element.name, element.value];
  },

  select: function(element) {
    return Form.Element.Serializers[element.type == 'select-one' ?
      'selectOne' : 'selectMany'](element);
  },

  selectOne: function(element) {
    var value = '', opt, index = element.selectedIndex;
    if (index >= 0) {
      opt = element.options[index];
      value = opt.value || opt.text;
    }
    return [element.name, value];
  },

  selectMany: function(element) {
    var value = [];
    for (var i = 0; i < element.length; i++) {
      var opt = element.options[i];
      if (opt.selected)
        value.push(opt.value || opt.text);
    }
    return [element.name, value];
  }
}

/*--------------------------------------------------------------------------*/

var $F = Form.Element.getValue;

/*--------------------------------------------------------------------------*/

Abstract.TimedObserver = function() {}
Abstract.TimedObserver.prototype = {
  initialize: function(element, frequency, callback) {
    this.frequency = frequency;
    this.element   = $(element);
    this.callback  = callback;

    this.lastValue = this.getValue();
    this.registerCallback();
  },

  registerCallback: function() {
    setInterval(this.onTimerEvent.bind(this), this.frequency * 1000);
  },

  onTimerEvent: function() {
    var value = this.getValue();
    if (this.lastValue != value) {
      this.callback(this.element, value);
      this.lastValue = value;
    }
  }
}

Form.Element.Observer = Class.create();
Form.Element.Observer.prototype = Object.extend(new Abstract.TimedObserver(), {
  getValue: function() {
    return Form.Element.getValue(this.element);
  }
});

Form.Observer = Class.create();
Form.Observer.prototype = Object.extend(new Abstract.TimedObserver(), {
  getValue: function() {
    return Form.serialize(this.element);
  }
});

/*--------------------------------------------------------------------------*/

Abstract.EventObserver = function() {}
Abstract.EventObserver.prototype = {
  initialize: function(element, callback) {
    this.element  = $(element);
    this.callback = callback;

    this.lastValue = this.getValue();
    if (this.element.tagName.toLowerCase() == 'form')
      this.registerFormCallbacks();
    else
      this.registerCallback(this.element);
  },

  onElementEvent: function() {
    var value = this.getValue();
    if (this.lastValue != value) {
      this.callback(this.element, value);
      this.lastValue = value;
    }
  },

  registerFormCallbacks: function() {
    var elements = Form.getElements(this.element);
    for (var i = 0; i < elements.length; i++)
      this.registerCallback(elements[i]);
  },

  registerCallback: function(element) {
    if (element.type) {
      switch (element.type.toLowerCase()) {
        case 'checkbox':
        case 'radio':
          Event.observe(element, 'click', this.onElementEvent.bind(this));
          break;
        case 'password':
        case 'text':
        case 'textarea':
        case 'select-one':
        case 'select-multiple':
          Event.observe(element, 'change', this.onElementEvent.bind(this));
          break;
      }
    }
  }
}

Form.Element.EventObserver = Class.create();
Form.Element.EventObserver.prototype = Object.extend(new Abstract.EventObserver(), {
  getValue: function() {
    return Form.Element.getValue(this.element);
  }
});

Form.EventObserver = Class.create();
Form.EventObserver.prototype = Object.extend(new Abstract.EventObserver(), {
  getValue: function() {
    return Form.serialize(this.element);
  }
});



if (!window.Event) {
  var Event = new Object();
}

Object.extend(Event, {
  KEY_BACKSPACE: 8,
  KEY_TAB:       9,
  KEY_RETURN:   13,
  KEY_ESC:      27,
  KEY_LEFT:     37,
  KEY_UP:       38,
  KEY_RIGHT:    39,
  KEY_DOWN:     40,
  KEY_DELETE:   46,
  KEY_SPACEBAR: 32,

  element: function(event) {
    return event.target || event.srcElement;
  },

  isLeftClick: function(event) {
    return (((event.which) && (event.which == 1)) ||
            ((event.button) && (event.button == 1)));
  },

  pointerX: function(event) {
    return event.pageX || (event.clientX + 
      (document.documentElement.scrollLeft || document.body.scrollLeft));
  },

  pointerY: function(event) {
    return event.pageY || (event.clientY + 
      (document.documentElement.scrollTop || document.body.scrollTop));
  },

  stop: function(event) {
    if (event.preventDefault) { 
      event.preventDefault(); 
      event.stopPropagation(); 
    } else {
      event.returnValue = false;
      event.cancelBubble = true;
    }
  },

  // find the first node with the given tagName, starting from the
  // node the event was triggered on; traverses the DOM upwards
  findElement: function(event, tagName) {
    var element = Event.element(event);
    while (element.parentNode && (!element.tagName ||
        (element.tagName.toUpperCase() != tagName.toUpperCase())))
      element = element.parentNode;
    return element;
  },

  observers: false,
  
  _observeAndCache: function(element, name, observer, useCapture) {
    if (!this.observers) this.observers = [];
    if (element.addEventListener) {
      this.observers.push([element, name, observer, useCapture]);
      element.addEventListener(name, observer, useCapture);
    } else if (element.attachEvent) {
      this.observers.push([element, name, observer, useCapture]);
      element.attachEvent('on' + name, observer);
    }
  },
  
  unloadCache: function() {
    if (!Event.observers) return;
    for (var i = 0; i < Event.observers.length; i++) {
      Event.stopObserving.apply(this, Event.observers[i]);
      Event.observers[i][0] = null;
    }
    Event.observers = false;
  },

  observe: function(element, name, observer, useCapture) {
    var element = $(element);
    useCapture = useCapture || false;
    
    if (name == 'keypress' &&
        (navigator.appVersion.match(/Konqueror|Safari|KHTML/)
        || element.attachEvent))
      name = 'keydown';
    
    this._observeAndCache(element, name, observer, useCapture);
  },

  stopObserving: function(element, name, observer, useCapture) {
    var element = $(element);
    useCapture = useCapture || false;
    
    if (name == 'keypress' &&
        (navigator.appVersion.match(/Konqueror|Safari|KHTML/)
        || element.detachEvent))
      name = 'keydown';
    
    if (element.removeEventListener) {
      element.removeEventListener(name, observer, useCapture);
    } else if (element.detachEvent) {
      element.detachEvent('on' + name, observer);
    }
  }
});

/* prevent memory leaks in IE */
if (navigator.appVersion.match(/\bMSIE\b/))
  Event.observe(window, 'unload', Event.unloadCache, false);


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
	fireEvent : function(element,type,canBubble)
	{
		canBubble = (typeof(canBubble) == undefined) ? true : canBubble;
		element = $(element);
		if(type == "submit")
			return element.submit();
		if(document.createEvent)
        {
			if(Event.isHTMLEvent(type))
			{
				var event = document.createEvent('HTMLEvents');
	            event.initEvent(type, canBubble, true);
			}
			else if(Event.isMouseEvent(type))
			{
				var event = document.createEvent('MouseEvents');
				if (event.initMouseEvent)
		        {
					event.initMouseEvent(type,canBubble,true,
						document.defaultView, 1, 0, 0, 0, 0, false,
								false, false, false, 0, null);
		        }
		        else
		        {
		            // Safari
		            // TODO we should be initialising other mouse-event related attributes here
		            event.initEvent(type, canBubble, true);
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

var Position = {
  // set to true if needed, warning: firefox performance problems
  // NOT neeeded for page scrolling, only if draggable contained in
  // scrollable elements
  includeScrollOffsets: false, 

  // must be called before calling withinIncludingScrolloffset, every time the
  // page is scrolled
  prepare: function() {
    this.deltaX =  window.pageXOffset 
                || document.documentElement.scrollLeft 
                || document.body.scrollLeft 
                || 0;
    this.deltaY =  window.pageYOffset 
                || document.documentElement.scrollTop 
                || document.body.scrollTop 
                || 0;
  },

  realOffset: function(element) {
    var valueT = 0, valueL = 0;
    do {
      valueT += element.scrollTop  || 0;
      valueL += element.scrollLeft || 0; 
      element = element.parentNode;
    } while (element);
    return [valueL, valueT];
  },

  cumulativeOffset: function(element) {
    var valueT = 0, valueL = 0;
    do {
      valueT += element.offsetTop  || 0;
      valueL += element.offsetLeft || 0;
      element = element.offsetParent;
    } while (element);
    return [valueL, valueT];
  },

  positionedOffset: function(element) {
    var valueT = 0, valueL = 0;
    do {
      valueT += element.offsetTop  || 0;
      valueL += element.offsetLeft || 0;
      element = element.offsetParent;
      if (element) {
        p = Element.getStyle(element, 'position');
        if (p == 'relative' || p == 'absolute') break;
      }
    } while (element);
    return [valueL, valueT];
  },
  
  offsetParent: function(element) {
    if (element.offsetParent) return element.offsetParent;
    if (element == document.body) return element;

    while ((element = element.parentNode) && element != document.body)
      if (Element.getStyle(element, 'position') != 'static')
        return element;

    return document.body;
  },
  
  // caches x/y coordinate pair to use with overlap
  within: function(element, x, y) {
    if (this.includeScrollOffsets)
      return this.withinIncludingScrolloffsets(element, x, y);
    this.xcomp = x;
    this.ycomp = y;
    this.offset = this.cumulativeOffset(element);

    return (y >= this.offset[1] &&
            y <  this.offset[1] + element.offsetHeight &&
            x >= this.offset[0] && 
            x <  this.offset[0] + element.offsetWidth);
  },

  withinIncludingScrolloffsets: function(element, x, y) {
    var offsetcache = this.realOffset(element);

    this.xcomp = x + offsetcache[0] - this.deltaX;
    this.ycomp = y + offsetcache[1] - this.deltaY;
    this.offset = this.cumulativeOffset(element);

    return (this.ycomp >= this.offset[1] &&
            this.ycomp <  this.offset[1] + element.offsetHeight &&
            this.xcomp >= this.offset[0] && 
            this.xcomp <  this.offset[0] + element.offsetWidth);
  },

  // within must be called directly before
  overlap: function(mode, element) {  
    if (!mode) return 0;  
    if (mode == 'vertical') 
      return ((this.offset[1] + element.offsetHeight) - this.ycomp) / 
        element.offsetHeight;
    if (mode == 'horizontal')
      return ((this.offset[0] + element.offsetWidth) - this.xcomp) / 
        element.offsetWidth;
  },

  clone: function(source, target) {
    source = $(source);
    target = $(target);
    target.style.position = 'absolute';
    var offsets = this.cumulativeOffset(source);
    target.style.top    = offsets[1] + 'px';
    target.style.left   = offsets[0] + 'px';
    target.style.width  = source.offsetWidth + 'px';
    target.style.height = source.offsetHeight + 'px';
  },

  page: function(forElement) {
    var valueT = 0, valueL = 0;

    var element = forElement;
    do {
      valueT += element.offsetTop  || 0;
      valueL += element.offsetLeft || 0;

      // Safari fix
      if (element.offsetParent==document.body)
        if (Element.getStyle(element,'position')=='absolute') break;

    } while (element = element.offsetParent);

    element = forElement;
    do {
      valueT -= element.scrollTop  || 0;
      valueL -= element.scrollLeft || 0;    
    } while (element = element.parentNode);

    return [valueL, valueT];
  },

  clone: function(source, target) {
    var options = Object.extend({
      setLeft:    true,
      setTop:     true,
      setWidth:   true,
      setHeight:  true,
      offsetTop:  0,
      offsetLeft: 0
    }, arguments[2] || {})

    // find page position of source
    source = $(source);
    var p = Position.page(source);

    // find coordinate system to use
    target = $(target);
    var delta = [0, 0];
    var parent = null;
    // delta [0,0] will do fine with position: fixed elements, 
    // position:absolute needs offsetParent deltas
    if (Element.getStyle(target,'position') == 'absolute') {
      parent = Position.offsetParent(target);
      delta = Position.page(parent);
    }

    // correct by body offsets (fixes Safari)
    if (parent == document.body) {
      delta[0] -= document.body.offsetLeft;
      delta[1] -= document.body.offsetTop; 
    }

    // set position
    if(options.setLeft)   target.style.left  = (p[0] - delta[0] + options.offsetLeft) + 'px';
    if(options.setTop)    target.style.top   = (p[1] - delta[1] + options.offsetTop) + 'px';
    if(options.setWidth)  target.style.width = source.offsetWidth + 'px';
    if(options.setHeight) target.style.height = source.offsetHeight + 'px';
  },

  absolutize: function(element) {
    element = $(element);
    if (element.style.position == 'absolute') return;
    Position.prepare();

    var offsets = Position.positionedOffset(element);
    var top     = offsets[1];
    var left    = offsets[0];
    var width   = element.clientWidth;
    var height  = element.clientHeight;

    element._originalLeft   = left - parseFloat(element.style.left  || 0);
    element._originalTop    = top  - parseFloat(element.style.top || 0);
    element._originalWidth  = element.style.width;
    element._originalHeight = element.style.height;

    element.style.position = 'absolute';
    element.style.top    = top + 'px';;
    element.style.left   = left + 'px';;
    element.style.width  = width + 'px';;
    element.style.height = height + 'px';;
  },

  relativize: function(element) {
    element = $(element);
    if (element.style.position == 'relative') return;
    Position.prepare();

    element.style.position = 'relative';
    var top  = parseFloat(element.style.top  || 0) - (element._originalTop || 0);
    var left = parseFloat(element.style.left || 0) - (element._originalLeft || 0);

    element.style.top    = top + 'px';
    element.style.left   = left + 'px';
    element.style.height = element._originalHeight;
    element.style.width  = element._originalWidth;
  }
}

// Safari returns margins on body which is incorrect if the child is absolutely
// positioned.  For performance reasons, redefine Position.cumulativeOffset for
// KHTML/WebKit only.
if (/Konqueror|Safari|KHTML/.test(navigator.userAgent)) {
  Position.cumulativeOffset = function(element) {
    var valueT = 0, valueL = 0;
    do {
      valueT += element.offsetTop  || 0;
      valueL += element.offsetLeft || 0;
      if (element.offsetParent == document.body)
        if (Element.getStyle(element, 'position') == 'absolute') break;
        
      element = element.offsetParent;
    } while (element);
    
    return [valueL, valueT];
  }
}




var Selector = Class.create();
Selector.prototype = {
  initialize: function(expression) {
    this.params = {classNames: []};
    this.expression = expression.toString().strip();
    this.parseExpression();
    this.compileMatcher();
  },

  parseExpression: function() {
    function abort(message) { throw 'Parse error in selector: ' + message; }

    if (this.expression == '')  abort('empty expression');

    var params = this.params, expr = this.expression, match, modifier, clause, rest;
    while (match = expr.match(/^(.*)\[([a-z0-9_:-]+?)(?:([~\|!]?=)(?:"([^"]*)"|([^\]\s]*)))?\]$/i)) {
      params.attributes = params.attributes || [];
      params.attributes.push({name: match[2], operator: match[3], value: match[4] || match[5] || ''});
      expr = match[1];
    }

    if (expr == '*') return this.params.wildcard = true;
    
    while (match = expr.match(/^([^a-z0-9_-])?([a-z0-9_-]+)(.*)/i)) {
      modifier = match[1], clause = match[2], rest = match[3];
      switch (modifier) {
        case '#':       params.id = clause; break;
        case '.':       params.classNames.push(clause); break;
        case '':
        case undefined: params.tagName = clause.toUpperCase(); break;
        default:        abort(expr.inspect());
      }
      expr = rest;
    }
    
    if (expr.length > 0) abort(expr.inspect());
  },

  buildMatchExpression: function() {
    var params = this.params, conditions = [], clause;

    if (params.wildcard)
      conditions.push('true');
    if (clause = params.id)
      conditions.push('element.id == ' + clause.inspect());
    if (clause = params.tagName)
      conditions.push('element.tagName.toUpperCase() == ' + clause.inspect());
    if ((clause = params.classNames).length > 0)
      for (var i = 0; i < clause.length; i++)
        conditions.push('Element.hasClassName(element, ' + clause[i].inspect() + ')');
    if (clause = params.attributes) {
      clause.each(function(attribute) {
        var value = 'element.getAttribute(' + attribute.name.inspect() + ')';
        var splitValueBy = function(delimiter) {
          return value + ' && ' + value + '.split(' + delimiter.inspect() + ')';
        }
        
        switch (attribute.operator) {
          case '=':       conditions.push(value + ' == ' + attribute.value.inspect()); break;
          case '~=':      conditions.push(splitValueBy(' ') + '.include(' + attribute.value.inspect() + ')'); break;
          case '|=':      conditions.push(
                            splitValueBy('-') + '.first().toUpperCase() == ' + attribute.value.toUpperCase().inspect()
                          ); break;
          case '!=':      conditions.push(value + ' != ' + attribute.value.inspect()); break;
          case '':
          case undefined: conditions.push(value + ' != null'); break;
          default:        throw 'Unknown operator ' + attribute.operator + ' in selector';
        }
      });
    }

    return conditions.join(' && ');
  },

  compileMatcher: function() {
    this.match = new Function('element', 'if (!element.tagName) return false; \
      return ' + this.buildMatchExpression());
  },

  findElements: function(scope) {
    var element;

    if (element = $(this.params.id))
      if (this.match(element))
        if (!scope || Element.childOf(element, scope))
          return [element];

    scope = (scope || document).getElementsByTagName(this.params.tagName || '*');

    var results = [];
    for (var i = 0; i < scope.length; i++)
      if (this.match(element = scope[i]))
        results.push(Element.extend(element));

    return results;
  },

  toString: function() {
    return this.expression;
  }
}

function $$() {
  return $A(arguments).map(function(expression) {
    return expression.strip().split(/\s+/).inject([null], function(results, expr) {
      var selector = new Selector(expr);
      return results.map(selector.findElements.bind(selector)).flatten();
    });
  }).flatten();
}


// Copyright (c) 2005 Thomas Fuchs (http://script.aculo.us, http://mir.aculo.us)
//
// See scriptaculous.js for full license.

var Builder = {
  NODEMAP: {
    AREA: 'map',
    CAPTION: 'table',
    COL: 'table',
    COLGROUP: 'table',
    LEGEND: 'fieldset',
    OPTGROUP: 'select',
    OPTION: 'select',
    PARAM: 'object',
    TBODY: 'table',
    TD: 'table',
    TFOOT: 'table',
    TH: 'table',
    THEAD: 'table',
    TR: 'table'
  },
  // note: For Firefox < 1.5, OPTION and OPTGROUP tags are currently broken,
  //       due to a Firefox bug
  node: function(elementName) {
    elementName = elementName.toUpperCase();
    
    // try innerHTML approach
    var parentTag = this.NODEMAP[elementName] || 'div';
    var parentElement = document.createElement(parentTag);
    try { // prevent IE "feature": http://dev.rubyonrails.org/ticket/2707
      parentElement.innerHTML = "<" + elementName + "></" + elementName + ">";
    } catch(e) {}
    var element = parentElement.firstChild || null;
      
    // see if browser added wrapping tags
    if(element && (element.tagName != elementName))
      element = element.getElementsByTagName(elementName)[0];
    
    // fallback to createElement approach
    if(!element) element = document.createElement(elementName);
    
    // abort if nothing could be created
    if(!element) return;

    // attributes (or text)
    if(arguments[1])
      if(this._isStringOrNumber(arguments[1]) ||
        (arguments[1] instanceof Array)) {
          this._children(element, arguments[1]);
        } else {
          var attrs = this._attributes(arguments[1]);
          if(attrs.length) {
            try { // prevent IE "feature": http://dev.rubyonrails.org/ticket/2707
              parentElement.innerHTML = "<" +elementName + " " +
                attrs + "></" + elementName + ">";
            } catch(e) {}
            element = parentElement.firstChild || null;
            // workaround firefox 1.0.X bug
            if(!element) {
              element = document.createElement(elementName);
              for(attr in arguments[1]) 
                element[attr == 'class' ? 'className' : attr] = arguments[1][attr];
            }
            if(element.tagName != elementName)
              element = parentElement.getElementsByTagName(elementName)[0];
            }
        } 

    // text, or array of children
    if(arguments[2])
      this._children(element, arguments[2]);

     return element;
  },
  _text: function(text) {
     return document.createTextNode(text);
  },
  _attributes: function(attributes) {
    var attrs = [];
    for(attribute in attributes)
      attrs.push((attribute=='className' ? 'class' : attribute) +
          '="' + attributes[attribute].toString().escapeHTML() + '"');
    return attrs.join(" ");
  },
  _children: function(element, children) {
    if(typeof children=='object') { // array can hold nodes and text
      children.flatten().each( function(e) {
        if(typeof e=='object')
          element.appendChild(e)
        else
          if(Builder._isStringOrNumber(e))
            element.appendChild(Builder._text(e));
      });
    } else
      if(Builder._isStringOrNumber(children)) 
         element.appendChild(Builder._text(children));
  },
  _isStringOrNumber: function(param) {
    return(typeof param=='string' || typeof param=='number');
  }
}


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


var Prado =
{
	Version: '3.1',

	/**
	 * Returns browser information. Example
	 * <code>
	 * var browser = Prado.Browser();
	 * alert(browser.ie); //should ouput true if IE, false otherwise
	 * </code>
	 * @param ${parameter}
	 * @return ${return}
	 */
	Browser : function()
	{
		var info = { Version : "1.0" };
		var is_major = parseInt( navigator.appVersion );
		info.nver = is_major;
		info.ver = navigator.appVersion;
		info.agent = navigator.userAgent;
		info.dom = document.getElementById ? 1 : 0;
		info.opera = window.opera ? 1 : 0;
		info.ie5 = ( info.ver.indexOf( "MSIE 5" ) > -1 && info.dom && !info.opera ) ? 1 : 0;
		info.ie6 = ( info.ver.indexOf( "MSIE 6" ) > -1 && info.dom && !info.opera ) ? 1 : 0;
		info.ie4 = ( document.all && !info.dom && !info.opera ) ? 1 : 0;
		info.ie = info.ie4 || info.ie5 || info.ie6;
		info.mac = info.agent.indexOf( "Mac" ) > -1;
		info.ns6 = ( info.dom && parseInt( info.ver ) >= 5 ) ? 1 : 0;
		info.ie3 = ( info.ver.indexOf( "MSIE" ) && ( is_major < 4 ) );
		info.hotjava = ( info.agent.toLowerCase().indexOf( 'hotjava' ) != -1 ) ? 1 : 0;
		info.ns4 = ( document.layers && !info.dom && !info.hotjava ) ? 1 : 0;
		info.bw = ( info.ie6 || info.ie5 || info.ie4 || info.ns4 || info.ns6 || info.opera );
		info.ver3 = ( info.hotjava || info.ie3 );
		info.opera7 = ( ( info.agent.toLowerCase().indexOf( 'opera 7' ) > -1 ) || ( info.agent.toLowerCase().indexOf( 'opera/7' ) > -1 ) );
		info.operaOld = info.opera && !info.opera7;
		return info;
	},

	ImportCss : function(doc, css_file)
	{
		if (Prado.Browser().ie)
			var styleSheet = doc.createStyleSheet(css_file);
		else
		{
			var elm = doc.createElement("link");

			elm.rel = "stylesheet";
			elm.href = css_file;

			if (headArr = doc.getElementsByTagName("head"))
				headArr[0].appendChild(elm);
		}
	}
};


/*Prado.Focus = Class.create();

Prado.Focus.setFocus = function(id)
{
	var target = document.getElementById ? document.getElementById(id) : document.all[id];
	if(target && !Prado.Focus.canFocusOn(target))
	{
		target = Prado.Focus.findTarget(target);
	}
	if(target)
	{
        try
		{
            target.focus();
			target.scrollIntoView(false);
            if (window.__smartNav)
			{
				window.__smartNav.ae = target.id;
			}
		}
        catch (e)
		{
		}
	}
}

Prado.Focus.canFocusOn = function(element)
{
	if(!element || !(element.tagName))
		return false;
	var tagName = element.tagName.toLowerCase();
	return !element.disabled && (!element.type || element.type.toLowerCase() != "hidden") && Prado.Focus.isFocusableTag(tagName) && Prado.Focus.isVisible(element);
}

Prado.Focus.isFocusableTag = function(tagName)
{
	return (tagName == "input" || tagName == "textarea" || tagName == "select" || tagName == "button" || tagName == "a");
}


Prado.Focus.findTarget = function(element)
{
	if(!element || !(element.tagName))
	{
		return null;
	}
	var tagName = element.tagName.toLowerCase();
	if (tagName == "undefined")
	{
		return null;
	}
	var children = element.childNodes;
	if (children)
	{
		for(var i=0;i<children.length;i++)
		{
			try
			{
				if(Prado.Focus.canFocusOn(children[i]))
				{
					return children[i];
				}
				else
				{
					var target = Prado.Focus.findTarget(children[i]);
					if(target)
					{
						return target;
					}
				}
			}
			catch (e)
			{
			}
		}
	}
	return null;
}

Prado.Focus.isVisible = function(element)
{
	var current = element;
	while((typeof(current) != "undefined") && (current != null))
	{
		if(current.disabled || (typeof(current.style) != "undefined" && ((typeof(current.style.display) != "undefined" && current.style.display == "none") || (typeof(current.style.visibility) != "undefined" && current.style.visibility == "hidden") )))
		{
			return false;
		}
		if(typeof(current.parentNode) != "undefined" &&	current.parentNode != null && current.parentNode != current && current.parentNode.tagName.toLowerCase() != "body")
		{
			current = current.parentNode;
		}
		else
		{
			return true;
		}
	}
    return true;
}
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

/*

Prado.doPostBack = function(formID, eventTarget, eventParameter, performValidation, validationGroup, actionUrl, trackFocus, clientSubmit)
{
	if (typeof(performValidation) == 'undefined')
	{
		var performValidation = false;
		var validationGroup = '';
		var actionUrl = null;
		var trackFocus = false;
		var clientSubmit = true;
	}
	var theForm = document.getElementById ? document.getElementById(formID) : document.forms[formID];
	var canSubmit = true;
    if (performValidation)
	{
		//canSubmit = Prado.Validation.validate(validationGroup);
	*	Prado.Validation.ActiveTarget = theForm;
		Prado.Validation.CurrentTargetGroup = null;
		Prado.Validation.IsGroupValidation = false;
		canSubmit = Prado.Validation.IsValid(theForm);
		Logger.debug(canSubmit);*
		canSubmit = Prado.Validation.IsValid(theForm);
	}
	if (canSubmit)
	{
		if (actionUrl != null && (actionUrl.length > 0))
		{
			theForm.action = actionUrl;
		}
		if (trackFocus)
		{
			var lastFocus = theForm.elements['PRADO_LASTFOCUS'];
			if ((typeof(lastFocus) != 'undefined') && (lastFocus != null))
			{
				var active = document.activeElement;
				if (typeof(active) == 'undefined')
				{
					lastFocus.value = eventTarget;
				}
				else
				{
					if ((active != null) && (typeof(active.id) != 'undefined'))
					{
						if (active.id.length > 0)
						{
							lastFocus.value = active.id;
						}
						else if (typeof(active.name) != 'undefined')
						{
							lastFocus.value = active.name;
						}
					}
				}
			}
		}
		if (!clientSubmit)
		{
			canSubmit = false;
		}
	}
	if (canSubmit && (!theForm.onsubmit || theForm.onsubmit()))
	{
		theForm.PRADO_POSTBACK_TARGET.value = eventTarget;
		theForm.PRADO_POSTBACK_PARAMETER.value = eventParameter;
		theForm.submit();
	}
}
*/

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
		f = RegExp('(<!--'+boundary+'-->)([\\s\\S\\w\\W]*)(<!--//'+boundary+'-->)',"m");
		result = text.match(f);
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

Prado.WebUI = Class.create();

Prado.WebUI.PostBackControl = Class.create();

Prado.WebUI.PostBackControl.prototype =
{
	initialize : function(options)
	{
		this._elementOnClick = null, //capture the element's onclick function

		this.element = $(options.ID);
		if(this.onInit)
			this.onInit(options);
	},

	onInit : function(options)
	{
		if(typeof(this.element.onclick)=="function")
		{
			this._elementOnClick = this.element.onclick;
			this.element.onclick = null;
		}
		Event.observe(this.element, "click", this.elementClicked.bindEvent(this,options));
	},

	elementClicked : function(event, options)
	{
		var src = Event.element(event);
		var doPostBack = true;
		var onclicked = null;

		if(this._elementOnClick)
		{
			var onclicked = this._elementOnClick(event);
			if(typeof(onclicked) == "boolean")
				doPostBack = onclicked;
		}
		if(doPostBack)
			this.onPostBack(event,options);
		if(typeof(onclicked) == "boolean" && !onclicked)
			Event.stop(event);
	},

	onPostBack : function(event, options)
	{
		Prado.PostBack(event,options);
	}
};

Prado.WebUI.TButton = Class.extend(Prado.WebUI.PostBackControl);
Prado.WebUI.TLinkButton = Class.extend(Prado.WebUI.PostBackControl);
Prado.WebUI.TCheckBox = Class.extend(Prado.WebUI.PostBackControl);
Prado.WebUI.TBulletedList = Class.extend(Prado.WebUI.PostBackControl);
Prado.WebUI.TImageMap = Class.extend(Prado.WebUI.PostBackControl);

/**
 * TImageButton client-side behaviour. With validation, Firefox needs
 * to capture the x,y point of the clicked image in hidden form fields.
 */
Prado.WebUI.TImageButton = Class.extend(Prado.WebUI.PostBackControl);
Object.extend(Prado.WebUI.TImageButton.prototype,
{
	/**
	 * Only add the hidden inputs once.
	 */
	hasXYInput : false,

	/**
	 * Override parent onPostBack function, tried to add hidden forms
	 * inputs to capture x,y clicked point.
	 */
	onPostBack : function(event, options)
	{
		if(!this.hasXYInput)
		{
			this.addXYInput(event,options);
			this.hasXYInput = true;
		}
		Prado.PostBack(event, options);
	},

	/**
	 * Add hidden inputs to capture the x,y point clicked on the image.
	 * @param event DOM click event.
	 * @param array image button options.
	 */
	addXYInput : function(event,options)
	{
		imagePos = Position.cumulativeOffset(this.element);
		clickedPos = [event.clientX, event.clientY];
		x = clickedPos[0]-imagePos[0]+1;
		y = clickedPos[1]-imagePos[1]+1;
		x = x < 0 ? 0 : x;
		y = y < 0 ? 0 : y;
		id = options['EventTarget'];
		x_input = $(id+"_x");
		y_input = $(id+"_y");
		if(x_input)
		{
			x_input.value = x;
		}
		else
		{
			x_input = INPUT({type:'hidden',name:id+'_x','id':id+'_x',value:x});
			this.element.parentNode.appendChild(x_input);
		}
		if(y_input)
		{
			y_input.value = y;
		}
		else
		{
			y_input = INPUT({type:'hidden',name:id+'_y','id':id+'_y',value:y});
			this.element.parentNode.appendChild(y_input);
		}
	}
});


/**
 * Radio button, only initialize if not already checked.
 */
Prado.WebUI.TRadioButton = Class.extend(Prado.WebUI.PostBackControl);
Prado.WebUI.TRadioButton.prototype.onRadioButtonInitialize = Prado.WebUI.TRadioButton.prototype.initialize;
Object.extend(Prado.WebUI.TRadioButton.prototype,
{
	initialize : function(options)
	{
		this.element = $(options['ID']);
		if(!this.element.checked)
			this.onRadioButtonInitialize(options);
	}
});


Prado.WebUI.TTextBox = Class.extend(Prado.WebUI.PostBackControl,
{
	onInit : function(options)
	{
		this.options=options;
		if(options['TextMode'] != 'MultiLine')
			Event.observe(this.element, "keydown", this.handleReturnKey.bind(this));
		if(this.options['AutoPostBack']==true)
			Event.observe(this.element, "change", Prado.PostBack.bindEvent(this,options));
	},

	handleReturnKey : function(e)
	{
		 if(Event.keyCode(e) == Event.KEY_RETURN)
        {
			var target = Event.element(e);
			if(target)
			{
				if(this.options['AutoPostBack']==true)
				{
					Event.fireEvent(target, "change");
					Event.stop(e);
				}
				else
				{
					if(this.options['CausesValidation'] && typeof(Prado.Validation) != "undefined")
					{
						if(!Prado.Validation.validate(this.options['FormID'], this.options['ValidationGroup'], $(this.options['ID'])))
							return Event.stop(e);
					}
				}
			}
		}
	}
});

Prado.WebUI.TListControl = Class.extend(Prado.WebUI.PostBackControl,
{
	onInit : function(options)
	{
		Event.observe(this.element, "change", Prado.PostBack.bindEvent(this,options));
	}
});

Prado.WebUI.TListBox = Class.extend(Prado.WebUI.TListControl);
Prado.WebUI.TDropDownList = Class.extend(Prado.WebUI.TListControl);

Prado.WebUI.DefaultButton = Class.create();
Prado.WebUI.DefaultButton.prototype =
{
	initialize : function(options)
	{
		this.options = options;
		this._event = this.triggerEvent.bindEvent(this);
		Event.observe(options['Panel'], 'keydown', this._event);
	},

	triggerEvent : function(ev, target)
	{
		var enterPressed = Event.keyCode(ev) == Event.KEY_RETURN;
		var isTextArea = Event.element(ev).tagName.toLowerCase() == "textarea";
		if(enterPressed && !isTextArea)
		{
			var defaultButton = $(this.options['Target']);
			if(defaultButton)
			{
				this.triggered = true;
				$('PRADO_POSTBACK_TARGET').value = this.options.EventTarget;
				Event.fireEvent(defaultButton, this.options['Event']);
				Event.stop(ev);
			}
		}
	}
};

Prado.WebUI.TTextHighlighter=Class.create();
Prado.WebUI.TTextHighlighter.prototype=
{
	initialize:function(id)
	{
		if(!window.clipboardData) return;
		var options =
		{
			href : 'javascript:;/'+'/copy code to clipboard',
			onclick : 'Prado.WebUI.TTextHighlighter.copy(this)',
			onmouseover : 'Prado.WebUI.TTextHighlighter.hover(this)',
			onmouseout : 'Prado.WebUI.TTextHighlighter.out(this)'
		}
		var div = DIV({className:'copycode'}, A(options, 'Copy Code'));
		document.write(DIV(null,div).innerHTML);
	}
};

Object.extend(Prado.WebUI.TTextHighlighter,
{
	copy : function(obj)
	{
		var parent = obj.parentNode.parentNode.parentNode;
		var text = '';
		for(var i = 0; i < parent.childNodes.length; i++)
		{
			var node = parent.childNodes[i];
			if(node.innerText)
				text += node.innerText == 'Copy Code' ? '' : node.innerText;
			else
				text += node.nodeValue;
		}
		if(text.length > 0)
			window.clipboardData.setData("Text", text);
	},

	hover : function(obj)
	{
		obj.parentNode.className = "copycode copycode_hover";
	},

	out : function(obj)
	{
		obj.parentNode.className = "copycode";
	}
});


Prado.WebUI.TCheckBoxList = Base.extend(
{
	constructor : function(options)
	{
		for(var i = 0; i<options.ItemCount; i++)
		{
			var checkBoxOptions = Object.extend(
			{
				ID : options.ListID+"_c"+i,
				EventTarget : options.ListName+"$c"+i
			}, options);
			new Prado.WebUI.TCheckBox(checkBoxOptions);
		}
	}
});

Prado.WebUI.TRadioButtonList = Base.extend(
{
	constructor : function(options)
	{
		for(var i = 0; i<options.ItemCount; i++)
		{
			var radioButtonOptions = Object.extend(
			{
				ID : options.ListID+"_c"+i,
				EventTarget : options.ListName+"$c"+i
			}, options);
			new Prado.WebUI.TRadioButton(radioButtonOptions);
		}
	}
});

