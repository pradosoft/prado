<?php

class GerTurno2 extends TPage {
    
    private $_turnos = null;


    public function onLoad($param) {
        parent::onLoad($param);
        
        $this->loadTurnoOptions();
        
        if (!$this->IsPostBack) {
            $this->ativaModoEdicao();
        }
    }
    
    
    protected function loadTurnoOptions()
    {
    	$this->DDropTurno->DataTextField="descricao";
        $this->DDropTurno->DataValueField="id";
        $this->_turnos = array(
							array('id' => 1, 'codigo'=>'test 1', 'descricao' => 'hello 1'),
							array('id' => 2, 'codigo'=>'test 2', 'descricao' => 'hello 2')
						);
        $this->DDropTurno->setDataSource($this->_turnos);
        $this->DDropTurno->dataBind();
    }


    protected function ativaModoEdicao() {
        $this->loadDadosTurno($this->DDropTurno->getSelectedValue());
    }

    
    protected function loadDadosTurno($id) {
        foreach ($this->_turnos as $key => $tur) {
        	if ($tur['id'] == $id) {
        	    $this->Codigo->setText($tur['codigo']);
                $this->Descricao->setText($tur['descricao']);
        	}
        }
    }

    
    public function trocaTurno($sender,$param) {
        $this->loadDadosTurno($sender->getSelectedValue());
    }
    
}

?>