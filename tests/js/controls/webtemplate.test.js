/**
 * Tests for Prado.WebUI.TWebTemplate (webtemplate.js).
 * Source: framework/Web/Javascripts/source/prado/controls/webtemplate.js
 *
 * DOM structure expected by TWebTemplate:
 *   <template id="{ID}"> … inert content … </template>
 *
 * The wrapper stamps copies of the template's DocumentFragment into targets,
 * substituting {{path}} placeholders in text nodes and attribute values.
 */

import { describe, it, expect, beforeEach, afterEach } from 'vitest';
import { TWebTemplate } from '../adapters/webtemplate.js';

// ─── helpers ─────────────────────────────────────────────────────────────────

/**
 * Creates a <template> with the given inner HTML plus a target <div>, and
 * returns a registered TWebTemplate wrapper for it.
 *
 * @param {string} html  - the template's inert content
 * @param {string} id    - the template element id
 */
function buildTemplate(html, id = 'tpl') {
	const template = document.createElement('template');
	template.id = id;
	template.innerHTML = html;
	document.body.appendChild(template);

	const target = document.createElement('div');
	target.id = 'target';
	document.body.appendChild(target);

	return new TWebTemplate({ ID: id });
}

function target() {
	return document.getElementById('target');
}

beforeEach(() => {
	document.body.innerHTML = '';
	global.Prado.Registry = {};
});

afterEach(() => {
	document.body.innerHTML = '';
	global.Prado.Registry = {};
});

// ─── registration ────────────────────────────────────────────────────────────

describe('TWebTemplate registration', () => {
	it('registers itself in Prado.Registry under its ID', () => {
		const tpl = buildTemplate('<p>hi</p>');
		expect(global.Prado.Registry['tpl']).toBe(tpl);
	});

	it('get() returns the registered wrapper', () => {
		const tpl = buildTemplate('<p>hi</p>');
		expect(TWebTemplate.get('tpl')).toBe(tpl);
	});

	it('get() returns undefined for an unknown id', () => {
		expect(TWebTemplate.get('nope')).toBeUndefined();
	});
});

// ─── content access ──────────────────────────────────────────────────────────

describe('TWebTemplate content access', () => {
	it('getContent() returns the inert DocumentFragment', () => {
		const tpl = buildTemplate('<p>hi</p>');
		const content = tpl.getContent();
		expect(content).toBeInstanceOf(DocumentFragment);
		expect(content.querySelector('p').textContent).toBe('hi');
	});

	it('template content is not part of the rendered document', () => {
		buildTemplate('<p class="inert">hi</p>');
		expect(document.querySelector('p.inert')).toBeNull();
	});

	it('find() queries inside the content', () => {
		const tpl = buildTemplate('<p class="a">A</p><p class="b">B</p>');
		expect(tpl.find('.b').textContent).toBe('B');
	});

	it('find() returns null when there is no match', () => {
		const tpl = buildTemplate('<p>hi</p>');
		expect(tpl.find('.nothing')).toBeNull();
	});

	it('findAll() returns every match as an array', () => {
		const tpl = buildTemplate('<p>A</p><p>B</p><p>C</p>');
		const found = tpl.findAll('p');
		expect(Array.isArray(found)).toBe(true);
		expect(found.map((n) => n.textContent)).toEqual(['A', 'B', 'C']);
	});
});

// ─── clone ───────────────────────────────────────────────────────────────────

