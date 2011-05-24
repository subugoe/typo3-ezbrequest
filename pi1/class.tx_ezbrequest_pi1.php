<?php

/* * *************************************************************
 *  Copyright notice
 *
 *  (c) 2008 Marianna Mühlhölzer <mmuehlh@sub.uni-goettingen.de>
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

require_once(PATH_tslib . 'class.tslib_pibase.php');

/**
 * Plugin 'ezbrequest' for the 'ezbrequest' extension.
 *
 * @author	Marianna Mühlhölzer <mmuehlh@sub.uni-goettingen.de>
 * @package	TYPO3
 * @subpackage	tx_ezbrequest
 */
class tx_ezbrequest_pi1 extends tslib_pibase {

	var $prefixId = 'tx_ezbrequest_pi1';  // Same as class name
	var $scriptRelPath = 'pi1/class.tx_ezbrequest_pi1.php'; // Path to this script relative to the extension dir.
	var $extKey = 'ezbrequest'; // The extension key.
	var $pi_checkCHash = true;
	var $conf;
	var $baseParams;
	var $hitText = '';

	/**
	 * The main method contorls the data flow.
	 *
	 * @param	string		$content: The PlugIn content
	 * @param	array		$conf: The PlugIn configuration
	 * @return	The content that is displayed on the website
	 */
	function main ($content, $conf) {


		$this->init($conf);
		$this->pi_loadLL();
		$content = '';

		$listParams = $this->baseParams;
		$listParams['notation'] = $this->conf['notation'];
		$itemParams = $this->baseParams;

		$itemParams['xmloutput'] = '0';
		//complate params

		$listParams["sc"] = $_GET['sc'];
		$listParams["lc"] = $_GET['lc'];

		$listParamString = '';
		$itemParamString = '';
		foreach ($listParams as $key => $value) {
			$listParamString .= $key . '=';
			$listParamString .= $value . '&';
		}

		foreach ($itemParams as $key => $value) {
			$itemParamString .= $key . '=';
			$itemParamString .= $value . '&';
		}


		if ($_GET['jour_id']) {
			//######################### detailed item-view required #########################


			$xml = simplexml_load_file($this->conf['ezbItemURL'] . '?' . $_SERVER['QUERY_STRING']);

			$journal = $xml->ezb_detail_about_journal->journal;
			$institut = $this->pi_getLL('institut');
			$institut .= (string)$xml->library ? (string)$xml->library : $this->pi_getLL('none') . '; ';

			$headline = '<img alt="' . $journal->journal_color['color'] . '" width="30px" height="12" src="typo3conf/ext/ezbrequest/res/' . $journal->journal_color['color'] . '.gif" />' . "\n";

			// Alte Parameter sichern
			$oldATagParams = $GLOBALS['TSFE']->ATagParams;
			$GLOBALS['TSFE']->ATagParams = ' class="external-link-new-window" ';


			$headline .= $this->pi_linkToPage(htmlspecialchars($journal->title), $this->conf['ezbJourURL'] . '?' . str_replace('xmloutput=1', 'xmloutput=0', $_SERVER['QUERY_STRING']), '_blank', array());
			// gesicherten Status wiederherstellen:                                                                                                                 
			$GLOBALS['TSFE']->ATagParams = $oldATagParams;
			unset($oldATagParams);

			$itemTable = $this->createItemTable($journal, $listParams, $listParamString, $itemParams, $itemParamString);



			$this->templateCode = $this->cObj->fileResource($this->conf['itemViewTemplate']);
			$templateMarker = "###TEMPLATE###";

			$template = array();
			$template = $this->cObj->getSubpart($this->templateCode, $templateMarker);

			// create the content by replacing the marker in the template
			$markerArray = array(
				"###T3LANG###" => $GLOBALS["TSFE"]->sys_language_uid,
				"###JOURNALNAVI###" => '',
				"###NOTATION###" => $this->conf['notation'],
				"###USERIP###" => $this->baseParams['client_ip'],
				"###LANG###" => $lang[$GLOBALS["TSFE"]->sys_language_uid],
				"###HEADLINE###" => $headline,
				"###JOURITEM###" => $itemTable,
			);

			// build content from template + array
			$content = $this->cObj->substituteMarkerArrayCached($template, array(), $markerArray, array());
		}
		else {
		//######################### list-view required #########################
			$search = 0;
			if ($_GET['client_ip']) {
				$listParamString = $_SERVER[QUERY_STRING];
				$listParams = $_GET;
			}

			if ($_GET['jq_term1']) {

				$search = 1;


				//fetch search results
				$xml = simplexml_load_file($this->conf['ezbSearchURL'] . '?' . $listParamString . '&hits_per_page=100000');
				$institut = $this->pi_getLL('institut');
				$institut .= (string)$xml->library ? (string)$xml->library : $this->pi_getLL('none') . '; ';

				$result = $xml->ezb_alphabetical_list_searchresult;
				$hits = (string)$result->search_count;

				$list = $result->navlist->other_pages;

				$journalNode = $result;

				$current = (string)$xml->page_vars->sc['value'];
			}
			else {
				//fetch journal list
				$xml = simplexml_load_file($this->conf['ezbListURL'] . '?' . $listParamString);
				$institut = $this->pi_getLL('institut');
				$institut .= (string)$xml->library ? (string)$xml->library : $this->pi_getLL('none') . '; ';

				//find current page

				$currentEnd = (string)$xml->page_vars->lc['value'];

				//find xml node with navigation list
				$list = $xml->ezb_alphabetical_list->navlist->other_pages;

				//find node with journal list
				$journalNode = $xml->ezb_alphabetical_list;
				$currentPage = $journalNode->navlist->current_page;
			}
			if ($list != null) {
				$navi = $this->createNavi($list, $currentPage, $currentEnd, $listParams, $listParamString);
			}
			if (($search) && ($hits > 0 )) {
				$navi = '<span class="hits">' . $hits . $this->pi_getLL('hitText') . '</span> ' . $navi;
			}



			$journalList = $this->createList($journalNode, $listParams, $listParamString, $itemParams, $itemParamString);
			$this->templateCode = $this->cObj->fileResource($this->conf['listViewTemplate']);
			$templateMarker = "###TEMPLATE###";
			$template = array();
			$template = $this->cObj->getSubpart($this->templateCode, $templateMarker);

			// create the content by replacing the marker in the template
			$markerArray = array(
				"###T3LANG###" => $GLOBALS["TSFE"]->sys_language_uid,
				"###JOURNALNAVI###" => $navi,
				"###NOTATION###" => $this->conf['notation'],
				"###USERIP###" => $this->baseParams['client_ip'],
				"###LANG###" => $GLOBALS["TSFE"]->sys_language_uid,
				"###HEADLINE###" => '',
				"###JOURNALLIST###" => $journalList,
				"###INFO1###" => $institut,
				"###INFO2###" => $this->pi_getLL('ipText') . $this->baseParams['client_ip'],
			);


			$content = $this->cObj->substituteMarkerArrayCached($template, array(), $markerArray, array());
		}
		return $this->pi_wrapInBaseClass($content);
	}

