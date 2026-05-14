import { test, expect } from '@playwright/test';
import { demosHelper } from '../../helpers.js';

test('QuickstartImageTestCase', async ({ page }) => {
  const h = demosHelper(page);

  await h.url('quickstart/index.php?page=Controls.Samples.TImage.Home&notheme=true&lang=en');

  await h.assertTitle('PRADO QuickStart Sample');

  await h.assertElementPresent("//img[contains(@src,'/hello_world.gif') and @alt='Hello World!']");
  await h.assertSourceContains('Hello World! Hello World! Hello World!');
});