describe('TWebTemplate clone', () => {
	it('returns a detached copy, leaving the original content intact', () => {
		const tpl = buildTemplate('<p>hi</p>');
		const fragment = tpl.clone();
		expect(fragment).toBeInstanceOf(DocumentFragment);
		expect(fragment.querySelector('p').textContent).toBe('hi');
		// original still holds its single <p>
		expect(tpl.getContent().querySelectorAll('p').length).toBe(1);
	});

	it('mutating a clone does not affect the template', () => {
		const tpl = buildTemplate('<p>hi</p>');
		const fragment = tpl.clone();
		fragment.querySelector('p').textContent = 'changed';
		expect(tpl.find('p').textContent).toBe('hi');
	});

	it('substitutes placeholders when data is given', () => {
		const tpl = buildTemplate('<p>{{name}}</p>');
		expect(tpl.clone({ name: 'Ada' }).querySelector('p').textContent).toBe('Ada');
	});

	it('leaves the template content unsubstituted after stamping', () => {
		const tpl = buildTemplate('<p>{{name}}</p>');
		tpl.clone({ name: 'Ada' });
		expect(tpl.find('p').textContent).toBe('{{name}}');
	});
});

// ─── interpolation ───────────────────────────────────────────────────────────

describe('TWebTemplate interpolation', () => {
	it('replaces a simple placeholder', () => {
		expect(TWebTemplate.interpolate('Hello {{name}}', { name: 'Ada' })).toBe('Hello Ada');
	});

	it('replaces repeated placeholders', () => {
		expect(TWebTemplate.interpolate('{{a}}-{{a}}', { a: 'x' })).toBe('x-x');
	});

	it('tolerates whitespace inside the braces', () => {
		expect(TWebTemplate.interpolate('{{ name }}', { name: 'Ada' })).toBe('Ada');
	});

	it('resolves dotted paths', () => {
		expect(TWebTemplate.interpolate('{{user.name}}', { user: { name: 'Ada' } })).toBe('Ada');
	});

	it('leaves undefined paths in place so typos stay visible', () => {
		expect(TWebTemplate.interpolate('{{missing}}', { name: 'Ada' })).toBe('{{missing}}');
	});

	it('leaves a broken dotted path in place', () => {
		expect(TWebTemplate.interpolate('{{user.name}}', {})).toBe('{{user.name}}');
	});

	it('renders null as an empty string', () => {
		expect(TWebTemplate.interpolate('[{{v}}]', { v: null })).toBe('[]');
	});

	it('renders numbers and false', () => {
		expect(TWebTemplate.interpolate('{{n}}/{{b}}', { n: 0, b: false })).toBe('0/false');
	});

	it('resolvePath() returns undefined past a null', () => {
		expect(TWebTemplate.resolvePath({ a: null }, 'a.b')).toBeUndefined();
	});
});

// ─── substitution safety ─────────────────────────────────────────────────────

describe('TWebTemplate substitution', () => {
	it('substitutes inside attribute values', () => {
		const tpl = buildTemplate('<a href="/user/{{id}}" title="{{name}}">x</a>');
		const link = tpl.clone({ id: 7, name: 'Ada' }).querySelector('a');
		expect(link.getAttribute('href')).toBe('/user/7');
		expect(link.getAttribute('title')).toBe('Ada');
	});

	it('substitutes in nested text nodes', () => {
		const tpl = buildTemplate('<div><span><b>{{deep}}</b></span></div>');
		expect(tpl.clone({ deep: 'found' }).querySelector('b').textContent).toBe('found');
	});

	it('assigns data as text, never as markup', () => {
		const tpl = buildTemplate('<p>{{value}}</p>');
		const p = tpl.clone({ value: '<img src=x onerror=alert(1)>' }).querySelector('p');
		expect(p.querySelector('img')).toBeNull();
		expect(p.textContent).toBe('<img src=x onerror=alert(1)>');
	});

	it('assigns data into attributes as text, not extra attributes', () => {
		const tpl = buildTemplate('<a title="{{value}}">x</a>');
		const link = tpl.clone({ value: '" onmouseover="alert(1)' }).querySelector('a');
		expect(link.getAttribute('title')).toBe('" onmouseover="alert(1)');
		expect(link.hasAttribute('onmouseover')).toBe(false);
	});
});

// ─── stamping ────────────────────────────────────────────────────────────────

