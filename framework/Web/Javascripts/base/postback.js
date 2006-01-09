Prado.doPostBack = function(formID, eventTarget, eventParameter, performValidation, validationGroup, actionUrl, trackFocus, clientSubmit)
{
	if (typeof(performValidation) == 'undefined')
	{
		var performValidation = false;
		var validationGroup = '';
		var actionUrl = null;
		var trackFocus = false;
		var clientSubmit = true;
	}
	var theForm = document.getElementById ? document.getElementById(formID) : document.forms[formID];
	var canSubmit = true;
    if (performValidation)
	{
		//canSubmit = Prado.Validation.validate(validationGroup);
	/*	Prado.Validation.ActiveTarget = theForm;
		Prado.Validation.CurrentTargetGroup = null;
		Prado.Validation.IsGroupValidation = false;
		canSubmit = Prado.Validation.IsValid(theForm);
		Logger.debug(canSubmit);*/
		canSubmit = Prado.Validation.IsValid(theForm);
	}
	if (canSubmit)
	{
		if (actionUrl != null && (actionUrl.length > 0))
		{
			theForm.action = actionUrl;
		}
		if (trackFocus)
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
		if (!clientSubmit)
		{
			canSubmit = false;
		}
	}
	if (canSubmit && (!theForm.onsubmit || theForm.onsubmit()))
	{
		theForm.PRADO_POSTBACK_TARGET.value = eventTarget;
		theForm.PRADO_POSTBACK_PARAMETER.value = eventParameter;
		theForm.submit();
	}
}