import { test, expect } from '@playwright/test';
import { demosHelper } from '../../helpers.js';

test('QuickstartTableTestCase', async ({ page }) => {
  const h = demosHelper(page);

  await h.url('quickstart/index.php?page=Controls.Samples.TTable.Home&notheme=true&lang=en');

  await h.assertElementPresent("//table[@rules='all' and @border='1']");
  await h.assertElementPresent("//table/caption[contains(@style,'caption-side:bottom') and text()='This is table caption']");
  await h.assertElementPresent("//th[text()='header cell 2']");
  await h.assertElementPresent("//tr[contains(@style,'text-align:right')]/td[text()='text']");
  await h.assertElementPresent("//td[contains(@style,'text-align:center') and contains(text(),'cell 5')]");
  await h.assertElementPresent("//th[text()='Header 1']");
  await h.assertElementPresent("//td[text()='Cell 1']");
});
