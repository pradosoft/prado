/*! PRADO TColorPicker javascript file | github.com/pradosoft/prado */

// Compute viewport-relative offset of an element (jQuery.fn.offset equivalent).
const _cpOffset = (el) => {
	const rect = el.getBoundingClientRect();
	return { top: rect.top + window.pageYOffset, left: rect.left + window.pageXOffset };
};

//-------------------- ricoColor.js
if(typeof(Rico) == "undefined") Rico = {};

Rico.Color = Prado.Class();

Rico.Color.prototype = {

   initialize(red, green, blue) {
      this.rgb = { r: red, g : green, b : blue };
   },

   setRed(r) {
      this.rgb.r = r;
   },

   setGreen(g) {
      this.rgb.g = g;
   },

   setBlue(b) {
      this.rgb.b = b;
   },

   setHue(h) {

      // get an HSB model, and set the new hue...
      const hsb = this.asHSB();
      hsb.h = h;

      // convert back to RGB...
      this.rgb = Rico.Color.HSBtoRGB(hsb.h, hsb.s, hsb.b);
   },

   setSaturation(s) {
      // get an HSB model, and set the new hue...
      const hsb = this.asHSB();
      hsb.s = s;

      // convert back to RGB and set values...
      this.rgb = Rico.Color.HSBtoRGB(hsb.h, hsb.s, hsb.b);
   },

   setBrightness(b) {
      // get an HSB model, and set the new hue...
      const hsb = this.asHSB();
      hsb.b = b;

      // convert back to RGB and set values...
      this.rgb = Rico.Color.HSBtoRGB( hsb.h, hsb.s, hsb.b );
   },

   darken(percent) {
      const hsb  = this.asHSB();
      this.rgb = Rico.Color.HSBtoRGB(hsb.h, hsb.s, Math.max(hsb.b - percent,0));
   },

   brighten(percent) {
      const hsb  = this.asHSB();
      this.rgb = Rico.Color.HSBtoRGB(hsb.h, hsb.s, Math.min(hsb.b + percent,1));
   },

   blend(other) {
      this.rgb.r = Math.floor((this.rgb.r + other.rgb.r)/2);
      this.rgb.g = Math.floor((this.rgb.g + other.rgb.g)/2);
      this.rgb.b = Math.floor((this.rgb.b + other.rgb.b)/2);
   },

   isBright() {
      const hsb = this.asHSB();
      return this.asHSB().b > 0.5;
   },

   isDark() {
      return ! this.isBright();
   },

   asRGB() {
      return `rgb(${this.rgb.r},${this.rgb.g},${this.rgb.b})`;
   },

   asHex() {
      return `#${this.toColorPart(this.rgb.r)}${this.toColorPart(this.rgb.g)}${this.toColorPart(this.rgb.b)}`;
   },

   asHSB() {
      return Rico.Color.RGBtoHSB(this.rgb.r, this.rgb.g, this.rgb.b);
   },

   toString() {
      return this.asHex();
   },

   toColorPart(number) {
        number = (number > 255 ? 255 : (number < 0 ? 0 : number));
        const hex = number.toString(16);
        return hex.length < 2 ? `0${hex}` : hex;
    }
};

Rico.Color.createFromHex = hexCode => {

   if ( hexCode.indexOf('#') == 0 )
      hexCode = hexCode.substring(1);

   let red = "ff", green = "ff", blue="ff";
   if(hexCode.length > 4)
	{
	   red   = hexCode.substring(0,2);
	   green = hexCode.substring(2,4);
	   blue  = hexCode.substring(4,6);
	}
	else if(hexCode.length > 0 & hexCode.length < 4)
	{
	  const r = hexCode.substring(0,1);
	  const g = hexCode.substring(1,2);
	  const b = hexCode.substring(2);
	  red = r+r;
	  green = g+g;
	  blue = b+b;
	}
   return new Rico.Color( parseInt(red,16), parseInt(green,16), parseInt(blue,16) );
};

/**
 * Factory method for creating a color from the background of
 * an HTML element.
 */
