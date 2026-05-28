/*! PRADO Ajax javascript file | github.com/pradosoft/prado */

Prado.CallbackRequestManager =
{
	/**
	 * Callback request target POST field name.
	 */
	FIELD_CALLBACK_TARGET : 'PRADO_CALLBACK_TARGET',
	/**
	 * Callback request parameter POST field name.
	 */
	FIELD_CALLBACK_PARAMETER : 'PRADO_CALLBACK_PARAMETER',
	/**
	 * Callback request page state field name,
	 */
	FIELD_CALLBACK_PAGESTATE : 'PRADO_PAGESTATE',
	/**
	 * Response redirect header name.
	 */
	REDIRECT_HEADER : 'X-PRADO-REDIRECT',
	/**
	 * Response data header name.
	 */
	DATA_HEADER : 'X-PRADO-DATA',
	/**
	 * Response javascript execution statement header name.
	 */
	ACTION_HEADER : 'X-PRADO-ACTIONS',
	/**
	 * Response errors/exceptions header name.
	 */
	ERROR_HEADER : 'X-PRADO-ERROR',
	/**
	 * Response debug header name.
	 */
	DEBUG_HEADER : 'X-PRADO-DEBUG',
	/**
	 * Page state header name.
	 */
	PAGESTATE_HEADER : 'X-PRADO-PAGESTATE',
	/**
	 * Script list header name.
	 */
	SCRIPTLIST_HEADER : 'X-PRADO-SCRIPTLIST',
	/**
	 * Stylesheet code header name.
	 */
	STYLESHEET_HEADER : 'X-PRADO-STYLESHEET',
	/**
	 * Stylesheet list header name.
	 */
	STYLESHEETLIST_HEADER : 'X-PRADO-STYLESHEETLIST',
	/**
	 * Hidden field list header name.
	 */
	HIDDENFIELDLIST_HEADER : 'X-PRADO-HIDDENFIELDLIST',
	/**
	 * Log debug informations when a callback fails, default true
	 */
	LOG_ERROR : true,
	/**
	 * Log debug informations when a callback succedes, default false
	 */
	LOG_SUCCESS : false,

	/**
	 * Formats the exception message for display in console.
	 */
	logFormatException(log, e) {
		log.info(`${e.type} with message "${e.message}" in ${e.file}(${e.line})`);
		log.info("Stack trace:");
		const trace = e.trace;
		let args;
		for(let i = 0; i<trace.length; i++)
		{
			const msg = `#${i} ${trace[i].file}(${trace[i].line})`;
			if(i == 0)
			{
				if(typeof log.group === "function")
					log.group(msg);
				else
					log.info(msg);
			} else {
				if(typeof log.groupCollapsed === "function")
					log.groupCollapsed(msg);
				else
					log.info(msg);
			}

			if(trace[i]["args"] === undefined) {
				args = "";
			} else {
				args = trace[i]["args"].join(", ");
			}
			log.info(`${trace[i]["class"]}->${trace[i]["function"]}(${args})`);

			if(typeof log.groupEnd === "function")
				log.groupEnd();
		}
		log.info(`${e.version} ${e.time}`);
	},

	/**
	 * Formats the debug message for display in console.
	 */
	logDebug(log, blocks) {
		const groupFunc = blocks.length < 10 ? 'group': 'groupCollapsed';
		if(typeof log[groupFunc] === "function")
			log[groupFunc](`Callback logs (${blocks.length} entries)`);

		for(let i = 0; i<blocks.length; i++)
		{
			log[blocks[i][0]](`[${blocks[i][1]}] [${blocks[i][2]}] ${blocks[i][3]}`);
		}

		if(typeof log.groupEnd === "function")
			log.groupEnd();
	},

	/*! jQuery Ajax Queue - v0.1.2pre - 2013-03-19
	* https://github.com/gnarf37/jquery-ajaxQueue
	* Copyright (c) 2013 Corey Frang; Licensed MIT
	* Slightly adapted for use within prado by Fabio Bas <ctrlaltca@gmail.com>
	*/

	// jQuery on an empty object, we are going to use this as our Queue
	ajaxQueue : jQuery({}),

	ajax(ajaxOpts) {
        let jqXHR;
        const dfd = jQuery.Deferred();
        const promise = dfd.promise();

        // run the actual query
        function doRequest( next ) {
			// Add request data just before send to have it actual
			ajaxOpts.data = ajaxOpts.context.getParameters();
			jqXHR = jQuery.ajax( ajaxOpts );
			jqXHR.done( dfd.resolve )
				.fail( dfd.reject )
				.then( next, next );
		}

        // queue our ajax request
        Prado.CallbackRequestManager.ajaxQueue.queue( doRequest );

        // add the abort method
        promise.abort = statusText => {

			// proxy abort to the jqXHR if it is active
			if ( jqXHR ) {
				return jqXHR.abort( statusText );
			}

			// if there wasn't already a jqXHR we need to remove from queue
			const queue = Prado.CallbackRequestManager.ajaxQueue.queue(), index = jQuery.inArray( doRequest, queue );

			if ( index > -1 ) {
				queue.splice( index, 1 );
			}

			// and then reject the deferred
			dfd.rejectWith( ajaxOpts.context || ajaxOpts, [ promise, statusText, "" ] );
			return promise;
		};

        return promise;
    }
};

