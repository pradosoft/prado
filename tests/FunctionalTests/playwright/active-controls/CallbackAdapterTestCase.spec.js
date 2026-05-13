import { test, expect } from '@playwright/test';
import { genericHelper } from '../helpers.js';

test('CallbackAdapterTestCase', async ({ page }) => {
  const h = genericHelper(page);
  const base = 'ctl0_Content_';
  await h.url('active-controls/index.php?page=ControlAdapterTest');
  await h.assertSourceContains('Control Adapter - State Tracking Tests');

  await h.byId(`${base}button2`).click();
  await h.pause(50);
  expect(h.alertText()).toBe('ok');
  h.acceptAlert();

  await h.byId(`${base}test6`).click();
  await h.byId(`${base}test7`).click();
  await h.byId(`${base}test8`).click();
  await h.byId(`${base}test9`).click();

  await h.byId(`${base}button1`).click();
  await h.pause(50);
  expect(h.alertText()).toBe('haha!');
  h.acceptAlert();

  await h.byId(`${base}button2`).click();
  await h.pause(50);
  expect(h.alertText()).toBe('ok');
  h.acceptAlert();
  await h.pause(500);
  expect(h.alertText()).toBe('baz!');
  h.acceptAlert();
});
