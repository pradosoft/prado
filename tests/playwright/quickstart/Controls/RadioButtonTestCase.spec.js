import { test, expect } from '@playwright/test';
import { demosHelper } from '../../helpers.js';

test('QuickstartRadioButtonTestCase', async ({ page }) => {
  const h = demosHelper(page);

  await h.url('quickstart/index.php?page=Controls.Samples.TRadioButton.Home&notheme=true&lang=en');

  await h.assertTitle('PRADO QuickStart Sample');

  // a regular radiobutton
  await h.byXPath("//input[@name='ctl0$body$ctl0' and @value='ctl0$body$ctl0']").click();

  // a radiobutton with customized value
  await h.byXPath("//input[@name='ctl0$body$ctl1' and @value='value']").click();

  // an auto postback radiobutton
  await h.assertSourceNotContains("I'm clicked");
  await h.byXPath("//input[@name='ctl0$body$ctl2' and @value='ctl0$body$ctl2']").click();
  await h.assertSourceContains("I'm clicked");
  await h.byXPath("//input[@name='ctl0$body$ctl2' and @value='ctl0$body$ctl2']").click();
  await h.assertSourceContains("I'm clicked");

  // a radiobutton causing validation on a textbox
  await h.assertNotVisible('ctl0_body_ctl3');
  await h.byXPath("//input[@name='ctl0$body$ctl4' and @value='ctl0$body$ctl4']").click();
  await h.pause(1000);
  await h.assertVisible('ctl0_body_ctl3');
  await h.byXPath("//input[@name='ctl0$body$ctl4' and @value='ctl0$body$ctl4']").click();
  await h.pause(500);
  await h.assertVisible('ctl0_body_ctl3');
  await h.type('ctl0$body$TextBox', 'test');
  await h.byXPath("//input[@name='ctl0$body$ctl4' and @value='ctl0$body$ctl4']").click();
  await h.assertNotVisible('ctl0_body_ctl3');

  // a radiobutton validated by a required field validator
  await h.assertNotVisible('ctl0_body_ctl6');
  await h.byXPath("//input[@type='submit' and @value='Submit']").first().click();
  await h.pause(1000);
  await h.assertVisible('ctl0_body_ctl6');
  await h.byXPath("//input[@name='ctl0$body$RadioButton' and @value='ctl0$body$RadioButton']").click();
  await h.byXPath("//input[@type='submit' and @value='Submit']").first().click();
  await h.assertNotVisible('ctl0_body_ctl6');

  // a radiobutton group
  await h.byName('ctl0$body$ctl7').click();
  await h.assertSourceContains('Your selection is empty');
  await h.byXPath("//input[@name='ctl0$body$RadioGroup' and @value='ctl0$body$Radio2']").click();
  await h.byName('ctl0$body$ctl7').click();
  await h.assertSourceContains('Your selection is 2');
  await h.byXPath("//input[@name='ctl0$body$RadioGroup' and @value='ctl0$body$Radio3']").click();
  await h.byXPath("//input[@name='ctl0$body$Radio4' and @value='ctl0$body$Radio4']").click();
  await h.byName('ctl0$body$ctl7').click();
  await h.assertSourceContains('Your selection is 34');
});
