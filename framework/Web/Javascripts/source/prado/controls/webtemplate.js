/*! PRADO TWebTemplate javascript file | github.com/pradosoft/prado */

/**
 * TWebTemplate control.
 *
 * Wraps an HTML5 `<template>` element and stamps copies of its inert content
 * into the document. The content of a `<template>` lives in a separate
 * `DocumentFragment`, so it must be cloned before it appears on the page.
 *
 * Placeholders written as `{{path}}` in text nodes and attribute values are
 * substituted during stamping. `path` may be a dotted path, e.g. `{{user.name}}`.
 * Substituted values are assigned as text and attribute values, never parsed as
 * HTML, so data cannot inject markup.
 *
 * Every stamping method takes a `target` that is an element ID string, a DOM
 * element, or a jQuery object, and returns the array of top-level nodes that
 * were inserted.
 *
 * ```javascript
 * const tpl = Prado.WebUI.TWebTemplate.get('ctl0_Content_RowTemplate');
 * tpl.appendTo('listBody', {name: 'Ada', role: 'Engineer'});
 * tpl.repeatInto('listBody', [{name: 'Ada'}, {name: 'Grace'}]);
 * ```
 *
 * ## Instances
 *
 * Unless tracking is disabled, each stamped copy becomes an *instance*: its data
 * and the positions of its placeholders are recorded so the copy can be updated
 * later without being rebuilt. An instance record is stored as a property on
 * each of its root nodes, so it is released when those nodes are, and the roots
 * carry `data-prado-template` and `data-prado-instance` attributes. The instance
 * attribute holds a UID that groups the roots of a multi-root template.
 *
 * ```javascript
 * const uid = tpl.appendTo('listBody', {name: 'Ada', role: 'Engineer'})
 *                .pradoInstance.uid;
 * tpl.updateInstance(uid, {role: 'Rear Admiral'});   // patches in place
 * tpl.refreshInstance(uid);                          // rebuilds from new markup
 * ```
 *
 * `updateInstance()` writes only into the recorded placeholder positions, so
 * anything else inside the copy — typed input, focus, listeners, nodes added by
 * other code — is left untouched. `refreshInstance()` re-stamps from the
 * template's current content using the instance's stored data, which is the path
 * to take when the markup itself changed.
 */
