import { test, expect } from '@playwright/test';
import { demosHelper } from '../../helpers.js';

test('QuickstartDataList1TestCase', async ({ page }) => {
  const h = demosHelper(page);

  await h.url('quickstart/index.php?page=Controls.Samples.TDataList.Sample1&notheme=true&lang=en');

  // verify if all required texts are present
  await h.assertSourceContains('ITN001');
  await h.assertSourceContains('$100');
  await h.assertSourceContains('Motherboard');
  await h.assertSourceContains('ITN018');
  await h.assertSourceContains('Surge protector');
  await h.assertSourceContains('45');
  await h.assertSourceContains('$15');
  await h.assertSourceContains('Total 19 products.');
  await h.assertSourceContains('Computer Parts');

  // verify specific table tags
  await h.assertElementPresent('ctl0_body_DataList');
  await h.assertElementPresent("//td[@align='right']");
});
