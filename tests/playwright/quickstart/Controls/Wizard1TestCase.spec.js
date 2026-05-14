import { test, expect } from '@playwright/test';
import { demosHelper } from '../../helpers.js';

test('QuickstartWizard1TestCase', async ({ page }) => {
  const h = demosHelper(page);

  await h.url('quickstart/index.php?page=Controls.Samples.TWizard.Sample1&notheme=true&lang=en');

  await h.assertTitle('PRADO QuickStart Sample');

  // step 1
  await h.assertSourceContains('Wizard Step 1');
  await h.assertSourceNotContains('Wizard Step 2');
  await h.assertVisible('ctl0_body_Wizard1_SideBarList_ctl0_SideBarButton');
  await h.assertAttribute('ctl0_body_Wizard1_SideBarList_ctl1_SideBarButton@disabled', 'regexp:true|disabled');
  await h.select('ctl0$body$Wizard1$DropDownList1', 'Purple');
  await h.byName('ctl0$body$Wizard1$ctl6$ctl1').click();

  // step 2
  await h.assertSourceContains('Your favorite color is: Purple');
  await h.assertSourceNotContains('Wizard Step 1');
  await h.assertSourceContains('Wizard Step 2');
});
