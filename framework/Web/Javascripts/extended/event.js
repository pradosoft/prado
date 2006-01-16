Object.extend(Event, {
	OnLoad : function (fn) {
		// opera onload is in document, not window
		var w = document.addEventListener && !window.addEventListener ? document : window;
		Event.__observe(w,'load',fn);
	},
	observe: function(elements, name, observer, useCapture) {
    if(!isList(elements))
		return this.__observe(elements, name, observer, useCapture);
	for(var i=0; i<elements.length; i++)
		this.__observe(elements[i], name, observer, useCapture);
  },
  __observe: function(element, name, observer, useCapture) {
    var element = $(element);
    useCapture = useCapture || false;
    
    if (name == 'keypress' &&
        ((navigator.appVersion.indexOf('AppleWebKit') > 0) 
        || element.attachEvent))
      name = 'keydown';
    
    this._observeAndCache(element, name, observer, useCapture);
  },
   keyCode : function(e)
	{
	   return e.keyCode != null ? e.keyCode : e.charCode
	},

	fireEvent : function(el,type)
	{
		if(document.createEvent)
        {
            var evt = document.createEvent('HTMLEvents');
            evt.initEvent(type, true, true);
            el.dispatchEvent(evt);
        }
        else if(el.fireEvent)
        {
            el.fireEvent('on'+type);
            el[type]();
        }
        else
            el[type]();
	}
});