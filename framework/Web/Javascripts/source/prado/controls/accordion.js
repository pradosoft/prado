/*! PRADO TAccordion javascript file | github.com/pradosoft/prado */

/* Based on:
 *
 * Simple Accordion Script
 * Requires Prototype and Script.aculo.us Libraries
 * By: Brian Crescimanno <brian.crescimanno@gmail.com>
 * http://briancrescimanno.com
 *
 * Adapted to Prado & minor improvements: Gabor Berczi <gabor.berczi@devworx.hu>
 * jQuery port by Bas Fabio <ctrlaltca@gmail.com>
 *
 * This work is licensed under the Creative Commons Attribution-Share Alike 3.0
 * http://creativecommons.org/licenses/by-sa/3.0/us/
 */

/**
 * Small native height-animation helper. Animates element height between two
 * values using requestAnimationFrame; calls done() on completion. Used where
 * the accordion previously called jQuery.fn.animate({height}, duration[, cb]).
 * @since 4.4.0
 */
const _accAnimateHeight = (el, from, to, duration, done) => {
	if (!duration || duration <= 0) {
		el.style.height = `${to}px`;
		if (done) done();
		return;
	}
	const start = performance.now();
	const step = (now) => {
		const t = Math.min(1, (now - start) / duration);
		el.style.height = `${from + (to - from) * t}px`;
		if (t < 1) requestAnimationFrame(step);
		else if (done) done();
	};
	requestAnimationFrame(step);
};

Prado.WebUI.TAccordion = Prado.Class(Prado.WebUI.Control,
{
    	onInit(options) {
            this.accordion = document.getElementById(options.ID);
            this.options = options;
            this.hiddenField = document.getElementById(`${options.ID}_1`);

            if (this.options.maxHeight)
            {
                this.maxHeight = this.options.maxHeight;
            } else {
                this.maxHeight = 0;
                this.checkMaxHeight();
            }

            this.currentView = null;
            this.oldView = null;

            let i = 0;
            for(const view in this.options.Views)
            {
                const header = document.getElementById(`${view}_0`);
                if(header)
                {
                    this.observe(header, "click", this.elementClicked.bind(this, view));
                    if(this.hiddenField.value == i)
                    {
                        this.currentView = view;
                        const cur = document.getElementById(this.currentView);
                        if(cur && cur.offsetHeight != this.maxHeight)
                            cur.style.height = `${this.maxHeight}px`;
                    }
                }
                i++;
            }
        },

	checkMaxHeight() {
		for(const viewID in this.options.Views)
		{
			const view = document.getElementById(viewID);
			if(view && view.offsetHeight > this.maxHeight)
 				this.maxHeight = view.offsetHeight;
		}
	},

	elementClicked(viewID, _event) {
		let i = 0;
		for(const index in this.options.Views)
		{
			if (document.getElementById(index))
			{
				if(index == viewID)
				{
					this.oldView = this.currentView;
					this.currentView = index;

					this.hiddenField.value=i;
				}
			}
			i++;
		}
		if(this.oldView != this.currentView)
		{
			const cur = document.getElementById(this.currentView);
			const old = document.getElementById(this.oldView);
			const oldHdr = document.getElementById(`${this.oldView}_0`);
			const curHdr = document.getElementById(`${this.currentView}_0`);
			if(this.options.Duration > 0)
			{
				this.animate();
			} else {
				if (cur) {
					cur.style.height = `${this.maxHeight}px`;
					cur.style.display = '';
				}
				if (old) old.style.display = 'none';

				if (oldHdr) { oldHdr.className = ''; oldHdr.classList.add(this.options.HeaderCssClass); }
				if (curHdr) { curHdr.className = ''; curHdr.classList.add(this.options.ActiveHeaderCssClass); }
			}
		}
	},

	animate() {
		const oldHdr = document.getElementById(`${this.oldView}_0`);
		const curHdr = document.getElementById(`${this.currentView}_0`);
		if (oldHdr) { oldHdr.className = ''; oldHdr.classList.add(this.options.HeaderCssClass); }
		if (curHdr) { curHdr.className = ''; curHdr.classList.add(this.options.ActiveHeaderCssClass); }

		const old = document.getElementById(this.oldView);
		const cur = document.getElementById(this.currentView);
		if (old) {
			_accAnimateHeight(old, old.offsetHeight, 0, this.options.Duration, () => { old.style.display = 'none'; });
		}
		if (cur) {
			cur.style.height = '0px';
			cur.style.display = '';
			_accAnimateHeight(cur, 0, this.maxHeight, this.options.Duration);
		}
	}
});
