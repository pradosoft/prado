import { test, expect } from '@playwright/test';
import { demosHelper } from '../../helpers.js';

test('QuickstartActiveCustomValidatorTestCase', async ({ page }) => {
  const h = demosHelper(page);

  await h.url('quickstart/index.php?page=ActiveControls.Samples.TActiveCustomValidator.Home&notheme=true&lang=en');

  await h.assertTitle('PRADO QuickStart Sample');

  await h.assertSourceContains('TActiveCustomValidator Samples (AJAX)');

  const base = 'ctl0_body_';

  await h.assertNotVisible(base + 'validator1');
  await h.byId(base + 'button1').click();
  await h.waitForAjaxCalls();
  await h.assertVisible(base + 'validator1');

  await h.type(base + 'textbox1', 'hello');
  await h.assertVisible(base + 'validator1');

  await h.type(base + 'textbox1', 'Prado');
  await h.assertVisible(base + 'validator1');

  await h.byId(base + 'button1').click();
  await h.waitForAjaxCalls();
  await h.assertNotVisible(base + 'validator1');
});
