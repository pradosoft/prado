<?php

/**
 * CultureInfoUnits class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\I18N\core;

/**
 * CultureInfoUnits class.
 *
 * Provides constants for culture-specific unit definitions following
 * the Unicode CLDR format for consistent unit handling across cultures.
 *
 * This is not comprehensive.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.3.3
 */
class CultureInfoUnits
{
	/**
	 * Unit display name - the display name of the @[style][type] unit
	 * @var string
	 */
	public const UNIT_DISPLAY_NAME = 'dnam';

	/**
	 * Unit Pattern for one unit - this is the singular format.
	 * @var string
	 */
	public const UNIT_ONE_PATTERN = 'one';

	/**
	 * Unit Pattern for other than one unit - this is the multiple format.
	 * @var string
	 */
	public const UNIT_OTHER_PATTERN = 'other';

	/**
	 * Per Unit Pattern - the display name of the @[style][type] unit
	 * @var string
	 */
	public const UNIT_PER_UNIT_PATTERN = 'per';


	// --- Digital Unit TYPES ---

	/**
	 * Digital unit - bits
	 * @var string
	 */
	public const TYPE_DIGITAL_BIT = 'digital-bit';

	/**
	 * Digital unit - bytes
	 * @var string
	 */
	public const TYPE_DIGITAL_BYTE = 'digital-byte';


	// Binary - 2^n
	//	Bits

	/**
	 * Digital unit - kibibits - 2^10 bits
	 * @var string
	 */
	//public const TYPE_DIGITAL_KIBIBIT = 'digital-kibibit';

	/**
	 * Digital unit - mebibits - 2^20 bits
	 * @var string
	 */
	//public const TYPE_DIGITAL_MEBIBIT = 'digital-mebibit';

	/**
	 * Digital unit - gibibits - 2^30 bits
	 * @var string
	 */
	//public const TYPE_DIGITAL_GIBIBIT = 'digital-gibibit';

	/**
	 * Digital unit - tebibits - 2^40 bits
	 * @var string
	 */
	//public const TYPE_DIGITAL_TEBIBIT = 'digital-tebibit';

	/**
	 * Digital unit - pebibits  - 2^50 bits
	 * @var string
	 */
	//public const TYPE_DIGITAL_PEBIBYTE = 'digital-pebibit';

	/**
	 * Digital unit - exbibits - 2^60 bits
	 * @var string
	 */
	//public const TYPE_DIGITAL_EXBIBIT = 'digital-exbibit';

	/**
	 * Bleeding edge ICU Units - zebibits  - 2^70 bits
	 * @var string
	 */
	//public const TYPE_DIGITAL_ZEBIBIT = 'digital-zebibit';

	/**
	 * Bleeding edge ICU Units - yobibits - 2^80 bits
	 * @var string
	 */
	//public const TYPE_DIGITAL_YOBIBIT = 'digital-yobibit';

	/**
	 * Experimental ICU Units - robibits (ending) - 2^90 bits
	 * This is proposed for updated ICU.
	 * @var string
	 */
	//public const TYPE_DIGITAL_ROBIBIT = 'digital-robibit';

	/**
	 * Experimental ICU Units - quebibits (pending) - 2^100 bits
	 * This is proposed for updated ICU.
	 * @var string
	 */
	//public const TYPE_DIGITAL_QUEBIBIT = 'digital-quebibit';


	//	Bytes

	/**
	 * Digital unit - kibibytes - 2^10 bytes
	 * @var string
	 */
	//public const TYPE_DIGITAL_KIBIBYTE = 'digital-kibibyte';

	/**
	 * Digital unit - mebibytes - 2^20 bytes
	 * @var string
	 */
	//public const TYPE_DIGITAL_MEBIBYTE = 'digital-mebibyte';

	/**
	 * Digital unit - gibibytes - 2^30 bytes
	 * @var string
	 */
	//public const TYPE_DIGITAL_GIBIBYTE = 'digital-gibibyte';

	/**
	 * Digital unit - tebibytes - 2^40 bytes
	 * @var string
	 */
	//public const TYPE_DIGITAL_TEBIBYTE = 'digital-tebibyte';

	/**
	 * Digital unit - pebibytes - 2^50 bytes
	 * @var string
	 */
	//public const TYPE_DIGITAL_PEBIBYTE = 'digital-pebibyte';

	/**
	 * Digital unit - exbibytes - 2^60 bytes
	 * @var string
	 */
	//public const TYPE_DIGITAL_EXBIBYTE = 'digital-exbibyte';

	/**
	 * Bleeding edge ICU Units - zebibytes - 2^70 bytes
	 * @var string
	 */
	//public const TYPE_DIGITAL_ZEBIBYTE = 'digital-zebibyte';

	/**
	 * Bleeding edge ICU Units - yobibytes - 2^80 bytes
	 * @var string
	 */
	//public const TYPE_DIGITAL_YOBIBYTE = 'digital-yobibyte';

