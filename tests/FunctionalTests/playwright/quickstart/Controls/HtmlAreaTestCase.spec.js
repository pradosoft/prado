import { test, expect } from '@playwright/test';
import { demosHelper } from '../../helpers.js';

test('QuickstartHtmlAreaTestCase', async ({ page }) => {
  const h = demosHelper(page);

  await h.url('quickstart/index.php?page=Controls.Samples.THtmlArea.Home&notheme=true&lang=en');

  await h.assertTitle('PRADO QuickStart Sample');

  // can't perform any test
});