/**
 * Serialize every `<input>`, `<select>` and `<textarea>` on the page into
 * an application/x-www-form-urlencoded string. Matches the legacy behavior
 * of `jQuery('input, select, textarea').serialize()` which ignored form
 * boundaries and skipped:
 *   - disabled controls
 *   - elements without a `name`
 *   - file inputs
 *   - submit/button/image/reset inputs
 *   - unchecked checkboxes and radios
 */
Prado.CallbackRequest_serializePageInputs = function() {
	const out = [];
	const enc = (s) => encodeURIComponent(s).replace(/%20/g, '+');
	const skipTypes = new Set(['file', 'submit', 'reset', 'button', 'image']);
	const fields = document.querySelectorAll('input, select, textarea');
	for (const el of fields) {
		if (el.disabled || !el.name) continue;
		const tag = el.tagName.toLowerCase();
		if (tag === 'input') {
			const t = (el.type || 'text').toLowerCase();
			if (skipTypes.has(t)) continue;
			if ((t === 'checkbox' || t === 'radio') && !el.checked) continue;
			out.push(`${enc(el.name)}=${enc(el.value)}`);
		} else if (tag === 'select') {
			for (const opt of el.options) {
				if (opt.selected) out.push(`${enc(el.name)}=${enc(opt.value)}`);
			}
		} else { // textarea
			out.push(`${enc(el.name)}=${enc(el.value)}`);
		}
	}
	return out.join('&');
};

