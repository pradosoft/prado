import { test, expect } from '@playwright/test';
import { demosHelper } from '../../helpers.js';

test('QuickstartWizard4TestCase', async ({ page }) => {
  const h = demosHelper(page);

  await h.url('quickstart/index.php?page=Controls.Samples.TWizard.Sample4&notheme=true&lang=en');

  await h.assertTitle('PRADO QuickStart Sample');
  await h.pause(100);

  // step 1
  await h.assertSourceContains('Step 1 of 3');
  await h.select('ctl0_body_Wizard1_DropDownList1', 'Cyan');
  await h.byId('ctl0_body_Wizard1_SideBarList_ctl2_SideBarButton').click();

  // step 3
  await h.assertSourceContains('Step 3 of 3');
  await h.assertSourceContains('Thank you for completing this survey.');
  await h.byId('ctl0_body_Wizard1_SideBarList_ctl0_SideBarButton').click();

  // step 1
  await h.pause(50);
  await h.assertSelected('ctl0_body_Wizard1_DropDownList1', 'Cyan');
  await h.select('ctl0_body_Wizard1_DropDownList1', 'Black');
  await h.byId('ctl0_body_Wizard1_ctl4_ctl0').click();

  // step 2
  await h.assertSourceContains('Step 2 of 3');
  await h.assertSourceContains('Your favorite color is: Black');
  await h.byId('ctl0_body_Wizard1_ctl5_ctl0').click();

  // step 1
  await h.assertSourceContains('Step 1 of 3');
  await h.assertSelected('ctl0_body_Wizard1_DropDownList1', 'Black');
  await h.byId('ctl0_body_Wizard1_ctl4_ctl0').click();

  // step 2
  await h.pause(50);
  await h.byId('ctl0_body_Wizard1_ctl5_ctl1').click();

  // step 3
  await h.assertSourceContains('Step 3 of 3');
});
