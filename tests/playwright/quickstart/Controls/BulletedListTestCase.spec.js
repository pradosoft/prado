import { test, expect } from '@playwright/test';
import { demosHelper } from '../../helpers.js';

test('QuickstartBulletedListTestCase', async ({ page }) => {
  const h = demosHelper(page);

  await h.url('quickstart/index.php?page=Controls.Samples.TBulletedList.Home&notheme=true&lang=en');

  // verify if all required texts are present
  await h.assertSourceContains('item 1');
  await h.assertSourceContains('item 2');
  await h.assertSourceContains('item 3');
  await h.assertSourceContains('item 4');
  await h.assertSourceContains('google');
  await h.assertSourceContains('yahoo');
  await h.assertSourceContains('amazon');

  // verify order list starting from 5
  await h.assertElementPresent("//ol[@start='5']");

  // verify hyperlink list
  await h.assertElementPresent("//a[@href='http://www.google.com/']");
  await h.assertElementPresent("//a[@href='http://www.yahoo.com/']");
  await h.assertElementPresent("//a[@href='http://www.amazon.com/']");

  // verify linkbutton list
  await h.byId('ctl0_body_ctl40').click();
  await h.pause(50);
  await h.assertSourceContains('You clicked google : http://www.google.com/.');
  await h.byId('ctl0_body_ctl41').click();
  await h.pause(50);
  await h.assertSourceContains('You clicked yahoo : http://www.yahoo.com/.');
  await h.byId('ctl0_body_ctl42').click();
  await h.pause(50);
  await h.assertSourceContains('You clicked amazon : http://www.amazon.com/.');
});
