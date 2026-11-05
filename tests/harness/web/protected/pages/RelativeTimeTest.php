<?php

/**
 * Harness page for TRelativeTime functional tests.
 *
 * Each control's DateTime is set relative to the server clock so the initial rendered
 * relative text is deterministic. Offsets use a half-unit margin (e.g. 330s = 5.5 min)
 * so the leading unit stays stable for the duration of a test run.
 */
class RelativeTimeTest extends TPage
{
	public function onLoad($param)
	{
		parent::onLoad($param);

		$now = time();
		$fiveAndHalfMinutes = 330;              // 5 min, +30s margin
		$oneHourFiveMinutes = 3600 + 330;       // 1 hr 5 min, +30s margin

		$this->rtPast->setDateTime($now - $fiveAndHalfMinutes);
		$this->rtFuture->setDateTime($now + $fiveAndHalfMinutes);
		$this->rtShort->setDateTime($now - $fiveAndHalfMinutes);
		$this->rtNarrow->setDateTime($now - $fiveAndHalfMinutes);
		$this->rtMulti->setDateTime($now - $oneHourFiveMinutes);
		$this->rtSep->setDateTime($now - $oneHourFiveMinutes);
		$this->rtSeconds->setDateTime($now - 8);
		$this->rtFrench->setDateTime($now - $fiveAndHalfMinutes);
		$this->rtToggle->setDateTime($now - $fiveAndHalfMinutes);
		$this->rtNoClick->setDateTime($now - $fiveAndHalfMinutes);
		$this->rtDuration->setDateTime($now - $fiveAndHalfMinutes);
		$this->rtNoJs->setDateTime($now - $fiveAndHalfMinutes);
	}
}
