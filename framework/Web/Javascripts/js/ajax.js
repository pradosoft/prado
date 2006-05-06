
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
{Logger.info("using json")
return Prado.CallbackRequest.decode(json);}}}});Prado.CallbackRequest=Class.create();Object.extend(Prado.CallbackRequest,{FIELD_CALLBACK_TARGET:'PRADO_CALLBACK_TARGET',FIELD_CALLBACK_PARAMETER:'PRADO_CALLBACK_PARAMETER',FIELD_CALLBACK_PAGESTATE:'PRADO_PAGESTATE',PostDataLoaders:[],DATA_HEADER:'X-PRADO-DATA',ACTION_HEADER:'X-PRADO-ACTIONS',ERROR_HEADER:'X-PRADO-ERROR',PAGESTATE_HEADER:'X-PRADO-PAGESTATE',requestInProgress:null,dispatchActions:function(transport,actions)
{if(actions&&actions.length>0)
actions.each(this.__run.bind(this,transport));},__run:function(transport,command)
{for(var method in command)
{if(command[method][0])
{var id=command[method][0];if($(id)||id.indexOf("[]")>-1)
method.toFunction().apply(this,command[method].concat(transport));else if(typeof(Logger)!="undefined")
{Logger.error("Error in executing callback response:","Unable to find HTML element with ID '"+id+"' before executing "+method+"().");}}}},Exception:{"on500":function(request,transport,data)
{var e=request.getHeaderData(Prado.CallbackRequest.ERROR_HEADER);Logger.error("Callback Server Error "+e.code,this.formatException(e));},'on200':function(request,transport,data)
{if(transport.status<500)
{var msg='HTTP '+transport.status+" with response : \n";msg+=transport.responseText+"\n";msg+="Data : \n"+inspect(data)+"\n";msg+="Actions : \n";data=request.getHeaderData(Prado.CallbackRequest.ACTION_HEADER);if(data&&data.length>0)
{data.each(function(action)
{msg+=inspect(action)+"\n";});}
Logger.warn(msg);}},onException:function(request,e)
{msg="";for(var v in e)
{if(typeof(v[e])!="object"&&typeof(v[e])!="function")
msg+=v+":"+e[v]+"\n";}
Logger.error('Uncaught Callback Client Exception:',e);},formatException:function(e)
{var msg=e.type+" with message \""+e.message+"\"";msg+=" in "+e.file+"("+e.line+")\n";msg+="Stack trace:\n";var trace=e.trace;for(var i=0;i<trace.length;i++)
{msg+="  #"+i+" "+trace[i].file;msg+="("+trace[i].line+"): ";msg+=trace[i]["class"]+"->"+trace[i]["function"]+"()"+"\n";}
msg+=e.version+" "+e.time+"\n";return msg;}},encode:function(data)
{return Prado.JSON.stringify(data);},decode:function(data)
{if(typeof(data)=="string"&&data.trim().length>0)
return Prado.JSON.parse(data);else
return null;},dispatchPriorityRequest:function(callback)
{Logger.info("priority request "+callback.id)
this.abortRequestInProgress();callback.request=new Ajax.Request(callback.url,callback.options);callback.timeout=setTimeout(function()
{Logger.warn("priority timeout");Prado.CallbackRequest.abortRequestInProgress();},callback.options.RequestTimeOut);this.requestInProgress=callback;Logger.info("dispatched "+this.requestInProgress)},dispatchNormalRequest:function(callback)
{Logger.info("dispatching normal request");new Ajax.Request(callback.url,callback.options);},abortRequestInProgress:function()
{inProgress=Prado.CallbackRequest.requestInProgress;Logger.info("aborting ... "+inProgress);if(inProgress)
{Logger.warn("aborted "+inProgress.id)
inProgress.request.transport.abort();clearTimeout(inProgress.timeout);Prado.CallbackRequest.requestInProgress=null;return true;}
return false;},updatePageState:function(request,transport)
{pagestate=$(this.FIELD_CALLBACK_PAGESTATE);if(request.options.EnablePageStateUpdate&&request.options.HasPriority&&pagestate)
{data=request.header(this.PAGESTATE_HEADER);if(typeof(data)=="string"&&data.length>0)
{Logger.warn("updating page state");pagestate.value=data;}
else
{Logger.debug("Bad page state:"+data);}}}})
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
data[callback.FIELD_CALLBACK_PAGESTATE]=pageState;data[callback.FIELD_CALLBACK_TARGET]=this.id;return $H(data).toQueryString();}}
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
return value();}};Prado.WebUI.CallbackControl=Class.extend(Prado.WebUI.PostBackControl,{onPostBack:function(event,options)
{new Prado.CallbackRequest(options.EventTarget,options);Event.stop(event);}});Prado.WebUI.TActiveButton=Class.extend(Prado.WebUI.CallbackControl);