jgdoc = {}; 
jgdoc.TreeItem = function(nodeName, item)
{
    this._nodeName = nodeName;
    this._data = item;
    this._children = [];
    
}

jgdoc.Searcher = 
{
    setData : function(data) {
      this._data = data;
      this.processItems();
      this.render();
    },

    sorter: function(o1, o2) {
      var l1 = o1.localName;
      var l2 = o2.localName;
      return l1 < l2? -1 : (l1 > l2 ? 1 : 0);
    },
    
    processItems : function() {
      var root;

      // Pass 1: Build index by fullName, and locate the root element
      this._byName = {};
      for (var i = 0; i < this._data.length; i++) {
        var d = this._data[i];
        if (d.fullName == "GLOBAL") {
          root = this._root = d;
        }
        this._byName[d.fullName] = d;
      }
    
      // Pass 2: Populate _children arrays
      for (var i = 0; i < this._data.length; i++) {
        var item = this._data[i];
        if (item.elementType == "logical_container" && item != this._root) {
          var parent = this._byName[item.parent];
          parent._children = parent._children || [];
          parent._children.push(item);
        }
      }
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
        var found = false;
        for (var i = 0; i < a.length; i++)
        {
            if (a[i] == className)
            {
                a.splice(i, 1);
                found = true;
                break;
            }
        }
        element.className = a.join(' ');
        return found;
    },    
    
    clicked : function(event)
    {
        event = window.event? window.event : event;
        var target = event.target || event.srcElement;
        var span = target.parentNode;
        var li = span.parentNode;
        var wasOpen = jgdoc.Searcher.removeClass(li, 'open');
        if (wasOpen)
        {
            jgdoc.Searcher.addClass(li, 'closed');
        } 
        else
        {
            jgdoc.Searcher.removeClass(li, 'closed');
            jgdoc.Searcher.addClass(li, 'open');
        }
        span.title = "Click to " + (wasOpen? "expand" : "collapse");
                
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
    
    
    render : function()
    {
        var d = document.getElementById('content');
        d.innerHTML = '';
        var athis = this;
        function renderNode(item)
        {
            var node = document.createElement('li');
            node.className = item.type;
            node.innerHTML = "<span class='node'><span class='markerSpace'>&nbsp;</span></span><a href='" + item.ref + "' target='classFrame' title='" + item.summary + "'>" + item.localName + "</a>";
            var span = node.firstChild;
            var img = span.firstChild;
            athis.addListener(img, 'mousedown', athis.clicked);
            if (item._children)
            {
                item._children.sort(jgdoc.Searcher.sorter);
                node.className += (item == athis._root)? ' open' : ' closed';
                span.title = "Click to " + (item != athis._root? 'expand' : 'collapse');
                var subnode = document.createElement("ul");
                subnode.className = 'contents';
                for (var i = 0; i < item._children.length; i++)
                {
                    var child = renderNode(item._children[i]);
                    subnode.appendChild(child);
                }
                node.appendChild(subnode);
            }
            else
            {
                node.className += ' leaf';
            }
            item._node = node;
            return node;
        }
        var root = renderNode(this._root);
        d.appendChild(root);
    },
    
    cancelEvent : function(event)
    {
        if (event.preventDefault)
        {
            event.preventDefault();
            event.stopPropagation();
        }
        else
        {
            event.preventDefault();
            event.stopPropagation();
        }
    },
    
    switchAll : function(doOpen)
    {
        var ac = doOpen? 'open' : 'closed';
        var rc = doOpen? 'closed' : 'open';
        
        var athis = this;
        
        function doSwitchNode(anode)
        {
            if (anode._children)
            {
                if (doOpen || anode != athis._root)
                {
                    athis.removeClass(anode._node, rc);
                    athis.addClass(anode._node, ac);
                }
                for (var i = 0; i < anode._children.length; i++)
                {
                    doSwitchNode(anode._children[i]);
                }
            }
        }
        doSwitchNode(this._root);
    },
    
    onOpenAll : function()
    {
        jgdoc.Searcher.switchAll(true);
    },
    
    onCloseAll : function()
    {
        jgdoc.Searcher.switchAll(false);
    }

};
