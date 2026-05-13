import { test, expect } from '@playwright/test';
import { demosHelper } from '../../helpers.js';

test('QuickstartWizard5TestCase', async ({ page }) => {
  const h = demosHelper(page);

  await h.url('quickstart/index.php?page=Controls.Samples.TWizard.Sample5&notheme=true&lang=en');

  await h.assertTitle('PRADO QuickStart Sample');

  // step 1
  await h.assertSourceContains('Please let us know your preference');
  await h.assertVisible('ctl0_body_Wizard1_SideBarList_ctl0_SideBarButton');
  await h.assertVisible('ctl0_body_Wizard1_SideBarList_ctl1_SideBarButton');
  await h.assertAttribute('ctl0_body_Wizard1_SideBarList_ctl2_SideBarButton@disabled', 'regexp:true|disabled');
  await h.select('ctl0_body_Wizard1_DropDownList1', 'Cyan');
  await h.byName('ctl0$body$Wizard1$ctl4$ctl0').click();
  await h.pause(50);

  // step 2
  await h.select('ctl0_body_Wizard1_Step2_DropDownList2', 'Football');
  await h.pause(50);
  await h.byName('ctl0$body$Wizard1$ctl6$ctl0').click();
  await h.pause(50);

  // step 1
  await h.assertSelected('ctl0_body_Wizard1_DropDownList1', 'Cyan');
  await h.byId('ctl0_body_Wizard1_SideBarList_ctl1_SideBarButton').click();
  await h.pause(50);

  // step 2
  await h.assertSelected('ctl0_body_Wizard1_Step2_DropDownList2', 'Football');
  await h.byName('ctl0$body$Wizard1$ctl6$ctl1').click();
  await h.pause(50);

  // step 3
  await h.assertSourceContains('Your favorite color is: Cyan');
  await h.assertSourceContains('Your favorite sport is: Football');
});
