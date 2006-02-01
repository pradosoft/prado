Prado.Validation=Class.create();
Prado.Validation.Util=Class.create();
Prado.Validation.Util.toInteger=function(_1){
var _2=/^\s*[-\+]?\d+\s*$/;
if(_1.match(_2)==null){
return null;
}
var _3=parseInt(_1,10);
return (isNaN(_3)?null:_3);
};
Prado.Validation.Util.toDouble=function(_4,_5){
_5=undef(_5)?".":_5;
var _6=new RegExp("^\\s*([-\\+])?(\\d+)?(\\"+_5+"(\\d+))?\\s*$");
var m=_4.match(_6);
if(m==null){
return null;
}
var _8=m[1]+(m[2].length>0?m[2]:"0")+"."+m[4];
var _9=parseFloat(_8);
return (isNaN(_9)?null:_9);
};
Prado.Validation.Util.toCurrency=function(_10,_11,_12,_13){
_11=undef(_11)?",":_11;
_13=undef(_13)?".":_13;
_12=undef(_12)?2:_12;
var exp=new RegExp("^\\s*([-\\+])?(((\\d+)\\"+_11+")*)(\\d+)"+((_12>0)?"(\\"+_13+"(\\d{1,"+_12+"}))?":"")+"\\s*$");
var m=_10.match(exp);
if(m==null){
return null;
}
var _15=m[2]+m[5];
var _16=m[1]+_15.replace(new RegExp("(\\"+_11+")","g"),"")+((_12>0)?"."+m[7]:"");
var num=parseFloat(_16);
return (isNaN(num)?null:num);
};
Prado.Validation.Util.toDate=function(_18,_19){
var y=0;
var m=-1;
var d=0;
var a=_18.split(/\W+/);
var b=_19.match(/%./g);
var i=0,j=0;
var hr=0;
var min=0;
for(i=0;i<a.length;++i){
if(!a[i]){
continue;
}
switch(b[i]){
case "%d":
case "%e":
d=parseInt(a[i],10);
break;
case "%m":
m=parseInt(a[i],10)-1;
break;
case "%Y":
case "%y":
y=parseInt(a[i],10);
(y<100)&&(y+=(y>29)?1900:2000);
break;
case "%H":
case "%I":
case "%k":
case "%l":
hr=parseInt(a[i],10);
break;
case "%P":
case "%p":
if(/pm/i.test(a[i])&&hr<12){
hr+=12;
}
break;
case "%M":
min=parseInt(a[i],10);
break;
}
}
if(y!=0&&m!=-1&&d!=0){
var _27=new Date(y,m,d,hr,min,0);
return (isObject(_27)&&y==_27.getFullYear()&&m==_27.getMonth()&&d==_27.getDate())?_27.valueOf():null;
}
return null;
};
Prado.Validation.trim=function(_28){
if(isString(_28)){
return _28.trim();
}
return "";
};
Prado.Validation.Util.focus=function(_29){
var obj=$(_29);
if(isObject(obj)&&isdef(obj.focus)){
setTimeout(function(){
obj.focus();
},100);
}
return false;
};
Prado.Validation.validators=[];
Prado.Validation.forms=[];
Prado.Validation.summaries=[];
Prado.Validation.groups=[];
Prado.Validation.TargetGroups={};
Prado.Validation.CurrentTargetGroup=null;
Prado.Validation.HasTargetGroup=false;
Prado.Validation.ActiveTarget=null;
Prado.Validation.IsGroupValidation=false;
Prado.Validation.AddForm=function(id){
Prado.Validation.forms.push($(id));
};
Prado.Validation.AddTarget=function(id,_32){
var _33=$(id);
Event.observe(_33,"click",function(){
Prado.Validation.ActiveTarget=_33;
Prado.Validation.CurrentTargetGroup=Prado.Validation.TargetGroups[id];
});
if(_32){
Prado.Validation.TargetGroups[id]=_32;
Prado.Validation.HasTargetGroup=true;
}
};
Prado.Validation.AddGroup=function(_34,_35){
_34.active=false;
_34.target=$(_34.target);
_34.validators=_35;
Prado.Validation.groups.push(_34);
Event.observe(_34.target,"click",Prado.Validation.UpdateActiveGroup);
};
Prado.Validation.UpdateActiveGroup=function(ev){
var _37=Prado.Validation.groups;
for(var i=0;i<_37.length;i++){
_37[i].active=(isdef(ev)&&_37[i].target==Event.element(ev));
}
Prado.Validation.IsGroupValidation=isdef(ev);
};
Prado.Validation.IsValid=function(_38){
var _39=true;
var _40=Prado.Validation.validators;
for(var i=0;i<_40.length;i++){
_40[i].enabled=!_40[i].control||undef(_40[i].control.form)||_40[i].control.form==_38;
_40[i].visible=Prado.Validation.IsGroupValidation?_40[i].inActiveGroup():true;
if(Prado.Validation.HasTargetGroup){
if(_40[i].group!=Prado.Validation.CurrentTargetGroup){
_40[i].enabled=false;
}
}
_39&=_40[i].validate();
}
Prado.Validation.ShowSummary(_38);
Prado.Validation.UpdateActiveGroup();
return _39;
};
Prado.Validation.prototype={initialize:function(_41,_42){
this.evaluateIsValid=_41;
this.attr=undef(_42)?[]:_42;
this.message=$(_42.id);
this.control=$(_42.controltovalidate);
this.enabled=isdef(_42.enabled)?_42.enabled:true;
this.visible=isdef(_42.visible)?_42.visible:true;
this.group=isdef(_42.validationgroup)?_42.validationgroup:null;
this.isValid=true;
Prado.Validation.validators.push(this);
if(this.evaluateIsValid){
this.evaluateIsValid.bind(this);
}
},validate:function(){
if(this.visible&&this.enabled&&this.evaluateIsValid){
this.isValid=this.evaluateIsValid();
}else{
this.isValid=true;
}
this.observe();
this.update();
return this.isValid;
},update:function(){
if(this.attr.display=="Dynamic"){
this.isValid?Element.hide(this.message):Element.show(this.message);
}
if(this.message){
this.message.style.visibility=this.isValid?"hidden":"visible";
}
var _43=this.attr.controlcssclass;
if(this.control&&isString(_43)&&_43.length>0){
Element.condClassName(this.control,_43,!this.isValid);
}
Prado.Validation.ShowSummary();
var _44=this.attr.focusonerror;
var _45=Prado.Validation.HasTargetGroup;
var _46=this.group==Prado.Validation.CurrentTargetGroup;
if(_44&&(!_45||(_45&&_46))){
Prado.Element.focus(this.attr.focuselementid);
}
},setValid:function(_47){
this.isValid=_47;
this.update();
},observe:function(){
if(undef(this.observing)){
if(this.control&&this.control.form){
Event.observe(this.control,"change",this.validate.bind(this));
}
this.observing=true;
}
},convert:function(_48,_49){
if(undef(_49)){
_49=Form.Element.getValue(this.control);
}
switch(_48){
case "Integer":
return Prado.Validation.Util.toInteger(_49);
case "Double":
case "Float":
return Prado.Validation.Util.toDouble(_49,this.attr.decimalchar);
case "Currency":
return Prado.Validation.Util.toCurrency(_49,this.attr.groupchar,this.attr.digits,this.attr.decimalchar);
case "Date":
return Prado.Validation.Util.toDate(_49,this.attr.dateformat);
}
return _49.toString();
},inActiveGroup:function(){
var _50=Prado.Validation.groups;
for(var i=0;i<_50.length;i++){
if(_50[i].active&&_50[i].validators.contains(this.attr.id)){
return true;
}
}
return false;
}};
Prado.Validation.Summary=Class.create();
Prado.Validation.Summary.prototype={initialize:function(_51){
this.attr=_51;
this.div=$(_51.id);
this.visible=false;
this.enabled=false;
this.group=isdef(_51.validationgroup)?_51.validationgroup:null;
Prado.Validation.summaries.push(this);
},show:function(_52){
var _53=_52||this.attr.refresh=="1";
var _54=this.getMessages();
if(_54.length<=0||!this.visible||!this.enabled){
if(_53){
Element.hide(this.div);
}
return;
}
if(Prado.Validation.HasTargetGroup){
if(Prado.Validation.CurrentTargetGroup!=this.group){
if(_53){
Element.hide(this.div);
}
return;
}
}
if(this.attr.showsummary!="False"&&_53){
this.div.style.display="block";
while(this.div.childNodes.length>0){
this.div.removeChild(this.div.lastChild);
}
new Insertion.Bottom(this.div,this.formatSummary(_54));
}
if(_52){
window.scrollTo(this.div.offsetLeft-20,this.div.offsetTop-20);
}
var _55=this;
if(_52&&this.attr.showmessagebox=="True"&&_53){
setTimeout(function(){
alert(_55.formatMessageBox(_54));
},20);
}
},getMessages:function(){
var _56=Prado.Validation.validators;
var _57=[];
for(var i=0;i<_56.length;i++){
if(_56[i].isValid==false&&isString(_56[i].attr.errormessage)&&_56[i].attr.errormessage.length>0){
_57.push(_56[i].attr.errormessage);
}
}
return _57;
},formats:function(_58){
switch(_58){
case "List":
return {header:"<br />",first:"",pre:"",post:"<br />",last:""};
case "SingleParagraph":
return {header:" ",first:"",pre:"",post:" ",last:"<br />"};
case "BulletList":
default:
return {header:"",first:"<ul>",pre:"<li>",post:"</li>",last:"</ul>"};
}
},formatSummary:function(_59){
var _60=this.formats(this.attr.displaymode);
var _61=isdef(this.attr.headertext)?this.attr.headertext+_60.header:"";
_61+=_60.first;
for(var i=0;i<_59.length;i++){
_61+=(_59[i].length>0)?_60.pre+_59[i]+_60.post:"";
}
_61+=_60.last;
return _61;
},formatMessageBox:function(_62){
var _63=isdef(this.attr.headertext)?this.attr.headertext+"\n":"";
for(var i=0;i<_62.length;i++){
switch(this.attr.displaymode){
case "List":
_63+=_62[i]+"\n";
break;
case "BulletList":
default:
_63+="  - "+_62[i]+"\n";
break;
case "SingleParagraph":
_63+=_62[i]+" ";
break;
}
}
return _63;
},inActiveGroup:function(){
var _64=Prado.Validation.groups;
for(var i=0;i<_64.length;i++){
if(_64[i].active&&_64[i].id==this.attr.group){
return true;
}
}
return false;
}};
Prado.Validation.ShowSummary=function(_65){
var _66=Prado.Validation.summaries;
for(var i=0;i<_66.length;i++){
if(isdef(_65)){
if(Prado.Validation.IsGroupValidation){
_66[i].visible=_66[i].inActiveGroup();
}else{
_66[i].visible=undef(_66[i].attr.group);
}
_66[i].enabled=$(_66[i].attr.form)==_65;
}
_66[i].show(_65);
}
};
Prado.Validation.OnSubmit=function(ev){
if(typeof tinyMCE!="undefined"){
tinyMCE.triggerSave();
}
if(!Prado.Validation.ActiveTarget){
return true;
}
var _67=Prado.Validation.IsValid(Event.element(ev)||ev);
if(Event.element(ev)&&!_67){
Event.stop(ev);
}
Prado.Validation.ActiveTarget=null;
return _67;
};
Prado.Validation.OnLoad=function(){
Event.observe(Prado.Validation.forms,"submit",Prado.Validation.OnSubmit);
};
Prado.Validation.ValidateValidatorGroup=function(_68){
var _69=Prado.Validation.groups;
var _70=null;
for(var i=0;i<_69.length;i++){
if(_69[i].id==_68){
_70=_69[i];
Prado.Validation.groups[i].active=true;
Prado.Validation.CurrentTargetGroup=null;
Prado.Validation.IsGroupValidation=true;
}else{
Prado.Validation.groups[i].active=false;
}
}
if(_70){
return Prado.Validation.IsValid(_70.target.form);
}
return true;
};
Prado.Validation.ValidateValidationGroup=function(_71){
var _72=Prado.Validation.TargetGroups;
for(var id in _72){
if(_72[id]==_71){
var _73=$(id);
Prado.Validation.ActiveTarget=_73;
Prado.Validation.CurrentTargetGroup=_71;
Prado.Validation.IsGroupValidation=false;
return Prado.Validation.IsValid(_73.form);
}
}
return true;
};
Prado.Validation.ValidateNonGroup=function(_74){
if(Prado.Validation){
var _75=$(_74);
_75=_75||document.forms[0];
Prado.Validation.ActiveTarget=_75;
Prado.Validation.CurrentTargetGroup=null;
Prado.Validation.IsGroupValidation=false;
return Prado.Validation.IsValid(_75);
}
return true;
};
Event.OnLoad(Prado.Validation.OnLoad);