Prado.CallbackRequest = Prado.Class(Prado.PostBack,
{

	options : {},
	data    : '',

	initialize(id, options) {
		this.options = {
			RequestTimeOut : 30000, // 30 second timeout.
			EnablePageStateUpdate : true,
			CausesValidation : true,
			ValidationGroup : null,
			PostInputs : true,
			RetryLimit : 1,

			type: "POST",
			context:  this,
			success:  this.successHandler,
			error:    this.errorHandler,
			complete: this.completeHandler
		};

		Object.assign(this.options, options || {});

		if(this.options.onUninitialized)
			this.options.onUninitialized(this,null);
	},

	/**
	 * Sets the request options
	 * @return {Array} request options.
	 */
	setOptions(options) {
		Object.assign(this.options, options || {});
	},

	getForm() {
		const el = document.getElementById(this.options.ID);
		const owned = el && el.closest('form');
		if (owned) return owned;
		const ps = document.getElementById('PRADO_PAGESTATE');
		return ps ? ps.form : null;
	},

	/**
	 * Gets the url from the forms that contains the PRADO_PAGESTATE
	 * @return {String} callback url.
	 */
	getCallbackUrl() {
		return this.getForm().action;
	},

	/**
	 * Sets the request parameter
	 * @param {Object} parameter value
	 */
	setCallbackParameter(value) {
		this.options['CallbackParameter'] = value;
	},

	/**
	 * @return {Object} request paramater value.
	 */
	getCallbackParameter() {
		return JSON.stringify(this.options['CallbackParameter']);
	},

	/**
	 * Sets the callback request timeout.
	 * @param {integer} timeout in  milliseconds
	 */
	setRequestTimeOut(timeout) {
		this.options['RequestTimeOut'] = timeout;
	},

	/**
	 * @return {integer} request timeout in milliseconds
	 */
	getRequestTimeOut() {
		return this.options['RequestTimeOut'];
	},

	/**
	 * Set true to enable validation on callback dispatch.
	 * @param {boolean} true to validate
	 */
	setCausesValidation(validate) {
		this.options['CausesValidation'] = validate;
	},

	/**
	 * @return {boolean} validate on request dispatch
	 */
	getCausesValidation() {
		return this.options['CausesValidation'];
	},

	/**
	 * Sets the validation group to validate during request dispatch.
	 * @param {string} validation group name
	 */
	setValidationGroup(group) {
		this.options['ValidationGroup'] = group;
	},

	/**
	 * @return {string} validation group name.
	 */
	getValidationGroup() {
		return this.options['ValidationGroup'];
	},

	/**
	 * Sets the number of retries before an ajax callback is considered failed
	 * @param {integer}
	 */
	setRetryLimit(limit) {
		this.options['RetryLimit'] = limit;
	},

	/**
	 * @return {integer} number of retries before an ajax callback is considered failed
	 */
	getRetryLimit() {
		return this.options['RetryLimit'];
	},

	dispatch() {
		//trigger tinyMCE to save data.
		if(typeof tinyMCE != "undefined")
			tinyMCE.triggerSave();

		if(this.options['CausesValidation'] && typeof(Prado.Validation) != "undefined")
		{
			if(!Prado.Validation.validate(this.getForm().id, this.options['ValidationGroup'], this))
				return false;
		}

		if(this.options.onPreDispatch)
			this.options.onPreDispatch(this,null);

		// prepare callback paramters
		this.options.url = this.getCallbackUrl();
		this.options.timeout = this.getRequestTimeOut();

		// jQuery don't have all these states.. simulate them to avoid breaking old scripts
		if (this.options.onLoading)
			this.options.onLoading(this,null);
		if (this.options.onLoaded)
			this.options.onLoaded(this,null);
		if (this.options.onInteractive)
			this.options.onInteractive(this,null);

		this.request = Prado.CallbackRequestManager.ajax(this.options);
	},

	abort() {
		if(this.request != "undefined")
			this.request.abort();
	},

	/**
	 * Collects the form inputs, encode the parameters, and sets the callback
	 * target id. The resulting string is the request content body.
	 * @return string request body content containing post data.
	 */
	getParameters() {
		const data = {};

		if(typeof(this.options.CallbackParameter) != "undefined")
			data[Prado.CallbackRequestManager.FIELD_CALLBACK_PARAMETER] = this.getCallbackParameter();
		if(this.options.EventTarget)
			data[Prado.CallbackRequestManager.FIELD_CALLBACK_TARGET] = this.options.EventTarget;

		if(this.options.PostInputs != false)
		{
			const form = this.getForm();
			// Serialize every form-bearing input on the page (matches the legacy
			// behavior of jQuery('input, select, textarea').serialize() which
			// ignored form boundaries) plus the extra `data` object.
			return `${Prado.CallbackRequest_serializePageInputs()}&${new URLSearchParams(data).toString()}`;
		} else {
			const pagestate = document.getElementById(Prado.CallbackRequestManager.FIELD_CALLBACK_PAGESTATE);
			if(pagestate)
				data[Prado.CallbackRequestManager.FIELD_CALLBACK_PAGESTATE] = pagestate.value;
			return new URLSearchParams(data).toString();
		}
	},

	/**
	 * Extract content from a text by its boundary id.
	 * Boundaries have this form:
	 * <pre>
	 * &lt;!--123456--&gt;Democontent&lt;!--//123456--&gt;
	 * </pre>
	 * @function {string} ?
	 * @param {string} boundary - Boundary id
	 * @returns Content from given boundaries
	 */
	extractContent(boundary) {
		const tagStart = `<!--${boundary}-->`;
		const tagEnd = `<!--//${boundary}-->`;
		let start = this.data.indexOf(tagStart);
		if(start > -1)
		{
			start += tagStart.length;
			const end = this.data.indexOf(tagEnd,start);
			if(end > -1)
				return this.data.substring(start,end);
		}
		return null;
	},

	getLogger() {
		if(typeof Logger != "undefined")
			return Logger;

		// use the browser console if no Logger is available
		if(typeof console != "undefined")
			return console;

		return null;
	},

	errorHandler(request, textStatus, errorThrown) {
		let log;
		this.data = request.responseText;

		if(Prado.CallbackRequestManager.LOG_ERROR && (log = this.getLogger()))
		{
			log.error("PRADO Ajax callback error:", request.status, `(${request.statusText})`);
			if(request.status==500)
			{
				/**
				 * Server returns 500 exception. Just log it.
				 */
				let errorData = this.extractContent(Prado.CallbackRequestManager.ERROR_HEADER);
				if (typeof(errorData) == "string" && errorData.length > 0)
				{
					errorData = JSON.parse(errorData);
					if(typeof(errorData) == "object")
						Prado.CallbackRequestManager.logFormatException(log, errorData);
				}
			}
		}

		if (textStatus == 'timeout') {
			if (--this.options.RetryLimit) {
				//try again
				this.dispatch();
				return;
			}
		}

		if (this.options.onFailure)
			this.options.onFailure(this,textStatus);
	},

	completeHandler(request, textStatus) {
//"success", "notmodified", "error", "timeout", "abort", or "parsererror"
		if (this.options.onComplete)
			this.options.onComplete(this,textStatus);
	},

	/**
	 * Uncaught exceptions during callback response.
	 */
	exceptionHandler(e) {
		let log;
		if(Prado.CallbackRequestManager.LOG_ERROR && (log = this.getLogger()))
		{
			log.error("Uncaught Callback Client Exception:", e.message);
			log.info('Stack:', e.stack);
		}

		if (this.options.onException)
			this.options.onException(this,e);
	},

	/**
	 * Callback OnSuccess event,logs reponse and data to console.
	 */
	successHandler(data, textStatus, request) {
		let log;
		this.data = data;

		if(Prado.CallbackRequestManager.LOG_SUCCESS && (log = this.getLogger()))
		{
			log.info(`HTTP ${request.status} with response : \n`);

			const tagStart = '<!--';
			const tagEnd = '<!--//';
			let start = request.responseText.indexOf(tagStart);
			while(start > -1)
			{
				const end = request.responseText.indexOf(tagEnd,start);
				if(end > -1)
					log.info(`${request.responseText.substring(start,end)}\n`);
				start = request.responseText.indexOf(tagStart,end+6);
			}
		}

		if (this.options.onSuccess)
		{
			let customData=this.extractContent(Prado.CallbackRequestManager.DATA_HEADER);
			if (typeof(customData) == "string" && customData.length > 0)
				customData = JSON.parse(customData);

			this.options.onSuccess(this,customData);
		}

		const redirectUrl = this.extractContent(Prado.CallbackRequestManager.REDIRECT_HEADER);
		if (redirectUrl)
				document.location.href = redirectUrl;

		this.outputDebug(this, data);

		try {
			this.updatePageState(this, data);
			this.checkHiddenFields(this, data);
			const obj = this;
			this.loadAssets(this, data, () => {
                try {
                    obj.dispatchActions(obj, data);
                } catch (e) {
                    obj.exceptionHandler(e);
                }
            }
			);

		} catch (e) {
			this.exceptionHandler(e);
		}
	},

	/**
	 * Updates the page state. It will update only if EnablePageStateUpdate is true.
	 */
	updatePageState(request, datain) {
		let log;
		const pagestate = document.getElementById(Prado.CallbackRequestManager.FIELD_CALLBACK_PAGESTATE);
		const enabled = request.options.EnablePageStateUpdate;
		const aborted = false; //typeof(self.currentRequest) == 'undefined' || self.currentRequest == null;
		if(enabled && !aborted && pagestate)
		{
			const data = this.extractContent(Prado.CallbackRequestManager.PAGESTATE_HEADER);
			if(typeof(data) == "string" && data.length > 0)
				pagestate.value = data;
			else
			{
				if(Prado.CallbackRequestManager.LOG_ERROR && (log = this.getLogger()))
					log.warn(`Missing page state:${data}`);
				//Logger.warn('## bad state: setting current request to null');
				//self.endCurrentRequest();
				//self.tryNextRequest();
				return false;
			}
		}
		//self.endCurrentRequest();
		//Logger.warn('## state updated: setting current request to null');
		//self.tryNextRequest();
		return true;
	},

	checkHiddenField(name, value) {
		const id = name.replace(':','_');
		if (!document.getElementById(id))
		{
			const field = document.createElement('input');
			field.setAttribute('type','hidden');
			field.id = id;
			field.name = name;
			field.value = value;
			document.body.appendChild(field);
		}
	},

	checkHiddenFields(request, datain) {
		let log, json;
		const data = this.extractContent(Prado.CallbackRequestManager.HIDDENFIELDLIST_HEADER);
		if (typeof(data) == "string" && data.length > 0)
		{
			json = JSON.parse(data);
			if(typeof(json) != "object")
			{
				if(Prado.CallbackRequestManager.LOG_ERROR && (log = this.getLogger()))
					log.warn(`Invalid hidden field list:${data}`);
			} else {
				for(const key in json)
					this.checkHiddenField(key,json[key]);
			}
		}
	},

	/*
	 * Checks which assets are used by the response and ensures they're loaded
	 */
	loadAssets(request, datain, callback) {
		/*

		  ! This is the callback-based loader for stylesheets, which loads them one-by-one, and
		  ! waits for all of them to be loaded before loading scripts and processing the rest of
		  ! the callback.
		  !
		  ! That however is not neccessary, as stylesheets can be loaded asynchronously too.
		  !
		  ! I leave this code here for the case that this turns out to be a compatibility issue
		  ! (for ex. I can imagine some scripts trying to access stylesheet properties and such)
		  ! so if need can be reactivated. If you do so, comment out the async stylesheet loader below!

		var obj = this;
		this.loadStyleSheets(request,transport, function() {
			obj.loadScripts(request,transport,callback);
		});

		*/

		this.loadStyleSheetsCode(request,datain);

		this.loadStyleSheetsAsync(request,datain);

		this.loadScripts(request,datain,callback);
	},

	/*
	 * Checks which scripts are used by the response and ensures they're loaded
	 */
	loadScripts(request, datain, callback) {
		let log, json;
		const data = this.extractContent(Prado.CallbackRequestManager.SCRIPTLIST_HEADER);
		if (!this.ScriptsToLoad) this.ScriptsToLoad = new Array();
		this.ScriptLoadFinishedCallback = callback;
		if (typeof(data) == "string" && data.length > 0)
		{
			json = JSON.parse(data);
			if(typeof(json) != "object")
			{
				if(Prado.CallbackRequestManager.LOG_ERROR && (log = this.getLogger()))
					log.warn(`Invalid script list:${data}`);
			} else {
				for(const key in json)
					if (/^\d+$/.test(key))
					{
						const url = json[key];
						if (!Prado.ScriptManager.isAssetLoaded(url))
							this.ScriptsToLoad.push(url);
					}
			}
		}
		this.loadNextScript();
	},

	loadNextScript() {
		const done = (!this.ScriptsToLoad || (this.ScriptsToLoad.length==0));
		if (!done)
			{
				const url = this.ScriptsToLoad.shift(); const obj = this;
				if (
					Prado.ScriptManager.ensureAssetIsLoaded(url,
						() => {
							obj.loadNextScript();
						}
					)
				   )
				   this.loadNextScript();
			}
		else
			{
				if (this.ScriptLoadFinishedCallback)
				{
					const cb = this.ScriptLoadFinishedCallback;
					this.ScriptLoadFinishedCallback = null;
					cb();
				}
			}
	},

	loadStyleSheetsCode(request, datain) {
		let log, json;
		const data = this.extractContent(Prado.CallbackRequestManager.STYLESHEET_HEADER);
		if (typeof(data) == "string" && data.length > 0)
		{
			json = JSON.parse(data);
			if(typeof(json) != "object")
			{
				if(Prado.CallbackRequestManager.LOG_ERROR && (log = this.getLogger()))
					log.warn(`Invalid stylesheet list:${data}`);
			} else {
				for(const key in json)
					if (/^\d+$/.test(key))
						Prado.StyleSheetManager.createStyleSheetCode(json[key],null);
			}
		}
	},

	loadStyleSheetsAsync(request, datain) {
		let log, json;
		const data = this.extractContent(Prado.CallbackRequestManager.STYLESHEETLIST_HEADER);
		if (typeof(data) == "string" && data.length > 0)
		{
			json = JSON.parse(data);
			if(typeof(json) != "object")
			{
				if(Prado.CallbackRequestManager.LOG_ERROR && (log = this.getLogger()))
					log.warn(`Invalid stylesheet list:${data}`);
			} else {
				for(const key in json)
					if (/^\d+$/.test(key))
						Prado.StyleSheetManager.ensureAssetIsLoaded(json[key],null);
			}
		}
	},

	loadStyleSheets(request, datain, callback) {
		let log, json;
		const data = this.extractContent(Prado.CallbackRequestManager.STYLESHEETLIST_HEADER);
		if (!this.StyleSheetsToLoad) this.StyleSheetsToLoad = new Array();
		this.StyleSheetLoadFinishedCallback = callback;
		if (typeof(data) == "string" && data.length > 0)
		{
			json = JSON.parse(data);
			if(typeof(json) != "object")
			{
				if(Prado.CallbackRequestManager.LOG_ERROR && (log = this.getLogger()))
					log.warn(`Invalid stylesheet list:${data}`);
			} else {
				for(const key in json)
					if (/^\d+$/.test(key))
					{
						const url = json[key];
						if (!Prado.StyleSheetManager.isAssetLoaded(url))
							this.StyleSheetsToLoad.push(url);
					}
			}
		}
		this.loadNextStyleSheet();
	},

	loadNextStyleSheet() {
		const done = (!this.StyleSheetsToLoad || (this.StyleSheetsToLoad.length==0));
		if (!done)
			{
				const url = this.StyleSheetsToLoad.shift(); const obj = this;
				if (
					Prado.StyleSheetManager.ensureAssetIsLoaded(url,
						() => {
							obj.loadNextStyleSheet();
						}
					)
				   )
				   this.loadNextStyleSheet();
			} else {
				if (this.StyleSheetLoadFinishedCallback)
				{
					const cb = this.StyleSheetLoadFinishedCallback;
					this.StyleSheetLoadFinishedCallback = null;
					cb();
				}
			}
	},

	/**
	 * Dispatch callback response actions.
	 */
	dispatchActions(request, datain) {
		let log, json;
		const data = this.extractContent(Prado.CallbackRequestManager.ACTION_HEADER);
		if (typeof(data) == "string" && data.length > 0)
		{
			json = JSON.parse(data);
			if(typeof(json) != "object")
			{
				if(Prado.CallbackRequestManager.LOG_ERROR && (log = this.getLogger()))
					log.warn(`Invalid action:${data}`);
			} else {
				const that = this;
				json.forEach((item, idx) => {
					that.__run(that, item);
				});
			}
		}
	},

	/**
	 * Output callback response debug.
	 */
	outputDebug(request, datain) {
		let log, json;
		const data = this.extractContent(Prado.CallbackRequestManager.DEBUG_HEADER);
		if (typeof(data) == "string" && data.length > 0 && (log = this.getLogger()))
		{
			json = JSON.parse(data);
			if(typeof(json) == "object")
			{
				Prado.CallbackRequestManager.logDebug(log, json);
			}
		}
	},

	/**
	 * Prase and evaluate a Callback clien-side action
	 */
	__run(request, command) {
		for(const method in command)
		{
			try {
				method.toFunction().apply(request,command[method]);
			} catch(e) {
				this.exceptionHandler(e);
			}
		}
	}
});

