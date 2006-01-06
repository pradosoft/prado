# phpMyAdmin SQL Dump
# version 2.5.5-rc2
# http://www.phpmyadmin.net
#
# Host: localhost
# Generation Time: Jan 09, 2005 at 09:24 PM
# Server version: 4.0.17
# PHP Version: 5.0.2
# 
# Database : `messages`
# 

# --------------------------------------------------------

#
# Table structure for table `catalogue`
#

DROP TABLE IF EXISTS catalogue;
CREATE TABLE catalogue (
  cat_id int(11) NOT NULL auto_increment,
  name varchar(100) NOT NULL default '',
  source_lang varchar(100) NOT NULL default '',
  target_lang varchar(100) NOT NULL default '',
  date_created int(11) NOT NULL default '0',
  date_modified int(11) NOT NULL default '0',
  author varchar(255) NOT NULL default '',
  PRIMARY KEY  (cat_id)
) TYPE=MyISAM AUTO_INCREMENT=7 ;


INSERT INTO catalogue VALUES ('1', 'messages', '', '', '', '1103936017', '');
INSERT INTO catalogue VALUES ('2', 'messages.en', '', '', '', '1103936017', '');
INSERT INTO catalogue VALUES ('3', 'messages.en_AU', '', '', '', '1105250301', '');
INSERT INTO catalogue VALUES ('4', 'tests', '', '', '', '1103936017', '');
INSERT INTO catalogue VALUES ('5', 'tests.en', '', '', '', '1103936017', '');
INSERT INTO catalogue VALUES ('6', 'tests.en_AU', '', '', '', '1103936017', '');

# --------------------------------------------------------

#
# Table structure for table `trans_unit`
#

DROP TABLE IF EXISTS trans_unit;
CREATE TABLE trans_unit (
  msg_id int(11) NOT NULL auto_increment,
  cat_id int(11) NOT NULL default '1',
  id varchar(255) NOT NULL default '',
  source text NOT NULL,
  target text NOT NULL,
  comments text NOT NULL,
  date_added int(11) NOT NULL default '0',
  date_modified int(11) NOT NULL default '0',
  author varchar(255) NOT NULL default '',
  translated tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (msg_id)
) TYPE=MyISAM AUTO_INCREMENT=19 ;

INSERT INTO trans_unit VALUES ('1', '1', '1', 'Hello', 'Hello World', '', '', '', '', '1');
INSERT INTO trans_unit VALUES ('2', '2', '1', 'Hello', 'Hello :)', '', '', '', '', '0');
INSERT INTO trans_unit VALUES ('3', '1', '1', 'Welcome', 'Welcome!', '', '', '', '', '0');
INSERT INTO trans_unit VALUES ('4', '3', '1', 'Hello', 'G''day Mate!', '', '', '', '', '0');
INSERT INTO trans_unit VALUES ('5', '3', '2', 'Welcome', 'Welcome Mate!', '', '', '', '', '0');
INSERT INTO trans_unit VALUES ('13', '4', '1', 'Goodbye', 'Aloha!', '', '', '', '', '0');
INSERT INTO trans_unit VALUES ('14', '4', '2', 'Welcome', 'Ho Ho!', '', '', '', '', '0');
INSERT INTO trans_unit VALUES ('15', '5', '1', 'Hello', 'hello', '', '', '', '', '0');
INSERT INTO trans_unit VALUES ('16', '5', '2', 'Goodbye', 'Sayonara', '', '', '', '', '0');
INSERT INTO trans_unit VALUES ('17', '6', '1', 'Hello', 'Howdy!', '', '', '', '', '0');