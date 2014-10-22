<?php

/**
 * @author Daniel Sampedro Bello <darthdaniel85@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2014 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Id$
 * @since 3.3
 * @package Wsat.pages
 */
Prado::using("System.Wsat.TWsatScaffoldingGenerator");

class TWsatScaffolding extends TPage
{

        public function onInit($param)
        {
                parent::onInit($param);
                if (!$this->IsPostBack)
                {
                        $this->startVisual();
                }
        }

        private function startVisual()
        {
                $scf_generator = new TWsatScaffoldingGenerator();
                foreach ($scf_generator->getAllTableNames() as $tableName)
                {
                        $dynChb = new TCheckBox();
                        $dynChb->ID = $tableName;
                        $dynChb->Text = ucfirst($tableName);
                        $dynChb->Checked = true;
                        $this->tableNames->getControls()->add($dynChb);

                }
        }

        public function generate($sender)
        {
                if ($this->IsValid)
                {
                        $scf_generator = new TWsatScaffoldingGenerator();
                        $scf_generator->renderAllTablesInformation();
                }
        }

}