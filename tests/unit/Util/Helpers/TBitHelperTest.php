<?php

use Prado\Util\Helpers\TBitHelper;
use Prado\Exceptions\TInvalidDataValueException;

class TBitHelperTest extends PHPUnit\Framework\TestCase
{
	public function testHasLongLong()
	{
		self::assertEquals(PHP_INT_SIZE >= 8, TBitHelper::hasLongLong());
	}
	
	public function testIsNegativeFloat()
	{
		self::assertEquals(false, TBitHelper::isNegativeFloat(0.0));
		self::assertEquals(false, TBitHelper::isNegativeFloat(1.0));
		self::assertEquals(false, TBitHelper::isNegativeFloat(INF));
		self::assertEquals(false, TBitHelper::isNegativeFloat(NAN));
		self::assertEquals(true, TBitHelper::isNegativeFloat(-0.0), "negative zero is not flagged as a negative float");
		self::assertEquals(true, TBitHelper::isNegativeFloat(-1.0));
		self::assertEquals(true, TBitHelper::isNegativeFloat(-INF));
	}
	
	public function testIsNegativeZero()
	{
		self::assertEquals(false, TBitHelper::isNegativeZero(0.0));
		self::assertEquals(false, TBitHelper::isNegativeZero(1.0));
		self::assertEquals(false, TBitHelper::isNegativeZero(INF));
		self::assertEquals(false, TBitHelper::isNegativeZero(NAN));
		self::assertEquals(true, TBitHelper::isNegativeZero(-0.0), "negative zero is not flagged as a negative zero");
		self::assertEquals(false, TBitHelper::isNegativeZero(-1.0));
		self::assertEquals(false, TBitHelper::isNegativeZero(-INF));
		
		self::assertTrue(-0.0 === 0.0, "isNegativeFloat is no longer needed because -0.0 !== 0.0 in PHP");
	}
	
	public function testBitCount()
	{
		//  Positive numbers
		self::assertEquals(0, TBitHelper::bitCount(0));
		self::assertEquals(1, TBitHelper::bitCount(1));
		self::assertEquals(2, TBitHelper::bitCount(2));
		self::assertEquals(2, TBitHelper::bitCount(3));
		self::assertEquals(3, TBitHelper::bitCount(4));
		self::assertEquals(3, TBitHelper::bitCount(7));
		self::assertEquals(4, TBitHelper::bitCount(8));
		self::assertEquals(4, TBitHelper::bitCount(15));
		self::assertEquals(5, TBitHelper::bitCount(16));
		self::assertEquals(5, TBitHelper::bitCount(31));
		self::assertEquals(6, TBitHelper::bitCount(32));
		self::assertEquals(6, TBitHelper::bitCount(63));
		self::assertEquals(7, TBitHelper::bitCount(64));
		self::assertEquals(7, TBitHelper::bitCount(127));
		self::assertEquals(8, TBitHelper::bitCount(128));
		self::assertEquals(8, TBitHelper::bitCount(255));
		
		//Negative numbers have one extra bit to represent their negativity.
		self::assertEquals(2, TBitHelper::bitCount(-1));
		self::assertEquals(3, TBitHelper::bitCount(-2));
		self::assertEquals(3, TBitHelper::bitCount(-3));
		self::assertEquals(4, TBitHelper::bitCount(-4));
		self::assertEquals(4, TBitHelper::bitCount(-7));
		self::assertEquals(5, TBitHelper::bitCount(-8));
		self::assertEquals(5, TBitHelper::bitCount(-15));
		self::assertEquals(6, TBitHelper::bitCount(-16));
		self::assertEquals(6, TBitHelper::bitCount(-31));
		self::assertEquals(7, TBitHelper::bitCount(-32));
		self::assertEquals(7, TBitHelper::bitCount(-63));
		self::assertEquals(8, TBitHelper::bitCount(-64));
		self::assertEquals(8, TBitHelper::bitCount(-127));
		self::assertEquals(9, TBitHelper::bitCount(-128));
		self::assertEquals(9, TBitHelper::bitCount(-255));
			
		self::assertEquals(PHP_INT_SIZE * 8, TBitHelper::bitCount(PHP_INT_MIN + 1));
		self::assertEquals(PHP_INT_SIZE * 8 - 1, TBitHelper::bitCount(PHP_INT_MAX));
		self::assertEquals(PHP_INT_SIZE * 8, TBitHelper::bitCount(PHP_INT_MIN / 2));
		self::assertEquals(PHP_INT_SIZE * 8 - 1, TBitHelper::bitCount(PHP_INT_MIN / 2 + 1));
	}
	
