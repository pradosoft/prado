import { test, expect } from '@playwright/test';
import { genericHelper } from '../helpers.js';

test('ActiveListBoxTestCase', async ({ page }) => {
  const h = genericHelper(page);
  const base = 'ctl0_Content_';
  await h.url('active-controls/index.php?page=ActiveListBoxTest');
  await h.assertSourceContains('Active List Box Functional Test');

  await h.assertText(`${base}label1`, 'Label 1');

  await h.byId(`${base}button1`).click();
  await h.waitForAjaxCalls();
  expect(await h.getSelectedLabels(`${base}list1`)).toEqual(['item 2', 'item 3', 'item 4']);

  await h.byId(`${base}button3`).click();
  await h.waitForAjaxCalls();
  expect(await h.getSelectedLabels(`${base}list1`)).toEqual(['item 1']);

  await h.byId(`${base}button4`).click();
  await h.waitForAjaxCalls();
  expect(await h.getSelectedLabels(`${base}list1`)).toEqual(['item 5']);

  await h.byId(`${base}button5`).click();
  await h.waitForAjaxCalls();
  expect(await h.getSelectedLabels(`${base}list1`)).toEqual(['item 2', 'item 5']);

  await h.byId(`${base}button2`).click();
  await h.waitForAjaxCalls();
  await h.assertNotSomethingSelected(`${base}list1`);

  await h.byId(`${base}button6`).click();
  await h.waitForAjaxCalls();
  await h.byId(`${base}button1`).click();
  await h.waitForAjaxCalls();
  expect(await h.getSelectedLabels(`${base}list1`)).toEqual(['item 2', 'item 3', 'item 4']);

  await h.select(`${base}list1`, 'item 1');
  await h.waitForAjaxCalls();
  await h.assertText(`${base}label1`, 'Selection: value 1');

  await h.addSelection(`${base}list1`, 'item 4');
  await h.waitForAjaxCalls();
  await h.assertText(`${base}label1`, 'Selection: value 1, value 4');
});
