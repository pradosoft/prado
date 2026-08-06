import { test, expect } from '@playwright/test';
import { genericHelper } from '../helpers.js';

test('ActiveMeterTestCase', async ({ page }) => {
  const h = genericHelper(page);
  const base = 'ctl0_Content_';
  await h.url('active-controls/index.php?page=ActiveMeterTest');
  await h.assertSourceContains('Active Meter Test Case');

  // Initial render: value only, defaults omitted
  await h.assertAttribute(`${base}meter1@value`, '0.25');
  await h.assertAttribute(`${base}meter1@low`, null);

  // Server sets scale and segment boundaries during a callback
  await h.click(`${base}button1`);
  await h.waitForAjaxCalls();
  await h.assertAttribute(`${base}meter1@max`, '100');
  await h.assertAttribute(`${base}meter1@value`, '70');
  await h.assertAttribute(`${base}meter1@low`, '25');
  await h.assertAttribute(`${base}meter1@high`, '85');
  await h.assertAttribute(`${base}meter1@optimum`, '10');

  // Server sets segments to null: the attributes are removed
  await h.click(`${base}button2`);
  await h.waitForAjaxCalls();
  await h.assertAttribute(`${base}meter1@low`, null);
  await h.assertAttribute(`${base}meter1@high`, null);
  await h.assertAttribute(`${base}meter1@optimum`, null);
  await h.assertAttribute(`${base}meter1@value`, '70');

  // Server shifts the range into negative values
  await h.click(`${base}button3`);
  await h.waitForAjaxCalls();
  await h.assertAttribute(`${base}meter1@min`, '-10');
  await h.assertAttribute(`${base}meter1@value`, '-5');
});