	public function testColorBitShift()
	{
		try {
			TBitHelper::colorBitShift(0xAA, 0, 8);
			self::fail("failed to throw TInvalidDataValueException with negative inBits");
		} catch(TInvalidDataValueException $e) {
		}
		try {
			TBitHelper::colorBitShift(0xAA, PHP_INT_SIZE * 8 + 1, 8);
			self::fail("failed to throw TInvalidDataValueException with inBits too large");
		} catch(TInvalidDataValueException $e){
		}
		try {
			TBitHelper::colorBitShift(0xAA, 8, 0);
			self::fail("failed to throw TInvalidDataValueException with negative outBits");
		} catch(TInvalidDataValueException $e) {
		}
		try {
			TBitHelper::colorBitShift(0xAA, 8, PHP_INT_SIZE * 8 + 1);
			self::fail("failed to throw TInvalidDataValueException with outBits too large");
		} catch(TInvalidDataValueException $e) {
		}
		
		self::assertEquals(0b1011001110, TBitHelper::colorBitShift(0b1011001110001010, 16, 10));
		self::assertEquals(0b1111111111, TBitHelper::colorBitShift(0b1, 1, 10));
		self::assertEquals(0b10101010101, TBitHelper::colorBitShift(0b10, 2, 11));
		self::assertEquals(0b1101101101, TBitHelper::colorBitShift(0b110, 3, 10));
		self::assertEquals(0b101010101010101, TBitHelper::colorBitShift(0b1010, 4, 15));
		self::assertEquals(0b111011101110111, TBitHelper::colorBitShift(0b1110, 4, 15));
		self::assertEquals(0b001000100010001, TBitHelper::colorBitShift(0b0010, 4, 15));
	}
	
	public function testUnsignedShift()
	{
		self::assertEquals(0, TBitHelper::unsignedShift(0, 0));
		self::assertEquals(PHP_INT_MAX, TBitHelper::unsignedShift(PHP_INT_MAX, 0));
		self::assertEquals(PHP_INT_MIN, TBitHelper::unsignedShift(PHP_INT_MIN, 0));
		self::assertEquals(-1, TBitHelper::unsignedShift(-1, 0));
		self::assertEquals(1, TBitHelper::unsignedShift(1, 0));
			
		self::assertEquals(2, TBitHelper::unsignedShift(1, -1));
		self::assertEquals(4, TBitHelper::unsignedShift(1, -2));
		self::assertEquals(8, TBitHelper::unsignedShift(2, -2));
			
		self::assertEquals(0, TBitHelper::unsignedShift(1, 1));
		self::assertEquals(1, TBitHelper::unsignedShift(2, 1));
		self::assertEquals(2, TBitHelper::unsignedShift(4, 1));
		self::assertEquals(4, TBitHelper::unsignedShift(16, 2));
			
		self::assertEquals(-1, -1 >> 1);
		self::assertEquals(-1, -1 >> 2);
		self::assertEquals(-1, -1 >> 3);
		self::assertEquals(PHP_INT_MAX, TBitHelper::unsignedShift(-1, 1));
		self::assertEquals(PHP_INT_MAX >> 1, TBitHelper::unsignedShift(-1, 2));
		self::assertEquals(PHP_INT_MAX >> 2, TBitHelper::unsignedShift(-1, 3));
	}
	
