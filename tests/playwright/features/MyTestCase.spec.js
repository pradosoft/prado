import { test, expect } from '@playwright/test';
import { genericHelper } from '../helpers.js';

test.describe('MyTestCase', () => {
  test('test1', async ({ page }) => {
    const h = genericHelper(page);
    await h.url('http://127.0.0.1');
    await h.assertSourceNotContains('asd');
  });
});
