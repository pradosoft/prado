
Prado.PostBack = Class.create();

Prado.PostBack.Options = Class.create();

Prado.PostBack.Options.prototype =
{
	initialize : function(performValidation, validationGroup, actionUrl, trackFocus, clientSubmit)
	{
	    this.performValidation = performValidation;
	    this.validationGroup = validationGroup;
	    this.actionUrl = actionUrl;
	    this.trackFocus = trackFocus;
	    this.clientSubmit = clientSubmit;
    }
}

Prado.PostBack.perform = function(formID, eventTarget, eventParameter, options)
{
	var theForm = document.getElementById ? document.getElementById(formID) : document.forms[formID];
	var canSubmit = true;
	if ((typeof(options) != 'undefined') || options == null)
	{
	    if (options.performValidation)
		{
			canSubmit = Prado.Validation.validate(options.validationGroup);
		}
		if (canSubmit)
		{
			if ((typeof(options.actionUrl) != 'undefined') && (options.actionUrl != null) && (options.actionUrl.length > 0))
			{
				theForm.action = options.actionUrl;
			}
			if (options.trackFocus)
			{
				var lastFocus = theForm.elements['PRADO_LASTFOCUS'];
				if ((typeof(lastFocus) != 'undefined') && (lastFocus != null))
				{
					var active = document.activeElement;
					if (typeof(active) == 'undefined')
					{
						lastFocus.value = eventTarget;
					}
					else
					{
						if ((active != null) && (typeof(active.id) != 'undefined'))
						{
							if (active.id.length > 0)
							{
								lastFocus.value = active.id;
							}
							else if (typeof(active.name) != 'undefined')
							{
								lastFocus.value = active.name;
							}
						}
					}
				}
			}
			if (!options.clientSubmit)
			{
				canSubmit = false;
			}
		}
	}
	if (canSubmit && (!theForm.onsubmit || theForm.onsubmit()))
	{
		theForm.PRADO_POSTBACK_TARGET.value = eventTarget;
		theForm.PRADO_POSTBACK_PARAMETER.value = eventParameter;
		theForm.submit();
	}
}