/**
 * Create a new callback request using default settings.
 * @param string callback handler unique ID.
 * @param mixed parameter to pass to callback handler on the server side.
 * @param function client side onSuccess event handler.
 * @param object additional request options.
 * @return Prado.CallbackRequest request that was created
 */
// Callable as both `Prado.Callback(id, ...)` and `new Prado.Callback(id, ...)` —
// PHP-emitted client script (TJuiDialogButton among others) uses the `new` form.
// Must be a regular function declaration, not an arrow function, so `new` works.
Prado.Callback = function(UniqueID, parameter, onSuccess, options) {
	const callback =
	{
		'EventTarget' : UniqueID || '',
		'CallbackParameter' : parameter || '',
		'onSuccess' : onSuccess || (() => {})
	};

	Object.assign(callback, options || {});

	const request = new Prado.CallbackRequest(UniqueID, callback);
	request.dispatch();
	return request;
};

/**
 * Create a new callback request initiated by jQuery-UI elements.
 * @param event object as sent by jQuery-UI events
 * @param ui object as sent by jQuery-UI events
 * @return Prado.CallbackRequest request that was created
 */
Prado.JuiCallback = (UniqueID, eventType, event, ui, target) => {
	// Retuns an array of all properties of the object received as parameter and their values.
	// If a property represent a jQuery element, its id is returnet instead
	const cleanUi = {};
	jQuery.each( ui, (key, value) => {
		if(value instanceof jQuery)
			cleanUi[key]=value[0].id;
		else
			cleanUi[key]=value;
	});

	target=jQuery(target);
	cleanUi['target']= {
		'position' : target.position(),
		'offset' : target.offset()
	};

	const callback =
	{
		'EventTarget' : UniqueID,
		'CallbackParameter' : {
			'event' : eventType,
			'ui' : cleanUi
		}
	};

	const request = new Prado.CallbackRequest(UniqueID, callback);
	request.dispatch();
	return request;
};

