/**
 * Auto complete textbox via AJAX.
 */
Prado.AutoCompleter = Class.create();


/**
 * Overrides parent implementation of updateElement by trimming the value.
 */
Prado.AutoCompleter.Base = function(){};
Prado.AutoCompleter.Base.prototype = Object.extend(Autocompleter.Base.prototype,
{
  updateElement: function(selectedElement) 
  {
    if (this.options.updateElement) {
      this.options.updateElement(selectedElement);
      return;
    }

    var value = Element.collectTextNodesIgnoreClass(selectedElement, 'informal');
    var lastTokenPos = this.findLastToken();
    if (lastTokenPos != -1) {
      var newValue = this.element.value.substr(0, lastTokenPos + 1);
      var whitespace = this.element.value.substr(lastTokenPos + 1).match(/^\s+/);
      if (whitespace)
        newValue += whitespace[0];
      this.element.value = (newValue + value).trim();
    } else {
      this.element.value = value.trim();
    }
    this.element.focus();
    
    if (this.options.afterUpdateElement)
      this.options.afterUpdateElement(this.element, selectedElement);
  }
});

/**
 * Based on the Prototype Autocompleter class.
 * This client-side component should be instantiated from a Prado component.
 * Usage: <t>new Prado.AutoCompleter('textboxID', 'updateDivID', {callbackID : '...'});
 */
Prado.AutoCompleter.prototype = Object.extend(new Autocompleter.Base(),
{
	/**
	 * This component is initialized by
	 * <code>new Prado.AutoCompleter(...)</code>
	 * @param string the ID of the textbox element to observe
	 * @param string the ID of the div to display the auto-complete options
	 * @param array a hash of options, e.g. auto-completion token separator.
	 */
	initialize : function(element, update, options)
	{
		this.baseInitialize(element, update, options);
	},

	/**
	 * The callback function, i.e., function called on successful AJAX return.
	 * Calls update choices in the Autocompleter.
	 * @param string new auto-complete options for display
	 */
	onUpdateReturn : function(result)
	{
		if(isString(result) && result.length > 0)
			this.updateChoices(result);
	},

	/**
	 * Requesting new choices using Prado's client-side callback scheme.
	 */
	getUpdatedChoices : function()
	{
		Prado.Callback(this.element.id, this.getToken(), this.onUpdateReturn.bind(this));
	}
});

/**
 * Prado TActivePanel client javascript. Usage
 * <code>
 * Prado.ActivePanel.register("id", options);
 * Prado.ActivePanel.update("id", "hello");
 * </code>
 */
Prado.ActivePanel =
{
	callbacks : {},

	register : function(id, options)
	{
		Prado.ActivePanel.callbacks[id] = options;
	},

	update : function(id, param)
	{
		var request = new Prado.ActivePanel.Request(id,
						Prado.ActivePanel.callbacks[id]);
		request.callback(param);
	}
}

/**
 * Client-script for TActivePanel. Uses Callback to notify the server
 * for updates, if update option is set, the innerHTML of the update ID
 * is set to the returned output.
 */
Prado.ActivePanel.Request = Class.create();
Prado.ActivePanel.Request.prototype =
{
	initialize : function(element, options)
	{
		this.element = element;
		this.setOptions(options);
	},

	/**
	 * Set some options.
	 */
	setOptions : function(options)
	{
		this.options =
		{
			onSuccess : this.onSuccess.bind(this)
		}
		Object.extend(this.options, options || {});
	},

	/**
	 * Make the callback request
	 */
	callback : function(param)
	{
		this.options.params = [param];
		new Prado.AJAX.Callback(this.element, this.options);
	},

	/**
	 * Callback onSuccess handler, update the element innerHTML if necessary
	 */
	onSuccess : function(result, output)
	{
		if(this.options.update)
		{
			var element = $(this.options.update)
			if(element) element.innerHTML = output;
		}
	}
}

/**
 * Drop container to accept draggable component drops.
 */
Prado.DropContainer = Class.create();
Prado.DropContainer.prototype = Object.extend(new Prado.ActivePanel.Request(),
{
	initialize : function(element, options)
	{
		this.element = element;
		this.setOptions(options);
		Object.extend(this.options,
		{
			onDrop : this.onDrop.bind(this),
			evalScripts : true,
			onSuccess : options.onSuccess || this.update.bind(this)
		});
		Droppables.add(element, this.options);
	},

	onDrop : function(draggable, droppable)
	{
		this.callback(draggable.id)
	},

	update : function(result, output)
	{
		this.onSuccess(result, output);
		if (this.options.evalScripts)
			Prado.AJAX.EvalScript(output);
	}
});