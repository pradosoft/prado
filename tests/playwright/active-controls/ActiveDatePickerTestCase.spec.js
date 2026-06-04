import { test, expect } from '@playwright/test';
import { genericHelper } from '../helpers.js';

// phpDate: mirrors PHP date() using UTC — matches PHP-server-rendered values.
// localPhpDate: uses the browser/Node local timezone — matches values set by the
//   datepicker calendar popup's JavaScript "Today" button (new Date() in the widget).
function localPhpDate(fmt, ts = null) {
  const d = ts !== null ? new Date(ts * 1000) : new Date();
  const pad = n => String(n).padStart(2, '0');
  // Single-pass substitution so an already-inserted token value (e.g. the "n"
  // in the month name "June") is not reprocessed by a later token replacement.
  const map = {
    m: pad(d.getMonth() + 1),
    d: pad(d.getDate()),
    Y: String(d.getFullYear()),
    F: d.toLocaleString('en-US', { month: 'long' }),
    n: String(d.getMonth() + 1),
    j: String(d.getDate()),
    t: String(new Date(d.getFullYear(), d.getMonth() + 1, 0).getDate()),
  };
  return fmt.replace(/[mdYFnjt]/g, ch => map[ch]);
}

function phpDate(fmt, ts = null) {
  const d = ts !== null ? new Date(ts * 1000) : new Date();
  const pad = n => String(n).padStart(2, '0');
  // Single-pass substitution so an already-inserted token value (e.g. the "n"
  // in the month name "June") is not reprocessed by a later token replacement.
  const map = {
    m: pad(d.getUTCMonth() + 1),
    d: pad(d.getUTCDate()),
    Y: String(d.getUTCFullYear()),
    F: d.toLocaleString('en-US', { month: 'long', timeZone: 'UTC' }),
    n: String(d.getUTCMonth() + 1),
    j: String(d.getUTCDate()),
    t: String(new Date(Date.UTC(d.getUTCFullYear(), d.getUTCMonth() + 1, 0)).getUTCDate()),
  };
  return fmt.replace(/[mdYFnjt]/g, ch => map[ch]);
}

