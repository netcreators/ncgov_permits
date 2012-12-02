#
# Table structure for table 'tx_ncgovappointments_log'
#
CREATE TABLE tx_ncgovpermits_log (
	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	cruser_id int(11) DEFAULT '0' NOT NULL,
	deleted tinyint(4) DEFAULT '0' NOT NULL,
	hidden tinyint(4) DEFAULT '0' NOT NULL,
	message text NOT NULL,
	logtype tinytext NOT NULL,
	messagenumber int(11) DEFAULT '0' NOT NULL,

	PRIMARY KEY (uid),
	KEY parent (pid)
);

#
# Table structure for table 'tx_ncgovpermits_permits'
#
CREATE TABLE tx_ncgovpermits_permits (
	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	cruser_id int(11) DEFAULT '0' NOT NULL,
	deleted tinyint(4) DEFAULT '0' NOT NULL,
	hidden tinyint(4) DEFAULT '0' NOT NULL,

	lastpublished int(11) DEFAULT '0' NOT NULL,
	type tinyint(4) DEFAULT '0' NOT NULL,
	language text NOT NULL,
	producttype text NOT NULL,
	productactivities blob NOT NULL,
	publication text NOT NULL,
	validity_start int(11) DEFAULT '0' NOT NULL,
	validity_end int(11) DEFAULT '0' NOT NULL,
	description text NOT NULL,
	documents blob NOT NULL,
	documenttypes blob NOT NULL,
	casereference text NOT NULL,
	casereference_pub text NOT NULL,
	phase text NOT NULL,
	termtype text NOT NULL,
	termtype_start int(11) DEFAULT '0' NOT NULL,
	termtype_end int(11) DEFAULT '0' NOT NULL,
	company text NOT NULL,
	companynumber text NOT NULL,
	companyaddress text NOT NULL,
	companyaddressnumber text NOT NULL,
	companyzipcode text NOT NULL,
	objectreference text NOT NULL,
#	objectzipcode text NOT NULL,
#	objectaddressnumber text NOT NULL,
#	objectaddressnumberadditional text NOT NULL,
#	objectaddress text NOT NULL,
#	objectcity text NOT NULL,
#	objectmunicipality text NOT NULL,
#	objectprovince text NOT NULL,
#	objectcadastremunicipality text NOT NULL,
#	objectcoordinates text NOT NULL,
# publication stuff

	objectaddresses blob,
	lots blob,
	coordinates blob,
	related blob,
	
	title text NOT NULL,
	publishdate int(11) DEFAULT '0' NOT NULL,
	link text NOT NULL,
	publicationbody text NOT NULL,
	
	PRIMARY KEY (uid),
	KEY parent (pid)
);

#
# Table structure for table 'tx_ncgovpermits_addresses'
#
CREATE TABLE tx_ncgovpermits_addresses (
	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	cruser_id int(11) DEFAULT '0' NOT NULL,
	deleted tinyint(4) DEFAULT '0' NOT NULL,
	hidden tinyint(4) DEFAULT '0' NOT NULL,

	zipcode text NOT NULL,
	addressnumber text NOT NULL,
	addressnumberadditional text NOT NULL,
	address text NOT NULL,
	city text NOT NULL,
	municipality text NOT NULL,
	province text NOT NULL,
	
	PRIMARY KEY (uid),
	KEY parent (pid)
);

#
# Table structure for table 'tx_ncgovpermits_lots'
#
CREATE TABLE tx_ncgovpermits_lots (
	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	cruser_id int(11) DEFAULT '0' NOT NULL,
	deleted tinyint(4) DEFAULT '0' NOT NULL,
	hidden tinyint(4) DEFAULT '0' NOT NULL,

	cadastremunicipality text NOT NULL,
	section text NOT NULL,
	number text NOT NULL,
	
	PRIMARY KEY (uid),
	KEY parent (pid)
);

#
# Table structure for table 'tx_ncgovpermits_coordinates'
#
CREATE TABLE tx_ncgovpermits_coordinates (
	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	cruser_id int(11) DEFAULT '0' NOT NULL,
	deleted tinyint(4) DEFAULT '0' NOT NULL,
	hidden tinyint(4) DEFAULT '0' NOT NULL,
	
	coordinatex text NOT NULL,
	coordinatey text NOT NULL,
	coordinatez text NOT NULL,
	
	PRIMARY KEY (uid),
	KEY parent (pid)
);