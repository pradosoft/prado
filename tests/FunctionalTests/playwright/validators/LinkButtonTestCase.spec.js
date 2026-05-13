import { test, expect } from '@playwright/test';
import { genericHelper } from '../helpers.js';

test('LinkButtonTestCase', async ({ page }) => {
  const h = genericHelper(page);
  await h.url('validators/index.php?page=LinkButton');

  // verify all error messages are invisible
  await h.assertNotVisible('ctl0_Content_ctl0');
  await h.assertNotVisible('ctl0_Content_ctl2');
  await h.assertNotVisible('ctl0_Content_ctl4');

  // verify the first validator shows the error
  await h.byId('ctl0_Content_ctl1').click();
  await h.assertVisible('ctl0_Content_ctl0');
  await h.assertNotVisible('ctl0_Content_ctl2');
  await h.assertNotVisible('ctl0_Content_ctl4');

  // verify the first validation is passed
  await h.pause(500);
  await h.assertSourceNotContains('Button1 is clicked');
  await h.type('ctl0_Content_TextBox1', 'test');
  await h.byId('ctl0_Content_ctl1').click();
  await h.assertNotVisible('ctl0_Content_ctl0');
  await h.assertNotVisible('ctl0_Content_ctl2');
  await h.assertNotVisible('ctl0_Content_ctl4');
  await h.assertSourceContains('Button1 is clicked and valid');

  // verify the second validator shows the error
  await h.byId('ctl0_Content_ctl3').click();
  await h.assertNotVisible('ctl0_Content_ctl0');
  await h.assertVisible('ctl0_Content_ctl2');
  await h.assertNotVisible('ctl0_Content_ctl4');

  // verify the second validation is passed
  await h.pause(500);
  await h.assertSourceNotContains('Button2 is clicked');
  await h.type('ctl0_Content_TextBox2', 'test');
  await h.byId('ctl0_Content_ctl3').click();
  await h.assertNotVisible('ctl0_Content_ctl0');
  await h.assertNotVisible('ctl0_Content_ctl2');
  await h.assertNotVisible('ctl0_Content_ctl4');
  await h.assertSourceContains('Button2 is clicked and valid');

  // verify the third validator shows the error
  await h.byId('ctl0_Content_ctl5').click();
  await h.assertNotVisible('ctl0_Content_ctl0');
  await h.assertNotVisible('ctl0_Content_ctl2');
  await h.assertVisible('ctl0_Content_ctl4');

  // verify the third validation is passed
  await h.assertSourceContains('Button3 is clicked');
  await h.assertSourceNotContains('Button3 is clicked and valid');
  await h.type('ctl0_Content_TextBox3', 'test');
  await h.byId('ctl0_Content_ctl5').click();
  await h.assertNotVisible('ctl0_Content_ctl0');
  await h.assertNotVisible('ctl0_Content_ctl2');
  await h.assertNotVisible('ctl0_Content_ctl4');
  await h.assertSourceContains('Button3 is clicked and valid');
});