	// Convert PHP float to Half and mini float bit representations
	public function testFloatToFp16()
	{
		self::assertEquals(0x0000, TBitHelper::floatToFp16(0), "TBitHelper::floatToFp16 0 is not converting to 0x0000; float zero is not half float zero");
		self::assertEquals(0x3C00, TBitHelper::floatToFp16(1), "TBitHelper::floatToFp16 1 is not converting to 0x3C00; float one is not half float one");
		self::assertEquals(0x8001, TBitHelper::floatToFp16(-5.960464477539063E-8), "TBitHelper::floatToFp16 0x8001 is not converting smallest sub-normal number");
		self::assertEquals(0x03FF, TBitHelper::floatToFp16(6.097555160522461E-5), "TBitHelper::floatToFp16 0x03FF is not converting largest sub-normal number");
		self::assertEquals(0x0400, TBitHelper::floatToFp16(6.103515625E-5), "TBitHelper::floatToFp16 0x0400 is not converting smallest normal number");
		self::assertEquals(0x3555, TBitHelper::floatToFp16(0.333251953125), "TBitHelper::floatToFp16 0x3555 is not converting the nearest to 1/3");
		self::assertEquals(0x3bff, TBitHelper::floatToFp16(0.99951171875), "TBitHelper::floatToFp16 0x3bff is not converting the largest less than 1");
		self::assertEquals(0x3C01, TBitHelper::floatToFp16(1.0009765625), "TBitHelper::floatToFp16 0x3C01 is not converting the smallest more than 1");
		self::assertEquals(0x7bff, TBitHelper::floatToFp16(65504.0), "TBitHelper::floatToFp16 0x7bff is not converting the largest normal number");
		self::assertEquals(0x7C00, TBitHelper::floatToFp16(90000.0), "TBitHelper::floatToFp16 overly large numbers should become infinity");
		self::assertEquals(0x7C00, TBitHelper::floatToFp16(INF), "TBitHelper::floatToFp16 0x7C00 is not converting infinity");
		self::assertEquals(0x8000, TBitHelper::floatToFp16(-0.0), "TBitHelper::floatToFp16 0x8000 is not converting negative zero");
		self::assertEquals(0xC000, TBitHelper::floatToFp16(-2), "TBitHelper::floatToFp16 0xC000 is not converting negative two");
		self::assertEquals(0xFC00, TBitHelper::floatToFp16(-INF), "TBitHelper::floatToFp16 0xFC00 is not converting negative infinity");
		self::assertEquals(0x7E00, TBitHelper::floatToFp16(NAN), "TBitHelper::floatToFp16 0xFC01 is not converting Not-A-Number (NaN).");
	}
	
	public function testFloatToBf16()
	{
		self::assertEquals(0x0000, TBitHelper::floatToBf16(0), "TBitHelper::floatToBf16 0 is not converting to 0x0000; float zero is not half float zero");
		self::assertEquals(0x3F80, TBitHelper::floatToBf16(1), "TBitHelper::floatToBf16 1 is not converting to 0x3F80; float one is not half float one");
		self::assertEquals(0x8001, TBitHelper::floatToBf16(-9.183549615799121E-41), "TBitHelper::floatToBf16 0x8001 is not converting smallest sub-normal number");
		self::assertEquals(0x007F, TBitHelper::floatToBf16(1.1663108012064884E-38), "TBitHelper::floatToBf16 0x03FF is not converting largest sub-normal number");
		self::assertEquals(0x0080, TBitHelper::floatToBf16(1.1754943508222875E-38), "TBitHelper::floatToBf16 0x0400 is not converting smallest normal number");
		self::assertEquals(0x3EAB, TBitHelper::floatToBf16(0.333984375), "TBitHelper::floatToBf16 0x3555 is not converting the nearest to 1/3");
		self::assertEquals(0x3F7F, TBitHelper::floatToBf16(0.99609375), "TBitHelper::floatToBf16 0x3bff is not converting the largest less than 1");
		self::assertEquals(0x3F81, TBitHelper::floatToBf16(1.0078125), "TBitHelper::floatToBf16 0x3C01 is not converting the smallest more than 1");
		self::assertEquals(0x7f7f, TBitHelper::floatToBf16(3.3895313892515355E+38), "TBitHelper::floatToBf16 0x7bff is not converting the largest normal number");
		self::assertEquals(0x7f80, TBitHelper::floatToBf16(3.3895313892515355E+39), "TBitHelper::floatToBf16 overly large numbers should become infinity");
		self::assertEquals(0x7f80, TBitHelper::floatToBf16(INF), "TBitHelper::floatToBf16 0x7f80 is not converting infinity");
		self::assertEquals(0x8000, TBitHelper::floatToBf16(-0.0), "TBitHelper::floatToBf16 0x8000 is not converting negative zero");
		self::assertEquals(0xC000, TBitHelper::floatToBf16(-2), "TBitHelper::floatToBf16 0xC000 is not converting negative two");
		self::assertEquals(0xFF80, TBitHelper::floatToBf16(-INF), "TBitHelper::floatToBf16 0xFC00 is not converting negative infinity");
		self::assertEquals(0x7FC0, TBitHelper::floatToBf16(NAN), "TBitHelper::floatToBf16 0xFC01 is not converting Not-A-Number (NaN).");
	}
	
