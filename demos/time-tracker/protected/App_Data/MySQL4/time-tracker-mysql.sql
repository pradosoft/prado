DROP TABLE IF EXISTS user_roles;
CREATE TABLE user_roles (
  UserID varchar(50) NOT NULL,
  RoleType varchar(50) NOT NULL,
  PRIMARY KEY  (UserID,RoleType),
  KEY RoleType (RoleType)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS role_types;
CREATE TABLE role_types (
  RoleType varchar(50) NOT NULL,
  Description varchar(255) NOT NULL,
  PRIMARY KEY  (RoleType)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS project_members;
CREATE TABLE project_members (
  UserID varchar(50) NOT NULL,
  ProjectID int(11) NOT NULL,
  PRIMARY KEY  (UserID,ProjectID),
  KEY ProjectID (ProjectID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS time_entry;
CREATE TABLE time_entry (
  EntryID int(11) NOT NULL auto_increment,
  EntryCreated datetime NOT NULL,
  Duration float(10,2) NOT NULL default '0.00',
  Description varchar(1000) default NULL,
  CategoryID int(11) NOT NULL default '0',
  EntryDate datetime default NULL,
  CreatorID varchar(50) NOT NULL,
  UserID varchar(50) NOT NULL,
  PRIMARY KEY  (EntryID),
  KEY CategoryID (CategoryID),
  KEY CreatorID (CreatorID),
  KEY UserID (UserID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

DROP TABLE IF EXISTS signon;
CREATE TABLE signon (
  SessionToken varchar(32) NOT NULL,
  Username varchar(50) NOT NULL,
  LastSignOnDate datetime NOT NULL,
  PRIMARY KEY  (SessionToken),
  KEY Username (Username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS categories;
CREATE TABLE categories (
  CategoryID int(11) NOT NULL auto_increment,
  `Name` varchar(255) NOT NULL,
  ProjectID int(11) NOT NULL,
  ParentCategoryID int(11) default '0',
  Abbreviation varchar(255) default NULL,
  EstimateDuration float(10,2) default '0.00',
  PRIMARY KEY  (CategoryID),
  UNIQUE KEY UniqueNamePerProject (`Name`,ProjectID),
  KEY ProjectID (ProjectID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

DROP TABLE IF EXISTS project;
CREATE TABLE project (
  ProjectID int(11) NOT NULL auto_increment,
  `Name` varchar(255) NOT NULL,
  Description varchar(255) default NULL,
  CreationDate datetime NOT NULL,
  CompletionDate datetime NOT NULL,
  Disabled tinyint(1) NOT NULL default '0',
  EstimateDuration float(10,2) NOT NULL default '0.00',
  CreatorID varchar(50) NOT NULL,
  ManagerID varchar(50) default NULL,
  PRIMARY KEY  (ProjectID),
  KEY `Name` (`Name`),
  KEY CreatorID (CreatorID),
  KEY ManagerID (ManagerID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

DROP TABLE IF EXISTS users;
CREATE TABLE users (
  Username varchar(50) NOT NULL,
  `Password` varchar(50) NOT NULL,
  EmailAddress varchar(100) NOT NULL,
  Disabled tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (Username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `categories`
  ADD CONSTRAINT categories_ibfk_1 
  	FOREIGN KEY (ProjectID) REFERENCES project (ProjectID);

ALTER TABLE `project`
  ADD CONSTRAINT project_ibfk_2 
  	FOREIGN KEY (ManagerID) REFERENCES users (Username),
  ADD CONSTRAINT project_ibfk_1 
  	FOREIGN KEY (CreatorID) REFERENCES users (Username);

ALTER TABLE `project_members`
  ADD CONSTRAINT project_members_ibfk_1 
  	FOREIGN KEY (UserID) REFERENCES users (Username),
  ADD CONSTRAINT project_members_ibfk_2 
  	FOREIGN KEY (ProjectID) REFERENCES project (ProjectID);

ALTER TABLE `signon`
  ADD CONSTRAINT signon_ibfk_1 
  	FOREIGN KEY (Username) REFERENCES users (Username);

ALTER TABLE `time_entry`
  ADD CONSTRAINT time_entry_ibfk_2 
  	FOREIGN KEY (UserID) REFERENCES users (Username),
  ADD CONSTRAINT time_entry_ibfk_1 
  	FOREIGN KEY (CategoryID) REFERENCES categories (CategoryID);

ALTER TABLE `user_roles`
  ADD CONSTRAINT user_roles_ibfk_2 
  	FOREIGN KEY (RoleType) REFERENCES role_types (RoleType),
  ADD CONSTRAINT user_roles_ibfk_1 
  	FOREIGN KEY (UserID) REFERENCES users (Username);

INSERT INTO role_types (RoleType, Description) VALUES 
('admin', 'Project administrator may additionally view the list of all users.'),
('consultant', 'Consultant may log time entries only.'),
('manager', 'Project manager may additionally edit all projects and view reports.');

INSERT INTO users (Username, Password, EmailAddress, Disabled) VALUES 
('admin', '21232f297a57a5a743894a0e4a801fc3', 'admin@pradosoft.com', 0),
('manager', '1d0258c2440a8d19e716292b231e3190', 'manager@pradosoft.com', 0),
('consultant', '7adfa4f2ba9323e6c1e024de375434b0', 'consultant@pradosoft.com', 0);

INSERT INTO user_roles (UserID, RoleType) VALUES 
('admin', 'admin'),
('admin', 'manager'),
('admin', 'consultant'),
('manager', 'manager'),
('manager', 'consultant'),
('consultant', 'consultant');