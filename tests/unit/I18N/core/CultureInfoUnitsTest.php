<?php

/**
 * Test class for CultureInfoUnits.
 */
class CultureInfoUnitsTest extends PHPUnit\Framework\TestCase
{
    public function testConstants()
    {
        // Test display name constant
        $this->assertEquals('dnam', Prado\I18N\core\CultureInfoUnits::UNIT_DISPLAY_NAME);
        
        // Test unit pattern constants
        $this->assertEquals('one', Prado\I18N\core\CultureInfoUnits::UNIT_ONE_PATTERN);
        $this->assertEquals('other', Prado\I18N\core\CultureInfoUnits::UNIT_OTHER_PATTERN);
        
        // Test per unit pattern constant
        $this->assertEquals('per', Prado\I18N\core\CultureInfoUnits::UNIT_PER_UNIT_PATTERN);
    }
    
    public function testDigitalUnitTypes()
    {
        // Test bit types
        $this->assertEquals('digital-bit', Prado\I18N\core\CultureInfoUnits::TYPE_DIGITAL_BIT);
        $this->assertEquals('digital-byte', Prado\I18N\core\CultureInfoUnits::TYPE_DIGITAL_BYTE);
        
        /*
        $this->assertEquals('digital-kibibit', Prado\I18N\core\CultureInfoUnits::TYPE_DIGITAL_KIBIBIT);
        $this->assertEquals('digital-mebibit', Prado\I18N\core\CultureInfoUnits::TYPE_DIGITAL_MEBIBIT);
        $this->assertEquals('digital-gibibit', Prado\I18N\core\CultureInfoUnits::TYPE_DIGITAL_GIBIBIT);
        $this->assertEquals('digital-tebibit', Prado\I18N\core\CultureInfoUnits::TYPE_DIGITAL_TEBIBIT);
        $this->assertEquals('digital-pebibit', Prado\I18N\core\CultureInfoUnits::TYPE_DIGITAL_PEBIBIT);
        $this->assertEquals('digital-exbibit', Prado\I18N\core\CultureInfoUnits::TYPE_DIGITAL_EXBIBIT);
        $this->assertEquals('digital-zebibit', Prado\I18N\core\CultureInfoUnits::TYPE_DIGITAL_ZEBIBIT);
        $this->assertEquals('digital-yobibit', Prado\I18N\core\CultureInfoUnits::TYPE_DIGITAL_YOBIBIT);
        $this->assertEquals('digital-robibit', Prado\I18N\core\CultureInfoUnits::TYPE_DIGITAL_ROBIBIT);
        $this->assertEquals('digital-quebibit', Prado\I18N\core\CultureInfoUnits::TYPE_DIGITAL_QUEBIBIT);
        
        // Test byte types
        $this->assertEquals('digital-kibibyte', Prado\I18N\core\CultureInfoUnits::TYPE_DIGITAL_KIBIBYTE);
        $this->assertEquals('digital-mebibyte', Prado\I18N\core\CultureInfoUnits::TYPE_DIGITAL_MEBIBYTE);
        $this->assertEquals('digital-gibibyte', Prado\I18N\core\CultureInfoUnits::TYPE_DIGITAL_GIBIBYTE);
        $this->assertEquals('digital-tebibyte', Prado\I18N\core\CultureInfoUnits::TYPE_DIGITAL_TEBIBYTE);
        $this->assertEquals('digital-pebibyte', Prado\I18N\core\CultureInfoUnits::TYPE_DIGITAL_PEBIBYTE);
        $this->assertEquals('digital-exbibyte', Prado\I18N\core\CultureInfoUnits::TYPE_DIGITAL_EXBIBYTE);
        $this->assertEquals('digital-zebibyte', Prado\I18N\core\CultureInfoUnits::TYPE_DIGITAL_ZEBIBYTE);
        $this->assertEquals('digital-yobibyte', Prado\I18N\core\CultureInfoUnits::TYPE_DIGITAL_YOBIBYTE);
        $this->assertEquals('digital-robibyte', Prado\I18N\core\CultureInfoUnits::TYPE_DIGITAL_ROBIBYTE);
        $this->assertEquals('digital-quebibyte', Prado\I18N\core\CultureInfoUnits::TYPE_DIGITAL_QUEBIBYTE);
        */
        
        // Test marketing term bit types
        $this->assertEquals('digital-kilobit', Prado\I18N\core\CultureInfoUnits::TYPE_DIGITAL_KILOBIT);
        $this->assertEquals('digital-megabit', Prado\I18N\core\CultureInfoUnits::TYPE_DIGITAL_MEGABIT);
        $this->assertEquals('digital-gigabit', Prado\I18N\core\CultureInfoUnits::TYPE_DIGITAL_GIGABIT);
        $this->assertEquals('digital-terabit', Prado\I18N\core\CultureInfoUnits::TYPE_DIGITAL_TERABIT);
        $this->assertEquals('digital-petabit', Prado\I18N\core\CultureInfoUnits::TYPE_DIGITAL_PETABIT);
        $this->assertEquals('digital-exabit', Prado\I18N\core\CultureInfoUnits::TYPE_DIGITAL_EXABIT);
        $this->assertEquals('digital-zettabit', Prado\I18N\core\CultureInfoUnits::TYPE_DIGITAL_ZETTABIT);
        $this->assertEquals('digital-yottabit', Prado\I18N\core\CultureInfoUnits::TYPE_DIGITAL_YOTTABIT);
        $this->assertEquals('digital-ronnabit', Prado\I18N\core\CultureInfoUnits::TYPE_DIGITAL_RONNABIT);
        $this->assertEquals('digital-quettabit', Prado\I18N\core\CultureInfoUnits::TYPE_DIGITAL_QUETTABIT);
        
        // Test marketing term byte types
        $this->assertEquals('digital-kilobyte', Prado\I18N\core\CultureInfoUnits::TYPE_DIGITAL_KILOBYTE);
        $this->assertEquals('digital-megabyte', Prado\I18N\core\CultureInfoUnits::TYPE_DIGITAL_MEGABYTE);
        $this->assertEquals('digital-gigabyte', Prado\I18N\core\CultureInfoUnits::TYPE_DIGITAL_GIGABYTE);
        $this->assertEquals('digital-terabyte', Prado\I18N\core\CultureInfoUnits::TYPE_DIGITAL_TERABYTE);
        $this->assertEquals('digital-petabyte', Prado\I18N\core\CultureInfoUnits::TYPE_DIGITAL_PETABYTE);
        $this->assertEquals('digital-exabyte', Prado\I18N\core\CultureInfoUnits::TYPE_DIGITAL_EXABYTE);
        $this->assertEquals('digital-zettabyte', Prado\I18N\core\CultureInfoUnits::TYPE_DIGITAL_ZETTABYTE);
        $this->assertEquals('digital-yottabyte', Prado\I18N\core\CultureInfoUnits::TYPE_DIGITAL_YOTTABYTE);
        $this->assertEquals('digital-ronnabyte', Prado\I18N\core\CultureInfoUnits::TYPE_DIGITAL_RONNABYTE);
        $this->assertEquals('digital-quettabyte', Prado\I18N\core\CultureInfoUnits::TYPE_DIGITAL_QUETTABYTE);
    }
    
