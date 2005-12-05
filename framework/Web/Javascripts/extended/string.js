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
	}

});
