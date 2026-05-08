/**
 * Tests for the Prado core namespace and RequestManager constants.
 * Source: framework/Web/Javascripts/source/prado/prado.js
 *
 * ESM note: imports come from the adapter only.  When prado.js is converted
 * to ES Modules, only tests/js/adapters/prado-core.js needs updating —
 * this file stays unchanged.
 */

import { Version, Registry, RequestManager } from '../adapters/prado-core.js';

describe('Prado namespace', () => {
	it('exports a semver Version string', () => {
		expect(typeof Version).toBe('string');
		expect(Version).toMatch(/^\d+\.\d+\.\d+$/);
	});

	it('exports a Registry object', () => {
		expect(typeof Registry).toBe('object');
		expect(Registry).not.toBeNull();
	});
});

describe('Prado.RequestManager', () => {
	it('FIELD_POSTBACK_TARGET matches the PHP-side field name', () => {
		expect(RequestManager.FIELD_POSTBACK_TARGET).toBe('PRADO_POSTBACK_TARGET');
	});

	it('FIELD_POSTBACK_PARAMETER matches the PHP-side field name', () => {
		expect(RequestManager.FIELD_POSTBACK_PARAMETER).toBe('PRADO_POSTBACK_PARAMETER');
	});
});
