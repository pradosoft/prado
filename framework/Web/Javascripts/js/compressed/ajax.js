
if(typeof Effect=='undefined')
throw("controls.js requires including script.aculo.us' effects.js library");var Autocompleter={}
Autocompleter.Base=function(){};Autocompleter.Base.prototype={baseInitialize:function(element,update,options){this.element=$(element);this.update=$(update);this.hasFocus=false;this.changed=false;this.active=false;this.index=0;this.entryCount=0;if(this.setOptions)
this.setOptions(options);else
this.options=options||{};this.options.paramName=this.options.paramName||this.element.name;this.options.tokens=this.options.tokens||[];this.options.frequency=this.options.frequency||0.4;this.options.minChars=this.options.minChars||1;this.options.onShow=this.options.onShow||function(element,update){if(!update.style.position||update.style.position=='absolute'){update.style.position='absolute';Position.clone(element,update,{setHeight:false,offsetTop:element.offsetHeight});}
Effect.Appear(update,{duration:0.15});};this.options.onHide=this.options.onHide||function(element,update){new Effect.Fade(update,{duration:0.15})};if(typeof(this.options.tokens)=='string')
this.options.tokens=new Array(this.options.tokens);this.observer=null;this.element.setAttribute('autocomplete','off');Element.hide(this.update);Event.observe(this.element,"blur",this.onBlur.bindAsEventListener(this));Event.observe(this.element,"keypress",this.onKeyPress.bindAsEventListener(this));},show:function(){if(Element.getStyle(this.update,'display')=='none')this.options.onShow(this.element,this.update);if(!this.iefix&&(Prototype.Browser.IE)&&(Element.getStyle(this.update,'position')=='absolute')){new Insertion.After(this.update,'<iframe id="'+this.update.id+'_iefix" '+'style="display:none;position:absolute;filter:progid:DXImageTransform.Microsoft.Alpha(opacity=0);" '+'src="javascript:false;" frameborder="0" scrolling="no"></iframe>');this.iefix=$(this.update.id+'_iefix');}
if(this.iefix)setTimeout(this.fixIEOverlapping.bind(this),50);},fixIEOverlapping:function(){Position.clone(this.update,this.iefix,{setTop:(!this.update.style.height)});this.iefix.style.zIndex=1;this.update.style.zIndex=2;Element.show(this.iefix);},hide:function(){this.stopIndicator();if(Element.getStyle(this.update,'display')!='none')this.options.onHide(this.element,this.update);if(this.iefix)Element.hide(this.iefix);},startIndicator:function(){if(this.options.indicator)Element.show(this.options.indicator);},stopIndicator:function(){if(this.options.indicator)Element.hide(this.options.indicator);},onKeyPress:function(event){if(this.active)
switch(event.keyCode){case Event.KEY_TAB:case Event.KEY_RETURN:this.selectEntry();Event.stop(event);case Event.KEY_ESC:this.hide();this.active=false;Event.stop(event);return;case Event.KEY_LEFT:case Event.KEY_RIGHT:return;case Event.KEY_UP:this.markPrevious();this.render();if(Prototype.Browser.WebKit)Event.stop(event);return;case Event.KEY_DOWN:this.markNext();this.render();if(Prototype.Browser.WebKit)Event.stop(event);return;}
else
if(event.keyCode==Event.KEY_TAB||event.keyCode==Event.KEY_RETURN||(Prototype.Browser.WebKit>0&&event.keyCode==0))return;this.changed=true;this.hasFocus=true;if(this.observer)clearTimeout(this.observer);this.observer=setTimeout(this.onObserverEvent.bind(this),this.options.frequency*1000);},activate:function(){this.changed=false;this.hasFocus=true;this.getUpdatedChoices();},onHover:function(event){var element=Event.findElement(event,'LI');if(this.index!=element.autocompleteIndex)
{this.index=element.autocompleteIndex;this.render();}
Event.stop(event);},onClick:function(event){var element=Event.findElement(event,'LI');this.index=element.autocompleteIndex;this.selectEntry();this.hide();},onBlur:function(event){setTimeout(this.hide.bind(this),250);this.hasFocus=false;this.active=false;},render:function(){if(this.entryCount>0){for(var i=0;i<this.entryCount;i++)
this.index==i?Element.addClassName(this.getEntry(i),"selected"):Element.removeClassName(this.getEntry(i),"selected");if(this.hasFocus){this.show();this.active=true;}}else{this.active=false;this.hide();}},markPrevious:function(){if(this.index>0)this.index--
else this.index=this.entryCount-1;this.getEntry(this.index).scrollIntoView(true);},markNext:function(){if(this.index<this.entryCount-1)this.index++
else this.index=0;this.getEntry(this.index).scrollIntoView(false);},getEntry:function(index){return this.update.firstChild.childNodes[index];},getCurrentEntry:function(){return this.getEntry(this.index);},selectEntry:function(){this.active=false;this.updateElement(this.getCurrentEntry());},updateElement:function(selectedElement){if(this.options.updateElement){this.options.updateElement(selectedElement);return;}
var value='';if(this.options.select){var nodes=document.getElementsByClassName(this.options.select,selectedElement)||[];if(nodes.length>0)value=Element.collectTextNodes(nodes[0],this.options.select);}else
value=Element.collectTextNodesIgnoreClass(selectedElement,'informal');var lastTokenPos=this.findLastToken();if(lastTokenPos!=-1){var newValue=this.element.value.substr(0,lastTokenPos+1);var whitespace=this.element.value.substr(lastTokenPos+1).match(/^\s+/);if(whitespace)
newValue+=whitespace[0];this.element.value=newValue+value;}else{this.element.value=value;}
this.element.focus();if(this.options.afterUpdateElement)
this.options.afterUpdateElement(this.element,selectedElement);},updateChoices:function(choices){if(!this.changed&&this.hasFocus){this.update.innerHTML=choices;Element.cleanWhitespace(this.update);Element.cleanWhitespace(this.update.down());if(this.update.firstChild&&this.update.down().childNodes){this.entryCount=this.update.down().childNodes.length;for(var i=0;i<this.entryCount;i++){var entry=this.getEntry(i);entry.autocompleteIndex=i;this.addObservers(entry);}}else{this.entryCount=0;}
this.stopIndicator();this.index=0;if(this.entryCount==1&&this.options.autoSelect){this.selectEntry();this.hide();}else{this.render();}}},addObservers:function(element){Event.observe(element,"mouseover",this.onHover.bindAsEventListener(this));Event.observe(element,"click",this.onClick.bindAsEventListener(this));},onObserverEvent:function(){this.changed=false;if(this.getToken().length>=this.options.minChars){this.startIndicator();this.getUpdatedChoices();}else{this.active=false;this.hide();}},getToken:function(){var tokenPos=this.findLastToken();if(tokenPos!=-1)
var ret=this.element.value.substr(tokenPos+1).replace(/^\s+/,'').replace(/\s+$/,'');else
var ret=this.element.value;return/\n/.test(ret)?'':ret;},findLastToken:function(){var lastTokenPos=-1;for(var i=0;i<this.options.tokens.length;i++){var thisTokenPos=this.element.value.lastIndexOf(this.options.tokens[i]);if(thisTokenPos>lastTokenPos)
lastTokenPos=thisTokenPos;}
return lastTokenPos;}}
Ajax.Autocompleter=Class.create();Object.extend(Object.extend(Ajax.Autocompleter.prototype,Autocompleter.Base.prototype),{initialize:function(element,update,url,options){this.baseInitialize(element,update,options);this.options.asynchronous=true;this.options.onComplete=this.onComplete.bind(this);this.options.defaultParams=this.options.parameters||null;this.url=url;},getUpdatedChoices:function(){entry=encodeURIComponent(this.options.paramName)+'='+
encodeURIComponent(this.getToken());this.options.parameters=this.options.callback?this.options.callback(this.element,entry):entry;if(this.options.defaultParams)
this.options.parameters+='&'+this.options.defaultParams;new Ajax.Request(this.url,this.options);},onComplete:function(request){this.updateChoices(request.responseText);}});Autocompleter.Local=Class.create();Autocompleter.Local.prototype=Object.extend(new Autocompleter.Base(),{initialize:function(element,update,array,options){this.baseInitialize(element,update,options);this.options.array=array;},getUpdatedChoices:function(){this.updateChoices(this.options.selector(this));},setOptions:function(options){this.options=Object.extend({choices:10,partialSearch:true,partialChars:2,ignoreCase:true,fullSearch:false,selector:function(instance){var ret=[];var partial=[];var entry=instance.getToken();var count=0;for(var i=0;i<instance.options.array.length&&ret.length<instance.options.choices;i++){var elem=instance.options.array[i];var foundPos=instance.options.ignoreCase?elem.toLowerCase().indexOf(entry.toLowerCase()):elem.indexOf(entry);while(foundPos!=-1){if(foundPos==0&&elem.length!=entry.length){ret.push("<li><strong>"+elem.substr(0,entry.length)+"</strong>"+
elem.substr(entry.length)+"</li>");break;}else if(entry.length>=instance.options.partialChars&&instance.options.partialSearch&&foundPos!=-1){if(instance.options.fullSearch||/\s/.test(elem.substr(foundPos-1,1))){partial.push("<li>"+elem.substr(0,foundPos)+"<strong>"+
elem.substr(foundPos,entry.length)+"</strong>"+elem.substr(foundPos+entry.length)+"</li>");break;}}
foundPos=instance.options.ignoreCase?elem.toLowerCase().indexOf(entry.toLowerCase(),foundPos+1):elem.indexOf(entry,foundPos+1);}}
if(partial.length)
ret=ret.concat(partial.slice(0,instance.options.choices-ret.length))
return"<ul>"+ret.join('')+"</ul>";}},options||{});}});Field.scrollFreeActivate=function(field){setTimeout(function(){Field.activate(field);},1);}
Ajax.InPlaceEditor=Class.create();Ajax.InPlaceEditor.defaultHighlightColor="#FFFF99";Ajax.InPlaceEditor.prototype={initialize:function(element,url,options){this.url=url;this.element=$(element);this.options=Object.extend({paramName:"value",okButton:true,okLink:false,okText:"ok",cancelButton:false,cancelLink:true,cancelText:"cancel",textBeforeControls:'',textBetweenControls:'',textAfterControls:'',savingText:"Saving...",clickToEditText:"Click to edit",okText:"ok",rows:1,onComplete:function(transport,element){new Effect.Highlight(element,{startcolor:this.options.highlightcolor});},onFailure:function(transport){alert("Error communicating with the server: "+transport.responseText.stripTags());},callback:function(form){return Form.serialize(form);},handleLineBreaks:true,loadingText:'Loading...',savingClassName:'inplaceeditor-saving',loadingClassName:'inplaceeditor-loading',formClassName:'inplaceeditor-form',highlightcolor:Ajax.InPlaceEditor.defaultHighlightColor,highlightendcolor:"#FFFFFF",externalControl:null,submitOnBlur:false,ajaxOptions:{},evalScripts:false},options||{});if(!this.options.formId&&this.element.id){this.options.formId=this.element.id+"-inplaceeditor";if($(this.options.formId)){this.options.formId=null;}}
if(this.options.externalControl){this.options.externalControl=$(this.options.externalControl);}
this.originalBackground=Element.getStyle(this.element,'background-color');if(!this.originalBackground){this.originalBackground="transparent";}
this.element.title=this.options.clickToEditText;this.onclickListener=this.enterEditMode.bindAsEventListener(this);this.mouseoverListener=this.enterHover.bindAsEventListener(this);this.mouseoutListener=this.leaveHover.bindAsEventListener(this);Event.observe(this.element,'click',this.onclickListener);Event.observe(this.element,'mouseover',this.mouseoverListener);Event.observe(this.element,'mouseout',this.mouseoutListener);if(this.options.externalControl){Event.observe(this.options.externalControl,'click',this.onclickListener);Event.observe(this.options.externalControl,'mouseover',this.mouseoverListener);Event.observe(this.options.externalControl,'mouseout',this.mouseoutListener);}},enterEditMode:function(evt){if(this.saving)return;if(this.editing)return;this.editing=true;this.onEnterEditMode();if(this.options.externalControl){Element.hide(this.options.externalControl);}
Element.hide(this.element);this.createForm();this.element.parentNode.insertBefore(this.form,this.element);if(!this.options.loadTextURL)Field.scrollFreeActivate(this.editField);if(evt){Event.stop(evt);}
return false;},createForm:function(){this.form=document.createElement("form");this.form.id=this.options.formId;Element.addClassName(this.form,this.options.formClassName)
this.form.onsubmit=this.onSubmit.bind(this);this.createEditField();if(this.options.textarea){var br=document.createElement("br");this.form.appendChild(br);}
if(this.options.textBeforeControls)
this.form.appendChild(document.createTextNode(this.options.textBeforeControls));if(this.options.okButton){var okButton=document.createElement("input");okButton.type="submit";okButton.value=this.options.okText;okButton.className='editor_ok_button';this.form.appendChild(okButton);}
if(this.options.okLink){var okLink=document.createElement("a");okLink.href="#";okLink.appendChild(document.createTextNode(this.options.okText));okLink.onclick=this.onSubmit.bind(this);okLink.className='editor_ok_link';this.form.appendChild(okLink);}
if(this.options.textBetweenControls&&(this.options.okLink||this.options.okButton)&&(this.options.cancelLink||this.options.cancelButton))
this.form.appendChild(document.createTextNode(this.options.textBetweenControls));if(this.options.cancelButton){var cancelButton=document.createElement("input");cancelButton.type="submit";cancelButton.value=this.options.cancelText;cancelButton.onclick=this.onclickCancel.bind(this);cancelButton.className='editor_cancel_button';this.form.appendChild(cancelButton);}
if(this.options.cancelLink){var cancelLink=document.createElement("a");cancelLink.href="#";cancelLink.appendChild(document.createTextNode(this.options.cancelText));cancelLink.onclick=this.onclickCancel.bind(this);cancelLink.className='editor_cancel editor_cancel_link';this.form.appendChild(cancelLink);}
if(this.options.textAfterControls)
this.form.appendChild(document.createTextNode(this.options.textAfterControls));},hasHTMLLineBreaks:function(string){if(!this.options.handleLineBreaks)return false;return string.match(/<br/i)||string.match(/<p>/i);},convertHTMLLineBreaks:function(string){return string.replace(/<br>/gi,"\n").replace(/<br\/>/gi,"\n").replace(/<\/p>/gi,"\n").replace(/<p>/gi,"");},createEditField:function(){var text;if(this.options.loadTextURL){text=this.options.loadingText;}else{text=this.getText();}
var obj=this;if(this.options.rows==1&&!this.hasHTMLLineBreaks(text)){this.options.textarea=false;var textField=document.createElement("input");textField.obj=this;textField.type="text";textField.name=this.options.paramName;textField.value=text;textField.style.backgroundColor=this.options.highlightcolor;textField.className='editor_field';var size=this.options.size||this.options.cols||0;if(size!=0)textField.size=size;if(this.options.submitOnBlur)
textField.onblur=this.onSubmit.bind(this);this.editField=textField;}else{this.options.textarea=true;var textArea=document.createElement("textarea");textArea.obj=this;textArea.name=this.options.paramName;textArea.value=this.convertHTMLLineBreaks(text);textArea.rows=this.options.rows;textArea.cols=this.options.cols||40;textArea.className='editor_field';if(this.options.submitOnBlur)
textArea.onblur=this.onSubmit.bind(this);this.editField=textArea;}
if(this.options.loadTextURL){this.loadExternalText();}
this.form.appendChild(this.editField);},getText:function(){return this.element.innerHTML;},loadExternalText:function(){Element.addClassName(this.form,this.options.loadingClassName);this.editField.disabled=true;new Ajax.Request(this.options.loadTextURL,Object.extend({asynchronous:true,onComplete:this.onLoadedExternalText.bind(this)},this.options.ajaxOptions));},onLoadedExternalText:function(transport){Element.removeClassName(this.form,this.options.loadingClassName);this.editField.disabled=false;this.editField.value=transport.responseText.stripTags();Field.scrollFreeActivate(this.editField);},onclickCancel:function(){this.onComplete();this.leaveEditMode();return false;},onFailure:function(transport){this.options.onFailure(transport);if(this.oldInnerHTML){this.element.innerHTML=this.oldInnerHTML;this.oldInnerHTML=null;}
return false;},onSubmit:function(){var form=this.form;var value=this.editField.value;this.onLoading();if(this.options.evalScripts){new Ajax.Request(this.url,Object.extend({parameters:this.options.callback(form,value),onComplete:this.onComplete.bind(this),onFailure:this.onFailure.bind(this),asynchronous:true,evalScripts:true},this.options.ajaxOptions));}else{new Ajax.Updater({success:this.element,failure:null},this.url,Object.extend({parameters:this.options.callback(form,value),onComplete:this.onComplete.bind(this),onFailure:this.onFailure.bind(this)},this.options.ajaxOptions));}
if(arguments.length>1){Event.stop(arguments[0]);}
return false;},onLoading:function(){this.saving=true;this.removeForm();this.leaveHover();this.showSaving();},showSaving:function(){this.oldInnerHTML=this.element.innerHTML;this.element.innerHTML=this.options.savingText;Element.addClassName(this.element,this.options.savingClassName);this.element.style.backgroundColor=this.originalBackground;Element.show(this.element);},removeForm:function(){if(this.form){if(this.form.parentNode)Element.remove(this.form);this.form=null;}},enterHover:function(){if(this.saving)return;this.element.style.backgroundColor=this.options.highlightcolor;if(this.effect){this.effect.cancel();}
Element.addClassName(this.element,this.options.hoverClassName)},leaveHover:function(){if(this.options.backgroundColor){this.element.style.backgroundColor=this.oldBackground;}
Element.removeClassName(this.element,this.options.hoverClassName)
if(this.saving)return;this.effect=new Effect.Highlight(this.element,{startcolor:this.options.highlightcolor,endcolor:this.options.highlightendcolor,restorecolor:this.originalBackground});},leaveEditMode:function(){Element.removeClassName(this.element,this.options.savingClassName);this.removeForm();this.leaveHover();this.element.style.backgroundColor=this.originalBackground;Element.show(this.element);if(this.options.externalControl){Element.show(this.options.externalControl);}
this.editing=false;this.saving=false;this.oldInnerHTML=null;this.onLeaveEditMode();},onComplete:function(transport){this.leaveEditMode();this.options.onComplete.bind(this)(transport,this.element);},onEnterEditMode:function(){},onLeaveEditMode:function(){},dispose:function(){if(this.oldInnerHTML){this.element.innerHTML=this.oldInnerHTML;}
this.leaveEditMode();Event.stopObserving(this.element,'click',this.onclickListener);Event.stopObserving(this.element,'mouseover',this.mouseoverListener);Event.stopObserving(this.element,'mouseout',this.mouseoutListener);if(this.options.externalControl){Event.stopObserving(this.options.externalControl,'click',this.onclickListener);Event.stopObserving(this.options.externalControl,'mouseover',this.mouseoverListener);Event.stopObserving(this.options.externalControl,'mouseout',this.mouseoutListener);}}};Ajax.InPlaceCollectionEditor=Class.create();Object.extend(Ajax.InPlaceCollectionEditor.prototype,Ajax.InPlaceEditor.prototype);Object.extend(Ajax.InPlaceCollectionEditor.prototype,{createEditField:function(){if(!this.cached_selectTag){var selectTag=document.createElement("select");var collection=this.options.collection||[];var optionTag;collection.each(function(e,i){optionTag=document.createElement("option");optionTag.value=(e instanceof Array)?e[0]:e;if((typeof this.options.value=='undefined')&&((e instanceof Array)?this.element.innerHTML==e[1]:e==optionTag.value))optionTag.selected=true;if(this.options.value==optionTag.value)optionTag.selected=true;optionTag.appendChild(document.createTextNode((e instanceof Array)?e[1]:e));selectTag.appendChild(optionTag);}.bind(this));this.cached_selectTag=selectTag;}
this.editField=this.cached_selectTag;if(this.options.loadTextURL)this.loadExternalText();this.form.appendChild(this.editField);this.options.callback=function(form,value){return"value="+encodeURIComponent(value);}}});Form.Element.DelayedObserver=Class.create();Form.Element.DelayedObserver.prototype={initialize:function(element,delay,callback){this.delay=delay||0.5;this.element=$(element);this.callback=callback;this.timer=null;this.lastValue=$F(this.element);Event.observe(this.element,'keyup',this.delayedListener.bindAsEventListener(this));},delayedListener:function(event){if(this.lastValue==$F(this.element))return;if(this.timer)clearTimeout(this.timer);this.timer=setTimeout(this.onTimerEvent.bind(this),this.delay*1000);this.lastValue=$F(this.element);},onTimerEvent:function(){this.timer=null;this.callback(this.element,$F(this.element));}};Array.prototype.______array='______array';Prado.JSON={org:'http://www.JSON.org',copyright:'(c)2005 JSON.org',license:'http://www.crockford.com/JSON/license.html',stringify:function(arg){var c,i,l,s='',v;switch(typeof arg){case'object':if(arg){if(arg.______array=='______array'){for(i=0;i<arg.length;++i){v=this.stringify(arg[i]);if(s){s+=',';}
s+=v;}
return'['+s+']';}else if(typeof arg.toString!='undefined'){for(i in arg){v=arg[i];if(typeof v!='undefined'&&typeof v!='function'){v=this.stringify(v);if(s){s+=',';}
s+=this.stringify(i)+':'+v;}}
return'{'+s+'}';}}
return'null';case'number':return isFinite(arg)?String(arg):'null';case'string':l=arg.length;s='"';for(i=0;i<l;i+=1){c=arg.charAt(i);if(c>=' '){if(c=='\\'||c=='"'){s+='\\';}
s+=c;}else{switch(c){case'\b':s+='\\b';break;case'\f':s+='\\f';break;case'\n':s+='\\n';break;case'\r':s+='\\r';break;case'\t':s+='\\t';break;default:c=c.charCodeAt();s+='\\u00'+Math.floor(c/16).toString(16)+
(c%16).toString(16);}}}
return s+'"';case'boolean':return String(arg);default:return'null';}},parse:function(text){var at=0;var ch=' ';function error(m){throw{name:'JSONError',message:m,at:at-1,text:text};}
function next(){ch=text.charAt(at);at+=1;return ch;}
function white(){while(ch){if(ch<=' '){next();}else if(ch=='/'){switch(next()){case'/':while(next()&&ch!='\n'&&ch!='\r'){}
break;case'*':next();for(;;){if(ch){if(ch=='*'){if(next()=='/'){next();break;}}else{next();}}else{error("Unterminated comment");}}
break;default:error("Syntax error");}}else{break;}}}
function string(){var i,s='',t,u;if(ch=='"'){outer:while(next()){if(ch=='"'){next();return s;}else if(ch=='\\'){switch(next()){case'b':s+='\b';break;case'f':s+='\f';break;case'n':s+='\n';break;case'r':s+='\r';break;case't':s+='\t';break;case'u':u=0;for(i=0;i<4;i+=1){t=parseInt(next(),16);if(!isFinite(t)){break outer;}
u=u*16+t;}
s+=String.fromCharCode(u);break;default:s+=ch;}}else{s+=ch;}}}
error("Bad string");}
function array(){var a=[];if(ch=='['){next();white();if(ch==']'){next();return a;}
while(ch){a.push(value());white();if(ch==']'){next();return a;}else if(ch!=','){break;}
next();white();}}
error("Bad array");}
function object(){var k,o={};if(ch=='{'){next();white();if(ch=='}'){next();return o;}
while(ch){k=string();white();if(ch!=':'){break;}
next();o[k]=value();white();if(ch=='}'){next();return o;}else if(ch!=','){break;}
next();white();}}
error("Bad object");}
function number(){var n='',v;if(ch=='-'){n='-';next();}
while(ch>='0'&&ch<='9'){n+=ch;next();}
if(ch=='.'){n+='.';while(next()&&ch>='0'&&ch<='9'){n+=ch;}}
if(ch=='e'||ch=='E'){n+='e';next();if(ch=='-'||ch=='+'){n+=ch;next();}
while(ch>='0'&&ch<='9'){n+=ch;next();}}
v=+n;if(!isFinite(v)){}else{return v;}}
function word(){switch(ch){case't':if(next()=='r'&&next()=='u'&&next()=='e'){next();return true;}
break;case'f':if(next()=='a'&&next()=='l'&&next()=='s'&&next()=='e'){next();return false;}
break;case'n':if(next()=='u'&&next()=='l'&&next()=='l'){next();return null;}
break;}
error("Syntax error");}
function value(){white();switch(ch){case'{':return object();case'[':return array();case'"':return string();case'-':return number();default:return ch>='0'&&ch<='9'?number():word();}}
return value();}};Prado.AjaxRequest=Class.create();Prado.AjaxRequest.prototype=Ajax.Request.prototype;Object.extend(Prado.AjaxRequest.prototype,{respondToReadyState:function(readyState)
{var event=Ajax.Request.Events[readyState];var transport=this.transport,json=this.getBodyDataPart(Prado.CallbackRequest.DATA_HEADER);if(event=='Complete')
{var redirectUrl=this.getBodyContentPart(Prado.CallbackRequest.REDIRECT_HEADER);if(redirectUrl)
document.location.href=redirectUrl;if((this.getHeader('Content-type')||'').match(/^text\/javascript/i))
{try
{json=eval('('+transport.responseText+')');}catch(e)
{if(typeof(json)=="string")
json=Prado.CallbackRequest.decode(result);}}
try
{Prado.CallbackRequest.updatePageState(this,transport);Ajax.Responders.dispatch('on'+transport.status,this,transport,json);Prado.CallbackRequest.dispatchActions(transport,this.getBodyDataPart(Prado.CallbackRequest.ACTION_HEADER));(this.options['on'+this.transport.status]||this.options['on'+(this.success()?'Success':'Failure')]||Prototype.emptyFunction)(this,json);}catch(e){this.dispatchException(e);}}
try{(this.options['on'+event]||Prototype.emptyFunction)(this,json);Ajax.Responders.dispatch('on'+event,this,transport,json);}catch(e){this.dispatchException(e);}
if(event=='Complete')
this.transport.onreadystatechange=Prototype.emptyFunction;},getHeaderData:function(name)
{return this.getJsonData(this.getHeader(name));},getBodyContentPart:function(name)
{if(typeof(this.transport.responseText)=="string")
return Prado.Element.extractContent(this.transport.responseText,name);},getJsonData:function(json)
{try
{return eval('('+json+')');}
catch(e)
{if(typeof(json)=="string")
return Prado.CallbackRequest.decode(json);}},getBodyDataPart:function(name)
{return this.getJsonData(this.getBodyContentPart(name));}});Prado.CallbackRequest=Class.create();Object.extend(Prado.CallbackRequest,{FIELD_CALLBACK_TARGET:'PRADO_CALLBACK_TARGET',FIELD_CALLBACK_PARAMETER:'PRADO_CALLBACK_PARAMETER',FIELD_CALLBACK_PAGESTATE:'PRADO_PAGESTATE',FIELD_POSTBACK_TARGET:'PRADO_POSTBACK_TARGET',FIELD_POSTBACK_PARAMETER:'PRADO_POSTBACK_PARAMETER',PostDataLoaders:[],DATA_HEADER:'X-PRADO-DATA',ACTION_HEADER:'X-PRADO-ACTIONS',ERROR_HEADER:'X-PRADO-ERROR',PAGESTATE_HEADER:'X-PRADO-PAGESTATE',REDIRECT_HEADER:'X-PRADO-REDIRECT',requestQueue:[],requests:{},getRequestById:function(id)
{var requests=Prado.CallbackRequest.requests;if(typeof(requests[id])!="undefined")
return requests[id];},dispatch:function(id)
{var requests=Prado.CallbackRequest.requests;if(typeof(requests[id])!="undefined")
requests[id].dispatch();},addPostLoaders:function(ids)
{var self=Prado.CallbackRequest;self.PostDataLoaders=self.PostDataLoaders.concat(ids);var list=[];self.PostDataLoaders.each(function(id)
{if(list.indexOf(id)<0)
list.push(id);});self.PostDataLoaders=list;},dispatchActions:function(transport,actions)
{var self=Prado.CallbackRequest;if(actions&&actions.length>0)
actions.each(self.__run.bind(self,transport));},__run:function(transport,command)
{var self=Prado.CallbackRequest;self.transport=transport;for(var method in command)
{try
{method.toFunction().apply(self,command[method]);}
catch(e)
{if(typeof(Logger)!="undefined")
self.Exception.onException(null,e);}}},Exception:{"on500":function(request,transport,data)
{var e=request.getHeaderData(Prado.CallbackRequest.ERROR_HEADER);Logger.error("Callback Server Error "+e.code,this.formatException(e));},'on200':function(request,transport,data)
{if(transport.status<500)
{var msg='HTTP '+transport.status+" with response : \n";if(transport.responseText.trim().length>0)
{var f=RegExp('(<!--X-PRADO[^>]+-->)([\\s\\S\\w\\W]*)(<!--//X-PRADO[^>]+-->)',"m");msg+=transport.responseText.replace(f,'')+"\n";}
if(typeof(data)!="undefined"&&data!=null)
msg+="Data : \n"+inspect(data)+"\n";data=request.getBodyDataPart(Prado.CallbackRequest.ACTION_HEADER);if(data&&data.length>0)
{msg+="Actions : \n";data.each(function(action)
{msg+=inspect(action)+"\n";});}
Logger.info(msg);}},onException:function(request,e)
{msg="";$H(e).each(function(item)
{msg+=item.key+": "+item.value+"\n";})
Logger.error('Uncaught Callback Client Exception:',msg);},formatException:function(e)
{var msg=e.type+" with message \""+e.message+"\"";msg+=" in "+e.file+"("+e.line+")\n";msg+="Stack trace:\n";var trace=e.trace;for(var i=0;i<trace.length;i++)
{msg+="  #"+i+" "+trace[i].file;msg+="("+trace[i].line+"): ";msg+=trace[i]["class"]+"->"+trace[i]["function"]+"()"+"\n";}
msg+=e.version+" "+e.time+"\n";return msg;}},encode:function(data)
{return Prado.JSON.stringify(data);},decode:function(data)
{if(typeof(data)=="string"&&data.trim().length>0)
return Prado.JSON.parse(data);else
return null;},dispatchNormalRequest:function(callback)
{callback.request(callback.url);return true;},tryNextRequest:function()
{var self=Prado.CallbackRequest;if(typeof(self.currentRequest)=='undefined'||self.currentRequest==null)
{if(self.requestQueue.length>0)
return self.dispatchQueue();}},updatePageState:function(request,transport)
{var self=Prado.CallbackRequest;var pagestate=$(self.FIELD_CALLBACK_PAGESTATE);var enabled=request.ActiveControl.EnablePageStateUpdate&&request.ActiveControl.HasPriority;var aborted=self.currentRequest==null;if(enabled&&!aborted&&pagestate)
{var data=request.getBodyContentPart(self.PAGESTATE_HEADER);if(typeof(data)=="string"&&data.length>0)
pagestate.value=data;else
{if(typeof(Logger)!="undefined")
Logger.warn("Missing page state:"+data);self.endCurrentRequest();return false;}}
self.endCurrentRequest();return true;},enqueue:function(callback)
{var self=Prado.CallbackRequest;self.requestQueue.push(callback);self.tryNextRequest();},dispatchQueue:function()
{var self=Prado.CallbackRequest;var callback=self.requestQueue.shift();self.currentRequest=callback;callback.options.postBody=callback._getPostData(),callback.request(callback.url);callback.timeout=setTimeout(function()
{self.abortRequest(callback.id);},callback.ActiveControl.RequestTimeOut);},endCurrentRequest:function()
{var self=Prado.CallbackRequest;clearTimeout(self.currentRequest.timeout);self.currentRequest=null;},abortRequest:function(id)
{var self=Prado.CallbackRequest;if(typeof(self.currentRequest)!='undefined'&&self.currentRequest!=null&&self.currentRequest.id==id)
{var request=self.currentRequest;if(request.transport.readyState<4)
request.transport.abort();self.endCurrentRequest();}
self.tryNextRequest();}})
Ajax.Responders.register({onComplete:function(request)
{if(request.ActiveControl.HasPriority)
Prado.CallbackRequest.tryNextRequest();}});Event.OnLoad(function()
{if(typeof Logger!="undefined")
Ajax.Responders.register(Prado.CallbackRequest.Exception);});Prado.CallbackRequest.prototype=Object.extend(Prado.AjaxRequest.prototype,{initialize:function(id,options)
{this.url=this.getCallbackUrl();this.transport=Ajax.getTransport();this.Enabled=true;this.id=id;if(typeof(id)=="string")
Prado.CallbackRequest.requests[id]=this;this.setOptions(Object.extend({RequestTimeOut:30000,EnablePageStateUpdate:true,HasPriority:true,CausesValidation:true,ValidationGroup:null,PostInputs:true},options||{}));this.ActiveControl=this.options;},getCallbackUrl:function()
{return $('PRADO_PAGESTATE').form.action;},setCallbackParameter:function(value)
{this.ActiveControl['CallbackParameter']=value;},getCallbackParameter:function()
{return this.ActiveControl['CallbackParameter'];},setRequestTimeOut:function(timeout)
{this.ActiveControl['RequestTimeOut']=timeout;},getRequestTimeOut:function()
{return this.ActiveControl['RequestTimeOut'];},setCausesValidation:function(validate)
{this.ActiveControl['CausesValidation']=validate;},getCausesValidation:function()
{return this.ActiveControl['CausesValidation'];},setValidationGroup:function(group)
{this.ActiveControl['ValidationGroup']=group;},getValidationGroup:function()
{return this.ActiveControl['ValidationGroup'];},dispatch:function()
{if(typeof tinyMCE!="undefined")
tinyMCE.triggerSave();if(this.ActiveControl.CausesValidation&&typeof(Prado.Validation)!="undefined")
{var form=this.ActiveControl.Form||Prado.Validation.getForm();if(Prado.Validation.validate(form,this.ActiveControl.ValidationGroup,this)==false)
return false;}
if(this.ActiveControl.onPreDispatch)
this.ActiveControl.onPreDispatch(this,null);if(!this.Enabled)
return;if(this.ActiveControl.HasPriority)
{return Prado.CallbackRequest.enqueue(this);}
else
return Prado.CallbackRequest.dispatchNormalRequest(this);},abort:function()
{return Prado.CallbackRequest.abortRequest(this.id);},_getPostData:function()
{var data={};var callback=Prado.CallbackRequest;if(this.ActiveControl.PostInputs!=false)
{callback.PostDataLoaders.each(function(name)
{$A(document.getElementsByName(name)).each(function(element)
{if(element.type&&element.name==name)
{value=$F(element);if(typeof(value)!="undefined"&&value!=null)
data[name]=value;}})})}
if(typeof(this.ActiveControl.CallbackParameter)!="undefined")
data[callback.FIELD_CALLBACK_PARAMETER]=callback.encode(this.ActiveControl.CallbackParameter);var pageState=$F(callback.FIELD_CALLBACK_PAGESTATE);if(typeof(pageState)!="undefined")
data[callback.FIELD_CALLBACK_PAGESTATE]=pageState;data[callback.FIELD_CALLBACK_TARGET]=this.id;if(this.ActiveControl.EventTarget)
data[callback.FIELD_POSTBACK_TARGET]=this.ActiveControl.EventTarget;if(this.ActiveControl.EventParameter)
data[callback.FIELD_POSTBACK_PARAMETER]=this.ActiveControl.EventParameter;return $H(data).toQueryString();}});Prado.Callback=function(UniqueID,parameter,onSuccess,options)
{var callback={'CallbackParameter':parameter||'','onSuccess':onSuccess||Prototype.emptyFunction};Object.extend(callback,options||{});request=new Prado.CallbackRequest(UniqueID,callback);request.dispatch();return false;}
Prado.WebUI.CallbackControl=Class.extend(Prado.WebUI.PostBackControl,{onPostBack:function(event,options)
{var request=new Prado.CallbackRequest(options.EventTarget,options);request.dispatch();Event.stop(event);}});Prado.WebUI.TActiveButton=Class.extend(Prado.WebUI.CallbackControl);Prado.WebUI.TActiveLinkButton=Class.extend(Prado.WebUI.CallbackControl);Prado.WebUI.TActiveImageButton=Class.extend(Prado.WebUI.TImageButton,{onPostBack:function(event,options)
{this.addXYInput(event,options);var request=new Prado.CallbackRequest(options.EventTarget,options);request.dispatch();Event.stop(event);}});Prado.WebUI.TActiveCheckBox=Class.extend(Prado.WebUI.CallbackControl,{onPostBack:function(event,options)
{var request=new Prado.CallbackRequest(options.EventTarget,options);if(request.dispatch()==false)
Event.stop(event);}});Prado.WebUI.TActiveRadioButton=Class.extend(Prado.WebUI.TActiveCheckBox);Prado.WebUI.TActiveCheckBoxList=Base.extend({constructor:function(options)
{for(var i=0;i<options.ItemCount;i++)
{var checkBoxOptions=Object.extend({ID:options.ListID+"_c"+i,EventTarget:options.ListName+"$c"+i},options);new Prado.WebUI.TActiveCheckBox(checkBoxOptions);}}});Prado.WebUI.TActiveRadioButtonList=Prado.WebUI.TActiveCheckBoxList;Prado.WebUI.TActiveTextBox=Class.extend(Prado.WebUI.TTextBox,{onInit:function(options)
{this.options=options;if(options['TextMode']!='MultiLine')
Event.observe(this.element,"keydown",this.handleReturnKey.bind(this));if(this.options['AutoPostBack']==true)
Event.observe(this.element,"change",this.doCallback.bindEvent(this,options));},doCallback:function(event,options)
{var request=new Prado.CallbackRequest(options.EventTarget,options);request.dispatch();Event.stop(event);}});Prado.WebUI.TAutoComplete=Class.extend(Autocompleter.Base,Prado.WebUI.TActiveTextBox.prototype);Prado.WebUI.TAutoComplete=Class.extend(Prado.WebUI.TAutoComplete,{initialize:function(options)
{this.options=options;this.hasResults=false;this.baseInitialize(options.ID,options.ResultPanel,options);Object.extend(this.options,{onSuccess:this.onComplete.bind(this)});if(options.AutoPostBack)
this.onInit(options);},doCallback:function(event,options)
{if(!this.active)
{var request=new Prado.CallbackRequest(this.options.EventTarget,options);request.dispatch();Event.stop(event);}},onClick:function(event)
{var element=Event.findElement(event,'LI');this.index=element.autocompleteIndex;this.selectEntry();this.hide();Event.fireEvent(this.element,"change");},getUpdatedChoices:function()
{var options=new Array(this.getToken(),"__TAutoComplete_onSuggest__");Prado.Callback(this.options.EventTarget,options,null,this.options);},selectEntry:function()
{if(this.hasResults)
{this.active=false;this.updateElement(this.getCurrentEntry());}},onComplete:function(request,boundary)
{var result=Prado.Element.extractContent(request.transport.responseText,boundary);if(typeof(result)=="string")
{if(result.length>0)
{this.hasResults=true;this.updateChoices(result);}
else
{this.active=false;this.hasResults=false;this.hide();}}}});Prado.WebUI.TTimeTriggeredCallback=Base.extend({constructor:function(options)
{this.options=Object.extend({Interval:1},options||{});Prado.WebUI.TTimeTriggeredCallback.register(this);},startTimer:function()
{setTimeout(this.onTimerEvent.bind(this),100);if(typeof(this.timer)=='undefined'||this.timer==null)
this.timer=setInterval(this.onTimerEvent.bind(this),this.options.Interval*1000);},stopTimer:function()
{if(typeof(this.timer)!='undefined')
{clearInterval(this.timer);this.timer=null;}},onTimerEvent:function()
{var request=new Prado.CallbackRequest(this.options.EventTarget,this.options);request.dispatch();}},{timers:{},register:function(timer)
{Prado.WebUI.TTimeTriggeredCallback.timers[timer.options.ID]=timer;},start:function(id)
{Prado.WebUI.TTimeTriggeredCallback.timers[id].startTimer();},stop:function(id)
{Prado.WebUI.TTimeTriggeredCallback.timers[id].stopTimer();}});Prado.WebUI.ActiveListControl=Base.extend({constructor:function(options)
{this.element=$(options.ID);if(this.element)
{this.options=options;Event.observe(this.element,"change",this.doCallback.bind(this));}},doCallback:function(event)
{var request=new Prado.CallbackRequest(this.options.EventTarget,this.options);request.dispatch();Event.stop(event);}});Prado.WebUI.TActiveDropDownList=Prado.WebUI.ActiveListControl;Prado.WebUI.TActiveListBox=Prado.WebUI.ActiveListControl;Prado.WebUI.TEventTriggeredCallback=Base.extend({constructor:function(options)
{this.options=options;var element=$(options['ControlID']);if(element)
Event.observe(element,this.getEventName(element),this.doCallback.bind(this));},getEventName:function(element)
{var name=this.options.EventName;if(typeof(name)=="undefined"&&element.type)
{switch(element.type.toLowerCase())
{case'password':case'text':case'textarea':case'select-one':case'select-multiple':return'change';}}
return typeof(name)=="undefined"||name=="undefined"?'click':name;},doCallback:function(event)
{var request=new Prado.CallbackRequest(this.options.EventTarget,this.options);request.dispatch();if(this.options.StopEvent==true)
Event.stop(event);}});Prado.WebUI.TValueTriggeredCallback=Base.extend({count:1,observing:true,constructor:function(options)
{this.options=options;this.options.PropertyName=this.options.PropertyName||'value';var element=$(options['ControlID']);this.value=element?element[this.options.PropertyName]:undefined;Prado.WebUI.TValueTriggeredCallback.register(this);this.startObserving();},stopObserving:function()
{clearTimeout(this.timer);this.observing=false;},startObserving:function()
{this.timer=setTimeout(this.checkChanges.bind(this),this.options.Interval*1000);},checkChanges:function()
{var element=$(this.options.ControlID);if(element)
{var value=element[this.options.PropertyName];if(this.value!=value)
{this.doCallback(this.value,value);this.value=value;this.count=1;}
else
this.count=this.count+this.options.Decay;if(this.observing)
this.time=setTimeout(this.checkChanges.bind(this),parseInt(this.options.Interval*1000*this.count));}},doCallback:function(oldValue,newValue)
{var request=new Prado.CallbackRequest(this.options.EventTarget,this.options);var param={'OldValue':oldValue,'NewValue':newValue};request.setCallbackParameter(param);request.dispatch();}},{timers:{},register:function(timer)
{Prado.WebUI.TValueTriggeredCallback.timers[timer.options.ID]=timer;},stop:function(id)
{Prado.WebUI.TValueTriggeredCallback.timers[id].stopObserving();}});Prado.WebUI.TInPlaceTextBox=Base.extend({isSaving:false,isEditing:false,editField:null,constructor:function(options)
{this.options=Object.extend({LoadTextFromSource:false,TextMode:'SingleLine'},options||{});this.element=$(this.options.ID);Prado.WebUI.TInPlaceTextBox.register(this);this.createEditorInput();this.initializeListeners();},initializeListeners:function()
{this.onclickListener=this.enterEditMode.bindAsEventListener(this);Event.observe(this.element,'click',this.onclickListener);if(this.options.ExternalControl)
Event.observe($(this.options.ExternalControl),'click',this.onclickListener);},enterEditMode:function(evt)
{if(this.isSaving||this.isEditing)return;this.isEditing=true;this.onEnterEditMode();this.createEditorInput();this.showTextBox();this.editField.disabled=false;if(this.options.LoadTextOnEdit)
this.loadExternalText();Prado.Element.focus(this.editField);if(evt)
Event.stop(evt);return false;},exitEditMode:function(evt)
{this.isEditing=false;this.isSaving=false;this.editField.disabled=false;this.element.innerHTML=this.editField.value;this.showLabel();},showTextBox:function()
{Element.hide(this.element);Element.show(this.editField);},showLabel:function()
{Element.show(this.element);Element.hide(this.editField);},createEditorInput:function()
{if(this.editField==null)
this.createTextBox();this.editField.value=this.getText();},loadExternalText:function()
{this.editField.disabled=true;this.onLoadingText();options=new Array('__InlineEditor_loadExternalText__',this.getText());request=new Prado.CallbackRequest(this.options.EventTarget,this.options);request.setCausesValidation(false);request.setCallbackParameter(options);request.ActiveControl.onSuccess=this.onloadExternalTextSuccess.bind(this);request.ActiveControl.onFailure=this.onloadExternalTextFailure.bind(this);request.dispatch();},createTextBox:function()
{cssClass=this.element.className||'';inputName=this.options.EventTarget;options={'className':cssClass,name:inputName,id:this.options.TextBoxID};if(this.options.TextMode=='SingleLine')
{if(this.options.MaxLength>0)
options['maxlength']=this.options.MaxLength;this.editField=INPUT(options);}
else
{if(this.options.Rows>0)
options['rows']=this.options.Rows;if(this.options.Columns>0)
options['cols']=this.options.Columns;if(this.options.Wrap)
options['wrap']='off';this.editField=TEXTAREA(options);}
this.editField.style.display="none";this.element.parentNode.insertBefore(this.editField,this.element)
if(this.options.TextMode=='SingleLine')
{Event.observe(this.editField,"keydown",function(e)
{if(Event.keyCode(e)==Event.KEY_RETURN)
{var target=Event.element(e);if(target)
{Event.fireEvent(target,"blur");Event.stop(e);}}});}
Event.observe(this.editField,"blur",this.onTextBoxBlur.bind(this));},getText:function()
{return this.element.innerHTML;},onEnterEditMode:function()
{if(typeof(this.options.onEnterEditMode)=="function")
this.options.onEnterEditMode(this,null);},onTextBoxBlur:function(e)
{text=this.element.innerHTML;if(this.options.AutoPostBack&&text!=this.editField.value)
this.onTextChanged(text);else
{this.element.innerHTML=this.editField.value;this.isEditing=false;if(this.options.AutoHide)
this.showLabel();}},onTextChanged:function(text)
{request=new Prado.CallbackRequest(this.options.EventTarget,this.options);request.setCallbackParameter(text);request.ActiveControl.onSuccess=this.onTextChangedSuccess.bind(this);request.ActiveControl.onFailure=this.onTextChangedFailure.bind(this);if(request.dispatch())
{this.isSaving=true;this.editField.disabled=true;}},onLoadingText:function()
{},onloadExternalTextSuccess:function(request,parameter)
{this.isEditing=true;this.editField.disabled=false;this.editField.value=this.getText();Prado.Element.focus(this.editField);if(typeof(this.options.onSuccess)=="function")
this.options.onSuccess(sender,parameter);},onloadExternalTextFailure:function(request,parameter)
{this.isSaving=false;this.isEditing=false;this.showLabel();if(typeof(this.options.onFailure)=="function")
this.options.onFailure(sender,parameter);},onTextChangedSuccess:function(sender,parameter)
{this.isSaving=false;this.isEditing=false;if(this.options.AutoHide)
this.showLabel();this.element.innerHTML=parameter==null?this.editField.value:parameter;this.editField.disabled=false;if(typeof(this.options.onSuccess)=="function")
this.options.onSuccess(sender,parameter);},onTextChangedFailure:function(sender,parameter)
{this.editField.disabled=false;this.isSaving=false;this.isEditing=false;if(typeof(this.options.onFailure)=="function")
this.options.onFailure(sender,parameter);}},{textboxes:{},register:function(obj)
{Prado.WebUI.TInPlaceTextBox.textboxes[obj.options.TextBoxID]=obj;},setDisplayTextBox:function(id,value)
{var textbox=Prado.WebUI.TInPlaceTextBox.textboxes[id];if(textbox)
{if(value)
textbox.enterEditMode(null);else
{textbox.exitEditMode(null);}}}});Prado.WebUI.TActiveRatingList=Base.extend({selectedIndex:-1,rating:-1,enabled:true,readOnly:false,constructor:function(options)
{var cap=$(options.CaptionID);this.options=Object.extend({caption:cap?cap.innerHTML:''},options||{});Prado.WebUI.TActiveRatingList.register(this);this._init();this.selectedIndex=options.SelectedIndex;this.rating=options.Rating;if(options.Rating<=0&&options.SelectedIndex>=0)
this.rating=options.SelectedIndex+1;this.showRating(this.rating);},_init:function(options)
{Element.addClassName($(this.options.ListID),this.options.Style);this.radios=new Array();var index=0;for(var i=0;i<this.options.ItemCount;i++)
{var radio=$(this.options.ListID+'_c'+i);var td=radio.parentNode;if(radio&&td.tagName.toLowerCase()=='td')
{this.radios.push(radio);Event.observe(td,"mouseover",this.hover.bindEvent(this,index));Event.observe(td,"mouseout",this.recover.bindEvent(this,index));Event.observe(td,"click",this.click.bindEvent(this,index));index++;Element.addClassName(td,"rating");}}},hover:function(ev,index)
{if(this.enabled==false)return;for(var i=0;i<this.radios.length;i++)
{var node=this.radios[i].parentNode;var action=i<=index?'addClassName':'removeClassName'
Element[action](node,"rating_hover");Element.removeClassName(node,"rating_selected");Element.removeClassName(node,"rating_half");}
this.showCaption(this.getIndexCaption(index));},recover:function(ev,index)
{if(this.enabled==false)return;this.showRating(this.rating);this.showCaption(this.options.caption);},click:function(ev,index)
{if(this.enabled==false)return;for(var i=0;i<this.radios.length;i++)
this.radios[i].checked=(i==index);this.selectedIndex=index;this.setRating(index+1);this.dispatchRequest(ev);},dispatchRequest:function(ev)
{var requestOptions=Object.extend({ID:this.options.ListID+"_c"+this.selectedIndex,EventTarget:this.options.ListName+"$c"+this.selectedIndex},this.options);var request=new Prado.CallbackRequest(requestOptions.EventTarget,requestOptions);if(request.dispatch()==false)
Event.stop(ev);},setRating:function(value)
{this.rating=value;var base=Math.floor(value-1);var remainder=value-base-1;var halfMax=this.options.HalfRating["1"];var index=remainder>halfMax?base+1:base;for(var i=0;i<this.radios.length;i++)
this.radios[i].checked=(i==index);var caption=this.getIndexCaption(index);this.setCaption(caption);this.showCaption(caption);this.showRating(value);},showRating:function(value)
{var base=Math.floor(value-1);var remainder=value-base-1;var halfMin=this.options.HalfRating["0"];var halfMax=this.options.HalfRating["1"];var index=remainder>halfMax?base+1:base;var hasHalf=remainder>=halfMin&&remainder<=halfMax;for(var i=0;i<this.radios.length;i++)
{var node=this.radios[i].parentNode;var action=i>index?'removeClassName':'addClassName';Element[action](node,"rating_selected");if(i==index+1&&hasHalf)
Element.addClassName(node,"rating_half");else
Element.removeClassName(node,"rating_half");Element.removeClassName(node,"rating_hover");}},getIndexCaption:function(index)
{return index>-1?this.radios[index].value:this.options.caption;},showCaption:function(value)
{var caption=$(this.options.CaptionID);if(caption)caption.innerHTML=value;$(this.options.ListID).title=value;},setCaption:function(value)
{this.options.caption=value;this.showCaption(value);},setEnabled:function(value)
{this.enabled=value;for(var i=0;i<this.radios.length;i++)
{var action=value?'removeClassName':'addClassName'
Element[action](this.radios[i].parentNode,"rating_disabled");}}},{ratings:{},register:function(rating)
{Prado.WebUI.TActiveRatingList.ratings[rating.options.ListID]=rating;},setEnabled:function(id,value)
{Prado.WebUI.TActiveRatingList.ratings[id].setEnabled(value);},setRating:function(id,value)
{Prado.WebUI.TActiveRatingList.ratings[id].setRating(value);},setCaption:function(id,value)
{Prado.WebUI.TActiveRatingList.ratings[id].setCaption(value);}});