import { test, expect } from '@playwright/test';
import { demosHelper } from '../../helpers.js';

test('QuickstartRepeater1TestCase', async ({ page }) => {
  const h = demosHelper(page);

  await h.url('quickstart/index.php?page=Controls.Samples.TRepeater.Sample1&notheme=true&lang=en');

  // verify if all required texts are present
  await h.assertSourceContains('ID');
  await h.assertSourceContains('Name');
  await h.assertSourceContains('Quantity');
  await h.assertSourceContains('Price');
  await h.assertSourceContains('Imported');
  await h.assertSourceContains('ITN001');
  await h.assertSourceContains('Motherboard');
  await h.assertSourceContains('Yes');
  await h.assertSourceContains('ITN019');
  await h.assertSourceContains('Speaker');
  await h.assertSourceContains('No');
  await h.assertSourceContains('Computer Parts Inventory');

  // verify specific table tags
  await h.assertElementPresent("//td[@colspan='5']");
  await h.assertElementPresent("//table[@cellpadding='2']");
});