    public function testNonDigitalUnitTypes()
    {
        // Test length units
        $this->assertEquals('length-meter', Prado\I18N\core\CultureInfoUnits::TYPE_LENGTH_METER);
        $this->assertEquals('length-kilometer', Prado\I18N\core\CultureInfoUnits::TYPE_LENGTH_KILOMETER);
        $this->assertEquals('length-foot', Prado\I18N\core\CultureInfoUnits::TYPE_LENGTH_FOOT);
        $this->assertEquals('length-inch', Prado\I18N\core\CultureInfoUnits::TYPE_LENGTH_INCH);
        
        // Test mass units
        $this->assertEquals('mass-gram', Prado\I18N\core\CultureInfoUnits::TYPE_MASS_GRAM);
        $this->assertEquals('mass-kilogram', Prado\I18N\core\CultureInfoUnits::TYPE_MASS_KILOGRAM);
        $this->assertEquals('mass-pound', Prado\I18N\core\CultureInfoUnits::TYPE_MASS_POUND);
        
        // Test duration units
        $this->assertEquals('duration-second', Prado\I18N\core\CultureInfoUnits::TYPE_DURATION_SECOND);
        $this->assertEquals('duration-minute', Prado\I18N\core\CultureInfoUnits::TYPE_DURATION_MINUTE);
        $this->assertEquals('duration-hour', Prado\I18N\core\CultureInfoUnits::TYPE_DURATION_HOUR);
        $this->assertEquals('duration-day', Prado\I18N\core\CultureInfoUnits::TYPE_DURATION_DAY);
        $this->assertEquals('duration-nanosecond', Prado\I18N\core\CultureInfoUnits::TYPE_DURATION_NANOSECOND);
        $this->assertEquals('duration-microsecond', Prado\I18N\core\CultureInfoUnits::TYPE_DURATION_MICROSECOND);
        $this->assertEquals('duration-millisecond', Prado\I18N\core\CultureInfoUnits::TYPE_DURATION_MILLISECOND);
        $this->assertEquals('duration-week', Prado\I18N\core\CultureInfoUnits::TYPE_DURATION_WEEK);
        $this->assertEquals('duration-month', Prado\I18N\core\CultureInfoUnits::TYPE_DURATION_MONTH);
        $this->assertEquals('duration-quarter', Prado\I18N\core\CultureInfoUnits::TYPE_DURATION_QUARTER);
        $this->assertEquals('duration-year', Prado\I18N\core\CultureInfoUnits::TYPE_DURATION_YEAR);
        $this->assertEquals('duration-decade', Prado\I18N\core\CultureInfoUnits::TYPE_DURATION_DECADE);
        $this->assertEquals('duration-century', Prado\I18N\core\CultureInfoUnits::TYPE_DURATION_CENTURY);
        
        // Test volume units
        $this->assertEquals('volume-liter', Prado\I18N\core\CultureInfoUnits::TYPE_VOLUME_LITER);
        $this->assertEquals('volume-gallon', Prado\I18N\core\CultureInfoUnits::TYPE_VOLUME_GALLON);
        
        // Test speed units
        $this->assertEquals('speed-kilometer-per-hour', Prado\I18N\core\CultureInfoUnits::TYPE_SPEED_KM_H);
        $this->assertEquals('speed-mile-per-hour', Prado\I18N\core\CultureInfoUnits::TYPE_SPEED_MPH);
        
        // Test temperature units
        $this->assertEquals('temperature-celsius', Prado\I18N\core\CultureInfoUnits::TYPE_TEMPERATURE_CELSIUS);
        $this->assertEquals('temperature-fahrenheit', Prado\I18N\core\CultureInfoUnits::TYPE_TEMPERATURE_FAHRENHEIT);
        
        // Test concentration units
        $this->assertEquals('concentr-permillion', Prado\I18N\core\CultureInfoUnits::TYPE_CONCENTRATION_PERMILLION);
        $this->assertEquals('concentr-milligram-per-deciliter', Prado\I18N\core\CultureInfoUnits::TYPE_CONCENTRATION_MILLIGRAM_PER_DECILITER);
        
        // Test electric units
        $this->assertEquals('electric-ampere', Prado\I18N\core\CultureInfoUnits::TYPE_ELECTRIC_AMPERE);
        $this->assertEquals('electric-volt', Prado\I18N\core\CultureInfoUnits::TYPE_ELECTRIC_VOLT);
        $this->assertEquals('electric-ohm', Prado\I18N\core\CultureInfoUnits::TYPE_ELECTRIC_OHM);
        
        // Test energy units
        $this->assertEquals('energy-joule', Prado\I18N\core\CultureInfoUnits::TYPE_ENERGY_JOULE);
        $this->assertEquals('energy-kilowatt-hour', Prado\I18N\core\CultureInfoUnits::TYPE_ENERGY_KILOWATT_HOUR);
        
        // Test force units
        $this->assertEquals('force-newton', Prado\I18N\core\CultureInfoUnits::TYPE_FORCE_NEWTON);
        
        // Test graphics units
        $this->assertEquals('graphics-dot-per-inch', Prado\I18N\core\CultureInfoUnits::TYPE_GRAPHICS_DPI);
        $this->assertEquals('graphics-pixel', Prado\I18N\core\CultureInfoUnits::TYPE_GRAPHICS_PIXEL);
        
        // Test light units
        $this->assertEquals('light-lux', Prado\I18N\core\CultureInfoUnits::TYPE_LIGHT_LUX);
        
        // Test pressure units
        $this->assertEquals('pressure-hectopascal', Prado\I18N\core\CultureInfoUnits::TYPE_PRESSURE_HECTOPASCAL);
        $this->assertEquals('pressure-bar', Prado\I18N\core\CultureInfoUnits::TYPE_PRESSURE_BAR);
        
        // Test torque units
        $this->assertEquals('torque-newton-meter', Prado\I18N\core\CultureInfoUnits::TYPE_TORQUE_NEWTON_METER);
    }
}