import { test, expect } from '@playwright/test';
import { genericHelper } from '../helpers.js';

test('ReplaceContentTestCase', async ({ page }) => {
  const h = genericHelper(page);
  const base = 'ctl0_Content_';
  await h.url('active-controls/index.php?page=ReplaceContentTest');
  await h.assertSourceContains('Callback Replace Content Test');

  await h.assertText(`${base}subpanel`, 'Sub Panel');
  await h.assertText(`${base}panel1`, 'Main Panel\nSub Panel');

  await h.type(`${base}content`, 'something');

  await h.byId(`${base}btn_append`).click();
  await h.waitForAjaxCalls();

  await h.assertText(`${base}subpanel`, 'Sub Panel something');
  await h.assertText(`${base}panel1`, 'Main Panel\nSub Panel something');

  await h.type(`${base}content`, 'more');
  await h.byId(`${base}btn_prepend`).click();
  await h.waitForAjaxCalls();

  await h.assertText(`${base}subpanel`, 'more Sub Panel something');
  await h.assertText(`${base}panel1`, 'Main Panel\nmore Sub Panel something');

  await h.type(`${base}content`, 'prado');
  await h.byId(`${base}btn_before`).click();
  await h.waitForAjaxCalls();

  await h.assertText(`${base}subpanel`, 'more Sub Panel something');
  await h.assertText(`${base}panel1`, 'Main Panel pradomore Sub Panel something');

  await h.type(`${base}content`, ' php ');
  await h.byId(`${base}btn_after`).click();
  await h.waitForAjaxCalls();

  await h.type(`${base}content`, 'mauahahaha');
  await h.byId(`${base}btn_replace`).click();
  await h.waitForAjaxCalls();
  await h.pause(1000);

  await h.assertText(`${base}panel1`, 'Main Panel pradomauahahaha php');
});