	public function testFloatTFp8Range()
	{
		self::assertEquals(0x00, TBitHelper::floatToFp8Range(0), "TBitHelper::floatToFp8Range 0 is not converting to 0x00; float zero is not half float zero");
		self::assertEquals(0x3C, TBitHelper::floatToFp8Range(1), "TBitHelper::floatToFp8Range 1 is not converting to 0x3C; float one is not half float one");
		self::assertEquals(0x81, TBitHelper::floatToFp8Range(-1.52587890625E-5), "TBitHelper::floatToFp8Range 0x81 is not converting smallest sub-normal number");
		self::assertEquals(0x03, TBitHelper::floatToFp8Range(4.57763671875E-5), "TBitHelper::floatToFp8Range 0x03 is not converting largest sub-normal number");
		self::assertEquals(0x04, TBitHelper::floatToFp8Range(6.103515625E-5), "TBitHelper::floatToFp8Range 0x04 is not converting smallest normal number");
		self::assertEquals(0x35, TBitHelper::floatToFp8Range(0.3125), "TBitHelper::floatToFp8Range 0x35 is not converting the nearest to 1/3");
		self::assertEquals(0x3B, TBitHelper::floatToFp8Range(0.875), "TBitHelper::floatToFp8Range 0x3B is not converting the largest less than 1");
		self::assertEquals(0x3D, TBitHelper::floatToFp8Range(1.25), "TBitHelper::floatToFp8Range 0x3D is not converting the smallest more than 1");
		self::assertEquals(0x7B, TBitHelper::floatToFp8Range(57344.0), "TBitHelper::floatToFp8Range 0x7B is not converting the largest normal number");
		self::assertEquals(0x7C, TBitHelper::floatToFp8Range(64000.0), "TBitHelper::floatToFp8Range overly large numbers should become infinity");
		self::assertEquals(0x7C, TBitHelper::floatToFp8Range(INF), "TBitHelper::floatToFp8Range 0x7C is not converting infinity");
		self::assertEquals(0x80, TBitHelper::floatToFp8Range(-0.0), "TBitHelper::floatToFp8Range 0x80 is not converting negative zero");
		self::assertEquals(0xC0, TBitHelper::floatToFp8Range(-2), "TBitHelper::floatToFp8Range 0xC0 is not converting negative two");
		self::assertEquals(0xFC, TBitHelper::floatToFp8Range(-INF), "TBitHelper::floatToFp8Range 0xFC is not converting negative infinity");
		self::assertEquals(0x7E, TBitHelper::floatToFp8Range(NAN), "TBitHelper::floatToFp8Range 0x7F is not converting Not-A-Number (NaN).");
	}
	
	public function testFloatToFp8Precision()
	{
		self::assertEquals(0x00, TBitHelper::floatToFp8Precision(0), "TBitHelper::floatToFp8Precision 0 is not converting to 0x00; float zero is not half float zero");
		self::assertEquals(0x38, TBitHelper::floatToFp8Precision(1), "TBitHelper::floatToFp8Precision 1 is not converting to 0x38; float one is not half float one");
		self::assertEquals(0x81, TBitHelper::floatToFp8Precision(-0.001953125), "TBitHelper::floatToFp8Precision 0x81 is not converting smallest sub-normal number");
		self::assertEquals(0x07, TBitHelper::floatToFp8Precision(0.013671875), "TBitHelper::floatToFp8Precision 0x07 is not converting largest sub-normal number");
		self::assertEquals(0x08, TBitHelper::floatToFp8Precision(0.015625), "TBitHelper::floatToFp8Precision 0x08 is not converting smallest normal number");
		self::assertEquals(0x2B, TBitHelper::floatToFp8Precision(0.34375), "TBitHelper::floatToFp8Precision 0x2B is not converting the nearest to 1/3");
		self::assertEquals(0x37, TBitHelper::floatToFp8Precision(0.9375), "TBitHelper::floatToFp8Precision 0x37 is not converting the largest less than 1");
		self::assertEquals(0x39, TBitHelper::floatToFp8Precision(1.125), "TBitHelper::floatToFp8Precision 0x39 is not converting the smallest more than 1");
		self::assertEquals(0x77, TBitHelper::floatToFp8Precision(240.0), "TBitHelper::floatToFp8Precision 0x77 is not converting the largest normal number");
		self::assertEquals(0x78, TBitHelper::floatToFp8Precision(300.0), "TBitHelper::floatToFp8Precision overly large numbers become infinity");
		self::assertEquals(0x78, TBitHelper::floatToFp8Precision(INF), "TBitHelper::floatToFp8Precision 0x78 is not converting infinity");
		self::assertEquals(0x80, TBitHelper::floatToFp8Precision(-0.0), "TBitHelper::floatToFp8Precision 0x80 is not converting negative zero");
		self::assertEquals(0xC0, TBitHelper::floatToFp8Precision(-2), "TBitHelper::floatToFp8Precision 0xC0 is not converting negative two");
		self::assertEquals(0xF8, TBitHelper::floatToFp8Precision(-INF), "TBitHelper::floatToFp8Precision 0xF8 is not converting negative infinity");
		self::assertEquals(0x7C, TBitHelper::floatToFp8Precision(NAN), "TBitHelper::floatToFp8Precision 0x7F is not converting Not-A-Number (NaN).");
	}
	
