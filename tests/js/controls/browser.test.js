/**
 * Tests for controls/browser.js: Prado.Browser (title, badge, vibration,
 * permission state) and Prado.Notification (permission and show).
 *
 * jsdom has none of these APIs; each test installs the one it needs on
 * navigator or window and removes it after.
 *
 * Source: framework/Web/Javascripts/source/prado/controls/browser.js
 */

import { Browser, Notification } from '../adapters/browser.js';

function define(target, name, value) {
	Object.defineProperty(target, name, { configurable: true, writable: true, value });
}

const settle = async () => {
	for (let i = 0; i < 4; i++)
		await Promise.resolve();
};

describe('Prado.Browser', () => {
	afterEach(() => {
		delete navigator.setAppBadge;
		delete navigator.clearAppBadge;
		delete navigator.vibrate;
		delete navigator.permissions;
		document.title = '';
	});

	it('setTitle sets the document title and clears it for null', () => {
		Browser.setTitle('Inbox (3)');
		expect(document.title).toBe('Inbox (3)');
		Browser.setTitle(null);
		expect(document.title).toBe('');
	});

	it('canBadge is false without the Badging API and the badge calls resolve false', async () => {
		expect(Browser.canBadge()).toBe(false);
		await expect(Browser.setAppBadge(3)).resolves.toBe(false);
		await expect(Browser.clearAppBadge()).resolves.toBe(false);
	});

	it('setAppBadge passes the count, a flag for null, and clears', async () => {
		define(navigator, 'setAppBadge', vi.fn(() => Promise.resolve()));
		define(navigator, 'clearAppBadge', vi.fn(() => Promise.resolve()));
		expect(Browser.canBadge()).toBe(true);
		await expect(Browser.setAppBadge(3)).resolves.toBe(true);
		expect(navigator.setAppBadge).toHaveBeenLastCalledWith(3);
		await expect(Browser.setAppBadge(null)).resolves.toBe(true);
		expect(navigator.setAppBadge).toHaveBeenLastCalledWith();
		await expect(Browser.setAppBadge('7')).resolves.toBe(true);
		expect(navigator.setAppBadge).toHaveBeenLastCalledWith(7);
		await expect(Browser.clearAppBadge()).resolves.toBe(true);
		expect(navigator.clearAppBadge).toHaveBeenCalledTimes(1);
	});

	it('setAppBadge resolves false when the browser rejects', async () => {
		define(navigator, 'setAppBadge', vi.fn(() => Promise.reject(new Error('no'))));
		define(navigator, 'clearAppBadge', vi.fn(() => Promise.reject(new Error('no'))));
		await expect(Browser.setAppBadge(1)).resolves.toBe(false);
		await expect(Browser.clearAppBadge()).resolves.toBe(false);
	});

	it('vibrate is false without the API and passes the pattern with it', () => {
		expect(Browser.vibrate(100)).toBe(false);
		define(navigator, 'vibrate', vi.fn(() => true));
		expect(Browser.vibrate([100, 50, 100])).toBe(true);
		expect(navigator.vibrate).toHaveBeenCalledWith([100, 50, 100]);
		define(navigator, 'vibrate', vi.fn(() => { throw new TypeError('bad'); }));
		expect(Browser.vibrate(100)).toBe(false);
	});

	it('queryPermission is unsupported without the Permissions API or an unknown name', async () => {
		await expect(Browser.queryPermission('notifications')).resolves.toBe('unsupported');
		define(navigator, 'permissions', { query: vi.fn(() => Promise.reject(new TypeError('unknown'))) });
		await expect(Browser.queryPermission('nope')).resolves.toBe('unsupported');
	});

	it('queryPermission resolves with the state', async () => {
		define(navigator, 'permissions', { query: vi.fn(() => Promise.resolve({ state: 'prompt' })) });
		await expect(Browser.queryPermission('notifications')).resolves.toBe('prompt');
		expect(navigator.permissions.query).toHaveBeenCalledWith({ name: 'notifications' });
	});

	it('isVisible and isOnline read the document and navigator', () => {
		expect(Browser.isVisible()).toBe(document.visibilityState !== 'hidden');
		Object.defineProperty(document, 'visibilityState', { configurable: true, get: () => 'hidden' });
		expect(Browser.isVisible()).toBe(false);
		delete document.visibilityState;
		expect(Browser.isOnline()).toBe(true);
		Object.defineProperty(navigator, 'onLine', { configurable: true, get: () => false });
		expect(Browser.isOnline()).toBe(false);
		delete navigator.onLine;
	});
});