/**
* Asset manager classes for lazy loading of scripts and stylesheets
* @author Gabor Berczi (gabor.berczi@devworx.hu)
*/

if (typeof(Prado.AssetManagerClass)=="undefined") {

	Prado.AssetManagerClass = Prado.Class();
	Prado.AssetManagerClass.prototype = {

		initialize() {
			this.loadedAssets = new Array();
			this.discoverLoadedAssets();
		},


		/**
		 * Detect which assets are already loaded by page markup.
		 * This is done by looking up all <asset> elements and registering the values of their src attributes.
		 */
		discoverLoadedAssets() {

			// wait until document has finished loading to avoid javascript errors
			if (!document.body) return;

			const assets = this.findAssetUrlsInMarkup();
			for(let i=0;i<assets.length;i++)
				this.markAssetAsLoaded(assets[i]);
		},

		/**
		 * Extend url to a fully qualified url.
		 * @param string url
		 */
		makeFullUrl(url) {

			// this is not intended to be a fully blown url "canonicalizator",
			// just to handle the most common and basic asset paths used by Prado

			if (!this.baseUri) this.baseUri = window.location;

			if (url.indexOf('://')==-1)
			{
				const a = document.createElement('a');
				a.href = url;

				if (a.href.indexOf('://')!=-1)
					url = a.href;
				else
					{
						let path = a.pathname;
						if (path.substr(0,1)!='/') path = `/${path}`;
						url = `${this.baseUri.protocol}//${this.baseUri.host}${path}`;
					}
			}
			return url;
		},

		isAssetLoaded(url) {
			url = this.makeFullUrl(url);
			return this.loadedAssets.includes(url);
		},

		/**
		 * Mark asset as being already loaded
		 * @param string url of the asset
		 */
		markAssetAsLoaded(url) {
			url = this.makeFullUrl(url);
			if (!this.loadedAssets.includes(url))
				this.loadedAssets.push(url)
		},

		assetReadyStateChanged(url, element, callback, finalevent) {
			if (finalevent || (element.readyState == 'loaded') || (element.readyState == 'complete'))
			if (!element.assetCallbackFired)
			{
				element.assetCallbackFired = true;
				callback(url,element);
			}
		},

		assetLoadFailed(url, element, callback) {
			let log;
			element.assetCallbackFired = true;
			if(Prado.CallbackRequestManager.LOG_ERROR && (log = this.getLogger()))
				log.error(`Failed to load asset: ${url}`, this);
			if (!element.assetCallbackFired)
				callback(url,element,false);
		},

		/**
		 * Load a new asset dynamically into the page.
			 * Please not thet loading is asynchronous and therefore you can't assume that
		 * the asset is loaded and ready when returning from this function.
		 * @param string url of the asset to load
		 * @param callback will be called when the asset has loaded (or failed to load)
		 */
		startAssetLoad(url, callback) {

			// create new <asset> element in page header
			const asset = this.createAssetElement(url);

			if (callback)
			{
				asset.onreadystatechange = this.assetReadyStateChanged.bind(this, url, asset, callback, false);
				asset.onload = this.assetReadyStateChanged.bind(this, url, asset, callback, true);
				asset.onerror = this.assetLoadFailed.bind(this, url, asset, callback);
				asset.assetCallbackFired = false;
			}

			const head = document.getElementsByTagName('head')[0];
				head.appendChild(asset);

			// mark this asset as loaded
			this.markAssetAsLoaded(url);

			return (callback!=false);
		},

		/**
		 * Check whether a asset is loaded into the page, and if itsn't, load it now
		 * @param string url of the asset to check/load
		 * @return boolean returns true if asset is already loaded, or false, if loading has just started. callback will be called when loading has finished.
		 */
		ensureAssetIsLoaded(url, callback) {
			url = this.makeFullUrl(url);
			if (!this.loadedAssets.includes(url))
			{
				this.startAssetLoad(url,callback);
				return false;
			}
			else
				return true;
		}

	}

};