	// Convert half and mini bit (float) representations to PHP Float
	public function testFp16ToFloat()
	{
		self::assertEquals(0, TBitHelper::fp16ToFloat(0x0000), "TBitHelper::fp16ToFloat 0x0000 is not converting to 0; half float zero is not float zero");
		self::assertEquals(1, TBitHelper::fp16ToFloat(0x3C00), "TBitHelper::fp16ToFloat 0x3C00 is not converting to 1; half float one is not float one");
		self::assertEquals(-5.960464477539063E-8, TBitHelper::fp16ToFloat(0x8001), "TBitHelper::fp16ToFloat 0x8001 is not converting smallest sub-normal number");
		self::assertEquals(6.097555160522461E-5, TBitHelper::fp16ToFloat(0x03FF), "TBitHelper::fp16ToFloat 0x03FF is not converting largest sub-normal number");
		self::assertEquals(6.103515625E-5, TBitHelper::fp16ToFloat(0x0400), "TBitHelper::fp16ToFloat 0x0400 is not converting smallest normal number");
		self::assertEquals(0.333251953125, TBitHelper::fp16ToFloat(0x3555), "TBitHelper::fp16ToFloat 0x3555 is not converting the nearest to 1/3");
		self::assertEquals(0.99951171875, TBitHelper::fp16ToFloat(0x3bff), "TBitHelper::fp16ToFloat 0x3bff is not converting the largest less than 1");
		self::assertEquals(1.0009765625, TBitHelper::fp16ToFloat(0x3C01), "TBitHelper::fp16ToFloat 0x3C01 is not converting the smallest more than 1");
		self::assertEquals(65504, TBitHelper::fp16ToFloat(0x7bff), "TBitHelper::fp16ToFloat 0x7bff is not converting the largest normal number");
		self::assertEquals(INF, TBitHelper::fp16ToFloat(0x7C00), "TBitHelper::fp16ToFloat 0x7C00 is not converting infinity");
		self::assertTrue(TBitHelper::isNegativeZero(TBitHelper::fp16ToFloat(0x8000)), "TBitHelper::fp16ToFloat 0x8000 is not converting negative zero");
		self::assertEquals(-2, TBitHelper::fp16ToFloat(0xC000), "TBitHelper::fp16ToFloat 0xC000 is not converting negative two");
		self::assertEquals(-INF, TBitHelper::fp16ToFloat(0xFC00), "TBitHelper::fp16ToFloat 0xFC00 is not converting negative infinity");
		self::assertTrue(is_nan(TBitHelper::fp16ToFloat(0xFC01)), "TBitHelper::fp16ToFloat 0xFC01 is not converting Not-A-Number (NaN)");
		self::assertTrue(is_nan(TBitHelper::fp16ToFloat(0x7E00)), "TBitHelper::fp16ToFloat 0x7FFFis not converting Not-A-Number (NaN)");
	}
	
