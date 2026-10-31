/*! PRADO TRowSelectColumn javascript file | github.com/pradosoft/prado */

/**
 * TRowSelectColumn select-all checkbox.
 *
 * The wrapper registers under the ClientID of the select-all checkbox in the
 * header cell; `CheckBoxIDs` lists the row checkboxes of the column.
 *
 * - Changing the select-all checkbox checks or clears every enabled row checkbox.
 * - The select-all checkbox is checked when every row is checked, and
 *   `indeterminate` when some rows are checked.
 * - A shift-click on a row checkbox sets the enabled rows between the previously
 *   clicked row and the clicked row to the clicked row's state.
 *
 * ```javascript
 * const column = Prado.Registry['ctl0_Content_Grid_ctl1_ctl0'];
 * column.selectAll(true);   // check every row
 * column.selectedBoxes();   // the checked row checkboxes
 * ```
 */
Prado.WebUI.TRowSelectColumn = Prado.Class(Prado.WebUI.Control,
{
	onInit(options) {
		this.boxes = [];
		this.lastBox = null;
		for (const id of options.CheckBoxIDs || []) {
			const box = document.getElementById(id);
			if (box) {
				this.boxes.push(box);
			}
		}
		if (!this.element) {
			return;
		}
		this.observe(this.element, 'click', this.selectAllClicked.bind(this));
		for (const box of this.boxes) {
			this.observe(box, 'click', this.boxClicked.bind(this));
		}
		this.update();
	},

	/**
	 * Checks or clears every enabled row checkbox.
	 * @param {boolean} checked - the state to set.
	 */
	selectAll(checked) {
		for (const box of this.boxes) {
			if (!box.disabled) {
				box.checked = checked;
			}
		}
		this.lastBox = null;
		this.update();
	},

	selectAllClicked() {
		this.selectAll(this.element.checked);
	},

	boxClicked(event) {
		const box = event.currentTarget;
		if (event.shiftKey && this.lastBox && this.lastBox !== box) {
			const from = this.boxes.indexOf(this.lastBox);
			const to = this.boxes.indexOf(box);
			const start = Math.min(from, to);
			const end = Math.max(from, to);
			for (let i = start; i <= end; i++) {
				if (!this.boxes[i].disabled) {
					this.boxes[i].checked = box.checked;
				}
			}
		}
		this.lastBox = box;
		this.update();
	},

	/**
	 * @returns {HTMLInputElement[]} the checked row checkboxes.
	 */
	selectedBoxes() {
		return this.boxes.filter((box) => box.checked);
	},

	/**
	 * Shows the select-all checkbox checked, cleared, or mixed from the row checkboxes.
	 */
	update() {
		const count = this.selectedBoxes().length;
		this.element.checked = count > 0 && count === this.boxes.length;
		this.element.indeterminate = count > 0 && count < this.boxes.length;
	},
});
