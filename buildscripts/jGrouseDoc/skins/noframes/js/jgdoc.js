/**
 * Searcher for JGrouseDoc
 * $Id: jgdoc.js 324 2008-01-06 16:44:39Z denis.riabtchik $
 */

if (typeof jgdoc == 'undefined')
{
    jgdoc = {}
    
    jgdoc._dataHandlers = [];
    
    jgdoc.setData = function(data)
    {
        for (var i = 0; i < jgdoc._dataHandlers.length; i++)
        {
        	jgdoc._dataHandlers[i](data);
        }
    }
}

jgdoc.Searcher = 
{
    initialize : function()
    {
        this._searchBox = document.getElementById("jgsSearchString");
        this._searchResults = document.getElementById("jgsSearchResults");
        this._info = document.getElementById("jgsInfo");
        this._currentValue = "";
        this._currentItems = [];
        this._currentItem = -1;
        this._data = null;
        this._searchPanel = document.getElementById("jgsSearchPanel");
        this.addListener(this._searchBox, 'focus', this.dispatcher);
        this.addListener(this._searchBox, 'blur', this.dispatcher);
        this.addListener(window, 'resize', this.dispatcher);
        var width = this._searchBox.offsetWidth - 4;
        this._searchPanel.style.width = width + 'px';
        
        /*var node = this._searchBox;
        var current = 0;
        var currentTop = 0;
        while (node)
        {
            current += node.offsetLeft;
            currentTop += node.offsetTop;
            node = node.offsetParent;
        }
        
        this._searchPanel.style.left = current + 'px';*/
        this.positionBox();
        return this;
    },
    
    positionBox : function()
    {
        var node = this._searchBox;
        var current = 0;
        var currentTop = 0;
        while (node)
        {
            current += node.offsetLeft;
            currentTop += node.offsetTop;
            node = node.offsetParent;
        }
        
        this._searchPanel.style.left = current + 'px';
        this._searchPanel.style.top = (currentTop + this._searchBox.offsetHeight) + "px";
    	
    },
    
    _getEvent : function(event)
    {
        return window.event? window.event : event;
    },
    
    _getTarget : function(event)
    {
        return event.target || event.srcElement
    },
    
    addClass : function(element, className)
    {
    	var s = element.className;
    	var a = s.split(' ');
    	for (var i = 0; i < a.length; i++)
    	{
    		if (a[i] == className)
    		{
    			return;
    		}
    	}
    	a.push(className);
    	element.className = a.join(' ');
    },
    
    removeClass : function(element, className)
    {
        var s = element.className;
        var a = s.split(' ');
        for (var i = 0; i < a.length; i++)
        {
            if (a[i] == className)
            {
                a.splice(i, 1);
                break;
            }
        }
        element.className = a.join(' ');
    	
    },
    
    dispatcher : function(event)
    {   
        if (this != jgdoc.Searcher) 
        {
            arguments.callee.apply(jgdoc.Searcher, arguments)
            return;
        }        
        event = this._getEvent(event);
        var type = event.type;
        var handler = "on" + type;
        this[handler](event, this._getTarget(event));
    },
    
    onclick : function(event, target)
    {
    	// workaround for IE bug
        window.location.href = target.getAttribute("href", 1);
    },
    
    onmouseover : function(event, target)
    {
        this.selectItem(target.index);
    },
    
    onresize : function(event, target)
    {
    	if (this._searchPanel.style.display != 'none')
    	{
    		this.positionBox();
    	}
    },
    
    onmouseout : function(event, target)
    {
        this.unselectItem(target.index);
    },
    
    onfocus : function(event, target)
    {
    	this._searchPanel.style.display = '';
    	this.positionBox();
    },
    
    onblur : function(event, target)
    {
    	var athis = this;
    	window.setTimeout(function()
    	{
            athis._searchPanel.style.display = 'none';    		
    	}, 300);
        
    },
    
    selectItem : function(index)
    {
        if (index != this._currentItem)
        {
            this._currentItem = index;
            var item = this._currentItems[index];
            this.addClass(item, 'jgdSelectedItem');
            var text = item._data.summary.split('\n').join('<br/>');
            this._info.innerHTML = text;
        }
    },
    
    unselectItem : function(index)
    {
        this._currentItem = -1;
        var item = this._currentItems[index];
        this.removeClass(item, 'jgdSelectedItem');
        this._info.innerHTML = 'No selection';
    },
    
    openItem : function(name)
    {
    	
    },
   

    onTimer : function()
    {
        if (this != jgdoc.Searcher) 
        {
            arguments.callee.apply(jgdoc.Searcher, arguments)
            return;
        }
        var val = this._searchBox.value;
        if (val != this._currentValue)
        {
            this._currentValue = val;
            this.redraw();
        }
    },
    
    setData : function(data)
    {
        this._data = data;
        this.redraw();
        //this._searchBox.focus();
    },
    
    addListener : function(element, eventName, handler)
    {
        if (element.addEventListener)
        {
            element.addEventListener(eventName, handler, false);
        }
        else
        {
            element.attachEvent('on' + eventName, handler);
        }
    },
    
    removeListener : function(element, eventName, handler)
    {
        if (element.removeEventListener)
        {
            element.removeEventListener(eventName, handler, false);
        }
        else
        {
            element.detachEvent('on' + eventName, handler);
        }
    },    
    
    findMatches : function()
    {
        var result = [];
        if (this._currentValue)
        {
            var v = this._currentValue.toUpperCase();
            for (var i = 0; i < this._data.length; i++)
            {
                var item = this._data[i];
                if (item.localName.toUpperCase().indexOf(v) == 0)
                {
                    result.push(item);
                }
            } 
        }
        return result;
    },
    
    
    clearItem : function(item)
    {
       item._data = null;
       this.removeListener(item, 'click', this.dispatcher);
       this.removeListener(item, 'mouseover', this.dispatcher);
       this.removeListener(item, 'mouseout', this.dispatcher);
    },
    
    clear : function()
    {
        for (var i = 0; i < this._currentItems.length; i++)
        {
            this.clearItem(this._currentItems[i]);
        }
        this._currentItems = [];
        this._searchResults.innerHTML = "";
        this._currentItem = -1;
    },
    
    
    createItem : function(item, index)
    {
        var d1 = document.createElement("div");
        d1.innerHTML = "<a href='" + item.ref + "'>" + item.fullName + "</a>";
        d = d1.firstChild; 
        /*d.href = item.ref;
        item.ref = d.href;*/
        d.className = "searchItem";
        //d.title = item.summary;
        //d.innerHTML = item.fullName;
        d.index = index;
        d._data = item;
        this.addListener(d, 'click', this.dispatcher);
        this.addListener(d, 'mouseover', this.dispatcher);
        this.addListener(d, 'mouseout', this.dispatcher);
        //todo - set listeners
        return d;
    },
    
    redraw : function()
    {
        this.clear();
        var res = this.findMatches();
        if (res.length > 0)
        {
            for (var i = 0; i < res.length; i++)
            {
                var d = this.createItem(res[i], i);
                this._currentItems.push(d);
                this._searchResults.appendChild(d);
            }
        }
        else
        {
           var s = (this._currentValue)? "Not found" : "Start typing the name of the item";
           this._searchResults.innerHTML = s;
        }
    },
    
    start : function()
    {
	    var instance = jgdoc.Searcher.initialize();
	    instance.setData([]);
	    instance._timer = window.setInterval(instance.onTimer, 100);    
    }
}

jgdoc._dataHandlers.push(function(data)
{
    jgdoc.Searcher.setData(data);
});