	public function testBf16ToFloat()
	{
		self::assertEquals(0, TBitHelper::bf16ToFloat(0x0000), "TBitHelper::bf16ToFloat 0x0000 is not converting to 0; half float zero is not float zero");
		self::assertEquals(1, TBitHelper::bf16ToFloat(0x3F80), "TBitHelper::bf16ToFloat 0x3F80 is not converting to 1; half float one is not float one");
		self::assertEquals(-9.183549615799121E-41, TBitHelper::bf16ToFloat(0x8001), "TBitHelper::bf16ToFloat 0x8001 is not converting smallest sub-normal number");
		self::assertEquals(1.1663108012064884E-38, TBitHelper::bf16ToFloat(0x007F), "TBitHelper::bf16ToFloat 0x007F is not converting largest sub-normal number");
		self::assertEquals(1.1754943508222875E-38, TBitHelper::bf16ToFloat(0x0080), "TBitHelper::bf16ToFloat 0x0080 is not converting smallest normal number");
		self::assertEquals(0.333984375, TBitHelper::bf16ToFloat(0x3EAB), "TBitHelper::bf16ToFloat 0x3EAB is not converting the nearest to 1/3");
		self::assertEquals(0.99609375, TBitHelper::bf16ToFloat(0x3F7F), "TBitHelper::bf16ToFloat 0x3F7F is not converting the largest less than 1");
		self::assertEquals(1.0078125, TBitHelper::bf16ToFloat(0x3F81), "TBitHelper::bf16ToFloat 0x3F81 is not converting the smallest more than 1");
		self::assertEquals(3.3895313892515355E+38, TBitHelper::bf16ToFloat(0x7f7f), "TBitHelper::bf16ToFloat 0x7f7f is not converting the largest normal number");
		self::assertEquals(INF, TBitHelper::bf16ToFloat(0x7f80), "TBitHelper::bf16ToFloat 0x7f80 is not converting infinity");
		self::assertTrue(TBitHelper::isNegativeZero(TBitHelper::bf16ToFloat(0x8000)), "TBitHelper::bf16ToFloat 0x8000 is not converting negative zero");
		self::assertEquals(-2, TBitHelper::bf16ToFloat(0xC000), "TBitHelper::bf16ToFloat 0xC000 is not converting negative two");
		self::assertEquals(-INF, TBitHelper::bf16ToFloat(0xFF80), "TBitHelper::bf16ToFloat 0xFF80 is not converting negative infinity");
		self::assertTrue(is_nan(TBitHelper::bf16ToFloat(0x7FC0)), "TBitHelper::bf16ToFloat 0xFF81 is not converting Not-A-Number (NaN)");
	}
	
	public function testFp8RangeToFloat()
	{
		self::assertEquals(0, TBitHelper::fp8RangeToFloat(0x00), "TBitHelper::fp8RangeToFloat 0x00 is not converting to 0; half float zero is not float zero");
		self::assertEquals(1, TBitHelper::fp8RangeToFloat(0x3C), "TBitHelper::fp8RangeToFloat 0x3C is not converting to 1; half float one is not float one");
		self::assertEquals(-1.52587890625E-5, TBitHelper::fp8RangeToFloat(0x81), "TBitHelper::fp8RangeToFloat 0x81 is not converting smallest sub-normal number");
		self::assertEquals(4.57763671875E-5, TBitHelper::fp8RangeToFloat(0x03), "TBitHelper::fp8RangeToFloat 0x03 is not converting largest sub-normal number");
		self::assertEquals(6.103515625E-5, TBitHelper::fp8RangeToFloat(0x04), "TBitHelper::fp8RangeToFloat 0x04 is not converting smallest normal number");
		self::assertEquals(0.3125, TBitHelper::fp8RangeToFloat(0x35), "TBitHelper::fp8RangeToFloat 0x35 is not converting the nearest to 1/3");
		self::assertEquals(0.875, TBitHelper::fp8RangeToFloat(0x3B), "TBitHelper::fp8RangeToFloat 0x3B is not converting the largest less than 1");
		self::assertEquals(1.25, TBitHelper::fp8RangeToFloat(0x3D), "TBitHelper::fp8RangeToFloat 0x3D is not converting the smallest more than 1");
		self::assertEquals(57344.0, TBitHelper::fp8RangeToFloat(0x7B), "TBitHelper::fp8RangeToFloat 0x7B is not converting the largest normal number");
		self::assertEquals(INF, TBitHelper::fp8RangeToFloat(0x7C), "TBitHelper::fp8RangeToFloat 0x7C is not converting infinity");
		self::assertTrue(TBitHelper::isNegativeZero(TBitHelper::fp8RangeToFloat(0x80)), "TBitHelper::fp8RangeToFloat 0x80 is not converting negative zero");
		self::assertEquals(-2, TBitHelper::fp8RangeToFloat(0xC0), "TBitHelper::fp8RangeToFloat 0xC0 is not converting negative two");
		self::assertEquals(-INF, TBitHelper::fp8RangeToFloat(0xFC), "TBitHelper::fp8RangeToFloat 0xFC is not converting negative infinity");
		self::assertTrue(is_nan(TBitHelper::fp8RangeToFloat(0x7E)), "TBitHelper::fp8RangeToFloat 0x7F is not converting Not-A-Number (NaN)");
	}
	
