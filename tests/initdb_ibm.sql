-- IBM DB2 test database schema for prado unit tests
-- Run as db2inst1 (DB2 instance owner) after connecting to the database:
--   db2 CONNECT TO pradount
--   db2 -td@ -f tests/initdb_ibm.sql
--
-- NOTE: DB2 database names are limited to 8 characters and may not contain
-- underscores — hence "prado" rather than "prado_unitest".
--
-- The DROP blocks use CONTINUE HANDLERs so the script is safe to run on a
-- fresh database (SQLSTATE 42704 = object not found is silently ignored).

BEGIN
  DECLARE CONTINUE HANDLER FOR SQLSTATE '42704' BEGIN END;
  EXECUTE IMMEDIATE 'DROP TABLE address';
END@
BEGIN
  DECLARE CONTINUE HANDLER FOR SQLSTATE '42704' BEGIN END;
  EXECUTE IMMEDIATE 'DROP TABLE table1';
END@
CREATE TABLE table1 (
	id              INTEGER       NOT NULL GENERATED ALWAYS AS IDENTITY,
	name            VARCHAR(45)   NOT NULL DEFAULT '',
	field1_smallint SMALLINT      NOT NULL DEFAULT 0,
	field2_varchar  VARCHAR(4000),
	field3_date     DATE,
	field4_float    FLOAT         NOT NULL DEFAULT 10,
	field5_decimal  DECIMAL(10,4) NOT NULL DEFAULT 0,
	field6_double   DOUBLE        NOT NULL DEFAULT 0,
	field7_timestamp TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
	field8_time     TIME          NOT NULL DEFAULT '00:00:00',
	field9_bigint   BIGINT        NOT NULL DEFAULT 0,
	field10_char    CHAR(10),
	field11_boolean BOOLEAN       NOT NULL DEFAULT FALSE,
	field12_numeric NUMERIC(8,2)  NOT NULL DEFAULT 0,
	PRIMARY KEY (id)
)@
CREATE TABLE address (
	username    VARCHAR(128)  NOT NULL,
	phone       VARCHAR(40)   NOT NULL DEFAULT '',
	field1_bool BOOLEAN       NOT NULL DEFAULT FALSE,
	field2_date DATE          NOT NULL,
	field3_dbl  DOUBLE        NOT NULL DEFAULT 0,
	field4_int  INTEGER       NOT NULL DEFAULT 0 REFERENCES table1(id),
	field5_text VARCHAR(4000),
	field6_time TIME          NOT NULL DEFAULT '00:00:00',
	field7_ts   TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
	field8_dec  DECIMAL(19,4) NOT NULL DEFAULT 0,
	field9_num  NUMERIC(10,4) NOT NULL DEFAULT 0,
	int_fk1     INTEGER       NOT NULL DEFAULT 0,
	int_fk2     INTEGER       NOT NULL DEFAULT 0,
	PRIMARY KEY (username)
)@

INSERT INTO table1 (name) VALUES ('test')@
INSERT INTO address (username, phone, field2_date, field4_int) VALUES ('wei', '1111111', CURRENT_DATE, 1)@

BEGIN
  DECLARE CONTINUE HANDLER FOR SQLSTATE '42704' BEGIN END;
  EXECUTE IMMEDIATE 'DROP TABLE upsert_test';
END@

-- DB2 upsert uses MERGE ... USING (SELECT ... FROM SYSIBM.SYSDUMMY1) ON the PK.
CREATE TABLE upsert_test (
	username VARCHAR(100) NOT NULL,
	score    INTEGER      NOT NULL DEFAULT 0,
	PRIMARY KEY (username)
)@
