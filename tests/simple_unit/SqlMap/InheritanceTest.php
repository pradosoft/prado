<?php

require_once(dirname(__FILE__).'/BaseCase.php');

/**
 * @package System.DataAccess.SQLMap
 */
class InheritanceTest extends BaseCase
{
	function __construct()
	{
		parent::__construct();

		$this->initSqlMap();
		$this->initScript('documents-init.sql');
	}

	/// Test All document with no formula
	function testGetAllDocument()
	{
		$list = $this->sqlmap->queryForList("GetAllDocument");

		$this->assertIdentical(6, count($list));
		$book = $list[0];
		$this->assertBook($book, 1, "The World of Null-A", 55);

		$book = $list[1];
		$this->assertBook($book, 3, "Lord of the Rings", 3587);

		$document = $list[2];
		$this->assertDocument($document, 5, "Le Monde");

		$document = $list[3];
		$this->assertDocument($document, 6, "Foundation");

		$news = $list[4];
		$this->assertNewspaper($news, 2, "Le Progres de Lyon", "Lyon");

		$document = $list[5];
		$this->assertDocument($document, 4, "Le Canard enchaine");
	}

	/// Test All document in a typed collection
	function testGetTypedCollection()
	{
		$list = $this->sqlmap->queryForList("GetTypedCollection");

		$this->assertIdentical(6, $list->getCount());

		$book = $list[0];
		$this->assertBook($book, 1, "The World of Null-A", 55);

		$book = $list[1];
		$this->assertBook($book, 3, "Lord of the Rings", 3587);

		$document = $list[2];
		$this->assertDocument($document, 5, "Le Monde");

		$document = $list[3];
		$this->assertDocument($document, 6, "Foundation");

		$news = $list[4];
		$this->assertNewspaper($news, 2, "Le Progres de Lyon", "Lyon");

		$document = $list[5];
		$this->assertDocument($document, 4, "Le Canard enchaine");
	}

	/// Test All document with Custom Type Handler
	function testGetAllDocumentWithCustomTypeHandler()
	{

		//register the custom inheritance type handler
		$this->sqlmap->registerTypeHandler(new CustomInheritance);

		$list = $this->sqlmap->queryForList("GetAllDocumentWithCustomTypeHandler");

		$this->assertIdentical(6, count($list));
		$book = $list[0];
		$this->assertBook($book, 1, "The World of Null-A", 55);

		$book = $list[1];
		$this->assertBook($book, 3, "Lord of the Rings", 3587);

		$news = $list[2];
		$this->assertNewspaper($news, 5, "Le Monde", "Paris");

		$book = $list[3];
		$this->assertBook($book, 6, "Foundation", 557);

		$news = $list[4];
		$this->assertNewspaper($news, 2, "Le Progres de Lyon", "Lyon");

		$news = $list[5];
		$this->assertNewspaper($news, 4, "Le Canard enchaine", "Paris");
	}

	function AssertDocument(Document $document, $id, $title)
	{
		$this->assertIdentical($id, $document->getID());
		$this->assertIdentical($title, $document->getTitle());
	}

	function AssertBook(Book $book, $id, $title, $pageNumber)
	{
		$this->assertIdentical($id, $book->getId());
		$this->assertIdentical($title, $book->getTitle());
		$this->assertIdentical($pageNumber, (int)$book->getPageNumber());
	}

	function AssertNewspaper(Newspaper $news, $id, $title, $city)
	{
		$this->assertIdentical($id, $news->getId());
		$this->assertIdentical($title, $news->getTitle());
		$this->assertIdentical($city, $news->getCity());
	}
}


class CustomInheritance extends TSqlMapTypeHandler
{
	public function getResult($type)
	{
		switch ($type)
		{
			case 'Monograph': case 'Book':
				return 'Book';
			case 'Tabloid': case 'Broadsheet': case 'Newspaper':
				return 'Newspaper';
			default:
				return 'Document';
		}
	}

	public function getParameter($parameter)
	{
		throw new TDataMapperException('not implemented');
	}

	public function createNewInstance($data=null)
	{
		throw new TDataMapperException('can not create');
	}
}
?>