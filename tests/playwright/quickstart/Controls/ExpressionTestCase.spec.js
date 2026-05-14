import { test, expect } from '@playwright/test';
import { demosHelper } from '../../helpers.js';

test('QuickstartExpressionTestCase', async ({ page }) => {
  const h = demosHelper(page);

  await h.url('quickstart/index.php?page=Controls.Samples.TExpression.Home&notheme=true&lang=en');

  await h.assertTitle('PRADO QuickStart Sample');

  await h.assertSourceContains('PRADO QuickStart Sample');
});
