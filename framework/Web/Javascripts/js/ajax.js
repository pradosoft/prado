
var Ajax={getTransport:function(){return Try.these(function(){return new XMLHttpRequest()},function(){return new ActiveXObject('Msxml2.XMLHTTP')},function(){return new ActiveXObject('Microsoft.XMLHTTP')})||false;},activeRequestCount:0}
Ajax.Responders={responders:[],_each:function(iterator){this.responders._each(iterator);},register:function(responderToAdd){if(!this.include(responderToAdd))
this.responders.push(responderToAdd);},unregister:function(responderToRemove){this.responders=this.responders.without(responderToRemove);},dispatch:function(callback,request,transport,json){this.each(function(responder){if(responder[callback]&&typeof responder[callback]=='function'){try{responder[callback].apply(responder,[request,transport,json]);}catch(e){}}});}};Object.extend(Ajax.Responders,Enumerable);Ajax.Responders.register({onCreate:function(){Ajax.activeRequestCount++;},onComplete:function(){Ajax.activeRequestCount--;}});Ajax.Base=function(){};Ajax.Base.prototype={setOptions:function(options){this.options={method:'post',asynchronous:true,contentType:'application/x-www-form-urlencoded',parameters:''}
Object.extend(this.options,options||{});},responseIsSuccess:function(){return this.transport.status==undefined||this.transport.status==0||(this.transport.status>=200&&this.transport.status<300);},responseIsFailure:function(){return!this.responseIsSuccess();}}
Ajax.Request=Class.create();Ajax.Request.Events=['Uninitialized','Loading','Loaded','Interactive','Complete'];Ajax.Request.prototype=Object.extend(new Ajax.Base(),{initialize:function(url,options){this.transport=Ajax.getTransport();this.setOptions(options);this.request(url);},request:function(url){var parameters=this.options.parameters||'';if(parameters.length>0)parameters+='&_=';try{this.url=url;if(this.options.method=='get'&&parameters.length>0)
this.url+=(this.url.match(/\?/)?'&':'?')+parameters;Ajax.Responders.dispatch('onCreate',this,this.transport);this.transport.open(this.options.method,this.url,this.options.asynchronous);if(this.options.asynchronous){this.transport.onreadystatechange=this.onStateChange.bind(this);setTimeout((function(){this.respondToReadyState(1)}).bind(this),10);}
this.setRequestHeaders();var body=this.options.postBody?this.options.postBody:parameters;this.transport.send(this.options.method=='post'?body:null);}catch(e){this.dispatchException(e);}},setRequestHeaders:function(){var requestHeaders=['X-Requested-With','XMLHttpRequest','X-Prototype-Version',Prototype.Version,'Accept','text/javascript, text/html, application/xml, text/xml'];if(this.options.method=='post'){requestHeaders.push('Content-type',this.options.contentType);if(this.transport.overrideMimeType)
requestHeaders.push('Connection','close');}
if(this.options.requestHeaders)
requestHeaders.push.apply(requestHeaders,this.options.requestHeaders);for(var i=0;i<requestHeaders.length;i+=2)
this.transport.setRequestHeader(requestHeaders[i],requestHeaders[i+1]);},onStateChange:function(){var readyState=this.transport.readyState;if(readyState!=1)
this.respondToReadyState(this.transport.readyState);},header:function(name){try{return this.transport.getResponseHeader(name);}catch(e){}},evalJSON:function(){try{return eval('('+this.header('X-JSON')+')');}catch(e){}},evalResponse:function(){try{return eval(this.transport.responseText);}catch(e){this.dispatchException(e);}},respondToReadyState:function(readyState){var event=Ajax.Request.Events[readyState];var transport=this.transport,json=this.evalJSON();if(event=='Complete'){try{(this.options['on'+this.transport.status]||this.options['on'+(this.responseIsSuccess()?'Success':'Failure')]||Prototype.emptyFunction)(transport,json);}catch(e){this.dispatchException(e);}
if((this.header('Content-type')||'').match(/^text\/javascript/i))
this.evalResponse();}
try{(this.options['on'+event]||Prototype.emptyFunction)(transport,json);Ajax.Responders.dispatch('on'+event,this,transport,json);}catch(e){this.dispatchException(e);}
if(event=='Complete')
this.transport.onreadystatechange=Prototype.emptyFunction;},dispatchException:function(exception){(this.options.onException||Prototype.emptyFunction)(this,exception);Ajax.Responders.dispatch('onException',this,exception);}});Ajax.Updater=Class.create();Object.extend(Object.extend(Ajax.Updater.prototype,Ajax.Request.prototype),{initialize:function(container,url,options){this.containers={success:container.success?$(container.success):$(container),failure:container.failure?$(container.failure):(container.success?null:$(container))}
this.transport=Ajax.getTransport();this.setOptions(options);var onComplete=this.options.onComplete||Prototype.emptyFunction;this.options.onComplete=(function(transport,object){this.updateContent();onComplete(transport,object);}).bind(this);this.request(url);},updateContent:function(){var receiver=this.responseIsSuccess()?this.containers.success:this.containers.failure;var response=this.transport.responseText;if(!this.options.evalScripts)
response=response.stripScripts();if(receiver){if(this.options.insertion){new this.options.insertion(receiver,response);}else{Element.update(receiver,response);}}
if(this.responseIsSuccess()){if(this.onComplete)
setTimeout(this.onComplete.bind(this),10);}}});Ajax.PeriodicalUpdater=Class.create();Ajax.PeriodicalUpdater.prototype=Object.extend(new Ajax.Base(),{initialize:function(container,url,options){this.setOptions(options);this.onComplete=this.options.onComplete;this.frequency=(this.options.frequency||2);this.decay=(this.options.decay||1);this.updater={};this.container=container;this.url=url;this.start();},start:function(){this.options.onComplete=this.updateComplete.bind(this);this.onTimerEvent();},stop:function(){this.updater.onComplete=undefined;clearTimeout(this.timer);(this.onComplete||Prototype.emptyFunction).apply(this,arguments);},updateComplete:function(request){if(this.options.decay){this.decay=(request.responseText==this.lastText?this.decay*this.options.decay:1);this.lastText=request.responseText;}
this.timer=setTimeout(this.onTimerEvent.bind(this),this.decay*this.frequency*1000);},onTimerEvent:function(){this.updater=new Ajax.Updater(this.container,this.url,this.options);}});Object.extend(Ajax.Request.prototype,{respondToReadyState:function(readyState)
{var event=Ajax.Request.Events[readyState];var transport=this.transport,json=this.getHeaderData(Prado.CallbackRequest.DATA_HEADER);if(event=='Complete')
{try
{Prado.CallbackRequest.updatePageState(this,transport);Ajax.Responders.dispatch('on'+transport.status,this,transport,json);Prado.CallbackRequest.dispatchActions(transport,this.getHeaderData(Prado.CallbackRequest.ACTION_HEADER));(this.options['on'+this.transport.status]||this.options['on'+(this.responseIsSuccess()?'Success':'Failure')]||Prototype.emptyFunction)(transport,json);}catch(e){this.dispatchException(e);}
if((this.header('Content-type')||'').match(/^text\/javascript/i))
this.evalResponse();}
try{(this.options['on'+event]||Prototype.emptyFunction)(transport,json);Ajax.Responders.dispatch('on'+event,this,transport,json);}catch(e){this.dispatchException(e);}
if(event=='Complete')
this.transport.onreadystatechange=Prototype.emptyFunction;},getHeaderData:function(name)
{try
{var json=this.header(name);return eval('('+json+')');}
catch(e)
{if(typeof(json)=="string")
return Prado.CallbackRequest.decode(json);}}});Prado.CallbackRequest=Class.create();Object.extend(Prado.CallbackRequest,{FIELD_CALLBACK_TARGET:'PRADO_CALLBACK_TARGET',FIELD_CALLBACK_PARAMETER:'PRADO_CALLBACK_PARAMETER',FIELD_CALLBACK_PAGESTATE:'PRADO_PAGESTATE',FIELD_POSTBACK_TARGET:'PRADO_POSTBACK_TARGET',FIELD_POSTBACK_PARAMETER:'PRADO_POSTBACK_PARAMETER',PostDataLoaders:[],DATA_HEADER:'X-PRADO-DATA',ACTION_HEADER:'X-PRADO-ACTIONS',ERROR_HEADER:'X-PRADO-ERROR',PAGESTATE_HEADER:'X-PRADO-PAGESTATE',requestInProgress:null,addPostLoaders:function(ids)
{this.PostDataLoaders=this.PostDataLoaders.concat(ids);},dispatchActions:function(transport,actions)
{if(actions&&actions.length>0)
actions.each(this.__run.bind(this,transport));},__run:function(transport,command)
{for(var method in command)
{try
{method.toFunction().apply(this,command[method].concat(transport));}
catch(e)
{if(typeof(Logger)!="undefined")
Prado.CallbackRequest.Exception.onException(null,e);}}},Exception:{"on500":function(request,transport,data)
{var e=request.getHeaderData(Prado.CallbackRequest.ERROR_HEADER);Logger.error("Callback Server Error "+e.code,this.formatException(e));},'on200':function(request,transport,data)
{if(transport.status<500)
{var msg='HTTP '+transport.status+" with response : \n";msg+=transport.responseText+"\n";msg+="Data : \n"+inspect(data)+"\n";msg+="Actions : \n";data=request.getHeaderData(Prado.CallbackRequest.ACTION_HEADER);if(data&&data.length>0)
{data.each(function(action)
{msg+=inspect(action)+"\n";});}
Logger.warn(msg);}},onException:function(request,e)
{msg="";$H(e).each(function(item)
{msg+=item.key+": "+item.value+"\n";})
Logger.error('Uncaught Callback Client Exception:',msg);},formatException:function(e)
{var msg=e.type+" with message \""+e.message+"\"";msg+=" in "+e.file+"("+e.line+")\n";msg+="Stack trace:\n";var trace=e.trace;for(var i=0;i<trace.length;i++)
{msg+="  #"+i+" "+trace[i].file;msg+="("+trace[i].line+"): ";msg+=trace[i]["class"]+"->"+trace[i]["function"]+"()"+"\n";}
msg+=e.version+" "+e.time+"\n";return msg;}},encode:function(data)
{return Prado.JSON.stringify(data);},decode:function(data)
{if(typeof(data)=="string"&&data.trim().length>0)
return Prado.JSON.parse(data);else
return null;},dispatchPriorityRequest:function(callback)
{this.abortRequestInProgress();callback.request=new Ajax.Request(callback.url,callback.options);callback.timeout=setTimeout(function()
{Prado.CallbackRequest.abortRequestInProgress();},callback.options.RequestTimeOut);this.requestInProgress=callback;},dispatchNormalRequest:function(callback)
{new Ajax.Request(callback.url,callback.options);},abortRequestInProgress:function()
{inProgress=Prado.CallbackRequest.requestInProgress;if(inProgress)
{inProgress.request.transport.abort();clearTimeout(inProgress.timeout);Prado.CallbackRequest.requestInProgress=null;return true;}
return false;},updatePageState:function(request,transport)
{pagestate=$(this.FIELD_CALLBACK_PAGESTATE);if(request.options.EnablePageStateUpdate&&request.options.HasPriority&&pagestate)
{data=request.header(this.PAGESTATE_HEADER);if(typeof(data)=="string"&&data.length>0)
pagestate.value=data;else
{if(typeof(Logger)!="undefined")
Logger.debug("Bad page state:"+data);}}}})
Ajax.Responders.register({onComplete:function(request)
{if(request.options.HasPriority)
Prado.CallbackRequest.abortRequestInProgress();}});Event.OnLoad(function()
{if(typeof Logger!="undefined")
Ajax.Responders.register(Prado.CallbackRequest.Exception);});Prado.CallbackRequest.prototype={url:window.location.href,options:{},id:null,request:null,initialize:function(id,options)
{this.id=id;this.options={RequestTimeOut:30000,EnablePageStateUpdate:true,HasPriority:true,CausesValidation:true,ValidationGroup:null,PostInputs:true}
Object.extend(this.options,options||{});Object.extend(this.options,{postBody:this._getPostData(),parameters:''});if(this.options.CausesValidation&&typeof(Prado.Validation)!="undefined")
{var form=this.options.Form||Prado.Validation.getForm();if(Prado.Validation.validate(form,this.options.ValidationGroup,this)==false)
return;}
if(this.options.HasPriority)
Prado.CallbackRequest.dispatchPriorityRequest(this);else
Prado.CallbackRequest.dispatchNormalRequest(this);},_getPostData:function()
{var data={};var callback=Prado.CallbackRequest;if(this.options.PostInputs!=false)
{callback.PostDataLoaders.each(function(name)
{$A(document.getElementsByName(name)).each(function(element)
{var value=$F(element);if(typeof(value)!="undefined")
data[name]=value;})})}
if(typeof(this.options.params)!="undefined")
data[callback.FIELD_CALLBACK_PARAMETER]=callback.encode(this.options.params);var pageState=$F(callback.FIELD_CALLBACK_PAGESTATE);if(typeof(pageState)!="undefined")
data[callback.FIELD_CALLBACK_PAGESTATE]=pageState;data[callback.FIELD_CALLBACK_TARGET]=this.id;if(this.options.EventTarget)
data[callback.FIELD_POSTBACK_TARGET]=this.options.EventTarget;if(this.options.EventParameter)
data[callback.FIELD_POSTBACK_PARAMETER]=this.options.EventParameter;return $H(data).toQueryString();}}
Prado.Callback=function(UniqueID,parameter,onSuccess,options)
{var callback={'params':parameter||'','onSuccess':onSuccess||Prototype.emptyFunction};Object.extend(callback,options||{});new Prado.CallbackRequest(UniqueID,callback);return false;}
Array.prototype.______array='______array';Prado.JSON={org:'http://www.JSON.org',copyright:'(c)2005 JSON.org',license:'http://www.crockford.com/JSON/license.html',stringify:function(arg){var c,i,l,s='',v;switch(typeof arg){case'object':if(arg){if(arg.______array=='______array'){for(i=0;i<arg.length;++i){v=this.stringify(arg[i]);if(s){s+=',';}
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
return value();}};var Autocompleter={}
Autocompleter.Base=function(){};Autocompleter.Base.prototype={baseInitialize:function(element,update,options){this.element=$(element);this.update=$(update);this.hasFocus=false;this.changed=false;this.active=false;this.index=0;this.entryCount=0;if(this.setOptions)
this.setOptions(options);else
this.options=options||{};this.options.paramName=this.options.paramName||this.element.name;this.options.tokens=this.options.tokens||[];this.options.frequency=this.options.frequency||0.4;this.options.minChars=this.options.minChars||1;this.options.onShow=this.options.onShow||function(element,update){if(!update.style.position||update.style.position=='absolute'){update.style.position='absolute';Position.clone(element,update,{setHeight:false,offsetTop:element.offsetHeight});}
Effect.Appear(update,{duration:0.15});};this.options.onHide=this.options.onHide||function(element,update){new Effect.Fade(update,{duration:0.15})};if(typeof(this.options.tokens)=='string')
this.options.tokens=new Array(this.options.tokens);this.observer=null;this.element.setAttribute('autocomplete','off');Element.hide(this.update);Event.observe(this.element,"blur",this.onBlur.bindAsEventListener(this));Event.observe(this.element,"keypress",this.onKeyPress.bindAsEventListener(this));},show:function(){if(Element.getStyle(this.update,'display')=='none')this.options.onShow(this.element,this.update);if(!this.iefix&&(navigator.appVersion.indexOf('MSIE')>0)&&(navigator.userAgent.indexOf('Opera')<0)&&(Element.getStyle(this.update,'position')=='absolute')){new Insertion.After(this.update,'<iframe id="'+this.update.id+'_iefix" '+'style="display:none;position:absolute;filter:progid:DXImageTransform.Microsoft.Alpha(opacity=0);" '+'src="javascript:false;" frameborder="0" scrolling="no"></iframe>');this.iefix=$(this.update.id+'_iefix');}
if(this.iefix)setTimeout(this.fixIEOverlapping.bind(this),50);},fixIEOverlapping:function(){Position.clone(this.update,this.iefix);this.iefix.style.zIndex=1;this.update.style.zIndex=2;Element.show(this.iefix);},hide:function(){this.stopIndicator();if(Element.getStyle(this.update,'display')!='none')this.options.onHide(this.element,this.update);if(this.iefix)Element.hide(this.iefix);},startIndicator:function(){if(this.options.indicator)Element.show(this.options.indicator);},stopIndicator:function(){if(this.options.indicator)Element.hide(this.options.indicator);},onKeyPress:function(event){if(this.active)
switch(event.keyCode){case Event.KEY_TAB:case Event.KEY_RETURN:this.selectEntry();Event.stop(event);case Event.KEY_ESC:this.hide();this.active=false;Event.stop(event);return;case Event.KEY_LEFT:case Event.KEY_RIGHT:return;case Event.KEY_UP:this.markPrevious();this.render();if(navigator.appVersion.indexOf('AppleWebKit')>0)Event.stop(event);return;case Event.KEY_DOWN:this.markNext();this.render();if(navigator.appVersion.indexOf('AppleWebKit')>0)Event.stop(event);return;}
else
if(event.keyCode==Event.KEY_TAB||event.keyCode==Event.KEY_RETURN||(navigator.appVersion.indexOf('AppleWebKit')>0&&event.keyCode==0))return;this.changed=true;this.hasFocus=true;if(this.observer)clearTimeout(this.observer);this.observer=setTimeout(this.onObserverEvent.bind(this),this.options.frequency*1000);},activate:function(){this.changed=false;this.hasFocus=true;this.getUpdatedChoices();},onHover:function(event){var element=Event.findElement(event,'LI');if(this.index!=element.autocompleteIndex)
{this.index=element.autocompleteIndex;this.render();}
Event.stop(event);},onClick:function(event){var element=Event.findElement(event,'LI');this.index=element.autocompleteIndex;this.selectEntry();this.hide();},onBlur:function(event){setTimeout(this.hide.bind(this),250);this.hasFocus=false;this.active=false;},render:function(){if(this.entryCount>0){for(var i=0;i<this.entryCount;i++)
this.index==i?Element.addClassName(this.getEntry(i),"selected"):Element.removeClassName(this.getEntry(i),"selected");if(this.hasFocus){this.show();this.active=true;}}else{this.active=false;this.hide();}},markPrevious:function(){if(this.index>0)this.index--
else this.index=this.entryCount-1;},markNext:function(){if(this.index<this.entryCount-1)this.index++
else this.index=0;},getEntry:function(index){return this.update.firstChild.childNodes[index];},getCurrentEntry:function(){return this.getEntry(this.index);},selectEntry:function(){this.active=false;this.updateElement(this.getCurrentEntry());},updateElement:function(selectedElement){if(this.options.updateElement){this.options.updateElement(selectedElement);return;}
var value='';if(this.options.select){var nodes=document.getElementsByClassName(this.options.select,selectedElement)||[];if(nodes.length>0)value=Element.collectTextNodes(nodes[0],this.options.select);}else
value=Element.collectTextNodesIgnoreClass(selectedElement,'informal');var lastTokenPos=this.findLastToken();if(lastTokenPos!=-1){var newValue=this.element.value.substr(0,lastTokenPos+1);var whitespace=this.element.value.substr(lastTokenPos+1).match(/^\s+/);if(whitespace)
newValue+=whitespace[0];this.element.value=newValue+value;}else{this.element.value=value;}
this.element.focus();if(this.options.afterUpdateElement)
this.options.afterUpdateElement(this.element,selectedElement);},updateChoices:function(choices){if(!this.changed&&this.hasFocus){this.update.innerHTML=choices;Element.cleanWhitespace(this.update);Element.cleanWhitespace(this.update.firstChild);if(this.update.firstChild&&this.update.firstChild.childNodes){this.entryCount=this.update.firstChild.childNodes.length;for(var i=0;i<this.entryCount;i++){var entry=this.getEntry(i);entry.autocompleteIndex=i;this.addObservers(entry);}}else{this.entryCount=0;}
this.stopIndicator();this.index=0;this.render();}},addObservers:function(element){Event.observe(element,"mouseover",this.onHover.bindAsEventListener(this));Event.observe(element,"click",this.onClick.bindAsEventListener(this));},onObserverEvent:function(){this.changed=false;if(this.getToken().length>=this.options.minChars){this.startIndicator();this.getUpdatedChoices();}else{this.active=false;this.hide();}},getToken:function(){var tokenPos=this.findLastToken();if(tokenPos!=-1)
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
Ajax.InPlaceEditor=Class.create();Ajax.InPlaceEditor.defaultHighlightColor="#FFFF99";Ajax.InPlaceEditor.prototype={initialize:function(element,url,options){this.url=url;this.element=$(element);this.options=Object.extend({okButton:true,okText:"ok",cancelLink:true,cancelText:"cancel",savingText:"Saving...",clickToEditText:"Click to edit",okText:"ok",rows:1,onComplete:function(transport,element){new Effect.Highlight(element,{startcolor:this.options.highlightcolor});},onFailure:function(transport){alert("Error communicating with the server: "+transport.responseText.stripTags());},callback:function(form){return Form.serialize(form);},handleLineBreaks:true,loadingText:'Loading...',savingClassName:'inplaceeditor-saving',loadingClassName:'inplaceeditor-loading',formClassName:'inplaceeditor-form',highlightcolor:Ajax.InPlaceEditor.defaultHighlightColor,highlightendcolor:"#FFFFFF",externalControl:null,submitOnBlur:false,ajaxOptions:{},evalScripts:false},options||{});if(!this.options.formId&&this.element.id){this.options.formId=this.element.id+"-inplaceeditor";if($(this.options.formId)){this.options.formId=null;}}
if(this.options.externalControl){this.options.externalControl=$(this.options.externalControl);}
this.originalBackground=Element.getStyle(this.element,'background-color');if(!this.originalBackground){this.originalBackground="transparent";}
this.element.title=this.options.clickToEditText;this.onclickListener=this.enterEditMode.bindAsEventListener(this);this.mouseoverListener=this.enterHover.bindAsEventListener(this);this.mouseoutListener=this.leaveHover.bindAsEventListener(this);Event.observe(this.element,'click',this.onclickListener);Event.observe(this.element,'mouseover',this.mouseoverListener);Event.observe(this.element,'mouseout',this.mouseoutListener);if(this.options.externalControl){Event.observe(this.options.externalControl,'click',this.onclickListener);Event.observe(this.options.externalControl,'mouseover',this.mouseoverListener);Event.observe(this.options.externalControl,'mouseout',this.mouseoutListener);}},enterEditMode:function(evt){if(this.saving)return;if(this.editing)return;this.editing=true;this.onEnterEditMode();if(this.options.externalControl){Element.hide(this.options.externalControl);}
Element.hide(this.element);this.createForm();this.element.parentNode.insertBefore(this.form,this.element);Field.scrollFreeActivate(this.editField);if(evt){Event.stop(evt);}
return false;},createForm:function(){this.form=document.createElement("form");this.form.id=this.options.formId;Element.addClassName(this.form,this.options.formClassName)
this.form.onsubmit=this.onSubmit.bind(this);this.createEditField();if(this.options.textarea){var br=document.createElement("br");this.form.appendChild(br);}
if(this.options.okButton){okButton=document.createElement("input");okButton.type="submit";okButton.value=this.options.okText;okButton.className='editor_ok_button';this.form.appendChild(okButton);}
if(this.options.cancelLink){cancelLink=document.createElement("a");cancelLink.href="#";cancelLink.appendChild(document.createTextNode(this.options.cancelText));cancelLink.onclick=this.onclickCancel.bind(this);cancelLink.className='editor_cancel';this.form.appendChild(cancelLink);}},hasHTMLLineBreaks:function(string){if(!this.options.handleLineBreaks)return false;return string.match(/<br/i)||string.match(/<p>/i);},convertHTMLLineBreaks:function(string){return string.replace(/<br>/gi,"\n").replace(/<br\/>/gi,"\n").replace(/<\/p>/gi,"\n").replace(/<p>/gi,"");},createEditField:function(){var text;if(this.options.loadTextURL){text=this.options.loadingText;}else{text=this.getText();}
var obj=this;if(this.options.rows==1&&!this.hasHTMLLineBreaks(text)){this.options.textarea=false;var textField=document.createElement("input");textField.obj=this;textField.type="text";textField.name="value";textField.value=text;textField.style.backgroundColor=this.options.highlightcolor;textField.className='editor_field';var size=this.options.size||this.options.cols||0;if(size!=0)textField.size=size;if(this.options.submitOnBlur)
textField.onblur=this.onSubmit.bind(this);this.editField=textField;}else{this.options.textarea=true;var textArea=document.createElement("textarea");textArea.obj=this;textArea.name="value";textArea.value=this.convertHTMLLineBreaks(text);textArea.rows=this.options.rows;textArea.cols=this.options.cols||40;textArea.className='editor_field';if(this.options.submitOnBlur)
textArea.onblur=this.onSubmit.bind(this);this.editField=textArea;}
if(this.options.loadTextURL){this.loadExternalText();}
this.form.appendChild(this.editField);},getText:function(){return this.element.innerHTML;},loadExternalText:function(){Element.addClassName(this.form,this.options.loadingClassName);this.editField.disabled=true;new Ajax.Request(this.options.loadTextURL,Object.extend({asynchronous:true,onComplete:this.onLoadedExternalText.bind(this)},this.options.ajaxOptions));},onLoadedExternalText:function(transport){Element.removeClassName(this.form,this.options.loadingClassName);this.editField.disabled=false;this.editField.value=transport.responseText.stripTags();},onclickCancel:function(){this.onComplete();this.leaveEditMode();return false;},onFailure:function(transport){this.options.onFailure(transport);if(this.oldInnerHTML){this.element.innerHTML=this.oldInnerHTML;this.oldInnerHTML=null;}
return false;},onSubmit:function(){var form=this.form;var value=this.editField.value;this.onLoading();if(this.options.evalScripts){new Ajax.Request(this.url,Object.extend({parameters:this.options.callback(form,value),onComplete:this.onComplete.bind(this),onFailure:this.onFailure.bind(this),asynchronous:true,evalScripts:true},this.options.ajaxOptions));}else{new Ajax.Updater({success:this.element,failure:null},this.url,Object.extend({parameters:this.options.callback(form,value),onComplete:this.onComplete.bind(this),onFailure:this.onFailure.bind(this)},this.options.ajaxOptions));}
if(arguments.length>1){Event.stop(arguments[0]);}
return false;},onLoading:function(){this.saving=true;this.removeForm();this.leaveHover();this.showSaving();},showSaving:function(){this.oldInnerHTML=this.element.innerHTML;this.element.innerHTML=this.options.savingText;Element.addClassName(this.element,this.options.savingClassName);this.element.style.backgroundColor=this.originalBackground;Element.show(this.element);},removeForm:function(){if(this.form){if(this.form.parentNode)Element.remove(this.form);this.form=null;}},enterHover:function(){if(this.saving)return;this.element.style.backgroundColor=this.options.highlightcolor;if(this.effect){this.effect.cancel();}
Element.addClassName(this.element,this.options.hoverClassName)},leaveHover:function(){if(this.options.backgroundColor){this.element.style.backgroundColor=this.oldBackground;}
Element.removeClassName(this.element,this.options.hoverClassName)
if(this.saving)return;this.effect=new Effect.Highlight(this.element,{startcolor:this.options.highlightcolor,endcolor:this.options.highlightendcolor,restorecolor:this.originalBackground});},leaveEditMode:function(){Element.removeClassName(this.element,this.options.savingClassName);this.removeForm();this.leaveHover();this.element.style.backgroundColor=this.originalBackground;Element.show(this.element);if(this.options.externalControl){Element.show(this.options.externalControl);}
this.editing=false;this.saving=false;this.oldInnerHTML=null;this.onLeaveEditMode();},onComplete:function(transport){this.leaveEditMode();this.options.onComplete.bind(this)(transport,this.element);},onEnterEditMode:function(){},onLeaveEditMode:function(){},dispose:function(){if(this.oldInnerHTML){this.element.innerHTML=this.oldInnerHTML;}
this.leaveEditMode();Event.stopObserving(this.element,'click',this.onclickListener);Event.stopObserving(this.element,'mouseover',this.mouseoverListener);Event.stopObserving(this.element,'mouseout',this.mouseoutListener);if(this.options.externalControl){Event.stopObserving(this.options.externalControl,'click',this.onclickListener);Event.stopObserving(this.options.externalControl,'mouseover',this.mouseoverListener);Event.stopObserving(this.options.externalControl,'mouseout',this.mouseoutListener);}}};Ajax.InPlaceCollectionEditor=Class.create();Object.extend(Ajax.InPlaceCollectionEditor.prototype,Ajax.InPlaceEditor.prototype);Object.extend(Ajax.InPlaceCollectionEditor.prototype,{createEditField:function(){if(!this.cached_selectTag){var selectTag=document.createElement("select");var collection=this.options.collection||[];var optionTag;collection.each(function(e,i){optionTag=document.createElement("option");optionTag.value=(e instanceof Array)?e[0]:e;if(this.options.value==optionTag.value)optionTag.selected=true;optionTag.appendChild(document.createTextNode((e instanceof Array)?e[1]:e));selectTag.appendChild(optionTag);}.bind(this));this.cached_selectTag=selectTag;}
this.editField=this.cached_selectTag;if(this.options.loadTextURL)this.loadExternalText();this.form.appendChild(this.editField);this.options.callback=function(form,value){return"value="+encodeURIComponent(value);}}});Form.Element.DelayedObserver=Class.create();Form.Element.DelayedObserver.prototype={initialize:function(element,delay,callback){this.delay=delay||0.5;this.element=$(element);this.callback=callback;this.timer=null;this.lastValue=$F(this.element);Event.observe(this.element,'keyup',this.delayedListener.bindAsEventListener(this));},delayedListener:function(event){if(this.lastValue==$F(this.element))return;if(this.timer)clearTimeout(this.timer);this.timer=setTimeout(this.onTimerEvent.bind(this),this.delay*1000);this.lastValue=$F(this.element);},onTimerEvent:function(){this.timer=null;this.callback(this.element,$F(this.element));}};Prado.WebUI.CallbackControl=Class.extend(Prado.WebUI.PostBackControl,{onPostBack:function(event,options)
{new Prado.CallbackRequest(options.EventTarget,options);Event.stop(event);}});Prado.WebUI.TActiveButton=Class.extend(Prado.WebUI.CallbackControl);Prado.WebUI.TAutoComplete=Class.extend(Autocompleter.Base,{initialize:function(options)
{this.options=options;this.baseInitialize(options.ID,options.ResultPanel,options);Object.extend(this.options,{onSuccess:this.onComplete.bind(this)});},getUpdatedChoices:function()
{Prado.Callback(this.options.EventTarget,this.getToken(),null,this.options);},onComplete:function(request,boundary)
{result=Prado.Element.extractContent(request.responseText,boundary);if(typeof(result)=="string"&&result.length>0)
this.updateChoices(result);}});Prado.WebUI.TActiveTextBox=Class.extend(Prado.WebUI.TTextBox,{onInit:function(options)
{if(options['TextMode']!='MultiLine')
Event.observe(this.element,"keydown",this.handleReturnKey.bind(this));Event.observe(this.element,"change",this.doCallback.bindEvent(this,options));},doCallback:function(event,options)
{new Prado.CallbackRequest(options.EventTarget,options);Event.stop(event);}});