//  DragDropExtra Scriptaculous Enhancement, version 0.5
//  (c) 2007-2008 Christopher Williams, Iterative Designs
//
// v0.5 release
//      - Fixed bug where 2nd drag on an element in IE would result in funny placement of the
//        element. [shammond42]
// v0.4 release
//		- Fixed issue with dragging and dropping in IE7 due to an exception being thrown and not properly reseting in FinishDrag.
// v0.3 release
//	  - Fixed bug found by Phillip Sauerbeck psauerbeck@gmail. Tests added based on Phillip's efforts.
// v0.2 release
//		- Minor bug fix for the releasing of objects after they have been dropped, prevents memory leak.
// v0.1 release
//		- initial release for the super ghosting capability
//		- Drags from one scrolling list to the other (overflow:auto)
//		- Retains the original object so that it can remain present despite being dragged
// 
// dragdropextra.js is freely distributable under the terms of an MIT-style license.
// For details, see the Iterative Designs web site: http://www.iterativedesigns.com/
// Parts of this code have been taken from the original dragdrop.js library which is 
// copyrighted by (c) 2005-2007 Thomas Fuchs (http://script.aculo.us, 
// http://mir.aculo.us) and (c) 2005-2007 Sammi Williams 
// (http://www.oriontransfer.co.nz, sammi@oriontransfer.co.nz) and available under 
// a MIT-style license.

Draggable.prototype.startDrag = function(event) {
  this.dragging = true;
  if(!this.delta)
    this.delta = this.currentDelta();
  
  if(this.options.zindex) {
    this.originalZ = parseInt(Element.getStyle(this.element,'z-index') || 0);
    this.element.style.zIndex = this.options.zindex;
  }
  
  if(this.options.ghosting) {
    this._clone = this.element.cloneNode(true);
    this.element._originallyAbsolute = (this.element.getStyle('position') == 'absolute');
    if (!this.element._originallyAbsolute)
      Position.absolutize(this.element);
    this.element.parentNode.insertBefore(this._clone, this.element);
  }
  
	if(this.options.superghosting) {
		Position.prepare();
		var pointer = [Event.pointerX(event), Event.pointerY(event)];
		body = document.getElementsByTagName("body")[0];
		me = this.element;
		this._clone = me.cloneNode(true);
		if (Prototype.Browser.IE) {
 			// Clear event handing from the clone
			// Solves the second drag issue in IE
			this._clone.clearAttributes();
			this._clone.mergeAttributes(me.cloneNode(false));
		}
		me.parentNode.insertBefore(this._clone, me);
		me.id = "clone_"+me.id;
		me.hide();

		Position.absolutize(me);
		me.parentNode.removeChild(me);
		body.appendChild(me);
		//Retain height and width of object only if it has been nulled out.  -v0.3 Fix
		if (me.style.width == "0px" || me.style.height == "0px")	{
		me.style.width=Element.getWidth(this._clone)+"px";
		me.style.height=Element.getHeight(this._clone)+"px";
		}

		//overloading in order to reduce repeated code weight.
		this.originalScrollTop = (Element.getHeight(this._clone)/2);

		this.draw(pointer);
		me.show();
	}

  if(this.options.scroll) {
    if (this.options.scroll == window) {
      var where = this._getWindowScroll(this.options.scroll);
      this.originalScrollLeft = where.left;
      this.originalScrollTop = where.top;
    } else {
      this.originalScrollLeft = this.options.scroll.scrollLeft;
      this.originalScrollTop = this.options.scroll.scrollTop;
    }
  }
  
  Draggables.notify('onStart', this, event);
      
  if(this.options.starteffect) this.options.starteffect(this.element);
}




