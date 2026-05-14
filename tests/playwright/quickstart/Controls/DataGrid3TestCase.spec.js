import { test, expect } from '@playwright/test';
import { demosHelper } from '../../helpers.js';

test('QuickstartDataGrid3TestCase', async ({ page }) => {
  const h = demosHelper(page);

  await h.url('quickstart/index.php?page=Controls.Samples.TDataGrid.Sample3&notheme=true&lang=en');

  // verify the 2nd row of data
  await h.assertSourceContains('Design Patterns: Elements of Reusable Object-Oriented Software');
  await h.assertSourceContains('Addison-Wesley Professional');
  await h.assertSourceContains('$47.04');
  await h.assertAttribute('ctl0_body_DataGrid_ctl2_ctl4@checked', 'regexp:true|checked');
  await h.assertAttribute('ctl0_body_DataGrid_ctl2_ctl4@disabled', 'regexp:true|disabled');

  // edit the 2nd row
  await h.byId('ctl0_body_DataGrid_ctl2_ctl7').click();
  await h.pause(50);
  await h.type('ctl0$body$DataGrid$ctl2$ctl1', 'Design Pattern: Elements of Reusable Object-Oriented Software');
  await h.type('ctl0$body$DataGrid$ctl2$ctl3', 'Addison Wesley Professional');
  await h.type('ctl0$body$DataGrid$ctl2$ctl5', '$57.04');
  await h.byXPath("//input[@name='ctl0$body$DataGrid$ctl2$ctl7']").click();
  await h.pause(50);
  await h.select('ctl0$body$DataGrid$ctl2$ctl9', '1');
  await h.byLinkText('Save').click();
  await h.pause(50);

  // verify the 2nd row is saved
  await h.assertSourceContains('Design Pattern: Elements of Reusable Object-Oriented Software');
  await h.assertSourceContains('Addison Wesley Professional');
  await h.assertSourceContains('$57.04');
  await h.assertAttribute('ctl0_body_DataGrid_ctl2_ctl4@checked', null);
  await h.assertAttribute('ctl0_body_DataGrid_ctl2_ctl4@disabled', 'regexp:true|disabled');

  // verify cancel editing the 3rd row
  await h.byId('ctl0_body_DataGrid_ctl3_ctl7').click();
  await h.pause(50);
  await h.byLinkText('Cancel').click();
  await h.assertSourceContains('Design Patterns Explained : A New Perspective on Object-Oriented Design');

  // verify deleting
  await h.byId('ctl0_body_DataGrid_ctl3_ctl9').click();
  await h.pause(50);

  expect(h.alertText()).toBe('Are you sure?');
  h.acceptAlert();

  await h.pause(500);
  await h.assertSourceNotContains('Design Patterns Explained : A New Perspective on Object-Oriented Design');

  await h.assertSourceContains('Extreme Programming Explained : Embrace Change');
  h.dismissNextAlert();
  await h.byId('ctl0_body_DataGrid_ctl6_ctl9').click();

  await h.pause(50);
  expect(h.alertText()).toBe('Are you sure?');
  h.dismissAlert();

  await h.assertSourceContains('Extreme Programming Explained : Embrace Change');
});
