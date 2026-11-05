import { test, expect } from '@playwright/test';
import { genericHelper } from '../helpers.js';

test('ActiveProgressTestCase', async ({ page }) => {
  const h = genericHelper(page);
  const base = 'ctl0_Content_';
  await h.url('active-controls/index.php?page=ActiveProgressTest');
  await h.assertSourceContains('Active Progress Test Case');

  // Initial render: progress1 determinate at 0.25, progress2 indeterminate
  await h.assertAttribute(`${base}progress1@value`, '0.25');
  await h.assertAttribute(`${base}progress2@value`, null);

  // Server updates the value during a callback
  await h.click(`${base}button1`);
  await h.waitForAjaxCalls();
  await h.assertAttribute(`${base}progress1@value`, '0.5');

  // Server updates max and value together
  await h.click(`${base}button2`);
  await h.waitForAjaxCalls();
  await h.assertAttribute(`${base}progress1@max`, '100');
  await h.assertAttribute(`${base}progress1@value`, '75');

  // Server sets Value to null: the value attribute is removed (indeterminate)
  await h.click(`${base}button3`);
  await h.waitForAjaxCalls();
  await h.assertAttribute(`${base}progress1@value`, null);

  // Server gives the indeterminate bar a value: the attribute is added
  await h.click(`${base}button4`);
  await h.waitForAjaxCalls();
  await h.assertAttribute(`${base}progress2@value`, '0.4');
});
