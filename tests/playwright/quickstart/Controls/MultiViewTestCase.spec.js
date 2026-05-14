import { test, expect } from '@playwright/test';
import { demosHelper } from '../../helpers.js';

test('QuickstartMultiViewTestCase', async ({ page }) => {
  const h = demosHelper(page);

  await h.url('quickstart/index.php?page=Controls.Samples.TMultiView.Home&notheme=true&lang=en');

  await h.assertTitle('PRADO QuickStart Sample');

  // view 1 : type in a string
  await h.assertElementNotPresent('ctl0_body_Result1');
  await h.assertElementNotPresent('ctl0_body_Result2');
  await h.type('ctl0_body_Memo', 'test');
  await h.byName('ctl0$body$ctl0').click(); // view 2 to select the dropdown
  await h.pause(50);
  await h.byName('ctl0$body$ctl4').click();

  // view 3 : check if the output is updated
  await h.assertSourceContains('Your text input is: test');
  await h.assertSourceContains('Your color choice is: Red');
  await h.byName('ctl0$body$ctl7').click();
  await h.pause(50);

  // view 2 : update dropdownlist
  await h.assertElementNotPresent('ctl0_body_Result1');
  await h.assertElementNotPresent('ctl0_body_Result2');
  await h.select('ctl0$body$DropDownList', 'Blue');
  await h.byName('ctl0$body$ctl4').click();

  // view 3 : check if the output is updated
  await h.assertSourceContains('Your text input is: test');
  await h.assertSourceContains('Your color choice is: Blue');
  await h.byName('ctl0$body$ctl7').click();

  // view 2 : check if dropdownlist maintains state
  await h.assertSelected('ctl0$body$DropDownList', 'Blue');
});
