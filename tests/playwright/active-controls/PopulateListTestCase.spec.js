import { test, expect } from '@playwright/test';
import { genericHelper } from '../helpers.js';

test('PopulateListTestCase', async ({ page }) => {
  const h = genericHelper(page);
  const base = 'ctl0_Content_';
  await h.url('active-controls/index.php?page=PopulateActiveList');
  await h.assertSourceContains('Populate active list controls');
  await h.assertText(`${base}label1`, '');

  await h.byId(`${base}button1`).click();
  await h.select(`${base}list1`, 'World');
  await h.assertText(`${base}label1`, 'list1: World');

  await h.byId(`${base}button2`).click();
  await h.select(`${base}list2`, 'Prado');
  await h.assertText(`${base}label1`, 'list2: Prado');
});
