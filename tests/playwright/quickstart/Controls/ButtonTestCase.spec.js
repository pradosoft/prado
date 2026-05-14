import { test, expect } from '@playwright/test';
import { demosHelper } from '../../helpers.js';

test('QuickstartButtonTestCase', async ({ page }) => {
  const h = demosHelper(page);

  await h.url('quickstart/index.php?page=Controls.Samples.TButton.Home&notheme=true&lang=en');

  await h.assertTitle('PRADO QuickStart Sample');

  // a regular button
  await h.byXPath("//input[@type='submit' and @value='text']").click();

  // a click button
  await h.assertElementNotPresent("//input[@type='submit' and @value=\"I'm clicked\"]");
  await h.byXPath("//input[@type='submit' and @value='click me']").first().click();
  await h.pause(50);
  await h.assertElementPresent("//input[@type='submit' and @value=\"I'm clicked\"]");

  // a command button
  await h.assertElementNotPresent("//input[@type='submit' and @value=\"Name: test, Param: value\"]");
  await h.byXPath("//input[@type='submit' and @value='click me']").first().click();
  await h.pause(50);
  await h.assertElementPresent("//input[@type='submit' and @value=\"Name: test, Param: value\"]");

  // a button causing validation
  await h.assertNotVisible('ctl0_body_ctl3');
  await h.byXPath("//input[@type='submit' and @value='submit']").click();
  await h.assertVisible('ctl0_body_ctl3');
  await h.type('ctl0$body$TextBox', 'test');
  await h.byXPath("//input[@type='submit' and @value='submit']").click();
  await h.assertNotVisible('ctl0_body_ctl3');
});
