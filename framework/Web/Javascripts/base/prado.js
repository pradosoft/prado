Prado = Class.create();

Prado.version = '3.0a';

Prado.DefaultButton = Class.create();

Prado.DefaultButton.buttonFired = false;
Prado.DefaultButton.fire = function(event, target)
{
	if (!Prado.DefaultButton.buttonFired && event.keyCode == 13 && !(event.srcElement && (event.srcElement.tagName.toLowerCase() == "textarea")))
	{
		var defaultButton = document.getElementById ? document.getElementById(target) : document.all[target];
		if (defaultButton && typeof(defaultButton.click) != "undefined")
		{
			Prado.DefaultButton.buttonFired = true;
			defaultButton.click();
			event.cancelBubble = true;
			if (event.stopPropagation)
				event.stopPropagation();
			return false;
		}
	}
	return true;
}
