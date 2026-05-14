import { test, expect } from '@playwright/test';
import { genericHelper } from '../helpers.js';

test('TimeTriggeredCallbackTestCase', async ({ page }) => {
  const h = genericHelper(page);
  const base = 'ctl0_Content_';
  await h.url('active-controls/index.php?page=TimeTriggeredCallbackTest');
  await h.assertSourceContains('TimeTriggeredCallback + ViewState Tests');

  await h.assertText(`${base}label1`, 'ViewState Counter :');

  await h.byId(`${base}button1`).click();

  await h.pause(8000);

  await h.assertText(`${base}label1`, 'ViewState Counter : 1 2 3 4 5 6 7 8 9 10');
});
