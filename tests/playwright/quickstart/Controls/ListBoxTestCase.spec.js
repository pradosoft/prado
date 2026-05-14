import { test, expect } from '@playwright/test';
import { demosHelper } from '../../helpers.js';

test('QuickstartListBoxTestCase', async ({ page }) => {
  const h = demosHelper(page);

  await h.url('quickstart/index.php?page=Controls.Samples.TListBox.Home&notheme=true&lang=en');

  // a default single selection listbox
  await h.assertAttribute('ctl0$body$ctl0@size', '4');

  // single selection list box with initial options
  expect(await h.getSelectOptions('ctl0$body$ctl1')).toEqual(['item 1', 'item 2', 'item 3', 'item 4']);
  await h.assertSelected('ctl0$body$ctl1', 'item 2');

  // a single selection list box with customized style
  await h.assertAttribute('ctl0$body$ctl2@size', '3');
  expect(await h.getSelectOptions('ctl0$body$ctl2')).toEqual(['item 1', 'item 2', 'item 3', 'item 4']);
  await h.assertSelected('ctl0$body$ctl2', 'item 2');

  // a disabled list box
  await h.assertAttribute('ctl0$body$ctl3@disabled', 'regexp:true|disabled');

  // an auto postback single selection list box
  await h.assertSourceNotContains('Your selection is: (Index: 2, Value: value 3, Text: item 3)');
  await h.select('ctl0$body$ctl4', 'item 3');
  await h.assertSourceContains('Your selection is: (Index: 2, Value: value 3, Text: item 3)');

  // a single selection list box upon postback
  await h.select('ctl0$body$ListBox1', 'item 4');
  await h.assertSourceNotContains('Your selection is: (Index: 3, Value: value 4, Text: item 4)');
  await h.byXPath("//input[@type='submit' and @value='Submit']").first().click();
  await h.assertSourceContains('Your selection is: (Index: 3, Value: value 4, Text: item 4)');

  // a multiple selection list box
  await h.assertAttribute('ctl0$body$ctl6[]@size', '4');
  await h.assertAttribute('ctl0$body$ctl6[]@multiple', 'regexp:true|multiple');

  // a multiple selection list box with initial options
  await h.assertAttribute('ctl0$body$ctl7[]@multiple', 'regexp:true|multiple');
  expect(await h.getSelectOptions('ctl0$body$ctl7[]')).toEqual(['item 1', 'item 2', 'item 3', 'item 4']);

  // multiselection list box's behavior upon postback
  await h.addSelection('ctl0$body$ListBox2[]', 'item 3');
  await h.byName('ctl0$body$ctl8').click();
  await h.pause(50);
  await h.assertText('ctl0_body_MultiSelectionResult2', 'Your selection is: (Index: 1, Value: value 2, Text: item 2)(Index: 2, Value: value 3, Text: item 3)(Index: 3, Value: value 4, Text: item 4)');

  // Auto postback multiselection list box
  await h.addSelection('ctl0$body$ctl9[]', 'item 1');
  await h.assertText('ctl0_body_MultiSelectionResult', 'Your selection is: (Index: 0, Value: value 1, Text: item 1)(Index: 1, Value: value 2, Text: item 2)(Index: 3, Value: value 4, Text: item 4)');

  // Databind to an integer-indexed array
  await h.select('ctl0$body$DBListBox1[]', 'item 3');
  await h.assertSourceContains('Your selection is: (Index: 2, Value: 2, Text: item 3)');

  // Databind to an associative array
  await h.select('ctl0$body$DBListBox2[]', 'item 2');
  await h.assertSourceContains('Your selection is: (Index: 1, Value: key 2, Text: item 2)');

  // Databind with DataTextField and DataValueField specified
  await h.select('ctl0$body$DBListBox3[]', 'Cary');
  await h.assertSourceContains('Your selection is: (Index: 2, Value: 003, Text: Cary)');

  // List box is being validated
  await h.assertNotVisible('ctl0_body_ctl10');
  await h.byId('ctl0_body_ctl11').click();
  await h.assertVisible('ctl0_body_ctl10');
  await h.select('ctl0$body$VListBox1', 'item 2');
  await h.byId('ctl0_body_ctl11').click();
  await h.assertNotVisible('ctl0_body_ctl10');

  // List box causing validation
  await h.assertNotVisible('ctl0_body_ctl12');
  await h.select('ctl0$body$VListBox2', 'Agree');
  await h.assertVisible('ctl0_body_ctl12');
  await h.type('ctl0$body$TextBox', 'test');
  await h.select('ctl0$body$VListBox2', 'Disagree');
  await h.assertNotVisible('ctl0_body_ctl12');
});