describe('TWebTemplate stamping', () => {
	it('appendTo() adds content as the last child', () => {
		const tpl = buildTemplate('<p class="stamped">{{v}}</p>');
		target().innerHTML = '<span class="existing"></span>';
		tpl.appendTo('target', { v: 'A' });
		expect(target().children.length).toBe(2);
		expect(target().lastElementChild.className).toBe('stamped');
	});

	it('appendTo() returns the inserted top-level nodes', () => {
		const tpl = buildTemplate('<p>A</p><p>B</p>');
		const nodes = tpl.appendTo('target');
		expect(nodes.length).toBe(2);
		expect(nodes[0].parentNode).toBe(target());
	});

	it('prependTo() adds content as the first child', () => {
		const tpl = buildTemplate('<p class="stamped">x</p>');
		target().innerHTML = '<span class="existing"></span>';
		tpl.prependTo('target');
		expect(target().firstElementChild.className).toBe('stamped');
	});

	it('replaceContentOf() clears existing children first', () => {
		const tpl = buildTemplate('<p class="stamped">x</p>');
		target().innerHTML = '<span class="existing"></span><span class="existing"></span>';
		tpl.replaceContentOf('target');
		expect(target().querySelectorAll('.existing').length).toBe(0);
		expect(target().querySelectorAll('.stamped').length).toBe(1);
	});

	it('insertBefore() places content before the target', () => {
		const tpl = buildTemplate('<p class="stamped">x</p>');
		tpl.insertBefore('target');
		expect(target().previousElementSibling.className).toBe('stamped');
	});

	it('insertAfter() places content after the target', () => {
		const tpl = buildTemplate('<p class="stamped">x</p>');
		tpl.insertAfter('target');
		expect(target().nextElementSibling.className).toBe('stamped');
	});

	it('accepts a DOM element as the target', () => {
		const tpl = buildTemplate('<p>x</p>');
		tpl.appendTo(target());
		expect(target().children.length).toBe(1);
	});

	it('accepts an array-like (jQuery style) target', () => {
		const tpl = buildTemplate('<p>x</p>');
		tpl.appendTo([target()]);
		expect(target().children.length).toBe(1);
	});

	it('returns an empty array for an unresolvable target', () => {
		const tpl = buildTemplate('<p>x</p>');
		expect(tpl.appendTo('does-not-exist')).toEqual([]);
	});

	it('resolve() returns null for null and unknown ids', () => {
		expect(TWebTemplate.resolve(null)).toBeNull();
		expect(TWebTemplate.resolve('does-not-exist')).toBeNull();
	});
});

// ─── repeatInto ──────────────────────────────────────────────────────────────

describe('TWebTemplate repeatInto', () => {
	it('stamps one copy per data item', () => {
		const tpl = buildTemplate('<p class="row">{{name}}</p>');
		tpl.repeatInto('target', [{ name: 'Ada' }, { name: 'Grace' }, { name: 'Alan' }]);
		const rows = target().querySelectorAll('.row');
		expect(rows.length).toBe(3);
		expect([...rows].map((r) => r.textContent)).toEqual(['Ada', 'Grace', 'Alan']);
	});

	it('clears the target by default', () => {
		const tpl = buildTemplate('<p class="row">x</p>');
		target().innerHTML = '<span class="existing"></span>';
		tpl.repeatInto('target', [{}]);
		expect(target().querySelectorAll('.existing').length).toBe(0);
	});

	it('keeps existing children when keep is true', () => {
		const tpl = buildTemplate('<p class="row">x</p>');
		target().innerHTML = '<span class="existing"></span>';
		tpl.repeatInto('target', [{}], true);
		expect(target().querySelectorAll('.existing').length).toBe(1);
		expect(target().querySelectorAll('.row').length).toBe(1);
	});

	it('returns every inserted node', () => {
		const tpl = buildTemplate('<p>x</p>');
		expect(tpl.repeatInto('target', [{}, {}]).length).toBe(2);
	});

	it('empties the target for an empty data array', () => {
		const tpl = buildTemplate('<p>x</p>');
		target().innerHTML = '<span></span>';
		expect(tpl.repeatInto('target', [])).toEqual([]);
		expect(target().children.length).toBe(0);
	});

	it('treats a missing data array as empty', () => {
		const tpl = buildTemplate('<p>x</p>');
		expect(tpl.repeatInto('target')).toEqual([]);
	});
});

