<?php

class PradoUnit {

	public static function skipDatabaseTests()
	{
		return getenv('PRADO_UNITTEST_SKIP_DB');
	}

}