Prado.ScriptManagerClass = Prado.Class(Prado.AssetManagerClass, {

	findAssetUrlsInMarkup() {
		const urls = new Array();
		const scripts = document.getElementsByTagName('script');
		for(let i=0;i<scripts.length;i++)
		{
			const e = scripts[i]; const src = e.src;
			if (src!="")
				urls.push(src);
		}
		return urls;
	},

	createAssetElement(url) {
		const asset = document.createElement('script');
		asset.type = 'text/javascript';
		asset.src = url;
	//	asset.async = false; // HTML5 only
		return asset;
	}

});

Prado.StyleSheetManagerClass = Prado.Class(Prado.AssetManagerClass, {

	findAssetUrlsInMarkup() {
		const urls = new Array();
		const scripts = document.getElementsByTagName('link');
		for(let i=0;i<scripts.length;i++)
		{
			const e = scripts[i]; const href = e.href;
			if ((e.rel=="stylesheet") && (href.length>0))
				urls.push(href);
		}
		return urls;
	},

	createAssetElement(url) {
		const asset = document.createElement('link');
		asset.rel = 'stylesheet';
		asset.media = 'screen';
		asset.setAttribute('type', 'text/css');
		asset.href = url;
	//	asset.async = false; // HTML5 only
		return asset;
	},

	createStyleSheetCode(code) {
		const asset = document.createElement('style');
		asset.setAttribute('type', 'text/css');

		if(asset.styleSheet)
			asset.styleSheet.cssText = code; // IE7+IE8
		else {
			const cssCodeNode = document.createTextNode(code);
			asset.appendChild(cssCodeNode);
		}

		const head = document.getElementsByTagName('head')[0];
		head.appendChild(asset);
	}

});

if (typeof(Prado.ScriptManager)=="undefined") Prado.ScriptManager = new Prado.ScriptManagerClass();
if (typeof(Prado.StyleSheetManager)=="undefined") Prado.StyleSheetManager = new Prado.StyleSheetManagerClass();

// make sure we scan for loaded scripts again when the page has been loaded
const discover = () => {
	Prado.ScriptManager.discoverLoadedAssets();
	Prado.StyleSheetManager.discoverLoadedAssets();
};
if (window.attachEvent) window.attachEvent('onload', discover);
else if (window.addEventListener) window.addEventListener('load', discover, false);
