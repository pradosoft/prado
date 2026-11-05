/**
 * Minimal PNG builder for validator functional tests.
 *
 * Produces a real, decodable 8-bit RGB PNG of the requested pixel dimensions
 * so both the browser (client-side TImageValidator) and PHP's getimagesize()
 * (server-side) read the same width and height.
 */

import zlib from 'zlib';

const CRC_TABLE = (() => {
	const table = new Int32Array(256);
	for (let n = 0; n < 256; n++) {
		let c = n;
		for (let k = 0; k < 8; k++) {
			c = c & 1 ? 0xedb88320 ^ (c >>> 1) : c >>> 1;
		}
		table[n] = c;
	}
	return table;
})();

function crc32(buf) {
	let crc = -1;
	for (let i = 0; i < buf.length; i++) {
		crc = CRC_TABLE[(crc ^ buf[i]) & 0xff] ^ (crc >>> 8);
	}
	return (crc ^ -1) >>> 0;
}

function chunk(type, data) {
	const length = Buffer.alloc(4);
	length.writeUInt32BE(data.length);
	const typeAndData = Buffer.concat([Buffer.from(type, 'latin1'), data]);
	const crc = Buffer.alloc(4);
	crc.writeUInt32BE(crc32(typeAndData));
	return Buffer.concat([length, typeAndData, crc]);
}

/**
 * @param {number} width  pixel width
 * @param {number} height pixel height
 * @returns {Buffer} a complete PNG file
 */
export function pngBuffer(width, height) {
	const signature = Buffer.from([0x89, 0x50, 0x4e, 0x47, 0x0d, 0x0a, 0x1a, 0x0a]);
	const ihdr = Buffer.alloc(13);
	ihdr.writeUInt32BE(width, 0);
	ihdr.writeUInt32BE(height, 4);
	ihdr[8] = 8; // bit depth
	ihdr[9] = 2; // color type: truecolor RGB
	// one filter byte per scanline followed by black RGB pixels
	const raw = Buffer.alloc((width * 3 + 1) * height);
	const idat = zlib.deflateSync(raw);
	return Buffer.concat([
		signature,
		chunk('IHDR', ihdr),
		chunk('IDAT', idat),
		chunk('IEND', Buffer.alloc(0)),
	]);
}

/**
 * @param {string} name   file name for the upload
 * @param {number} width  pixel width
 * @param {number} height pixel height
 * @returns {{name: string, mimeType: string, buffer: Buffer}} a Playwright setInputFiles payload
 */
export function pngFile(name, width, height) {
	return { name, mimeType: 'image/png', buffer: pngBuffer(width, height) };
}
