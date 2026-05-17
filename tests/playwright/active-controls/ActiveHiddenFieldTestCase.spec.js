import { test, expect } from '@playwright/test';
import { genericHelper } from '../helpers.js';

test('ActiveHiddenFieldTestCase', async ({ page }) => {
  const h = genericHelper(page);
  const base = 'ctl0_Content_';
  await h.url('active-controls/index.php?page=ActiveHiddenFieldTest');
  const fieldEmpty = 'No longer empty';
  const fieldUsed = 'My value';

  await h.assertSourceContains('Value of current hidden field');
  await h.byId(`${base}Button1`).click();
  await h.assertText(`${base}ResponseLabel`, fieldEmpty);
  await h.byId(`${base}Button2`).click();
  await h.assertText(`${base}ResponseLabel`, fieldUsed);
  await h.byId(`${base}Button3`).click();
  await h.assertText(`${base}ResponseLabel`, `${fieldEmpty}|${fieldUsed}`);
});
