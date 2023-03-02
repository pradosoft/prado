<?php

require_once(__DIR__ . '/BaseCase.php');

use Prado\Data\SqlMap\DataMapper\TSqlMapTypeHandler;

class InheritanceTest extends BaseCase
{
	public function __construct()
	{
		parent::__construct();

		$this->initSqlMap();
		$this->initScript('documents-init.sql');
	}

	/// Test All document with no formula
	public function testGetAllDocument()
	{
		$list = $this->sqlmap->queryForList("GetAllDocument");

		$this->assertEquals(6, count($list));
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
	public function testGetTypedCollection()
	{
		$list = $this->sqlmap->queryForList("GetTypedCollection");

		$this->assertEquals(6, $list->getCount());

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
	public function testGetAllDocumentWithCustomTypeHandler()
	{

		//register the custom inheritance type handler
		$this->sqlmap->registerTypeHandler(new CustomInheritance);

		$list = $this->sqlmap->queryForList("GetAllDocumentWithCustomTypeHandler");

		$this->assertEquals(6, count($list));
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

	public function AssertDocument(Document $document, $id, $title)
	{
		$this->assertEquals($id, $document->getID());
		$this->assertEquals($title, $document->getTitle());
	}

	public function AssertBook(Book $book, $id, $title, $pageNumber)
	{
		$this->assertEquals($id, $book->getId());
		$this->assertEquals($title, $book->getTitle());
		$this->assertEquals($pageNumber, (int) $book->getPageNumber());
	}

	public function AssertNewspaper(Newspaper $news, $id, $title, $city)
	{
		$this->assertEquals($id, $news->getId());
		$this->assertEquals($title, $news->getTitle());
		$this->assertEquals($city, $news->getCity());
	}
}


class CustomInheritance extends TSqlMapTypeHandler
{
	public function getResult($type)
	{
		switch ($type) {
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

	public function createNewInstance($data = null)
	{
		throw new TDataMapperException('can not create');
	}
}
