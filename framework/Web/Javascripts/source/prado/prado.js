/*! PRADO main js file | github.com/pradosoft/prado */

/*
 * Prado.Class — minimal class factory backed by native ES classes.
 *
 * Usage:
 *   const A = Prado.Class({ initialize() { ... }, foo() { ... } });
 *   const B = Prado.Class(A, { initialize($super, opts) { $super(opts); ... } });
 *   new B(options);
 *
 * Properties named `initialize` run as the constructor. Methods whose first
 * parameter is `$super` receive a bound reference to the parent's same-named
 * method as their first argument (low-pro-style super injection, retained
 * for backward compatibility with existing Prado controls).
 *
 * Also aliased as `jQuery.klass` for compatibility with the 4.x call sites.
 */
(() => {
  const SUPER_RE = /^[\s(]*(?:function[^(]*|[A-Za-z_$][\w$]*)\(\s*\$super\b/;
  const wantsSuper = (fn) => typeof fn === 'function' && SUPER_RE.test(Function.prototype.toString.call(fn));

  const installMethods = (klass, source) => {
    const parentProto = klass.superclass && klass.superclass.prototype;
    for (const name of Object.keys(source)) {
      const value = source[name];
      if (parentProto && wantsSuper(value)) {
        const method = value;
        klass.prototype[name] = function (...args) {
          const $super = parentProto[name].bind(this);
          return method.call(this, $super, ...args);
        };
      } else {
        klass.prototype[name] = value;
      }
    }
  };

  const Class = (...args) => {
    let parent = null;
    if (typeof args[0] === 'function') parent = args.shift();

    // Function constructor (not `class` keyword) so the prototype slot stays
    // writable — existing controls (ajax3.js, colorpicker.js) do wholesale
    // `Class.prototype = {...}` assignment and rely on that.
    const klass = function (...ctorArgs) {
      this.initialize(...ctorArgs);
    };
    // Marker the jQuery.fn.trigger bridge (below) uses to identify
    // Prado-managed elements. More robust than checking constructor.name,
    // which would break on minification or a future `class` keyword move.
    klass.isPradoClass = true;

    if (parent) {
      klass.prototype = Object.create(parent.prototype);
      klass.prototype.constructor = klass;
    }
    klass.superclass = parent;
    klass.subclasses = [];
    if (parent) (parent.subclasses ||= []).push(klass);

    for (const props of args) installMethods(klass, props);

    if (!klass.prototype.initialize) klass.prototype.initialize = function () {};

    // Native event-dispatch helper. New 4.4 code calls `this.trigger('foo')`
    // instead of `jQuery(this.element).trigger('foo')` to fire an event
    // without depending on jQuery. Uses CustomEvent so a `detail` payload is
    // delivered to listeners. Sub-classes may override.
    // @since 4.4.0
    if (!klass.prototype.trigger) {
      klass.prototype.trigger = function (eventName, detail) {
        const el = this.element;
        if (!el || !el.dispatchEvent || !eventName) return;
        el.dispatchEvent(new CustomEvent(eventName, {
          bubbles: true,
          cancelable: true,
          detail,
        }));
      };
    }

    return klass;
  };

  // Public API. Prado namespace doesn't exist yet at this point in the file,
  // so attach to globalThis and re-export onto Prado after it's defined below.
  globalThis.__PradoClass = Class;
  if (typeof jQuery !== 'undefined') jQuery.klass = Class;
})();


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
	Version: '4.3.3',

	/**
	 * Registry for Prado components
	 * @var Registry
	 */
	Registry: {},

	/**
	 * Class factory — native ES class with $super injection support.
	 * Returns a class that calls initialize(...) from its constructor.
	 * @var Class
	 * @since 4.4.0
	 */
	Class: globalThis.__PradoClass
};
delete globalThis.__PradoClass;

Prado.RequestManager =
{
	FIELD_POSTBACK_TARGET : 'PRADO_POSTBACK_TARGET',

	FIELD_POSTBACK_PARAMETER : 'PRADO_POSTBACK_PARAMETER'
};

// jQuery compatibility bridges. Both are installed only when jQuery is
// available on the page; when the jQuery quarantine plan ships, this whole
// block moves into the jQuery-loading package only.
if (typeof jQuery !== 'undefined') {
	// Bridge jQuery's ajaxComplete event to a native 'prado:ajaxComplete'
	// event on document. Controls that need to react to AJAX completion
	// (THtmlArea, THtmlArea5, etc.) listen to the native event so they
	// don't depend on jQuery directly. When ajax3.js moves off $.ajax
	// (step 4c), it will dispatch the native event directly and this
	// bridge will be removed.
	jQuery(document).on('ajaxComplete', () => {
		document.dispatchEvent(new CustomEvent('prado:ajaxComplete'));
	});

	/**
	 * Bridge `jQuery(el).trigger(name)` to native `dispatchEvent` for
	 * Prado-managed elements. Pre-4.4 controls registered handlers through
	 * the jQuery event bus and fired them with `jQuery(el).trigger('name')`.
	 * The framework's `observe` now uses native `addEventListener`, and
	 * jQuery `.trigger()` does not reach native listeners (see
	 * https://github.com/jquery/jquery/issues/2476), so pre-4.4 calls
	 * become no-ops without this bridge.
	 *
	 * Behavior:
	 *   - Elements not registered in `Prado.Registry` keep the original
	 *     jQuery `.trigger()` semantics. No regression for pure-jQuery
	 *     controls.
	 *   - Klass-registered elements receive `el.dispatchEvent(CustomEvent)`.
	 *     This reaches both native `addEventListener` handlers AND any
	 *     jQuery-bound handlers (because jQuery itself attaches via
	 *     `addEventListener` under the hood).
	 *   - Forms get `el.submit()` after the event so they actually submit.
	 *     `<input type="submit">` / `<button>` clicks call `el.click()` so
	 *     the browser performs the default activation behavior (submit,
	 *     toggle, navigate). Synthetic events alone do not fire defaults.
	 *
	 * @since 4.4.0
	 */
	const originalTrigger = jQuery.fn.trigger;
	jQuery.fn.trigger = function (type, extraParameters) {
		const isEventObject = type && typeof type === 'object';
		const eventType = isEventObject ? type.type : type;

		// Partition the wrapper into Prado-managed (klass) and non-klass
		// elements. We only deviate from jQuery's behavior for klass
		// elements; everything else goes through jQuery's own trigger
		// pipeline exactly as before.
		const $klass = this.filter(function () {
			const reg = this.id && Prado.Registry[this.id];
			return !!(reg && reg.constructor && reg.constructor.isPradoClass);
		});
		const $rest = this.not($klass);

		// Klass path: dispatch native events, with the right activation
		// special-cases so default behavior (form submit, focus, click)
		// still happens.
		$klass.each(function () {
			const el = this;
			if (!el.dispatchEvent || !eventType) return;

			// Form submit: dispatchEvent does not submit. Match
			// jQuery .trigger('submit') by firing the cancellable event
			// then calling native submit() if not preventDefault'd.
			if (eventType === 'submit' && el.tagName === 'FORM') {
				const evt = new Event('submit', { bubbles: true, cancelable: true });
				if (el.dispatchEvent(evt)) el.submit();
				return;
			}

			// Activation events: jQuery .trigger('click') / 'focus' /
			// 'blur' invoke the native method, which fires the real
			// event AND runs the default action (form submission for
			// submit buttons, focus state changes, etc.). dispatchEvent
			// skips defaults because synthetic events have
			// isTrusted === false.
			if (
				(eventType === 'click' || eventType === 'focus' || eventType === 'blur')
				&& typeof el[eventType] === 'function'
			) {
				el[eventType]();
				return;
			}

			const nativeEvent = (isEventObject && type instanceof Event)
				? type
				: new CustomEvent(eventType, {
					bubbles: true,
					cancelable: true,
					detail: extraParameters,
				});
			el.dispatchEvent(nativeEvent);
		});

		// Non-klass path: one delegation to jQuery's original trigger on
		// the remaining subset. Preserves all of jQuery's specialized
		// event hooks (event.special.X) and per-event-type defaults.
		if ($rest.length) originalTrigger.call($rest, type, extraParameters);

		return this;
	};
}
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
Prado.PostBack = Prado.Class(
{
	options : {},

	initialize(options, event) {
		Object.assign(this.options, options || {});
		this.event = event;
		this.doPostBack();
	},

	getForm() {
		return document.getElementById(this.options['FormID']);
	},

	doPostBack() {
		const form = this.getForm();
		if(this.options['CausesValidation'] && typeof(Prado.Validation) != "undefined")
		{
			if(!Prado.Validation.validate(this.options['FormID'], this.options['ValidationGroup'], document.getElementById(this.options['ID'])))
				return this.event.preventDefault();
		}

		if(this.options['PostBackUrl'] && this.options['PostBackUrl'].length > 0)
			form.action = this.options['PostBackUrl'];

		if(this.options['TrackFocus'])
		{
			const lastFocus = document.getElementById('PRADO_LASTFOCUS');
			if(lastFocus)
			{
				const active = document.activeElement;
				if(active)
					lastFocus.value = active.id;
				else
					lastFocus.value = this.options['EventTarget'];
			}
		}

		let input=null;
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

		if (!form) return;
		// Match jQuery $(form).trigger('submit'): fire a cancellable submit event
		// so handlers can preventDefault, then call form.submit() (which skips
		// HTML5 constraint validation — important because Prado does its own
		// validation in JS and many emitted forms don't carry HTML5 constraints
		// that would silently abort form.requestSubmit()).
		const evt = new Event('submit', { bubbles: true, cancelable: true });
		if (form.dispatchEvent(evt)) form.submit();
	}
});

/**
 * Prado utilities to manipulate DOM elements.
 * @object Prado.Element
 */
Prado.Element =
{
	/**
	 * Executes a jQuery method on a particular element. Retained as a
	 * passthrough for PHP-side controls that emit calls like
	 * Prado.Element.j('foo', 'fadeIn', [200]). Requires jQuery to be
	 * loaded at the page level.
	 * @function ?
	 * @param {string} element - Element id
	 * @param {string} method - method name
	 * @param {array} value - method parameters
	 */
	j(element, method, params) {
		const obj=jQuery(`#${element}`);
		obj[method].apply(obj, params);
	},

	/**
	 * Select options from a selectable element.
	 * @function ?
	 * @param {string} element - Element id
	 * @param {string} method - Name of any {@see Prado.Element.Selection} method
	 * @param {array|boolean|string} value - Values that should be selected
	 * @param {int} total - Number of elements
	 */
	select(element, method, value, total) {
		const el = document.getElementById(element);
		if(!el) return;
		const selection = Prado.Element.Selection;
		if(typeof(selection[method]) == "function")
		{
			const control = selection.isSelectable(el) ? [el] : selection.getListElements(element,total);
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
	setAttribute(element, attribute, value) {
		const el = document.getElementById(element);
		if(!el) return;
		// "disabled" is for <button>, <fieldset>, <input>, <optgroup>, <option>, <select>, <textarea>
		// "readonly is for <input>, <textarea>
		// global presence attributes: hidden, inert, popover
		// removed 'href' and 'multiple' was a hack - they use removeAttribute now
		if((attribute == "disabled" || attribute == "readonly" || attribute == "hidden" || attribute == "inert" || attribute == "popover") && value==false)
			el.removeAttribute(attribute);
		else if(attribute.match(/^on/i)) //event methods
		{
			try
			{
				// Compile the server-emitted handler body as a function of
				// `event` and assign it to the DOM property (onclick/onchange/etc.).
				// `new Function` keeps the closure scope local instead of leaking
				// a global the way `eval` would.
				el[attribute] = new Function('event', value);
			}
			catch(_e)
			{
				throw `Error in evaluating '${value}' for attribute ${attribute} for element ${element}`;
			}
		}
		else
			el.setAttribute(attribute, value);
	},

	/**
	 * Removes an attribute of a DOM element.
	 * @function ?
	 * @param {string} element - Element id
	 * @param {string} attribute - Name of attribute
	 * @since 4.3.3
	 */
	removeAttribute(element, attribute) {
		const el = document.getElementById(element);
		if(!el) return;
		el.removeAttribute(attribute);
	},

	scrollTo(element, options) {
		const op = { offset : 50, ...(options || {}) };
		const el = document.getElementById(element);
		if (!el) return;
		const top = el.getBoundingClientRect().top + window.pageYOffset - op.offset;
		window.scrollTo({ top, behavior: 'smooth' });
	},

	focus(element) {
		const doFocus = () => {
			const el = document.getElementById(element);
			if (el) el.focus();
		};
		// If a callback request is in flight, defer briefly so we focus the
		// post-update DOM. Prado.CallbackRequest tracks in-flight requests
		// in its requestQueue (see ajax3.js).
		const pending = (typeof Prado !== 'undefined'
			&& Prado.CallbackRequest
			&& Prado.CallbackRequest.requestQueue
			&& Prado.CallbackRequest.requestQueue.length) > 0;
		if (pending) setTimeout(doFocus, 100);
		else doFocus();
	},

	/**
	 * Sets the options for a select element.
	 * @function ?
	 * @param {string} element - Element id
	 * @param {array[]} options - Array of options, each an array of structure
	 *   [ "optionText" , "optionValue" , "optionGroup" ]
	 */
	setOptions(element, options) {
		const el = document.getElementById(element);
		if(el && el.tagName.toLowerCase() == "select")
		{
			while(el.childNodes.length > 0)
				el.removeChild(el.lastChild);

			const optDom = Prado.Element.createOptions(options);
			for(let i = 0; i < optDom.length; i++)
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
	createOptions(options) {
		let previousGroup = null;
		let optgroup=null;
		const result = [];
		for(let i = 0; i<options.length; i++)
		{
			const option = options[i];
			if(option.length > 2)
			{
				const group = option[2];
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
			const opt = document.createElement('option');
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
	replace(element, content, boundary, self) {
		if(boundary)
		{
			const result = this.extractContent(boundary);
			if(result != null)
				content = result;
		}
		const el = document.getElementById(element);
		if(!el) return;
		if(self)
		  el.outerHTML = content;
		else
		  el.innerHTML = content;
		// innerHTML / outerHTML parse <script> tags but the HTML spec marks
		// them as already-executed, so the browser refuses to run them. jQuery's
		// .html() / .replaceWith() worked around this by re-creating script
		// nodes. Mirror that so Prado callback responses that embed inline
		// JavaScript continue to execute.
		const container = self ? el.parentNode : el;
		if (container) Prado.Element.executeScripts(container);
	},

	/**
	 * Re-evaluate every <script> element under `root`. Used after
	 * Prado.Element.replace() to mimic jQuery .html() / .replaceWith()
	 * behavior, which executes inline scripts injected via innerHTML.
	 * @function ?
	 * @param {element} root - DOM subtree to scan
	 * @since 4.4.0
	 */
	executeScripts(root) {
		const scripts = root.querySelectorAll('script');
		for (const oldScript of scripts) {
			const fresh = document.createElement('script');
			for (const attr of oldScript.attributes) fresh.setAttribute(attr.name, attr.value);
			fresh.text = oldScript.textContent;
			oldScript.parentNode.replaceChild(fresh, oldScript);
		}
	},

	/**
	 * Appends a javascript block to the document.
	 * @function ?
	 * @param {string} boundary - Boundary containing the javascript code
	 */
	appendScriptBlock(boundary) {
		const content = this.extractContent(boundary);
		if(content == null)
			return;

		const el   = document.createElement("script");
		el.type  = "text/javascript";
		el.id    = `inline_${boundary}`;
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
	evaluateScript(content, boundary) {
		if(boundary)
		{
			const result = this.extractContent(boundary);
			if(result != null)
				content = result;
		}

		try
		{
			// Evaluate in global scope (jQuery.globalEval equivalent).
			// The indirect (0, eval) call forces script to run as global code.
			// eslint-disable-next-line no-eval
			(0, eval)(content);
		}
		catch(e)
		{
			if(typeof(Logger) != "undefined")
				Logger.error(`Error during evaluation of script "${content}"`);
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
	isSelectable(el) {
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
	inputValue(el, value) {
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
	selectValue(elements, value) {
		for (const el of elements) {
			for (const option of el.options) {
				if(typeof(value) == "boolean")
					option.selected = value;
				else if(option.value == value)
					option.selected = true;
			}
		}
	},

	/**
	 * Set selected attribute for elements options by array of values.
	 * @function ?
	 * @param {element[]} elements - Array of selectable DOM elements
	 * @param {string[]} value - Array of values to select
	 */
	selectValues(elements, values) {
		for (const value of values) this.selectValue(elements, value);
	},

	/**
	 * Set selected attribute for elements options by option index.
	 * @function ?
	 * @param {element[]} elements - Array of selectable DOM elements
	 * @param {int} index - Index of option to select
	 */
	selectIndex(elements, index) {
		for (const el of elements) {
			if(el.type.toLowerCase() == 'select-one')
				el.selectedIndex = index;
			else
			{
				for(let i = 0; i<el.length; i++)
				{
					if(i == index)
						el.options[i].selected = true;
				}
			}
		}
	},

	/**
	 * Set selected attribute to true for all elements options.
	 * @function ?
	 * @param {element[]} elements - Array of selectable DOM elements
	 */
	selectAll(elements) {
		for (const el of elements) {
			if(el.type.toLowerCase() != 'select-one')
				for (const option of el.options) option.selected = true;
		}
	},

	/**
	 * Toggle the selected attribute for elements options.
	 * @function ?
	 * @param {element[]} elements - Array of selectable DOM elements
	 */
	selectInvert(elements) {
		for (const el of elements) {
			if(el.type.toLowerCase() != 'select-one')
				for (const option of el.options) option.selected = !option.selected;
		}
	},

	/**
	 * Set selected attribute for elements options by array of option indices.
	 * @function ?
	 * @param {element[]} elements - Array of selectable DOM elements
	 * @param {int[]} indices - Array of option indices to select
	 */
	selectIndices(elements, indices) {
		for (const index of indices) this.selectIndex(elements, index);
	},

	/**
	 * Unselect elements.
	 * @function ?
	 * @param {element[]} elements - Array of selectable DOM elements
	 */
	selectClear(elements) {
		for (const el of elements) el.selectedIndex = -1;
	},

	/**
	 * Get list elements of an element.
	 * @function {element[]} ?
	 * @param {element[]} elements - Array of selectable DOM elements
	 * @param {int} total - Number of list elements to return
	 * @returns Array of list DOM elements
	 */
	getListElements(element, total) {
		const elements = new Array();
		let el;
		for(let i = 0; i < total; i++)
		{
			el = document.getElementById(`${element}_c${i}`);
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
	checkValue(elements, value) {
		for (const el of elements) {
			if(typeof(value) == "boolean")
				el.checked = value;
			else if(el.value == value)
				el.checked = true;
		}
	},

	/**
	 * Set checked attribute of elements by array of values.
	 * @function ?
	 * @param {element[]} elements - Array of checkable DOM elements
	 * @param {string[]} values - Values that should be checked
	 *
	 */
	checkValues(elements, values) {
		for (const value of values) this.checkValue(elements, value);
	},

	/**
	 * Set checked attribute of elements by list index.
	 * @function ?
	 * @param {element[]} elements - Array of checkable DOM elements
	 * @param {int} index - Index of element to set checked
	 */
	checkIndex(elements, index) {
		for(let i = 0; i<elements.length; i++)
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
	checkIndices(elements, indices) {
		for (const index of indices) this.checkIndex(elements, index);
	},

	/**
	 * Uncheck elements.
	 * @function ?
	 * @param {element[]} elements - Array of checkable DOM elements
	 */
	checkClear(elements) {
		for (const el of elements) el.checked = false;
	},

	/**
	 * Set checked attribute of all elements to true.
	 * @function ?
	 * @param {element[]} elements - Array of checkable DOM elements
	 */
	checkAll(elements) {
		for (const el of elements) el.checked = true;
	},

	/**
	 * Toggle the checked attribute of elements.
	 * @function ?
	 * @param {element[]} elements - Array of selectable DOM elements
	 */
	checkInvert(elements) {
		for (const el of elements) el.checked = !el.checked;
	}
};

Object.assign(String.prototype, {

	/**
	 * Add padding to string
	 * @function {string} ?
	 * @param {string} side - "left" to pad the string on the left, "right" to pad right.
	 * @param {int} len - Minimum string length.
	 * @param {string} chr - Character(s) to pad
	 * @returns Padded string
	 */
	pad(side, len, chr) {
		if (!chr) chr = ' ';
		let s = this;
		const left = side.toLowerCase()=='left';
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
	padLeft(len, chr) {
		return this.pad('left',len,chr);
	},

	/**
	 * Add right padding to string
	 * @function {string} ?
	 * @param {int} len - Minimum string length.
	 * @param {string} chr - Character(s) to pad
	 * @returns Padded string
	 */
	padRight(len, chr) {
		return this.pad('right',len,chr);
	},

	/**
	 * Add zeros to the right of string
	 * @function {string} ?
	 * @param {int} len - Minimum string length.
	 * @returns Padded string
	 */
	zerofill(len) {
		return this.padLeft(len,'0');
	},

	/**
	 * Remove white spaces from both ends of string.
	 * @function {string} ?
	 * @returns Trimmed string
	 */
	trim() {
		return this.replace(/^\s+|\s+$/g,'');
	},

	/**
	 * Remove white spaces from the left side of string.
	 * @function {string} ?
	 * @returns Trimmed string
	 */
	trimLeft() {
		return this.replace(/^\s+/,'');
	},

	/**
	 * Remove white spaces from the right side of string.
	 * @function {string} ?
	 * @returns Trimmed string
	 */
	trimRight() {
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
	toFunction() {
		const commands = this.split(/\./);
		let command = window;
		for (const action of commands) {
			if(command[new String(action)])
				command=command[new String(action)];
		}
		if(typeof(command) == "function")
			return command;
		else
		{
			if(typeof Logger != "undefined")
				Logger.error("Missing function", this);

			throw new Error	(`Missing function '${this}'`);
		}
	},

	/**
	 * Convert string into integer, returns null if not integer.
	 * @function {int} ?
	 * @returns Integer, null if string does not represent an integer.
	 */
	toInteger() {
		const exp = /^\s*[-+]?\d+\s*$/;
		if (this.match(exp) == null)
			return null;
		const num = parseInt(this, 10);
		return (isNaN(num) ? null : num);
	},

	/**
	 * Convert string into a double/float value. <b>Internationalization
	 * is not supported</b>
	 * @function {double} ?
	 * @param {string} decimalchar - Decimal character, defaults to "."
	 * @returns Double, null if string does not represent a float value
	 */
	toDouble(decimalchar) {
		if(this.length <= 0) return null;
		decimalchar = decimalchar || ".";
		const exp = new RegExp(`^\\s*([-\\+])?(\\d+)?(\\${decimalchar}(\\d+))?\\s*$`);
		const m = this.match(exp);

		if (m == null)
			return null;
		m[1] = m[1] || "";
		m[2] = m[2] || "0";
		m[4] = m[4] || "0";

		const cleanInput = `${m[1] + (m[2].length>0 ? m[2] : "0")}.${m[4]}`;
		const num = parseFloat(cleanInput);
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
	toCurrency(groupchar, digits, decimalchar) {
		groupchar = groupchar || ",";
		decimalchar = decimalchar || ".";
		digits = typeof(digits) == "undefined" ? 2 : digits;

		const exp = new RegExp(`^\\s*([-\\+])?(((\\d+)\\${groupchar})*)(\\d+)${(digits > 0) ? `(\\${decimalchar}(\\d{1,${digits}}))?` : ""}\\s*$`);
		const m = this.match(exp);
		if (m == null)
			return null;
		m[1] = m[1] || "";
		m[2] = m[2] || "";
		const intermed = m[2] + m[5];
		const cleanInput = m[1] + intermed.replace(
				new RegExp(`(\\${groupchar})`, "g"), "")
								+ ((digits > 0) ? `.${m[7]}` : "");
		const num = parseFloat(cleanInput);
		return (isNaN(num) ? null : num);
	},
	
	/**
	 * Appends 'px' at the end if it is not there.
	 * @function {string} ?
	 * @returns string with 'px' at the end
	 * @since 4.2.0
	 */
	px() {
		return this.endsWith('px') ? this : `${this}px`;
	}
});

Object.assign(Number.prototype,
{
	/**
	 * Appends 'px' at the end of the number.
	 * @function {string} ?
	 * @returns string with number plus 'px' at the end
	 * @since 4.2.0
	 */
	px() {
		return `${this}px`;
	}
});

Object.assign(Date.prototype,
{
	/**
	 * SimpleFormat
	 * @function ?
	 * @param {string} format - TODO
	 * @param {string} data - TODO
	 * @returns TODO
	 */
	SimpleFormat(format, data) {
		data = data || {};
		const bits = new Array();
		bits['d'] = this.getDate();
		bits['dd'] = String(this.getDate()).zerofill(2);

		bits['M'] = this.getMonth()+1;
		bits['MM'] = String(this.getMonth()+1).zerofill(2);
		if(data.AbbreviatedMonthNames)
			bits['MMM'] = data.AbbreviatedMonthNames[this.getMonth()];
		if(data.MonthNames)
			bits['MMMM'] = data.MonthNames[this.getMonth()];
		let yearStr = `${this.getFullYear()}`;
		yearStr = (yearStr.length == 2) ? `19${yearStr}`: yearStr;
		bits['yyyy'] = yearStr;
		bits['yy'] = bits['yyyy'].toString().substr(2,2);

		// do some funky regexs to replace the format string
		// with the real values
		let frm = new String(format);
		for (const sect in bits)
		{
			const reg = new RegExp(`\\b${sect}\\b` ,"g");
			frm = frm.replace(reg, bits[sect]);
		}
		return frm;
	},

	/**
	 * toISODate
	 * @function {string} ?
	 * @returns TODO
	 */
	toISODate() {
		const y = this.getFullYear();
		const m = String(this.getMonth() + 1).zerofill(2);
		const d = String(this.getDate()).zerofill(2);
		return String(y) + String(m) + String(d);
	}
});

Object.assign(Date,
{
	/**
	 * SimpleParse
	 * @function ?
	 * @param {string} format - TODO
	 * @param {string} data - TODO
	 * @returns TODO
	 */
	SimpleParse(value, format) {
		const val=String(value);
		format=String(format);

		if(val.length <= 0) return null;

		if(format.length <= 0) return new Date(value);

		const isInteger = val => {
			const digits="1234567890";
			for (let i=0; i < val.length; i++)
			{
				if (digits.indexOf(val.charAt(i))==-1) { return false; }
			}
			return true;
		};

		const getInt = (str, i, minlength, maxlength) => {
			for (let x=maxlength; x>=minlength; x--)
			{
				const token=str.substring(i,i+x);
				if (token.length < minlength) { return null; }
				if (isInteger(token)) { return token; }
			}
			return null;
		};

		let i_val=0;
		let i_format=0;
		let c="";
		let token="";
		let x, y;
		const now=new Date();
		let year=now.getFullYear();
		let month=now.getMonth()+1;
		let date=1;

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

		const newdate = new Date(year,month-1,date, 0, 0, 0);
		return newdate;
	}
});
