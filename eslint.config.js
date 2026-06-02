import js from '@eslint/js';
import globals from 'globals';

export default [
	{
		// Lint all PRADO client-side source scripts.
		files: ['framework/Web/Javascripts/source/**/*.js'],

		languageOptions: {
			// Source targets ES2022. Browsers: Chrome 94+, Firefox 93+, Safari 15+
			// (the same baseline jQuery 3.7 supports).
			ecmaVersion: 2022,

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
				tinyMCE_GZ:  'readonly', // optional TinyMCE gzip-compressor (htmlarea.js)
				grecaptcha:  'readonly', // optional reCAPTCHA
				// Rico is defined by colorpicker.js (bundled copy of ricoColor.js).
				Rico:        'writable',
				// CustomEvent is polyfilled by prado.js for older browsers.
				CustomEvent: 'writable',

				// Third-party libraries Prado integrates with via PHP-emitted scripts.
				hljs:        'readonly', // highlight.js — TTextHighlighter
				ClipboardJS: 'readonly', // clipboard.js — TTextHighlighter copy-code

				// reCAPTCHA v2 loader callback. Defined in validation3.js, called
				// asynchronously by Google's loader script.
				TReCaptcha2_onloadCallback: 'writable',

				// logger.js public exports — defined in logger.js, used by
				// PHP-emitted client scripts and dev consoles.
				LogEntry:    'writable',
				LogConsole:  'writable',
				puts:        'writable',
				print_r:     'writable',
				var_dump:    'writable',
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

			// Some variables are declared but used only conditionally. Allow
			// `_`-prefixed args/locals to be intentionally unused (e.g. event
			// handlers that ignore the event object).
			'no-unused-vars': ['warn', {
				argsIgnorePattern: '^_',
				varsIgnorePattern: '^_',
				caughtErrorsIgnorePattern: '^_',
			}],

			// Loose equality is used intentionally in several places (value == false, etc.).
			'eqeqeq': 'off',

			// Legacy code has many undeclared-variable usages (implicit globals, library
			// references, etc.).  Downgrade to warn so CI surface the issues without
			// blocking.  Promote back to 'error' once the codebase is cleaned up.
			'no-undef': 'warn',

			// var-in-switch-case redeclarations are common in the legacy code; warn only.
			// `builtinGlobals: false` lets the file that DEFINES a global
			// (e.g. logger.js defining puts/var_dump/print_r, prado.js
			// defining Prado) keep its `var foo = …` declaration without
			// being flagged as a redeclaration of the globals listed above.
			'no-redeclare': ['warn', { builtinGlobals: false }],

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
