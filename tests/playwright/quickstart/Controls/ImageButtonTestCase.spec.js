import { test, expect } from '@playwright/test';
import { demosHelper } from '../../helpers.js';

test('QuickstartImageButtonTestCase', async ({ page }) => {
  const h = demosHelper(page);

  await h.url('quickstart/index.php?page=Controls.Samples.TImageButton.Home&notheme=true&lang=en');

  await h.assertTitle('PRADO QuickStart Sample');

  // a click button
  await h.byXPath("//input[@type='image' and @alt='hello world']").first().click();
  await h.assertSourceContains('You clicked at ');

  // a command button
  await h.byName('ctl0$body$ctl1').click();
  await h.assertSourceContains('Command name: test, Command parameter: value');

  // a button causing validation
  await h.assertNotVisible('ctl0_body_ctl2');
  await h.byId('ctl0_body_ctl3').click();
  await h.assertVisible('ctl0_body_ctl2');
  await h.type('ctl0$body$TextBox', 'test');
  await h.byId('ctl0_body_ctl3').click();
  await h.assertNotVisible('ctl0_body_ctl2');
});
