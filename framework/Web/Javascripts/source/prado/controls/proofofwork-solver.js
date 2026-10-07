/*! PRADO TProofOfWork solver javascript file | github.com/pradosoft/prado */
/* global WorkerGlobalScope */

/**
 * TProofOfWork solver.
 *
 * Finds the number n in [0, max] where SHA-256(salt + n), in lowercase hex, equals the challenge.
 * The SHA-256 is pure JavaScript, so it runs without crypto.subtle, which needs a secure context.
 * Salts and numbers are ASCII.
 *
 * Loaded as a Web Worker, the file answers {challenge, salt, max} messages with {number}, where
 * number is -1 when no solution exists. Loaded as a page script, it defines
 * Prado.ProofOfWorkSolver with sha256Hex(), solve(), and solveAsync() for the main-thread fallback.
 */
(function(scope)
{
	'use strict';

	const K = new Uint32Array([
		0x428a2f98, 0x71374491, 0xb5c0fbcf, 0xe9b5dba5, 0x3956c25b, 0x59f111f1, 0x923f82a4, 0xab1c5ed5,
		0xd807aa98, 0x12835b01, 0x243185be, 0x550c7dc3, 0x72be5d74, 0x80deb1fe, 0x9bdc06a7, 0xc19bf174,
		0xe49b69c1, 0xefbe4786, 0x0fc19dc6, 0x240ca1cc, 0x2de92c6f, 0x4a7484aa, 0x5cb0a9dc, 0x76f988da,
		0x983e5152, 0xa831c66d, 0xb00327c8, 0xbf597fc7, 0xc6e00bf3, 0xd5a79147, 0x06ca6351, 0x14292967,
		0x27b70a85, 0x2e1b2138, 0x4d2c6dfc, 0x53380d13, 0x650a7354, 0x766a0abb, 0x81c2c92e, 0x92722c85,
		0xa2bfe8a1, 0xa81a664b, 0xc24b8b70, 0xc76c51a3, 0xd192e819, 0xd6990624, 0xf40e3585, 0x106aa070,
		0x19a4c116, 0x1e376c08, 0x2748774c, 0x34b0bcb5, 0x391c0cb3, 0x4ed8aa4a, 0x5b9cca4f, 0x682e6ff3,
		0x748f82ee, 0x78a5636f, 0x84c87814, 0x8cc70208, 0x90befffa, 0xa4506ceb, 0xbef9a3f7, 0xc67178f2
	]);
	const W = new Uint32Array(64);
	const HEX = '0123456789abcdef';

	/**
	 * Returns the SHA-256 of an ASCII string as lowercase hex.
	 * @param {string} message ASCII text
	 * @return {string} 64 hex characters
	 */
	function sha256Hex(message)
	{
		const length = message.length;
		const blocks = ((length + 9 + 63) >> 6) << 4;
		const words = new Uint32Array(blocks);
		for (let i = 0; i < length; i++)
			words[i >> 2] |= (message.charCodeAt(i) & 0xff) << (24 - (i & 3) * 8);
		words[length >> 2] |= 0x80 << (24 - (length & 3) * 8);
		words[blocks - 1] = length * 8;

		let h0 = 0x6a09e667, h1 = 0xbb67ae85, h2 = 0x3c6ef372, h3 = 0xa54ff53a;
		let h4 = 0x510e527f, h5 = 0x9b05688c, h6 = 0x1f83d9ab, h7 = 0x5be0cd19;
		for (let offset = 0; offset < blocks; offset += 16) {
			for (let t = 0; t < 16; t++)
				W[t] = words[offset + t];
			for (let t = 16; t < 64; t++) {
				const w15 = W[t - 15], w2 = W[t - 2];
				const s0 = ((w15 >>> 7) | (w15 << 25)) ^ ((w15 >>> 18) | (w15 << 14)) ^ (w15 >>> 3);
				const s1 = ((w2 >>> 17) | (w2 << 15)) ^ ((w2 >>> 19) | (w2 << 13)) ^ (w2 >>> 10);
				W[t] = (W[t - 16] + s0 + W[t - 7] + s1) | 0;
			}
			let a = h0, b = h1, c = h2, d = h3, e = h4, f = h5, g = h6, h = h7;
			for (let t = 0; t < 64; t++) {
				const S1 = ((e >>> 6) | (e << 26)) ^ ((e >>> 11) | (e << 21)) ^ ((e >>> 25) | (e << 7));
				const t1 = (h + S1 + ((e & f) ^ (~e & g)) + K[t] + W[t]) | 0;
				const S0 = ((a >>> 2) | (a << 30)) ^ ((a >>> 13) | (a << 19)) ^ ((a >>> 22) | (a << 10));
				const t2 = (S0 + ((a & b) ^ (a & c) ^ (b & c))) | 0;
				h = g; g = f; f = e; e = (d + t1) | 0;
				d = c; c = b; b = a; a = (t1 + t2) | 0;
			}
			h0 = (h0 + a) | 0; h1 = (h1 + b) | 0; h2 = (h2 + c) | 0; h3 = (h3 + d) | 0;
			h4 = (h4 + e) | 0; h5 = (h5 + f) | 0; h6 = (h6 + g) | 0; h7 = (h7 + h) | 0;
		}

		let hex = '';
		for (const word of [h0, h1, h2, h3, h4, h5, h6, h7])
			for (let shift = 28; shift >= 0; shift -= 4)
				hex += HEX[(word >>> shift) & 0xf];
		return hex;
	}

	/**
	 * Searches [from, to] for the number that solves the challenge.
	 * @param {string} challenge the target hash in lowercase hex
	 * @param {string} salt the salt prepended to each number
	 * @param {number} from the first number to try
	 * @param {number} to the last number to try
	 * @return {number} the solution, or -1 when the range has none
	 */
	function solve(challenge, salt, from, to)
	{
		for (let n = from; n <= to; n++)
			if (sha256Hex(salt + n) === challenge)
				return n;
		return -1;
	}

	/**
	 * Searches [0, max] in chunks that yield to the event loop.
	 * @param {string} challenge the target hash in lowercase hex
	 * @param {string} salt the salt prepended to each number
	 * @param {number} max the largest number to try
	 * @param {number} chunk the numbers tried before each yield
	 * @return {Promise<number>} the solution, or -1 when none exists
	 */
	function solveAsync(challenge, salt, max, chunk)
	{
		chunk = chunk || 5000;
		return new Promise(function(resolve)
		{
			let from = 0;
			(function step()
			{
				const to = Math.min(from + chunk - 1, max);
				const found = solve(challenge, salt, from, to);
				if (found >= 0 || to >= max)
					return resolve(found);
				from = to + 1;
				setTimeout(step, 0);
			})();
		});
	}

	if (typeof WorkerGlobalScope !== 'undefined' && scope instanceof WorkerGlobalScope) {
		scope.onmessage = function(event)
		{
			const data = event.data;
			scope.postMessage({ number: solve(data.challenge, data.salt, 0, data.max) });
		};
	} else if (scope.Prado) {
		scope.Prado.ProofOfWorkSolver = { sha256Hex: sha256Hex, solve: solve, solveAsync: solveAsync };
	}
})(typeof self !== 'undefined' ? self : globalThis);
