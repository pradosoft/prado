import { test, expect } from '@playwright/test';
import { demosHelper } from '../../helpers.js';

test('QuickstartHangmanTestCase', async ({ page }) => {
  const h = demosHelper(page);

  await h.url('quickstart/index.php?page=Fundamentals.Samples.Hangman.Home&notheme=true&lang=en');

  await h.assertTitle('Hangman Game');
  await h.assertSourceContains('Medium game; you are allowed 5 misses.');
  await h.byXPath("//input[@type='submit' and @value='Play!']").click();
  await h.assertSourceContains('You must choose a difficulty level');
  await h.byXPath("//input[@type='submit' and @value='Play!']").click();
  await h.pause(50);
  await h.byXPath("//input[@name='ctl0$body$LevelSelection' and @value='3']").click();
  await h.pause(50);
  await h.byXPath("//input[@type='submit' and @value='Play!']").click();
  await h.assertSourceContains('Please make a guess');
  await h.assertSourceContains('maximum of 3');
  await h.byLinkText('B').click();
  await h.pause(50);
  await h.byLinkText('F').click();
  await h.pause(50);
  await h.byLinkText('Give up?').click();
  await h.assertSourceContains('You Lose');
  await h.byLinkText('Start Again').click();
  await h.pause(50);
  await h.byXPath("//input[@type='submit' and @value='Play!']").click();
  await h.assertSourceContains('Please make a guess');
  await h.assertSourceContains('maximum of 3');
  await h.byLinkText('Give up?').click();
  await h.assertSourceContains('You Lose');
  await h.byLinkText('Start Again').click();
  await h.pause(50);
  await h.byXPath("//input[@name='ctl0$body$LevelSelection' and @value='5']").click();
  await h.pause(50);
  await h.byXPath("//input[@type='submit' and @value='Play!']").click();
  await h.assertSourceContains('maximum of 5');
});
