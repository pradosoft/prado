/*! PRADO shared connection | github.com/pradosoft/prado */

/**
 * Prado.SharedConnection shares one live connection, such as a server-sent
 * event stream or a WebSocket, among the tabs of the origin. The tabs that
 * subscribe to a key elect one leader, which holds the connection and passes
 * its messages to the others; when the leader leaves, the next tab takes over
 * and resumes from the last message ID.
 *
 * ```javascript
 * const subscription = Prado.SharedConnection.subscribe({
 *     key: 'sse:/index.php?events=orders&token=abc',
 *     open: Prado.SharedConnection.eventSource('/index.php?events=orders&token=abc'),
 *     onMessage(message) {
 *         console.log(message.id, message.event, message.data);
 *     },
 * });
 * subscription.close();
 * ```
 *
 * | Option | Use |
 * |---|---|
 * | `key` | Identifies the connection; tabs with one key share one connection. Include the user's token, so a connection is never shared between sessions. |
 * | `open(resumeId, emit)` | Opens the connection from `resumeId` (null for the start) and calls `emit({id, event, data})` per message; returns `{close()}` |
 * | `onMessage(message, info)` | Receives each message in every tab; `info.leader` is whether this tab holds the connection |
 * | `heartbeat` | Milliseconds between the leader's heartbeats; 15000 by default |
 * | `shared` | False holds a connection of this tab's own |
 *
 * **Leadership.** A tab requests the Web Lock `prado-sharedconnection:<key>`.
 * The holder opens the connection and posts each message, and a heartbeat, on
 * the `BroadcastChannel` of the same name. The other tabs wait in the lock's
 * queue and dispatch the posted messages.
 *
 * **Handover.** The holder releases the lock when its last subscriber closes,
 * when the tab enters the back-forward cache (`pagehide`), and when the browser
 * freezes it (`freeze`); it requests the lock again on `pageshow` and `resume`.
 * The next tab in the queue takes the lock and opens the connection with the
 * last message ID it received, so no message is lost. A tab that hears no
 * heartbeat for two intervals takes the lock from a holder that stopped
 * delivering (`steal`).
 *
 * Without `navigator.locks` or `BroadcastChannel`, or with `shared: false`, each
 * tab holds its own connection.
 */
Prado.SharedConnection = {
	/** @type {string} the prefix of lock and channel names */
	prefix: 'prado-sharedconnection:',

	/** @type {number} the default heartbeat interval, in milliseconds */
	heartbeat: 15000,

	/** @type {object<string, Prado.SharedConnection.Connection>} this tab's connections by key */
	connections: {},

	/**
	 * Subscribes to a shared connection.
	 * @param {object} options - `key`, `open`, `onMessage`, `heartbeat`, and `shared`
	 * @returns {{close: function, isLeader: function}} the subscription
	 */
	subscribe(options)
	{
		const key = options.key;
		let connection = this.connections[key];
		if (!connection) {
			connection = this.connections[key] = new this.Connection(key, options);
			connection.onEmpty = () => {
				if (this.connections[key] === connection) {
					delete this.connections[key];
				}
			};
		}
		return connection.subscribe(options.onMessage);
	},

	/**
	 * Returns an `open` function that opens a server-sent event stream. A resumed
	 * stream carries the last message ID as a query parameter; the browser's own
	 * reconnections send `Last-Event-ID`.
	 * @param {string} url - the stream URL
	 * @param {object} [options] - `resumeParameter` ('lastEventId'), `events`
	 *   (named event types besides `message`), and `withCredentials`
	 * @returns {function(?string, function): {close: function}} the open function
	 */
	eventSource(url, options)
	{
		options = options || {};
		const parameter = options.resumeParameter || 'lastEventId';
		return (resumeId, emit) => {
			let target = url;
			if (resumeId !== null && resumeId !== undefined && resumeId !== '') {
				target += (url.indexOf('?') === -1 ? '?' : '&') + encodeURIComponent(parameter) + '=' + encodeURIComponent(resumeId);
			}
			const source = new EventSource(target, { withCredentials: !!options.withCredentials });
			const forward = (event) => emit({ id: event.lastEventId || null, event: event.type, data: event.data });
			source.addEventListener('message', forward);
			(options.events || []).forEach((type) => source.addEventListener(type, forward));
			return { close: () => source.close() };
		};
	},
};

