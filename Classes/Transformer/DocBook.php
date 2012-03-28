<?php

/* * *************************************************************
 *  Copyright notice
 *
 *  (c) 2005-2006 Robert Lemke (robert@typo3.org)
 *  (c) 2011 Fabien Udriot <fabien.udriot@ecodev.ch>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 * ************************************************************* */
/**
 * Documentation renderer for the ter_xsl extension. Called from the CLI
 * script as well as from the ter_xsl_* extensions for registering
 * output formats.
 *
 * $Id: class.tx_terdoc_renderdocuments.php 4352 2006-12-15 17:31:20Z robert $
 *
 * @author	Robert Lemke <robert@typo3.org>
 * @author	Fabien Udriot <fabien.udriot@ecodev.ch>
 */

class Tx_TerXsl_Transformer_DocBook extends t3lib_svbase {

	const ERRORCODE_CONTENTXMLDIDNOTEXISTAFTEREXTRACTINGSXW = 2;
	const ERRORCODE_COULDNOTEXTRACTABSTRACT = 6;
	const ERRORCODE_SIMPLEXMLERRORWHILETRANSFORMINGXMLTODOCBOOK = 3;
	const ERRORCODE_COULDNOTGUESSDOCUMENTLANGUAGENOTEXTLANGSERVICEAVAILABLE = 4;
	const ERRORCODE_COULDNOTGUESSDOCUMENTLANGUAGEEXCERPTEMPTY = 5;

	protected $repositoryDir = '';		 // Full path to the local extension repository. Configured in the Extension Manager
	protected $information = array();

	/**
	 * Settings setter
	 *
	 * @param  array $settings: the run settings
	 * @return void
	 */
	public function setSettings($settings) {
		$this->settings = $settings;
	}

	/**
	 * Transforms the manual of the specified extension version to docbook and saves
	 * the result in a file called "manual.xml" in the document cache directory of the
	 * extension.
	 *
	 * @param	string		$documentDir: the directory where to find the extension
	 * @param	string		$docBookVersion: the number of Version
	 * @param	array		$errorCodes: An array of error codes which occurred during the transformation
	 * @return	mixed		returns TRUE if operation was successful, otherwise FALSE.
	 * @access	protected
	 */
	public function transformManualToDocBook($documentDir, $docBookVersion, &$errorCodes) {

		// Transform the manual's content.xml to DocBook:
		Tx_TerXsl_Utility_Cli::log('   * Rendering DocBook v' . $docBookVersion);
		$xsl = new DomDocument();
		$styleSheet = t3lib_extMgm::extPath('ter_xsl') . 'Resources/Private/XSL/OpenOffice/oomanual2docbookv' . $docBookVersion . '.xsl';
		if (!file_exists($styleSheet)) {
			throw new Exception('Exception thrown #1294749992: file does not exist "' . $styleSheet . '". Check settings', 1294749992);
		}
		$xsl->load($styleSheet);

		if (!@file_exists($documentDir . 'sxw/content.xml')) {
			Tx_TerXsl_Utility_Cli::log('	* transformManualToDocBook: ' . $documentDir . 'sxw/content.xml does not exist.');
			$errorCodes[] = self::ERRORCODE_CONTENTXMLDIDNOTEXISTAFTEREXTRACTINGSXW;
			return FALSE;
		}

		$manualDom = new DomDocument();
		$manualDom->load($documentDir . 'sxw/content.xml');

		$xsltProc = new XsltProcessor();
		$xsl = $xsltProc->importStylesheet($xsl);

		$docBookDom = $xsltProc->transformToDoc($manualDom);
		$docBookDom->formatOutput = FALSE;
		$docBookDom->save($documentDir . 'docbook' . $docBookVersion . '/manual.xml');

		// Create Table Of Content:
		$tocArr = array();
		$chapterCount = 1;
		$sectionCount = 1;
		$subSectionCount = 1;
		$simpleDocBook = simplexml_import_dom($docBookDom);
		if ($simpleDocBook === FALSE) {
			Tx_TerXsl_Utility_Cli::log('	* transformManualToDocBook: SimpleXML error while transforming XML to DocBook');
			$errorCodes[] = self::ERRORCODE_SIMPLEXMLERRORWHILETRANSFORMINGXMLTODOCBOOK;
			return FALSE;
		}

		$abstract = '';
		$textExcerpt = '';
		foreach ($simpleDocBook->chapter as $chapter) {
			$tocArr[$chapterCount]['title'] = (string) $chapter->title;
			foreach ($chapter->section as $section) {
				$tocArr[$chapterCount]['sections'][$sectionCount]['title'] = (string) $section->title;

				// Try to extract an abstract out of the first paragraph of a section usually called "What does it do?":
				if ($chapterCount <= 1) {
					foreach ($section->section as $subSection) {
						if (strlen($abstract) == 0) {
							$abstract = (string) $subSection->para;
						}
					}
				}
				if (strlen($textExcerpt) < 2000) {
					$textExcerpt .= (string) $section->para;
					foreach ($section->section as $subSection) {
						if (strlen($textExcerpt) < 2000) {
							$textExcerpt .= (string) $subSection->para;
							$textExcerpt .= (string) $subSection->itemizedlist->listitem->para;
						}
					}
				}

				foreach ($section->section as $subSection) {
					$tocArr[$chapterCount]['sections'][$sectionCount]['subsections'][$subSectionCount]['title'] = (string) $subSection->title;
					$subSectionCount++;
				}
				$sectionCount++;
				$subSectionCount = 1;
			}
			$chapterCount++;
			$sectionCount = 1;
		}
		t3lib_div::writeFile($documentDir . 'toc.dat', serialize($tocArr));

		if (strlen($abstract) < 5) {
			$errorCodes[] = self::ERRORCODE_COULDNOTEXTRACTABSTRACT;
		}

		// Identify the language of the document:
		if (strlen($textExcerpt)) {
			if (is_object($this->languageGuesserServiceObj)) {
				$this->languageGuesserServiceObj->process($textExcerpt, '', array('encoding' => 'utf-8'));
				$documentLanguage = strtolower($this->languageGuesserServiceObj->getOutput());
			} else {
				Tx_TerXsl_Utility_Cli::log('   * Warning: Could not guess language because textLang service was not available');
				$errorCodes[] = self::ERRORCODE_COULDNOTGUESSDOCUMENTLANGUAGENOTEXTLANGSERVICEAVAILABLE;
				$metaXML = simplexml_load_file($documentDir . 'sxw/meta.xml');
				$DCLanguageArr = $metaXML ? $metaXML->xpath('//dc:language') : '';
				$documentLanguage = is_array($DCLanguageArr) ? strtolower(substr($DCLanguageArr[0], 0, 2)) : '';
			}
		} else {
			Tx_TerXsl_Utility_Cli::log('   * Warning: Could not guess language because the text excerpt was empty!');
			$errorCodes[] = self::ERRORCODE_COULDNOTGUESSDOCUMENTLANGUAGEEXCERPTEMPTY;
		}

		t3lib_div::writeFile($documentDir . 'abstract.txt', $abstract);
		t3lib_div::writeFile($documentDir . 'text-excerpt.txt', $textExcerpt);
		t3lib_div::writeFile($documentDir . 'language.txt', $documentLanguage);

		$this->resetInformation();
		$this->setInformation('abstract', $abstract);
		$this->setInformation('documentLanguage', $documentLanguage);

		return TRUE;
	}

