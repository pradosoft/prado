<?php

class Home extends TPage {

  private $_client;
  
  public function onInit($param) {
    // TODO: configure wsdl
    $wsdl = 'http://localhost/prado/svn/trunk/demos/soap/index.php?soap=SimpleService&wsdl';
    $location = 'http://localhost/prado/svn/trunk/demos/soap/index.php?soap=SimpleService';
    // TODO: use TSoapClient
    //$this->_client = new SoapClient($wsdl, array('soap_version' => SOAP_1_1,
    //'use' => '',
    //						   'style' => ''));

    // TODO: use classmap
    $this->_client = new SoapClient(null, array('location' => $location, 'uri' => 'urn:SimpleService', 'soap_version' => SOAP_1_2));
  }

  public function onCompute($sender, $param) {
    $a = $this->a->Text;
    $b = $this->b->Text;

    try {
      $result = $this->_client->add($a, $b);
    } catch(SoapFault $e) { // TODO: Prado wrapper for SoapFault (TSoapFaultException)
      print $e;
    }
    //var_dump($result);
    $this->result->Text = $result;
  }

  public function onHighlight($sender, $param) {
    try {
      $result = $this->_client->__soapCall('highlight', array(file_get_contents(__FILE__)));
    } catch(SoapFault $e) { // TODO: Prado wrapper for SoapFault (TSoapFaultException)
      print $e;
    }
    $this->SourceCode->Text = $result;
  }

}

?>