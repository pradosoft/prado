import { test, expect } from '@playwright/test';
import { demosHelper } from '../../helpers.js';

test('QuickstartCheckBoxTestCase', async ({ page }) => {
  const h = demosHelper(page);

  await h.url('quickstart/index.php?page=Controls.Samples.TCheckBox.Home&notheme=true&lang=en');

  await h.assertTitle('PRADO QuickStart Sample');

  // a regular checkbox
  await h.byXPath("//input[@name='ctl0$body$ctl0']").click();

  // a checkbox with customized value
  await h.byXPath("//input[@name='ctl0$body$ctl1' and @value='value']").click();

  // an auto postback checkbox
  await h.assertSourceNotContains("I'm clicked");
  await h.byXPath("//input[@name='ctl0$body$ctl2']").click();
  await h.assertSourceContains("I'm clicked");
  await h.byXPath("//input[@name='ctl0$body$ctl2']").click();
  await h.assertSourceContains("I'm clicked");

  // a checkbox causing validation on a textbox
  await h.assertNotVisible('ctl0_body_ctl3');
  await h.byXPath("//input[@name='ctl0$body$ctl4']").click();
  await h.assertVisible('ctl0_body_ctl3');
  await h.byXPath("//input[@name='ctl0$body$ctl4']").click();
  await h.assertVisible('ctl0_body_ctl3');
  await h.type('ctl0$body$TextBox', 'test');
  await h.byXPath("//input[@name='ctl0$body$ctl4']").click();
  await h.assertNotVisible('ctl0_body_ctl3');

  // a checkbox validated by a required field validator
  await h.assertNotVisible('ctl0_body_ctl6');
  await h.byXPath("//input[@type='submit' and @value='Submit']").click();
  await h.assertVisible('ctl0_body_ctl6');
  await h.byXPath("//input[@name='ctl0$body$CheckBox']").click();
  await h.byXPath("//input[@type='submit' and @value='Submit']").click();
  await h.assertNotVisible('ctl0_body_ctl6');

  // a checkbox validated by a required field validator using AutoPostBack
  await h.assertNotVisible('ctl0_body_ctl7');
  await h.byXPath("//input[@name='ctl0$body$CheckBox2']").click();
  await h.assertVisible('ctl0_body_ctl7');
});
