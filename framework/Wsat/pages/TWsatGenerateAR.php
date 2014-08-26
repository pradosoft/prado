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
                        $tableName = $this->table_name->Text;
                        $outputFolderNs = $this->output_folder->Text;
                        $classPrefix = $this->class_prefix->Text;
                        $classSuffix = $this->class_suffix->Text;

                        try
                        {
                                $ar_generator = new TWsatARGenerator();
                                $ar_generator->setOpFile($outputFolderNs);
                                $ar_generator->setClasPrefix($classPrefix);
                                $ar_generator->setClassSufix($classSuffix);

                                if ($this->build_rel->Checked)
                                        $ar_generator->buildRelations();

                                if ($tableName != "*")
                                        $ar_generator->generate($tableName);
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
//                throw new THttpException(500, "Not implemented yet.");
        }

}