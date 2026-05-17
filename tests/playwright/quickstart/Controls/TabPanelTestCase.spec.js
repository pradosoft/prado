import { test, expect } from '@playwright/test';
import { demosHelper } from '../../helpers.js';

test('QuickstartTabPanelTestCase', async ({ page }) => {
  const h = demosHelper(page);

  await h.url('quickstart/index.php?page=Controls.Samples.TTabPanel.Home&notheme=true&lang=en');

  await h.assertTitle('PRADO QuickStart Sample');

  // verify initial visibility
  await h.assertNotVisible('ctl0_body_View1');
  await h.assertVisible('ctl0_body_View2');
  await h.assertNotVisible('ctl0_body_ctl2');

  // switching to the first view
  await h.byId('ctl0_body_View1_0').click();
  await h.pause(500);
  await h.assertVisible('ctl0_body_View1');
  await h.assertNotVisible('ctl0_body_View2');
  await h.assertNotVisible('ctl0_body_ctl2');
  await h.assertNotVisible('ctl0_body_View11');
  await h.assertVisible('ctl0_body_View21');

  // switching to View11
  await h.byId('ctl0_body_View11_0').click();
  await h.pause(500);
  await h.assertVisible('ctl0_body_View1');
  await h.assertNotVisible('ctl0_body_View2');
  await h.assertNotVisible('ctl0_body_ctl2');
  await h.assertVisible('ctl0_body_View11');
  await h.assertNotVisible('ctl0_body_View21');

  // switching to the third view
  await h.byId('ctl0_body_ctl2_0').click();
  await h.pause(500);
  await h.assertNotVisible('ctl0_body_View1');
  await h.assertNotVisible('ctl0_body_View2');
  await h.assertVisible('ctl0_body_ctl2');

  // submit: check if the visibility is kept
  await h.byXPath("//input[@type='submit' and @value='Submit']").click();
  await h.assertNotVisible('ctl0_body_View1');
  await h.assertNotVisible('ctl0_body_View2');
  await h.assertVisible('ctl0_body_ctl2');
});
