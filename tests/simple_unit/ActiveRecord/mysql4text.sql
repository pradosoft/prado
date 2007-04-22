CREATE TABLE album (
  title varchar(100) NOT NULL default '',
  PRIMARY KEY  (title)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE artist (
  name varchar(25) NOT NULL default '',
  PRIMARY KEY  (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE album_artists (
  album_title varchar(100) NOT NULL default '',
  artist_name varchar(25) NOT NULL default '',
  PRIMARY KEY  (album_title,artist_name),
  KEY FK_album_artists_2 (artist_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE track (
  id int(11) NOT NULL auto_increment,
  song_name varchar(200) NOT NULL default '',
  album_id varchar(100) NOT NULL default '',
  PRIMARY KEY  (id),
  KEY album_id (album_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE album_artists
  ADD CONSTRAINT FK_album_artists_2 FOREIGN KEY (artist_name) REFERENCES artist (name),
  ADD CONSTRAINT FK_album_artists_1 FOREIGN KEY (album_title) REFERENCES album (title);

ALTER TABLE track
  ADD CONSTRAINT track_ibfk_1 FOREIGN KEY (album_id) REFERENCES album (title);


INSERT INTO album (title) VALUES ('Album 1');
INSERT INTO album (title) VALUES ('Album 2');

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