	/**
	 * Experimental ICU Units - robibytes (pending) - 2^90 bytes
	 * This is proposed for updated ICU.
	 * @var string
	 */
	//public const TYPE_DIGITAL_ROBIBYTE = 'digital-robibyte';

	/**
	 * Experimental ICU Units - quebibytes (pending) - 2^100 bytes
	 * This is proposed for updated ICU.
	 * @var string
	 */
	//public const TYPE_DIGITAL_QUEBIBYTE = 'digital-quebibyte';



	// Marketing Terms - 10^n
	//	Bits

	/**
	 * Digital unit - kilobits - 10^3 bits
	 * @var string
	 */
	public const TYPE_DIGITAL_KILOBIT = 'digital-kilobit';

	/**
	 * Digital unit - megabits - 10^6 bits
	 * @var string
	 */
	public const TYPE_DIGITAL_MEGABIT = 'digital-megabit';

	/**
	 * Digital unit - gigabits - 10^9 bits
	 * @var string
	 */
	public const TYPE_DIGITAL_GIGABIT = 'digital-gigabit';

	/**
	 * Digital unit - terabits - 10^12 bits
	 * @var string
	 */
	public const TYPE_DIGITAL_TERABIT = 'digital-terabit';

	/**
	 * Digital unit - petabits - 10^15 bits
	 * @var string
	 */
	public const TYPE_DIGITAL_PETABIT = 'digital-petabit';

	/**
	 * Future Digital unit - exabits - 10^18 bits
	 * @var string
	 */
	public const TYPE_DIGITAL_EXABIT = 'digital-exabit';

	/**
	 * Future Digital unit - zettabits - 10^21 bits
	 * @var string
	 */
	public const TYPE_DIGITAL_ZETTABIT = 'digital-zettabit';

	/**
	 * Future Digital unit - yottabits - 10^24 bits
	 * @var string
	 */
	public const TYPE_DIGITAL_YOTTABIT = 'digital-yottabit';

	/**
	 * Future Digital unit - ronnabits (pending) - 10^27 bits
	 * @var string
	 */
	public const TYPE_DIGITAL_RONNABIT = 'digital-ronnabit';

	/**
	 * Future Digital unit - quettabits (pending) - 10^30 bits
	 * @var string
	 */
	public const TYPE_DIGITAL_QUETTABIT = 'digital-quettabit';




	/**
	 * Digital unit - kilobytes - 10^3 bytes
	 * @var string
	 */
	public const TYPE_DIGITAL_KILOBYTE = 'digital-kilobyte';

	/**
	 * Digital unit - megabytes - 10^6 bytes
	 * @var string
	 */
	public const TYPE_DIGITAL_MEGABYTE = 'digital-megabyte';

	/**
	 * Digital unit - gigabytes - 10^9 bytes
	 * @var string
	 */
	public const TYPE_DIGITAL_GIGABYTE = 'digital-gigabyte';

	/**
	 * Digital unit - terabytes - 10^12 bytes
	 * @var string
	 */
	public const TYPE_DIGITAL_TERABYTE = 'digital-terabyte';

	/**
	 * Digital unit - petabytes - 10^15 bytes
	 * @var string
	 */
	public const TYPE_DIGITAL_PETABYTE = 'digital-petabyte';

	/**
	 * Future Digital unit - exabytes - 10^18 bytes
	 * @var string
	 */
	public const TYPE_DIGITAL_EXABYTE = 'digital-exabyte';

	/**
	 * Future Digital unit - zettabytes - 10^21 bytes
	 * @var string
	 */
	public const TYPE_DIGITAL_ZETTABYTE = 'digital-zettabyte';

	/**
	 * Future Digital unit - yottabytes - 10^24 bytes
	 * @var string
	 */
	public const TYPE_DIGITAL_YOTTABYTE = 'digital-yottabyte';

	/**
	 * Future Digital unit - ronnabytes - 10^27 bytes
	 * @var string
	 */
	public const TYPE_DIGITAL_RONNABYTE = 'digital-ronnabyte';

	/**
	 * Future Digital unit - quettabytes - 10^30 bytes
	 * @var string
	 */
	public const TYPE_DIGITAL_QUETTABYTE = 'digital-quettabyte';



	// Non-Digital Units
	//	Time
	/**
	 * Duration unit - nanosecond
	 * @var string
	 */
	public const TYPE_DURATION_NANOSECOND = 'duration-nanosecond';

	/**
	 * Duration unit - microsecond
	 * @var string
	 */
	public const TYPE_DURATION_MICROSECOND = 'duration-microsecond';

	/**
	 * Duration unit - millisecond
	 * @var string
	 */
	public const TYPE_DURATION_MILLISECOND = 'duration-millisecond';

	/**
	 * Duration unit - second
	 * @var string
	 */
	public const TYPE_DURATION_SECOND = 'duration-second';

	/**
	 * Duration unit - minute
	 * @var string
	 */
	public const TYPE_DURATION_MINUTE = 'duration-minute';

	/**
	 * Duration unit - hour
	 * @var string
	 */
	public const TYPE_DURATION_HOUR = 'duration-hour';


