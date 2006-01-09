Object.extend(Element, {
	condClassName : function (element, className, cond)
	{
		(cond?Element.addClassName:Element.removeClassName)(element,className);
	}
});

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

	select : function(element, method, value)
	{
		var el = $(element);
		var isList = element.indexOf('[]') > -1;
		if(!el && !isList) return;
		method = isList ? 'check'+method : el.tagName.toLowerCase()+method;
		var selection = Prado.Element.Selection;
		if(isFunction(selection[method])) 
			selection[method](isList ? element : el,value);
	},

	click : function(element)
	{
		var el = $(element);
		//Logger.info(el);
		if(!el) return;
		if(document.createEvent)
        {
            var evt = document.createEvent('HTMLEvents');
            evt.initEvent('click', true, true);
            el.dispatchEvent(evt);
			//Logger.warn("dispatching click for "+el.id);
        }
        else if(el.fireEvent)
		{
            el.fireEvent('onclick');
			if(isFunction(el.onclick))
				el.onclick();
		}
	},
	
	setAttribute : function(element, attribute, value)
	{
		var el = $(element);
		if(attribute == "disabled" && value==false)
			el.removeAttribute(attribute);
		else
			el.setAttribute(attribute, value);
	},

	setOptions : function(element, options)
	{
		var el = $(element);
		if(el && el.tagName.toLowerCase() == "select")
		{
			while(el.length > 0)
				el.remove(0);
			for(var i = 0; i<options.length; i++)
				el.options[el.options.length] = new Option(options[i][0],options[i][1]);
		}
	}
}

Prado.Element.Selection = 
{
	inputValue : function(el, value)
	{
		switch(el.type.toLowerCase()) 
		{
			case 'checkbox':  
			case 'radio':
			return el.checked = value;
		}
	},

	selectValue : function(el, value)
	{
		$A(el.options).each(function(option)
		{
			option.selected = option.value == value;
		});
	},

	selectIndex : function(el, index)
	{
		if(el.type == 'select-one')
			el.selectedIndex = index;
		else
		{
			for(var i = 0; i<el.length; i++)
			{
				if(i == index)
					el.options[i].selected = true;
			}
		}
	},

	selectClear : function(el)
	{
		el.selectedIndex = -1;
	},

	selectAll : function(el)
	{
		$A(el.options).each(function(option)
		{
			option.selected = true;
			Logger.warn(option.value);
		});
	},

	selectInvert : function(el)
	{
		$A(el.options).each(function(option)
		{
			option.selected = !option.selected;
		});
	},

	checkValue : function(name, value)
	{
		$A(document.getElementsByName(name)).each(function(el)
		{
			el.checked = el.value == value
		});
	},

	checkIndex : function(name, index)
	{
		var elements = $A(document.getElementsByName(name));
		for(var i = 0; i<elements.length; i++)
		{
			if(i == index)
				elements[i].checked = true;
		}
	},

	checkClear : function(name)
	{
		$A(document.getElementsByName(name)).each(function(el)
		{
			el.checked = false;
		});
	},

	checkAll : function(name)
	{
		$A(document.getElementsByName(name)).each(function(el)
		{
			el.checked = true;
		});
	},
	checkInvert : function(name)
	{
		$A(document.getElementsByName(name)).each(function(el)
		{
			el.checked = !el.checked;
		});
	}
};


/**
 * Alias some of the prototype functions.
 * Insert a html fragment relative to an element.
 */
Object.extend(Prado.Element,
{
	/**
	 * 
	 */
	Insert : 
	{
		/**
		 * Insert directly after the element.
		 */
		After : function(element, innerHTML)
		{
			new Insertion.After(element, innerHTML);
		},

		/**
		 * Insert directly after the element
		 */
		Before : function(element, innerHTML)
		{
			new Insertion.Before(element. innerHTML);
		},

		/**
		 * Insert below the element container.
		 */
		Below : function(element, innerHTML)
		{
			new Insertion.Bottom(element, innerHTML);
		},

		/**
		 * Insert above the element container.
		 */
		Above : function(element, innerHTML)
		{
			new Insertion.Top(element, innerHTML);
		}
	},
	CssClass :
	{
		/**
		 * Set the css class name of an element.
		 */
		set : function(element, cssClass)
		{
			element = new Element.ClassNames(element);
			element.set(cssClass);
		}
	}
});