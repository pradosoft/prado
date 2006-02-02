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
Prado.Validation.SetActiveGroup=function(_34,_35){
Prado.Validation.ActiveTarget=_34;
Prado.Validation.CurrentTargetGroup=_35;
};
Prado.Validation.AddGroup=function(_36,_37){
_36.active=false;
_36.target=$(_36.target);
_36.validators=_37;
Prado.Validation.groups.push(_36);
Event.observe(_36.target,"click",Prado.Validation.UpdateActiveGroup);
};
Prado.Validation.UpdateActiveGroup=function(ev){
var _39=Prado.Validation.groups;
for(var i=0;i<_39.length;i++){
_39[i].active=(isdef(ev)&&_39[i].target==Event.element(ev));
}
Prado.Validation.IsGroupValidation=isdef(ev);
};
Prado.Validation.IsValid=function(_40){
var _41=true;
var _42=Prado.Validation.validators;
for(var i=0;i<_42.length;i++){
_42[i].enabled=!_42[i].control||undef(_42[i].control.form)||_42[i].control.form==_40;
_42[i].visible=Prado.Validation.IsGroupValidation?_42[i].inActiveGroup():true;
if(Prado.Validation.HasTargetGroup){
if(_42[i].group!=Prado.Validation.CurrentTargetGroup){
_42[i].enabled=false;
}
}
_41&=_42[i].validate();
}
Prado.Validation.ShowSummary(_40);
Prado.Validation.UpdateActiveGroup();
return _41;
};
Prado.Validation.prototype={initialize:function(_43,_44){
this.evaluateIsValid=_43;
this.attr=undef(_44)?[]:_44;
this.message=$(_44.id);
this.control=$(_44.controltovalidate);
this.enabled=isdef(_44.enabled)?_44.enabled:true;
this.visible=isdef(_44.visible)?_44.visible:true;
this.group=isdef(_44.validationgroup)?_44.validationgroup:null;
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
var _45=this.attr.controlcssclass;
if(this.control&&isString(_45)&&_45.length>0){
Element.condClassName(this.control,_45,!this.isValid);
}
Prado.Validation.ShowSummary();
var _46=this.attr.focusonerror;
var _47=Prado.Validation.HasTargetGroup;
var _48=this.group==Prado.Validation.CurrentTargetGroup;
if(_46&&(!_47||(_47&&_48))){
Prado.Element.focus(this.attr.focuselementid);
}
},setValid:function(_49){
this.isValid=_49;
this.update();
},observe:function(){
if(undef(this.observing)){
if(this.control&&this.control.form){
Event.observe(this.control,"change",this.validate.bind(this));
}
this.observing=true;
}
},convert:function(_50,_51){
if(undef(_51)){
_51=Form.Element.getValue(this.control);
}
switch(_50){
case "Integer":
return Prado.Validation.Util.toInteger(_51);
case "Double":
case "Float":
return Prado.Validation.Util.toDouble(_51,this.attr.decimalchar);
case "Currency":
return Prado.Validation.Util.toCurrency(_51,this.attr.groupchar,this.attr.digits,this.attr.decimalchar);
case "Date":
return Prado.Validation.Util.toDate(_51,this.attr.dateformat);
}
return _51.toString();
},inActiveGroup:function(){
var _52=Prado.Validation.groups;
for(var i=0;i<_52.length;i++){
if(_52[i].active&&_52[i].validators.contains(this.attr.id)){
return true;
}
}
return false;
}};
Prado.Validation.Summary=Class.create();
Prado.Validation.Summary.prototype={initialize:function(_53){
this.attr=_53;
this.div=$(_53.id);
this.visible=false;
this.enabled=false;
this.group=isdef(_53.validationgroup)?_53.validationgroup:null;
Prado.Validation.summaries.push(this);
},show:function(_54){
var _55=_54||this.attr.refresh=="1";
var _56=this.getMessages();
if(_56.length<=0||!this.visible||!this.enabled){
if(_55){
Element.hide(this.div);
}
return;
}
if(Prado.Validation.HasTargetGroup){
if(Prado.Validation.CurrentTargetGroup!=this.group){
if(_55){
Element.hide(this.div);
}
return;
}
}
if(this.attr.showsummary!="False"&&_55){
this.div.style.display="block";
while(this.div.childNodes.length>0){
this.div.removeChild(this.div.lastChild);
}
new Insertion.Bottom(this.div,this.formatSummary(_56));
}
if(_54){
window.scrollTo(this.div.offsetLeft-20,this.div.offsetTop-20);
}
var _57=this;
if(_54&&this.attr.showmessagebox=="True"&&_55){
setTimeout(function(){
alert(_57.formatMessageBox(_56));
},20);
}
},getMessages:function(){
var _58=Prado.Validation.validators;
var _59=[];
for(var i=0;i<_58.length;i++){
if(_58[i].isValid==false&&isString(_58[i].attr.errormessage)&&_58[i].attr.errormessage.length>0){
_59.push(_58[i].attr.errormessage);
}
}
return _59;
},formats:function(_60){
switch(_60){
case "List":
return {header:"<br />",first:"",pre:"",post:"<br />",last:""};
case "SingleParagraph":
return {header:" ",first:"",pre:"",post:" ",last:"<br />"};
case "BulletList":
default:
return {header:"",first:"<ul>",pre:"<li>",post:"</li>",last:"</ul>"};
}
},formatSummary:function(_61){
var _62=this.formats(this.attr.displaymode);
var _63=isdef(this.attr.headertext)?this.attr.headertext+_62.header:"";
_63+=_62.first;
for(var i=0;i<_61.length;i++){
_63+=(_61[i].length>0)?_62.pre+_61[i]+_62.post:"";
}
_63+=_62.last;
return _63;
},formatMessageBox:function(_64){
var _65=isdef(this.attr.headertext)?this.attr.headertext+"\n":"";
for(var i=0;i<_64.length;i++){
switch(this.attr.displaymode){
case "List":
_65+=_64[i]+"\n";
break;
case "BulletList":
default:
_65+="  - "+_64[i]+"\n";
break;
case "SingleParagraph":
_65+=_64[i]+" ";
break;
}
}
return _65;
},inActiveGroup:function(){
var _66=Prado.Validation.groups;
for(var i=0;i<_66.length;i++){
if(_66[i].active&&_66[i].id==this.attr.group){
return true;
}
}
return false;
}};
Prado.Validation.ShowSummary=function(_67){
var _68=Prado.Validation.summaries;
for(var i=0;i<_68.length;i++){
if(isdef(_67)){
if(Prado.Validation.IsGroupValidation){
_68[i].visible=_68[i].inActiveGroup();
}else{
_68[i].visible=undef(_68[i].attr.group);
}
_68[i].enabled=$(_68[i].attr.form)==_67;
}
_68[i].show(_67);
}
};
Prado.Validation.OnSubmit=function(ev){
if(typeof tinyMCE!="undefined"){
tinyMCE.triggerSave();
}
if(!Prado.Validation.ActiveTarget){
return true;
}
var _69=Prado.Validation.IsValid(Event.element(ev)||ev);
if(Event.element(ev)&&!_69){
Event.stop(ev);
}
Prado.Validation.ActiveTarget=null;
return _69;
};
Prado.Validation.OnLoad=function(){
Event.observe(Prado.Validation.forms,"submit",Prado.Validation.OnSubmit);
};
Prado.Validation.ValidateValidatorGroup=function(_70){
var _71=Prado.Validation.groups;
var _72=null;
for(var i=0;i<_71.length;i++){
if(_71[i].id==_70){
_72=_71[i];
Prado.Validation.groups[i].active=true;
Prado.Validation.CurrentTargetGroup=null;
Prado.Validation.IsGroupValidation=true;
}else{
Prado.Validation.groups[i].active=false;
}
}
if(_72){
return Prado.Validation.IsValid(_72.target.form);
}
return true;
};
Prado.Validation.ValidateValidationGroup=function(_73){
var _74=Prado.Validation.TargetGroups;
for(var id in _74){
if(_74[id]==_73){
var _75=$(id);
Prado.Validation.ActiveTarget=_75;
Prado.Validation.CurrentTargetGroup=_73;
Prado.Validation.IsGroupValidation=false;
return Prado.Validation.IsValid(_75.form);
}
}
return true;
};
Prado.Validation.ValidateNonGroup=function(_76){
if(Prado.Validation){
var _77=$(_76);
_77=_77||document.forms[0];
Prado.Validation.ActiveTarget=_77;
Prado.Validation.CurrentTargetGroup=null;
Prado.Validation.IsGroupValidation=false;
return Prado.Validation.IsValid(_77);
}
return true;
};
Event.OnLoad(Prado.Validation.OnLoad);
Prado.Validation.TRequiredFieldValidator=function(){
var _78=this.control.getAttribute("type");
if(_78=="file"){
return true;
}else{
var a=Prado.Validation.trim($F(this.control));
var b=Prado.Validation.trim(this.attr.initialvalue);
return (a!=b);
}
};
Prado.Validation.TRegularExpressionValidator=function(){
var _79=Prado.Validation.trim($F(this.control));
if(_79==""){
return true;
}
var rx=new RegExp(this.attr.validationexpression);
var _81=rx.exec(_79);
return (_81!=null&&_79==_81[0]);
};
Prado.Validation.TEmailAddressValidator=Prado.Validation.TRegularExpressionValidator;
Prado.Validation.TCustomValidator=function(){
var _82=isNull(this.control)?null:$F(this.control);
var _83=this.attr.clientvalidationfunction;
eval("var validate = "+_83);
return validate&&isFunction(validate)?validate(this,_82):true;
};
Prado.Validation.TRangeValidator=function(){
var _84=Prado.Validation.trim($F(this.control));
if(_84==""){
return true;
}
var _85=this.attr.minimumvalue;
var _86=this.attr.maximumvalue;
if(undef(_85)&&undef(_86)){
return true;
}
if(_85==""){
_85=0;
}
if(_86==""){
_86=0;
}
var _87=this.attr.type;
if(undef(_87)){
return (parseFloat(_84)>=parseFloat(_85))&&(parseFloat(_84)<=parseFloat(_86));
}
var min=this.convert(_87,_85);
var max=this.convert(_87,_86);
_84=this.convert(_87,_84);
return _84>=min&&_84<=max;
};
Prado.Validation.TCompareValidator=function(){
var _89=Prado.Validation.trim($F(this.control));
if(_89.length==0){
return true;
}
var _90;
var _91=$(this.attr.controlhookup);
if(_91){
_90=Prado.Validation.trim($F(_91));
}else{
_90=isString(this.attr.valuetocompare)?this.attr.valuetocompare:"";
}
var _92=Prado.Validation.TCompareValidator.compare;
var _93=_92.bind(this)(_89,_90);
if(_91){
var _94=this.attr.controlcssclass;
if(isString(_94)&&_94.length>0){
Element.condClassName(_91,_94,!_93);
}
if(undef(this.observingComparee)){
Event.observe(_91,"change",this.validate.bind(this));
this.observingComparee=true;
}
}
return _93;
};
Prado.Validation.TCompareValidator.compare=function(_95,_96){
var op1,op2;
if((op1=this.convert(this.attr.type,_95))==null){
return false;
}
if(this.attr.operator=="DataTypeCheck"){
return true;
}
if((op2=this.convert(this.attr.type,_96))==null){
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
var _98=document.getElementsByName(this.attr.selector);
if(_98.length<=0){
return true;
}
var _99=new Array();
if(isString(this.attr.required)&&this.attr.required.length>0){
_99=this.attr.required.split(/,\s* /);
}
var _100=true;
var _101=Prado.Validation.TRequiredListValidator;
switch(_98[0].type){
case "radio":
case "checkbox":
_100=_101.IsValidRadioList(_98,min,max,_99);
break;
case "select-multiple":
_100=_101.IsValidSelectMultipleList(_98,min,max,_99);
break;
}
var _102=this.attr.elementcssclass;
if(isString(_102)&&_102.length>0){
map(_98,function(_103){
condClass(_103,_102,!_100);
});
}
if(undef(this.observingRequiredList)){
Event.observe(_98,"change",this.validate.bind(this));
this.observingRequiredList=true;
}
return _100;
};
Prado.Validation.TRequiredListValidator.IsValidRadioList=function(_104,min,max,_105){
var _106=0;
var _107=new Array();
for(var i=0;i<_104.length;i++){
if(_104[i].checked){
_106++;
_107.push(_104[i].value);
}
}
return Prado.Validation.TRequiredListValidator.IsValidList(_106,_107,min,max,_105);
};
Prado.Validation.TRequiredListValidator.IsValidSelectMultipleList=function(_108,min,max,_109){
var _110=0;
var _111=new Array();
for(var i=0;i<_108.length;i++){
var _112=_108[i];
for(var j=0;j<_112.options.length;j++){
if(_112.options[j].selected){
_110++;
_111.push(_112.options[j].value);
}
}
}
return Prado.Validation.TRequiredListValidator.IsValidList(_110,_111,min,max,_109);
};
Prado.Validation.TRequiredListValidator.IsValidList=function(_114,_115,min,max,_116){
var _117=true;
if(_116.length>0){
if(_115.length<_116.length){
return false;
}
for(var k=0;k<_116.length;k++){
_117=_117&&_115.contains(_116[k]);
}
}
return _117&&_114>=min&&_114<=max;
};

