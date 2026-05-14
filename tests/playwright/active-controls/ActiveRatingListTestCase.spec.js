import { test, expect } from '@playwright/test';
import { genericHelper } from '../helpers.js';

test.describe('ActiveRatingListTestCase', () => {
  function clickTD(h, clientID) {
    return h.byXPath(`//input[@id='${clientID}']/../..`).click();
  }

  async function assertCheckBoxes(h, clientID, checks, total = 5) {
    for (let i = 0; i < total; i++) {
      if (checks.includes(i)) {
        await h.assertChecked(`${clientID}_c${i}`);
      } else {
        await h.assertNotChecked(`${clientID}_c${i}`);
      }
    }
  }

  test('testCheckBoxes', async ({ page }) => {
    const h = genericHelper(page);
    const base = 'ctl0_Content_';
    await h.url('active-controls/index.php?page=ActiveRatingListCheckBoxesTest');
    await h.assertSourceContains('TActiveRatingList Check Boxes Test Case');
    await assertCheckBoxes(h, `${base}RatingList`, [2], 6);

    await clickTD(h, `${base}RatingList_c4`);
    await assertCheckBoxes(h, `${base}RatingList`, [4], 6);

    await clickTD(h, `${base}RatingList_c2`);
    await assertCheckBoxes(h, `${base}RatingList`, [2], 6);
  });

  test('testRating', async ({ page }) => {
    const h = genericHelper(page);
    const base = 'ctl0_Content_';
    await h.url('active-controls/index.php?page=ActiveRatingListRatingTest');
    await h.assertSourceContains('TActiveRatingList Rating Test Case');

    await h.assertText(`${base}Status`, 'Rating: 5');

    await clickTD(h, `${base}RatingList_c0`);
    await h.assertText(`${base}Status`, 'Rating: 1');

    await h.byId(`${base}SetRating`).click();
    await h.assertText(`${base}Status`, 'Rating: 3');
  });

  test('testSelectedIndex', async ({ page }) => {
    const h = genericHelper(page);
    const base = 'ctl0_Content_';
    await h.url('active-controls/index.php?page=ActiveRatingListSelectedIndexTest');
    await h.assertSourceContains('TActiveRatingList SelectedIndex Test Case');
    await h.assertText(`${base}Status`, 'SelectedIndex: 1');

    await clickTD(h, `${base}RatingList_c4`);
    await h.assertText(`${base}Status`, 'SelectedIndex: 4');

    await h.byId(`${base}SetSelectedIndex`).click();
    await h.assertText(`${base}Status`, 'SelectedIndex: 5');
  });

  test('testAutoPostBack', async ({ page }) => {
    const h = genericHelper(page);
    const base = 'ctl0_Content_';
    await h.url('active-controls/index.php?page=ActiveRatingListAutoPostBackTest');
    await h.assertSourceContains('TActiveRatingList AutoPostBack Test Case');
    await h.assertText(`${base}Status`, 'AutoPostback=false');

    await clickTD(h, `${base}RatingList_c3`);
    await h.assertText(`${base}Status`, 'AutoPostback=false');

    await h.byId(`${base}Submit`).click();
    await h.assertText(`${base}Status`, '4 : Good');
  });

  test('testAllowInput', async ({ page }) => {
    const h = genericHelper(page);
    const base = 'ctl0_Content_';
    await h.url('active-controls/index.php?page=ActiveRatingListAllowInputTest');
    await h.assertSourceContains('TActiveRatingList AllowInput Test Case');
    await h.assertText(`${base}Status`, 'AllowInput=false');
    await assertCheckBoxes(h, `${base}RatingList`, [3], 6);

    await clickTD(h, `${base}RatingList_c5`);
    await h.assertText(`${base}Status`, 'AllowInput=false');
    await assertCheckBoxes(h, `${base}RatingList`, [3], 6);
  });

  test('testReadOnly', async ({ page }) => {
    const h = genericHelper(page);
    const base = 'ctl0_Content_';
    await h.url('active-controls/index.php?page=ActiveRatingListReadOnlyTest');
    await h.assertSourceContains('TActiveRatingList ReadOnly Test Case');
    await h.assertText(`${base}Status`, 'ReadOnly=true');
    await assertCheckBoxes(h, `${base}RatingList`, [0], 6);

    await clickTD(h, `${base}RatingList_c4`);
    await h.assertText(`${base}Status`, 'ReadOnly=true');
    await assertCheckBoxes(h, `${base}RatingList`, [0], 6);

    await h.byId(`${base}Writable`).click();
    await h.assertText(`${base}Status`, 'ReadOnly=false');
    await assertCheckBoxes(h, `${base}RatingList`, [0], 6);

    await clickTD(h, `${base}RatingList_c1`);
    await h.assertText(`${base}Status`, '2 : Fair');
    await assertCheckBoxes(h, `${base}RatingList`, [1], 6);

    await h.byId(`${base}ReadOnly`).click();
    await h.assertText(`${base}Status`, 'ReadOnly=true');
    await assertCheckBoxes(h, `${base}RatingList`, [1], 6);

    await clickTD(h, `${base}RatingList_c2`);
    await h.assertText(`${base}Status`, 'ReadOnly=true');
    await assertCheckBoxes(h, `${base}RatingList`, [1], 6);
  });

  test('testEnabled', async ({ page }) => {
    const h = genericHelper(page);
    const base = 'ctl0_Content_';
    await h.url('active-controls/index.php?page=ActiveRatingListEnabledTest');
    await h.assertSourceContains('TActiveRatingList Enabled Test Case');
    await h.assertText(`${base}Status`, 'Enabled=false');
    await assertCheckBoxes(h, `${base}RatingList`, [5], 6);

    await clickTD(h, `${base}RatingList_c4`);
    await h.assertText(`${base}Status`, 'Enabled=false');
    await assertCheckBoxes(h, `${base}RatingList`, [5], 6);

    await h.byId(`${base}Enable`).click();
    await h.assertText(`${base}Status`, 'Enabled=true');
    await assertCheckBoxes(h, `${base}RatingList`, [5], 6);

    await clickTD(h, `${base}RatingList_c3`);
    await h.assertText(`${base}Status`, '4 : Good');
    await assertCheckBoxes(h, `${base}RatingList`, [3], 6);

    await h.byId(`${base}Disable`).click();
    await h.assertText(`${base}Status`, 'Enabled=false');
    await assertCheckBoxes(h, `${base}RatingList`, [3], 6);

    await clickTD(h, `${base}RatingList_c5`);
    await h.assertText(`${base}Status`, 'Enabled=false');
    await assertCheckBoxes(h, `${base}RatingList`, [3], 6);
  });

  test('testHoverCaption', async ({ page }) => {
    const h = genericHelper(page);
    const base = 'ctl0_Content_';
    await h.url('active-controls/index.php?page=ActiveRatingListHoverCaptionTest');
    await h.assertSourceContains('TActiveRatingList Hover Caption Test Case');
    await h.assertText(`${base}Status`, "CaptionID='Status'");
    await h.assertElementPresent(`//input[@id='${base}RatingList_c0']/../../../td[contains(@class, 'rating_selected')]`);
    await h.assertElementPresent(`//input[@id='${base}RatingList_c1']/../../../td[contains(@class, 'rating_selected')]`);
    await h.assertElementPresent(`//input[@id='${base}RatingList_c2']/../../../td[contains(@class, 'rating_selected')]`);
    await h.assertElementPresent(`//input[@id='${base}RatingList_c3']/../../../td[contains(@class, 'rating_half')]`);
    await h.assertElementPresent(`//input[@id='${base}RatingList_c3']/../../../td[contains(@class, 'rating')]`);
    await h.assertElementPresent(`//input[@id='${base}RatingList_c4']/../../../td[contains(@class, 'rating')]`);
    await h.assertElementPresent(`//input[@id='${base}RatingList_c5']/../../../td[contains(@class, 'rating')]`);

    await h.moveto(h.byXPath(`//input[@id='${base}RatingList_c4']/../..`));
    await h.waitForAjaxCalls();
    await h.assertText(`${base}Status`, 'Excellent');
    await h.assertElementPresent(`//input[@id='${base}RatingList_c0']/../../../td[contains(@class, 'rating_hover')]`);
    await h.assertElementPresent(`//input[@id='${base}RatingList_c1']/../../../td[contains(@class, 'rating_hover')]`);
    await h.assertElementPresent(`//input[@id='${base}RatingList_c2']/../../../td[contains(@class, 'rating_hover')]`);
    await h.assertElementPresent(`//input[@id='${base}RatingList_c3']/../../../td[contains(@class, 'rating_hover')]`);
    await h.assertElementPresent(`//input[@id='${base}RatingList_c4']/../../../td[contains(@class, 'rating_hover')]`);
    await h.assertElementPresent(`//input[@id='${base}RatingList_c5']/../../../td[not(contains(@class, 'rating_hover'))]`);
    await h.assertElementPresent(`//input[@id='${base}RatingList_c5']/../../../td[contains(@class, 'rating')]`);

    await h.moveto(h.byCssSelector('body'));
    await h.waitForAjaxCalls();
    await h.assertText(`${base}Status`, "CaptionID='Status'");
    await h.assertElementPresent(`//input[@id='${base}RatingList_c0']/../../../td[contains(@class, 'rating_selected')]`);
    await h.assertElementPresent(`//input[@id='${base}RatingList_c1']/../../../td[contains(@class, 'rating_selected')]`);
    await h.assertElementPresent(`//input[@id='${base}RatingList_c2']/../../../td[contains(@class, 'rating_selected')]`);
    await h.assertElementPresent(`//input[@id='${base}RatingList_c3']/../../../td[not(contains(@class, 'rating_selected'))]`);
    await h.assertElementPresent(`//input[@id='${base}RatingList_c3']/../../../td[contains(@class, 'rating')]`);
    await h.assertElementPresent(`//input[@id='${base}RatingList_c4']/../../../td[contains(@class, 'rating')]`);
    await h.assertElementPresent(`//input[@id='${base}RatingList_c5']/../../../td[contains(@class, 'rating')]`);

    await h.moveto(h.byXPath(`//input[@id='${base}RatingList_c1']/../..`));
    await h.waitForAjaxCalls();
    await h.assertText(`${base}Status`, 'Fair');

    await h.byXPath(`//input[@id='${base}RatingList_c1']/../..`).click();
    await h.waitForAjaxCalls();
    await h.assertText(`${base}Status`, '2 : Fair');
    await h.assertElementPresent(`//input[@id='${base}RatingList_c0']/../../../td[contains(@class, 'rating_selected')]`);
    await h.assertElementPresent(`//input[@id='${base}RatingList_c1']/../../../td[contains(@class, 'rating_selected')]`);
    await h.assertElementPresent(`//input[@id='${base}RatingList_c2']/../../../td[not(contains(@class, 'rating_selected'))]`);
    await h.assertElementPresent(`//input[@id='${base}RatingList_c2']/../../../td[contains(@class, 'rating')]`);
    await h.assertElementPresent(`//input[@id='${base}RatingList_c3']/../../../td[contains(@class, 'rating')]`);
    await h.assertElementPresent(`//input[@id='${base}RatingList_c4']/../../../td[contains(@class, 'rating')]`);
    await h.assertElementPresent(`//input[@id='${base}RatingList_c5']/../../../td[contains(@class, 'rating')]`);
  });
});
