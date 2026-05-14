import { test, expect } from '@playwright/test';
import { genericHelper } from '../helpers.js';

test('ActiveDropDownListTestCase', async ({ page }) => {
  const h = genericHelper(page);
  const base = 'ctl0_Content_';
  await h.url('active-controls/index.php?page=ActiveDropDownList');
  await h.assertSourceContains('Active Drop Down List Test Case');

  await h.assertText(`${base}label1`, 'Label 1');

  await h.byId(`${base}button1`).click();
  await h.waitForAjaxCalls();
  await h.assertSelected(`${base}list1`, 'item 4');

  await h.byId(`${base}button2`).click();
  await h.waitForAjaxCalls();
  await h.assertSelectedValue(`${base}list1`, 'value 1');

  await h.byId(`${base}button3`).click();
  await h.waitForAjaxCalls();
  await h.assertSelected(`${base}list1`, 'item 2');

  await h.assertText(`${base}label1`, 'Selection 1: value 1');

  await h.select(`${base}list1`, 'item 1');
  await h.waitForAjaxCalls();
  await h.select(`${base}list2`, 'value 1 - item 4');
  await h.waitForAjaxCalls();
  await h.assertText(`${base}label2`, 'Selection 2: value 1 - item 4');

  await h.select(`${base}list1`, 'item 3');
  await h.waitForAjaxCalls();
  await h.select(`${base}list2`, 'value 3 - item 5');
  await h.waitForAjaxCalls();
  await h.assertText(`${base}label2`, 'Selection 2: value 3 - item 5');

  await h.byId(`${base}button4`).click();
  await h.waitForAjaxCalls();
  await h.assertSelected(`${base}list1`, 'item 3');
  await h.pause(300);
  await h.assertSelected(`${base}list2`, 'value 3 - item 3');
});
