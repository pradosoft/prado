import { test, expect } from '@playwright/test';
import { genericHelper } from '../helpers.js';

test('Ticket528TestCase', async ({ page }) => {
	const base = 'ctl0_Content_';
	const h = genericHelper(page);
	await h.url('tickets/index.php?page=Ticket528');
	await h.assertTitle('Verifying Ticket 528');

	await h.select(`${base}DDropTurno`, 'Tarde');

	await h.assertValue(`${base}Codigo`, 'T');
	await h.assertValue(`${base}Descricao`, 'Tarde');

	await h.select(`${base}DDropTurno`, 'Manhã');

	await h.assertValue(`${base}Codigo`, 'M');
	await h.assertValue(`${base}Descricao`, 'Manhã');

	await h.select(`${base}DDropTurno`, 'Noite');

	await h.assertValue(`${base}Codigo`, 'N');
	await h.assertValue(`${base}Descricao`, 'Noite');
});
