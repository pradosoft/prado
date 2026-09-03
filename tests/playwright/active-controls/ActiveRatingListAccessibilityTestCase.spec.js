import { test, expect } from '@playwright/test';
import { genericHelper } from '../helpers.js';

/**
 * Accessibility of the rating widget (parent TRatingList behavior, inherited by
 * TActiveRatingList): the star cells are a real radio group that assistive tech
 * can read and the keyboard can operate. Regression guard for the previous
 * `display:none` on the radios, which removed them from the a11y tree entirely.
 */
test.describe('ActiveRatingListAccessibilityTestCase', () => {
  const base = 'ctl0_Content_';

  test('renders an operable, labeled radio group', async ({ page }) => {
    const h = genericHelper(page);
    await h.url('active-controls/index.php?page=ActiveRatingListAutoPostBackTest');
    await h.assertSourceContains('TActiveRatingList AutoPostBack Test Case');

    const group = page.locator(`#${base}RatingList`);
    await expect(group).toHaveAttribute('role', 'radiogroup');

    // Each radio has an accessible name from its item text
    await expect(page.locator(`#${base}RatingList_c0`)).toHaveAttribute('aria-label', 'Poor');
    await expect(page.locator(`#${base}RatingList_c3`)).toHaveAttribute('aria-label', 'Good');

    // The radios are in the accessibility tree and focusable (not display:none)
    const display = await page.locator(`#${base}RatingList_c0`).evaluate(
      (el) => getComputedStyle(el).display
    );
    expect(display).not.toBe('none');

    // The decorative star cells are hidden from assistive tech
    const cellHidden = await page.locator(`#${base}RatingList_c0`).evaluate(
      (el) => el.closest('td').getAttribute('aria-hidden')
    );
    expect(cellHidden).toBe('true');

    // Keyboard: focus a star's radio and activate it with the Space key, the
    // native radio behavior — no mouse involved
    await page.locator(`#${base}RatingList_c3`).focus();
    await expect(page.locator(`#${base}RatingList_c3`)).toBeFocused();
    await page.keyboard.press('Space');
    await expect(page.locator(`#${base}RatingList_c3`)).toBeChecked();
    // The change handler updated the visual selection (stars 0..3 selected)
    const selectedStars = await page.locator(`#${base}RatingList td.rating_selected`).count();
    expect(selectedStars).toBe(4);
  });

  test('read-only rating is disabled and marked aria-readonly', async ({ page }) => {
    const h = genericHelper(page);
    await h.url('active-controls/index.php?page=ActiveRatingListReadOnlyTest');

    const group = page.locator(`#${base}RatingList`);
    await expect(group).toHaveAttribute('aria-readonly', 'true');
    await expect(page.locator(`#${base}RatingList_c0`)).toBeDisabled();

    // A callback toggling ReadOnly keeps the group state and radios in step
    await page.locator(`#${base}Writable`).click();
    await h.waitForAjaxCalls();
    await expect(page.locator(`#${base}RatingList_c0`)).toBeEnabled();
    expect(await group.getAttribute('aria-readonly')).toBeNull();

    await page.locator(`#${base}ReadOnly`).click();
    await h.waitForAjaxCalls();
    await expect(page.locator(`#${base}RatingList_c0`)).toBeDisabled();
    await expect(group).toHaveAttribute('aria-readonly', 'true');
  });
});
