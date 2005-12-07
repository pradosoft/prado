Prado = Class.create();

Prado.version = '3.0a';

Prado.Button = Class.create();

Prado.Button.buttonFired = false;
Prado.Button.fireButton = function(event, target)
{
	if (!Prado.Button.buttonFired && event.keyCode == 13 && !(event.srcElement && (event.srcElement.tagName.toLowerCase() == "textarea")))
	{
		var defaultButton = document.getElementById ? document.getElementById(target) : document.all[target];
		if (defaultButton && typeof(defaultButton.click) != "undefined")
		{
			Prado.Button.buttonFired = true;
			defaultButton.click();
			event.cancelBubble = true;
			if (event.stopPropagation)
				event.stopPropagation();
			return false;
		}
	}
	return true;
}

Prado.TextBox = Class.create();

Prado.TextBox.handleReturnKey = function(event)
{
	if (event.keyCode == 13)
	{
		var target;
		if(typeof(event.target)!="undefined")
			target=event.target;
		else if(typeof(event.srcElement)!="undefined")
			target=event.srcElement;
		if((typeof(target)!="undefined") && (target!=null))
		{
			if(typeof(target.onchange)!="undefined")
			{
				target.onchange();
				event.cancelBubble=true;
				if(event.stopPropagation)
					event.stopPropagation();
				return false;
			}
		}
	}
	return true;
}