// ─── instance tracking ───────────────────────────────────────────────────────

describe('TWebTemplate instance tracking', () => {
	it('records an instance for a stamped copy', () => {
		const tpl = buildTemplate('<p>{{name}}</p>');
		const nodes = tpl.appendTo('target', { name: 'Ada' });
		expect(nodes.pradoInstance).toBeTruthy();
		expect(nodes.pradoInstance.data).toEqual({ name: 'Ada' });
		expect(nodes.pradoInstance.templateId).toBe('tpl');
	});

	it('tags element roots with the template and instance attributes', () => {
		const tpl = buildTemplate('<p>{{name}}</p>');
		const uid = tpl.appendTo('target', { name: 'Ada' }).pradoInstance.uid;
		const root = target().firstElementChild;
		expect(root.getAttribute('data-prado-template')).toBe('tpl');
		expect(root.getAttribute('data-prado-instance')).toBe(uid);
	});

	it('gives each copy a distinct UID', () => {
		const tpl = buildTemplate('<p>{{name}}</p>');
		const a = tpl.appendTo('target', { name: 'A' }).pradoInstance.uid;
		const b = tpl.appendTo('target', { name: 'B' }).pradoInstance.uid;
		expect(a).not.toBe(b);
	});

	it('groups every root of a multi-root template under one UID', () => {
		const tpl = buildTemplate('<p class="a">{{v}}</p><p class="b">{{v}}</p>');
		const instance = tpl.appendTo('target', { v: 'x' }).pradoInstance;
		expect(instance.roots.filter((n) => n.nodeType === 1).length).toBe(2);
		const uids = [...target().querySelectorAll('[data-prado-instance]')].map((n) =>
			n.getAttribute('data-prado-instance')
		);
		expect(uids).toEqual([instance.uid, instance.uid]);
	});

	it('getInstances() returns connected instances in document order', () => {
		const tpl = buildTemplate('<p>{{name}}</p>');
		tpl.appendTo('target', { name: 'A' });
		tpl.appendTo('target', { name: 'B' });
		expect(tpl.getInstances().map((i) => i.data.name)).toEqual(['A', 'B']);
	});

	it('getInstances() drops instances removed from the document', () => {
		const tpl = buildTemplate('<p>{{name}}</p>');
		const uid = tpl.appendTo('target', { name: 'A' }).pradoInstance.uid;
		tpl.appendTo('target', { name: 'B' });
		expect(tpl.getInstances().length).toBe(2);
		tpl.removeInstance(uid);
		expect(tpl.getInstances().map((i) => i.data.name)).toEqual(['B']);
	});

	it('getInstance() finds an instance by UID', () => {
		const tpl = buildTemplate('<p>{{name}}</p>');
		const uid = tpl.appendTo('target', { name: 'Ada' }).pradoInstance.uid;
		expect(tpl.getInstance(uid).data.name).toBe('Ada');
		expect(tpl.getInstance('nope')).toBeNull();
	});

	it('instanceOf() finds the instance a descendant node belongs to', () => {
		const tpl = buildTemplate('<div class="row"><span class="cell">{{v}}</span></div>');
		const uid = tpl.appendTo('target', { v: 'x' }).pradoInstance.uid;
		const cell = target().querySelector('.cell');
		expect(TWebTemplate.instanceOf(cell).uid).toBe(uid);
	});

	it('instanceOf() returns null outside any instance', () => {
		buildTemplate('<p>x</p>');
		expect(TWebTemplate.instanceOf(target())).toBeNull();
	});

	it('does not track when TrackInstances is false', () => {
		const template = document.createElement('template');
		template.id = 'untracked';
		template.innerHTML = '<p>{{v}}</p>';
		document.body.appendChild(template);
		const div = document.createElement('div');
		div.id = 'target';
		document.body.appendChild(div);

		const tpl = new TWebTemplate({ ID: 'untracked', TrackInstances: false });
		const nodes = tpl.appendTo('target', { v: 'x' });
		expect(nodes.pradoInstance).toBeNull();
		expect(tpl.getInstances()).toEqual([]);
		expect(target().firstElementChild.hasAttribute('data-prado-instance')).toBe(false);
	});
});

