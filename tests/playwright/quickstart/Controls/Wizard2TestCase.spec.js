import { test, expect } from '@playwright/test';
import { demosHelper } from '../../helpers.js';

test('QuickstartWizard2TestCase', async ({ page }) => {
  const h = demosHelper(page);

  await h.url('quickstart/index.php?page=Controls.Samples.TWizard.Sample2&notheme=true&lang=en');

  await h.assertTitle('PRADO QuickStart Sample');

  // step 1
  await h.assertSourceContains('Please let us know your preference');
  await h.assertSourceNotContains('Thank you for your answer');
  await h.assertVisible('ctl0_body_Wizard1_SideBarList_ctl0_SideBarButton');
  await h.assertAttribute('ctl0_body_Wizard1_SideBarList_ctl1_SideBarButton@disabled', 'regexp:true|disabled');
  await h.select('ctl0$body$Wizard1$DropDownList1', 'Blue');
  await h.byName('ctl0$body$Wizard1$ctl6$ctl1').click();

  // step 2
  await h.assertSourceContains('Your favorite color is: Blue');
  await h.assertSourceNotContains('Please let us know your preference');
  await h.assertSourceContains('Thank you for your answer');
});
