
Prado.Validation=Class.create();Object.extend(Prado.Validation,{managers:{},validate:function(formID,groupID,invoker)
{if(this.managers[formID])
{return this.managers[formID].validate(groupID,invoker);}
else
{throw new Error("Form '"+form+"' is not registered with Prado.Validation");}},isValid:function(formID,groupID)
{if(this.managers[formID])
return this.managers[formID].isValid(groupID);return true;},addValidator:function(formID,validator)
{if(this.managers[formID])
this.managers[formID].addValidator(validator);else
throw new Error("A validation manager for form '"+formID+"' needs to be created first.");return this.managers[formID];},addSummary:function(formID,validator)
{if(this.managers[formID])
this.managers[formID].addSummary(validator);else
throw new Error("A validation manager for form '"+formID+"' needs to be created first.");return this.managers[formID];}});Prado.ValidationManager=Class.create();Prado.ValidationManager.prototype={validators:[],summaries:[],groups:[],options:{},initialize:function(options)
{this.options=options;Prado.Validation.managers[options.FormID]=this;},validate:function(group,invoker)
{if(group)
return this._validateGroup(group,invoker);else
return this._validateNonGroup(invoker);},_validateGroup:function(groupID,invoker)
{var valid=true;if(this.groups.include(groupID))
{this.validators.each(function(validator)
{if(validator.group==groupID)
valid=valid&validator.validate(invoker);else
validator.hide();});}
this.updateSummary(groupID,true);return valid;},_validateNonGroup:function(invoker)
{var valid=true;this.validators.each(function(validator)
{if(!validator.group)
valid=valid&validator.validate(invoker);else
validator.hide();});this.updateSummary(null,true);return valid;},isValid:function(group)
{if(group)
return this._isValidGroup(group);else
return this._isValidNonGroup();},_isValidNonGroup:function()
{var valid=true;this.validators.each(function(validator)
{if(!validator.group)
valid=valid&validator.isValid;});return valid;},_isValidGroup:function(groupID)
{var valid=true;if(this.groups.include(groupID))
{this.validators.each(function(validator)
{if(validator.group==groupID)
valid=valid&validator.isValid;});}
return valid;},addValidator:function(validator)
{this.validators.push(validator);if(validator.group&&!this.groups.include(validator.group))
this.groups.push(validator.group);},addSummary:function(summary)
{this.summaries.push(summary);},getValidatorsWithError:function(group)
{var validators=this.validators.findAll(function(validator)
{var notValid=!validator.isValid;var inGroup=group&&validator.group==group;var noGroup=validator.group==null;return notValid&&(inGroup||noGroup);});return validators;},updateSummary:function(group,refresh)
{var validators=this.getValidatorsWithError(group);this.summaries.each(function(summary)
{var inGroup=group&&summary.group==group;var noGroup=!group&&!summary.group;if(inGroup||noGroup)
summary.updateSummary(validators,refresh);else
summary.hideSummary(true);});}};Prado.WebUI.TValidationSummary=Class.create();Prado.WebUI.TValidationSummary.prototype={group:null,options:{},visible:false,messages:null,initialize:function(options)
{this.options=options;this.group=options.ValidationGroup;this.messages=$(options.ID);this.visible=this.messages.style.visibility!="hidden"
this.visible=this.visible&&this.messages.style.display!="none";Prado.Validation.addSummary(options.FormID,this);},updateSummary:function(validators,update)
{if(validators.length<=0)
{if(update||this.options.Refresh!=false)
{return this.hideSummary(validators);}
return;}
var refresh=update||this.visible==false||this.options.Refresh!=false;if(this.options.ShowSummary!=false&&refresh)
{this.updateHTMLMessages(this.getMessages(validators));this.showSummary(validators);}
if(this.options.ScrollToSummary!=false)
window.scrollTo(this.messages.offsetLeft-20,this.messages.offsetTop-20);if(this.options.ShowMessageBox==true&&refresh)
{this.alertMessages(this.getMessages(validators));this.visible=true;}},updateHTMLMessages:function(messages)
{while(this.messages.childNodes.length>0)
this.messages.removeChild(this.messages.lastChild);new Insertion.Bottom(this.messages,this.formatSummary(messages));},alertMessages:function(messages)
{var text=this.formatMessageBox(messages);setTimeout(function(){alert(text);},20);},getMessages:function(validators)
{var messages=[];validators.each(function(validator)
{var message=validator.getErrorMessage();if(typeof(message)=='string'&&message.length>0)
messages.push(message);})
return messages;},hideSummary:function(validators)
{if(typeof(this.options.OnHideSummary)=="function")
{this.messages.style.visibility="visible";this.options.OnHideSummary(this,validators)}
else
{this.messages.style.visibility="hidden";if(this.options.Display=="None"||this.options.Display=="Dynamic")
this.messages.hide();}
this.visible=false;},showSummary:function(validators)
{this.messages.style.visibility="visible";if(typeof(this.options.OnShowSummary)=="function")
this.options.OnShowSummary(this,validators);else
this.messages.show();this.visible=true;},formats:function(type)
{switch(type)
{case"List":return{header:"<br />",first:"",pre:"",post:"<br />",last:""};case"SingleParagraph":return{header:" ",first:"",pre:"",post:" ",last:"<br />"};case"BulletList":default:return{header:"",first:"<ul>",pre:"<li>",post:"</li>",last:"</ul>"};}},formatSummary:function(messages)
{var format=this.formats(this.options.DisplayMode);var output=this.options.HeaderText?this.options.HeaderText+format.header:"";output+=format.first;messages.each(function(message)
{output+=message.length>0?format.pre+message+format.post:"";});output+=format.last;return output;},formatMessageBox:function(messages)
{var output=this.options.HeaderText?this.options.HeaderText+"\n":"";for(var i=0;i<messages.length;i++)
{switch(this.options.DisplayMode)
{case"List":output+=messages[i]+"\n";break;case"BulletList":default:output+="  - "+messages[i]+"\n";break;case"SingleParagraph":output+=messages[i]+" ";break;}}
return output;}};Prado.WebUI.TBaseValidator=Class.create();Prado.WebUI.TBaseValidator.prototype={enabled:true,visible:false,isValid:true,options:{},_isObserving:{},group:null,manager:null,message:null,initialize:function(options)
{this.options=options;this.control=$(options.ControlToValidate);this.message=$(options.ID);this.group=options.ValidationGroup;this.manager=Prado.Validation.addValidator(options.FormID,this);},getErrorMessage:function()
{return this.options.ErrorMessage;},updateControl:function()
{if(this.message)
{if(this.options.Display=="Dynamic")
this.isValid?this.message.hide():this.message.show();this.message.style.visibility=this.isValid?"hidden":"visible";}
if(this.control)
this.updateControlCssClass(this.control,this.isValid);if(this.options.FocusOnError&&!this.isValid)
Prado.Element.focus(this.options.FocusElementID);this.visible=true;},updateControlCssClass:function(control,valid)
{var CssClass=this.options.ControlCssClass;if(typeof(CssClass)=="string"&&CssClass.length>0)
{if(valid)
control.removeClassName(CssClass);else
control.addClassName(CssClass);}},hide:function()
{this.isValid=true;this.updateControl();this.visible=false;},validate:function(invoker)
{if(typeof(this.options.OnValidate)=="function")
this.options.OnValidate(this,invoker);if(this.enabled)
this.isValid=this.evaluateIsValid();if(this.isValid)
{if(typeof(this.options.OnSuccess)=="function")
{this.visible=true;this.message.style.visibility="visible";this.updateControlCssClass(this.control,this.isValid);this.options.OnSuccess(this,invoker);}
else
this.updateControl();}
else
{if(typeof(this.options.OnError)=="function")
{this.visible=true;this.message.style.visibility="visible";this.updateControlCssClass(this.control,this.isValid);this.options.OnError(this,invoker);}
else
this.updateControl();}
this.observeChanges(this.control);return this.isValid;},observeChanges:function(control)
{if(!control)return;var canObserveChanges=this.options.ObserveChanges!=false;var currentlyObserving=this._isObserving[control.id+this.options.ID];if(canObserveChanges&&!currentlyObserving)
{var validator=this;Event.observe(control,'change',function()
{if(validator.visible)
{validator.validate();validator.manager.updateSummary(validator.group);}});this._isObserving[control.id+this.options.ID]=true;}},trim:function(value)
{return typeof(value)=="string"?value.trim():"";},convert:function(dataType,value)
{if(typeof(value)=="undefined")
value=this.getValidationValue();var string=new String(value);switch(dataType)
{case"Integer":return string.toInteger();case"Double":case"Float":return string.toDouble(this.options.DecimalChar);case"Date":if(typeof(value)!="string")
return value;else
{var value=string.toDate(this.options.DateFormat);if(value&&typeof(value.getTime)=="function")
return value.getTime();else
return null;}
case"String":return string.toString();}
return value;},getValidationValue:function(control)
{if(!control)
control=this.control
switch(this.options.ControlType)
{case'TDatePicker':if(control.type=="text")
return this.trim($F(control));else
{this.observeDatePickerChanges();return Prado.WebUI.TDatePicker.getDropDownDate(control).getTime();}
case'THtmlArea':if(typeof tinyMCE!="undefined")
tinyMCE.triggerSave();return this.trim($F(control));default:if(this.isListControlType())
return this.getFirstSelectedListValue();else
return this.trim($F(control));}},observeDatePickerChanges:function()
{if(Prado.Browser().ie)
{var DatePicker=Prado.WebUI.TDatePicker;this.observeChanges(DatePicker.getDayListControl(this.control));this.observeChanges(DatePicker.getMonthListControl(this.control));this.observeChanges(DatePicker.getYearListControl(this.control));}},getSelectedValuesAndChecks:function(elements,initialValue)
{var checked=0;var values=[];var isSelected=this.isCheckBoxType(elements[0])?'checked':'selected';elements.each(function(element)
{if(element[isSelected]&&element.value!=initialValue)
{checked++;values.push(element.value);}});return{'checks':checked,'values':values};},getListElements:function()
{switch(this.options.ControlType)
{case'TCheckBoxList':case'TRadioButtonList':var elements=[];for(var i=0;i<this.options.TotalItems;i++)
{var element=$(this.options.ControlToValidate+"_c"+i);if(this.isCheckBoxType(element))
elements.push(element);}
return elements;case'TListBox':var elements=[];var element=$(this.options.ControlToValidate);if(element&&(type=element.type.toLowerCase()))
{if(type=="select-one"||type=="select-multiple")
elements=$A(element.options);}
return elements;default:return[];}},isCheckBoxType:function(element)
{if(element&&element.type)
{var type=element.type.toLowerCase();return type=="checkbox"||type=="radio";}
return false;},isListControlType:function()
{var list=['TCheckBoxList','TRadioButtonList','TListBox'];return list.include(this.options.ControlType);},getFirstSelectedListValue:function()
{var initial="";if(typeof(this.options.InitialValue)!="undefined")
initial=this.options.InitialValue;var elements=this.getListElements();var selection=this.getSelectedValuesAndChecks(elements,initial);return selection.values.length>0?selection.values[0]:initial;}}
Prado.WebUI.TRequiredFieldValidator=Class.extend(Prado.WebUI.TBaseValidator,{evaluateIsValid:function()
{var inputType=this.control.getAttribute("type");if(inputType=='file')
{return true;}
else
{var a=this.getValidationValue();var b=this.trim(this.options.InitialValue);return(a!=b);}}});Prado.WebUI.TCompareValidator=Class.extend(Prado.WebUI.TBaseValidator,{evaluateIsValid:function()
{var value=this.getValidationValue();if(value.length<=0)
return true;var comparee=$(this.options.ControlToCompare);if(comparee)
var compareTo=this.getValidationValue(comparee);else
var compareTo=this.options.ValueToCompare||"";var isValid=this.compare(value,compareTo);if(comparee)
{this.updateControlCssClass(comparee,isValid);this.observeChanges(comparee);}
return isValid;},compare:function(operand1,operand2)
{var op1,op2;if((op1=this.convert(this.options.DataType,operand1))==null)
return false;if((op2=this.convert(this.options.DataType,operand2))==null)
return true;switch(this.options.Operator)
{case"NotEqual":return(op1!=op2);case"GreaterThan":return(op1>op2);case"GreaterThanEqual":return(op1>=op2);case"LessThan":return(op1<op2);case"LessThanEqual":return(op1<=op2);default:return(op1==op2);}}});Prado.WebUI.TCustomValidator=Class.extend(Prado.WebUI.TBaseValidator,{evaluateIsValid:function()
{var value=this.getValidationValue();var clientFunction=this.options.ClientValidationFunction;if(typeof(clientFunction)=="string"&&clientFunction.length>0)
{validate=clientFunction.toFunction();return validate(this,value);}
return true;}});Prado.WebUI.TRangeValidator=Class.extend(Prado.WebUI.TBaseValidator,{evaluateIsValid:function()
{var value=this.getValidationValue();if(value.length<=0)
return true;if(typeof(this.options.DataType)=="undefined")
this.options.DataType="String";var min=this.convert(this.options.DataType,this.options.MinValue||null);var max=this.convert(this.options.DataType,this.options.MaxValue||null);value=this.convert(this.options.DataType,value);if(value==null)
return false;var valid=true;if(min!=null)
valid=valid&&value>=min;if(max!=null)
valid=valid&&value<=max;return valid;}});Prado.WebUI.TRegularExpressionValidator=Class.extend(Prado.WebUI.TBaseValidator,{evaluateIsValid:function()
{var value=this.getValidationValue();if(value.length<=0)
return true;var rx=new RegExp(this.options.ValidationExpression);var matches=rx.exec(value);return(matches!=null&&value==matches[0]);}});Prado.WebUI.TEmailAddressValidator=Prado.WebUI.TRegularExpressionValidator;Prado.WebUI.TListControlValidator=Class.extend(Prado.WebUI.TBaseValidator,{evaluateIsValid:function()
{var elements=this.getListElements();if(elements&&elements.length<=0)
return true;this.observeListElements(elements);var selection=this.getSelectedValuesAndChecks(elements);return this.isValidList(selection.checks,selection.values);},observeListElements:function(elements)
{if(Prado.Browser().ie&&this.isCheckBoxType(elements[0]))
{var validator=this;elements.each(function(element)
{validator.observeChanges(element);});}},isValidList:function(checked,values)
{var exists=true;var required=this.getRequiredValues();if(required.length>0)
{if(values.length<required.length)
return false;required.each(function(requiredValue)
{exists=exists&&values.include(requiredValue);});}
var min=typeof(this.options.Min)=="undefined"?Number.NEGATIVE_INFINITY:this.options.Min;var max=typeof(this.options.Max)=="undefined"?Number.POSITIVE_INFINITY:this.options.Max;return exists&&checked>=min&&checked<=max;},getRequiredValues:function()
{var required=[];if(this.options.Required&&this.options.Required.length>0)
required=this.options.Required.split(/,\s*/);return required;}});Prado.WebUI.TDataTypeValidator=Class.extend(Prado.WebUI.TBaseValidator,{evaluateIsValid:function()
{var value=this.getValidationValue();if(value.length<=0)
return true;return this.convert(this.options.DataType,value)!=null;}});