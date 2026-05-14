import { test, expect } from '@playwright/test';
import { demosHelper } from '../../helpers.js';

test('QuickstartLabelTestCase', async ({ page }) => {
  const h = demosHelper(page);

  await h.url('quickstart/index.php?page=Controls.Samples.TLabel.Home&notheme=true&lang=en');

  await h.assertTitle('PRADO QuickStart Sample');
  await h.assertSourceContains('This is a label with customized color and font.');
  await h.assertSourceContains('This is a form label associated with the TTextBox control below');
  await h.assertSourceContains('This is a label with empty Text property and <b>nonempty body</b>');
  await h.assertSourceContains('This is a disabled label');

  await h.assertAttribute('ctl0_body_Label2@disabled', 'regexp:true|disabled');

  await h.type('ctl0$body$test', 'test');
});
