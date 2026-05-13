import { test, expect } from '@playwright/test';
import { demosHelper } from '../../helpers.js';

test('QuickstartPagerTestCase', async ({ page }) => {
  const h = demosHelper(page);

  await h.url('quickstart/index.php?page=Controls.Samples.TPager.Sample1&notheme=true&lang=en');

  // verify datalist content
  await h.assertSourceContains('ITN001');
  await h.assertSourceContains('ITN002');
  await h.assertSourceNotContains('ITN003');

  // verify numeric paging
  await h.byId('ctl0_body_Pager_ctl1').click(); // 2nd page
  await h.assertSourceContains('ITN003');
  await h.assertSourceContains('ITN004');
  await h.assertSourceNotContains('ITN002');
  await h.assertSourceNotContains('ITN005');
  await h.byId('ctl0_body_Pager_ctl3').click(); // 4th page
  await h.assertSourceContains('ITN007');
  await h.assertSourceContains('ITN008');
  await h.assertSourceNotContains('ITN006');
  await h.assertSourceNotContains('ITN009');
  await h.byId('ctl0_body_Pager_ctl6').click(); // last page
  await h.assertSourceContains('ITN019');
  await h.assertSourceNotContains('ITN018');
  await h.assertSourceNotContains('ITN001');

  // verify next-prev paging
  await h.byId('ctl0_body_Pager2_ctl1').click(); // prev page
  await h.assertSourceContains('ITN017');
  await h.assertSourceContains('ITN018');
  await h.assertSourceNotContains('ITN019');
  await h.assertSourceNotContains('ITN016');
  await h.byId('ctl0_body_Pager2_ctl0').click(); // first page
  await h.assertSourceContains('ITN001');
  await h.assertSourceContains('ITN002');
  await h.assertSourceNotContains('ITN003');
  await h.byId('ctl0_body_Pager2_ctl2').click(); // next page
  await h.assertSourceContains('ITN003');
  await h.assertSourceContains('ITN004');
  await h.assertSourceNotContains('ITN002');
  await h.assertSourceNotContains('ITN005');

  await h.assertSelected('ctl0_body_Pager3_ctl0', '2');
  await h.select('ctl0_body_Pager3_ctl0', '5');
  await h.assertSourceContains('ITN009');
  await h.assertSourceContains('ITN010');
  await h.assertSourceNotContains('ITN008');
  await h.assertSourceNotContains('ITN011');
  await h.select('ctl0_body_Pager3_ctl0', '10');
  await h.assertSourceContains('ITN019');
  await h.assertSourceNotContains('ITN018');
});
