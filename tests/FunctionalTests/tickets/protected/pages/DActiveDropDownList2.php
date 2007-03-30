<?php
Prado::using('System.Web.UI.ActiveControls.*');
class DActiveDropDownList2 extends TActiveDropDownList
{
    public function setOpcoes($val)
    {
        $this->setViewState('Opcoes', $val);
    }

    public function loadOptions()
    {
        $opcao =  $this->getViewState('Opcoes');

        switch ($opcao) {
            case "turnos":
                $this->DataTextField="descricao";
                $this->DataValueField="id_turno";
                $opts = array(
                    array('id_turno' => 'M', 'descricao' => 'Manhã'),
                    array('id_turno' => 'T', 'descricao' => 'Tarde'),
                    array('id_turno' => 'N', 'descricao' => 'Noite')
                );
                break;

            default:
                throw new TConfigurationException('Falta argumento OPCOES no DActiveDropDownList');
                break;
        }
        $this->setDataSource($opts);
        $this->dataBind();
    }
}

?>