Rico.Color.createColorFromBackground = elem => {

   const actualColor = window.getComputedStyle(elem).backgroundColor;
  if ( actualColor == "transparent" && elem.parent )
      return Rico.Color.createColorFromBackground(elem.parent);

   if ( actualColor == null )
      return new Rico.Color(255,255,255);

   if ( actualColor.indexOf("rgb(") == 0 ) {
      const colors = actualColor.substring(4, actualColor.length - 1 );
      const colorArray = colors.split(",");
      return new Rico.Color( parseInt( colorArray[0] ),
                            parseInt( colorArray[1] ),
                            parseInt( colorArray[2] )  );

   }
   else if ( actualColor.indexOf("#") == 0 ) {
	  return Rico.Color.createFromHex(actualColor);
   }
   else
      return new Rico.Color(255,255,255);
};

Rico.Color.HSBtoRGB = (hue, saturation, brightness) => {

   let red   = 0;
	let green = 0;
	let blue  = 0;

   if (saturation == 0) {
      red = parseInt(brightness * 255.0 + 0.5);
	   green = red;
	   blue = red;
	}
	else {
      const h = (hue - Math.floor(hue)) * 6.0;
      const f = h - Math.floor(h);
      const p = brightness * (1.0 - saturation);
      const q = brightness * (1.0 - saturation * f);
      const t = brightness * (1.0 - (saturation * (1.0 - f)));

      switch (parseInt(h)) {
         case 0:
            red   = (brightness * 255.0 + 0.5);
            green = (t * 255.0 + 0.5);
            blue  = (p * 255.0 + 0.5);
            break;
         case 1:
            red   = (q * 255.0 + 0.5);
            green = (brightness * 255.0 + 0.5);
            blue  = (p * 255.0 + 0.5);
            break;
         case 2:
            red   = (p * 255.0 + 0.5);
            green = (brightness * 255.0 + 0.5);
            blue  = (t * 255.0 + 0.5);
            break;
         case 3:
            red   = (p * 255.0 + 0.5);
            green = (q * 255.0 + 0.5);
            blue  = (brightness * 255.0 + 0.5);
            break;
         case 4:
            red   = (t * 255.0 + 0.5);
            green = (p * 255.0 + 0.5);
            blue  = (brightness * 255.0 + 0.5);
            break;
          case 5:
            red   = (brightness * 255.0 + 0.5);
            green = (p * 255.0 + 0.5);
            blue  = (q * 255.0 + 0.5);
            break;
	    }
	}

   return { r : parseInt(red), g : parseInt(green) , b : parseInt(blue) };
};

Rico.Color.RGBtoHSB = (r, g, b) => {

   let hue;
   let saturation;
   let brightness;

   let cmax = (r > g) ? r : g;
   if (b > cmax)
      cmax = b;

   let cmin = (r < g) ? r : g;
   if (b < cmin)
      cmin = b;

   brightness = cmax / 255.0;
   if (cmax != 0)
      saturation = (cmax - cmin)/cmax;
   else
      saturation = 0;

   if (saturation == 0)
      hue = 0;
   else {
      const redc   = (cmax - r)/(cmax - cmin);
    	const greenc = (cmax - g)/(cmax - cmin);
    	const bluec  = (cmax - b)/(cmax - cmin);

    	if (r == cmax)
    	   hue = bluec - greenc;
    	else if (g == cmax)
    	   hue = 2.0 + redc - bluec;
      else
    	   hue = 4.0 + greenc - redc;

    	hue = hue / 6.0;
    	if (hue < 0)
    	   hue = hue + 1.0;
   }

   return { h : hue, s : saturation, b : brightness };
};

