use IBatisNet;

drop table if exists Documents;

create table Documents
(
   Document_Id                    int                            not null,
   Document_Title                  varchar(32),
   Document_Type                  varchar(32),
   Document_PageNumber				int,
   Document_City					varchar(32),
   primary key (DOCUMENT_ID)
) TYPE=INNODB;

INSERT INTO Documents VALUES (1, 'The World of Null-A', 'Book', 55, null);
INSERT INTO Documents VALUES (2, 'Le Progres de Lyon', 'Newspaper', null , 'Lyon');
INSERT INTO Documents VALUES (3, 'Lord of the Rings', 'Book', 3587, null);
INSERT INTO Documents VALUES (4, 'Le Canard enchaine', 'Tabloid', null , 'Paris');
INSERT INTO Documents VALUES (5, 'Le Monde', 'Broadsheet', null , 'Paris');
INSERT INTO Documents VALUES (6, 'Foundation', 'Monograph', 557, null);
