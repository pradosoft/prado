/**
 * Shared test helpers for controls that dispatch Prado.CallbackRequest.
 *
 * Replacing Prado.CallbackRequest with a mock keeps tests off the network;
 * the mock records the calls each control makes so tests can assert on the
 * dispatch without a form or an XHR.
 */

import { vi } from 'vitest';

/** Remove all keys from Prado.Registry. */
export function clearRegistry() {
	for (const k of Object.keys(global.Prado.Registry)) {
		delete global.Prado.Registry[k];
	}
}

/** Remove all keys from an object registry (in-place control maps, timers, ...). */
export function clearMap(map) {
	for (const k of Object.keys(map)) {
		delete map[k];
	}
}

/**
 * Mock Prado.CallbackRequest so that no XHR is ever made.
 * Uses a real constructor function (not an arrow) so `new` works.
 *
 * @param {boolean} dispatchReturnValue what dispatch() reports to the control
 * @return {Object} the mock instance and its call spies
 */
export function mockCallbackRequest(dispatchReturnValue = true) {
	const dispatchMock              = vi.fn().mockReturnValue(dispatchReturnValue);
	const setCallbackParameterMock  = vi.fn();
	const setCausesValidationMock   = vi.fn();
	const instance = {
		dispatch:               dispatchMock,
		setCallbackParameter:   setCallbackParameterMock,
		setCausesValidation:    setCausesValidationMock,
		options:                {},
	};

	const original = global.Prado.CallbackRequest;
	const MockCtor = vi.fn(function () { return instance; });
	MockCtor.__original = original;
	global.Prado.CallbackRequest = MockCtor;

	return { instance, dispatchMock, setCallbackParameterMock, setCausesValidationMock };
}

/** Restore vi mocks and the real Prado.CallbackRequest. */
export function restoreMocks() {
	vi.restoreAllMocks();
	if (global.Prado.CallbackRequest?.__original !== undefined) {
		global.Prado.CallbackRequest = global.Prado.CallbackRequest.__original;
	}
}