// ─── in-place updates ────────────────────────────────────────────────────────

describe('TWebTemplate updateInstance', () => {
	it('writes new values into the stamped copy', () => {
		const tpl = buildTemplate('<p>{{name}}</p>');
		const uid = tpl.appendTo('target', { name: 'Ada' }).pradoInstance.uid;
		tpl.updateInstance(uid, { name: 'Grace' });
		expect(target().querySelector('p').textContent).toBe('Grace');
	});

	it('merges rather than replaces the instance data', () => {
		const tpl = buildTemplate('<p>{{a}}/{{b}}</p>');
		const uid = tpl.appendTo('target', { a: '1', b: '2' }).pradoInstance.uid;
		tpl.updateInstance(uid, { b: '9' });
		expect(target().querySelector('p').textContent).toBe('1/9');
	});

	it('updates attribute bindings', () => {
		const tpl = buildTemplate('<a href="/user/{{id}}">x</a>');
		const uid = tpl.appendTo('target', { id: 1 }).pradoInstance.uid;
		tpl.updateInstance(uid, { id: 42 });
		expect(target().querySelector('a').getAttribute('href')).toBe('/user/42');
	});

	it('keeps the same DOM nodes, preserving state held on them', () => {
		const tpl = buildTemplate('<div><span class="v">{{v}}</span><input class="typed"></div>');
		const uid = tpl.appendTo('target', { v: 'first' }).pradoInstance.uid;

		const span = target().querySelector('.v');
		const input = target().querySelector('.typed');
		input.value = 'user typing';
		let clicks = 0;
		input.addEventListener('click', () => {
			clicks += 1;
		});

		tpl.updateInstance(uid, { v: 'second' });

		expect(target().querySelector('.v')).toBe(span);
		expect(target().querySelector('.typed')).toBe(input);
		expect(input.value).toBe('user typing');
		input.click();
		expect(clicks).toBe(1);
	});

	it('leaves nodes added by other code alone', () => {
		const tpl = buildTemplate('<div class="row"><span class="v">{{v}}</span></div>');
		const uid = tpl.appendTo('target', { v: 'a' }).pradoInstance.uid;
		const extra = document.createElement('b');
		extra.className = 'injected';
		target().querySelector('.row').appendChild(extra);

		tpl.updateInstance(uid, { v: 'b' });
		expect(target().querySelectorAll('.injected').length).toBe(1);
	});

	it('returns null for an unknown UID', () => {
		const tpl = buildTemplate('<p>{{v}}</p>');
		expect(tpl.updateInstance('nope', { v: 'x' })).toBeNull();
	});

	it('updateAll() merges into every instance', () => {
		const tpl = buildTemplate('<p>{{name}}-{{tag}}</p>');
		tpl.appendTo('target', { name: 'A', tag: 'old' });
		tpl.appendTo('target', { name: 'B', tag: 'old' });
		tpl.updateAll({ tag: 'new' });
		expect([...target().querySelectorAll('p')].map((p) => p.textContent)).toEqual([
			'A-new',
			'B-new'
		]);
	});
});

// ─── refresh against changed markup ──────────────────────────────────────────

