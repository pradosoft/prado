<?php

namespace Prado\Test\Unit\Data\ActiveRecord;

class ForeignKeyTest extends \PHPUnit\Framework\TestCase
{
	public function test_has_many()
	{
		$albums = Album::finder()->withTracks()->findAll();
		$this->assertEquals(count($albums), 2);

		$this->assertEquals($albums[0]->title, 'Album 1');
		$this->assertEquals($albums[1]->title, 'Album 2');

		$this->assertEquals(count($albums[0]->Artists), 0);
		$this->assertEquals(count($albums[1]->Artists), 0);

		$this->assertEquals(count($albums[0]->Tracks), 3);
		$this->assertEquals(count($albums[1]->Tracks), 2);

		$this->assertEquals($albums[0]->Tracks[0]->song_name, 'Track 1');
		$this->assertEquals($albums[0]->Tracks[1]->song_name, 'Song 2');
		$this->assertEquals($albums[0]->Tracks[2]->song_name, 'Song 3');

		$this->assertEquals($albums[1]->Tracks[0]->song_name, 'Track A');
		$this->assertEquals($albums[1]->Tracks[1]->song_name, 'Track B');
	}

	public function test_has_one()
	{
		$albums = Album::finder()->with_cover()->findAll();
		$this->assertEquals(count($albums), 2);

		$this->assertEquals($albums[0]->title, 'Album 1');
		$this->assertEquals($albums[1]->title, 'Album 2');

		$this->assertEquals($albums[0]->cover->content, 'lalala');
		$this->assertEquals($albums[1]->cover->content, 'conver content');

		$this->assertEquals(count($albums[0]->Artists), 0);
		$this->assertEquals(count($albums[1]->Artists), 0);

		$this->assertEquals(count($albums[0]->Tracks), 0);
		$this->assertEquals(count($albums[1]->Tracks), 0);
	}

	public function test_belongs_to()
	{
		$track = Track::finder()->withAlbum()->find('id = ?', 1);

		$this->assertEquals($track->id, "1");
		$this->assertEquals($track->song_name, "Track 1");
		$this->assertEquals($track->Album->title, "Album 1");
	}

	public function test_has_many_associate()
	{
		$album = Album::finder()->withArtists()->find('title = ?', 'Album 2');
		$this->assertEquals($album->title, 'Album 2');
		$this->assertEquals(count($album->Artists), 3);

		$this->assertEquals($album->Artists[0]->name, 'Dan');
		$this->assertEquals($album->Artists[1]->name, 'Karl');
		$this->assertEquals($album->Artists[2]->name, 'Tom');
	}

	public function test_multiple_fk()
	{
		$album = Album::finder()->withArtists()->withTracks()->with_cover()->find('title = ?', 'Album 1');

		$this->assertEquals($album->title, 'Album 1');
		$this->assertEquals(count($album->Artists), 2);

		$this->assertEquals($album->Artists[0]->name, 'Dan');
		$this->assertEquals($album->Artists[1]->name, 'Jenny');

		$this->assertEquals($album->Tracks[0]->song_name, 'Track 1');
		$this->assertEquals($album->Tracks[1]->song_name, 'Song 2');
		$this->assertEquals($album->Tracks[2]->song_name, 'Song 3');

		$this->assertEquals($album->cover->content, 'lalala');
	}
}
