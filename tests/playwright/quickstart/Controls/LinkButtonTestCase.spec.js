import { test, expect } from '@playwright/test';
import { demosHelper } from '../../helpers.js';

test('QuickstartLinkButtonTestCase', async ({ page }) => {
  const h = demosHelper(page);

  await h.url('quickstart/index.php?page=Controls.Samples.TLinkButton.Home&notheme=true&lang=en');

  await h.assertTitle('PRADO QuickStart Sample');

  // regular buttons
  await h.byLinkText('link button').click();
  await h.pause(50);
  await h.byXPath("//a[contains(text(),'body content')]").first().click();
  await h.pause(50);

  // a click button
  await h.byLinkText('click me').first().click();
  await h.pause(50);
  await h.byLinkText("I'm clicked").click();
  await h.pause(50);

  // a command button
  await h.byLinkText('click me').first().click();
  await h.pause(50);
  await h.byXPath("//a[contains(text(),'Name: test, Param: value')]").first().click();

  // a button causing validation
  await h.assertNotVisible('ctl0_body_ctl4');
  await h.byLinkText('submit').click();
  await h.assertVisible('ctl0_body_ctl4');
  await h.type('ctl0$body$TextBox', 'test');
  await h.byLinkText('submit').click();
  await h.assertNotVisible('ctl0_body_ctl4');
});