	public function testFp8PrecisionToFloat()
	{
		self::assertEquals(0, TBitHelper::fp8PrecisionToFloat(0x00), "TBitHelper::fp8PrecisionToFloat 0x00 is not converting to 0; half float zero is not float zero");
		self::assertEquals(1, TBitHelper::fp8PrecisionToFloat(0x38), "TBitHelper::fp8PrecisionToFloat 0x38 is not converting to 1; half float one is not float one");
		self::assertEquals(-0.001953125, TBitHelper::fp8PrecisionToFloat(0x81), "TBitHelper::fp8PrecisionToFloat 0x81 is not converting smallest sub-normal number");
		self::assertEquals(0.013671875, TBitHelper::fp8PrecisionToFloat(0x07), "TBitHelper::fp8PrecisionToFloat 0x07 is not converting largest sub-normal number");
		self::assertEquals(0.015625, TBitHelper::fp8PrecisionToFloat(0x08), "TBitHelper::fp8PrecisionToFloat 0x08 is not converting smallest normal number");
		self::assertEquals(0.34375, TBitHelper::fp8PrecisionToFloat(0x2B), "TBitHelper::fp8PrecisionToFloat 0x2B is not converting the nearest to 1/3");
		self::assertEquals(0.9375, TBitHelper::fp8PrecisionToFloat(0x37), "TBitHelper::fp8PrecisionToFloat 0x37 is not converting the largest less than 1");
		self::assertEquals(1.125, TBitHelper::fp8PrecisionToFloat(0x39), "TBitHelper::fp8PrecisionToFloat 0x39 is not converting the smallest more than 1");
		self::assertEquals(240.0, TBitHelper::fp8PrecisionToFloat(0x77), "TBitHelper::fp8PrecisionToFloat 0x77 is not converting the largest normal number");
		self::assertEquals(INF, TBitHelper::fp8PrecisionToFloat(0x78), "TBitHelper::fp8PrecisionToFloat 0x78 is not converting infinity");
		self::assertTrue(TBitHelper::isNegativeZero(TBitHelper::fp8PrecisionToFloat(0x80)), "TBitHelper::fp8PrecisionToFloat 0x80 is not converting negative zero");
		self::assertEquals(-2, TBitHelper::fp8PrecisionToFloat(0xC0), "TBitHelper::fp8PrecisionToFloat 0xC0 is not converting negative two");
		self::assertEquals(-INF, TBitHelper::fp8PrecisionToFloat(0xF8), "TBitHelper::fp8PrecisionToFloat 0xF8 is not converting negative infinity");
		self::assertTrue(is_nan(TBitHelper::fp8PrecisionToFloat(0x7C)), "TBitHelper::fp8PrecisionToFloat 0x7F is not converting Not-A-Number (NaN)");
	}
	
	
	public function testMirrorBits()
	{
		self::assertEquals(0, TBitHelper::mirrorBits(3, 0));
		
		$size = PHP_INT_SIZE * 8;
		for ($bits = 1; $bits < $size; $bits++) {
			for ($bit = 0, $n = 1, $ref = 1 << ($bits - 1); $bit < $bits; $bit++) {
				self::assertEquals($ref, $r = TBitHelper::mirrorBits($n, $bits), "$bits bits being reversed: '" . decbin($r) . "' is not the reference value of " . decbin($ref) . "'.");
				$n <<= 1;
				$ref >>= 1;
			}
		}
		self::assertEquals(0, TBitHelper::mirrorBits(3, -1));
	}
	
	public function testMirrorByte()
	{
		$bits = PHP_INT_SIZE * 8;
		for ($bit = 0, $n = 1; $bit < $bits; $bit++) {
			$base = (($bit >> 3) << 3);
			$ref = TBitHelper::mirrorBits($n >> $base, 8) << $base;
			self::assertEquals($ref, $r = TBitHelper::mirrorByte($n), "$bits bits being reversed: '" . decbin($n) . "' => '" . decbin($r) . "' is not the reference value of " . decbin($ref) . "'.");
			$n <<= 1;
		}
	}
	
