import { test, expect } from '@playwright/test';
import { demosHelper } from '../../helpers.js';

test('QuickstartActiveCheckBoxTestCase', async ({ page }) => {
  const h = demosHelper(page);

  await h.url('quickstart/index.php?page=ActiveControls.Samples.TActiveCheckBox.Home&notheme=true&lang=en');

  await h.assertTitle('PRADO QuickStart Sample');

  await h.assertSourceContains('TActiveCheckBox Samples (AJAX)');

  // an auto postback checkbox
  await h.assertSourceNotContains('ctl0_body_ctl0 clicked using callback');
  await h.byXPath("//input[@name='ctl0$body$ctl0']").click();
  await h.waitForAjaxCalls();
  expect(await h.byXPath("//input[@name='ctl0$body$ctl0']").isChecked()).toBe(true);
  await h.assertSourceContains('ctl0_body_ctl0 clicked using callback');
  await h.byXPath("//input[@name='ctl0$body$ctl0']").click();
  await h.waitForAjaxCalls();
  await h.assertSourceContains('ctl0_body_ctl0 clicked using callback');
  expect(await h.byXPath("//input[@name='ctl0$body$ctl0']").isChecked()).toBe(false);

  // a checkbox causing validation on a textbox
  await h.assertNotVisible('ctl0_body_ctl1');
  await h.byXPath("//input[@name='ctl0$body$ctl2']").click();
  await h.waitForAjaxCalls();
  await h.assertVisible('ctl0_body_ctl1');
  await h.byXPath("//input[@name='ctl0$body$ctl2']").click();
  await h.waitForAjaxCalls();
  await h.assertVisible('ctl0_body_ctl3');
  await h.type('ctl0$body$TextBox', 'test');
  await h.byXPath("//input[@name='ctl0$body$ctl2']").click();
  await h.waitForAjaxCalls();
  await h.assertNotVisible('ctl0_body_ctl1');
  await h.assertSourceContains('ctl0_body_ctl2 clicked using callback');

  // a checkbox validated by a required field validator
  expect(await h.byXPath("//input[@name='ctl0$body$CheckBox']").isChecked()).toBe(false);
  await h.assertNotVisible('ctl0_body_ctl4');
  await h.byXPath("//input[@type='submit' and @value='Submit']").click();
  await h.waitForAjaxCalls();
  await h.assertVisible('ctl0_body_ctl4');
  await h.byXPath("//input[@name='ctl0$body$CheckBox']").click();
  await h.waitForAjaxCalls();
  expect(await h.byXPath("//input[@name='ctl0$body$CheckBox']").isChecked()).toBe(true);
  await h.byXPath("//input[@type='submit' and @value='Submit']").click();
  await h.waitForAjaxCalls();
  await h.assertNotVisible('ctl0_body_ctl4');
  await h.assertSourceContains('ctl0_body_CheckBox clicked');

  // a checkbox validated by a required field validator using AutoPostBack
  expect(await h.byXPath("//input[@name='ctl0$body$CheckBox2']").isChecked()).toBe(true);
  await h.assertNotVisible('ctl0_body_ctl5');
  await h.byXPath("//input[@name='ctl0$body$CheckBox2']").click();
  await h.waitForAjaxCalls();
  await h.assertVisible('ctl0_body_ctl5');
  expect(await h.byXPath("//input[@name='ctl0$body$CheckBox2']").isChecked()).toBe(true);
});