Draggable.prototype.draw = function(point) {
	  var pos = Position.cumulativeOffset(this.element);
	  if(this.options.ghosting) {
	    var r   = Position.realOffset(this.element);
	    pos[0] += r[0] - Position.deltaX; 
		pos[1] += r[1] - Position.deltaY;
	  }
  
	  var d = this.currentDelta();
	  pos[0] -= d[0]; 
	  pos[1] -= d[1];
  
	  if(this.options.scroll && (this.options.scroll != window && this._isScrollChild)) {
	    pos[0] -= this.options.scroll.scrollLeft-this.originalScrollLeft;
	    pos[1] -= this.options.scroll.scrollTop-this.originalScrollTop;
	  }
  
	  var p = [0,1].map(function(i){ 
	    return (point[i]-pos[i]-this.offset[i]) 
	  }.bind(this));

        if(this.options.snap) {
          if(Object.isFunction(this.options.snap)) {
            p = this.options.snap(p[0],p[1],this);
          } else {
          if(Object.isArray(this.options.snap)) {
            p = p.map( function(v, i) {
              return (v/this.options.snap[i]).round()*this.options.snap[i] }.bind(this))
          } else {
            p = p.map( function(v) {
              return (v/this.options.snap).round()*this.options.snap }.bind(this))
          }
        }}

  	if (this.options.superghosting)	{	
		p[1] = point[1] - this.originalScrollTop;
	}



    var style = this.element.style;
    if((!this.options.constraint) || (this.options.constraint=='horizontal'))
      style.left = p[0] + "px";
    if((!this.options.constraint) || (this.options.constraint=='vertical'))
      style.top  = p[1] + "px";
    
    if(style.visibility=="hidden") style.visibility = ""; // fix gecko rendering
}

Draggable.prototype.initDrag = function(event) {
  if(!Object.isUndefined(Draggable._dragging[this.element]) &&
    Draggable._dragging[this.element]) return;
  if(Event.isLeftClick(event)) {    
    // abort on form elements, fixes a Firefox issue
    var src = Event.element(event);
    if((tag_name = src.tagName.toUpperCase()) && (
      tag_name=='INPUT' ||
      tag_name=='SELECT' ||
      tag_name=='OPTION' ||
      tag_name=='BUTTON' ||
      tag_name=='TEXTAREA')) return;
      
    var pointer = [Event.pointerX(event), Event.pointerY(event)];
    var pos     = Position.cumulativeOffset(this.element);
    this.offset = [0,1].map( function(i) { return (pointer[i] - pos[i]) });
    
    Draggables.activate(this);
    Event.stop(event);
  }
}

Droppables.isAffected = function(point, element, drop) {
	Position.prepare();
	positioned_within = Position.withinIncludingScrolloffsets(drop.element, point[0], point[1])
	return (
      (drop.element!=element) &&
      ((!drop._containers) ||
        this.isContained(element, drop)) &&
      ((!drop.accept) ||
        (Element.classNames(element).detect( 
          function(v) { return drop.accept.include(v) } ) )) && positioned_within );


}

Draggable.prototype.finishDrag = function(event, success) {
  this.dragging = false;
  
  if(this.options.quiet){
    Position.prepare();
    var pointer = [Event.pointerX(event), Event.pointerY(event)];
    Droppables.show(pointer, this.element);
  }

  if(this.options.ghosting) {
    if (!this.element._originallyAbsolute)
      Position.relativize(this.element);
    delete this.element._originallyAbsolute;
    Element.remove(this._clone);
    this._clone = null;
  }

  var dropped = false; 
  if(success) { 
    dropped = Droppables.fire(event, this.element); 
    if (!dropped) dropped = false; 
  }
  if(dropped && this.options.onDropped) this.options.onDropped(this.element);
  Draggables.notify('onEnd', this, event);

  var revert = this.options.revert;
  if(revert && Object.isFunction(revert)) revert = revert(this.element);
  
  var d = this.currentDelta();
  if(revert && this.options.reverteffect) {
    if (dropped == 0 || revert != 'failure')
      this.options.reverteffect(this.element,
        d[1]-this.delta[1], d[0]-this.delta[0]);
  } else {
    this.delta = d;
  }

  if(this.options.zindex)
    this.element.style.zIndex = this.originalZ;

  if(this.options.endeffect) 
    this.options.endeffect(this.element);
    

	if(this.options.superghosting) {
		body = document.getElementsByTagName("body")[0];
	  Element.remove(this.element);
		new Draggable(this._clone, this.options);
	}


  Draggables.deactivate(this);
  Droppables.reset();
}
