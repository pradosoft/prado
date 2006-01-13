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
Prado.Validation.Util.trim=function(_28){
if(undef(_28)){
return "";
}
return _28.replace(/^\s+|\s+$/g,"");
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
if(this.attr.focusonerror){
Prado.Element.focus(this.attr.focuselementid);
}
},setValid:function(_44){
this.isValid=_44;
this.update();
},observe:function(){
if(undef(this.observing)){
if(this.control&&this.control.form){
Event.observe(this.control,"blur",this.validate.bind(this));
}
this.observing=true;
}
},convert:function(_45,_46){
if(undef(_46)){
_46=Form.Element.getValue(this.control);
}
switch(_45){
case "Integer":
return Prado.Validation.Util.toInteger(_46);
case "Double":
case "Float":
return Prado.Validation.Util.toDouble(_46,this.attr.decimalchar);
case "Currency":
return Prado.Validation.Util.toCurrency(_46,this.attr.groupchar,this.attr.digits,this.attr.decimalchar);
case "Date":
return Prado.Validation.Util.toDate(_46,this.attr.dateformat);
}
return _46.toString();
},inActiveGroup:function(){
var _47=Prado.Validation.groups;
for(var i=0;i<_47.length;i++){
if(_47[i].active&&_47[i].validators.contains(this.attr.id)){
return true;
}
}
return false;
}};
Prado.Validation.Summary=Class.create();
Prado.Validation.Summary.prototype={initialize:function(_48){
this.attr=_48;
this.div=$(_48.id);
this.visible=false;
this.enabled=false;
this.group=isdef(_48.validationgroup)?_48.validationgroup:null;
Prado.Validation.summaries.push(this);
},show:function(_49){
var _50=_49||this.attr.refresh=="1";
var _51=this.getMessages();
if(_51.length<=0||!this.visible||!this.enabled){
if(_50){
Element.hide(this.div);
}
return;
}
if(Prado.Validation.HasTargetGroup){
if(Prado.Validation.CurrentTargetGroup!=this.group){
if(_50){
Element.hide(this.div);
}
return;
}
}
if(this.attr.showsummary!="False"&&_50){
this.div.style.display="block";
while(this.div.childNodes.length>0){
this.div.removeChild(this.div.lastChild);
}
new Insertion.Bottom(this.div,this.formatSummary(_51));
}
if(_49){
window.scrollTo(this.div.offsetLeft-20,this.div.offsetTop-20);
}
var _52=this;
if(_49&&this.attr.showmessagebox=="True"&&_50){
setTimeout(function(){
alert(_52.formatMessageBox(_51));
},20);
}
},getMessages:function(){
var _53=Prado.Validation.validators;
var _54=[];
for(var i=0;i<_53.length;i++){
if(_53[i].isValid==false&&isString(_53[i].attr.errormessage)&&_53[i].attr.errormessage.length>0){
_54.push(_53[i].attr.errormessage);
}
}
return _54;
},formats:function(_55){
switch(_55){
case "List":
return {header:"<br />",first:"",pre:"",post:"<br />",last:""};
case "SingleParagraph":
return {header:" ",first:"",pre:"",post:" ",last:"<br />"};
case "BulletList":
default:
return {header:"",first:"<ul>",pre:"<li>",post:"</li>",last:"</ul>"};
}
},formatSummary:function(_56){
var _57=this.formats(this.attr.displaymode);
var _58=isdef(this.attr.headertext)?this.attr.headertext+_57.header:"";
_58+=_57.first;
for(var i=0;i<_56.length;i++){
_58+=(_56[i].length>0)?_57.pre+_56[i]+_57.post:"";
}
_58+=_57.last;
return _58;
},formatMessageBox:function(_59){
var _60=isdef(this.attr.headertext)?this.attr.headertext+"\n":"";
for(var i=0;i<_59.length;i++){
switch(this.attr.displaymode){
case "List":
_60+=_59[i]+"\n";
break;
case "BulletList":
default:
_60+="  - "+_59[i]+"\n";
break;
case "SingleParagraph":
_60+=_59[i]+" ";
break;
}
}
return _60;
},inActiveGroup:function(){
var _61=Prado.Validation.groups;
for(var i=0;i<_61.length;i++){
if(_61[i].active&&_61[i].id==this.attr.group){
return true;
}
}
return false;
}};
Prado.Validation.ShowSummary=function(_62){
var _63=Prado.Validation.summaries;
for(var i=0;i<_63.length;i++){
if(isdef(_62)){
if(Prado.Validation.IsGroupValidation){
_63[i].visible=_63[i].inActiveGroup();
}else{
_63[i].visible=undef(_63[i].attr.group);
}
_63[i].enabled=$(_63[i].attr.form)==_62;
}
_63[i].show(_62);
}
};
Prado.Validation.OnSubmit=function(ev){
if(typeof tinyMCE!="undefined"){
tinyMCE.triggerSave();
}
if(!Prado.Validation.ActiveTarget){
return true;
}
var _64=Prado.Validation.IsValid(Event.element(ev)||ev);
if(Event.element(ev)&&!_64){
Event.stop(ev);
}
Prado.Validation.ActiveTarget=null;
return _64;
};
Prado.Validation.OnLoad=function(){
Event.observe(Prado.Validation.forms,"submit",Prado.Validation.OnSubmit);
};
Prado.Validation.ValidateValidatorGroup=function(_65){
var _66=Prado.Validation.groups;
var _67=null;
for(var i=0;i<_66.length;i++){
if(_66[i].id==_65){
_67=_66[i];
Prado.Validation.groups[i].active=true;
Prado.Validation.CurrentTargetGroup=null;
Prado.Validation.IsGroupValidation=true;
}else{
Prado.Validation.groups[i].active=false;
}
}
if(_67){
return Prado.Validation.IsValid(_67.target.form);
}
return true;
};
Prado.Validation.ValidateValidationGroup=function(_68){
var _69=Prado.Validation.TargetGroups;
for(var id in _69){
if(_69[id]==_68){
var _70=$(id);
Prado.Validation.ActiveTarget=_70;
Prado.Validation.CurrentTargetGroup=_68;
Prado.Validation.IsGroupValidation=false;
return Prado.Validation.IsValid(_70.form);
}
}
return true;
};
Prado.Validation.ValidateNonGroup=function(_71){
if(Prado.Validation){
var _72=$(_71);
_72=_72||document.forms[0];
Prado.Validation.ActiveTarget=_72;
Prado.Validation.CurrentTargetGroup=null;
Prado.Validation.IsGroupValidation=false;
return Prado.Validation.IsValid(_72);
}
return true;
};
Event.OnLoad(Prado.Validation.OnLoad);

