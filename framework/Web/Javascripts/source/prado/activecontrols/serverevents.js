/**
 * Shares one stream per transport and stream key among its subscribers, and
 * delivers the stream's server events to them.
 *
 * A transport opens a stream; `sse` (Prado.EventSource) is built in, and another
 * script registers its own with registerTransport(), such as a WebSocket
 * transport. A transport is an object with:
 * - `key(options)`: the stream key; subscribers with the same key share a stream.
 * - `open(options, deliver, refused)`: opens the stream and returns a handle with
 *   `close()`. It calls `deliver(event)` with each server event `{Channel, ID,
 *   Event, Data}`, and `refused()` when the server refuses the stream for good.
 *
 * A subscriber is an object with `isActive()` and `onServerEvent(event)`. Each
 * event is first dispatched on `document` as a `prado:serverevent` CustomEvent
 * whose `detail` is the event, then delivered to the active subscribers. A
 * subscriber that is no longer active is removed when an event arrives and after
 * each callback response, and the last one to leave closes its stream.
 *
 * @namespace Prado.ServerEvents
 * @since 4.4.0
 */
Prado.ServerEvents =
{
	/** The type of the CustomEvent dispatched on `document` for each event. */
	DOCUMENT_EVENT: 'prado:serverevent',

	/** The transport of options without a Transport. */
	DEFAULT_TRANSPORT: 'sse',

	/** Registered transports by name. */
	transports: {},

	/** Open streams by stream key: `{transport, handle, subscribers}`. */
	streams: {},

	_listening: false,

	/**
	 * Registers a transport under a name; options select it with `Transport`.
	 * @param {string} name - the transport name
	 * @param {object} transport - the transport: `key(options)` and `open(options, deliver, refused)`
	 */
	registerTransport(name, transport) {
		this.transports[name] = transport;
	},

	/**
	 * Returns the stream key of subscription options.
	 * @param {object} options - the subscription options, with `Transport`
	 * @return {string} the stream key: the transport name and the transport's key
	 */
	keyOf(options) {
		const name = options.Transport || this.DEFAULT_TRANSPORT;
		const transport = this.transports[name];
		if (!transport)
			throw new Error(`Prado.ServerEvents: no transport '${name}' is registered.`);
		return name + ' ' + transport.key(options);
	},

	/**
	 * Adds a subscriber to the stream of its options, opening the stream on first use.
	 * @param {object} options - the subscription options, with `Transport`
	 * @param {object} subscriber - the subscriber
	 * @return {string} the stream key, for unsubscribe()
	 */
	subscribe(options, subscriber) {
		const key = this.keyOf(options);
		let entry = this.streams[key];
		if (!entry) {
			const name = options.Transport || this.DEFAULT_TRANSPORT;
			const opened = { transport: name, handle: null, subscribers: [] };
			this.streams[key] = entry = opened;
			try {
				opened.handle = this.transports[name].open(
					options,
					(event) => this.deliver(key, event),
					() => {
						// A stream the server refused is closed and not retried.
						if (this.streams[key] === opened)
							this.close(key);
					}
				);
			} catch (e) {
				delete this.streams[key];
				throw e;
			}
		}
		if (!entry.subscribers.includes(subscriber))
			entry.subscribers.push(subscriber);
		this.listen();
		return key;
	},

	/**
	 * Removes a subscriber; the last one closes the stream at the end of the
	 * current task, so a control registered again in the same callback
	 * response keeps the open stream.
	 * @param {string} key - the stream key
	 * @param {object} subscriber - the subscriber
	 */
	unsubscribe(key, subscriber) {
		const entry = this.streams[key];
		if (!entry) return;
		const index = entry.subscribers.indexOf(subscriber);
		if (index !== -1)
			entry.subscribers.splice(index, 1);
		if (entry.subscribers.length === 0)
			queueMicrotask(() => {
				if (this.streams[key] === entry && entry.subscribers.length === 0)
					this.close(key);
			});
	},

	/**
	 * Closes a stream and forgets its subscribers.
	 * @param {string} key - the stream key
	 */
	close(key) {
		const entry = this.streams[key];
		if (!entry) return;
		delete this.streams[key];
		if (entry.handle)
			entry.handle.close();
	},

	/**
	 * Dispatches a server event on `document` as {@link Prado.ServerEvents.DOCUMENT_EVENT},
	 * then delivers it to the active subscribers of its stream.
	 * @param {string} key - the stream key
	 * @param {object} event - the server event: Channel, ID, Event, Data
	 */
	deliver(key, event) {
		const entry = this.streams[key];
		if (!entry || !event || typeof event.Event !== 'string') return;
		document.dispatchEvent(new CustomEvent(this.DOCUMENT_EVENT, { detail: event }));
		for (const subscriber of [...entry.subscribers]) {
			if (subscriber.isActive())
				subscriber.onServerEvent(event);
			else
				this.unsubscribe(key, subscriber);
		}
	},

	/**
	 * Removes the subscribers that are no longer active from every stream.
	 */
	sweep() {
		for (const [key, entry] of Object.entries(this.streams)) {
			for (const subscriber of [...entry.subscribers]) {
				if (!subscriber.isActive())
					this.unsubscribe(key, subscriber);
			}
		}
	},

	/**
	 * Sweeps after each callback response, once per page.
	 */
	listen() {
		if (this._listening) return;
		this._listening = true;
		document.addEventListener('prado:ajaxComplete', () => this.sweep());
	}
};

