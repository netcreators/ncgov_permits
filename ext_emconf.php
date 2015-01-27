<?php

/***************************************************************
 * Extension Manager/Repository config file for ext "ncgov_permits".
 *
 * Auto generated 14-01-2014 16:10
 *
 * Manual updates:
 * Only the data in the array - everything else is removed by next
 * writing. "version" and "dependencies" must not be touched!
 ***************************************************************/

$EM_CONF[$_EXTKEY] = array(
	'title' => 'Permits and publications',
	'description' => 'Module which publishes permits and publications metadata for municipals',
	'category' => 'plugin',
	'author' => 'Frans van der Veen & Klaus Bitto & Jordi Bakker[netcreators]',
	'author_email' => 'extensions@netcreators.com',
	'shy' => '',
	'dependencies' => 'nc_lib',
	'conflicts' => '',
	'priority' => '',
	'module' => '',
	'state' => 'stable',
	'internal' => '',
	'uploadfolder' => 0,
	'createDirs' => 'uploads/tx_ncgovpermits/documents',
	'modify_tables' => '',
	'clearCacheOnLoad' => 0,
	'lockType' => '',
	'author_company' => 'Netcreators',
	'version' => '1.0.4',
	'constraints' => array(
		'depends' => array(
			'php' => '5.3.0-0.0.0',
			'typo3' => '6.2.0-6.2.99',
			'nc_lib' => '2.0.6-2.0.99',
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
	'_md5_values_when_last_written' => '',
	'suggests' => array(
	),
);

?>