Prado.Validation.TRequiredFieldValidator=function(){
var _1=this.control.getAttribute("type");
if(_1=="file"){
return true;
}else{
var a=Prado.Validation.trim($F(this.control));
var b=Prado.Validation.trim(this.attr.initialvalue);
return (a!=b);
}
};
Prado.Validation.TRegularExpressionValidator=function(){
var _4=Prado.Validation.trim($F(this.control));
if(_4==""){
return true;
}
var rx=new RegExp(this.attr.validationexpression);
var _6=rx.exec(_4);
return (_6!=null&&_4==_6[0]);
};
Prado.Validation.TEmailAddressValidator=Prado.Validation.TRegularExpressionValidator;
Prado.Validation.TCustomValidator=function(){
var _7=isNull(this.control)?null:$F(this.control);
var _8=this.attr.clientvalidationfunction;
eval("var validate = "+_8);
return validate&&isFunction(validate)?validate(this,_7):true;
};
Prado.Validation.TRangeValidator=function(){
var _9=Prado.Validation.trim($F(this.control));
if(_9==""){
return true;
}
var _10=this.attr.minimumvalue;
var _11=this.attr.maximumvalue;
if(undef(_10)&&undef(_11)){
return true;
}
if(_10==""){
_10=0;
}
if(_11==""){
_11=0;
}
var _12=this.attr.type;
if(undef(_12)){
return (parseFloat(_9)>=parseFloat(_10))&&(parseFloat(_9)<=parseFloat(_11));
}
var min=this.convert(_12,_10);
var max=this.convert(_12,_11);
_9=this.convert(_12,_9);
return _9>=min&&_9<=max;
};
Prado.Validation.TCompareValidator=function(){
var _15=Prado.Validation.trim($F(this.control));
if(_15.length==0){
return true;
}
var _16;
var _17=$(this.attr.controlhookup);
if(_17){
_16=Prado.Validation.trim($F(_17));
}else{
_16=isString(this.attr.valuetocompare)?this.attr.valuetocompare:"";
}
var _18=Prado.Validation.TCompareValidator.compare;
var _19=_18.bind(this)(_15,_16);
if(_17){
var _20=this.attr.controlcssclass;
if(isString(_20)&&_20.length>0){
Element.condClassName(_17,_20,!_19);
}
if(undef(this.observingComparee)){
Event.observe(_17,"change",this.validate.bind(this));
this.observingComparee=true;
}
}
return _19;
};
Prado.Validation.TCompareValidator.compare=function(_21,_22){
var op1,op2;
if((op1=this.convert(this.attr.type,_21))==null){
return false;
}
if(this.attr.operator=="DataTypeCheck"){
return true;
}
if((op2=this.convert(this.attr.type,_22))==null){
return true;
}
switch(this.attr.operator){
case "NotEqual":
return (op1!=op2);
case "GreaterThan":
return (op1>op2);
case "GreaterThanEqual":
return (op1>=op2);
case "LessThan":
return (op1<op2);
case "LessThanEqual":
return (op1<=op2);
default:
return (op1==op2);
}
};
Prado.Validation.TRequiredListValidator=function(){
var min=undef(this.attr.min)?Number.NEGATIVE_INFINITY:parseInt(this.attr.min);
var max=undef(this.attr.max)?Number.POSITIVE_INFINITY:parseInt(this.attr.max);
var _24=document.getElementsByName(this.attr.selector);
if(_24.length<=0){
return true;
}
var _25=new Array();
if(isString(this.attr.required)&&this.attr.required.length>0){
_25=this.attr.required.split(/,\s* /);
}
var _26=true;
var _27=Prado.Validation.TRequiredListValidator;
switch(_24[0].type){
case "radio":
case "checkbox":
_26=_27.IsValidRadioList(_24,min,max,_25);
break;
case "select-multiple":
_26=_27.IsValidSelectMultipleList(_24,min,max,_25);
break;
}
var _28=this.attr.elementcssclass;
if(isString(_28)&&_28.length>0){
map(_24,function(_29){
condClass(_29,_28,!_26);
});
}
if(undef(this.observingRequiredList)){
Event.observe(_24,"change",this.validate.bind(this));
this.observingRequiredList=true;
}
return _26;
};
Prado.Validation.TRequiredListValidator.IsValidRadioList=function(_30,min,max,_31){
var _32=0;
var _33=new Array();
for(var i=0;i<_30.length;i++){
if(_30[i].checked){
_32++;
_33.push(_30[i].value);
}
}
return Prado.Validation.TRequiredListValidator.IsValidList(_32,_33,min,max,_31);
};
Prado.Validation.TRequiredListValidator.IsValidSelectMultipleList=function(_35,min,max,_36){
var _37=0;
var _38=new Array();
for(var i=0;i<_35.length;i++){
var _39=_35[i];
for(var j=0;j<_39.options.length;j++){
if(_39.options[j].selected){
_37++;
_38.push(_39.options[j].value);
}
}
}
return Prado.Validation.TRequiredListValidator.IsValidList(_37,_38,min,max,_36);
};
Prado.Validation.TRequiredListValidator.IsValidList=function(_41,_42,min,max,_43){
var _44=true;
if(_43.length>0){
if(_42.length<_43.length){
return false;
}
for(var k=0;k<_43.length;k++){
_44=_44&&_42.contains(_43[k]);
}
}
return _44&&_41>=min&&_41<=max;
};

