import { test, expect } from '@playwright/test';
import { genericHelper } from '../helpers.js';

test('ActiveDialogTestCase', async ({ page }) => {
  const h = genericHelper(page);
  const base = 'ctl0_Content_';
  await h.url('active-controls/index.php?page=ActiveDialogTest');
  await h.assertSourceContains('Active Dialog Test Case');

  const dialog = h.byId(`${base}dialog1`);
  await expect(dialog).toHaveJSProperty('open', false);

  // Client-side show() -> MutationObserver dispatches 'open' -> OnOpen fires
  await h.byId('showBtn').click();
  await h.waitForAjaxCalls();
  await expect(dialog).toHaveJSProperty('open', true);
  await h.assertText(`${base}label1`, 'opened');

  // Client-side close() -> native close event -> OnClose fires
  await h.byId('dismissBtn').click();
  await h.waitForAjaxCalls();
  await expect(dialog).toHaveJSProperty('open', false);
  await h.assertText(`${base}label1`, 'closed');

  // Server-side open: dialog opens, and the open mutation must NOT echo a
  // callback back to the server (label would flip from 'server-opened' to 'opened')
  await h.click(`${base}button1`);
  await h.waitForAjaxCalls();
  await expect(dialog).toHaveJSProperty('open', true);
  await h.assertText(`${base}label1`, 'server-opened');
  await h.pause(500);
  await h.assertText(`${base}label1`, 'server-opened');

  // Server-side close: dialog closes, and the close event must NOT echo either
  await h.click(`${base}button2`);
  await h.waitForAjaxCalls();
  await expect(dialog).toHaveJSProperty('open', false);
  await h.assertText(`${base}label1`, 'server-closed');
  await h.pause(500);
  await h.assertText(`${base}label1`, 'server-closed');
});

/**
 * A close button's value reaches OnClose as ReturnValue; opening resets it, so
 * Esc reports ''.
 */
test('ActiveDialogTestCase: ReturnValue from invoker buttons', async ({ page }) => {
  const h = genericHelper(page);
  const base = 'ctl0_Content_';
  await h.url('active-controls/index.php?page=ActiveDialogTest');
  const dialog = h.byId(`${base}dialog2`);

  await page.getByRole('button', { name: 'Confirm' }).click();
  await h.waitForAjaxCalls();
  expect(await dialog.evaluate((el) => el.matches(':modal'))).toBe(true);
  await page.getByRole('button', { name: 'OK' }).click();
  await h.waitForAjaxCalls();
  await h.assertText(`${base}label2`, 'closed:ok');

  await page.getByRole('button', { name: 'Confirm' }).click();
  await h.waitForAjaxCalls();
  await page.keyboard.press('Escape');
  await h.waitForAjaxCalls();
  await expect(dialog).toHaveJSProperty('open', false);
  await h.assertText(`${base}label2`, 'closed:');
});

/**
 * A Modal dialog opened by the server in a callback shows modally, without an
 * open callback echoed back.
 */
test('ActiveDialogTestCase: server open of a Modal dialog', async ({ page }) => {
  const h = genericHelper(page);
  const base = 'ctl0_Content_';
  await h.url('active-controls/index.php?page=ActiveDialogTest');
  const dialog = h.byId(`${base}dialog2`);

  await h.click(`${base}button3`);
  await h.waitForAjaxCalls();
  await expect(dialog).toHaveJSProperty('open', true);
  expect(await dialog.evaluate((el) => el.matches(':modal'))).toBe(true);
  await h.pause(500);
  await h.assertText(`${base}label2`, 'server-opened');

  await page.getByRole('button', { name: 'Cancel' }).click();
  await h.waitForAjaxCalls();
  await h.assertText(`${base}label2`, 'closed:cancel');
});