Prado.WebUI.TColorPicker = Prado.Class(Prado.WebUI.Control, {

	onInit(options) {
		const basics =
		{
			Palette : 'Small',
			ClassName : 'TColorPicker',
			Mode : 'Basic',
			OKButtonText : 'OK',
			CancelButtonText : 'Cancel',
			ShowColorPicker : true
		};

		this.element = null;
		this.showing = false;

		options = Object.assign(basics, options);
		this.options = options;
		this.input = document.getElementById(options['ID']);
		this.button = document.getElementById(`${options['ID']}_button`);
		this._buttonOnClick = this.buttonOnClick.bind(this);
		if(options['ShowColorPicker'])
			this.observe(this.button, "click", this._buttonOnClick);
		this.observe(this.input, "change", this.updatePicker.bind(this));

		Prado.Registry[options.ID] = this;
	},

	updatePicker(e) {
		const color = Rico.Color.createFromHex(this.input.value);
		this.button.style.backgroundColor = color.toString();
	},

	buttonOnClick(event) {
		const mode = this.options['Mode'];
		if(this.element == null)
		{
			const constructor = mode == "Basic" ? "getBasicPickerContainer": "getFullPickerContainer";
			this.element = this[constructor](this.options['ID'], this.options['Palette'])
			this.input.parentNode.appendChild(this.element);
			this.element.style.display = "none";

			if(mode == "Full")
				this.initializeFullPicker();
		}
		this.show(mode);
	},

	show(type) {
		if(!this.showing)
		{
			const controlOffset = _cpOffset(this.input);
			const parent = this.input.offsetParent || document.body;
			const parentOffset = _cpOffset(parent);

			this.element.style.top = `${controlOffset.top - parentOffset.top + this.input.offsetHeight - 1}px`;
			this.element.style.left = `${controlOffset.left - parentOffset.left}px`;
			this.element.style.display = 'block';

			//observe for clicks on the document body
			this._documentClickEvent = this.hideOnClick.bind(this, type);
			this._documentKeyDownEvent = this.keyPressed.bind(this, type);
			this.observe(document.body, "click", this._documentClickEvent);
			this.observe(document,"keydown", this._documentKeyDownEvent);
			this.showing = true;

			if(type == "Full")
			{
				this.observeMouseMovement();
				const color = Rico.Color.createFromHex(this.input.value);
				this.inputs.oldColor.style.backgroundColor = color.asHex();
				this.setColor(color,true);
			}
		}
	},

	hide(event) {
		if(this.showing)
		{
			this.element.style.display = "none";
			this.showing = false;
			this.stopObserving(document.body, "click", this._documentClickEvent);
			this.stopObserving(document,"keydown", this._documentKeyDownEvent);

			if(this._observingMouseMove)
			{
				this.stopObserving(document.body, "mousemove", this._onMouseMove);
				this._observingMouseMove = false;
			}
		}
	},

	keyPressed(event, type) {
		// esc
		if(event.keyCode == 27)
			this.hide(event,type);
	},

	hideOnClick(ev) {
		if(!this.showing) return;
		let el = ev.target;
		let within = false;
		do
		{	within = within || String(el.className).indexOf('FullColorPicker') > -1
			within = within || el == this.button;
			within = within || el == this.input;
			if(within) break;
			el = el.parentNode;
		}
		while(el);
		if(!within) this.hide(ev);
	},

	getBasicPickerContainer(pickerID, palette) {
		let div;
		let table;
		let tbody;
		let tr;
		let td;

		// main div
		div = document.createElement("div");
		div.className = `${this.options['ClassName']} BasicColorPicker`;
		div.id = `${pickerID}_picker`;

		table = document.createElement("table");
		table.className = `basic_colors palette_${palette}`;
		div.appendChild(table);

		tbody = document.createElement("tbody");
		table.appendChild(tbody);

		const colors = Prado.WebUI.TColorPicker.palettes[palette];
		const pickerOnClick = this.cellOnClick.bind(this);
		const obj=this;
		for (const color of colors) {
			const row = document.createElement("tr");
			for (const c of color) {
				const td = document.createElement("td");
				const img = document.createElement("img");
				img.src=Prado.WebUI.TColorPicker.UIImages['button.gif'];
				img.width=16;
				img.height=16;
				img.style.backgroundColor = `#${c}`;
				obj.observe(img,"click", pickerOnClick);
				obj.observe(img,"mouseover", e => {
					e.target.classList.add("pickerhover");
				});
				obj.observe(img,"mouseout", e => {
					e.target.classList.remove("pickerhover");
				});
				td.appendChild(img);
				row.appendChild(td);
			}
			table.childNodes[0].appendChild(row);
		}
		return div;
	},

	cellOnClick(e) {
		const el = e.target;
		if(el.tagName.toLowerCase() != "img")
			return;
		const color = Rico.Color.createColorFromBackground(el);
		this.updateColor(color);
	},

	updateColor(color) {
		this.input.value = color.toString().toUpperCase();
		this.button.style.backgroundColor = color.toString();
		if(typeof(this.onChange) == "function")
			this.onChange(color);
		if(this.options.OnColorSelected)
			this.options.OnColorSelected(this,color);
	},

	getFullPickerContainer(pickerID) {
		//create the buttons
		const okBtn = document.createElement("input");
		okBtn.className = 'button';
		okBtn.type = 'button';
		okBtn.value = this.options.OKButtonText;

		const cancelBtn = document.createElement("input");
		cancelBtn.className = 'button';
		cancelBtn.type = 'button';
		cancelBtn.value = this.options.CancelButtonText;

		this.buttons =
		{
			OK	   : okBtn,
			Cancel : cancelBtn
		};

		//create the 6 inputs
		const inputs = {};
		for (const type of ['H','S','V','R','G','B']) {
			inputs[type] = document.createElement("input");
			inputs[type].type='text';
			inputs[type].size='3';
			inputs[type].maxlength='3';
		}

		//create the HEX input
		inputs['HEX'] = document.createElement("input");
		inputs['HEX'].className = 'hex';
		inputs['HEX'].type='text';
		inputs['HEX'].size='6';
		inputs['HEX'].maxlength='6';

		this.inputs = inputs;

		const images = Prado.WebUI.TColorPicker.UIImages;

		this.inputs['currentColor'] = document.createElement("span");
		this.inputs['currentColor'].className='currentColor';
		this.inputs['oldColor'] = document.createElement("span");
		this.inputs['oldColor'].className='oldColor';

		const inputsTable = document.createElement("table");
		inputsTable.className='inputs';

		let tbody = document.createElement("tbody");
		inputsTable.appendChild(tbody);

		var tr = document.createElement("tr");
		tbody.appendChild(tr);

		var td= document.createElement("td");
		tr.appendChild(td);
		td.className='currentcolor';
		td.colSpan=2;
		td.appendChild(this.inputs['currentColor']);
		td.appendChild(this.inputs['oldColor']);

		this.internalAddRow(tbody, 'H:', this.inputs['H'], '°');
		this.internalAddRow(tbody, 'S:', this.inputs['S'], '%');
		this.internalAddRow(tbody, 'V:', this.inputs['V'], '%');
		this.internalAddRow(tbody, 'R:', this.inputs['R'], null, 'gap');
		this.internalAddRow(tbody, 'G:', this.inputs['G']);
		this.internalAddRow(tbody, 'B:', this.inputs['B']);
		this.internalAddRow(tbody, '#', this.inputs['HEX'], null, 'gap');

		const UIimages =
		{
			selector : document.createElement("span"),
			background : document.createElement("span"),
			slider : document.createElement("span"),
			hue : document.createElement("span")
		};

		UIimages['selector'].className='selector';
		UIimages['background'].className='colorpanel';
		UIimages['slider'].className='slider';
		UIimages['hue'].className='strip';

		this.inputs = Object.assign(this.inputs, UIimages);

		const pickerTable = document.createElement("table");
		tbody=document.createElement("tbody");
		pickerTable.appendChild(tbody);

		var tr = document.createElement("tr");
		tr.className='selection';
		tbody.appendChild(tr);

		var td= document.createElement("td");
		tr.appendChild(td);
		td.className='colors';
		td.appendChild(UIimages['selector']);
		td.appendChild(UIimages['background']);

		var td= document.createElement("td");
		tr.appendChild(td);
		td.className='hue';
		td.appendChild(UIimages['slider']);
		td.appendChild(UIimages['hue']);

		var td= document.createElement("td");
		tr.appendChild(td);
		td.className='inputs';
		td.appendChild(inputsTable);

		var tr = document.createElement("tr");
		tr.className='options';
		tbody.appendChild(tr);

		var td= document.createElement("td");
		tr.appendChild(td);
		td.colSpan=3;
		td.appendChild(this.buttons['OK']);
		td.appendChild(this.buttons['Cancel']);

		const div = document.createElement('div');
		div.className=`${this.options['ClassName']} FullColorPicker`;
		div.id=`${pickerID}_picker`;
		div.appendChild(pickerTable);
		return div;
	},

	internalAddRow(tbody, label1, object2, label2, className) {
		const tr = document.createElement("tr");
		tbody.appendChild(tr);

		var td= document.createElement("td");
		if(className!==undefined && className!==null)
			td.className=className;
		tr.appendChild(td);
		td.appendChild(document.createTextNode(label1));

		var td= document.createElement("td");
		if(className!==undefined && className!==null)
			td.className=className;
		tr.appendChild(td);
		td.appendChild(object2);
		if(label2!==undefined && label2!==null)
			td.appendChild(document.createTextNode(label2));
	},

	initializeFullPicker() {
		const color = Rico.Color.createFromHex(this.input.value);
		this.inputs.oldColor.style.backgroundColor = color.asHex();
		this.setColor(color,true);

		let i = 0;
		for(const type in this.inputs)
		{
			this.observe(this.inputs[type], "change",
				this.onInputChanged.bind(this, type));
			i++;

			if(i > 6) break;
		}

		this.isMouseDownOnColor = false;
		this.isMouseDownOnHue = false;

		this._onColorMouseDown = this.onColorMouseDown.bind(this);
		this._onHueMouseDown = this.onHueMouseDown.bind(this);
		this._onMouseUp = this.onMouseUp.bind(this);
		this._onMouseMove = this.onMouseMove.bind(this);

		this.observe(this.inputs.background, "mousedown", this._onColorMouseDown);
		this.observe(this.inputs.selector, "mousedown", this._onColorMouseDown);
		this.observe(this.inputs.hue, "mousedown", this._onHueMouseDown);
		this.observe(this.inputs.slider, "mousedown", this._onHueMouseDown);

		this.observe(document.body, "mouseup", this._onMouseUp);

		this.observeMouseMovement();

		this.observe(this.buttons.Cancel, "click", this.hide.bind(this, this.options['Mode']));
		this.observe(this.buttons.OK, "click", this.onOKClicked.bind(this));
	},

	observeMouseMovement() {
		if(!this._observingMouseMove)
		{
			this.observe(document.body, "mousemove", this._onMouseMove);
			this._observingMouseMove = true;
		}
	},

	onColorMouseDown(ev) {
		this.isMouseDownOnColor = true;
		this.onMouseMove(ev);
		ev.stopPropagation();
	},

	onHueMouseDown(ev) {
		this.isMouseDownOnHue = true;
		this.onMouseMove(ev);
		ev.stopPropagation();
	},

	onMouseUp(ev) {
		this.isMouseDownOnColor = false;
		this.isMouseDownOnHue = false;
		ev.stopPropagation();
	},

	onMouseMove(ev) {
		if(this.isMouseDownOnColor)
			this.changeSV(ev);
		if(this.isMouseDownOnHue)
			this.changeH(ev);
		ev.stopPropagation();
	},

	changeSV(ev) {
		const px = ev.pageX;
		const py = ev.pageY;
		const pos = _cpOffset(this.inputs.background);

		const x = this.truncate(px - pos['left'],0,255);
		const y = this.truncate(py - pos['top'],0,255);


		const s = x/255;
		const b = (255-y)/255;

		const current_s = parseInt(this.inputs.S.value);
		const current_b = parseInt(this.inputs.V.value);

		if(current_s == parseInt(s*100) && current_b == parseInt(b*100)) return;

		const h = this.truncate(this.inputs.H.value,0,360)/360;

		const color = new Rico.Color();
		color.rgb = Rico.Color.HSBtoRGB(h,s,b);


		this.inputs.selector.style.left = `${x}px`;
		this.inputs.selector.style.top = `${y}px`;

		this.inputs.currentColor.style.backgroundColor = color.asHex();

		return this.setColor(color);
	},

	changeH(ev) {
		const py = ev.pageY;
		const pos = _cpOffset(this.inputs.background);
		const y = this.truncate(py - pos['top'],0,255);

		const h = (255-y)/255;
		let current_h = this.truncate(this.inputs.H.value,0,360);
		current_h = current_h == 0 ? 360 : current_h;
		if(current_h == parseInt(h*360)) return;

		const s = parseInt(this.inputs.S.value)/100;
		const b = parseInt(this.inputs.V.value)/100;
		const color = new Rico.Color();
		color.rgb = Rico.Color.HSBtoRGB(h,s,b);

		const hue = new Rico.Color(color.rgb.r,color.rgb.g,color.rgb.b);
		hue.setSaturation(1); hue.setBrightness(1);

		this.inputs.background.style.backgroundColor = hue.asHex();
		this.inputs.currentColor.style.backgroundColor = color.asHex();

		this.inputs.slider.style.top = `${this.truncate(y,0,255)}px`;
		return this.setColor(color);

	},

	onOKClicked(ev) {
		const r = this.truncate(this.inputs.R.value,0,255);///255;
		const g = this.truncate(this.inputs.G.value,0,255);///255;
		const b = this.truncate(this.inputs.B.value,0,255);///255;
		const color = new Rico.Color(r,g,b);
		this.updateColor(color);
		this.inputs.oldColor.style.backgroundColor = color.asHex();
		this.hide(ev);
	},

	onInputChanged(ev, type) {
		if(this.isMouseDownOnColor || isMouseDownOnHue)
			return;


		switch(type)
		{
			case "H": case "S": case "V":
				var h = this.truncate(this.inputs.H.value,0,360)/360;
				var s = this.truncate(this.inputs.S.value,0,100)/100;
				var b = this.truncate(this.inputs.V.value,0,100)/100;
				var color = new Rico.Color();
				color.rgb = Rico.Color.HSBtoRGB(h,s,b);
				return this.setColor(color,true);
			case "R": case "G": case "B":
				var r = this.truncate(this.inputs.R.value,0,255);///255;
				var g = this.truncate(this.inputs.G.value,0,255);///255;
				var b = this.truncate(this.inputs.B.value,0,255);///255;
				var color = new Rico.Color(r,g,b);
				return this.setColor(color,true);
			case "HEX":
				var color = Rico.Color.createFromHex(this.inputs.HEX.value);
				return this.setColor(color,true);
		}
	},

	setColor(color, update) {
		const hsb = color.asHSB();

		this.inputs.H.value = parseInt(hsb.h*360);
		this.inputs.S.value = parseInt(hsb.s*100);
		this.inputs.V.value = parseInt(hsb.b*100);
		this.inputs.R.value = color.rgb.r;
		this.inputs.G.value = color.rgb.g;
		this.inputs.B.value = color.rgb.b;
		this.inputs.HEX.value = color.asHex().substring(1).toUpperCase();

		const images = Prado.WebUI.TColorPicker.UIImages;

		if(color.isBright())
			this.inputs.selector.classList.remove('target_white');
		else
			this.inputs.selector.classList.add('target_white');

		if(update)
			this.updateSelectors(color);
	},

	updateSelectors(color) {
		const hsb = color.asHSB();
		const pos = [hsb.s*255, hsb.b*255, hsb.h*255];

		this.inputs.selector.style.left = `${this.truncate(pos[0],0,255)}px`;
		this.inputs.selector.style.top = `${this.truncate(255-pos[1],0,255)}px`;
		this.inputs.slider.style.top = `${this.truncate(255-pos[2],0,255)}px`;

		const hue = new Rico.Color(color.rgb.r,color.rgb.g,color.rgb.b);
		hue.setSaturation(1); hue.setBrightness(1);
		this.inputs.background.style.backgroundColor = hue.asHex();
		this.inputs.currentColor.style.backgroundColor = color.asHex();
	},

	truncate(value, min, max) {
		value = parseInt(value);
		return value < min ? min : value > max ? max : value;
	}
});

