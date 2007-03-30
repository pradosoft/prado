<?php
Prado::using('System.Web.UI.ActiveControls.*');
class Ticket528 extends TPage
{
	 public static $turnos = array(
                    'M' => array('id_turno' => 'M', 'descricao' => 'Manhã'),
                    'T' => array('id_turno' => 'T', 'descricao' => 'Tarde'),
                    'N' => array('id_turno' => 'N', 'descricao' => 'Noite')
                   );


    public function onLoad($param)
    {
        parent::onLoad($param);
        if (!$this->IsPostBack) {
            $this->DDropTurno->loadOptions();
            $this->loadDadosTurno($this->DDropTurno->getSelectedValue());
        }
    }


    protected function loadDadosTurno($id)
    {
        $this->Codigo->setText(self::$turnos[$id]['id_turno']);
        $this->Descricao->setText(self::$turnos[$id]['descricao']);
    }


    public function trocaTurno($sender,$param)
    {
        $this->loadDadosTurno($sender->getSelectedValue());
    }
}

?>