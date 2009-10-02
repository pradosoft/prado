/**
 * DropContainer control
 */
 
Prado.WebUI.DropContainer = Class.extend(Prado.WebUI.CallbackControl);

Object.extend(Prado.WebUI.DropContainer.prototype,
{
	initialize: function(options)
	{
		this.options = options;
		Object.extend (this.options, 
		{
			onDrop: this.onDrop.bind(this)
		});
		
		Droppables.add (options.ID, this.options);
		Prado.Registry.set(options.ID, this);
	},
	
	onDrop: function(dragElement, dropElement, event)
	{
		var elementId=dragElement.id.replace(/clone_/,"");
		var req = new Prado.CallbackRequest(this.options.EventTarget, this.options);
		req.setCallbackParameter({
			DragElementID : elementId,
			ScreenX : event.screenX,
			ScreenY : event.screenY,
			OffsetX : event.offsetX,
			OffsetY : event.offsetY,
			ClientX : event.clientX,
			ClientY : event.clientY,
			AltKey : event.altKey,
			CtrlKey : event.ctrlKey,
			ShiftKey : event.shiftKey
		});
		req.dispatch();

	}
});
