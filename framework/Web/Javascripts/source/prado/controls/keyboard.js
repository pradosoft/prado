/*! PRADO TKeyboard javascript file | github.com/pradosoft/prado */

Prado.WebUI.TKeyboard = Prado.Class(Prado.WebUI.Control,
{
	onInit(options) {
		this.cssClass = options['CssClass'];
        this.forControl = document.getElementById(options['ForControl']);
        this.autoHide = options['AutoHide'];
        this.label = options['Label'] || 'On-screen keyboard';

        this.flagShift = false;
        this.flagCaps = false;
        this.flagHover = false;
        this.flagFocus = false;

        this.keys = new Array
        (
            new Array('` ~ D', '1 ! D', '2 @ D', '3 # D', '4 $ D', '5 % D', '6 ^ D', '7 &amp; D', '8 * D', '9 ( D', '0 ) D', '- _ D', '= + D', 'Bksp Bksp Bksp'),
            new Array('Del Del Del', 'q Q L', 'w W L', 'e E L', 'r R L', 't T L', 'y Y L', 'u U L', 'i I L', 'o O L', 'p P L', '[ { D', '] } D', '\\ | \\'),
            new Array('Caps Caps Caps', 'a A L', 's S L', 'd D L', 'f F L', 'g G L', 'h H L', 'j J L', 'k K L', 'l L L', '; : D', '\' " D', 'Exit Exit Exit'),
            new Array('Shift Shift Shift', 'z Z L', 'x X L', 'c C L', 'v V L', 'b B L', 'n N L', 'm M L', ', &lt; D', '. &gt; D', '/ ? D', 'Shift Shift Shift')
        );

        if (this.isObject(this.forControl))
        {
            this.forControl.keyboard = this;
            this.forControl.onfocus = function() {this.keyboard.show(); };
            this.forControl.onblur = function() {this.keyboard.scheduleHide();};
            this.forControl.onkeydown = function(e) {if (!e) e = window.event; const key = (e.keyCode)?e.keyCode:e.which; if(key == 9)  this.keyboard.hide();;};
            this.forControl.onselect = this.saveSelection;
            this.forControl.onclick = this.saveSelection;
            this.forControl.onkeyup = this.saveSelection;
        }

        this.render();

        this.tagKeyboard.onmouseover = function() {this.keyboard.flagHover = true;};
        this.tagKeyboard.onmouseout = function() {this.keyboard.flagHover = false;};

        if (!this.autoHide) this.show();
    },

	isObject(a) {
		return (typeof a == 'object' && !!a) || typeof a == 'function';
	},

	createElement(tagName, attributes, parent) {
        const tagElement = document.createElement(tagName);
        if (this.isObject(attributes)) for (const attribute in attributes) tagElement[attribute] = attributes[attribute];
        if (this.isObject(parent)) parent.appendChild(tagElement);
        return tagElement;
    },

	onmouseover() {
		this.className += ' Hover';
	},

	onmouseout() {
		this.className = this.className.replace(/( Hover| Active)/ig, '');
	},

    onmousedown() {
    	this.className += ' Active';
	},

    onmouseup() {
    	this.className = this.className.replace(/( Active)/ig, '');
    	this.keyboard.type(this.innerHTML);
	},

	/**
	 * Enter or Space activates a key, matching a mouse click, so the on-screen
	 * keyboard is operable by keyboard (WCAG 2.1.1).
	 */
	onkeydown(e) {
		if (!e) e = window.event;
		const key = (e.keyCode) ? e.keyCode : e.which;
		if (key == 13 || key == 32)
		{
			if (e.preventDefault) e.preventDefault();
			this.className += ' Active';
			this.keyboard.type(this.innerHTML);
			this.className = this.className.replace(/( Active)/ig, '');
		}
	},

	/**
	 * Focus entering a key keeps the keyboard visible; focus leaving it defers a
	 * hide check so focus can settle before deciding.
	 */
	onfocus() {
		this.keyboard.show();
	},

	onblur() {
		this.keyboard.scheduleHide();
	},

	/**
	 * Human-readable label for a key's displayed text, decoding HTML entities and
	 * naming the command keys so assistive technology announces each button.
	 */
	keyLabel(text) {
		const names =
		{
			'Bksp' : 'Backspace', 'Del' : 'Delete', 'Caps' : 'Caps Lock',
			'Shift' : 'Shift', 'Exit' : 'Exit'
		};
		if (names[text]) return names[text];
		return text.replace(/&gt;/g, '>').replace(/&lt;/g, '<').replace(/&amp;/g, '&');
	},

	render() {
        this.tagKeyboard = this.createElement('div', {className: this.cssClass, onselectstart() {return false;}}, this.element);
        this.tagKeyboard.keyboard = this;
        this.tagKeyboard.setAttribute('role', 'group');
        this.tagKeyboard.setAttribute('aria-label', this.label);

        for (let line = 0; line < this.keys.length; line++)
        {
            const tagLine = this.createElement('div', {className: 'Line'}, this.tagKeyboard);
            for (let key = 0; key < this.keys[line].length; key++)
            {
                const split = this.keys[line][key].split(' ');
                const tagKey = this.createElement('div', {className: `Key ${split[2]}`}, tagLine);
                // tagKey1/tagKey2 are appended to tagKey for their side effect.
                const k1 = this.createElement('div', {className: 'Key1', innerHTML: split[0], keyboard: this, tabIndex: 0, onmouseover: this.onmouseover, onmouseout: this.onmouseout, onmousedown: this.onmousedown, onmouseup: this.onmouseup, onkeydown: this.onkeydown, onfocus: this.onfocus, onblur: this.onblur}, tagKey);
                const k2 = this.createElement('div', {className: 'Key2', innerHTML: split[1], keyboard: this, tabIndex: 0, onmouseover: this.onmouseover, onmouseout: this.onmouseout, onmousedown: this.onmousedown, onmouseup: this.onmouseup, onkeydown: this.onkeydown, onfocus: this.onfocus, onblur: this.onblur}, tagKey);
                k1.setAttribute('role', 'button');
                k1.setAttribute('aria-label', this.keyLabel(split[0]));
                k2.setAttribute('role', 'button');
                k2.setAttribute('aria-label', this.keyLabel(split[1]));
            }
        }
    },

    isShown() {
        return (this.tagKeyboard.style.visibility.toLowerCase() == 'visible');
    },

    show() {
        if (this.isShown() == false) this.tagKeyboard.style.visibility = 'visible';
    },

    hide() {
        if (this.isShown() == true && this.autoHide) {this.tagKeyboard.style.visibility = 'hidden'; }
    },

    /**
     * Defer the hide decision so focus moving between the text box and the
     * on-screen keys settles first; only hide once focus has left both and the
     * pointer is not hovering the keyboard.
     */
    scheduleHide() {
        const kb = this;
        window.setTimeout(function() {
            if (!kb.autoHide) return;
            const active = document.activeElement;
            const withinKeyboard = kb.tagKeyboard && active && kb.tagKeyboard.contains(active);
            const inField = active === kb.forControl;
            if (!kb.flagHover && !withinKeyboard && !inField) kb.hide();
        }, 0);
    },

    type(key) {
        const input = this.forControl;
        const command = key.toLowerCase();

        if (command == 'exit') {this.hide();}
        else if (input != 'undefined' && input != null && command == 'bksp') {this.insert(input, 'bksp');}
        else if (input != 'undefined' && input != null && command == 'del') {this.insert(input, 'del');}
        else if (command == 'shift') {this.tagKeyboard.className = this.flagShift?'Keyboard Off':'Keyboard Shift';this.flagShift = this.flagShift?false:true;}
        else if (command == 'caps') {this.tagKeyboard.className = this.caps?'Keyboard Off':'Keyboard Caps';this.caps = this.caps?false:true;}
        else if (input != 'undefined' && input != null)
        {
            if (this.flagShift == true) {this.flagShift = false; this.tagKeyboard.className = 'Keyboard Off';}
            key = key.replace(/&gt;/, '>'); key = key.replace(/&lt;/, '<'); key = key.replace(/&amp;/, '&');
            this.insert(input, key);
        }

        if (command != 'exit') input.focus();
    },

    saveSelection() {
        if (this.keyboard.forControl.createTextRange)
        {
            this.keyboard.selection = document.selection.createRange().duplicate();
            return;
        }
    },

    insert(field, value) {
        if (this.forControl.createTextRange && this.selection)
        {
            if (value == 'bksp') {this.selection.moveStart("character", -1); this.selection.text = '';}
            else if (value == 'del') {this.selection.moveEnd("character", 1); this.selection.text = '';}
            else {this.selection.text = value;}
            this.selection.select();
        }
        else
        {
            let selectStart = this.forControl.selectionStart;
            const selectEnd = this.forControl.selectionEnd;
            let start = (this.forControl.value).substring(0, selectStart);
            let end = (this.forControl.value).substring(selectEnd, this.forControl.textLength);

            if (value == 'bksp') {start = start.substring(0, start.length - 1); selectStart -= 1; value = '';}
            if (value == 'del') {end = end.substring(1, end.length); value = '';}

            this.forControl.value = start + value + end;
            this.forControl.selectionStart = selectEnd + value.length;
            this.forControl.selectionEnd = selectStart + value.length;
        }
    }
});