Prado.WebUI.TWebTemplate = Prado.Class(Prado.WebUI.Control,
{
	onInit(options) {
		this.options = options || {};
		this.restoreInstances();
	},

	/**
	 * @return bool whether stamped copies are recorded as updatable instances
	 */
	getTrackInstances() {
		return this.options.TrackInstances !== false;
	},

	/**
	 * @return bool whether instances round-trip a postback through a hidden field
	 */
	getPersistInstances() {
		return this.options.PersistInstances === true && this.getTrackInstances();
	},

	/**
	 * @return Element the hidden field carrying the persisted instances, or null
	 */
	getPersistField() {
		return document.getElementById(this.ID + '_instances');
	},

	/**
	 * Serializes every connected instance into the hidden field. An instance is
	 * persistable only when a root's parent element has an `id` to restore into.
	 * Called after every instance mutation; a no-op unless persistence is enabled.
	 */
	persistInstances() {
		if (!this.getPersistInstances()) {
			return;
		}
		const field = this.getPersistField();
		if (!field) {
			return;
		}
		const records = [];
		for (const instance of this.getInstances()) {
			const anchor = instance.roots.find((node) => node.parentNode && node.parentNode.id);
			if (anchor) {
				records.push({ uid: instance.uid, target: anchor.parentNode.id, data: instance.data });
			}
		}
		field.value = JSON.stringify(records);
	},

	/**
	 * Re-stamps the instances recorded in the hidden field, keeping their UIDs.
	 * The server renders the field with the state the previous page submitted,
	 * so the instances reappear after a full-page postback.
	 *
	 * Idempotent: a record whose instance is already present in the document is
	 * skipped, so re-registering the wrapper — which the callback response does
	 * on every callback — does not duplicate instances that survived in the DOM.
	 */
	restoreInstances() {
		if (!this.getPersistInstances()) {
			return;
		}
		const field = this.getPersistField();
		if (!field || field.value === '') {
			return;
		}
		let records;
		try {
			records = JSON.parse(field.value);
		} catch (_e) {
			return;
		}
		if (!Array.isArray(records)) {
			return;
		}
		for (const record of records) {
			const match = /^wt(\d+)$/.exec(record.uid || '');
			if (match) {
				Prado.WebUI.TWebTemplate.uidCounter =
					Math.max(Prado.WebUI.TWebTemplate.uidCounter, parseInt(match[1], 10));
			}
			// Already live in the DOM (wrapper re-registered, not a fresh page)
			if (this.getInstance(record.uid)) {
				continue;
			}
			const element = Prado.WebUI.TWebTemplate.resolve(record.target);
			if (!element) {
				continue;
			}
			const stamped = this.stampFragment(record.data || {}, record.uid);
			element.appendChild(stamped.fragment);
		}
		this.persistInstances();
	},

	/**
	 * Returns the template's own inert content fragment. Mutating it changes
	 * what later stamping produces.
	 * @return DocumentFragment the live content of the template element
	 */
	getContent() {
		return this.element ? this.element.content : null;
	},

	/**
	 * Replaces the template's inert content with new markup. Copies already
	 * stamped keep the markup they were stamped from; rebuild them with
	 * {@see refreshAll}.
	 * @param string html the markup to parse into the content fragment
	 * @return bool whether the content was replaced
	 */
	setContent(html) {
		if (!this.element) {
			return false;
		}
		this.element.innerHTML = html;
		return true;
	},

	/**
	 * Returns the first element inside the template content matching a CSS
	 * selector. The result belongs to the inert fragment, not the document.
	 * @param string selector CSS selector
	 * @return Element the matching element, or null
	 */
	find(selector) {
		const content = this.getContent();
		return content ? content.querySelector(selector) : null;
	},

	/**
	 * Returns all elements inside the template content matching a CSS selector.
	 * @param string selector CSS selector
	 * @return array the matching elements
	 */
	findAll(selector) {
		const content = this.getContent();
		return content ? Array.from(content.querySelectorAll(selector)) : [];
	},

	/**
	 * Clones the template content and substitutes any `{{path}}` placeholders.
	 * The copy is not tracked as an instance; use a stamping method for that.
	 * @param object data values keyed by placeholder path; optional
	 * @return DocumentFragment a detached copy of the content
	 */
	clone(data) {
		const content = this.getContent();
		if (!content) {
			return document.createDocumentFragment();
		}
		const fragment = content.cloneNode(true);
		if (data) {
			Prado.WebUI.TWebTemplate.substitute(fragment, data);
		}
		return fragment;
	},

	/**
	 * Clones the content, substitutes placeholders, and records an instance for
	 * the copy when tracking is enabled.
	 * @param object data values keyed by placeholder path
	 * @param string uid reuse this instance UID instead of allocating one; optional
	 * @return object `{fragment, nodes, instance}`; `instance` is null when untracked
	 */
	stampFragment(data, uid) {
		const content = this.getContent();
		const fragment = content ? content.cloneNode(true) : document.createDocumentFragment();
		const values = data || {};
		const bindings = Prado.WebUI.TWebTemplate.bind(fragment, values);
		const nodes = Array.from(fragment.childNodes);

		if (!this.getTrackInstances()) {
			return { fragment, nodes, instance: null };
		}

		const instance = {
			uid: uid || Prado.WebUI.TWebTemplate.nextUid(),
			templateId: this.ID,
			data: { ...values },
			bindings,
			roots: nodes
		};
		Prado.WebUI.TWebTemplate.markInstance(instance);
		return { fragment, nodes, instance };
	},

	/**
	 * Stamps a copy and inserts it with the given placement callback.
	 * @param mixed target element ID, DOM element, or jQuery object
	 * @param object data values keyed by placeholder path
	 * @param function place receives `(element, fragment)` and performs the insert
	 * @return array the inserted top-level nodes, carrying `pradoInstance`
	 */
	stampWith(target, data, place) {
		const element = Prado.WebUI.TWebTemplate.resolve(target);
		if (!element) {
			return [];
		}
		const stamped = this.stampFragment(data);
		place(element, stamped.fragment);
		stamped.nodes.pradoInstance = stamped.instance;
		this.persistInstances();
		return stamped.nodes;
	},

	/**
	 * Appends a stamped copy of the content as the last children of the target.
	 * @param mixed target element ID, DOM element, or jQuery object
	 * @param object data values keyed by placeholder path; optional
	 * @return array the inserted top-level nodes, carrying `pradoInstance`
	 */
	appendTo(target, data) {
		return this.stampWith(target, data, (element, fragment) => {
			element.appendChild(fragment);
		});
	},

	/**
	 * Inserts a stamped copy of the content as the first children of the target.
	 * @param mixed target element ID, DOM element, or jQuery object
	 * @param object data values keyed by placeholder path; optional
	 * @return array the inserted top-level nodes, carrying `pradoInstance`
	 */
	prependTo(target, data) {
		return this.stampWith(target, data, (element, fragment) => {
			element.insertBefore(fragment, element.firstChild);
		});
	},

	/**
	 * Replaces all children of the target with a stamped copy of the content.
	 * @param mixed target element ID, DOM element, or jQuery object
	 * @param object data values keyed by placeholder path; optional
	 * @return array the inserted top-level nodes, carrying `pradoInstance`
	 */
	replaceContentOf(target, data) {
		const element = Prado.WebUI.TWebTemplate.resolve(target);
		if (!element) {
			return [];
		}
		element.textContent = '';
		return this.appendTo(element, data);
	},

	/**
	 * Inserts a stamped copy of the content before the target element.
	 * @param mixed target element ID, DOM element, or jQuery object
	 * @param object data values keyed by placeholder path; optional
	 * @return array the inserted top-level nodes, carrying `pradoInstance`
	 */
	insertBefore(target, data) {
		const element = Prado.WebUI.TWebTemplate.resolve(target);
		if (!element || !element.parentNode) {
			return [];
		}
		return this.stampWith(element, data, (node, fragment) => {
			node.parentNode.insertBefore(fragment, node);
		});
	},

	/**
	 * Inserts a stamped copy of the content after the target element.
	 * @param mixed target element ID, DOM element, or jQuery object
	 * @param object data values keyed by placeholder path; optional
	 * @return array the inserted top-level nodes, carrying `pradoInstance`
	 */
	insertAfter(target, data) {
		const element = Prado.WebUI.TWebTemplate.resolve(target);
		if (!element || !element.parentNode) {
			return [];
		}
		return this.stampWith(element, data, (node, fragment) => {
			node.parentNode.insertBefore(fragment, node.nextSibling);
		});
	},

	/**
	 * Stamps one copy of the content per item of a data array. The target is
	 * emptied first unless `keep` is true.
	 * @param mixed target element ID, DOM element, or jQuery object
	 * @param array items one data object per copy
	 * @param bool keep whether to keep the existing children of the target
	 * @return array the inserted top-level nodes of every copy
	 */
	repeatInto(target, items, keep) {
		const element = Prado.WebUI.TWebTemplate.resolve(target);
		if (!element) {
			return [];
		}
		if (!keep) {
			element.textContent = '';
		}
		let nodes = [];
		for (const item of (items || [])) {
			nodes = nodes.concat(this.appendTo(element, item));
		}
		return nodes;
	},

	/**
	 * Attaches a shadow root to the target and stamps the content into it. This
	 * is the scripted equivalent of the `shadowrootmode` attribute. Copies inside
	 * a shadow root are not reachable by {@see getInstances}.
	 * @param mixed target element ID, DOM element, or jQuery object
	 * @param object data values keyed by placeholder path; optional
	 * @param object options shadow root options; defaults to `{mode: 'open'}`
	 * @return ShadowRoot the shadow root, or null when the target is unavailable
	 */
	attachShadowTo(target, data, options) {
		const element = Prado.WebUI.TWebTemplate.resolve(target);
		if (!element) {
			return null;
		}
		const root = element.shadowRoot || element.attachShadow({ mode: 'open', ...(options || {}) });
		root.appendChild(this.clone(data));
		return root;
	},

	// ── instances ────────────────────────────────────────────────────────────

	/**
	 * Returns every tracked instance of this template still connected to the
	 * document, in document order.
	 * @return array the instance records
	 */
	getInstances() {
		return Prado.WebUI.TWebTemplate.instancesOf(this.ID);
	},

	/**
	 * Returns a tracked instance by its UID.
	 * @param string uid the instance UID
	 * @return object the instance record, or null
	 */
	getInstance(uid) {
		for (const instance of this.getInstances()) {
			if (instance.uid === uid) {
				return instance;
			}
		}
		return null;
	},

	/**
	 * Merges new values into an instance's data and writes them into the
	 * recorded placeholder positions. Nothing outside those positions is
	 * touched, so user input, focus, and listeners inside the copy survive.
	 * @param string uid the instance UID
	 * @param object data values to merge into the instance data
	 * @return object the instance record, or null when the UID is unknown
	 */
	updateInstance(uid, data) {
		const instance = this.getInstance(uid);
		if (!instance) {
			return null;
		}
		Object.assign(instance.data, data || {});
		Prado.WebUI.TWebTemplate.applyBindings(instance.bindings, instance.data);
		this.persistInstances();
		return instance;
	},

	/**
	 * Merges the same values into every tracked instance of this template.
	 * @param object data values to merge into each instance's data
	 * @return array the updated instance records
	 */
	updateAll(data) {
		const updated = [];
		for (const instance of this.getInstances()) {
			Object.assign(instance.data, data || {});
			Prado.WebUI.TWebTemplate.applyBindings(instance.bindings, instance.data);
			updated.push(instance);
		}
		this.persistInstances();
		return updated;
	},

	/**
	 * Rebuilds an instance from the template's current content using the data it
	 * already holds, keeping its UID and its position in the document. Use this
	 * after the template markup changed; the replacement discards state held in
	 * the old nodes.
	 * @param string uid the instance UID
	 * @param object data values to merge before rebuilding; optional
	 * @return object the new instance record, or null when the UID is unknown
	 */
	refreshInstance(uid, data) {
		const previous = this.getInstance(uid);
		if (!previous) {
			return null;
		}
		const anchor = previous.roots.find((node) => node.parentNode);
		if (!anchor) {
			return null;
		}
		Object.assign(previous.data, data || {});
		const parent = anchor.parentNode;
		const stamped = this.stampFragment(previous.data, uid);
		parent.insertBefore(stamped.fragment, anchor);
		for (const node of previous.roots) {
			if (node.parentNode) {
				node.parentNode.removeChild(node);
			}
		}
		this.persistInstances();
		return stamped.instance;
	},

	/**
	 * Rebuilds every tracked instance of this template from its current content.
	 * @return array the new instance records
	 */
	refreshAll() {
		const uids = this.getInstances().map((instance) => instance.uid);
		const refreshed = [];
		for (const uid of uids) {
			const instance = this.refreshInstance(uid);
			if (instance) {
				refreshed.push(instance);
			}
		}
		return refreshed;
	},

	/**
	 * Removes an instance's nodes from the document.
	 * @param string uid the instance UID
	 * @return bool whether an instance was removed
	 */
	removeInstance(uid) {
		const instance = this.getInstance(uid);
		if (!instance) {
			return false;
		}
		for (const node of instance.roots) {
			if (node.parentNode) {
				node.parentNode.removeChild(node);
			}
		}
		this.persistInstances();
		return true;
	}
});

