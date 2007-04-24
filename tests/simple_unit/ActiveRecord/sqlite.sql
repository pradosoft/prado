CREATE TABLE album (
  title varchar(100) NOT NULL PRIMARY KEY
);

CREATE TABLE artist (
  name varchar(25) NOT NULL PRIMARY KEY
);

CREATE TABLE album_artists (
  album_title varchar(100) NOT NULL CONSTRAINT fk_album REFERENCES album(title) ON DELETE CASCADE,
  artist_name varchar(25) NOT NULL CONSTRAINT fk_artist REFERENCES artist(name) ON DELETE CASCADE
);

CREATE TABLE track (
  id INTEGER NOT NULL PRIMARY KEY,
  song_name varchar(200) NOT NULL default '',
  album_id varchar(100) NOT NULL CONSTRAINT fk_album_1 REFERENCES album(title) ON DELETE CASCADE
);

CREATE TABLE cover(
	album varchar(200) NOT NULL CONSTRAINT fk_album_2 REFERENCES album(title) ON DELETE CASCADE,
	content text
);

INSERT INTO album (title) VALUES ('Album 1');
INSERT INTO album (title) VALUES ('Album 2');

INSERT INTO cover(album,content) VALUES ('Album 1', 'lalala');
INSERT INTO cover(album,content) VALUES ('Album 2', 'conver content');

INSERT INTO artist (name) VALUES ('Dan');
INSERT INTO artist (name) VALUES ('Jenny');
INSERT INTO artist (name) VALUES ('Karl');
INSERT INTO artist (name) VALUES ('Tom');

INSERT INTO album_artists (album_title, artist_name) VALUES ('Album 1', 'Dan');
INSERT INTO album_artists (album_title, artist_name) VALUES ('Album 2', 'Dan');
INSERT INTO album_artists (album_title, artist_name) VALUES ('Album 1', 'Jenny');
INSERT INTO album_artists (album_title, artist_name) VALUES ('Album 2', 'Karl');
INSERT INTO album_artists (album_title, artist_name) VALUES ('Album 2', 'Tom');

INSERT INTO track (id, song_name, album_id) VALUES (1, 'Track 1', 'Album 1');
INSERT INTO track (id, song_name, album_id) VALUES (2, 'Song 2', 'Album 1');
INSERT INTO track (id, song_name, album_id) VALUES (3, 'Track A', 'Album 2');
INSERT INTO track (id, song_name, album_id) VALUES (4, 'Track B', 'Album 2');
INSERT INTO track (id, song_name, album_id) VALUES (5, 'Song 3', 'Album 1');