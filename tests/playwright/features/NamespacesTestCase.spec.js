import { test, expect } from '@playwright/test';
import { genericHelper } from '../helpers.js';

test('NamespacesTestCase', async ({ page }) => {
  const h = genericHelper(page);

  await h.url('features/index.php?page=Namespaces.WithoutNamespace');
  await h.pause(50);
  expect(await h.source()).toContain('Without Namespaces loaded');

  await h.url('features/index.php?page=Namespaces.WithNamespace');
  await h.pause(50);
  expect(await h.source()).toContain('With Namespaces loaded');
});
