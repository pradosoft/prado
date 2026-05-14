import { test, expect } from '@playwright/test';
import { demosHelper } from '../../helpers.js';

test('QuickstartRepeater2TestCase', async ({ page }) => {
  const h = demosHelper(page);

  await h.url('quickstart/index.php?page=Controls.Samples.TRepeater.Sample2&notheme=true&lang=en');

  // verify if all required texts are present
  await h.assertSourceContains('North');
  await h.assertSourceContains('John');
  await h.assertSourceContains('Developer');
  await h.assertSourceContains('South');
  await h.assertSourceContains('Carter');
  await h.assertSourceContains('Program Manager');

  // verify specific table tags
  await h.assertElementPresent("//table[@cellspacing='1']");
  await h.assertElementPresent("//td[@id='ctl0_body_Repeater_ctl1_Cell' and contains(text(),'North')]");
  await h.assertElementPresent("//td[@id='ctl0_body_Repeater_ctl1_Cell']");
  await h.assertElementPresent("//td[@id='ctl0_body_Repeater_ctl2_Cell']");
  await h.assertElementPresent("//td[@id='ctl0_body_Repeater_ctl3_Cell']");
  await h.assertElementPresent("//td[@id='ctl0_body_Repeater_ctl4_Cell']");
  await h.assertElementPresent("//tr[@id='ctl0_body_Repeater_ctl1_Repeater2_ctl1_Row']");
  await h.assertElementPresent("//tr[@id='ctl0_body_Repeater_ctl1_Repeater2_ctl2_Row']");
  await h.assertElementPresent("//tr[@id='ctl0_body_Repeater_ctl1_Repeater2_ctl3_Row']");
  await h.assertElementPresent("//tr[@id='ctl0_body_Repeater_ctl2_Repeater2_ctl1_Row']");
  await h.assertElementPresent("//tr[@id='ctl0_body_Repeater_ctl2_Repeater2_ctl2_Row']");
  await h.assertElementPresent("//tr[@id='ctl0_body_Repeater_ctl2_Repeater2_ctl3_Row']");
  await h.assertElementPresent("//tr[@id='ctl0_body_Repeater_ctl3_Repeater2_ctl1_Row']");
  await h.assertElementPresent("//tr[@id='ctl0_body_Repeater_ctl3_Repeater2_ctl2_Row']");
  await h.assertElementPresent("//tr[@id='ctl0_body_Repeater_ctl4_Repeater2_ctl1_Row']");
  await h.assertElementPresent("//tr[@id='ctl0_body_Repeater_ctl4_Repeater2_ctl2_Row']");
});
