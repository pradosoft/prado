/**
 * Tests for the document events of a callback response (ajax3.js).
 * Source: framework/Web/Javascripts/source/prado/activecontrols/ajax3.js
 */

import { describe, it, expect, afterEach } from 'vitest';
import { CallbackRequest, CallbackRequestManager } from '../adapters/ajax.js';

function respond(headers) {
	const request = new CallbackRequest('TargetID', { EnablePageStateUpdate: false });
	let data = '';
	for (const [name, value] of Object.entries(headers)) {
		data += `<!--${name}-->${value}<!--//${name}-->`;
	}
	request.successHandler(data, 'success', { status: 200, responseText: data });
	return request;
}

const listeners = [];

function listen(type) {
	const events = [];
	const handler = (e) => events.push(e);
	document.addEventListener(type, handler);
	listeners.push([type, handler]);
	return events;
}

afterEach(() => {
	for (const [type, handler] of listeners.splice(0)) {
		document.removeEventListener(type, handler);
	}
	delete window.__callbackOrder;
});

describe('Prado.CallbackRequest response events', () => {
	it('dispatches prado:callbackUpdated after the actions run', () => {
		window.__callbackOrder = [];
		window.__recordAction = () => window.__callbackOrder.push('action');
		document.addEventListener('prado:callbackUpdated', () => window.__callbackOrder.push('updated'), { once: true });
		const request = respond({ [CallbackRequestManager.ACTION_HEADER]: JSON.stringify([{ '__recordAction': [] }]) });
		expect(window.__callbackOrder).toEqual(['action', 'updated']);
		delete window.__recordAction;
		expect(request).toBeInstanceOf(CallbackRequest);
	});

	it('passes the request in the prado:callbackUpdated detail', () => {
		const events = listen('prado:callbackUpdated');
		const request = respond({});
		expect(events).toHaveLength(1);
		expect(events[0].detail.request).toBe(request);
	});

	it('dispatches prado:callbackRedirect before a redirect', () => {
		const events = listen('prado:callbackRedirect');
		respond({ [CallbackRequestManager.REDIRECT_HEADER]: '#moved' });
		expect(events).toHaveLength(1);
		expect(events[0].detail.url).toBe('#moved');
	});

	it('dispatches no prado:callbackRedirect without a redirect', () => {
		const events = listen('prado:callbackRedirect');
		respond({});
		expect(events).toHaveLength(0);
	});
});
