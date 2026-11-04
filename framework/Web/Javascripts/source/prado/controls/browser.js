/**
 * Browser integration: the document title, the app badge, vibration,
 * permission state, and system notifications. Each method checks for its
 * API and does nothing in a browser without it. A callback response calls
 * these through TCallbackClientScript.
 */

/**
 * Document title, app badge, vibration, and permission state.
 * @namespace Prado.Browser
 * @since 4.4.0
 */
Prado.Browser =
{
	/**
	 * Sets the document title.
	 * @function ?
	 * @param {string} title - The new title
	 */
	setTitle(title) {
		document.title = title === null || title === undefined ? '' : String(title);
	},

	/**
	 * Returns whether the Badging API is available.
	 * @function ?
	 * @returns {boolean} whether `navigator.setAppBadge` exists
	 */
	canBadge() {
		return typeof navigator.setAppBadge === 'function' && typeof navigator.clearAppBadge === 'function';
	},

	/**
	 * Sets the badge on the installed app's icon. A count of null or undefined
	 * shows a flag without a number; 0 clears the badge.
	 * @function ?
	 * @param {?number} [count] - The number to show
	 * @returns {Promise<boolean>} whether the browser set the badge
	 */
	setAppBadge(count) {
		if (!this.canBadge())
			return Promise.resolve(false);
		const promise = count === null || count === undefined
			? navigator.setAppBadge()
			: navigator.setAppBadge(Number(count));
		return promise.then(() => true, () => false);
	},

	/**
	 * Clears the badge on the installed app's icon.
	 * @function ?
	 * @returns {Promise<boolean>} whether the browser cleared the badge
	 */
	clearAppBadge() {
		if (!this.canBadge())
			return Promise.resolve(false);
		return navigator.clearAppBadge().then(() => true, () => false);
	},

	/**
	 * Vibrates the device. A pattern alternates vibration and pause lengths in
	 * milliseconds; 0 or an empty pattern cancels a vibration in progress. The
	 * browser ignores the call without a recent user interaction.
	 * @function ?
	 * @param {number|number[]} pattern - Milliseconds, or a pattern of them
	 * @returns {boolean} whether the browser accepted the pattern
	 */
	vibrate(pattern) {
		if (typeof navigator.vibrate !== 'function')
			return false;
		try {
			return !!navigator.vibrate(pattern);
		} catch {
			return false;
		}
	},

	/**
	 * Returns the state of a permission: `granted`, `denied`, or `prompt`, or
	 * `unsupported` when the browser has no Permissions API or does not know
	 * the permission name.
	 * @function ?
	 * @param {string} name - The permission name, such as `notifications` or `geolocation`
	 * @returns {Promise<string>} the permission state
	 */
	queryPermission(name) {
		if (!navigator.permissions || typeof navigator.permissions.query !== 'function')
			return Promise.resolve('unsupported');
		try {
			return navigator.permissions.query({ name }).then((status) => status.state, () => 'unsupported');
		} catch {
			return Promise.resolve('unsupported');
		}
	},

	/**
	 * Returns whether the page is visible: `document.visibilityState` is not `hidden`.
	 * @function ?
	 * @returns {boolean} whether the page is visible
	 */
	isVisible() {
		return document.visibilityState !== 'hidden';
	},

	/**
	 * Returns whether the browser reports a network connection.
	 * @function ?
	 * @returns {boolean} whether `navigator.onLine` is not false
	 */
	isOnline() {
		return navigator.onLine !== false;
	}
};

/**
 * System notifications through the Notification API. The page asks for
 * permission from a user interaction, such as a button's OnClientClick:
 * `Prado.Notification.requestPermission()`. A click on a notification
 * focuses the window, dispatches `prado:notificationclick` on the document,
 * and raises the callback of its EventTarget option, when there is one.
 * @namespace Prado.Notification
 * @since 4.4.0
 */
Prado.Notification =
{
	/**
	 * Returns whether the browser has the Notification API.
	 * @function ?
	 * @returns {boolean} whether `window.Notification` exists
	 */
	isSupported() {
		return typeof window.Notification === 'function';
	},

	/**
	 * Returns the permission: `granted`, `denied`, or `default` (not asked), or
	 * `unsupported` without the API.
	 * @function ?
	 * @returns {string} the permission
	 */
	getPermission() {
		return this.isSupported() ? window.Notification.permission : 'unsupported';
	},

	/**
	 * Asks the user for permission to show notifications. Browsers grant the
	 * request only from a user interaction. The promise resolves with the
	 * permission, as {@link Prado.Notification.getPermission} reports it.
	 * @function ?
	 * @returns {Promise<string>} the permission after the request
	 */
	requestPermission() {
		if (!this.isSupported())
			return Promise.resolve('unsupported');
		if (window.Notification.permission !== 'default')
			return Promise.resolve(window.Notification.permission);
		return new Promise((resolve) => {
			let result;
			try {
				// Safari before 16 takes a callback and returns undefined.
				result = window.Notification.requestPermission(resolve);
			} catch {
				resolve('denied');
				return;
			}
			if (result && typeof result.then === 'function')
				result.then(resolve, () => resolve('denied'));
		});
	},

	/**
	 * Shows a notification. Without permission, the page asks for it first,
	 * which the browser grants only from a recent user interaction; the promise
	 * resolves with null when the notification is not shown.
	 *
	 * The options are those of the Notification constructor (`body`, `icon`,
	 * `tag`, `lang`, `dir`, `badge`, `image`, `silent`, `requireInteraction`,
	 * `renotify`, `data`) and these:
	 *
	 * | Option | Effect |
	 * |---|---|
	 * | `EventTarget` | Unique ID of the control whose callback a click raises |
	 * | `CallbackParameter` | The parameter of that callback |
	 * | `focus` | Whether a click focuses the window; default true |
	 * | `closeAfter` | Milliseconds after which the notification closes |
	 *
	 * @function ?
	 * @param {string} title - The notification title
	 * @param {object} [options] - Notification and click options
	 * @returns {Promise<?Notification>} the notification, or null
	 */
	show(title, options) {
		const self = Prado.Notification;
		if (!self.isSupported())
			return Promise.resolve(null);
		const op = { focus : true, ...(options || {}) };
		return self.requestPermission().then((permission) => {
			if (permission !== 'granted')
				return null;
			return self.create(title, op);
		});
	},

	/**
	 * Creates the notification with permission granted, and binds its click.
	 * @function ?
	 * @param {string} title - The notification title
	 * @param {object} op - The options of {@link Prado.Notification.show}
	 * @returns {?Notification} the notification, or null when the browser refuses it
	 */
	create(title, op) {
		const { EventTarget, CallbackParameter, focus, closeAfter, ...init } = op;
		let notification;
		try {
			notification = new window.Notification(title === null || title === undefined ? '' : String(title), init);
		} catch {
			return null;
		}
		notification.addEventListener('click', () => {
			if (focus !== false && typeof window.focus === 'function')
				window.focus();
			document.dispatchEvent(new CustomEvent('prado:notificationclick', {
				detail: { notification, title, options: op }
			}));
			if (EventTarget && typeof Prado.Callback === 'function')
				Prado.Callback(EventTarget, CallbackParameter === undefined ? null : CallbackParameter);
			notification.close();
		});
		if (closeAfter > 0)
			setTimeout(() => notification.close(), closeAfter);
		return notification;
	}
};
