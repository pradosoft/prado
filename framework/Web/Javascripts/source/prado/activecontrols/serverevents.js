/**
 * Shares one EventSource per stream URL among its subscribers.
 *
 * A subscriber is an object with `isActive()` and `onServerEvent(event)`. Each
 * message of the stream is the JSON `{"event": name, "data": data}` of
 * TServerEventService; a subscriber receives `{ID, Event, Data}`. A subscriber
 * that is no longer active is removed when a message arrives and after each
 * callback response, and the last one to leave closes its EventSource.
 *
 * @namespace Prado.EventSource
 * @since 4.4.0
 */
Prado.EventSource =
{
	/** The query parameter carrying the event ID the stream resumes after. */
	LAST_EVENT_ID_PARAMETER: 'lastEventId',

	/** Open streams by URL: `{source, subscribers}`. */
	sources: {},

	_listening: false,

	/**
	 * Adds a subscriber to the stream of a URL, opening the stream on first use.
	 * @param {string} url - the stream URL
	 * @param {object} subscriber - the subscriber
	 * @param {string} lastEventId - the event ID a new stream resumes after
	 * @return {EventSource} the shared EventSource
	 */
	subscribe(url, subscriber, lastEventId) {
		let entry = this.sources[url];
		if (!entry) {
			const source = this.open(url, lastEventId);
			entry = this.sources[url] = { source, subscribers: [] };
			source.addEventListener('message', (message) => this.dispatch(url, message));
			source.addEventListener('error', () => {
				// A stream refused with an HTTP error is closed and not retried.
				if (source.readyState === 2 && this.sources[url] === entry)
					this.close(url);
			});
		}
		if (!entry.subscribers.includes(subscriber))
			entry.subscribers.push(subscriber);
		this.listen();
		return entry.source;
	},

	/**
	 * Removes a subscriber; the last one closes the stream at the end of the
	 * current task, so a control registered again in the same callback
	 * response keeps the open stream.
	 * @param {string} url - the stream URL
	 * @param {object} subscriber - the subscriber
	 */
	unsubscribe(url, subscriber) {
		const entry = this.sources[url];
		if (!entry) return;
		const index = entry.subscribers.indexOf(subscriber);
		if (index !== -1)
			entry.subscribers.splice(index, 1);
		if (entry.subscribers.length === 0)
			queueMicrotask(() => {
				if (this.sources[url] === entry && entry.subscribers.length === 0)
					this.close(url);
			});
	},

	/**
	 * Closes the stream of a URL and forgets its subscribers.
	 * @param {string} url - the stream URL
	 */
	close(url) {
		const entry = this.sources[url];
		if (!entry) return;
		delete this.sources[url];
		entry.source.close();
	},

	/**
	 * Creates the EventSource of a stream URL.
	 * @param {string} url - the stream URL
	 * @param {string} lastEventId - the event ID the stream resumes after
	 * @return {EventSource} the EventSource
	 */
	open(url, lastEventId) {
		const separator = url.includes('?') ? '&' : '?';
		return new EventSource(url + separator + this.LAST_EVENT_ID_PARAMETER + '=' + encodeURIComponent(lastEventId || ''));
	},

	/**
	 * Delivers a stream message to the active subscribers of its URL.
	 * @param {string} url - the stream URL
	 * @param {MessageEvent} message - the stream message
	 */
	dispatch(url, message) {
		const entry = this.sources[url];
		if (!entry) return;
		let payload;
		try {
			payload = JSON.parse(message.data);
		} catch {
			return;
		}
		if (!payload || typeof payload.event !== 'string') return;
		const event = { ID: message.lastEventId, Event: payload.event, Data: payload.data };
		for (const subscriber of [...entry.subscribers]) {
			if (subscriber.isActive())
				subscriber.onServerEvent(event);
			else
				this.unsubscribe(url, subscriber);
		}
	},

	/**
	 * Removes the subscribers that are no longer active from every stream.
	 */
	sweep() {
		for (const [url, entry] of Object.entries(this.sources)) {
			for (const subscriber of [...entry.subscribers]) {
				if (!subscriber.isActive())
					this.unsubscribe(url, subscriber);
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
 * Sends a callback request for each server event of its channel.
 * Options: ID, EventTarget, StreamUrl, LastEventID, EventName (empty for every event).
 * @class Prado.WebUI.TServerEventTriggeredCallback
 * @since 4.4.0
 */
Prado.WebUI.TServerEventTriggeredCallback = Prado.Class(Prado.WebUI.Control,
{
	onInit(options) {
		this.options = options || {};
		Prado.EventSource.subscribe(this.options.StreamUrl, this, this.options.LastEventID);
	},

	/**
	 * @return {boolean} whether the control is registered and its anchor is in the document
	 */
	isActive() {
		return this.registered && !!this.element && this.element.isConnected;
	},

	/**
	 * Sends the callback for a server event that matches EventName.
	 * @param {object} event - the server event: ID, Event, Data
	 */
	onServerEvent(event) {
		if (this.options.EventName && event.Event !== this.options.EventName) return;
		const request = new Prado.CallbackRequest(this.options.EventTarget, this.options);
		request.setCallbackParameter(event);
		request.dispatch();
	},

	onDone() {
		Prado.EventSource.unsubscribe(this.options.StreamUrl, this);
	}
});
