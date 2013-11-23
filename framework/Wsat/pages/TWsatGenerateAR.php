<?php

/**
 * Description of Inicio
 *
 * @author daniels
 */
Prado::using("System.Wsat.TWsatARGenerator");

class TWsatGenerateAR extends TPage {

    public function generate($sender) {
        if ($this->IsValid) {
            $table_name = $this->table_name->Text;
            $class_prefix = $this->class_prefix->Text;
            $output_folder_ns = $this->output_folder->Text;

            try {
                $ar_generator = new TWsatARGenerator();
                $ar_generator->setOpFile($output_folder_ns);
                $ar_generator->setClasPrefix($class_prefix);

                if ($this->build_rel->Checked) {
                    $ar_generator->buildRelations();
                }
                if ($table_name != "*") {
                    $ar_generator->generate($table_name);
                } else {
                    $ar_generator->generateAll();
                }
                $this->success_panel->CssClass = "success_panel";
                $this->generation_msg->Text = "The code has been generated successfully.";
            } catch (Exception $ex) {
                $this->success_panel->CssClass = "exception_panel";
                $this->generation_msg->Text = $ex->getMessage();
            }
            $this->success_panel->Visible = true;
        }
    }

    public function preview($sender) {
//        $ar_generator = new TWsatARGenerator();
//        $ar_generator->renderAllTablesInformation();
         throw new THttpException(500, "Not implemented yet.");
    }

}

?>