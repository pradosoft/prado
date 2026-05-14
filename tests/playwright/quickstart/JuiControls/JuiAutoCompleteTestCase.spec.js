import { test, expect } from '@playwright/test';
import { demosHelper } from '../../helpers.js';

test('JuiAutoCompleteTestCase', async ({ page }) => {
  const h = demosHelper(page);

  await h.url('quickstart/index.php?page=JuiControls.Samples.TJuiAutoComplete.Home&notheme=true&lang=en');

  await h.assertTitle('PRADO QuickStart Sample');

  await h.assertSourceContains('TJuiAutoComplete Samples');

  const base = 'ctl0_body_';

  await h.assertText(`${base}Selection1`, '');

  await h.byId(`${base}AutoComplete`).click();
  await h.keys('J');
  await h.pause(500);
  await h.assertSourceContains('John');

  await h.byCssSelector(`#${base}AutoComplete_result ul li`).click();
  await h.assertValue(`${base}AutoComplete`, 'John');
  await h.assertText(`${base}Selection1`, 'Selected ID: 1');

  await h.byId(`${base}AutoComplete2`).click();
  await h.keys('Joh');
  await h.pause(500);
  await h.byCssSelector(`#${base}AutoComplete2_result ul li`).click();
  await h.assertValue(`${base}AutoComplete2`, 'John');
  await h.assertText(`${base}Selection2`, 'Selected ID: 1');

  await h.keys(',Ge');
  await h.pause(500);
  await h.byCssSelector(`#${base}AutoComplete2_result ul li`).click();
  await h.pause(500);
  await h.assertValue(`${base}AutoComplete2`, 'John,George');
  await h.assertText(`${base}Selection2`, 'Selected ID: 3');
});
