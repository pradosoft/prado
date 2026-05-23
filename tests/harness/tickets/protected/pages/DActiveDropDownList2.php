<?php

class DActiveDropDownList2 extends TActiveDropDownList
{
	public function setOpcoes($val)
	{
		$this->setViewState('Opcoes', $val);
	}

	public function loadOptions()
	{
		$opcao = $this->getViewState('Opcoes');

		switch ($opcao) {
			case "turnos":
				$this->DataTextField = "descricao";
				$this->DataValueField = "id_turno";
				$opts = [
					['id_turno' => 'M', 'descricao' => 'Manhã'],
					['id_turno' => 'T', 'descricao' => 'Tarde'],
					['id_turno' => 'N', 'descricao' => 'Noite']
				];
				break;

			default:
				throw new TConfigurationException('Falta argumento OPCOES no DActiveDropDownList');
				break;
		}
		$this->setDataSource($opts);
		$this->dataBind();
	}
}