/**
 * One key's connection in this tab: its subscribers, its lock, and, while
 * this tab leads, the transport.
 */
Prado.SharedConnection.Connection = class
{
	/**
	 * @param {string} key - the connection key
	 * @param {object} options - `open`, `heartbeat`, and `shared`
	 * @param {object} [environment] - `locks`, `BroadcastChannel`, `target` (the
	 *   window for page lifecycle events), and `now`; the browser's by default
	 */
	constructor(key, options, environment)
	{
		const env = environment || {};
		this.key = key;
		this.open = options.open;
		this.interval = options.heartbeat || Prado.SharedConnection.heartbeat;
		this.locks = 'locks' in env ? env.locks : (typeof navigator !== 'undefined' ? navigator.locks : undefined);
		this.Channel = 'BroadcastChannel' in env ? env.BroadcastChannel : (typeof BroadcastChannel !== 'undefined' ? BroadcastChannel : undefined);
		this.target = env.target || (typeof window !== 'undefined' ? window : null);
		this.now = env.now || (() => Date.now());
		this.shared = options.shared !== false && !!this.locks && !!this.Channel;
		this.name = Prado.SharedConnection.prefix + key;
		this.subscribers = new Set();
		this.leader = false;
		this.transport = null;
		this.lastId = null;
		this.lastSeen = 0;
		this.channel = null;
		this.request = null;
		this.release = null;
		this.heartbeatTimer = null;
		this.watchdogTimer = null;
		this.running = false;
		this.paused = false;
		this.onEmpty = null;
		this.lifecycle = {
			freeze: () => this.pause(),
			resume: () => this.unpause(),
			pagehide: () => this.pause(),
			pageshow: () => this.unpause(),
		};
	}

	/**
	 * Adds a subscriber, starting the connection for the first one.
	 * @param {function(object, object)} onMessage - the message handler
	 * @returns {{close: function, isLeader: function}} the subscription
	 */
	subscribe(onMessage)
	{
		const subscriber = { onMessage };
		this.subscribers.add(subscriber);
		if (!this.running) {
			this.start();
		}
		return {
			close: () => this.unsubscribe(subscriber),
			isLeader: () => this.leader,
		};
	}

	/**
	 * Removes a subscriber, stopping the connection after the last one.
	 * @param {object} subscriber - the subscriber
	 */
	unsubscribe(subscriber)
	{
		if (this.subscribers.delete(subscriber) && this.subscribers.size === 0) {
			this.stop();
			if (this.onEmpty) {
				this.onEmpty();
			}
		}
	}

	/**
	 * Joins the election, or leads at once without sharing.
	 */
	start()
	{
		this.running = true;
		if (!this.shared) {
			this.lead();
			return;
		}
		this.channel = new this.Channel(this.name);
		this.channel.onmessage = (event) => this.receive(event.data || {});
		this.lastSeen = this.now();
		this.watchdogTimer = setInterval(() => this.watch(), this.interval);
		if (this.target) {
			Object.keys(this.lifecycle).forEach((type) => this.target.addEventListener(type, this.lifecycle[type]));
		}
		this.requestLock(false);
	}

	/**
	 * Leaves the election and closes the transport.
	 */
	stop()
	{
		this.running = false;
		this.resign();
		this.cancelRequest();
		clearInterval(this.watchdogTimer);
		this.watchdogTimer = null;
		if (this.channel) {
			this.channel.close();
			this.channel = null;
		}
		if (this.target) {
			Object.keys(this.lifecycle).forEach((type) => this.target.removeEventListener(type, this.lifecycle[type]));
		}
	}

	/**
	 * Requests the lock; the callback leads until the lock is released.
	 * @param {boolean} steal - whether to take the lock from its holder
	 */
	requestLock(steal)
	{
		this.cancelRequest();
		const controller = steal ? null : new AbortController();
		const options = steal ? { steal: true } : { signal: controller.signal };
		const request = { controller };
		this.request = request;
		this.locks.request(this.name, options, () => new Promise((release) => {
			if (this.request !== request || !this.running || this.paused) {
				release();
				return;
			}
			this.release = release;
			this.lead();
		})).catch(() => {
			// Aborted while queued, or stolen while held: follow again.
			if (this.request === request) {
				this.request = null;
				const wasLeader = this.leader;
				this.resign();
				if (wasLeader && this.running && !this.paused) {
					this.requestLock(false);
				}
			}
		});
	}

	/**
	 * Withdraws a queued lock request.
	 */
	cancelRequest()
	{
		const request = this.request;
		this.request = null;
		if (request && request.controller && !this.leader) {
			request.controller.abort();
		}
	}

	/**
	 * Opens the transport from the last message ID and starts the heartbeat.
	 */
	lead()
	{
		if (this.leader) {
			return;
		}
		this.leader = true;
		this.transport = this.open(this.lastId, (message) => this.emit(message));
		if (this.shared) {
			this.post({ type: 'leader', id: this.lastId });
			this.heartbeatTimer = setInterval(() => this.post({ type: 'heartbeat', id: this.lastId }), this.interval);
		}
	}

	/**
	 * Closes the transport and releases the lock.
	 */
	resign()
	{
		if (this.transport) {
			this.transport.close();
			this.transport = null;
		}
		clearInterval(this.heartbeatTimer);
		this.heartbeatTimer = null;
		this.leader = false;
		if (this.release) {
			const release = this.release;
			this.release = null;
			release();
		}
	}

	/**
	 * Hands the connection over while the tab is frozen or hidden in the
	 * back-forward cache.
	 */
	pause()
	{
		if (!this.running || this.paused) {
			return;
		}
		this.paused = true;
		this.resign();
		this.cancelRequest();
	}

	/**
	 * Rejoins the election when the tab resumes.
	 */
	unpause()
	{
		if (!this.running || !this.paused) {
			return;
		}
		this.paused = false;
		this.lastSeen = this.now();
		this.requestLock(false);
	}

	/**
	 * Takes the lock when the leader stopped sending heartbeats.
	 */
	watch()
	{
		if (this.leader || this.paused || !this.running) {
			return;
		}
		if (this.now() - this.lastSeen > 2 * this.interval) {
			this.lastSeen = this.now();
			this.requestLock(true);
		}
	}

	/**
	 * Dispatches a message of the transport and posts it to the other tabs.
	 * @param {object} message - `{id, event, data}`
	 */
	emit(message)
	{
		if (!this.leader) {
			return;
		}
		this.track(message);
		if (this.shared) {
			this.post({ type: 'message', message });
		}
		this.dispatch(message);
	}

	/**
	 * Handles a channel post from the leader.
	 * @param {object} post - `{type, id}` or `{type: 'message', message}`
	 */
	receive(post)
	{
		this.lastSeen = this.now();
		if (this.leader) {
			return;
		}
		if (post.type === 'message' && post.message) {
			this.track(post.message);
			this.dispatch(post.message);
		} else if (post.id !== undefined && post.id !== null && this.lastId === null) {
			this.lastId = post.id;
		}
	}

	/**
	 * Records the ID of a message.
	 * @param {object} message - the message
	 */
	track(message)
	{
		if (message.id !== undefined && message.id !== null && message.id !== '') {
			this.lastId = message.id;
		}
	}

	/**
	 * Posts on the channel.
	 * @param {object} post - the post
	 */
	post(post)
	{
		if (this.channel) {
			this.channel.postMessage(post);
		}
	}

	/**
	 * Calls each subscriber; a subscriber that throws does not stop the others.
	 * @param {object} message - the message
	 */
	dispatch(message)
	{
		const info = { leader: this.leader };
		Array.from(this.subscribers).forEach((subscriber) => {
			try {
				subscriber.onMessage(message, info);
			} catch (error) {
				console.error('PRADO shared connection: a subscriber failed.', error);
			}
		});
	}
};