Prado.Validation.TRequiredFieldValidator=function(){
var _1=this.control.getAttribute("type");
if(_1=="file"){
return true;
}else{
var _2=Prado.Util.trim;
var a=_2(Form.Element.getValue(this.control));
var b=_2(this.attr.initialvalue);
return (a!=b);
}
};
Prado.Validation.TRegularExpressionValidator=function(){
var _5=Prado.Util.trim;
var _6=_5(Form.Element.getValue(this.control));
if(_6==""){
return true;
}
var rx=new RegExp(this.attr.validationexpression);
var _8=rx.exec(_6);
return (_8!=null&&_6==_8[0]);
};
Prado.Validation.TEmailAddressValidator=Prado.Validation.TRegularExpressionValidator;
Prado.Validation.TCustomValidator=function(){
var _9=isNull(this.control)?null:$F(this.control);
var _10=this.attr.clientvalidationfunction;
eval("var validate = "+_10);
return validate&&isFunction(validate)?validate(this,_9):true;
};
Prado.Validation.TRangeValidator=function(){
var _11=Prado.Util.trim;
var _12=_11(Form.Element.getValue(this.control));
if(_12==""){
return true;
}
var _13=this.attr.minimumvalue;
var _14=this.attr.maximumvalue;
if(undef(_13)&&undef(_14)){
return true;
}
if(_13==""){
_13=0;
}
if(_14==""){
_14=0;
}
var _15=this.attr.type;
if(undef(_15)){
return (parseFloat(_12)>=parseFloat(_13))&&(parseFloat(_12)<=parseFloat(_14));
}
var min=this.convert(_15,_13);
var max=this.convert(_15,_14);
_12=this.convert(_15,_12);
return _12>=min&&_12<=max;
};
Prado.Validation.TCompareValidator=function(){
var _18=Prado.Util.trim;
var _19=_18(Form.Element.getValue(this.control));
if(_19.length==0){
return true;
}
var _20;
var _21=$(this.attr.controlhookup);
if(_21){
_20=_18(Form.Element.getValue(_21));
}else{
_20=isString(this.attr.valuetocompare)?this.attr.valuetocompare:"";
}
var _22=Prado.Validation.TCompareValidator.compare;
var _23=_22.bind(this)(_19,_20);
if(_21){
var _24=this.attr.controlcssclass;
if(isString(_24)&&_24.length>0){
Element.condClassName(_21,_24,!_23);
}
if(undef(this.observingComparee)){
Event.observe(_21,"change",this.validate.bind(this));
this.observingComparee=true;
}
}
return _23;
};
Prado.Validation.TCompareValidator.compare=function(_25,_26){
var op1,op2;
if((op1=this.convert(this.attr.type,_25))==null){
return false;
}
if(this.attr.operator=="DataTypeCheck"){
return true;
}
if((op2=this.convert(this.attr.type,_26))==null){
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
var _28=document.getElementsByName(this.attr.selector);
if(_28.length<=0){
_28=document.getElementsBySelector(this.attr.selector);
}
if(_28.length<=0){
return true;
}
var _29=new Array();
if(isString(this.attr.required)&&this.attr.required.length>0){
_29=this.attr.required.split(/,\s* /);
}
var _30=true;
var _31=Prado.Validation.TRequiredListValidator;
switch(_28[0].type){
case "radio":
case "checkbox":
_30=_31.IsValidRadioList(_28,min,max,_29);
break;
case "select-multiple":
_30=_31.IsValidSelectMultipleList(_28,min,max,_29);
break;
}
var _32=this.attr.elementcssclass;
if(isString(_32)&&_32.length>0){
map(_28,function(_33){
condClass(_33,_32,!_30);
});
}
if(undef(this.observingRequiredList)){
Event.observe(_28,"change",this.validate.bind(this));
this.observingRequiredList=true;
}
return _30;
};
Prado.Validation.TRequiredListValidator.IsValidRadioList=function(_34,min,max,_35){
var _36=0;
var _37=new Array();
for(var i=0;i<_34.length;i++){
if(_34[i].checked){
_36++;
_37.push(_34[i].value);
}
}
return Prado.Validation.TRequiredListValidator.IsValidList(_36,_37,min,max,_35);
};
Prado.Validation.TRequiredListValidator.IsValidSelectMultipleList=function(_39,min,max,_40){
var _41=0;
var _42=new Array();
for(var i=0;i<_39.length;i++){
var _43=_39[i];
for(var j=0;j<_43.options.length;j++){
if(_43.options[j].selected){
_41++;
_42.push(_43.options[j].value);
}
}
}
return Prado.Validation.TRequiredListValidator.IsValidList(_41,_42,min,max,_40);
};
Prado.Validation.TRequiredListValidator.IsValidList=function(_45,_46,min,max,_47){
var _48=true;
if(_47.length>0){
if(_46.length<_47.length){
return false;
}
for(var k=0;k<_47.length;k++){
_48=_48&&_46.contains(_47[k]);
}
}
return _48&&_45>=min&&_45<=max;
};

