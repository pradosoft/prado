/**
 * Tests for Prado.CallbackRequest getter/setter pairs and extractContent().
 * Source: framework/Web/Javascripts/source/prado/activecontrols/ajax3.js
 *
 * Strategy: CallbackRequest is instantiated with a target ID and options.
 * DOM-heavy methods (dispatch, successHandler) are not tested here; only the
 * pure-logic getters/setters and the boundary-parsing utility are covered.
 */

import { CallbackRequest, CallbackRequestManager } from '../adapters/ajax.js';

// ─── Helpers ──────────────────────────────────────────────────────────────────

/**
 * Build a minimal CallbackRequest without triggering dispatch().
 * We pass an empty options object so the constructor just merges defaults.
 */
function makeRequest(opts = {}) {
	return new CallbackRequest('TargetID', opts);
}

// ─── Default option values ────────────────────────────────────────────────────

describe('Prado.CallbackRequest — default options', () => {
	it('defaults CausesValidation to true', () => {
		expect(makeRequest().getCausesValidation()).toBe(true);
	});

	it('defaults RetryLimit to 1', () => {
		expect(makeRequest().getRetryLimit()).toBe(1);
	});

	it('defaults RequestTimeOut to 30000', () => {
		expect(makeRequest().getRequestTimeOut()).toBe(30000);
	});

	it('defaults ValidationGroup to null', () => {
		expect(makeRequest().getValidationGroup()).toBeNull();
	});
});

// ─── Getter / setter round-trips ─────────────────────────────────────────────

describe('Prado.CallbackRequest.setCallbackParameter / getCallbackParameter', () => {
	it('round-trips a string value (serialised as JSON)', () => {
		const req = makeRequest();
		req.setCallbackParameter('hello');
		expect(req.getCallbackParameter()).toBe(JSON.stringify('hello'));
	});

	it('round-trips an object value', () => {
		const req = makeRequest();
		req.setCallbackParameter({ x: 1, y: 2 });
		expect(req.getCallbackParameter()).toBe(JSON.stringify({ x: 1, y: 2 }));
	});

	it('round-trips null', () => {
		const req = makeRequest();
		req.setCallbackParameter(null);
		expect(req.getCallbackParameter()).toBe(JSON.stringify(null));
	});
});

describe('Prado.CallbackRequest.setRequestTimeOut / getRequestTimeOut', () => {
	it('round-trips an integer timeout', () => {
		const req = makeRequest();
		req.setRequestTimeOut(5000);
		expect(req.getRequestTimeOut()).toBe(5000);
	});
});

describe('Prado.CallbackRequest.setCausesValidation / getCausesValidation', () => {
	it('round-trips true', () => {
		const req = makeRequest({ CausesValidation: false });
		req.setCausesValidation(true);
		expect(req.getCausesValidation()).toBe(true);
	});

	it('round-trips false', () => {
		const req = makeRequest();
		req.setCausesValidation(false);
		expect(req.getCausesValidation()).toBe(false);
	});
});

describe('Prado.CallbackRequest.setValidationGroup / getValidationGroup', () => {
	it('round-trips a group string', () => {
		const req = makeRequest();
		req.setValidationGroup('GroupA');
		expect(req.getValidationGroup()).toBe('GroupA');
	});

	it('round-trips null (clear group)', () => {
		const req = makeRequest();
		req.setValidationGroup('GroupA');
		req.setValidationGroup(null);
		expect(req.getValidationGroup()).toBeNull();
	});
});

describe('Prado.CallbackRequest.setRetryLimit / getRetryLimit', () => {
	it('round-trips an integer', () => {
		const req = makeRequest();
		req.setRetryLimit(3);
		expect(req.getRetryLimit()).toBe(3);
	});

	it('round-trips zero', () => {
		const req = makeRequest();
		req.setRetryLimit(0);
		expect(req.getRetryLimit()).toBe(0);
	});
});

describe('Prado.CallbackRequest.setOptions', () => {
	it('merges additional options onto the instance', () => {
		const req = makeRequest();
		req.setOptions({ CustomKey: 'CustomValue' });
		expect(req.options.CustomKey).toBe('CustomValue');
	});

	it('overwrites existing options', () => {
		const req = makeRequest();
		req.setOptions({ RetryLimit: 5 });
		expect(req.getRetryLimit()).toBe(5);
	});
});

// ─── extractContent ───────────────────────────────────────────────────────────

describe('Prado.CallbackRequest.extractContent', () => {
	/**
	 * Set up a request with a known `data` string and call extractContent.
	 * extractContent reads from `this.data`.
	 */
	function extract(data, boundary) {
		const req  = makeRequest();
		req.data   = data;
		return req.extractContent(boundary);
	}

	it('returns null when the boundary is completely absent', () => {
		expect(extract('no markers here', 'BNDRY')).toBeNull();
	});

	it('returns null when only the opening marker is present', () => {
		expect(extract('<!--BNDRY-->content without closing', 'BNDRY')).toBeNull();
	});

	it('extracts content between matching boundary markers', () => {
		const data = '<!--BNDRY-->hello world<!--//BNDRY-->';
		expect(extract(data, 'BNDRY')).toBe('hello world');
	});

	it('extracts content even when surrounded by other text', () => {
		const data = 'before<!--BNDRY-->inner<!--//BNDRY-->after';
		expect(extract(data, 'BNDRY')).toBe('inner');
	});

	it('extracts empty string when markers are adjacent', () => {
		const data = '<!--BNDRY--><!--//BNDRY-->';
		expect(extract(data, 'BNDRY')).toBe('');
	});

	it('extracts content using the first opening marker when duplicates exist', () => {
		const data = '<!--B-->first<!--//B--><!--B-->second<!--//B-->';
		expect(extract(data, 'B')).toBe('first');
	});
});

// ─── Prado.AssetManagerClass — isAssetLoaded / markAssetAsLoaded ─────────────

describe('Prado.AssetManagerClass isAssetLoaded / markAssetAsLoaded', () => {
	let mgr;

	beforeEach(() => {
		// AssetManagerClass.initialize calls discoverLoadedAssets → findAssetUrlsInMarkup,
		// which is only defined on subclasses.  ScriptManagerClass is the concrete subclass
		// that supplies findAssetUrlsInMarkup; we instantiate it and then clear loadedAssets
		// so tests start from a known-empty state.
		mgr = new global.Prado.ScriptManagerClass();
		mgr.loadedAssets = [];
		// Stub makeFullUrl so tests don't depend on window.location.
		mgr.makeFullUrl = (url) => url;
	});

	it('returns false before an asset is marked as loaded', () => {
		expect(mgr.isAssetLoaded('http://example.com/script.js')).toBe(false);
	});

	it('returns true after markAssetAsLoaded is called', () => {
		mgr.markAssetAsLoaded('http://example.com/script.js');
		expect(mgr.isAssetLoaded('http://example.com/script.js')).toBe(true);
	});

	it('does not add the same asset twice', () => {
		mgr.markAssetAsLoaded('http://example.com/script.js');
		mgr.markAssetAsLoaded('http://example.com/script.js');
		expect(mgr.loadedAssets).toHaveLength(1);
	});

	it('returns false for a different asset after only one is marked', () => {
		mgr.markAssetAsLoaded('http://example.com/a.js');
		expect(mgr.isAssetLoaded('http://example.com/b.js')).toBe(false);
	});
});