	/**
	 * initializes the plugin: gets the settings from the flexform
	 *
	 * @param array $conf: array with the TS configuration
	 * @return void
	 */
	function init ($conf) {
		$this->conf = $conf;
		$this->pi_setPiVarDefaults();
		$this->pi_loadLL();
		$this->pi_initPIflexForm();

		//set css
		if ($this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'css', 'sDEF')) {
			$this->conf['css'] = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'css', 'sDEF');
		}
		$GLOBALS['TSFE']->additionalHeaderData[1] = '<link rel="stylesheet" type="text/css" href="' . $this->conf['css'] . '" media="screen" />';

		//set js
		//$GLOBALS['TSFE']->additionalHeaderData[2] = '<script type="text/javascript" href="fileadmin/js/ezb.js" />';
		//templates
		if ($this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'itemViewTemplate', 'sDEF')) {
			$this->conf['itemViewTemplate'] = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'itemViewTemplate', 'sDEF');
		}

		if ($this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'listViewTemplate', 'sDEF')) {
			$this->conf['listViewTemplate'] = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'listViewTemplate', 'sDEF');
		}
		//init params

		$lang = array(
			0 => 'de',
			1 => 'en',
		);

		$this->conf['currentPage'] = $GLOBALS['TSFE']->id;
		$this->conf['currentPageLink'] = $this->pi_getPageLink($GLOBALS['TSFE']->id);

		if ($this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'notation', 'sDEF')) {
			$this->conf['notation'] = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'notation', 'sDEF');
		}

		$listTarget = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'listTarget', 'sDEF');
		$this->conf['listTarget'] = $listTarget ? $listTarget : $this->conf['currentPage'];

		$itemTarget = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'itemTarget', 'sDEF');
		$this->conf['itemTarget'] = $itemTarget ? $itemTarget : $this->conf['currentPage'];


		//set base parameter
		$userIp = t3lib_div::getIndpEnv('REMOTE_ADDR');

		$baseParams = array(
			'L' => $GLOBALS["TSFE"]->sys_language_uid,
			'notation' => $this->conf['notation'],
			'client_ip' => $userIp,
			'xmloutput' => '1',
			'colors' => '7',
			'lang' => $lang[$GLOBALS["TSFE"]->sys_language_uid],
		);
		$this->baseParams = $baseParams;

		$htmlBaseParamString = 'notation=' . $this->conf['notation'] . '&client_ip=' . $userIp . '&lang=' . $lang . '&colors=7';

		$letterLinks = "";
		$infoLinks = "";
		$search = 0;
	}

	/**
	 * Traverses  the top-node of the EZB journal navigation list  and generate a linked alphabetical navigation list
	 *
	 * @param SimpleXMLElement	$node: xml-node with journal list navigation nodes
	 * @param string			$currentNode: name of the current list page
	 * @param array			$params: navigation list parameters
	 * @param string			$paramString: the same as $params as string
	 * @return string		$letterLinks: linked navigation list as HTML-snippet
	 */
	function createNavi ($node, $currentPage, $currentPageEnd, $params, $paramString) {

		$letterLinks = '';
		$params['sindex'] = 0;
		$paramString = preg_replace('/sindex=\d*/', 'sindex=0', $paramString);

		if ($currentPage == "A") {
			$listParams['lc'] = "B";
			$letterLinks .= '<span class="act">';
			$letterLinks .= $this->pi_linkToPage($currentPage, $this->conf['listTarget'], '', $params);
			$letterLinks .= '</span>&nbsp;';
		}

		foreach ($node as $pages) {
			$params["sc"] = (String)$pages["sc"];
			$params["lc"] = (String)$pages["lc"];
			$label = (string)$pages;
			$letterLinks .= $this->pi_linkToPage($label, $this->conf['listTarget'], '', $params);
			if ($currentPage[0] == (string)$pages["lc"]) {
				$letterLinks .= '<span class="act">';
				$letterLinks .= $this->pi_linkToPage($currentPage, $this->conf['listTarget'] . '' . $params);
				$letterLinks .= '</span>';
			}
		}
		return $letterLinks;
	}

	/**
	 * Traverses  xml nodes of journal list and generates a linked list of journals with access information
	 *
	 * @param SimpleXMLElement       $node: xml-node of journal list navigation nodes
	 * @param array                  $listParams: parameters for journal list request
	 * @param string                 $listParamString: $listParams as string
	 * @param array                  $itemParams: parameters for journal details request
	 * @param string			$itemParamString: $itemParams as string
	 * @return string                $journalLinks: linked list of journals as HTML-snippet
	 */
	function createList ($node, $listParams, $listParamString, $itemParams, $itemParamString) {

		$first = $node->first_fifty;
		$journals = $node->alphabetical_order;
		$listParamString = $_SERVER['QUERY_STRING'];
		$listParams = isset($_GET['client_ip']) ? $_GET : $this->baseParams;
		$firstList = '';
		$journalLinks = '';

		if ($first != null) {

			foreach ($first as $firstlink) {
				$label = '&raquo;&nbsp;' . $firstlink->first_fifty_titles;
				$listParams['sc'] = (String)$firstlink['sc'];
				$listParams['lc'] = (String)$firstlink["lc"];
				$listParams['sindex'] = (String)$firstlink["sindex"];
				$firstList .= $this->pi_linkToPage($label, $this->conf['listTarget'], '', $listParams) . '<br />';
			}
		}

		$journalLinks .= '<div class="firstlist">' . $firstList . '</div>';

		$next = $node->next_fifty;
		if ($next != null) {

			$nextList = '';
			foreach ($next as $nextlink) {
				$label = '&raquo;&nbsp;' . $nextlink->next_fifty_titles;

				$listParams['sc'] = (String)$nextlink['sc'];

				$listParams['lc'] = (String)$nextlink['lc'];
				$listParams['sindex'] = (String)$nextlink['sindex'];
				$nextList .= $this->pi_linkToPage($label, $this->conf['listTarget'], '', $listParams) . '<br />';
			}
		}

		foreach ($journals->journals->journal as $journal) {
			$access = $journal->journal_color['color'];
			$image = '<img alt="' . $access . '" width="30px" height="12" src="typo3conf/ext/ezbrequest/res/' . $access . '.gif" />';
			$itemParams["jour_id"] = (string)$journal['jourid'];
			$itemParams["xmloutput"] = "0";

			$journalLinks .= $this->pi_linkToPage($image, $this->conf['ezbItemURL'] . '?' . $itemParamString . 'jour_id=' . (string)$journal['jourid'], '', array());
			$title = (string)$journal->title;
			$itemParams["xmloutput"] = "1";
			$journalLinks .= $this->pi_linkToPage(htmlspecialchars($title), $this->conf['itemTarget'], '', $itemParams) . "<br />";
		}
		$journalLinks .= '<br />' . $nextList;
		return $journalLinks;
	}

	/**
	 * Traverses  xml node with journal details and generates a table
	 *
	 * @param SimpleXMLElement       $journal: xml-node with journal details
	 * @param array                  $listParams: parameters for journal list request
	 * @param string                 $listParamString: $listParams as string
	 * @param array                  $itemParams: parameters for journal details request
	 * @param string                 $itemParamString: $itemParams as string
	 * @return string                $itemTable: table with journal details as HTML-snippet
	 */
	function createItemTable ($journal, $listParams, $listParamString, $itemParam, $itemParamString) {


		$itemDetails = array();

		//traverse xml for creating detailed item table 
		$itemDetails["publisher"] = $journal->detail->publisher;
		if ($journal->detail->E_ISSNs->E_ISSN) {
			foreach ($journal->detail->E_ISSNs->E_ISSN as $issn) {
				$moreValues .= $issn . '<br/>';
			}
			$itemDetails['E_ISSN'] = $moreValues;
			$moreValues = "";
		}
		if ($journal->detail->P_ISSNs->P_ISSN) {
			foreach ($journal->detail->P_ISSNs->P_ISSN as $pissn) {
				$moreValues .= $pissn . '<br/>';
			}
			$itemDetails['P_ISSN'] = $moreValues;

			/* Alte Parameter sichern */
			$oldATagParams = $GLOBALS['TSFE']->ATagParams;
			$GLOBALS['TSFE']->ATagParams = ' class="external-link-new-window" ';
			$itemDetails['ZDB_number'] = $this->pi_linkToPage($journal->detail->ZDB_number, $journal->detail->ZDB_number["url"], '_blank', $empty);
			// gesicherten Status wiederherstellen:
			$GLOBALS['TSFE']->ATagParams = $oldATagParams;
			unset($oldATagParams);

			$moreValues = "";
		}
		if ($journal->detail->subjects->subject) {
			foreach ($journal->detail->subjects->subject as $subject) {
				$moreValues .= $subject . '; ';
			}
			$itemDetails['subject'] = $moreValues;
			$moreValues = "";
		}
		if ($journal->detail->keywords->keyword) {
			foreach ($journal->detail->keywords->keyword as $keyword) {
				$moreValues .= $keyword . '; ';
			}
			$itemDetails['keyword'] = $moreValues;
			$moreValues = "";
		}

		if ($journal->detail->fulltext) {

			/* Alte Parameter sichern */
			$oldATagParams = $GLOBALS['TSFE']->ATagParams;
			$GLOBALS['TSFE']->ATagParams = ' class="external-link-new-window" ';

			$itemDetails['fulltext'] = $this->pi_linkToPage(substr($journal->detail->fulltext, 0, 50) . '...', $journal->detail->fulltext, '_blank', $empty);

			// gesicherten Status wiederherstellen:
			$GLOBALS['TSFE']->ATagParams = $oldATagParams;
			unset($oldATagParams);
		}
		if ($journal->detail->homepages->homepage) {
			$extParam = array();
			foreach ($journal->detail->homepages->homepage as $homepage) {
				/* Alte Parameter sichern */
				$oldATagParams = $GLOBALS['TSFE']->ATagParams;

				$GLOBALS['TSFE']->ATagParams = ' class="external-link-new-window" ';


				if (strlen($homepage) > 50) {
					$moreValues .= $this->pi_linkToPage(substr($homepage, 0, 50) . '...', $homepage, '_blank', $empty) . '<br/>';
				}
				else {
					$moreValues .= $this->pi_linkToPage($homepage, $homepage, '_blank', $empty) . '<br/>';
				}

				// gesicherten Status wiederherstellen:
				$GLOBALS['TSFE']->ATagParams = $oldATagParams;
				unset($oldATagParams);
			}
			$itemDetails['homepage'] = $moreValues;
			$moreValues = "";
		}

		if ($journal->detail->first_fulltext_issue) {
			if ($journal->detail->first_fulltext_issue->first_volume) {
				$moreValues .= 'Vol. ' . $journal->detail->first_fulltext_issue->first_volume;
			}
			if ($journal->detail->first_fulltext_issue->first_issue) {
				$moreValues .= ', ' . $journal->detail->first_fulltext_issue->first_issue;
			}
			if ($journal->detail->first_fulltext_issue->first_date) {
				$moreValues .= ' (' . $journal->detail->first_fulltext_issue->first_date . ')';
			};
			$itemDetails['first_fulltext_issue'] = $moreValues;
			$moreValues = "";
		}

		if ($journal->detail->last_fulltext_issue) {
			if ($journal->detail->last_fulltext_issue->last_volume) {
				$moreValues .= 'Vol. ' . $journal->detail->last_fulltext_issue->last_volume;
			}
			if ($journal->detail->last_fulltext_issue->last_issue) {
				$moreValues.= ', ' . $journal->detail->last_fulltext_issue->last_issue;
			}
			if ($journal->detail->last_fulltext_issue->last_date) {
				$moreValues .= ' (' . $journal->detail->last_fulltext_issue->last_date . ')';
			};
			$itemDetails['last_fulltext_issue'] = $moreValues;
			$moreValues = "";
		}

		if ($journal->detail->appearence) {
			$itemDetails['appearence'] = $journal->detail->appearence;
		}
		if ($journal->detail->costs) {
			$itemDetails['costs'] = $journal->detail->costs;
		}
		if ($journal->detail->remarks) {
			$itemDetails['remarks'] = $journal->detail->remarks;
		}

		//create table (with item details) now
		$itemTable = '<table>';
		foreach ($itemDetails as $key => $value) {
			$itemTable .= '<tr><td><b>' . $this->pi_getLL($key) . '</b></td><td>';
			$itemTable .= $value . '</td></tr>';
		}
		$itemTable .= '</table>';
		return $itemTable;
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/ezbrequest/pi1/class.tx_ezbrequest_pi1.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/ezbrequest/pi1/class.tx_ezbrequest_pi1.php']);
}
?>
