import { test, expect } from '@playwright/test';
import { demosHelper } from '../../helpers.js';

test('QuickstartRepeater3TestCase', async ({ page }) => {
  const h = demosHelper(page);

  await h.url('quickstart/index.php?page=Controls.Samples.TRepeater.Sample3&notheme=true&lang=en');

  // verify product name is required
  await h.assertNotVisible('ctl0_body_Repeater_ctl0_ctl0');
  await h.type('ctl0_body_Repeater_ctl0_ProductName', '');
  await h.byXPath("//input[@type='submit' and @value='Save']").click();
  await h.assertVisible('ctl0_body_Repeater_ctl0_ctl0');

  // verify product price is of proper format
  await h.assertNotVisible('ctl0_body_Repeater_ctl0_ctl1');
  await h.type('ctl0_body_Repeater_ctl0_ProductPrice', 'abc');
  await h.byXPath("//input[@type='submit' and @value='Save']").click();
  await h.assertVisible('ctl0_body_Repeater_ctl0_ctl1');

  // perform postback
  await h.byId('ctl0_body_Repeater_ctl0_ProductImported').click();
  await h.type('ctl0_body_Repeater_ctl0_ProductName', 'Mother Board');
  await h.type('ctl0_body_Repeater_ctl0_ProductPrice', '99.01');
  await h.select('ctl0_body_Repeater_ctl3_ProductCategory', 'Accessories');
  await h.byXPath("//input[@type='submit' and @value='Save']").click();
  await h.assertNotVisible('ctl0_body_Repeater_ctl0_ctl0');
  await h.assertNotVisible('ctl0_body_Repeater_ctl0_ctl1');

  // verify postback results
  await h.assertElementPresent("//td[text()='Mother Board']");
  await h.assertElementNotPresent("//td[text()='Input Devices']");
  await h.assertElementPresent("//td[text()='99.01']");
});
