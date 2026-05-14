import { test, expect } from '@playwright/test';
import { demosHelper } from '../../helpers.js';

test('QuickstartPanelTestCase', async ({ page }) => {
  const h = demosHelper(page);

  await h.url('quickstart/index.php?page=Controls.Samples.TPanel.Home&notheme=true&lang=en');

  await h.assertSourceContains("This is panel content with");
  await h.assertElementPresent("//span[text()='label']");
  await h.assertSourceContains('grouping text');
  await h.byXPath("//input[@name='ctl0$body$ctl17']").click();
  await h.assertSourceNotContains("You have clicked on 'button2'.");
  await h.byXPath("//input[@type='submit' and @value='button2']").click();
  await h.assertSourceContains("You have clicked on 'button2'.");
});
