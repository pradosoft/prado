import { test, expect } from '@playwright/test';
import { demosHelper } from '../../helpers.js';

test('QuickstartCheckBoxListTestCase', async ({ page }) => {
  const h = demosHelper(page);

  await h.url('quickstart/index.php?page=Controls.Samples.TCheckBoxList.Home&notheme=true&lang=en');

  // Check box list with default settings:
  await h.byXPath("//input[@name='ctl0$body$ctl0$c0' and @value='value 1']").click();

  // Check box list with customized cellpadding, cellspacing, color and text alignment:
  await h.byXPath("//input[@name='ctl0$body$ctl1$c1' and @value='value 2']").click();

  // Check box list's behavior upon postback
  await h.byXPath("//input[@name='ctl0$body$CheckBoxList$c2' and @value='value 3']").click();
  await h.byXPath("//input[@type='submit' and @value='Submit']").click();
  await h.pause(50);
  const src1 = await h.source();
  expect(src1).toContain('Your selection is: (Index: 1, Value: value 2, Text: item 2)(Index: 2, Value: value 3, Text: item 3)(Index: 4, Value: value 5, Text: item 5)');

  // Auto postback check box list
  await h.byXPath("//input[@name='ctl0$body$ctl7$c1' and @value='value 2']").click();
  await h.pause(50);
  const src2 = await h.source();
  expect(src2).toContain('Your selection is: (Index: 4, Value: value 5, Text: item 5)');

  // Databind to an integer-indexed array
  await h.byXPath("//input[@name='ctl0$body$DBCheckBoxList1$c1' and @value='1']").click();
  await h.pause(50);
  const src3 = await h.source();
  expect(src3).toContain('Your selection is: (Index: 1, Value: 1, Text: item 2)');

  // Databind to an associative array:
  await h.byXPath("//input[@name='ctl0$body$DBCheckBoxList2$c1' and @value='key 2']").click();
  await h.pause(50);
  const src4 = await h.source();
  expect(src4).toContain('Your selection is: (Index: 1, Value: key 2, Text: item 2)');

  // Databind with DataTextField and DataValueField specified
  await h.byXPath("//input[@name='ctl0$body$DBCheckBoxList3$c2' and @value='003']").click();
  await h.pause(50);
  const src5 = await h.source();
  expect(src5).toContain('Your selection is: (Index: 2, Value: 003, Text: Cary)');

  // CheckBox list causing validation
  await h.assertNotVisible('ctl0_body_ctl8');
  await h.byXPath("//input[@name='ctl0$body$ctl9$c0' and @value='Agree']").click();
  await h.assertVisible('ctl0_body_ctl8');
  await h.type('ctl0$body$TextBox', 'test');
  await h.pause(50);
  await h.byXPath("//input[@name='ctl0$body$ctl9$c0' and @value='Agree']").click();
  await h.assertNotVisible('ctl0_body_ctl8');
});
