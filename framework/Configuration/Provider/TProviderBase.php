<?php
/**
 * TProviderBase class.
 * Provides a base implementation for the extensible provider model.
 *
 * @author Jason Ragsdale <jrags@jasrags.net>
 * @version $Id: TProviderBase.php 1398 2006-09-08 19:31:03Z xue $
 * @package System.Configuration.Provider
 * @since 3.1
 */
abstract class TProviderBase extends TModule  
{
	private $_description;
	private $_initialized = false;
	private $_name;
	private $_applicationName;
	private $_enabled=false;

	public function __construct(){}

	public function getDescription()
	{
		return $this->_description;
	}
	public function setDescription($value)
	{
		$this->_description = TPropertyValue::ensureString($value);
	}
	public function getName()
	{
		return $this->_name;
	}
	public function getApplicationName()
	{
		return $this->_applicationName;
	}
	public function setApplicationName($value)
	{
		$this->_applicationName = TPropertyValue::ensureString($value);
	}
	public function getEnabled()
	{
		return $this->_enabled;
	}
	public function setEnabled($value)
	{
		$this->_enabled = TPropertyValue::ensureBoolean($value);
	}
	public function initialize($name,$config)
	{
		if ($this->_initialized)
		{
			throw new TProviderException('Provider_Already_Initialized');
		}
		$this->_initialized=true;

		if ($name === null)
		{
			throw new TProviderException('name');
		}

		if (strlen($name) == 0)
		{
			throw new TProviderException('Config_provider_name_null_or_empty');
		}

		$this->_name = TPropertyValue::ensureString($name);

		if ($config !== null && is_array($config))
		{
			$this->_description = TPropertyValue::ensureString($config['description']);
			unset($config['description']);
		}
	}
	/**
	 * Generates a Universally Unique IDentifier, version 4.
	 * 
	 * RFC 4122 (http://www.ietf.org/rfc/rfc4122.txt) defines a special type of Globally
	 * Unique IDentifiers (GUID), as well as several methods for producing them. One
	 * such method, described in section 4.4, is based on truly random or pseudo-random
	 * number generators, and is therefore implementable in a language like PHP.
	 * 
	 * We choose to produce pseudo-random numbers with the Mersenne Twister, and to always
	 * limit single generated numbers to 16 bits (ie. the decimal value 65535). That is
	 * because, even on 32-bit systems, PHP's RAND_MAX will often be the maximum *signed*
	 * value, with only the equivalent of 31 significant bits. Producing two 16-bit random
	 * numbers to make up a 32-bit one is less efficient, but guarantees that all 32 bits
	 * are random.
	 * 
	 * The algorithm for version 4 UUIDs (ie. those based on random number generators)
	 * states that all 128 bits separated into the various fields (32 bits, 16 bits, 16 bits,
	 * 8 bits and 8 bits, 48 bits) should be random, except : (a) the version number should
	 * be the last 4 bits in the 3rd field, and (b) bits 6 and 7 of the 4th field should
	 * be 01. We try to conform to that definition as efficiently as possible, generating
	 * smaller values where possible, and minimizing the number of base conversions.
	 * 
	 * @copyright  Copyright (c) CFD Labs, 2006. This function may be used freely for
	 *              any purpose ; it is distributed without any form of warranty whatsoever.
	 * @author      David Holmes <dholmes@cfdsoftware.net>
	 * 
	 * @return  string  A UUID, made up of 32 hex digits and 4 hyphens.
	 */
	public function generateUuid()
	{
   		// The field names refer to RFC 4122 section 4.1.2
	   return sprintf('%04x%04x-%04x-%03x4-%04x-%04x%04x%04x',
    	   mt_rand(0, 65535), mt_rand(0, 65535), // 32 bits for "time_low"
    	   mt_rand(0, 65535), // 16 bits for "time_mid"
    	   mt_rand(0, 4095),  // 12 bits before the 0100 of (version) 4 for "time_hi_and_version"
    	   bindec(substr_replace(sprintf('%016b', mt_rand(0, 65535)), '01', 6, 2)),
    	       // 8 bits, the last two of which (positions 6 and 7) are 01, for "clk_seq_hi_res"
    	       // (hence, the 2nd hex digit after the 3rd hyphen can only be 1, 5, 9 or d)
    	       // 8 bits for "clk_seq_low"
    	   mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535) // 48 bits for "node" 
   		); 
	}
}
?>