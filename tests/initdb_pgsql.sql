DROP ROLE IF EXISTS prado_unitest;
CREATE ROLE prado_unitest superuser;
ALTER ROLE prado_unitest WITH LOGIN;

DROP TABLE IF EXISTS address;
CREATE TABLE address (
  "id" SERIAL,
  "username" VARCHAR(128) NOT NULL,
  "phone" CHAR(40) NOT NULL DEFAULT 'hello',
  "field1_boolean" BOOLEAN NOT NULL,
  "field2_date" DATE NOT NULL,
  "field3_double" FLOAT8 NOT NULL,
  "field4_integer" INT NOT NULL DEFAULT 1 references address(id), 
  "field5_text" TEXT NOT NULL,
  "field6_time" TIME NOT NULL,
  "field7_timestamp" TIMESTAMP(6) NOT NULL,
  "field8_money" MONEY NOT NULL,
  "field9_numeric" NUMERIC(6, 4) NOT NULL,
  "int_fk1" INT NOT NULL,
  "int_fk2" INT NOT NULL,
  PRIMARY KEY ("id")
);