describe('Prado.Notification', () => {
	let created, permission, requested;

	class FakeNotification extends EventTarget {
		constructor(title, options) {
			super();
			this.title = title;
			this.options = options;
			this.close = vi.fn();
			created.push(this);
		}

		static get permission() {
			return permission;
		}

		static requestPermission(callback) {
			requested++;
			permission = 'granted';
			if (callback) {
				callback(permission);
				return undefined;
			}
			return Promise.resolve(permission);
		}
	}

	beforeEach(() => {
		created = [];
		permission = 'default';
		requested = 0;
		define(window, 'Notification', FakeNotification);
	});

	afterEach(() => {
		vi.restoreAllMocks();
		delete window.Notification;
		delete global.Prado.Callback;
	});

	it('reports unsupported without the API', async () => {
		delete window.Notification;
		expect(Notification.isSupported()).toBe(false);
		expect(Notification.getPermission()).toBe('unsupported');
		await expect(Notification.requestPermission()).resolves.toBe('unsupported');
		await expect(Notification.show('Hi')).resolves.toBeNull();
	});

	it('requestPermission asks once and returns the permission', async () => {
		expect(Notification.getPermission()).toBe('default');
		await expect(Notification.requestPermission()).resolves.toBe('granted');
		await expect(Notification.requestPermission()).resolves.toBe('granted');
		expect(requested).toBe(1);
	});

	it('requestPermission takes the callback form of the API', async () => {
		define(window, 'Notification', class extends FakeNotification {
			static requestPermission(callback) {
				permission = 'denied';
				callback(permission);
			}
		});
		await expect(Notification.requestPermission()).resolves.toBe('denied');
	});

	it('show creates the notification with the API options once permission is granted', async () => {
		const notification = await Notification.show('Order shipped', { body: 'Today', tag: 'order-1', EventTarget: 'ctl0$btn', closeAfter: 0 });
		expect(notification).toBe(created[0]);
		expect(created[0].title).toBe('Order shipped');
		expect(created[0].options).toEqual({ body: 'Today', tag: 'order-1' });
		expect(requested).toBe(1);
	});

	it('show resolves null when permission is denied', async () => {
		permission = 'denied';
		await expect(Notification.show('Hi')).resolves.toBeNull();
		expect(created).toHaveLength(0);
	});

	it('show resolves null when the browser refuses the notification', async () => {
		permission = 'granted';
		define(window, 'Notification', class extends FakeNotification {
			constructor() {
				super();
				throw new TypeError('no constructor in a worker');
			}
		});
		await expect(Notification.show('Hi')).resolves.toBeNull();
	});

	it('a click focuses the window, dispatches prado:notificationclick, raises the callback, and closes', async () => {
		permission = 'granted';
		const focus = vi.spyOn(window, 'focus').mockImplementation(() => {});
		const callback = vi.fn();
		global.Prado.Callback = callback;
		const clicks = [];
		const listener = (e) => clicks.push(e.detail);
		document.addEventListener('prado:notificationclick', listener);
		const notification = await Notification.show('Hi', { EventTarget: 'ctl0$btn', CallbackParameter: { id: 7 } });
		notification.dispatchEvent(new Event('click'));
		expect(focus).toHaveBeenCalled();
		expect(clicks).toHaveLength(1);
		expect(clicks[0].notification).toBe(notification);
		expect(clicks[0].title).toBe('Hi');
		expect(callback).toHaveBeenCalledWith('ctl0$btn', { id: 7 });
		expect(notification.close).toHaveBeenCalled();
		document.removeEventListener('prado:notificationclick', listener);
	});

	it('a click without EventTarget raises no callback and focus false keeps the window', async () => {
		permission = 'granted';
		const focus = vi.spyOn(window, 'focus').mockImplementation(() => {});
		const callback = vi.fn();
		global.Prado.Callback = callback;
		const notification = await Notification.show('Hi', { focus: false });
		notification.dispatchEvent(new Event('click'));
		expect(focus).not.toHaveBeenCalled();
		expect(callback).not.toHaveBeenCalled();
		expect(notification.close).toHaveBeenCalled();
	});

	it('closeAfter closes the notification after the delay', async () => {
		permission = 'granted';
		vi.useFakeTimers();
		const promise = Notification.show('Hi', { closeAfter: 500 });
		await settle();
		const notification = await promise;
		expect(notification.close).not.toHaveBeenCalled();
		vi.advanceTimersByTime(500);
		expect(notification.close).toHaveBeenCalled();
		vi.useRealTimers();
	});
});