/**
 * The `sse` transport: a server-sent event stream of TServerEventService through
 * the browser's EventSource. Options: StreamUrl, the stream URL with its channel
 * token, and LastEventID, the event ID a new stream resumes after. Each message is
 * the JSON `{"channel": channel, "event": name, "data": data}` with its `id:`.
 *
 * @namespace Prado.EventSource
 * @since 4.4.0
 */
Prado.EventSource =
{
	/** The query parameter carrying the event ID the stream resumes after. */
	LAST_EVENT_ID_PARAMETER: 'lastEventId',

	/**
	 * @param {object} options - the subscription options
	 * @return {string} the stream URL
	 */
	key(options) {
		return options.StreamUrl;
	},

	/**
	 * Opens the EventSource of a stream.
	 * @param {object} options - StreamUrl and LastEventID
	 * @param {function} deliver - receives each decoded server event
	 * @param {function} refused - called when the server refuses the stream (HTTP 403 or 404)
	 * @return {object} the handle: `source`, the EventSource, `url`, and `close()`
	 */
	open(options, deliver, refused) {
		const url = options.StreamUrl;
		const separator = url.includes('?') ? '&' : '?';
		const source = new EventSource(url + separator + this.LAST_EVENT_ID_PARAMETER + '=' + encodeURIComponent(options.LastEventID || ''));
		source.addEventListener('message', (message) => {
			const event = this.decode(message);
			if (event)
				deliver(event);
		});
		source.addEventListener('error', () => {
			if (source.readyState === 2)
				refused();
		});
		return { source, url, close: () => source.close() };
	},

	/**
	 * Decodes a stream message into a server event.
	 * @param {MessageEvent} message - the stream message
	 * @return {?object} the server event, Channel, ID, Event, Data; null when it is not one
	 */
	decode(message) {
		let payload;
		try {
			payload = JSON.parse(message.data);
		} catch {
			return null;
		}
		if (!payload || typeof payload.event !== 'string') return null;
		return {
			Channel: typeof payload.channel === 'string' ? payload.channel : '',
			ID: message.lastEventId,
			Event: payload.event,
			Data: payload.data,
		};
	}
};

Prado.ServerEvents.registerTransport('sse', Prado.EventSource);

/**
 * Handles each server event of its channel: runs OnServerEvent, then sends a
 * callback request unless OnServerEvent returned false or CallbackOnEvent is false.
 * Options: ID, EventTarget, Transport ('sse' when absent) with the transport's
 * options (StreamUrl and LastEventID for 'sse'), EventName (empty for every
 * event), CallbackOnEvent, and the optional function OnServerEvent(sender, event).
 * @class Prado.WebUI.TServerEventTriggeredCallback
 * @since 4.4.0
 */
Prado.WebUI.TServerEventTriggeredCallback = Prado.Class(Prado.WebUI.Control,
{
	onInit(options) {
		this.options = options || {};
		this.streamKey = Prado.ServerEvents.subscribe(this.options, this);
	},

	/**
	 * @return {boolean} whether the control is registered and its anchor is in the document
	 */
	isActive() {
		return this.registered && !!this.element && this.element.isConnected;
	},

	/**
	 * Handles a server event that matches EventName: OnServerEvent runs first, and
	 * returning false from it skips the callback; CallbackOnEvent false skips it always.
	 * @param {object} event - the server event: Channel, ID, Event, Data
	 */
	onServerEvent(event) {
		if (this.options.EventName && event.Event !== this.options.EventName) return;
		if (typeof this.options.OnServerEvent === 'function' && this.options.OnServerEvent(this, event) === false) return;
		if (this.options.CallbackOnEvent === false) return;
		const request = new Prado.CallbackRequest(this.options.EventTarget, this.options);
		request.setCallbackParameter(event);
		request.dispatch();
	},

	onDone() {
		Prado.ServerEvents.unsubscribe(this.streamKey, this);
	}
});
