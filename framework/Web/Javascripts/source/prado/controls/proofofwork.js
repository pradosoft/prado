/*! PRADO TProofOfWork javascript file | github.com/pradosoft/prado */

/**
 * TProofOfWork client class.
 *
 * Solves the server's challenge in a Web Worker, or on the main thread in chunks when a
 * worker cannot start, then writes the signed solution into the hidden field. StartMode
 * sets when solving begins: 'Load', 'Focus' (the first focus inside the form), or 'Submit'.
 *
 * A submission before the solution is ready is held. Prado postbacks dispatch a synthetic
 * submit event and then call form.submit(); a held postback resumes with form.submit(),
 * and its PRADO_POSTBACK_* fields are already in the form. A held native submission resumes
 * with form.requestSubmit() so the submit button's name and value are posted. A Prado
 * button can produce both events for one click; the held submissions resume once.
 *
 * A callback that causes validation waits through a Prado.CallbackRequestManager send gate,
 * which holds the queued request until the solution is written. Each callback response
 * re-creates the control. KeepSolved, set when that callback did not validate the control,
 * keeps the unused solution in the field; otherwise the control solves the new challenge.
 *
 * The control element is a role="status" live region. Its text reports the progress.
 */
Prado.WebUI.TProofOfWork = Prado.Class(Prado.WebUI.Control,
{
	onInit(options)
	{
		this.options = options || {};
		this.field = document.getElementById(this.options.ID + '_solution');
		this.statusText = this.element ? this.element.querySelector('.pow-status') : null;
		this.form = this.element ? this.element.closest('form') : null;
		this.solved = false;
		this.promise = null;
		this.worker = null;
		this.pending = null;
		this.waiting = false;
		this.resuming = false;
		this.gate = this.holdCallback.bind(this);
		if (!this.form || !this.field)
			return;

		this.observe(this.form, 'submit', this.onSubmit.bind(this));
		if (Prado.CallbackRequestManager)
			Prado.CallbackRequestManager.addSendGate(this.gate);
		if (this.options.KeepSolved && this.field.value) {
			this.solved = true;
			this.promise = Promise.resolve(true);
			this.setStatus(this.options.VerifiedText, false);
			return;
		}
		this.field.value = '';
		if (this.options.StartMode === 'Load')
			this.start();
		else if (this.options.StartMode === 'Focus')
			this.observe(this.form, 'focusin', this.start.bind(this));
	},

	onDone()
	{
		if (Prado.CallbackRequestManager)
			Prado.CallbackRequestManager.removeSendGate(this.gate);
		this.stopWorker();
	},

	stopWorker()
	{
		if (this.worker) {
			this.worker.terminate();
			this.worker = null;
		}
	},

	/**
	 * Send gate: holds a callback that causes validation until the solution is written.
	 * @param {object} request the CallbackRequest
	 * @return {?Promise<boolean>} the solving promise, or null to let the request go
	 */
	holdCallback(request)
	{
		if (this.solved || !request || !request.options || !request.options.CausesValidation)
			return null;
		return this.start();
	},

	/**
	 * Starts solving once; later calls return the same promise.
	 * @return {Promise<boolean>} whether a solution was written
	 */
	start()
	{
		if (!this.promise) {
			this.setStatus(this.options.VerifyingText, true);
			const challenge = this.options.Challenge;
			this.promise = this.solve(challenge).then((number) => {
				if (!this.registered)
					return false;
				if (number < 0)
					throw new Error('No solution');
				this.field.value = JSON.stringify({
					challenge: challenge.challenge,
					salt: challenge.salt,
					signature: challenge.signature,
					number: number
				});
				this.solved = true;
				this.setStatus(this.options.VerifiedText, false);
				return true;
			}).catch(() => {
				if (this.registered)
					this.setStatus(this.options.FailedText, false);
				return false;
			});
		}
		return this.promise;
	},

	/**
	 * Solves the challenge in a worker, falling back to the main thread.
	 * @param {object} challenge the challenge, salt, and max number
	 * @return {Promise<number>} the solution, or -1
	 */
	solve(challenge)
	{
		const message = { challenge: challenge.challenge, salt: challenge.salt, max: challenge.maxnumber };
		const fallback = () => Prado.ProofOfWorkSolver.solveAsync(message.challenge, message.salt, message.max);
		if (typeof Worker === 'undefined' || !this.options.WorkerUrl)
			return fallback();
		return new Promise((resolve) => {
			try {
				this.worker = new Worker(this.options.WorkerUrl);
			} catch (_e) {
				resolve(fallback());
				return;
			}
			this.worker.onmessage = (event) => {
				this.stopWorker();
				resolve(event.data.number);
			};
			this.worker.onerror = () => {
				this.stopWorker();
				resolve(fallback());
			};
			this.worker.postMessage(message);
		});
	},

	/**
	 * Holds a submission until the solution is written, then resumes it once.
	 * @param {Event} event the submit event
	 */
	onSubmit(event)
	{
		if (this.solved || this.resuming)
			return;
		event.preventDefault();
		const held = { trusted: event.isTrusted, submitter: event.submitter || null };
		if (!this.pending || held.trusted)
			this.pending = held;
		if (this.waiting)
			return;
		this.waiting = true;
		this.start().then((ok) => {
			const resume = this.pending;
			this.pending = null;
			this.waiting = false;
			if (ok && resume)
				this.resume(resume);
		});
	},

	/**
	 * Resumes a held submission.
	 * @param {object} held whether the event was trusted, and its submitter
	 */
	resume(held)
	{
		if (held.trusted && typeof this.form.requestSubmit === 'function') {
			this.resuming = true;
			try {
				this.form.requestSubmit(held.submitter || undefined);
			} finally {
				this.resuming = false;
			}
		} else {
			this.form.submit();
		}
	},

	/**
	 * Shows the status text and marks the region busy while solving.
	 * An empty text keeps a no-break space, so the line keeps its height.
	 * @param {string} text the status text
	 * @param {boolean} busy whether solving is in progress
	 */
	setStatus(text, busy)
	{
		if (this.statusText)
			this.statusText.textContent = text || '\u00a0';
		if (this.element)
			this.element.setAttribute('aria-busy', busy ? 'true' : 'false');
	}
});
