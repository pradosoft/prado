
/** 
 * true if o is an Element Node or document or window. The last two because it's used for onload events
    if you specify strict as true, return false for document or window
 */
function isElement(o, strict) {
    return o && isObject(o) && ((!strict && (o==window || o==document)) || o.nodeType == 1)
}

/** 
 * get element
 @ @param element or element id string
 @ returns element
 */
function $(n,d) {
    if(isElement(n)) return n;
	if(isString(n)==false) return null;
	var p,i,x;  
	if(!d) d=document; if((p=n.indexOf("?"))>0&&parent.frames.length) {
		d=parent.frames[n.substring(p+1)].document; n=n.substring(0,p);}
		if(!(x=d[n])&&d.all) x=d.all[n]; for (i=0;!x&&i<d.forms.length;i++) x=d.forms[i][n];
		for(i=0;!x&&d.layers&&i<d.layers.length;i++) x=DOM.find(n,d.layers[i].document);
		if(!x && d.getElementById) x=d.getElementById(n); return x;
}

/**
 * Similar to bindAsEventLister, but takes additional arguments.
 */
Function.prototype.bindEvent = function() {
  var __method = this, args = $A(arguments), object = args.shift();
  return function(event) {
    return __method.apply(object, [event || window.event].concat(args));
  }
}

