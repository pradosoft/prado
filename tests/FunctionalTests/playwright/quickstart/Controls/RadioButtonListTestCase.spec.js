import { test, expect } from '@playwright/test';
import { demosHelper } from '../../helpers.js';

test('QuickstartRadioButtonListTestCase', async ({ page }) => {
  const h = demosHelper(page);

  await h.url('quickstart/index.php?page=Controls.Samples.TRadioButtonList.Home&notheme=true&lang=en');

  // RadioButton list with default settings:
  await h.byXPath("//input[@name='ctl0$body$ctl0' and @value='value 3']").click();

  // RadioButton list with customized cellpadding, cellspacing, color and text alignment:
  await h.byXPath("//input[@name='ctl0$body$ctl1' and @value='value 1']").click();

  // RadioButton list's behavior upon postback
  await h.byXPath("//input[@name='ctl0$body$RadioButtonList' and @value='value 3']").click();
  await h.byXPath("//input[@type='submit' and @value='Submit']").click();
  await h.assertSourceContains('Your selection is: (Index: 2, Value: value 3, Text: item 3)');

  // Auto postback radio button list
  await h.byXPath("//input[@name='ctl0$body$ctl7' and @value='value 5']").click();
  await h.assertSourceContains('Your selection is: (Index: 4, Value: value 5, Text: item 5)');

  // Databind to an integer-indexed array
  await h.byXPath("//input[@name='ctl0$body$DBRadioButtonList1' and @value='0']").click();
  await h.assertSourceContains('Your selection is: (Index: 0, Value: 0, Text: item 1)');

  // Databind to an associative array:
  await h.byXPath("//input[@name='ctl0$body$DBRadioButtonList2' and @value='key 2']").click();
  await h.assertSourceContains('Your selection is: (Index: 1, Value: key 2, Text: item 2)');

  // Databind with DataTextField and DataValueField specified
  await h.byXPath("//input[@name='ctl0$body$DBRadioButtonList3' and @value='003']").click();
  await h.assertSourceContains('Your selection is: (Index: 2, Value: 003, Text: Cary)');

  // RadioButton list causing validation
  await h.assertNotVisible('ctl0_body_ctl8');
  await h.byXPath("//input[@name='ctl0$body$ctl9' and @value='Agree']").click();
  await h.assertVisible('ctl0_body_ctl8');
  await h.type('ctl0$body$TextBox', 'test');
  await h.pause(50);
  await h.byXPath("//input[@name='ctl0$body$ctl9' and @value='Disagree']").click();
  await h.assertNotVisible('ctl0_body_ctl8');
});
