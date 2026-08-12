import { test, expect } from '@playwright/test';
import { genericHelper } from '../helpers.js';

test('ActiveDetailsTestCase', async ({ page }) => {
  const h = genericHelper(page);
  const base = 'ctl0_Content_';
  await h.url('active-controls/index.php?page=ActiveDetailsTest');
  await h.assertSourceContains('Active Details Test Case');

  const details = h.byId(`${base}details1`);
  await expect(details).toHaveJSProperty('open', false);

  // User opens the disclosure widget -> OnOpen server event fires
  await h.byCssSelector(`#${base}details1 > summary`).click();
  await h.waitForAjaxCalls();
  await h.assertText(`${base}label1`, 'opened');
  await expect(details).toHaveJSProperty('open', true);

  // User closes it -> OnClose server event fires
  await h.byCssSelector(`#${base}details1 > summary`).click();
  await h.waitForAjaxCalls();
  await h.assertText(`${base}label1`, 'closed');
  await expect(details).toHaveJSProperty('open', false);

  // Server-side open: widget opens, and the toggle event must NOT echo a
  // callback back to the server (label would flip from 'server-opened' to 'opened')
  await h.click(`${base}button1`);
  await h.waitForAjaxCalls();
  await expect(details).toHaveJSProperty('open', true);
  await h.assertText(`${base}label1`, 'server-opened');
  await h.pause(500);
  await h.assertText(`${base}label1`, 'server-opened');

  // Server-side close: widget closes, no echo either
  await h.click(`${base}button2`);
  await h.waitForAjaxCalls();
  await expect(details).toHaveJSProperty('open', false);
  await h.assertText(`${base}label1`, 'server-closed');
  await h.pause(500);
  await h.assertText(`${base}label1`, 'server-closed');
});
