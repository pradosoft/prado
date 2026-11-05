/**
 * Tests for Prado.WebUI.TTeleport (teleport.js).
 * Source: framework/Web/Javascripts/source/prado/controls/teleport.js
 *
 * Strategy
 * --------
 * The document holds a page form and, after it, the content element of a
 * TTeleport, as the server renders them. `new TTeleport({ID, FormID})` sets the
 * `form` attribute of the fields; the MutationObserver cases await a microtask.
 * `TTeleport.replace` is called with a stand-in request whose extractContent()
 * returns the boundary content.
 *
 * ESM note: only tests/js/adapters/teleport.js changes on ESM conversion.
 */

import { TTeleport } from '../adapters/teleport.js';

const tick = () => new Promise((resolve) => setTimeout(resolve, 0));

let control;

beforeEach(() => {
	document.body.innerHTML =
		'<form id="PageForm" method="post"><input id="inForm" name="inForm"></form>' +
		'<div id="Layer" data-prado-teleport="">' +
			'<input id="name" name="name"><select id="pick" name="pick"></select><textarea id="note" name="note"></textarea>' +
			'<button id="save" name="save">Save</button><fieldset id="group"><output id="out"></output></fieldset>' +
			'<input id="own" name="own" form="Other">' +
			'<dialog id="dlg"><form method="dialog" id="dialogForm"><input id="answer" name="answer"><button id="ok" value="ok">OK</button></form></dialog>' +
			'<template id="tpl"><input id="inTemplate"></template>' +
		'</div>';
	control = null;
});

afterEach(() => {
	if (control && control.registered)
		control.deinitialize();
	document.body.innerHTML = '';
});

const formOf = (id) => document.getElementById(id).getAttribute('form');

describe('Prado.WebUI.TTeleport', () => {
	it('associates the fields and buttons of the content with the page form', () => {
		control = new TTeleport({ ID: 'Layer', FormID: 'PageForm' });
		for (const id of ['name', 'pick', 'note', 'save', 'group', 'out'])
			expect(formOf(id)).toBe('PageForm');
		const form = document.getElementById('PageForm');
		expect(document.getElementById('name').form).toBe(form);
		expect(Array.from(form.elements).map((el) => el.id)).toContain('save');
	});

	it('leaves a field of a nested dialog form, a field with its own form, and template content alone', () => {
		control = new TTeleport({ ID: 'Layer', FormID: 'PageForm' });
		expect(formOf('answer')).toBeNull();
		expect(formOf('ok')).toBeNull();
		expect(document.getElementById('answer').form).toBe(document.getElementById('dialogForm'));
		expect(formOf('own')).toBe('Other');
		expect(document.getElementById('tpl').content.getElementById('inTemplate').hasAttribute('form')).toBe(false);
		expect(formOf('inForm')).toBeNull();
	});

	it('does nothing without its element or the page form', () => {
		control = new TTeleport({ ID: 'Layer', FormID: 'Missing' });
		expect(formOf('name')).toBeNull();
		expect(control.mutations).toBeUndefined();
	});

	it('associates a field that is added later', async () => {
		control = new TTeleport({ ID: 'Layer', FormID: 'PageForm' });
		document.getElementById('Layer').insertAdjacentHTML('beforeend', '<span><input id="later" name="later"></span>');
		await tick();
		expect(formOf('later')).toBe('PageForm');
	});

	it('restores a removed form attribute, as after a morph', async () => {
		control = new TTeleport({ ID: 'Layer', FormID: 'PageForm' });
		document.getElementById('name').removeAttribute('form');
		await tick();
		expect(formOf('name')).toBe('PageForm');
	});

	it('stops observing once deinitialized', async () => {
		control = new TTeleport({ ID: 'Layer', FormID: 'PageForm' });
		control.deinitialize();
		document.getElementById('Layer').insertAdjacentHTML('beforeend', '<input id="after" name="after">');
		await tick();
		expect(formOf('after')).toBeNull();
		expect(control.mutations).toBeNull();
	});

	it('replaces the observer of a control registered again for a new element', async () => {
		const first = new TTeleport({ ID: 'Layer', FormID: 'PageForm' });
		const old = document.getElementById('Layer');
		const fresh = document.createElement('div');
		fresh.id = 'Layer';
		fresh.innerHTML = '<input id="fresh" name="fresh">';
		old.replaceWith(fresh);
		control = new TTeleport({ ID: 'Layer', FormID: 'PageForm' });
		expect(first.registered).toBe(false);
		expect(first.mutations).toBeNull();
		expect(formOf('fresh')).toBe('PageForm');
	});
});

describe('Prado.WebUI.TTeleport.replace', () => {
	const request = (html) => ({ extractContent: () => html });

	it('replaces the existing content element', () => {
		const old = document.getElementById('Layer');
		TTeleport.replace.call(request('<div id="Layer" data-prado-teleport=""><p id="new">new</p></div>'), 'Layer', 'PageForm', null, 'b1');
		expect(document.getElementById('Layer')).not.toBe(old);
		expect(document.getElementById('new').textContent).toBe('new');
		expect(document.getElementById('PageForm').nextElementSibling.id).toBe('Layer');
	});

	it('adds a missing content element after the page form and the teleports that follow it', () => {
		TTeleport.replace.call(request(null), 'Second', 'PageForm', '<div id="Second" data-prado-teleport=""><p id="second">2</p></div>', null);
		const form = document.getElementById('PageForm');
		expect(form.nextElementSibling.id).toBe('Layer');
		expect(document.getElementById('Layer').nextElementSibling.id).toBe('Second');
		expect(document.getElementById('second').textContent).toBe('2');
	});

	it('does nothing for a missing element without the page form', () => {
		TTeleport.replace.call(request(null), 'Second', 'Missing', '<div id="Second"></div>', null);
		expect(document.getElementById('Second')).toBeNull();
	});
});
