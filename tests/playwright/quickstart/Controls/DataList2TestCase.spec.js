import { test, expect } from '@playwright/test';
import { demosHelper } from '../../helpers.js';

test('QuickstartDataList2TestCase', async ({ page }) => {
  const h = demosHelper(page);

  await h.url('quickstart/index.php?page=Controls.Samples.TDataList.Sample2&notheme=true&lang=en');

  // verify initial presentation
  await h.assertSourceContains('Motherboard');
  await h.assertSourceContains('Monitor');

  // verify selecting an item
  await h.byLinkText('ITN003').click();
  await h.assertSourceContains('Quantity');
  await h.assertSourceContains('Price');
  await h.assertSourceContains('$80');
  await h.byLinkText('ITN005').click();
  await h.assertSourceContains('$150');

  // verify editing an item
  await h.byId('ctl0_body_DataList_ctl5_ctl0').click();
  await h.pause(50);
  await h.type('ctl0$body$DataList$ctl5$ProductQuantity', '11');
  await h.type('ctl0$body$DataList$ctl5$ProductPrice', '140.99');
  await h.byXPath("//input[@name='ctl0$body$DataList$ctl5$ProductImported']").click();
  await h.byLinkText('Save').click();
  await h.pause(50);

  // verify item is saved
  await h.byLinkText('ITN005').click();
  await h.assertSourceContains('$140.99');
  await h.assertSourceContains('11');

  // verify editing another item
  await h.byId('ctl0_body_DataList_ctl3_ctl1').click();
  await h.pause(50);
  await h.type('ctl0$body$DataList$ctl3$ProductName', 'Hard Drive');
  await h.type('ctl0$body$DataList$ctl3$ProductQuantity', '23');
  await h.byXPath("//input[@name='ctl0$body$DataList$ctl3$ProductImported']").click();
  await h.byLinkText('Cancel').click();
  await h.pause(50);

  // verify item is canceled
  await h.byLinkText('ITN003').click();
  await h.assertSourceContains('2');
  await h.assertSourceContains('Harddrive');

  // verify item deletion
  await h.byId('ctl0_body_DataList_ctl3_ctl1').click();
  await h.pause(50);

  expect(h.alertText()).toBe('Are you sure?');
  h.acceptAlert();

  await h.pause(300);
  h.dismissNextAlert();
  await h.byId('ctl0_body_DataList_ctl5_ctl2').click();
  await h.pause(50);

  expect(h.alertText()).toBe('Are you sure?');
  h.dismissAlert();

  await h.assertSourceContains('Motherboard');
  await h.assertSourceContains('CPU');
  await h.assertSourceNotContains('Harddrive');
  await h.assertSourceContains('Sound card');
  await h.assertSourceContains('Video card');
  await h.assertSourceContains('Keyboard');
  await h.assertSourceContains('Monitor');
});
