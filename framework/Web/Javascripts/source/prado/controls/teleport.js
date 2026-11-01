/*! PRADO TTeleport javascript file | github.com/pradosoft/prado */

/**
 * TTeleport control.
 *
 * The server renders the content of a TTeleport after the page form, in an
 * element with the `data-prado-teleport` attribute. The browser submits a field
 * with its form owner only, so this control sets the HTML `form` attribute of
 * each field and button in the content to the page form ID. A field inside a
 * nested form, such as `<form method="dialog">`, belongs to that form and is left
 * alone, as is a field with its own `form` attribute.
 *
 * A MutationObserver applies the same to content that a callback or a script
 * adds later, and restores a `form` attribute that a morph removes.
 */
Prado.WebUI.TTeleport = Prado.Class(Prado.WebUI.Control,
{
	onInit(options) {
		this.form = document.getElementById(options.FormID);
		if (!this.element || !this.form)
			return;
		Prado.WebUI.TTeleport.associate(this.element, this.form);
		this.mutations = new MutationObserver(() => Prado.WebUI.TTeleport.associate(this.element, this.form));
		this.mutations.observe(this.element, { childList: true, subtree: true, attributes: true, attributeFilter: ['form'] });
	},

	onDone() {
		if (this.mutations)
			this.mutations.disconnect();
		this.mutations = null;
	}
});

/**
 * The listed form-associated elements, which a form owns through the `form` attribute.
 */
Prado.WebUI.TTeleport.FORM_ELEMENTS = 'button, fieldset, input, object, output, select, textarea';

/**
 * Sets the `form` attribute of each listed form-associated element in `root` to
 * the ID of `form`. An element inside a form, or with a `form` attribute, keeps its form.
 * @param {Element} root - the content element
 * @param {HTMLFormElement} form - the page form
 */
Prado.WebUI.TTeleport.associate = function(root, form) {
	for (const el of root.querySelectorAll(Prado.WebUI.TTeleport.FORM_ELEMENTS)) {
		if (!el.hasAttribute('form') && !el.closest('form'))
			el.setAttribute('form', form.id);
	}
};

/**
 * Replaces the content element of a TTeleport that an ancestor's callback update
 * renders. A missing element is added after the page form and the TTeleport
 * elements that follow it. The callback request is `this`, as for
 * `Prado.Element.replace()`.
 * @param {string} id - the client ID of the TTeleport
 * @param {string} formID - the client ID of the page form
 * @param {?string} content - the HTML, or null when it is in the boundary
 * @param {?string} boundary - the response boundary of the HTML
 */
Prado.WebUI.TTeleport.replace = function(id, formID, content, boundary) {
	if (!document.getElementById(id)) {
		const form = document.getElementById(formID);
		if (!form)
			return;
		let last = form;
		while (last.nextElementSibling && last.nextElementSibling.hasAttribute('data-prado-teleport'))
			last = last.nextElementSibling;
		const el = document.createElement('div');
		el.id = id;
		last.after(el);
	}
	Prado.Element.replace.call(this, id, content, boundary, true);
};
