<?php

/**
  * Keeps track of the people in our Contact list.
  *
  * Starts with a standard Contact list and can add
  * new people to our list or change existing Contacts.
  * This class is for example purposes only, just to
  * show how to create a webservice
  */
class ContactManager{

	/**
	 * Gets the current Contact list.
	 * @return Contact[]
	 * @soapmethod
	 */
	public function	getContacts() {
		$Contact = new Contact();
		$Contact->address = new Address();
		$Contact->address->city ="sesamcity";
		$Contact->address->street ="sesamstreet";
		$Contact->email = "me@you.com";
		$Contact->id = 1;
		$Contact->name ="me";
		
		$ret[] = $Contact;
		//debugObject("Contacten: ",$ret);
		return $ret;
	}
	
	/**
	  * Gets the Contact with the given id.
	  * @param int $id The id
	  * @return Contact
	  * @soapmethod
	  */
	public function	getContact($id) {
		//get Contact from db
		//might wanna throw an exception when it does not exists
		throw new Exception("Contact '$id' not found");
	}
	/**
	  * Generates an new, empty Contact template
	  * @return Contact
	  * @soapmethod
	  */
	public function newContact() {
		return new Contact();
	}
	
	/**
	  * Saves a given Contact
	  * @param Contact $Contact
	  * @return boolean
	  * @soapmethod
	  */
	public function saveContact(Contact $Contact) {
		error_log(var_export($Contact,true));
		//$Contact->save();
		return true;
	}
	
	/**
	 * @return mixed
	 * @soapmethod
	 */
	public function getList()
	{
		return array(array(1,2), array("12", 1.2));
	}
	

}


/**
  * The Contact details for a person
  *
  * Stores the person's name, address and e-mail
  * This class is for example purposes only, just to
  * show how to create a webservice
  *
  */
class Contact{

	/** 
	 * @var int $id
	 * @soapproperty
	 */
	public $id;
	
	/** 
	* @var string $name
	 * @soapproperty
	*/
	public $name;

	/** @var Address $address
	 * @soapproperty
	*/
	public $address;

	/** @var string $email
	 * @soapproperty	
	*/
	public $email;
	
	/**
	  * saves a Contact
	  *
	  * @return void
	  */
	public function save() {
		//save Contact 2 db
	}
}

/**
  * Stores an address
  *
  * An address consists of a street, number, zipcode and city.
  * This class is for example purposes only, just to
  * show how to create a webservice
  *
  */
class Address{
	/** @var string $street
	 * @soapproperty	
	*/
	public $street;
	
	/** @var string $nr
	 * @soapproperty
	*/
	public $nr;
	
	/** @var string $zipcode
	 * @soapproperty
	*/
	public $zipcode;	
	
	/** @var string $city
	 * @soapproperty
	*/
	public $city;	
}

?>