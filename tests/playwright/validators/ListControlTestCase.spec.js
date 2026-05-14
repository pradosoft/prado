import { test, expect } from '@playwright/test';
import { genericHelper } from '../helpers.js';

test('ListControlTestCase', async ({ page }) => {
  const h = genericHelper(page);
  const base = 'ctl0_Content_';
  await h.url('validators/index.php?page=ListControl');
  await h.assertSourceContains('List Control Required Field Validation Test');
  await h.byXPath("//input[@type='submit' and @value='Submit!']").click();

  await h.assertVisible(`${base}validator1`);
  await h.assertVisible(`${base}validator2`);
  await h.assertVisible(`${base}validator3`);
  await h.assertVisible(`${base}validator4`);

  await h.byXPath(`//input[@id='${base}list1_c1' and @value='Red']`).click();
  await h.select(`${base}list2`, 'Red');
  await h.select(`${base}list3`, 'Blue');
  await h.byId(`${base}list4_c3`).click();
  await h.byXPath("//input[@type='submit' and @value='Submit!']").click();

  await h.assertNotVisible(`${base}validator1`);
  await h.assertNotVisible(`${base}validator2`);
  await h.assertNotVisible(`${base}validator3`);
  await h.assertNotVisible(`${base}validator4`);

  await h.select(`${base}list3`, 'Dont select this one');
  await h.byId(`${base}list4_c0`).click();
  await h.select(`${base}list2`, '--- Select a color ---');
  await h.byXPath("//input[@type='submit' and @value='Submit!']").click();
  await h.byXPath(`//input[@id='${base}list1_c1' and @value='Red']`).click();
  await h.byXPath(`//input[@id='${base}list1_c0' and @value='Select a color below']`).click();
  await h.byXPath("//input[@type='submit' and @value='Submit!']").click();

  await h.assertVisible(`${base}validator1`);
  await h.assertVisible(`${base}validator2`);
  await h.assertVisible(`${base}validator3`);
  await h.assertVisible(`${base}validator4`);
});
