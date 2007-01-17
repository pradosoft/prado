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

	select : function(element, method, value, total)
	{
		var el = $(element);
		if(!el) return;
		var selection = Prado.Element.Selection;
		if(typeof(selection[method]) == "function")
		{
			control = selection.isSelectable(el) ? [el] : selection.getListElements(element,total);
			selection[method](control, value);
		}
	},

	click : function(element)
	{
		var el = $(element);
		if(el)
			Event.fireEvent(el,'click');
	},

	setAttribute : function(element, attribute, value)
	{
		var el = $(element);
		if(!el) return;
		if((attribute == "disabled" || attribute == "multiple") && value==false)
			el.removeAttribute(attribute);
		else if(attribute.match(/^on/i)) //event methods
		{
			try
			{
				eval("(func = function(event){"+value+"})");
				el[attribute] = func;
			}
			catch(e)
			{
				throw "Error in evaluating '"+value+"' for attribute "+attribute+" for element "+element.id;
			}
		}
		else
			el.setAttribute(attribute, value);
	},

	setOptions : function(element, options)
	{
		var el = $(element);
		if(!el) return;
		if(el && el.tagName.toLowerCase() == "select")
		{
			el.options.length = options.length;
			for(var i = 0; i<options.length; i++)
				el.options[i] = new Option(options[i][0],options[i][1]);
		}
	},

	/**
	 * A delayed focus on a particular element
	 * @param {element} element to apply focus()
	 */
	focus : function(element)
	{
		var obj = $(element);
		if(typeof(obj) != "undefined" && typeof(obj.focus) != "undefined")
			setTimeout(function(){ obj.focus(); }, 100);
		return false;
	},

	replace : function(element, method, content, boundary)
	{
		if(boundary)
		{
			result = Prado.Element.extractContent(this.transport.responseText, boundary);
			if(result != null)
				content = result;
		}
		if(typeof(element) == "string")
		{
			if($(element))
				method.toFunction().apply(this,[element,""+content]);
		}
		else
		{
			method.toFunction().apply(this,[""+content]);
		}
	},

	extractContent : function(text, boundary)
	{
		var f = RegExp('(<!--'+boundary+'-->)([\\s\\S\\w\\W]*)(<!--//'+boundary+'-->)',"m");
		var result = text.match(f);
		if(result && result.length >= 2)
			return result[2];
		else
			return null;
	},

	evaluateScript : function(content)
	{
		content.evalScripts();
	}
}

Prado.Element.Selection =
{
	isSelectable : function(el)
	{
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

	inputValue : function(el, value)
	{
		switch(el.type.toLowerCase())
		{
			case 'checkbox':
			case 'radio':
			return el.checked = value;
		}
	},

	selectValue : function(elements, value)
	{
		elements.each(function(el)
		{
			$A(el.options).each(function(option)
			{
				if(typeof(value) == "boolean")
					options.selected = value;
				else if(option.value == value)
					option.selected = true;
			});
		})
	},

	selectValues : function(elements, values)
	{
		selection = this;
		values.each(function(value)
		{
			selection.selectValue(elements,value);
		})
	},

	selectIndex : function(elements, index)
	{
		elements.each(function(el)
		{
			if(el.type.toLowerCase() == 'select-one')
				el.selectedIndex = index;
			else
			{
				for(var i = 0; i<el.length; i++)
				{
					if(i == index)
						el.options[i].selected = true;
				}
			}
		})
	},

	selectAll : function(elements)
	{
		elements.each(function(el)
		{
			if(el.type.toLowerCase() != 'select-one')
			{
				$A(el.options).each(function(option)
				{
					option.selected = true;
				})
			}
		})
	},

	selectInvert : function(elements)
	{
		elements.each(function(el)
		{
			if(el.type.toLowerCase() != 'select-one')
			{
				$A(el.options).each(function(option)
				{
					option.selected = !options.selected;
				})
			}
		})
	},

	selectIndices : function(elements, indices)
	{
		selection = this;
		indices.each(function(index)
		{
			selection.selectIndex(elements,index);
		})
	},

	selectClear : function(elements)
	{
		elements.each(function(el)
		{
			el.selectedIndex = -1;
		})
	},

	getListElements : function(element, total)
	{
		elements = new Array();
		for(i = 0; i < total; i++)
		{
			el = $(element+"_c"+i);
			if(el)
				elements.push(el);
		}
		return elements;
	},

	checkValue : function(elements, value)
	{
		elements.each(function(el)
		{
			if(typeof(value) == "boolean")
				el.checked = value;
			else if(el.value == value)
				el.checked = true;
		});
	},

	checkValues : function(elements, values)
	{
		selection = this;
		values.each(function(value)
		{
			selection.checkValue(elements, value);
		})
	},

	checkIndex : function(elements, index)
	{
		for(var i = 0; i<elements.length; i++)
		{
			if(i == index)
				elements[i].checked = true;
		}
	},

	checkIndices : function(elements, indices)
	{
		selection = this;
		indices.each(function(index)
		{
			selection.checkIndex(elements, index);
		})
	},

	checkClear : function(elements)
	{
		elements.each(function(el)
		{
			el.checked = false;
		});
	},

	checkAll : function(elements)
	{
		elements.each(function(el)
		{
			el.checked = true;
		})
	},

	checkInvert : function(elements)
	{
		elements.each(function(el)
		{
			el.checked != el.checked;
		})
	}
};


Prado.Element.Insert =
{
	append: function(element, content)
	{
		new Insertion.Bottom(element, content);
	},

	prepend: function(element, content)
	{
		new Insertion.Top(element, content);
	},

	after: function(element, content)
	{
		new Insertion.After(element, content);
	},

	before: function(element, content)
	{
		new Insertion.Before(element, content);
	}
}