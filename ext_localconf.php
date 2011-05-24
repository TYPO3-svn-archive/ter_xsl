<?php
if (!defined ('TYPO3_MODE')) {
 	die ('Access denied.');
}
// Register as Data Consumer service
// Note that the subtype corresponds to the name of the database table
t3lib_extMgm::addService($_EXTKEY,  'xsl' /* sv type */,  'tx_terxsl_docbook' /* sv key */,
	array(

		'title' => 'XSL Documentation transformer',
		'description' => 'Provides different documentation transformation services linked to the TER',

		'subtype' => 'xslt',

		'available' => TRUE,
		'priority' => 50,
		'quality' => 50,

		'os' => '',
		'exec' => '',

		'classFile' => t3lib_extMgm::extPath($_EXTKEY, 'Classes/Transformer/DocBook.php'),
		'className' => 'Tx_TerXsl_Transformer_DocBook',
	)
);
?>
