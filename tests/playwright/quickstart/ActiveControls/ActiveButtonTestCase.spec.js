import { test, expect } from '@playwright/test';
import { demosHelper } from '../../helpers.js';

test('QuickstartActiveButtonTestCase', async ({ page }) => {
  const h = demosHelper(page);

  await h.url('quickstart/index.php?page=ActiveControls.Samples.TActiveButton.Home&notheme=true&lang=en');

  await h.assertTitle('PRADO QuickStart Sample');

  await h.assertSourceContains('TActiveButton Samples (AJAX)');

  // a click button
  await h.assertElementNotPresent("//input[@type='submit' and @value=\"I'm clicked\"]");
  await h.byXPath("//input[@type='submit' and @value='click me']").first().click();
  await h.waitForAjaxCalls();
  await h.assertElementPresent("//input[@type='submit' and @value=\"I'm clicked\"]");

  // an html5 click button
  await h.assertElementNotPresent("//button[@type='submit' and text()=\"I'm clicked\"]");
  await h.byXPath("//button[@type='submit' and text()='click me']").first().click();
  await h.waitForAjaxCalls();
  await h.assertElementPresent("//button[@type='submit' and text()=\"I'm clicked\"]");

  // a command button
  await h.assertElementNotPresent("//input[@type='submit' and @value=\"Name: test, Param: value using callback\"]");
  await h.byXPath("//input[@type='submit' and @value='click me']").first().click();
  await h.waitForAjaxCalls();
  await h.assertElementPresent("//input[@type='submit' and @value=\"Name: test, Param: value using callback\"]");

  // a button causing validation
  await h.assertNotVisible('ctl0_body_ctl3');
  await h.byXPath("//input[@type='submit' and @value='submit']").click();
  await h.waitForAjaxCalls();
  await h.assertVisible('ctl0_body_ctl3');
  await h.type('ctl0$body$TextBox', 'test');
  await h.byXPath("//input[@type='submit' and @value='submit']").click();
  await h.waitForAjaxCalls();
  await h.assertNotVisible('ctl0_body_ctl3');
  await h.assertElementPresent("//input[@type='submit' and @value=\"I'm clicked using callback\"]");
});
