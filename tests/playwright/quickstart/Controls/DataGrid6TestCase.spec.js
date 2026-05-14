import { test, expect } from '@playwright/test';
import { demosHelper } from '../../helpers.js';

test('QuickstartDataGrid6TestCase', async ({ page }) => {
  const h = demosHelper(page);

  await h.url('quickstart/index.php?page=Controls.Samples.TDataGrid.Sample6&notheme=true&lang=en');

  // verify column headers
  await h.assertSourceContains('id');
  await h.assertSourceContains('name');
  await h.assertSourceContains('quantity');
  await h.assertSourceContains('price');
  await h.assertSourceContains('imported');

  await h.assertSourceContains('ITN001');
  await h.assertSourceContains('ITN002');
  await h.assertSourceContains('ITN003');
  await h.assertSourceContains('ITN004');
  await h.assertSourceContains('ITN005');
  await h.assertSourceNotContains('ITN006');

  // verify paging
  await h.byLinkText('2').click();
  await h.assertSourceContains('ITN006');
  await h.assertSourceContains('ITN007');
  await h.assertSourceContains('ITN008');
  await h.assertSourceContains('ITN009');
  await h.assertSourceContains('ITN010');
  await h.assertSourceNotContains('ITN011');
  await h.assertSourceNotContains('ITN005');

  await h.byLinkText('4').click();
  await h.assertSourceContains('ITN016');
  await h.assertSourceContains('ITN017');
  await h.assertSourceContains('ITN018');
  await h.assertSourceContains('ITN019');
  await h.assertSourceNotContains('ITN015');

  await h.byLinkText('1').click();
  await h.assertSourceContains('ITN001');
  await h.assertSourceContains('ITN002');
  await h.assertSourceContains('ITN003');
  await h.assertSourceContains('ITN004');
  await h.assertSourceContains('ITN005');
  await h.assertSourceNotContains('ITN006');
});
