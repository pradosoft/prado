import { test, expect } from '@playwright/test';
import { demosHelper } from '../../helpers.js';

test('QuickstartDropDownListTestCase', async ({ page }) => {
  const h = demosHelper(page);

  await h.url('quickstart/index.php?page=Controls.Samples.TDropDownList.Home&notheme=true&lang=en');

  await h.assertTitle('PRADO QuickStart Sample');

  // dropdown list with default settings
  await h.assertElementPresent('ctl0$body$ctl0');

  // dropdown list with initial options
  expect(await h.getSelectOptions('ctl0$body$ctl1')).toEqual(['item 1', 'item 2', 'item 3', 'item 4']);
  await h.assertSelected('ctl0$body$ctl1', 'item 2');

  // dropdown list with customized styles
  expect(await h.getSelectOptions('ctl0$body$ctl2')).toEqual(['item 1', 'item 2', 'item 3', 'item 4']);
  await h.assertSelected('ctl0$body$ctl2', 'item 2');

  // a disabled dropdown list
  await h.assertAttribute('ctl0$body$ctl3@disabled', 'regexp:true|disabled');

  // an auto postback dropdown list
  await h.assertSourceNotContains('Your selection is: (Index: 2, Value: value 3, Text: item 3)');
  await h.select('ctl0$body$ctl4', 'item 3');
  await h.assertSourceContains('Your selection is: (Index: 2, Value: value 3, Text: item 3)');

  // a single selection list box upon postback
  await h.select('ctl0$body$DropDownList1', 'item 4');
  await h.assertSourceNotContains('Your selection is: (Index: 3, Value: value 4, Text: item 4)');
  await h.byXPath("//input[@type='submit' and @value='Submit']").first().click();
  await h.assertSourceContains('Your selection is: (Index: 3, Value: value 4, Text: item 4)');

  // Databind to an integer-indexed array
  await h.select('ctl0$body$DBDropDownList1', 'item 3');
  await h.assertSourceContains('Your selection is: (Index: 2, Value: 2, Text: item 3)');

  // Databind to an associative array
  await h.select('ctl0$body$DBDropDownList2', 'item 2');
  await h.assertSourceContains('Your selection is: (Index: 1, Value: key 2, Text: item 2)');

  // Databind with DataTextField and DataValueField specified
  await h.select('ctl0$body$DBDropDownList3', 'Cary');
  await h.assertSourceContains('Your selection is: (Index: 2, Value: 003, Text: Cary)');

  // dropdown list is being validated
  await h.assertNotVisible('ctl0_body_ctl6');
  await h.byId('ctl0_body_ctl7').click();
  await h.assertVisible('ctl0_body_ctl6');
  await h.select('ctl0$body$VDropDownList1', 'item 2');
  await h.byId('ctl0_body_ctl7').click();
  await h.assertNotVisible('ctl0_body_ctl6');

  // dropdown list causing validation
  await h.assertNotVisible('ctl0_body_ctl8');
  await h.select('ctl0$body$VDropDownList2', 'Disagree');
  await h.pause(1000);
  await h.assertVisible('ctl0_body_ctl8');
  await h.type('ctl0$body$TextBox', 'test');
  await h.select('ctl0$body$VDropDownList2', 'Agree');
  await h.assertNotVisible('ctl0_body_ctl8');
});
