/*! PRADO TTabPanel javascript file | github.com/pradosoft/prado */

Prado.WebUI.TTabPanel = Prado.Class(Prado.WebUI.Control,
{
	onInit(options) {
		this.views = options.Views;
		this.viewsvis = options.ViewsVis;
		this.hiddenField = document.getElementById(`${options.ID}_1`);
		this.activeCssClass = options.ActiveCssClass;
		this.normalCssClass = options.NormalCssClass;
		const length = options.Views.length;
		for(let i = 0; i<length; i++)
		{
			const item = options.Views[i];
			const element = document.getElementById(`${item}_0`);
			if (element && options.ViewsVis[i])
			{
				this.observe(element, "click", this.elementClicked.bind(this, item));
				this.observe(element, "keydown", this.keyPressed.bind(this, item));
				if (options.AutoSwitch)
					this.observe(element, "mouseenter", this.elementClicked.bind(this, item));
			}

			if(element)
			{
				const view = document.getElementById(options.Views[i]);
				if (view)
					if(this.hiddenField.value == i)
					{
						element.classList.add(this.activeCssClass);
						element.classList.remove(this.normalCssClass);
						view.style.display = '';
						this.setTabState(element, true);
					} else {
						element.classList.add(this.normalCssClass);
						element.classList.remove(this.activeCssClass);
						view.style.display = 'none';
						this.setTabState(element, false);
					}
			}
		}
	},

	/**
	 * Reflects the selected state on a tab: aria-selected and the roving tabindex
	 * that keeps only the selected tab in the sequential tab order.
	 */
	setTabState(tab, selected) {
		if (!tab.hasAttribute('role')) return; // navigation tab, not part of the pattern
		tab.setAttribute('aria-selected', selected ? 'true' : 'false');
		tab.setAttribute('tabindex', selected ? '0' : '-1');
	},

	elementClicked(viewID, _event) {
		const length = this.views.length;
		for(let i = 0; i<length; i++)
		{
			const item = this.views[i];
			const tab = document.getElementById(`${item}_0`);
			const view = document.getElementById(item);
			if (tab && view)
			{
				if(item == viewID)
				{
					tab.classList.remove(this.normalCssClass);
					tab.classList.add(this.activeCssClass);
					view.style.display = '';
					this.hiddenField.value=i;
					this.setTabState(tab, true);
				}
				else
				{
					tab.classList.remove(this.activeCssClass);
					tab.classList.add(this.normalCssClass);
					view.style.display = 'none';
					this.setTabState(tab, false);
				}
			}
		}
	},

	/**
	 * Keyboard navigation for the tablist: arrow keys move to the previous or
	 * next visible tab, Home/End jump to the first or last, and the newly focused
	 * tab is activated (the same automatic-activation behavior as a click).
	 */
	keyPressed(viewID, event) {
		const kc = event.keyCode;
		// left/up 37/38, right/down 39/40, home 36, end 35
		if (kc < 35 || kc > 40) return;

		const visible = [];
		for (let i = 0; i < this.views.length; i++) {
			if (this.viewsvis[i]) {
				const tab = document.getElementById(`${this.views[i]}_0`);
				if (tab) visible.push({ id: this.views[i], tab });
			}
		}
		if (visible.length === 0) return;

		let pos = visible.findIndex((v) => v.id == viewID);
		if (pos === -1) return;

		if (kc == 37 || kc == 38)      pos = (pos - 1 + visible.length) % visible.length;
		else if (kc == 39 || kc == 40) pos = (pos + 1) % visible.length;
		else if (kc == 36)             pos = 0;
		else if (kc == 35)             pos = visible.length - 1;

		event.preventDefault();
		const target = visible[pos];
		this.elementClicked(target.id, event);
		target.tab.focus();
	}
});
