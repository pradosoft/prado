import { test, expect } from '@playwright/test';
import { genericHelper } from '../helpers.js';

test('EventTriggerTestCase', async ({ page }) => {
  const h = genericHelper(page);
  const base = 'ctl0_Content_';
  await h.url('active-controls/index.php?page=EventTriggeredCallback');
  await h.assertSourceContains('Event Triggered Callback Test');

  await h.assertText(`${base}label1`, 'Label 1');

  await h.byId('button1').click();
  await h.assertText(`${base}label1`, 'button 1 clicked');

  await h.type(`${base}text1`, 'test');
  await h.assertText(`${base}label1`, 'text 1 focused');
});
