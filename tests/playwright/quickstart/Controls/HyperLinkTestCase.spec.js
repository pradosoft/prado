import { test, expect } from '@playwright/test';
import { demosHelper } from '../../helpers.js';

test('QuickstartHyperLinkTestCase', async ({ page }) => {
  const h = demosHelper(page);

  await h.url('quickstart/index.php?page=Controls.Samples.THyperLink.Home&notheme=true&lang=en');

  await h.assertTitle('PRADO QuickStart Sample');
  await h.assertElementPresent("//a[@href='https://github.com/pradosoft/prado' and @target='_blank']");
  await h.assertSourceContains('Welcome to');
  await h.assertSourceContains('Body contents');
  await h.assertElementPresent("//a[img/@alt='Hello World']");
  await h.assertElementPresent("//a[contains(text(),'Body contents')]");
});