	public function testMirrorShort()
	{
		$bits = PHP_INT_SIZE * 8;
		for ($bit = 0, $n = 1; $bit < $bits; $bit++) {
			$base = (($bit >> 4) << 4);
			$ref = TBitHelper::mirrorBits($n >> $base, 16) << $base;
			self::assertEquals($ref, $r = TBitHelper::mirrorShort($n), "$bits bits being reversed: '" . decbin($n) . "' => '" . decbin($r) . "' is not the reference value of " . decbin($ref) . "'.");
			$n <<= 1;
		}
	}
	
	public function testMirrorLong()
	{
		$bits = PHP_INT_SIZE * 8;
		for ($bit = 0, $n = 1; $bit < $bits; $bit++) {
			$base = (($bit >> 5) << 5);
			$ref = TBitHelper::mirrorBits($n >> $base, 32) << $base;
			self::assertEquals($ref, $r = TBitHelper::mirrorLong($n), "$bits bits being reversed: '" . decbin($n) . "' => '" . decbin($r) . "' is not the reference value of " . decbin($ref) . "'.");
			$n <<= 1;
		}
	}
	
	public function testMirrorLongLong()
	{
		if (PHP_INT_SIZE === 4) {
			$this->markTestSkipped("Cannot run " . TBitHelper::class . "::mirrorLongLong on 32 bit systems");
		} else {
			$bits = PHP_INT_SIZE * 8;
			for ($bit = 0, $n = 1; $bit < $bits; $bit++) {
				$base = (($bit >> 6) << 6);
				$ref = TBitHelper::mirrorBits($n >> $base, 64) << $base;
				self::assertEquals($ref, $r = TBitHelper::mirrorLongLong($n), "$bit/$bits bits being reversed: '" . decbin($n) . "' => \n'" . decbin($r) . "' is not the reference value of \n'" . decbin($ref) . "'.");
				$n <<= 1;
			}
		}
	}
	
	public function testFlipEndianShort()
	{
		$bits = 16;
		for ($bit = 0, $n = 1; $bit < $bits; $bit++) {
			$d = pack('n', $n);
			$c = $d[0];
			$d[0] = $d[1];
			$d[1] = $c;
			$ref = unpack('n', $d)[1];
			self::assertEquals($ref, $r = TBitHelper::flipEndianShort($n), "flip endian short: '" . decbin($n) . "' => '" . decbin($r) . "' is not the reference value of " . decbin($ref) . "'.");
			$n <<= 1;
		}
	}
	
	public function testFlipEndianLong()
	{
		$bits = 32;
		for ($bit = 0, $n = 1; $bit < $bits; $bit++) {
			$d = pack('N', $n);
			$c = $d[0];
			$d[0] = $d[3];
			$d[3] = $c;
			$c = $d[1];
			$d[1] = $d[2];
			$d[2] = $c;
			$ref = unpack('N', $d)[1];
			self::assertEquals($ref, $r = TBitHelper::flipEndianLong($n), "flip endian long: '" . decbin($n) . "' => '" . decbin($r) . "' is not the reference value of " . decbin($ref) . "'.");
			$n <<= 1;
		}
	}
	
	public function testFlipEndianLongLong()
	{
		if (PHP_INT_SIZE === 4) {
			$this->markTestSkipped("Cannot run " . TBitHelper::class . "::flipEndianLongLong on 32 bit systems");
		} else {
			$bits = 64;
			for ($bit = 0, $n = 1; $bit < $bits; $bit++) {
				$d = pack('Q', $n);
				$c = $d[0];
				$d[0] = $d[7];
				$d[7] = $c;
				
				$c = $d[1];
				$d[1] = $d[6];
				$d[6] = $c;
				
				$c = $d[2];
				$d[2] = $d[5];
				$d[5] = $c;
				
				$c = $d[3];
				$d[3] = $d[4];
				$d[4] = $c;
				$ref = unpack('Q', $d)[1];
				self::assertEquals($ref, $r = TBitHelper::flipEndianLongLong($n), "flip endian long long: '" . decbin($n) . "' => '" . decbin($r) . "' is not the reference value of " . decbin($ref) . "'.");
				$n <<= 1;
			}
		}
	}
	
	
}
