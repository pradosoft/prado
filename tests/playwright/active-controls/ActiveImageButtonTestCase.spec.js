import { test, expect } from '@playwright/test';
import { genericHelper } from '../helpers.js';

test('ActiveImageButtonTestCase', async ({ page }) => {
  const h = genericHelper(page);
  const base = 'ctl0_Content_';
  await h.url('active-controls/index.php?page=ActiveImageButtonTest');
  await h.assertSourceContains('TActiveImageButton Functional Test');
  await h.assertText(`${base}label1`, 'Label 1');
  await h.byId(`${base}image1`).click();
  // unable to determine mouse position
  await h.assertSourceContains('Image clicked at x=');
});

test('ActiveImageButtonScrolledClickCoordinates', async ({ page }) => {
  const h = genericHelper(page);
  const base = 'ctl0_Content_';
  await h.url('active-controls/index.php?page=ActiveImageButtonTest');

  // A fixed size and position put the image below the first screen on whole pixels.
  await page.addStyleTag({ content: `#${base}image1 { position: absolute; left: 20px; top: 2000px; width: 100px; height: 50px; border: 0; padding: 0; }` });
  const image = h.byId(`${base}image1`);
  await image.scrollIntoViewIfNeeded();
  expect(await page.evaluate(() => window.scrollY)).toBeGreaterThan(1000);

  // The callback posts the point relative to the image, as a native image submit does.
  await image.click({ position: { x: 7, y: 5 } });
  await h.assertText(`${base}label1`, 'Image clicked at x=7, y=5');
});