test('ActiveDatePickerTestCase', async ({ page }) => {
  const h = genericHelper(page);
  const base = 'ctl0_Content_';
  await h.url('active-controls/index.php?page=ActiveDatePicker');
  await h.assertSourceContains('TActiveDatePicker test');
  await h.assertText(`${base}status`, '');
  await h.assertValue(`${base}datepicker`, phpDate('m-d-Y'));

  await h.byId(`${base}increaseButton`).click();
  await h.assertValue(`${base}datepicker`, phpDate('m-d-Y', Date.now() / 1000 + 86400));
  await h.assertText(`${base}status`, phpDate('m-d-Y', Date.now() / 1000 + 86400));

  await h.byId(`${base}increaseButton`).click();
  await h.assertValue(`${base}datepicker`, phpDate('m-d-Y', Date.now() / 1000 + 2 * 86400));
  await h.assertText(`${base}status`, phpDate('m-d-Y', Date.now() / 1000 + 2 * 86400));

  await h.byId(`${base}todayButton`).click();
  await h.assertValue(`${base}datepicker`, phpDate('m-d-Y'));
  await h.assertText(`${base}status`, phpDate('m-d-Y'));

  await h.byId(`${base}decreaseButton`).click();
  await h.assertValue(`${base}datepicker`, phpDate('m-d-Y', Date.now() / 1000 - 86400));
  await h.assertText(`${base}status`, phpDate('m-d-Y', Date.now() / 1000 - 86400));

  await h.byId(`${base}datepicker`).click();
  await h.byCssSelector('input.todayButton').click(); // JS popup button — uses browser local TZ
  await h.assertValue(`${base}datepicker`, localPhpDate('m-d-Y'));
  await h.assertText(`${base}status`, localPhpDate('m-d-Y'));

  await h.byId(`${base}datepicker`).click(); // re-open popup (Chromium closes it after Today click)
  await h.byCssSelector('input.nextMonthButton').click();
  // nextMonth calculation - mirrors datepicker.js:L532
  // The picker uses new Date() (local TZ) internally, so currentDay must be local.
  // We then construct a UTC midnight timestamp so phpDate() (UTC methods) returns the right value.
  const nextMonthTs = (() => {
    const now = new Date();
    const currentDay = now.getDate();                                          // local day
    const firstOfNext = new Date(now.getFullYear(), now.getMonth() + 1, 1);   // local next month
    const daysInMonth = new Date(firstOfNext.getFullYear(), firstOfNext.getMonth() + 1, 0).getDate();
    const day = Math.min(currentDay, daysInMonth);
    return Date.UTC(firstOfNext.getFullYear(), firstOfNext.getMonth(), day) / 1000;
  })();
  const nextMonthDate = nextMonthTs;
  await h.assertValue(`${base}datepicker`, phpDate('m-d-Y', nextMonthTs));
  await h.assertText(`${base}status`, phpDate('m-d-Y', nextMonthTs));

  await h.byId(`${base}toggleButton`).click();
  await h.pause(2000);

  await h.byId(`${base}todayButton`).click();
  await h.assertSelected(`${base}datepicker_month`, phpDate('m'));
  await h.assertText(`${base}status`, phpDate('m-d-Y'));

  await h.byId(`${base}increaseButton`).click();
  let dateToCheck = Date.now() / 1000 + 86400;
  await h.assertSelected(`${base}datepicker_month`, phpDate('m', dateToCheck));
  await h.assertSelected(`${base}datepicker_day`, phpDate('d', dateToCheck));
  await h.assertSelected(`${base}datepicker_year`, phpDate('Y', dateToCheck));
  await h.assertText(`${base}status`, phpDate('m-d-Y', dateToCheck));

  await h.byId(`${base}increaseButton`).click();
  dateToCheck = Date.now() / 1000 + 2 * 86400;
  await h.assertSelected(`${base}datepicker_month`, phpDate('m', dateToCheck));
  await h.assertSelected(`${base}datepicker_day`, phpDate('d', dateToCheck));
  await h.assertSelected(`${base}datepicker_year`, phpDate('Y', dateToCheck));
  await h.assertText(`${base}status`, phpDate('m-d-Y', dateToCheck));

  await h.byId(`${base}todayButton`).click();
  dateToCheck = Date.now() / 1000;
  await h.assertSelected(`${base}datepicker_month`, phpDate('m', dateToCheck));
  await h.assertSelected(`${base}datepicker_day`, phpDate('d', dateToCheck));
  await h.assertSelected(`${base}datepicker_year`, phpDate('Y', dateToCheck));
  await h.assertText(`${base}status`, phpDate('m-d-Y', dateToCheck));

  await h.byId(`${base}decreaseButton`).click();
  dateToCheck = Date.now() / 1000 - 86400;
  await h.assertSelected(`${base}datepicker_month`, phpDate('m', dateToCheck));
  await h.assertSelected(`${base}datepicker_day`, phpDate('d', dateToCheck));
  await h.assertSelected(`${base}datepicker_year`, phpDate('Y', dateToCheck));
  await h.assertText(`${base}status`, phpDate('m-d-Y', dateToCheck));

  await h.byId(`${base}datepickerbutton`).click();
  await h.byCssSelector('input.todayButton').click(); // JS popup button — uses browser local TZ
  await h.assertSelected(`${base}datepicker_month`, localPhpDate('m'));
  await h.assertSelected(`${base}datepicker_day`, localPhpDate('d'));
  await h.assertSelected(`${base}datepicker_year`, localPhpDate('Y'));
  await h.assertText(`${base}status`, localPhpDate('m-d-Y'));

  await h.byId(`${base}datepickerbutton`).click(); // re-open popup (Chromium closes it after Today click)
  await h.byCssSelector('input.nextMonthButton').click();
  dateToCheck = nextMonthDate;
  await h.assertSelected(`${base}datepicker_month`, phpDate('m', dateToCheck));
  await h.assertSelected(`${base}datepicker_day`, phpDate('d', dateToCheck));
  await h.assertSelected(`${base}datepicker_year`, phpDate('Y', dateToCheck));
  await h.assertText(`${base}status`, phpDate('m-d-Y', dateToCheck));

  await h.byId('ctl0_ctl1').click();

  await h.assertText(`${base}status2`, '');
  dateToCheck = Date.now() / 1000;
  await h.assertSelected(`${base}datepicker2_month`, phpDate('m', dateToCheck));
  await h.assertSelected(`${base}datepicker2_day`, phpDate('d', dateToCheck));
  await h.assertSelected(`${base}datepicker2_year`, phpDate('Y', dateToCheck));
  const yearPlus1 = String(new Date().getFullYear() + 1);
  await h.select(`${base}datepicker2_year`, yearPlus1);
  const d2 = new Date();
  const ts2 = Date.UTC(d2.getUTCFullYear() + 1, d2.getUTCMonth(), d2.getUTCDate()) / 1000;
  await h.assertText(`${base}status2`, phpDate('m-d-Y', ts2));

  await h.assertText(`${base}status3`, '');
  dateToCheck = Date.now() / 1000;
  await h.assertSelected(`${base}datepicker3_month`, phpDate('F', dateToCheck));
  await h.assertSelected(`${base}datepicker3_year`, phpDate('Y', dateToCheck));
  await h.select(`${base}datepicker3_year`, yearPlus1);
  const d3 = new Date();
  const ts3 = Date.UTC(d3.getUTCFullYear() + 1, d3.getUTCMonth(), d3.getUTCDate()) / 1000;
  // date('m/Y', ...) format
  const pad = n => String(n).padStart(2, '0');
  const d3obj = new Date(ts3 * 1000);
  await h.assertText(`${base}status3`, `${pad(d3obj.getUTCMonth() + 1)}/${d3obj.getUTCFullYear()}`);
});
