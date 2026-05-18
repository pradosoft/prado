import { test, expect } from '@playwright/test';
import { genericHelper } from '../helpers.js';

test('NamespacesTestCase', async ({ page }) => {
  const h = genericHelper(page);

  await h.url('features/index.php?page=Namespaces.WithoutNamespace');
  await h.assertSourceContains('Without Namespaces loaded');

  await h.url('features/index.php?page=Namespaces.WithNamespace');
  await h.assertSourceContains('With Namespaces loaded');
});
