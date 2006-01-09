Object.extend(String.prototype, {
	pad : function(side, len, chr) {
		if (!chr) chr = ' ';
		var s = this;
		var left = side.toLowerCase()=='left';
		while (s.length<len) s = left? chr + s : s + chr;
		return s;
	},

	padLeft : function(len, chr) {
		return this.pad('left',len,chr);
	},

	padRight : function(len, chr) {
		return this.pad('right',len,chr);
	},

	zerofill : function(len) { 
		var s = this;
		var ix = /^[+-]/.test(s) ? 1 : 0;
		while (s.length<len) s = s.insert(ix, '0');
		return s;
	},

	trim : function() { 
		return this.replace(/^\s+|\s+$/g,'');
	},

	trimLeft : function() { 
		return this.replace(/^\s+/,''); 
	},

	trimRight : function() { 
		return this.replace(/\s+$/,'');
	},

	/**
	 * Convert period separated function names into a function reference.
	 * e.g. "Prado.AJAX.Callback.Action.setValue".toFunction() will return
	 * the actual function Prado.AJAX.Callback.Action.setValue()
	 * @return Function the corresponding function represented by the string.
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
		if(isFunction(command))
			return command;
		else
		{
			if(typeof Logger != "undefined")
				Logger.error("Missing function", this);
			return Prototype.emptyFunction;
		}
	}

});
