<?php

Prado::using('Application.pages.AddressRecord');
/**
 * @author Wei Zhuo <weizho[at]gmail[dot]com>
 * @version $Id$
 * @since 3.1
 */
class AddressProvider extends TApplicationComponent
{
	/**
	 * @throws exception if not logged in
	 */ 
	public function __construct($server)
	{
		$authMethods = $server->getRequestedMethod()!=='login';
		$guestUser = $this->User ? $this->User->IsGuest : true;
		if($authMethods && $guestUser)
			throw new TException('authentication required');
	}

	/**
	 * @param string $username
	 * @param string $password
	 * @return boolean
	 * @soapmethod
	 */
	public function login($username, $password)
	{
		return $this->Application->Modules['auth']->login($username, $password);
	}

	/**
	 * @return AddressRecord[]
	 * @soapmethod
	 */
	public function getAllAddress()
	{
		return AddressRecord::finder()->findAll();
	}

	/**
	 * Update address if $data->id > 0, otherwise add new address.
	 * @param AddressRecord $data
	 * @return boolean
	 * @soapmethod
	 */
	public function saveAddress($data)
	{
		$finder = AddressRecord::finder();
		if($data->id > 0 && $address=$finder->findByPk($data->id))
		{
			return $address->copyFrom($data)->save();
		}
		else
		{
			$data->id = null; //nullify the id
			return $data->save();
		}
	}

	/**
	 * @param integer $id
	 * @return integer number of records deleted
	 * @soapmethod
	 */
	public function deleteAddress($id)
	{
		return AddressRecord::finder()->deleteByPk($id);
	}
}

?>