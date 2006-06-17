
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
/*
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
*/

/*
 * Signals and Slots for Prototype: Easy custom javascript events
 * http://tetlaw.id.au/view/blog/signals-and-slots-for-prototype-easy-custom-javascript-events
 * Andrew Tetlaw
 * Version 1 (2006-05-03)
 *
 * http://creativecommons.org/licenses/by-sa/2.5/
 */
Signal = {
	throwErrors : true,
	MT : function(){ return true },
	connect : function(obj1, func1, obj2, func2, options) {
		var options = Object.extend({
			connectOnce : false
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
				var args = arguments;
				var result = sigObj[signame].apply(sigObj,args);
				sigObj[slotsname].each(function(slot){
					try {
						if(slot && slot[0]) { // testing for null, a disconnect may have nulled this slot
							slot[0][slot[1]].apply(slot[0],args); //[0] = obj, [1] = func name
						}
					} catch(e) {
						if(Signal.throwErrors) throw e;
					}
				});
				return result;
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