describe('TWebTemplate refreshInstance', () => {
	it('rebuilds from the current content using the stored data', () => {
		const tpl = buildTemplate('<p class="old">{{name}}</p>');
		const uid = tpl.appendTo('target', { name: 'Ada' }).pradoInstance.uid;

		// the template markup changes, as a server re-render would do
		document.getElementById('tpl').innerHTML = '<div class="new"><em>{{name}}</em></div>';

		tpl.refreshInstance(uid);
		expect(target().querySelector('.old')).toBeNull();
		expect(target().querySelector('.new em').textContent).toBe('Ada');
	});

	it('keeps the UID and the position among siblings', () => {
		const tpl = buildTemplate('<p>{{v}}</p>');
		tpl.appendTo('target', { v: 'first' });
		const uid = tpl.appendTo('target', { v: 'middle' }).pradoInstance.uid;
		tpl.appendTo('target', { v: 'last' });

		document.getElementById('tpl').innerHTML = '<p>[{{v}}]</p>';
		const refreshed = tpl.refreshInstance(uid);

		expect(refreshed.uid).toBe(uid);
		expect([...target().querySelectorAll('p')].map((p) => p.textContent)).toEqual([
			'first',
			'[middle]',
			'last'
		]);
	});

	it('merges data passed to the refresh', () => {
		const tpl = buildTemplate('<p>{{v}}</p>');
		const uid = tpl.appendTo('target', { v: 'a' }).pradoInstance.uid;
		tpl.refreshInstance(uid, { v: 'b' });
		expect(target().querySelector('p').textContent).toBe('b');
	});

	it('replaces every root of a multi-root instance', () => {
		const tpl = buildTemplate('<p class="a">{{v}}</p><p class="b">{{v}}</p>');
		const uid = tpl.appendTo('target', { v: 'x' }).pradoInstance.uid;
		expect(target().children.length).toBe(2);

		document.getElementById('tpl').innerHTML = '<p class="c">{{v}}</p>';
		tpl.refreshInstance(uid);

		expect(target().children.length).toBe(1);
		expect(target().querySelector('.c').textContent).toBe('x');
	});

	it('refreshAll() rebuilds every instance', () => {
		const tpl = buildTemplate('<p>{{v}}</p>');
		tpl.appendTo('target', { v: '1' });
		tpl.appendTo('target', { v: '2' });

		document.getElementById('tpl').innerHTML = '<p>#{{v}}</p>';
		expect(tpl.refreshAll().length).toBe(2);
		expect([...target().querySelectorAll('p')].map((p) => p.textContent)).toEqual(['#1', '#2']);
	});

	it('returns null for an unknown UID', () => {
		const tpl = buildTemplate('<p>{{v}}</p>');
		expect(tpl.refreshInstance('nope')).toBeNull();
	});
});

// ─── instance persistence ────────────────────────────────────────────────────

/**
 * Builds a persisting template: <template>, target div, and the hidden field
 * the PHP control registers (id = templateId + '_instances').
 */
function buildPersistingTemplate(html, fieldValue = '', id = 'tpl') {
	const template = document.createElement('template');
	template.id = id;
	template.innerHTML = html;
	document.body.appendChild(template);

	const target = document.createElement('div');
	target.id = 'target';
	document.body.appendChild(target);

	const field = document.createElement('input');
	field.type = 'hidden';
	field.id = id + '_instances';
	field.value = fieldValue;
	document.body.appendChild(field);

	return new TWebTemplate({ ID: id, PersistInstances: true });
}

function persistField() {
	return document.getElementById('tpl_instances');
}

