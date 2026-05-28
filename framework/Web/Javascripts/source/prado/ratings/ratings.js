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
				this.radios.push(radio);
				td.classList.add("rating");
			}
		}

		this.selectedIndex = options.SelectedIndex;
		this.rating = options.Rating;
		this.readOnly = options.ReadOnly
		if(options.Rating <= 0 && options.SelectedIndex >= 0)
			this.rating = options.SelectedIndex+1;
		this.setReadOnly(this.readOnly);
	},

	hover(index, ev) {
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

	recover(index, ev) {
		if(this.readOnly==true) return;
		this.showRating(this.rating);
		this.showCaption(this.options.caption);
	},

	click(index, ev) {
		if(this.readOnly==true) return;
		this.selectedIndex = index;
		this.setRating(index+1);

		if(this.options['AutoPostBack']==true){
			this.dispatchRequest(ev);
		}
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
		for(let i = 0; i<this.radios.length; i++)
		{
			const node = this.radios[i].parentNode.parentNode;
			let h = this._handlers[i];
			if (!h) {
				h = this._handlers[i] = {
					hover:   this.hover.bind(this, i),
					recover: this.recover.bind(this, i),
					click:   this.click.bind(this, i),
				};
			}
			if(value)
			{
				node.classList.add('rating_disabled');
				node.removeEventListener('mouseover', h.hover);
				node.removeEventListener('mouseout',  h.recover);
				node.removeEventListener('click',     h.click);
			} else {
				node.classList.remove('rating_disabled');
				node.addEventListener('mouseover', h.hover);
				node.addEventListener('mouseout',  h.recover);
				node.addEventListener('click',     h.click);
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
