/*! PRADO TRatingList javascript file | github.com/pradosoft/prado */

Prado.WebUI.TRatingList = Prado.Class(Prado.WebUI.Control,
{
	selectedIndex : -1,
	rating: -1,
	readOnly : false,

	onInit(options) {
		const cap = document.getElementById(options.CaptionID);
		this.options = Object.assign({}, { caption : cap ? cap.innerHTML : '' }, options || {});

		this.radios = [];
		this._handlers = [];

		const root = document.getElementById(options.ID);
		if (root) root.classList.add(options.Style);
		for(let i = 0; i<options.ItemCount; i++)
		{
			const radio = document.getElementById(`${options.ID}_c${i}`);
			if (!radio) continue;
			const td = radio.parentNode.parentNode;

			if(td.tagName.toLowerCase()=='td')
			{
				const index = this.radios.length;
				this.radios.push(radio);
				td.classList.add("rating");
				// The star cell is decorative; the radio carries the semantics.
				td.setAttribute('aria-hidden', 'true');
				// Give the radio an accessible name even when its item text is empty.
				if(!radio.getAttribute('aria-label'))
					radio.setAttribute('aria-label', this.getIndexCaption(index) || String(index+1));
			}
		}

		this.selectedIndex = options.SelectedIndex;
		this.rating = options.Rating;
		this.readOnly = options.ReadOnly
		if(options.Rating <= 0 && options.SelectedIndex >= 0)
			this.rating = options.SelectedIndex+1;
		this.setReadOnly(this.readOnly);
	},

	hover(index, _ev) {
		if(this.readOnly==true) return;

		for(let i = 0; i<this.radios.length; i++)
		{
			const node = this.radios[i].parentNode.parentNode;
			if(i <= index)
				node.classList.add('rating_hover');
			else
				node.classList.remove('rating_hover');
			node.classList.remove("rating_selected");
			node.classList.remove("rating_half");
		}
		this.showCaption(this.getIndexCaption(index));
	},

	recover(_index, _ev) {
		if(this.readOnly==true) return;
		this.showRating(this.rating);
		this.showCaption(this.options.caption);
	},

	click(index, ev) {
		if(this.readOnly==true) return;
		this.select(index, ev);
	},

	/**
	 * Selects a rating. Shared by mouse clicks on a star cell and by the radio
	 * `change` event that a keyboard arrow key triggers.
	 */
	select(index, ev) {
		if(this.readOnly==true) return;
		this.selectedIndex = index;
		this.setRating(index+1);

		if(this.options['AutoPostBack']==true){
			this.dispatchRequest(ev);
		}
	},

	/** Radio `change` (keyboard arrow key or programmatic check). */
	change(index, ev) {
		this.select(index, ev);
	},

	/** Radio gains keyboard focus: preview the rating and mark the focused star. */
	focus(index, _ev) {
		if(this.readOnly==true) return;
		const node = this.radios[index].parentNode.parentNode;
		node.classList.add('rating_focus');
		this.hover(index, _ev);
	},

	/** Radio loses keyboard focus: clear the preview. */
	blur(index, _ev) {
		if(this.readOnly==true) return;
		const node = this.radios[index].parentNode.parentNode;
		node.classList.remove('rating_focus');
		this.recover(index, _ev);
	},

	dispatchRequest(ev) {
		const requestOptions = Object.assign({}, this.options,
		{
			ID : `${this.options.ID}_c${this.selectedIndex}`,
			EventTarget : `${this.options.ListName}$c${this.selectedIndex}`
		});
		new Prado.PostBack(requestOptions, ev);
 	},

	setRating(value) {
		this.rating = value;
		const base = Math.floor(value-1);
		const remainder = value - base-1;
		const halfMax = this.options.HalfRating["1"];
		const index = remainder > halfMax ? base+1 : base;
		for(let i = 0; i<this.radios.length; i++)
			this.radios[i].checked = (i == index);

		const caption = this.getIndexCaption(index);
		this.setCaption(caption);
		this.showCaption(caption);

		this.showRating(this.rating);
	},

	showRating(value) {
		const base = Math.floor(value-1);
		const remainder = value - base-1;
		const halfMin = this.options.HalfRating["0"];
		const halfMax = this.options.HalfRating["1"];
		const index = remainder > halfMax ? base+1 : base;
		const hasHalf = remainder >= halfMin && remainder <= halfMax;
		for(let i = 0; i<this.radios.length; i++)
		{
			const node = this.radios[i].parentNode.parentNode;
			if(i <= index)
				node.classList.add('rating_selected');
			else
				node.classList.remove('rating_selected');

			if(i==index+1 && hasHalf)
				node.classList.add("rating_half");
			else
				node.classList.remove("rating_half");
			node.classList.remove("rating_hover");
		}
	},

	getIndexCaption(index) {
		return index > -1 ? this.radios[index].value : this.options.caption;
	},

	showCaption(value) {
		const cap = document.getElementById(this.options.CaptionID);
		if (cap) cap.innerHTML = value;
		const root = document.getElementById(this.options.ID);
		if (root) root.setAttribute('title', value);
	},

	setCaption(value) {
		this.options.caption = value;
		this.showCaption(value);
	},

	setReadOnly(value) {
		this.readOnly = value;
		// Keep the radiogroup's server-rendered aria-readonly in step when the
		// state is toggled from a callback.
		const root = document.getElementById(this.options.ID);
		if (root) {
			if (value)
				root.setAttribute('aria-readonly', 'true');
			else
				root.removeAttribute('aria-readonly');
		}
		for(let i = 0; i<this.radios.length; i++)
		{
			const radio = this.radios[i];
			const node = radio.parentNode.parentNode;
			let h = this._handlers[i];
			if (!h) {
				h = this._handlers[i] = {
					hover:   this.hover.bind(this, i),
					recover: this.recover.bind(this, i),
					click:   this.click.bind(this, i),
					change:  this.change.bind(this, i),
					focus:   this.focus.bind(this, i),
					blur:    this.blur.bind(this, i),
				};
			}
			if(value)
			{
				node.classList.add('rating_disabled');
				// Disabled radios leave the tab order but still announce the
				// checked state, so a read-only rating stays readable.
				radio.disabled = true;
				node.removeEventListener('mouseover', h.hover);
				node.removeEventListener('mouseout',  h.recover);
				node.removeEventListener('click',     h.click);
				radio.removeEventListener('change',   h.change);
				radio.removeEventListener('focus',    h.focus);
				radio.removeEventListener('blur',     h.blur);
			} else {
				node.classList.remove('rating_disabled');
				radio.disabled = false;
				node.addEventListener('mouseover', h.hover);
				node.addEventListener('mouseout',  h.recover);
				node.addEventListener('click',     h.click);
				radio.addEventListener('change',   h.change);
				radio.addEventListener('focus',    h.focus);
				radio.addEventListener('blur',     h.blur);
			}
		}

		this.showRating(this.rating);
	}
});

Prado.WebUI.TActiveRatingList = Prado.Class(Prado.WebUI.TRatingList,
{
	dispatchRequest(ev) {
		const requestOptions = Object.assign({}, this.options,
		{
			ID : `${this.options.ID}_c${this.selectedIndex}`,
			EventTarget : `${this.options.ListName}$c${this.selectedIndex}`
		});
		const request = new Prado.CallbackRequest(requestOptions.EventTarget, requestOptions);
		if(request.dispatch()==false)
			ev.preventDefault();
	}

});
