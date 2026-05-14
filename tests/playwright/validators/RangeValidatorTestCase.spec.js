import { test, expect } from '@playwright/test';
import { genericHelper } from '../helpers.js';

test.describe('RangeValidatorTestCase', () => {
  test('testIntegerRange', async ({ page }) => {
    const h = genericHelper(page);
    const base = 'ctl0_Content_';
    await h.url('validators/index.php?page=RangeValidatorInteger');
    await h.assertSourceContains('Prado RangeValidator Tests Integer');

    // between 1 and 4
    await h.type(`${base}text1`, 'ad');
    await h.assertNotVisible(`${base}validator1`);
    await h.byXPath("//input[@type='submit' and @value='Test']").click();
    await h.assertVisible(`${base}validator1`);
    await h.type(`${base}text1`, '12');
    await h.byXPath("//input[@type='submit' and @value='Test']").click();
    await h.assertVisible(`${base}validator1`);
    await h.type(`${base}text1`, '2');
    await h.assertNotVisible(`${base}validator1`);
    await h.byXPath("//input[@type='submit' and @value='Test']").click();
    await h.assertNotVisible(`${base}validator1`);

    // >= 2
    await h.assertNotVisible(`${base}validator2`);
    await h.type(`${base}text2`, '1');
    await h.byXPath("//input[@type='submit' and @value='Test']").click();
    await h.assertVisible(`${base}validator2`);
    await h.type(`${base}text2`, '10');
    await h.byXPath("//input[@type='submit' and @value='Test']").click();
    await h.assertNotVisible(`${base}validator2`);

    // <= 20
    await h.assertNotVisible(`${base}validator3`);
    await h.type(`${base}text3`, '100');
    await h.byXPath("//input[@type='submit' and @value='Test']").click();
    await h.assertVisible(`${base}validator3`);
    await h.type(`${base}text3`, '10');
    await h.byXPath("//input[@type='submit' and @value='Test']").click();
    await h.assertNotVisible(`${base}validator3`);
  });

  test('testFloatRange', async ({ page }) => {
    const h = genericHelper(page);
    const base = 'ctl0_Content_';
    await h.url('validators/index.php?page=RangeValidatorFloat');
    await h.assertSourceContains('Prado RangeValidator Tests Float');

    // between 1 and 4
    await h.type(`${base}text1`, 'ad');
    await h.assertNotVisible(`${base}validator1`);
    await h.byXPath("//input[@type='submit' and @value='Test']").click();
    await h.assertVisible(`${base}validator1`);
    await h.type(`${base}text1`, '12');
    await h.byXPath("//input[@type='submit' and @value='Test']").click();
    await h.assertVisible(`${base}validator1`);
    await h.type(`${base}text1`, '2');
    await h.assertNotVisible(`${base}validator1`);
    await h.byXPath("//input[@type='submit' and @value='Test']").click();
    await h.assertNotVisible(`${base}validator1`);

    // >= 2
    await h.assertNotVisible(`${base}validator2`);
    await h.type(`${base}text2`, '1');
    await h.byXPath("//input[@type='submit' and @value='Test']").click();
    await h.assertVisible(`${base}validator2`);
    await h.type(`${base}text2`, '10');
    await h.byXPath("//input[@type='submit' and @value='Test']").click();
    await h.assertNotVisible(`${base}validator2`);

    // <= 20
    await h.assertNotVisible(`${base}validator3`);
    await h.type(`${base}text3`, '100');
    await h.byXPath("//input[@type='submit' and @value='Test']").click();
    await h.assertVisible(`${base}validator3`);
    await h.type(`${base}text3`, '10');
    await h.byXPath("//input[@type='submit' and @value='Test']").click();
    await h.assertNotVisible(`${base}validator3`);
  });

  test('testDateRange', async ({ page }) => {
    const h = genericHelper(page);
    const base = 'ctl0_Content_';
    await h.url('validators/index.php?page=RangeValidatorDate');
    await h.assertSourceContains('Prado RangeValidator Tests Date');

    // between 22/1/2005 and 3/2/2005
    await h.type(`${base}text1`, 'ad');
    await h.assertNotVisible(`${base}validator1`);
    await h.byXPath("//input[@type='submit' and @value='Test']").click();
    await h.assertVisible(`${base}validator1`);
    await h.type(`${base}text1`, '27/2/2005');
    await h.byXPath("//input[@type='submit' and @value='Test']").click();
    await h.assertVisible(`${base}validator1`);
    await h.type(`${base}text1`, '1/2/2005');
    await h.byXPath("//input[@type='submit' and @value='Test']").click();
    await h.assertNotVisible(`${base}validator1`);
    await h.byXPath("//input[@type='submit' and @value='Test']").click();
    await h.assertNotVisible(`${base}validator1`);

    // >= 22/1/2005
    await h.assertNotVisible(`${base}validator2`);
    await h.type(`${base}text2`, '1/1/2005');
    await h.byXPath("//input[@type='submit' and @value='Test']").click();
    await h.assertVisible(`${base}validator2`);
    await h.type(`${base}text2`, '1/4/2005');
    await h.byXPath("//input[@type='submit' and @value='Test']").click();
    await h.assertNotVisible(`${base}validator2`);

    // <= 3/2/2005
    await h.assertNotVisible(`${base}validator3`);
    await h.type(`${base}text3`, '4/5/2005');
    await h.byXPath("//input[@type='submit' and @value='Test']").click();
    await h.assertVisible(`${base}validator3`);
    await h.type(`${base}text3`, '1/2/2005');
    await h.byXPath("//input[@type='submit' and @value='Test']").click();
    await h.assertNotVisible(`${base}validator3`);
  });

  test('testStringRange', async ({ page }) => {
    const h = genericHelper(page);
    const base = 'ctl0_Content_';
    await h.url('validators/index.php?page=RangeValidatorString');
    await h.assertSourceContains('Prado RangeValidator Tests String');

    // between 'd' and 'y'
    await h.type(`${base}text1`, 'a');
    await h.assertNotVisible(`${base}validator1`);
    await h.byXPath("//input[@type='submit' and @value='Test']").click();
    await h.assertVisible(`${base}validator1`);
    await h.type(`${base}text1`, 'b');
    await h.byXPath("//input[@type='submit' and @value='Test']").click();
    await h.assertVisible(`${base}validator1`);
    await h.type(`${base}text1`, 'f');
    await h.assertNotVisible(`${base}validator1`);
    await h.pause(50);
    await h.byXPath("//input[@type='submit' and @value='Test']").click();
    await h.pause(50);
    await h.assertNotVisible(`${base}validator1`);

    // >= 'd'
    await h.assertNotVisible(`${base}validator2`);
    await h.type(`${base}text2`, 'a');
    await h.byXPath("//input[@type='submit' and @value='Test']").click();
    await h.assertVisible(`${base}validator2`);
    await h.type(`${base}text2`, 'g');
    await h.byXPath("//input[@type='submit' and @value='Test']").click();
    await h.assertNotVisible(`${base}validator2`);

    // <= 'y'
    await h.assertNotVisible(`${base}validator3`);
    await h.type(`${base}text3`, 'z');
    await h.byXPath("//input[@type='submit' and @value='Test']").click();
    await h.assertVisible(`${base}validator3`);
    await h.type(`${base}text3`, 't');
    await h.byXPath("//input[@type='submit' and @value='Test']").click();
    await h.assertNotVisible(`${base}validator3`);
  });
});