	/**
	 * Renders the cache for online reading of documents. The result consists
	 * of various files - one for each chapter and section - which are stored
	 * in the document cache directory.
	 *
	 * @param	string		$documentDir: Absolute directory for the document currently being processed.
	 * @return	void
	 * @access	public
	 */
	public function transformDocBookToHtml ($documentDir) {

		$docBookDom = new DomDocument();
		$docBookDom->load($documentDir . 'docbook/manual.xml');

		if (!$docBookDom) {
			return FALSE;
		}

			// Avoid that relative path settings within the xsl files break
		chdir(t3lib_extMgm::extPath('ter_xsl') . 'Resources/Private/XSL/Docbook/xhtml/');

			// Transform the DocBook manual to XHTML into various files, each containing one chapter:
		$xsl = new DomDocument();
		$xsl->load(t3lib_extMgm::extPath ('ter_xsl') . 'Resources/Private/XSL/Docbook/xhtml/chunk.xsl');

			// Workaround for https://bugs.php.net/bug.php?id=54446
		if (version_compare(PHP_VERSION, '5.4', "<")) {
			ini_set("xsl.security_prefs", 0);
		} else {
			$xsl->setSecurityPreferences(0);
		}

		$xsltProc = new XsltProcessor();
		$xsltProc->setParameter ('','base.dir', $documentDir . 'html_online/');
		$xsltProc->setParameter ('','section.autolabel','1');
		$xsltProc->setParameter ('','section.label.includes.component.label','1');
		$xsltProc->setParameter ('','section.autolabel.max.depth','1');
		$xsltProc->setParameter ('','generate.toc', 'book nop');
		$xsltProc->setParameter ('','suppress.navigation','1');
		if ($xsltProc->hasExsltSupport()) {
			$xsltProc->setParameter ('','chunk.fast','1');
		}

		$oldErrorLevel = error_reporting();
		// set_error_handler(function ($int, $str) { Tx_TerXsl_Utility_Cli::log( $int . ':' . $str );  });
		error_reporting(E_ERROR);
		$xsl = $xsltProc->importStylesheet($xsl);
		$xsltProc->transformToDoc($docBookDom);
		error_reporting($oldErrorLevel);
	}

	/**
	 * Reset variable information. Necessary since this service is a singleton
	 *
	 * @return void
	 */
	protected function resetInformation() {
		$this->information = array();
	}

	/**
	 * Setter for variable information
	 *
	 * @param string $key
	 * @param string $value
	 * @return void
	 */
	protected function setInformation($key, $value) {
		$this->information[$key] = $value;
	}

	/**
	 * Getter for variable information
	 *
	 * @return array
	 */
	public function getInformation() {
		return $this->information;
	}


}

?>