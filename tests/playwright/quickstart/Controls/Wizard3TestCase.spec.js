import { test, expect } from '@playwright/test';
import { demosHelper } from '../../helpers.js';

test('QuickstartWizard3TestCase', async ({ page }) => {
  const h = demosHelper(page);

  await h.url('quickstart/index.php?page=Controls.Samples.TWizard.Sample3&notheme=true&lang=en');

  await h.assertTitle('PRADO QuickStart Sample');

  // step 1
  await h.assertSourceContains('A Mini Survey');
  await h.assertSourceContains('PRADO QuickStart Sample');
  await h.byId('ctl0_body_Wizard3_StudentCheckBox').click();
  await h.pause(50);
  await h.byName('ctl0$body$Wizard3$ctl4$ctl0').click();
  await h.pause(50);

  // step 2
  await h.select('ctl0$body$Wizard3$DropDownList11', 'Chemistry');
  await h.pause(50);
  await h.byName('ctl0$body$Wizard3$ctl5$ctl1').click();
  await h.pause(50);

  // step 3
  await h.select('ctl0$body$Wizard3$DropDownList22', 'Tennis');
  await h.pause(50);
  await h.byName('ctl0$body$Wizard3$ctl6$ctl1').click();

  // step 4
  await h.assertSourceContains('You are a college student');
  await h.assertSourceContains('You are in major: Chemistry');
  await h.assertSourceContains('Your favorite sport is: Tennis');

  // run the example again. this time we skip the page asking about major
  await h.url('quickstart/index.php?page=Controls.Samples.TWizard.Sample3&notheme=true');

  // step 1
  await h.byName('ctl0$body$Wizard3$ctl4$ctl0').click();
  await h.pause(50);

  // step 3
  await h.select('ctl0$body$Wizard3$DropDownList22', 'Baseball');
  await h.pause(50);
  await h.byName('ctl0$body$Wizard3$ctl6$ctl1').click();

  // step 4
  await h.assertSourceNotContains('You are a college student');
  await h.assertSourceContains('Your favorite sport is: Baseball');
});
