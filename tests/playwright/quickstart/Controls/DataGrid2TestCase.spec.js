import { test, expect } from '@playwright/test';
import { demosHelper } from '../../helpers.js';

test('QuickstartDataGrid2TestCase', async ({ page }) => {
  const h = demosHelper(page);

  await h.url('quickstart/index.php?page=Controls.Samples.TDataGrid.Sample2&notheme=true&lang=en');

  // verify if all required texts are present
  await h.assertSourceContains('Book Title');
  await h.assertSourceContains('Publisher');
  await h.assertSourceContains('Price');
  await h.assertSourceContains('In-stock');
  await h.assertSourceContains('Rating');

  // verify book titles
  await h.assertElementPresent("//a[@href='http://www.amazon.com/gp/product/0596007124' and text()='Head First Design Patterns']");
  await h.assertElementPresent("//a[@href='http://www.amazon.com/gp/product/0321278658' and text()='Extreme Programming Explained : Embrace Change']");

  // verify publishers
  await h.assertSourceContains("O'Reilly Media, Inc.");
  await h.assertSourceContains('Addison-Wesley Professional');

  // verify prices
  await h.assertSourceContains('$37.49');
  await h.assertSourceContains('$38.49');

  // verify in-stock (checked attribute)
  await h.assertAttribute('ctl0_body_DataGrid_ctl1_ctl5@checked', 'regexp:true|checked');
  await h.assertAttribute('ctl0_body_DataGrid_ctl1_ctl5@disabled', 'regexp:true|disabled');
  await h.assertAttribute('ctl0_body_DataGrid_ctl2_ctl5@checked', 'regexp:true|checked');
  await h.assertAttribute('ctl0_body_DataGrid_ctl6_ctl5@checked', null);
  await h.assertAttribute('ctl0_body_DataGrid_ctl6_ctl5@disabled', 'regexp:true|disabled');

  // verify toggle column visibility
  await h.byXPath("//input[@name='ctl0$body$ctl1$c0' and @value='Book Title']").click();
  await h.assertSourceNotContains('Head First Design Patterns');
  await h.byXPath("//input[@name='ctl0$body$ctl1$c3' and @value='In-stock']").click();
  await h.pause(50);
  await h.assertElementNotPresent('ctl0_body_DataGrid_ctl1_ctl5');
  await h.byXPath("//input[@name='ctl0$body$ctl1$c3' and @value='In-stock']").click();
  await h.pause(50);
  await h.assertElementPresent('ctl0_body_DataGrid_ctl1_ctl5');
  await h.byXPath("//input[@name='ctl0$body$ctl1$c0' and @value='Book Title']").click();
  await h.assertSourceContains('Head First Design Patterns');
});
