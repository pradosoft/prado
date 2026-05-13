import { test, expect } from '@playwright/test';
import { genericHelper } from '../helpers.js';

test('DatePickerTestCase', async ({ page }) => {
  const h = genericHelper(page);
  const year = new Date().getUTCFullYear();
  const year2 = year + 1;
  const base = 'ctl0_Content_';
  await h.url('validators/index.php?page=DatePicker');
  await h.assertSourceContains('Date Picker validation Test');
  await h.assertNotVisible(`${base}validator1`);
  await h.assertNotVisible(`${base}validator2`);
  await h.assertNotVisible(`${base}validator4`);
  await h.assertNotVisible(`${base}validator5`);
  await h.assertNotVisible(`${base}validator6`);
  await h.assertNotVisible(`${base}validator8`);

  await h.byId(`${base}submit1`).click();
  await h.pause(500);
  await h.assertVisible(`${base}validator1`);
  await h.assertNotVisible(`${base}validator2`);

  // the range validator is visible because the date is a drop down list
  // thus has default value != ""
  await h.assertVisible(`${base}validator4`);
  await h.assertVisible(`${base}validator5`);
  await h.assertNotVisible(`${base}validator6`);
  await h.assertVisible(`${base}validator8`);

  await h.type(`${base}picker1`, `13/4/${year}`);
  await h.select(`${base}picker2_month`, '9');
  await h.select(`${base}picker2_day`, '10');
  await h.select(`${base}picker2_year`, `${year2}`);
  await h.pause(250);
  await h.type(`${base}picker3`, `14/4/${year}`);
  await h.pause(250);
  await h.type(`${base}picker4`, `7/4/${year}`);
  await h.select(`${base}picker5_day`, '6');
  await h.select(`${base}picker5_month`, '3');
  await h.select(`${base}picker5_year`, `${year2}`);
  await h.select(`${base}picker6_month`, '3');
  await h.select(`${base}picker6_year`, `${year2}`);
  await h.select(`${base}picker6_day`, '5');
  await h.byId(`${base}submit1`).click();
  await h.pause(500);

  await h.assertNotVisible(`${base}validator1`);
  await h.assertVisible(`${base}validator2`);
  await h.assertNotVisible(`${base}validator4`);
  await h.assertNotVisible(`${base}validator5`);
  await h.assertVisible(`${base}validator6`);
  await h.assertVisible(`${base}validator8`);

  await h.type(`${base}picker1`, `20/4/${year2}`);
  await h.type(`${base}picker4`, `29/4/${year}`);
  await h.select(`${base}picker6_day`, '10');

  await h.byId(`${base}submit1`).click();

  await h.assertNotVisible(`${base}validator1`);
  await h.assertNotVisible(`${base}validator2`);
  await h.assertNotVisible(`${base}validator4`);
  await h.assertNotVisible(`${base}validator5`);
  await h.assertNotVisible(`${base}validator6`);
  await h.assertNotVisible(`${base}validator8`);
});