Object.assign(Prado.WebUI.TWebTemplate,
{
	/** Property name under which an instance record is stored on its root nodes. */
	INSTANCE_KEY: '_pradoTemplateInstance',

	/** Attribute naming the template a stamped root came from. */
	TEMPLATE_ATTRIBUTE: 'data-prado-template',

	/** Attribute holding the UID that groups the roots of one instance. */
	INSTANCE_ATTRIBUTE: 'data-prado-instance',

	/** Source of instance UIDs for this document. */
	uidCounter: 0,

	/**
	 * Returns the next instance UID.
	 * @return string a UID unique within the document
	 */
	nextUid() {
		Prado.WebUI.TWebTemplate.uidCounter += 1;
		return 'wt' + Prado.WebUI.TWebTemplate.uidCounter;
	},

	/**
	 * Returns the registered wrapper for a template element.
	 * @param string id the ClientID of the template element
	 * @return Prado.WebUI.TWebTemplate the wrapper, or undefined when not registered
	 */
	get(id) {
		return Prado.Registry[id];
	},

	/**
	 * Invokes a method on a registered template wrapper. This is the entry point
	 * {@see \Prado\Web\UI\ActiveControls\TActiveWebTemplate} calls during a
	 * callback response.
	 * @param string id the ClientID of the template element
	 * @param string method the wrapper method to invoke
	 * @param array args the arguments to pass
	 * @return mixed the method's return value, or null when it cannot be invoked
	 */
	command(id, method, args) {
		const wrapper = Prado.Registry[id];
		if (!wrapper || typeof wrapper[method] !== 'function') {
			return null;
		}
		return wrapper[method].apply(wrapper, args || []);
	},

	/**
	 * Resolves a target into a DOM element.
	 * @param mixed target element ID string, DOM element, or jQuery object
	 * @return Element the element, or null when it cannot be resolved
	 */
	resolve(target) {
		if (!target) {
			return null;
		}
		if (typeof target === 'string') {
			return document.getElementById(target);
		}
		if (target.nodeType) {
			return target;
		}
		if (target.jquery || typeof target.length === 'number') {
			return target[0] || null;
		}
		return null;
	},

	/**
	 * Resolves a dotted path against a data object.
	 * @param object data the data object
	 * @param string path a key or dotted path, e.g. `user.name`
	 * @return mixed the resolved value, or undefined
	 */
	resolvePath(data, path) {
		let value = data;
		for (const key of path.split('.')) {
			if (value === null || value === undefined) {
				return undefined;
			}
			value = value[key];
		}
		return value;
	},

	/**
	 * Replaces `{{path}}` placeholders in a string with values from the data.
	 * A path that resolves to `undefined` is left in place, which keeps typos
	 * visible; a path that resolves to `null` becomes an empty string.
	 * @param string text the text containing placeholders
	 * @param object data values keyed by placeholder path
	 * @return string the substituted text
	 */
	interpolate(text, data) {
		return text.replace(/\{\{\s*([\w.$]+)\s*\}\}/g, (token, path) => {
			const value = Prado.WebUI.TWebTemplate.resolvePath(data, path);
			if (value === undefined) {
				return token;
			}
			return value === null ? '' : String(value);
		});
	},

	/**
	 * Walks a node, records every text node and attribute holding a `{{path}}`
	 * placeholder, and writes the initial values. Each binding keeps the original
	 * source string, which the placeholders are gone from once substituted and
	 * which later updates re-interpolate.
	 * @param Node node the root node to walk
	 * @param object data values keyed by placeholder path
	 * @return array the recorded bindings
	 */
	bind(node, data) {
		const bindings = [];
		const walker = document.createTreeWalker(node, NodeFilter.SHOW_ELEMENT | NodeFilter.SHOW_TEXT);
		let current = walker.currentNode;
		while (current) {
			if (current.nodeType === Node.TEXT_NODE) {
				if (current.nodeValue.indexOf('{{') !== -1) {
					bindings.push({ node: current, source: current.nodeValue });
				}
			} else if (current.nodeType === Node.ELEMENT_NODE) {
				for (const attribute of Array.from(current.attributes)) {
					if (attribute.value.indexOf('{{') !== -1) {
						bindings.push({ node: current, attr: attribute.name, source: attribute.value });
					}
				}
			}
			current = walker.nextNode();
		}
		Prado.WebUI.TWebTemplate.applyBindings(bindings, data);
		return bindings;
	},

	/**
	 * Writes interpolated values into recorded binding positions, assigning only
	 * where the result differs from what is already there.
	 * @param array bindings the recorded bindings
	 * @param object data values keyed by placeholder path
	 */
	applyBindings(bindings, data) {
		for (const binding of bindings) {
			const value = Prado.WebUI.TWebTemplate.interpolate(binding.source, data);
			if (binding.attr) {
				if (binding.node.getAttribute(binding.attr) !== value) {
					binding.node.setAttribute(binding.attr, value);
				}
			} else if (binding.node.nodeValue !== value) {
				binding.node.nodeValue = value;
			}
		}
	},

	/**
	 * Substitutes `{{path}}` placeholders throughout a node's text nodes and
	 * attribute values, in place, without recording bindings.
	 * @param Node node the root node to walk
	 * @param object data values keyed by placeholder path
	 * @return Node the same node
	 */
	substitute(node, data) {
		Prado.WebUI.TWebTemplate.bind(node, data);
		return node;
	},

	/**
	 * Stores an instance record on each of its root nodes and tags the element
	 * roots with the template and instance attributes.
	 * @param object instance the instance record
	 * @return object the same instance record
	 */
	markInstance(instance) {
		for (const node of instance.roots) {
			Object.defineProperty(node, Prado.WebUI.TWebTemplate.INSTANCE_KEY, {
				value: instance,
				configurable: true,
				enumerable: false,
				writable: true
			});
			if (node.nodeType === Node.ELEMENT_NODE) {
				node.setAttribute(Prado.WebUI.TWebTemplate.TEMPLATE_ATTRIBUTE, instance.templateId);
				node.setAttribute(Prado.WebUI.TWebTemplate.INSTANCE_ATTRIBUTE, instance.uid);
			}
		}
		return instance;
	},

	/**
	 * Returns the instance a node belongs to, searching the node and its
	 * ancestors. Use it in an event handler to find the stamped copy that was
	 * acted on.
	 * @param Node node any node inside a stamped copy
	 * @return object the instance record, or null
	 */
	instanceOf(node) {
		let current = node;
		while (current) {
			const instance = current[Prado.WebUI.TWebTemplate.INSTANCE_KEY];
			if (instance) {
				return instance;
			}
			current = current.parentNode;
		}
		return null;
	},

	/**
	 * Returns every connected instance stamped from a template, in document
	 * order. Copies stamped into a shadow root are not reachable this way.
	 * @param string templateId the ClientID of the template element
	 * @return array the instance records
	 */
	instancesOf(templateId) {
		const selector = '[' + Prado.WebUI.TWebTemplate.TEMPLATE_ATTRIBUTE + '="' + templateId + '"]';
		const seen = [];
		const instances = [];
		for (const node of document.querySelectorAll(selector)) {
			const instance = node[Prado.WebUI.TWebTemplate.INSTANCE_KEY];
			if (instance && seen.indexOf(instance.uid) === -1) {
				seen.push(instance.uid);
				instances.push(instance);
			}
		}
		return instances;
	}
});
