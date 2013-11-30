<?php

/**
 * @author Daniel Sampedro Bello <darthdaniel85@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2013 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Id$
 * @since 3.3
 * @package Wsat.pages
 */
Prado::using("System.Wsat.TWsatARGenerator");

class TWsatGenerateAR extends TPage
{

        public function generate($sender)
        {
                if ($this->IsValid)
                {
                        $table_name = $this->table_name->Text;
                        $output_folder_ns = $this->output_folder->Text;
                        $class_prefix = $this->class_prefix->Text;
                        $class_sufix = $this->class_sufix->Text;

                        try
                        {
                                $ar_generator = new TWsatARGenerator();
                                $ar_generator->setOpFile($output_folder_ns);
                                $ar_generator->setClasPrefix($class_prefix);
                                $ar_generator->setClassSufix($class_sufix);

                                if ($this->build_rel->Checked)
                                        $ar_generator->buildRelations();

                                if ($table_name != "*")
                                        $ar_generator->generate($table_name);
                                else
                                        $ar_generator->generateAll();

                                $this->feedback_panel->CssClass = "green_panel";
                                $this->generation_msg->Text = "The code has been generated successfully.";
                        } catch (Exception $ex)
                        {
                                $this->feedback_panel->CssClass = "red_panel";
                                $this->generation_msg->Text = $ex->getMessage();
                        }
                        $this->feedback_panel->Visible = true;
                }
        }

        public function preview($sender)
        {
//                $ar_generator = new TWsatARGenerator();
//                $ar_generator->renderAllTablesInformation();
                throw new THttpException(500, "Not implemented yet.");
        }

}