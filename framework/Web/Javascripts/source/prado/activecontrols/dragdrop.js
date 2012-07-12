/**
 * DropContainer control
 */
 
Prado.WebUI.DropContainer = Class.create(Prado.WebUI.CallbackControl,
{
	onInit: function(options)
	{
		this.options = options;
		Object.extend (this.options, 
		{
			onDrop: this.onDrop.bind(this)
		});
		
		Droppables.add (options.ID, this.options);
	},
	
	onDrop: function(dragElement, dropElement, event)
	{
		var elementId=dragElement.id.replace(/clone_/,"");
		var req = new Prado.CallbackRequest(this.options.EventTarget, this.options);
		var curleft = curtop = 0;
		var obj = dropElement;
		if (obj.offsetParent) {
			curleft = obj.offsetLeft
			curtop = obj.offsetTop
			while (obj = obj.offsetParent) {
				curleft += obj.offsetLeft
				curtop += obj.offsetTop
			}
		}
		var scrOfX = 0, scrOfY = 0;
		if( typeof( window.pageYOffset ) == 'number' ) {
			//Netscape compliant
			scrOfY = window.pageYOffset;
			scrOfX = window.pageXOffset;
		} else if( document.body && ( document.body.scrollLeft || document.body.scrollTop ) ) {
			//DOM compliant
			scrOfY = document.body.scrollTop;
			scrOfX = document.body.scrollLeft;
		} else if( document.documentElement && ( document.documentElement.scrollLeft || document.documentElement.scrollTop ) ) {
			//IE6 standards compliant mode
			scrOfY = document.documentElement.scrollTop;
			scrOfX = document.documentElement.scrollLeft;
		}
		req.setCallbackParameter({
			DragElementID : elementId,
			ScreenX : event.screenX,
			ScreenY : event.screenY,
			OffsetX : event.offsetX || event.clientX - curleft + scrOfX,
			OffsetY : event.offsetY || event.clientY - curtop + scrOfY,
			ClientX : event.clientX,
			ClientY : event.clientY,
			AltKey : event.altKey,
			CtrlKey : event.ctrlKey,
			ShiftKey : event.shiftKey
		});
		req.dispatch();

	}
});
