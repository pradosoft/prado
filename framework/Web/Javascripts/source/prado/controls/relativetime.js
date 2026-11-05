/*! PRADO TRelativeTime javascript file | github.com/pradosoft/prado */

/**
 * TRelativeTime client class.
 *
 * Renders a live, self-updating relative time ("5 minutes ago", "in 3 hours") inside the
 * control element and reschedules itself as the value changes. The visible magnitude is
 * composed from the localized CLDR duration-unit patterns supplied by the server; the
 * plural category is chosen with Intl.PluralRules and the number formatted with
 * Intl.NumberFormat. Direction ("ago" / "in") is applied from Intl.RelativeTimeFormat when
 * the browser provides it, unless DurationOnly is set. Clicking toggles between the relative
 * time and the absolute date.
 */
Prado.WebUI.TRelativeTime = Prado.Class(Prado.WebUI.Control,
{
	onInit: function(options)
	{
		this.options = options || {};
		this.startTime = Date.now() / 1000;
		this.showAbsolute = false;
		this.loopTimeout = null;
		this.waitTime = 1;

		var culture = this.options.Culture || undefined;
		this.pluralRules = this.makeIntl(function() { return new Intl.PluralRules(culture); });
		this.numberFormat = this.makeIntl(function() { return new Intl.NumberFormat(culture); });
		this.relativeFormat = this.makeIntl(function() {
			return new Intl.RelativeTimeFormat(culture, { numeric: 'always', style: options.Mode || 'long' });
		});

		if (this.options.ClickForDateTime && this.element) {
			this.element.style.cursor = 'pointer';
			this.observe(this.element, 'click', this.toggleDisplay.bind(this));
		}
		this.drawLoop();
	},

	/**
	 * Constructs an Intl formatter, returning null when the constructor is unavailable or
	 * the locale is rejected.
	 */
	makeIntl: function(factory)
	{
		try {
			return factory();
		} catch (_e) {
			return null;
		}
	},

	toggleDisplay: function()
	{
		this.showAbsolute = !this.showAbsolute;
		this.render();
	},

	/**
	 * Renders once, then reschedules itself after the computed wait time. The loop stops
	 * when the element leaves the document, so a control removed by a callback does not leak
	 * a timer.
	 */
	drawLoop: function()
	{
		if (!this.element || (document.body && !document.body.contains(this.element))) {
			return;
		}
		this.render();
		var self = this;
		this.loopTimeout = setTimeout(function() { self.drawLoop(); }, Math.max(this.waitTime, 1) * 1000);
	},

	formatNumber: function(value)
	{
		return this.numberFormat ? this.numberFormat.format(value) : String(value);
	},

	/**
	 * Formats a single unit count using the localized plural pattern for the unit,
	 * selecting the plural category with Intl.PluralRules.
	 */
	localizeUnit: function(type, count)
	{
		var patterns = this.options.UnitPatterns[type] || {};
		var category = 'other';
		if (this.pluralRules) {
			try {
				category = this.pluralRules.select(count);
			} catch (_e) {
				category = 'other';
			}
		}
		var pattern = patterns[category] || patterns.other || patterns.one || '{0}';
		return pattern.replace('{0}', this.formatNumber(count));
	},

	/**
	 * The unit timing table, largest first. `seconds` is the unit length; `sig` is the
	 * partial-element threshold from the server options.
	 */
	timingTable: function()
	{
		var partial = this.options.PartialCount;
		return [
			{ type: 'year',   seconds: 86400 * 365.2421896698, sig: partial[0] },
			{ type: 'month',  seconds: 2629743.7656,           sig: partial[1] },
			{ type: 'week',   seconds: 604800,                 sig: partial[2] },
			{ type: 'day',    seconds: 86400,                  sig: partial[3] },
			{ type: 'hour',   seconds: 3600,                   sig: partial[4] },
			{ type: 'minute', seconds: 60,                     sig: partial[5] },
			{ type: 'second', seconds: 1,                      sig: partial[6] }
		];
	},

	render: function()
	{
		if (!this.element) {
			return;
		}

		var current = Date.now() / 1000;
		var delta = current - this.options.OriginTime;
		if (this.options.UseServerTime) {
			delta += this.options.ServerTime - this.startTime;
		}
		var isFuture = delta < 0;
		if (isFuture) {
			delta = -delta;
		}

		var timing = this.timingTable();
		var remaining = delta;
		var leadingIndex = false;
		var shown = 0;
		var importantNext = false;
		var parts = [];
		var leadingValue = 0;
		var leadingUnit = 'second';

		for (var i = 0; i < timing.length; i++) {
			var num = Math.floor(remaining / timing[i].seconds);
			if (num !== 0 && leadingIndex === false) {
				leadingIndex = i;
			}
			if (leadingIndex !== false && (shown < this.options.SignificantElements
				|| (this.options.PartialElement && shown === 1 && importantNext))) {
				if (this.options.DisplayZero || num !== 0) {
					parts.push(this.localizeUnit(timing[i].type, num));
					remaining -= num * timing[i].seconds;
					if (shown === 0) {
						leadingValue = num;
						leadingUnit = timing[i].type;
					}
				}
				shown++;
				importantNext = (num <= timing[i].sig);
				this.waitTime = remaining % timing[i].seconds;
				if (!isFuture) {
					this.waitTime = timing[i].seconds - this.waitTime;
				}
			}
		}

		if (leadingIndex === false) {
			parts = [this.localizeUnit('second', 0)];
			leadingValue = 0;
			leadingUnit = 'second';
			this.waitTime = 1;
		}

		var magnitude = parts.join(this.options.Separator);
		var relative = this.options.DurationOnly
			? magnitude
			: this.applyDirection(magnitude, parts[0], leadingValue, leadingUnit, isFuture);

		this.element.innerHTML = this.showAbsolute ? this.options.AbsoluteText : relative;
	},

	/**
	 * Wraps the composed magnitude with the localized direction ("ago" / "in").
	 *
	 * A single unit returns the formatter output directly, which carries any inflection the
	 * language applies. Intl.RelativeTimeFormat fuses the unit word into the direction text,
	 * so a multi-unit magnitude is spliced in place of the leading unit's own rendering.
	 * When the formatter is unavailable, the value is zero, or the leading rendering cannot
	 * be located, the plain magnitude is returned.
	 */
	applyDirection: function(magnitude, leadingText, leadingValue, leadingUnit, isFuture)
	{
		if (!this.relativeFormat || leadingValue === 0 || !leadingText) {
			return magnitude;
		}
		var signed = isFuture ? leadingValue : -leadingValue;
		var relative;
		try {
			relative = this.relativeFormat.format(signed, leadingUnit);
		} catch (_e) {
			return magnitude;
		}
		if (magnitude === leadingText) {
			return relative;
		}
		var at = relative.indexOf(leadingText);
		if (at === -1) {
			return magnitude;
		}
		return relative.slice(0, at) + magnitude + relative.slice(at + leadingText.length);
	}
});
