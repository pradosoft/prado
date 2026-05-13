import { test, expect } from '@playwright/test';
import { genericHelper } from '../helpers.js';

test('Issue724TestCase', async ({ page }) => {
  const h = genericHelper(page);
  await h.url('issues/index.php?page=Issue724');
  await h.assertSourceContains('Issue 724 Test');
  const base = 'ctl0_Content_';

  await h.byId(`${base}cmdA`).click();
  await h.byId(`${base}cmdB`).click();
  await h.pause(6000);
  await h.assertText(`${base}labelA`, 'Button A Pressed');
  await h.assertText(`${base}labelB`, 'When button has been B pressed, the text of label A was: Button A Pressed');
});
