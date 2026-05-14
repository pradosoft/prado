import { test, expect } from '@playwright/test';
import { genericHelper } from '../helpers.js';

test('ActiveRadioButtonListTestCase', async ({ page }) => {
  const h = genericHelper(page);
  const base = 'ctl0_Content_';

  async function assertCheckBoxes(checks, total = 5) {
    for (let i = 0; i < total; i++) {
      if (checks.includes(i)) {
        await h.assertChecked(`${base}list1_c${i}`);
      } else {
        await h.assertNotChecked(`${base}list1_c${i}`);
      }
    }
  }

  await h.url('active-controls/index.php?page=ActiveRadioButtonListTest');
  await h.assertSourceContains('TActiveRadioButtonList Test Case');

  await h.assertText(`${base}label1`, 'Label 1');

  await h.byId(`${base}button3`).click();
  await assertCheckBoxes([0]);

  await h.byId(`${base}button2`).click();
  await assertCheckBoxes([]);

  await h.byId(`${base}button4`).click();
  await assertCheckBoxes([4]);

  await h.byId(`${base}list1_c2`).click();
  await h.assertText(`${base}label1`, 'Selection: value 3');

  await h.byId(`${base}list1_c3`).click();
  await h.assertText(`${base}label1`, 'Selection: value 4');
});
