Prado.Focus = Class.create();

Prado.Focus.setFocus = function(id)
{
	var target = document.getElementById ? document.getElementById(id) : document.all[id];
	if(target && !Prado.Focus.canFocusOn(target))
	{
		target = Prado.Focus.findTarget(target);
	}
	if(target)
	{
        try
		{
            target.focus();
			target.scrollIntoView(false);
            if (window.__smartNav)
			{
				window.__smartNav.ae = target.id;
			}
		}
        catch (e)
		{
		}
	}
}

Prado.Focus.canFocusOn = function(element)
{
	if(!element || !(element.tagName))
		return false;
	var tagName = element.tagName.toLowerCase();
	return !element.disabled && (!element.type || element.type.toLowerCase() != "hidden") && Prado.Focus.isFocusableTag(tagName) && Prado.Focus.isVisible(element);
}

Prado.Focus.isFocusableTag = function(tagName)
{
	return (tagName == "input" || tagName == "textarea" || tagName == "select" || tagName == "button" || tagName == "a");
}


Prado.Focus.findTarget = function(element)
{
	if(!element || !(element.tagName))
	{
		return null;
	}
	var tagName = element.tagName.toLowerCase();
	if (tagName == "undefined")
	{
		return null;
	}
	var children = element.childNodes;
	if (children)
	{
		for(var i=0;i<children.length;i++)
		{
			try
			{
				if(Prado.Focus.canFocusOn(children[i]))
				{
					return children[i];
				}
				else
				{
					var target = Prado.Focus.findTarget(children[i]);
					if(target)
					{
						return target;
					}
				}
			}
			catch (e)
			{
			}
		}
	}
	return null;
}

Prado.Focus.isVisible = function(element)
{
	var current = element;
	while((typeof(current) != "undefined") && (current != null))
	{
		if(current.disabled || (typeof(current.style) != "undefined" && ((typeof(current.style.display) != "undefined" && current.style.display == "none") || (typeof(current.style.visibility) != "undefined" && current.style.visibility == "hidden") )))
		{
			return false;
		}
		if(typeof(current.parentNode) != "undefined" &&	current.parentNode != null && current.parentNode != current && current.parentNode.tagName.toLowerCase() != "body")
		{
			current = current.parentNode;
		}
		else
		{
			return true;
		}
	}
    return true;
}
