<?php

require_once(dirname(__FILE__).'/../common.php');
require_once(dirname(__FILE__).'/CacheTestCase.php');
Prado::using('System.Caching.TAPCCache');

class utAPCCache extends CacheTestCase
{

   public function testInit()
   {
      if(!extension_loaded('apc'))
      {
         $this->fail('TAPCCache is not tested. PHP extension "apc" is required by TAPCCache.');
         return;
      }
      //compatibility test as this time of writing 01/02/2006 (dd//mm//yyyy)
      $apc_default_ini = $apc_default=ini_get('apc.cache_by_default');
      if ($apc_default=='Off' || $apc_default='off')
      	$apc_default=0;
      elseif($apc_default=='On' || $apc_default='on')
      	$apc_default=1;
      $apc_default=(boolean)$apc_default;
      if($apc_default) {
      	$this->fail('You have to disable apc.cache_by_default in your php.ini : you have apc.cache_by_default='.$apc_default_ini.' but currently prado won\'t execute without errors with APC caching all prado php files.');
         return;
      }
   }

   public function testBasicOperations()
   {
      if(!extension_loaded('apc'))
      {
         $this->fail('TAPCCache is not tested. PHP extension "apc" is required by TAPCCache.');
         return;
      }
      $cache=new TAPCCache;
      $cache->init(null);
      $this->setCache($cache);
      $this->basicOperations();
      $this->setCache(null);
   }
}

?>