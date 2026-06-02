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
					} else {
						element.classList.add(this.normalCssClass);
						element.classList.remove(this.activeCssClass);
						view.style.display = 'none';
					}
			}
		}
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
				}
				else
				{
					tab.classList.remove(this.activeCssClass);
					tab.classList.add(this.normalCssClass);
					view.style.display = 'none';
				}
			}
		}
	}
});
