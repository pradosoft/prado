<?php

require_once(PRADO_DIR.'/Web/Javascripts/TJavascriptSerializer.php');

class testSerializerObject
{
    public $public = 'public data';
    protected $protected = 'protected data';
    private $private = 'private !';
}

class testComponentObject extends TComponent
{
    public $public = 'public data';
    protected $protected = 'protected data';
    private $private = 'private !';
    
    public function getData()
    {
        return "component data";
    }
}

class utJavascriptSerializer extends UnitTestCase
{
    function testString()
    {
        $string = "Stan's world!";
        $expect = "'Stan\\'s world!'";
        $js = new TJavascriptSerializer($string);
        $this->assertEqual($expect, $js->toJavascript());
        
        $string = "";
        $expect = "''";
        $js = new TJavascriptSerializer($string);
        $this->assertEqual($expect, $js->toJavascript());
    }
    
    function testInteger()
    {
        $int = 10;
        $expect = "10";
        $js = new TJavascriptSerializer($int);
        $this->assertEqual($expect, $js->toJavascript());
    }
    
    function testFloat()
    {
        $float = 10.2;
        $expect = "10.2";
        $js = new TJavascriptSerializer($float);
        $this->assertEqual($expect, $js->toJavascript());
        
        $float = INF;
        $expect = "Number.POSITIVE_INFINITY";
        $js = new TJavascriptSerializer($float);
        $this->assertEqual($expect, $js->toJavascript());
        
        $expect = "Number.NEGATIVE_INFINITY";
        $js = new TJavascriptSerializer(-$float);
        $this->assertEqual($expect, $js->toJavascript());
    }
    
    function testBoolean()
    {
        $bool = false;
        $expect = "false";
        $js = new TJavascriptSerializer($bool);
        $this->assertEqual($expect, $js->toJavascript());
        
        $expect = "true";
        $js = new TJavascriptSerializer(!$bool);
        $this->assertEqual($expect, $js->toJavascript());
    }
    
    function testNull()
    {
        $null = null;
        $expect = "null";
        $js = new TJavascriptSerializer($null);
        $this->assertEqual($expect, $js->toJavascript());
    }
    
    function testArray()
	{
		$data[0] = 1;
		$data[1] = "hello";
		$data[2] = 1.20;
		$data[3] = true;
		$data[4] = false;
		$data[5] = null;
		$data[6] = array("one");
		
		$expect = "[1,'hello',1.2,true,false,null,['one']]";		
		$js = new TJavascriptSerializer($data);
        $this->assertEqual($expect, $js->toJavascript());
        
        $data = array();
        $expect = "[]";
        $js = new TJavascriptSerializer($data);
        $this->assertEqual($expect, $js->toJavascript(true));
	}
	
	function testMap()
	{
		$data['hello'] = 'world';
		$data['more'] = array('yes' => 'yah!');        
		$expect = "{'hello':'world','more':{'yes':'yah!'}}";
		$js = new TJavascriptSerializer($data);
        $this->assertEqual($expect, $js->toMap());
	}
    
    function testObject()
    {
        $data = new testSerializerObject;
        $expect = "{'public':'public data'}";
        $js = new TJavascriptSerializer($data);
        $this->assertEqual($expect, $js->toJavascript());
    }
    
    //should not serialize components!
    function testComponent()
    {
        $data = new testComponentObject;
        $expect = "{'public':'public data','Data':'component data'}";
        $js = new TJavascriptSerializer($data);
        try
        {
            $js->toJavascript();
            $this->fail();
        }
        catch(TException $e)
        {
            $this->pass();
        }
    }
	
	function testComplexStrings()
	{
		$data[] = "\"It's slash \/ wonderful\"";
		$expect = "['\\\"It\'s slash \\\\/ wonderful\\\"']";
        $js = new TJavascriptSerializer($data);
		$this->assertEqual($expect, $js->toJavascript());
	}
	

	function testArrayString()
	{
		$data[] = "['hello', 1]";
		$data[] = "{'asd':'asdasd'}";
		$data[] = "[hasdkj}";
		$expect = "[['hello', 1],{'asd':'asdasd'},'[hasdkj}']";
        $js = new TJavascriptSerializer($data);
		$this->assertEqual($expect, $js->toJavascript());
	}

    function testArrayComplex()
	{
		$data = array("hello", 1, 2.12, array("world", null, "", array()));
		$expect = "['hello',1,2.12,['world',null]]";
		$js = new TJavascriptSerializer($data);
        $this->assertEqual($expect, $js->toJavascript());
		
		$expect = "['hello',1,2.12,['world',null,'',[]]]";
		$this->assertEqual($expect, $js->toJavascript(true));
	}	
	
	function testListComplex()
	{
		$data = array("hello"=>"world", 1, 2.12);
		$data["more"] = array("the" => "world", null, "good"=>"", array());
		$expect = "{'hello':'world','0':1,'1':2.12,'more':{'the':'world','0':null}}";
		$js = new TJavascriptSerializer($data);
        $this->assertEqual($expect, $js->toMap());
		
		$expect = "{'hello':'world','0':1,'1':2.12,'more':{'the':'world','0':null,'good':'','1':{}}}";        
		$this->assertEqual($expect, $js->toMap(true));
	}
}

?>