describe('TWebTemplate instance persistence', () => {
	it('serializes each stamp into the hidden field', () => {
		const tpl = buildPersistingTemplate('<p>{{name}}</p>');
		const uid = tpl.appendTo('target', { name: 'Ada' }).pradoInstance.uid;
		const records = JSON.parse(persistField().value);
		expect(records).toEqual([{ uid, target: 'target', data: { name: 'Ada' } }]);
	});

	it('updates the field on updateInstance and removeInstance', () => {
		const tpl = buildPersistingTemplate('<p>{{name}}</p>');
		const uid = tpl.appendTo('target', { name: 'Ada' }).pradoInstance.uid;

		tpl.updateInstance(uid, { name: 'Grace' });
		expect(JSON.parse(persistField().value)[0].data.name).toBe('Grace');

		tpl.removeInstance(uid);
		expect(JSON.parse(persistField().value)).toEqual([]);
	});

	it('skips instances whose parent has no id', () => {
		const tpl = buildPersistingTemplate('<p>{{v}}</p>');
		const anonymous = document.createElement('div');
		document.body.appendChild(anonymous);
		tpl.appendTo('target', { v: 'kept' });
		tpl.appendTo(anonymous, { v: 'unrestorable' });
		const records = JSON.parse(persistField().value);
		expect(records.length).toBe(1);
		expect(records[0].data.v).toBe('kept');
	});

	it('restores instances from the field at initialization', () => {
		const state = JSON.stringify([
			{ uid: 'wt7', target: 'target', data: { name: 'Ada' } },
			{ uid: 'wt9', target: 'target', data: { name: 'Grace' } }
		]);
		const tpl = buildPersistingTemplate('<p class="row">{{name}}</p>', state);

		const rows = document.querySelectorAll('#target .row');
		expect([...rows].map((r) => r.textContent)).toEqual(['Ada', 'Grace']);
		expect(tpl.getInstance('wt7').data.name).toBe('Ada');
	});

	it('restored instances are updatable under their original UID', () => {
		const state = JSON.stringify([{ uid: 'wt7', target: 'target', data: { name: 'Ada' } }]);
		const tpl = buildPersistingTemplate('<p class="row">{{name}}</p>', state);
		tpl.updateInstance('wt7', { name: 'Grace' });
		expect(document.querySelector('#target .row').textContent).toBe('Grace');
	});

	it('restore is idempotent — re-init does not duplicate live instances', () => {
		// A callback response re-emits the wrapper boot script, re-running onInit
		// (hence restoreInstances). Instances already in the DOM must not be
		// stamped a second time.
		const state = JSON.stringify([{ uid: 'wt7', target: 'target', data: { name: 'Ada' } }]);
		const tpl = buildPersistingTemplate('<p class="row">{{name}}</p>', state);
		expect(document.querySelectorAll('#target .row').length).toBe(1);

		// simulate the wrapper re-registering over the same element
		tpl.restoreInstances();
		expect(document.querySelectorAll('#target .row').length).toBe(1);
		expect(tpl.getInstances().length).toBe(1);
	});

	it('bumps the UID counter past restored UIDs', () => {
		const state = JSON.stringify([{ uid: 'wt41', target: 'target', data: {} }]);
		const tpl = buildPersistingTemplate('<p>{{v}}</p>', state);
		const fresh = tpl.appendTo('target', { v: 'x' }).pradoInstance.uid;
		expect(parseInt(fresh.slice(2), 10)).toBeGreaterThan(41);
	});

	it('skips records whose target is missing', () => {
		const state = JSON.stringify([
			{ uid: 'wt1', target: 'gone', data: {} },
			{ uid: 'wt2', target: 'target', data: { v: 'here' } }
		]);
		const tpl = buildPersistingTemplate('<p>{{v}}</p>', state);
		expect(tpl.getInstances().length).toBe(1);
		expect(document.querySelector('#target p').textContent).toBe('here');
	});

	it('tolerates malformed field content', () => {
		const tpl = buildPersistingTemplate('<p>{{v}}</p>', 'not json');
		expect(tpl.getInstances()).toEqual([]);
	});

	it('does not persist when the option is off', () => {
		const template = document.createElement('template');
		template.id = 'tpl';
		template.innerHTML = '<p>{{v}}</p>';
		document.body.appendChild(template);
		const target = document.createElement('div');
		target.id = 'target';
		document.body.appendChild(target);
		const field = document.createElement('input');
		field.type = 'hidden';
		field.id = 'tpl_instances';
		field.value = '';
		document.body.appendChild(field);

		const tpl = new TWebTemplate({ ID: 'tpl' });
		tpl.appendTo('target', { v: 'x' });
		expect(persistField().value).toBe('');
	});
});

