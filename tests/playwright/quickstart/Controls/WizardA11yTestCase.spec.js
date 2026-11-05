import { test, expect } from '@playwright/test';
import { demosHelper } from '../../helpers.js';

/**
 * The TWizard side bar marks the active step with aria-current="step" so
 * assistive technology can announce which step is current; other steps in the
 * side bar do not carry the marker.
 */
test('QuickstartWizardA11yTestCase', async ({ page }) => {
	const h = demosHelper(page);

	await h.url('quickstart/index.php?page=Controls.Samples.TWizard.Sample2&notheme=true&lang=en');
	await h.assertTitle('PRADO QuickStart Sample');

	const step1 = page.locator('#ctl0_body_Wizard1_SideBarList_ctl0_SideBarButton');
	const step2 = page.locator('#ctl0_body_Wizard1_SideBarList_ctl1_SideBarButton');

	// Step 1 is active; only its side-bar button carries aria-current.
	await expect(step1).toHaveAttribute('aria-current', 'step');
	expect(await step2.getAttribute('aria-current')).toBeNull();
});
