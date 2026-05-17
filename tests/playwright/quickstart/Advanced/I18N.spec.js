import { test, expect } from '@playwright/test';
import { demosHelper } from '../../helpers.js';

test('QuickstartI18NTestCase', async ({ page }) => {
  const h = demosHelper(page);

  await h.url('quickstart/index.php?notheme=true&page=Advanced.Samples.I18N.Home&lang=en&notheme=true');
  await h.assertSourceContains('Internationlization  in PRADO');
  await h.assertSourceContains('46.412,42 €');
  await h.assertSourceContains('$12.40');
  await h.assertSourceContains('€100.00');
  await h.assertSourceContains('December 6, 2004');

  await h.url('quickstart/index.php?page=Advanced.Samples.I18N.Home&lang=zh&notheme=true');
  await h.assertSourceContains('PRADO 国际化');
  await h.assertSourceContains('2004年12月6日');
  await h.assertSourceContains('US$12.40');
  await h.assertSourceContains('46.412,42 €');
  await h.assertSourceContains('€100.00');

  // zh_TW: validCulture('zh_TW') returns false on modern ICU (hyphens vs underscores),
  // so the culture is not set and the page falls back to English.
  // The original Selenium test also never set this locale (its URL used literal &amp;lang=zh_TW
  // which PHP parsed as the parameter name "amp;lang", not "lang").
  await h.url('quickstart/index.php?page=Advanced.Samples.I18N.Home&lang=zh_TW&notheme=true');
  await h.assertSourceContains('Internationlization  in PRADO');
  await h.assertSourceContains('46.412,42 €');
  await h.assertSourceContains('$12.40');
  await h.assertSourceContains('€100.00');
  await h.assertSourceContains('December 6, 2004');

  await h.url('quickstart/index.php?page=Advanced.Samples.I18N.Home&lang=de&notheme=true');
  await h.assertSourceContains('Internationalisierung in PRADO');
  await h.assertSourceContains('6. Dezember 2004 ');
  await h.assertSourceContains('12,40 $');
  await h.assertSourceContains('46.412,42 €');
  await h.assertSourceContains('€100.00');

  await h.url('quickstart/index.php?page=Advanced.Samples.I18N.Home&lang=es&notheme=true');
  await h.assertSourceContains('Internationlization en PRADO');
  await h.assertSourceContains('6 de diciembre de 2004');
  await h.assertSourceContains('12,40 US$');
  await h.assertSourceContains('46.412,42 €');
  await h.assertSourceContains('€100.00');

  await h.url('quickstart/index.php?page=Advanced.Samples.I18N.Home&lang=fr&notheme=true');
  await h.assertSourceContains('Internationalisation avec PRADO');
  await h.assertSourceContains('6 décembre 2004');
  await h.assertSourceContains('12,40 $');
  await h.assertSourceContains('46.412,42 €');
  await h.assertSourceContains('€100.00');

  await h.url('quickstart/index.php?page=Advanced.Samples.I18N.Home&lang=pl&notheme=true');
  await h.assertSourceContains('Internacjonalizacja w PRADO');
  await h.assertSourceContains('6 grudnia 2004');
  await h.assertSourceContains('12,40 USD');
  await h.assertSourceContains('46.412,42 €');
  await h.assertSourceContains('€100.00');
});
