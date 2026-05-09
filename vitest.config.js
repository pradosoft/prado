import { defineConfig } from 'vitest/config';

export default defineConfig({
	test: {
		// jsdom gives us a real DOM (document, window) that jQuery can work with.
		environment: 'jsdom',

		// Vitest injects describe/it/expect/vi/beforeAll/afterAll globally,
		// matching the Jest API that many developers expect.
		globals: true,

		include: ['tests/js/**/*.test.js'],

		// Exclude temporary debugging scratch files (delete when convenient).
		exclude: ['tests/js/debug.test.js', 'tests/js/debug-inline.test.js'],

		reporters: ['verbose'],
	},
});
