import { test, expect } from '@playwright/test';
import { demosHelper } from '../../helpers.js';

test('QuickstartDataGrid4TestCase', async ({ page }) => {
  const h = demosHelper(page);

  await h.url('quickstart/index.php?page=Controls.Samples.TDataGrid.Sample4&notheme=true&lang=en');

  // verify the 2nd row of data
  await h.assertSourceContains('Design Patterns: Elements of Reusable Object-Oriented Software');
  await h.assertSourceContains('Addison-Wesley Professional');
  await h.assertSourceContains('$47.04');
  await h.assertAttribute('ctl0_body_DataGrid_ctl2_ctl5@checked', 'regexp:true|checked');
  await h.assertAttribute('ctl0_body_DataGrid_ctl2_ctl5@disabled', 'regexp:true|disabled');

  // verify sorting
  await h.byLinkText('Book Title').click();
  await h.pause(50);
  await h.assertAttribute('ctl0_body_DataGrid_ctl1_ctl5@checked', null);
  await h.byLinkText('Publisher').click();
  await h.pause(50);
  await h.assertAttribute('ctl0_body_DataGrid_ctl6_ctl5@checked', null);
  await h.byLinkText('Price').click();
  await h.pause(50);
  await h.assertAttribute('ctl0_body_DataGrid_ctl2_ctl5@checked', null);
  await h.byLinkText('In-stock').click();
  await h.pause(50);
  await h.assertAttribute('ctl0_body_DataGrid_ctl1_ctl5@checked', null);
  await h.byLinkText('Rating').click();
  await h.pause(50);
  await h.assertAttribute('ctl0_body_DataGrid_ctl6_ctl5@checked', null);
});