// ─── setContent / command (the TActiveWebTemplate entry points) ──────────────

describe('TWebTemplate setContent', () => {
	it('replaces the inert content', () => {
		const tpl = buildTemplate('<p class="old">{{v}}</p>');
		expect(tpl.setContent('<div class="new">{{v}}</div>')).toBe(true);
		expect(tpl.find('.old')).toBeNull();
		expect(tpl.find('.new')).not.toBeNull();
	});

	it('later stamps use the new content', () => {
		const tpl = buildTemplate('<p class="old">{{v}}</p>');
		tpl.setContent('<div class="new">{{v}}</div>');
		tpl.appendTo('target', { v: 'x' });
		expect(target().querySelector('.new').textContent).toBe('x');
	});

	it('leaves already-stamped copies untouched', () => {
		const tpl = buildTemplate('<p class="old">{{v}}</p>');
		tpl.appendTo('target', { v: 'x' });
		tpl.setContent('<div class="new">{{v}}</div>');
		expect(target().querySelector('.old')).not.toBeNull();
	});

	it('refreshAll() rebuilds copies after setContent', () => {
		const tpl = buildTemplate('<p class="old">{{v}}</p>');
		tpl.appendTo('target', { v: 'x' });
		tpl.setContent('<div class="new">{{v}}</div>');
		tpl.refreshAll();
		expect(target().querySelector('.old')).toBeNull();
		expect(target().querySelector('.new').textContent).toBe('x');
	});
});

describe('TWebTemplate command dispatch', () => {
	it('invokes a wrapper method by name', () => {
		buildTemplate('<p>{{v}}</p>');
		TWebTemplate.command('tpl', 'appendTo', ['target', { v: 'commanded' }]);
		expect(target().querySelector('p').textContent).toBe('commanded');
	});

	it('returns the method result', () => {
		buildTemplate('<p>{{v}}</p>');
		const nodes = TWebTemplate.command('tpl', 'appendTo', ['target', { v: 'x' }]);
		expect(nodes.length).toBe(1);
	});

	it('dispatches an instance update', () => {
		const tpl = buildTemplate('<p>{{v}}</p>');
		const uid = tpl.appendTo('target', { v: 'before' }).pradoInstance.uid;
		TWebTemplate.command('tpl', 'updateInstance', [uid, { v: 'after' }]);
		expect(target().querySelector('p').textContent).toBe('after');
	});

	it('tolerates an unknown template id', () => {
		expect(TWebTemplate.command('nope', 'appendTo', ['target', {}])).toBeNull();
	});

	it('tolerates an unknown method', () => {
		buildTemplate('<p>x</p>');
		expect(TWebTemplate.command('tpl', 'noSuchMethod', [])).toBeNull();
	});

	it('tolerates missing arguments', () => {
		const tpl = buildTemplate('<p>x</p>');
		tpl.appendTo('target', {});
		expect(TWebTemplate.command('tpl', 'refreshAll')).toHaveLength(1);
	});
});

// ─── shadow DOM ──────────────────────────────────────────────────────────────

describe('TWebTemplate attachShadowTo', () => {
	it('attaches an open shadow root and stamps into it', () => {
		const tpl = buildTemplate('<p class="shadowed">{{v}}</p>');
		const root = tpl.attachShadowTo('target', { v: 'inside' });
		expect(root).toBe(target().shadowRoot);
		expect(root.querySelector('.shadowed').textContent).toBe('inside');
	});

	it('reuses an existing shadow root', () => {
		const tpl = buildTemplate('<p>x</p>');
		const first = tpl.attachShadowTo('target');
		const second = tpl.attachShadowTo('target');
		expect(second).toBe(first);
		expect(first.querySelectorAll('p').length).toBe(2);
	});

	it('returns null for an unresolvable target', () => {
		const tpl = buildTemplate('<p>x</p>');
		expect(tpl.attachShadowTo('does-not-exist')).toBeNull();
	});
});