	//	Calendar

	/**
	 * Duration unit - day
	 * @var string
	 */
	public const TYPE_DURATION_DAY = 'duration-day';

	/**
	 * Duration unit - week
	 * @var string
	 */
	public const TYPE_DURATION_WEEK = 'duration-week';

	/**
	 * Duration unit - month
	 * @var string
	 */
	public const TYPE_DURATION_MONTH = 'duration-month';

	/**
	 * Duration unit - quarter
	 * @var string
	 */
	public const TYPE_DURATION_QUARTER = 'duration-quarter';

	/**
	 * Duration unit - year
	 * @var string
	 */
	public const TYPE_DURATION_YEAR = 'duration-year';

	/**
	 * Duration unit - decade
	 * @var string
	 */
	public const TYPE_DURATION_DECADE = 'duration-decade';

	/**
	 * Duration unit - century
	 * @var string
	 */
	public const TYPE_DURATION_CENTURY = 'duration-century';



	/**
	 * Length unit - meter
	 * @var string
	 */
	public const TYPE_LENGTH_METER = 'length-meter';

	/**
	 * Length unit - kilometer
	 * @var string
	 */
	public const TYPE_LENGTH_KILOMETER = 'length-kilometer';

	/**
	 * Length unit - foot
	 * @var string
	 */
	public const TYPE_LENGTH_FOOT = 'length-foot';

	/**
	 * Length unit - inch
	 * @var string
	 */
	public const TYPE_LENGTH_INCH = 'length-inch';

	/**
	 * Mass unit - gram
	 * @var string
	 */
	public const TYPE_MASS_GRAM = 'mass-gram';

	/**
	 * Mass unit - kilogram
	 * @var string
	 */
	public const TYPE_MASS_KILOGRAM = 'mass-kilogram';

	/**
	 * Mass unit - pound
	 * @var string
	 */
	public const TYPE_MASS_POUND = 'mass-pound';



	/**
	 * Volume unit - liter
	 * @var string
	 */
	public const TYPE_VOLUME_LITER = 'volume-liter';

	/**
	 * Volume unit - gallon
	 * @var string
	 */
	public const TYPE_VOLUME_GALLON = 'volume-gallon';

	/**
	 * Speed unit - km/h
	 * @var string
	 */
	public const TYPE_SPEED_KM_H = 'speed-kilometer-per-hour';

	/**
	 * Speed unit - mph
	 * @var string
	 */
	public const TYPE_SPEED_MPH = 'speed-mile-per-hour';

	/**
	 * Temperature unit - celsius
	 * @var string
	 */
	public const TYPE_TEMPERATURE_CELSIUS = 'temperature-celsius';

	/**
	 * Temperature unit - fahrenheit
	 * @var string
	 */
	public const TYPE_TEMPERATURE_FAHRENHEIT = 'temperature-fahrenheit';

	/**
	 * Concentration unit - per million
	 * @var string
	 */
	public const TYPE_CONCENTRATION_PERMILLION = 'concentr-permillion';

	/**
	 * Concentration unit - milligram per deciliter
	 * @var string
	 */
	public const TYPE_CONCENTRATION_MILLIGRAM_PER_DECILITER = 'concentr-milligram-per-deciliter';

	/**
	 * Electric unit - ampere
	 * @var string
	 */
	public const TYPE_ELECTRIC_AMPERE = 'electric-ampere';

	/**
	 * Electric unit - volt
	 * @var string
	 */
	public const TYPE_ELECTRIC_VOLT = 'electric-volt';

	/**
	 * Electric unit - ohm
	 * @var string
	 */
	public const TYPE_ELECTRIC_OHM = 'electric-ohm';

	/**
	 * Energy unit - joule
	 * @var string
	 */
	public const TYPE_ENERGY_JOULE = 'energy-joule';

	/**
	 * Energy unit - kilowatt-hour
	 * @var string
	 */
	public const TYPE_ENERGY_KILOWATT_HOUR = 'energy-kilowatt-hour';

	/**
	 * Force unit - newton
	 * @var string
	 */
	public const TYPE_FORCE_NEWTON = 'force-newton';

	/**
	 * Graphics unit - dot per inch
	 * @var string
	 */
	public const TYPE_GRAPHICS_DPI = 'graphics-dot-per-inch';

	/**
	 * Graphics unit - pixel
	 * @var string
	 */
	public const TYPE_GRAPHICS_PIXEL = 'graphics-pixel';

	/**
	 * Light unit - lux
	 * @var string
	 */
	public const TYPE_LIGHT_LUX = 'light-lux';

	/**
	 * Pressure unit - hectopascal
	 * @var string
	 */
	public const TYPE_PRESSURE_HECTOPASCAL = 'pressure-hectopascal';

	/**
	 * Pressure unit - bar
	 * @var string
	 */
	public const TYPE_PRESSURE_BAR = 'pressure-bar';

	/**
	 * Torque unit - newton-meter
	 * @var string
	 */
	public const TYPE_TORQUE_NEWTON_METER = 'torque-newton-meter';
}
