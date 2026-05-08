import js from '@eslint/js';
import globals from 'globals';

export default [
	{
		// Lint all PRADO client-side source scripts.
		files: ['framework/Web/Javascripts/source/**/*.js'],

		languageOptions: {
			// Current source targets ES5+ with some ES6 idioms.
			ecmaVersion: 2015,

			// Scripts are concatenated / loaded as <script> tags — not modules.
			// When ESM conversion happens, change this to 'module'.
			sourceType: 'script',

			globals: {
				// Standard browser globals — spread first so our overrides win.
				...globals.browser,

				// PRADO-specific globals.
				jQuery:      'readonly', // loaded before any PRADO script
				$:           'readonly', // jQuery shorthand alias used in ratings.js
				Prado:       'writable', // defined in prado.js, extended by others
				// Logger is declared/initialised by prado.js — must be writable.
				Logger:      'writable',
				tinyMCE:     'readonly', // optional TinyMCE editor
				grecaptcha:  'readonly', // optional reCAPTCHA
				// Rico is defined by colorpicker.js (bundled copy of ricoColor.js).
				Rico:        'writable',
				// CustomEvent is polyfilled by prado.js for older browsers.
				CustomEvent: 'writable',
			},
		},

		rules: {
			// Pull in eslint's safe defaults.
			...js.configs.recommended.rules,

			// Legacy codebase intentionally uses var everywhere.
			'no-var': 'off',

			// eval() appears in setAttribute for inline event handlers.
			// Warn so we can track and eliminate instances over time.
			'no-eval': 'warn',

			// Some variables are declared but used only conditionally.
			'no-unused-vars': 'warn',

			// Loose equality is used intentionally in several places (value == false, etc.).
			'eqeqeq': 'off',

			// Legacy code has many undeclared-variable usages (implicit globals, library
			// references, etc.).  Downgrade to warn so CI surface the issues without
			// blocking.  Promote back to 'error' once the codebase is cleaned up.
			'no-undef': 'warn',

			// var-in-switch-case redeclarations are common in the legacy code; warn only.
			'no-redeclare': 'warn',

			// debugger statements remaining in legacy code; warn so they appear in
			// the lint report but do not block CI.
			'no-debugger': 'warn',

			// Some switch fall-throughs are intentional in the legacy validators.
			'no-fallthrough': 'warn',

			// Legacy regexes contain unnecessary escape characters.
			'no-useless-escape': 'warn',

			// Assignment-in-condition (e.g. `if (x = getX())`) is used
			// intentionally in several places.
			'no-cond-assign': 'warn',

			// Empty catch/finally blocks exist in legacy code.
			'no-empty': 'warn',
		},
	},
];
