import { test, expect } from '@playwright/test';
import { demosHelper } from '../../helpers.js';

test('JuiDialogTestCase', async ({ page }) => {
  const h = demosHelper(page);

  await h.url('quickstart/index.php?page=JuiControls.Samples.TJuiDialog.Home&notheme=true&lang=en');

  await h.assertTitle('PRADO QuickStart Sample');

  await h.assertSourceContains('TJuiDialog Samples');

  const base = 'ctl0_body_';

  await h.byId(`${base}ctl0`).click();
  await h.assertVisible(`${base}dlg1`);

  await h.active().click(); // close

  await h.assertText(`${base}lbl3`, '');
  await h.byId(`${base}ctl2`).click();
  await h.assertVisible(`${base}dlg3`);

  // Click OK (by keys...)
  await h.keys('Enter');
  await h.assertText(`${base}lbl3`, 'Button Ok clicked');
});