Object.assign(Prado.WebUI.TColorPicker,
{
	palettes:
	{
		Small : [["fff", "fcc", "fc9", "ff9", "ffc", "9f9", "9ff", "cff", "ccf", "fcf"],
				["ccc", "f66", "f96", "ff6", "ff3", "6f9", "3ff", "6ff", "99f", "f9f"],
				["c0c0c0", "f00", "f90", "fc6", "ff0", "3f3", "6cc", "3cf", "66c", "c6c"],
				["999", "c00", "f60", "fc3", "fc0", "3c0", "0cc", "36f", "63f", "c3c"],
				["666", "900", "c60", "c93", "990", "090", "399", "33f", "60c", "939"],
				["333", "600", "930", "963", "660", "060", "366", "009", "339", "636"],
				["000", "300", "630", "633", "330", "030", "033", "006", "309", "303"]],

		Tiny : [["ffffff"/*white*/, "00ff00"/*lime*/, "008000"/*green*/, "0000ff"/*blue*/],
				["c0c0c0"/*silver*/, "ffff00"/*yellow*/, "ff00ff"/*fuchsia*/, "000080"/*navy*/],
				["808080"/*gray*/, "ff0000"/*red*/, "800080"/*purple*/, "000000"/*black*/]]
	},

	UIImages :
	{
		'button.gif' : 'button.gif',
//		'target_black.gif' : 'target_black.gif',
//		'target_white.gif' : 'target_white.gif',
//		'background.png' : 'background.png'
//		'slider.gif' : 'slider.gif',
//		'hue.gif' : 'hue.gif'
	}
});
