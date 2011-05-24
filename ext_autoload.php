<?php
/* 
 * Register necessary class names with autoloader
 *
 * $Id: ext_autoload.php 38588 2010-09-24 20:13:22Z fabien_u $
 */
$extensionPath = t3lib_extMgm::extPath('ter_xsl');
return array(
	'tx_terxsl_transformer_docbook' => $extensionPath . 'Classes/Transformer/DocBook.php',
	'tx_terxsl_utility_cli' => $extensionPath . 'Classes/Utility/Cli.php',
);
?>
