<?php

/**
 * Description of Inicio
 *
 * @author daniels
 */
Prado::using("System.Wsat.TWsatARGenerator");

class TWsatGenerateAR extends TPage {

    public function onInit($param) {
        parent::onInit($param);
    }

    public function generate($sender) {
        $table_name = $this->table_name->Text;

        $ar_generator = new TWsatARGenerator();
        if ($table_name != "*") {
            $ar_generator->generate($table_name);
        } else {
            $ar_generator->generateAll();
        }
    }

    public function preview($sender) {
        
    }

}

?>