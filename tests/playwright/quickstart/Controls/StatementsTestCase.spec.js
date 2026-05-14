import { test, expect } from '@playwright/test';
import { demosHelper } from '../../helpers.js';

test('QuickstartStatementsTestCase', async ({ page }) => {
  const h = demosHelper(page);

  await h.url('quickstart/index.php?page=Controls.Samples.TStatements.Home&notheme=true&lang=en');

  await h.assertTitle('PRADO QuickStart Sample');

  await h.assertSourceContains("UniqueID is 'ctl0$body$ctl0'");
});
