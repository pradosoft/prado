import { test, expect } from '@playwright/test';
import { demosHelper } from '../../helpers.js';

test('QuickstartDataGrid1TestCase', async ({ page }) => {
  const h = demosHelper(page);

  await h.url('quickstart/index.php?page=Controls.Samples.TDataGrid.Sample1&notheme=true&lang=en');

  // verify if all required texts are present
  await h.assertSourceContains('id');
  await h.assertSourceContains('name');
  await h.assertSourceContains('quantity');
  await h.assertSourceContains('price');
  await h.assertSourceContains('imported');
  await h.assertSourceContains('ITN001');
  await h.assertSourceContains('Motherboard');
  await h.assertSourceContains('100');
  await h.assertSourceContains('true');
  await h.assertSourceContains('ITN019');
  await h.assertSourceContains('Speaker');
  await h.assertSourceContains('35');
  await h.assertSourceContains('65');
  await h.assertSourceContains('false');

  // verify specific table tags
  await h.assertElementPresent('ctl0_body_DataGrid');
  await h.assertAttribute('ctl0_body_DataGrid@cellpadding', '2');
});
