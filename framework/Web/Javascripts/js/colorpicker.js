if(typeof (Rico)=="undefined"){
Rico={};
}
Rico.Color=Class.create();
Rico.Color.prototype={initialize:function(_1,_2,_3){
this.rgb={r:_1,g:_2,b:_3};
},setRed:function(r){
this.rgb.r=r;
},setGreen:function(g){
this.rgb.g=g;
},setBlue:function(b){
this.rgb.b=b;
},setHue:function(h){
var _8=this.asHSB();
_8.h=h;
this.rgb=Rico.Color.HSBtoRGB(_8.h,_8.s,_8.b);
},setSaturation:function(s){
var hsb=this.asHSB();
hsb.s=s;
this.rgb=Rico.Color.HSBtoRGB(hsb.h,hsb.s,hsb.b);
},setBrightness:function(b){
var hsb=this.asHSB();
hsb.b=b;
this.rgb=Rico.Color.HSBtoRGB(hsb.h,hsb.s,hsb.b);
},darken:function(_11){
var hsb=this.asHSB();
this.rgb=Rico.Color.HSBtoRGB(hsb.h,hsb.s,Math.max(hsb.b-_11,0));
},brighten:function(_12){
var hsb=this.asHSB();
this.rgb=Rico.Color.HSBtoRGB(hsb.h,hsb.s,Math.min(hsb.b+_12,1));
},blend:function(_13){
this.rgb.r=Math.floor((this.rgb.r+_13.rgb.r)/2);
this.rgb.g=Math.floor((this.rgb.g+_13.rgb.g)/2);
this.rgb.b=Math.floor((this.rgb.b+_13.rgb.b)/2);
},isBright:function(){
var hsb=this.asHSB();
return this.asHSB().b>0.5;
},isDark:function(){
return !this.isBright();
},asRGB:function(){
return "rgb("+this.rgb.r+","+this.rgb.g+","+this.rgb.b+")";
},asHex:function(){
return "#"+this.rgb.r.toColorPart()+this.rgb.g.toColorPart()+this.rgb.b.toColorPart();
},asHSB:function(){
return Rico.Color.RGBtoHSB(this.rgb.r,this.rgb.g,this.rgb.b);
},toString:function(){
return this.asHex();
}};
Rico.Color.createFromHex=function(_14){
if(_14.indexOf("#")==0){
_14=_14.substring(1);
}
var red="ff",green="ff",blue="ff";
if(_14.length>4){
red=_14.substring(0,2);
green=_14.substring(2,4);
blue=_14.substring(4,6);
}else{
if(_14.length>0&_14.length<4){
var r=_14.substring(0,1);
var g=_14.substring(1,2);
var b=_14.substring(2);
red=r+r;
green=g+g;
blue=b+b;
}
}
return new Rico.Color(parseInt(red,16),parseInt(green,16),parseInt(blue,16));
};
Rico.Color.createColorFromBackground=function(_16){
var _17=Element.getStyle($(_16),"background-color");
if(_17=="transparent"&&_16.parent){
return Rico.Color.createColorFromBackground(_16.parent);
}
if(_17==null){
return new Rico.Color(255,255,255);
}
if(_17.indexOf("rgb(")==0){
var _18=_17.substring(4,_17.length-1);
var _19=_18.split(",");
return new Rico.Color(parseInt(_19[0]),parseInt(_19[1]),parseInt(_19[2]));
}else{
if(_17.indexOf("#")==0){
return Rico.Color.createFromHex(_17);
}else{
return new Rico.Color(255,255,255);
}
}
};
Rico.Color.HSBtoRGB=function(hue,_21,_22){
var red=0;
var _23=0;
var _24=0;
if(_21==0){
red=parseInt(_22*255+0.5);
_23=red;
_24=red;
}else{
var h=(hue-Math.floor(hue))*6;
var f=h-Math.floor(h);
var p=_22*(1-_21);
var q=_22*(1-_21*f);
var t=_22*(1-(_21*(1-f)));
switch(parseInt(h)){
case 0:
red=(_22*255+0.5);
_23=(t*255+0.5);
_24=(p*255+0.5);
break;
case 1:
red=(q*255+0.5);
_23=(_22*255+0.5);
_24=(p*255+0.5);
break;
case 2:
red=(p*255+0.5);
_23=(_22*255+0.5);
_24=(t*255+0.5);
break;
case 3:
red=(p*255+0.5);
_23=(q*255+0.5);
_24=(_22*255+0.5);
break;
case 4:
red=(t*255+0.5);
_23=(p*255+0.5);
_24=(_22*255+0.5);
break;
case 5:
red=(_22*255+0.5);
_23=(p*255+0.5);
_24=(q*255+0.5);
break;
}
}
return {r:parseInt(red),g:parseInt(_23),b:parseInt(_24)};
};
Rico.Color.RGBtoHSB=function(r,g,b){
var hue;
var _29;
var _30;
var _31=(r>g)?r:g;
if(b>_31){
_31=b;
}
var _32=(r<g)?r:g;
if(b<_32){
_32=b;
}
_30=_31/255;
if(_31!=0){
saturation=(_31-_32)/_31;
}else{
saturation=0;
}
if(saturation==0){
hue=0;
}else{
var _33=(_31-r)/(_31-_32);
var _34=(_31-g)/(_31-_32);
var _35=(_31-b)/(_31-_32);
if(r==_31){
hue=_35-_34;
}else{
if(g==_31){
hue=2+_33-_35;
}else{
hue=4+_34-_33;
}
}
hue=hue/6;
if(hue<0){
hue=hue+1;
}
}
return {h:hue,s:saturation,b:_30};
};
Prado.WebUI.TColorPicker=Class.create();
Object.extend(Prado.WebUI.TColorPicker,{palettes:{Small:[["fff","fcc","fc9","ff9","ffc","9f9","9ff","cff","ccf","fcf"],["ccc","f66","f96","ff6","ff3","6f9","3ff","6ff","99f","f9f"],["c0c0c0","f00","f90","fc6","ff0","3f3","6cc","3cf","66c","c6c"],["999","c00","f60","fc3","fc0","3c0","0cc","36f","63f","c3c"],["666","900","c60","c93","990","090","399","33f","60c","939"],["333","600","930","963","660","060","366","009","339","636"],["000","300","630","633","330","030","033","006","309","303"]],Tiny:[["ffffff","00ff00","008000","0000ff"],["c0c0c0","ffff00","ff00ff","000080"],["808080","ff0000","800080","000000"]]},UIImages:{"button.gif":"button.gif","background.png":"background.png"}});
Object.extend(Prado.WebUI.TColorPicker.prototype,{initialize:function(_36){
var _37={Palette:"Small",ClassName:"TColorPicker",Mode:"Basic",OKButtonText:"OK",CancelButtonText:"Cancel",ShowColorPicker:true};
this.element=null;
this.showing=false;
_36=Object.extend(_37,_36);
this.options=_36;
this.input=$(_36["ID"]);
this.button=$(_36["ID"]+"_button");
this._buttonOnClick=this.buttonOnClick.bind(this);
if(_36["ShowColorPicker"]){
Event.observe(this.button,"click",this._buttonOnClick);
}
Event.observe(this.input,"change",this.updatePicker.bind(this));
},updatePicker:function(e){
var _39=Rico.Color.createFromHex(this.input.value);
this.button.style.backgroundColor=_39.toString();
},buttonOnClick:function(_40){
var _41=this.options["Mode"];
if(this.element==null){
var _42=_41=="Basic"?"getBasicPickerContainer":"getFullPickerContainer";
this.element=this[_42](this.options["ID"],this.options["Palette"]);
document.body.appendChild(this.element);
this.element.style.display="none";
if(Prado.Browser().ie){
this.iePopUp=document.createElement("iframe");
this.iePopUp.src="";
this.iePopUp.style.position="absolute";
this.iePopUp.scrolling="no";
this.iePopUp.frameBorder="0";
this.input.parentNode.appendChild(this.iePopUp);
}
if(_41=="Full"){
this.initializeFullPicker();
}
}
this.show();
},show:function(_43){
if(!this.showing){
var pos=Position.cumulativeOffset(this.input);
pos[1]+=this.input.offsetHeight;
this.element.style.top=(pos[1]-1)+"px";
this.element.style.left=pos[0]+"px";
this.element.style.display="block";
this.ieHack(_43);
this._documentClickEvent=this.hideOnClick.bindEvent(this,_43);
this._documentKeyDownEvent=this.keyPressed.bindEvent(this,_43);
Event.observe(document.body,"click",this._documentClickEvent);
Event.observe(document,"keydown",this._documentKeyDownEvent);
this.showing=true;
if(_43=="Full"){
var _45=Rico.Color.createFromHex(this.input.value);
this.inputs.oldColor.style.backgroundColor=_45.asHex();
this.setColor(_45,true);
}
}
},hide:function(_46){
if(this.showing){
if(this.iePopUp){
this.iePopUp.style.display="none";
}
this.element.style.display="none";
this.showing=false;
Event.stopObserving(document.body,"click",this._documentClickEvent);
Event.stopObserving(document,"keydown",this._documentKeyDownEvent);
}
},keyPressed:function(_47,_48){
if(Event.keyCode(_47)==Event.KEY_ESC){
this.hide(_47,_48);
}
},hideOnClick:function(ev){
if(!this.showing){
return;
}
var el=Event.element(ev);
var _51=false;
do{
_51=_51||String(el.className).indexOf("FullColorPicker")>-1;
_51=_51||el==this.button;
_51=_51||el==this.input;
if(_51){
break;
}
el=el.parentNode;
}while(el);
if(!_51){
this.hide(ev);
}
},ieHack:function(){
if(this.iePopUp){
this.iePopUp.style.display="block";
this.iePopUp.style.top=(this.element.offsetTop)+"px";
this.iePopUp.style.left=(this.element.offsetLeft)+"px";
this.iePopUp.style.width=Math.abs(this.element.offsetWidth)+"px";
this.iePopUp.style.height=(this.element.offsetHeight+1)+"px";
}
},getBasicPickerContainer:function(_52,_53){
var _54=TABLE({className:"basic_colors palette_"+_53},TBODY());
var _55=Prado.WebUI.TColorPicker.palettes[_53];
var _56=this.cellOnClick.bind(this);
_55.each(function(_57){
var row=document.createElement("tr");
_57.each(function(c){
var td=document.createElement("td");
var img=IMG({src:Prado.WebUI.TColorPicker.UIImages["button.gif"],width:16,height:16});
img.style.backgroundColor="#"+c;
Event.observe(img,"click",_56);
Event.observe(img,"mouseover",function(e){
Element.addClassName(Event.element(e),"pickerhover");
});
Event.observe(img,"mouseout",function(e){
Element.removeClassName(Event.element(e),"pickerhover");
});
td.appendChild(img);
row.appendChild(td);
});
_54.childNodes[0].appendChild(row);
});
return DIV({className:this.options["ClassName"]+" BasicColorPicker",id:_52+"_picker"},_54);
},cellOnClick:function(e){
var el=Event.element(e);
if(el.tagName.toLowerCase()!="img"){
return;
}
var _62=Rico.Color.createColorFromBackground(el);
this.updateColor(_62);
},updateColor:function(_63){
this.input.value=_63.toString().toUpperCase();
this.button.style.backgroundColor=_63.toString();
if(isFunction(this.onChange)){
this.onChange(_63);
}
},getFullPickerContainer:function(_64){
this.buttons={OK:INPUT({value:this.options.OKButtonText,className:"button",type:"button"}),Cancel:INPUT({value:this.options.CancelButtonText,className:"button",type:"button"})};
var _65={};
["H","S","V","R","G","B"].each(function(_66){
_65[_66]=INPUT({type:"text",size:"3",maxlength:"3"});
});
_65["HEX"]=INPUT({className:"hex",type:"text",size:"6",maxlength:"6"});
this.inputs=_65;
var _67=Prado.WebUI.TColorPicker.UIImages;
this.inputs["currentColor"]=SPAN({className:"currentColor"});
this.inputs["oldColor"]=SPAN({className:"oldColor"});
var _68=TABLE({className:"inputs"},TBODY(null,TR(null,TD({className:"currentcolor",colSpan:2},this.inputs["currentColor"],this.inputs["oldColor"])),TR(null,TD(null,"H:"),TD(null,this.inputs["H"],'°')),TR(null,TD(null,"S:"),TD(null,this.inputs["S"],"%")),TR(null,TD(null,"V:"),TD(null,this.inputs["V"],"%")),TR(null,TD({className:"gap"},"R:"),TD({className:"gap"},this.inputs["R"])),TR(null,TD(null,"G:"),TD(null,this.inputs["G"])),TR(null,TD(null,"B:"),TD(null,this.inputs["B"])),TR(null,TD({className:"gap"},"#"),TD({className:"gap"},this.inputs["HEX"]))));
var _69={selector:SPAN({className:"selector"}),background:SPAN({className:"colorpanel"}),slider:SPAN({className:"slider"}),hue:SPAN({className:"strip"})};
if(Prado.Browser().ie){
var _70="filter:progid:DXImageTransform.Microsoft.AlphaImageLoader";
_69["background"]=SPAN({className:"colorpanel",style:_70+"(src='"+_67["background.png"]+"' sizingMethod=scale);"});
}
this.inputs=Object.extend(this.inputs,_69);
var _71=TABLE(null,TBODY(null,TR({className:"selection"},TD({className:"colors"},_69["selector"],_69["background"]),TD({className:"hue"},_69["slider"],_69["hue"]),TD({className:"inputs"},_68)),TR({className:"options"},TD({colSpan:3},this.buttons["OK"],this.buttons["Cancel"]))));
return DIV({className:this.options["ClassName"]+" FullColorPicker",id:_64+"_picker"},_71);
},initializeFullPicker:function(){
var _72=Rico.Color.createFromHex(this.input.value);
this.inputs.oldColor.style.backgroundColor=_72.asHex();
this.setColor(_72,true);
var i=0;
for(var _74 in this.inputs){
Event.observe(this.inputs[_74],"change",this.onInputChanged.bindEvent(this,_74));
i++;
if(i>6){
break;
}
}
this.isMouseDownOnColor=false;
this.isMouseDownOnHue=false;
this._onColorMouseDown=this.onColorMouseDown.bind(this);
this._onHueMouseDown=this.onHueMouseDown.bind(this);
this._onMouseUp=this.onMouseUp.bind(this);
this._onMouseMove=this.onMouseMove.bind(this);
Event.observe(this.inputs.background,"mousedown",this._onColorMouseDown);
Event.observe(this.inputs.hue,"mousedown",this._onHueMouseDown);
Event.observe(document.body,"mouseup",this._onMouseUp);
Event.observe(document.body,"mousemove",this._onMouseMove);
Event.observe(this.buttons.Cancel,"click",this.hide.bindEvent(this,this.options["Mode"]));
Event.observe(this.buttons.OK,"click",this.onOKClicked.bind(this));
},onColorMouseDown:function(ev){
this.isMouseDownOnColor=true;
this.onMouseMove(ev);
},onHueMouseDown:function(ev){
this.isMouseDownOnHue=true;
this.onMouseMove(ev);
},onMouseUp:function(ev){
this.isMouseDownOnColor=false;
this.isMouseDownOnHue=false;
},onMouseMove:function(ev){
if(this.isMouseDownOnColor){
this.changeSV(ev);
}
if(this.isMouseDownOnHue){
this.changeH(ev);
}
},changeSV:function(ev){
var px=Event.pointerX(ev);
var py=Event.pointerY(ev);
var pos=Position.cumulativeOffset(this.inputs.background);
var x=this.truncate(px-pos[0],0,255);
var y=this.truncate(py-pos[1],0,255);
var h=this.truncate(this.inputs.H.value,0,360)/360;
var s=x/255;
var b=(255-y)/255;
var _79=new Rico.Color();
_79.rgb=Rico.Color.HSBtoRGB(h,s,b);
this.inputs.selector.style.left=x+"px";
this.inputs.selector.style.top=y+"px";
this.inputs.currentColor.style.backgroundColor=_79.asHex();
return this.setColor(_79);
},changeH:function(ev){
var py=Event.pointerY(ev);
var pos=Position.cumulativeOffset(this.inputs.background);
var y=this.truncate(py-pos[1],0,255);
var h=(255-y)/255;
var s=parseInt(this.inputs.S.value)/100;
var b=parseInt(this.inputs.V.value)/100;
var _80=new Rico.Color();
_80.rgb=Rico.Color.HSBtoRGB(h,s,b);
var hue=new Rico.Color(_80.rgb.r,_80.rgb.g,_80.rgb.b);
hue.setSaturation(1);
hue.setBrightness(1);
this.inputs.background.style.backgroundColor=hue.asHex();
this.inputs.currentColor.style.backgroundColor=_80.asHex();
this.inputs.slider.style.top=this.truncate(y,0,255)+"px";
return this.setColor(_80);
},onOKClicked:function(ev){
var r=this.truncate(this.inputs.R.value,0,255);
var g=this.truncate(this.inputs.G.value,0,255);
var b=this.truncate(this.inputs.B.value,0,255);
var _81=new Rico.Color(r,g,b);
this.updateColor(_81);
this.inputs.oldColor.style.backgroundColor=_81.asHex();
this.hide(ev);
},onInputChanged:function(ev,_82){
if(this.isMouseDownOnColor||isMouseDownOnHue){
return;
}
switch(_82){
case "H":
case "S":
case "V":
var h=this.truncate(this.inputs.H.value,0,360)/360;
var s=this.truncate(this.inputs.S.value,0,100)/100;
var b=this.truncate(this.inputs.V.value,0,100)/100;
var _83=new Rico.Color();
_83.rgb=Rico.Color.HSBtoRGB(h,s,b);
return this.setColor(_83,true);
case "R":
case "G":
case "B":
var r=this.truncate(this.inputs.R.value,0,255);
var g=this.truncate(this.inputs.G.value,0,255);
var b=this.truncate(this.inputs.B.value,0,255);
var _83=new Rico.Color(r,g,b);
return this.setColor(_83,true);
case "HEX":
var _83=Rico.Color.createFromHex(this.inputs.HEX.value);
return this.setColor(_83,true);
}
},setColor:function(_84,_85){
var hsb=_84.asHSB();
this.inputs.H.value=parseInt(hsb.h*360);
this.inputs.S.value=parseInt(hsb.s*100);
this.inputs.V.value=parseInt(hsb.b*100);
this.inputs.R.value=_84.rgb.r;
this.inputs.G.value=_84.rgb.g;
this.inputs.B.value=_84.rgb.b;
this.inputs.HEX.value=_84.asHex().substring(1).toUpperCase();
var _86=Prado.WebUI.TColorPicker.UIImages;
var _87=_84.isBright()?"removeClassName":"addClassName";
Element[_87](this.inputs.selector,"target_white");
if(_85){
this.updateSelectors(_84);
}
},updateSelectors:function(_88){
var hsb=_88.asHSB();
var pos=[hsb.s*255,hsb.b*255,hsb.h*255];
this.inputs.selector.style.left=this.truncate(pos[0],0,255)+"px";
this.inputs.selector.style.top=this.truncate(255-pos[1],0,255)+"px";
this.inputs.slider.style.top=this.truncate(255-pos[2],0,255)+"px";
var hue=new Rico.Color(_88.rgb.r,_88.rgb.g,_88.rgb.b);
hue.setSaturation(1);
hue.setBrightness(1);
this.inputs.background.style.backgroundColor=hue.asHex();
this.inputs.currentColor.style.backgroundColor=_88.asHex();
},truncate:function(_89,min,max){
_89=parseInt(_89);
return _89<min?min:_89>max?max:_89;
}});

