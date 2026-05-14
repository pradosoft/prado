import { test, expect } from '@playwright/test';
import { demosHelper } from '../../helpers.js';

test('QuickstartDataGrid5TestCase', async ({ page }) => {
  const h = demosHelper(page);

  await h.url('quickstart/index.php?page=Controls.Samples.TDataGrid.Sample5&notheme=true&lang=en');

  // verify column headers
  await h.assertSourceContains('id');
  await h.assertSourceContains('name');
  await h.assertSourceContains('quantity');
  await h.assertSourceContains('price');
  await h.assertSourceContains('imported');

  await h.assertSourceContains('ITN001');
  await h.assertSourceContains('ITN002');
  await h.assertSourceContains('ITN003');
  await h.assertSourceContains('ITN004');
  await h.assertSourceContains('ITN005');
  await h.assertSourceNotContains('ITN006');

  // verify paging
  await h.byLinkText('2').click();
  await h.assertSourceContains('ITN006');
  await h.assertSourceContains('ITN007');
  await h.assertSourceContains('ITN008');
  await h.assertSourceContains('ITN009');
  await h.assertSourceContains('ITN010');
  await h.assertSourceNotContains('ITN011');
  await h.assertSourceNotContains('ITN005');

  await h.byLinkText('4').click();
  await h.assertSourceContains('ITN016');
  await h.assertSourceContains('ITN017');
  await h.assertSourceContains('ITN018');
  await h.assertSourceContains('ITN019');
  await h.assertSourceNotContains('ITN015');

  await h.byLinkText('1').click();
  await h.assertSourceContains('ITN001');
  await h.assertSourceContains('ITN002');
  await h.assertSourceContains('ITN003');
  await h.assertSourceContains('ITN004');
  await h.assertSourceContains('ITN005');
  await h.assertSourceNotContains('ITN006');

  // show top pager
  await h.byXPath("//input[@name='ctl0$body$ctl1$c0' and @value='Top']").click();
  await h.pause(50);
  await h.byId('ctl0_body_DataGrid_ctl8_ctl3').click();
  await h.pause(50);
  await h.byLinkText('1').first().click();  // both top and bottom pagers have "1"
  await h.pause(50);
  // hide top pager
  await h.byXPath("//input[@name='ctl0$body$ctl1$c0' and @value='Top']").click();
  await h.pause(50);

  // change next prev caption
  await h.type('ctl0$body$NextPageText', 'Next Page');
  await h.pause(50);
  await h.type('ctl0$body$PrevPageText', 'Prev Page');
  await h.byXPath("//input[@type='submit' and @value='Submit']").first().click();

  // verify next prev paging
  await h.assertSourceContains('ITN001');
  await h.assertSourceNotContains('ITN006');
  await h.byLinkText('Next Page').click();
  await h.assertSourceNotContains('ITN005');
  await h.assertSourceContains('ITN006');
  await h.assertSourceNotContains('ITN011');
  await h.byLinkText('Next Page').click();
  await h.assertSourceNotContains('ITN010');
  await h.assertSourceContains('ITN011');
  await h.assertSourceNotContains('ITN016');
  await h.byLinkText('Next Page').click();
  await h.assertSourceNotContains('ITN015');
  await h.assertSourceContains('ITN016');
  await h.byLinkText('Prev Page').click();
  await h.assertSourceNotContains('ITN010');
  await h.assertSourceContains('ITN011');
  await h.assertSourceNotContains('ITN016');
  await h.byLinkText('Prev Page').click();
  await h.assertSourceNotContains('ITN005');
  await h.assertSourceContains('ITN006');
  await h.assertSourceNotContains('ITN011');
  await h.byLinkText('Prev Page').click();
  await h.assertSourceContains('ITN001');
  await h.assertSourceNotContains('ITN006');

  // change button count
  await h.type('ctl0$body$PageButtonCount', '2');
  await h.byName('ctl0$body$ctl6').click();
  await h.pause(50);
  await h.byLinkText('Next Page').click();
  await h.assertSourceNotContains('ITN010');
  await h.assertSourceContains('ITN011');
  await h.assertSourceNotContains('ITN016');
  await h.byLinkText('4').click();
  await h.assertSourceNotContains('ITN015');
  await h.assertSourceContains('ITN016');
  await h.byLinkText('Prev Page').click();
  await h.assertSourceNotContains('ITN005');
  await h.assertSourceContains('ITN006');
  await h.assertSourceNotContains('ITN011');

  await h.type('ctl0$body$PageButtonCount', '10');
  await h.byName('ctl0$body$ctl6').click();
  await h.pause(50);
  await h.type('ctl0$body$PageSize', '2');
  await h.pause(50);
  await h.byName('ctl0$body$ctl8').click();
  await h.assertSourceContains('ITN001');
  await h.assertSourceContains('ITN002');
  await h.assertSourceNotContains('ITN003');
  await h.byLinkText('10').click();
  await h.assertSourceContains('ITN019');
  await h.assertSourceNotContains('ITN018');
});
