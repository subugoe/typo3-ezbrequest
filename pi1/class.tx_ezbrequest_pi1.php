
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

/**
 * Plugin 'ezbrequest' for the 'ezbrequest' extension.
 */
class tx_ezbrequest_pi1 extends \TYPO3\CMS\Frontend\Plugin\AbstractPlugin {

	/**
	 * @var string
	 */
	public $prefixId = 'tx_ezbrequest_pi1';

	/**
	 * @var string
	 */
	public $scriptRelPath = 'pi1/class.tx_ezbrequest_pi1.php';

	/**
	 * @var string
	 */
	public $extKey = 'ezbrequest';

	/**
	 * @var bool
	 */
	public $pi_checkCHash = true;

	/**
	 * @var array
	 */
	public $conf;

	/**
	 * @var array
	 */
	protected $baseParams;

	/**
	 * @var string
	 */
	protected $hitText = '';

	/**
	 * @var string
	 */
	protected $templateCode;

	/**
	 * The main method controls the data flow.
	 *
	 * @param string $content : The PlugIn content
	 * @param array $conf : The PlugIn configuration
	 * @return string The content that is displayed on the website
	 */
	public function main($content, $conf) {
		$this->init($conf);
		$this->pi_loadLL();
		$content = '';

		$listParams = $this->baseParams;
		$listParams['notation'] = $this->conf['notation'];
		$listParams = array_merge($listParams, $_GET);
		$listParams['lang'] = $GLOBALS['TSFE']->lang; // overwrite language setting (for language switch)

		$itemParams = $this->baseParams;
		$itemParams['xmloutput'] = '0';

		if ($_GET['jour_id']) {
			$content = $this->getDetailView();
		} else {
			$content = $this->getListView($listParams, $itemParams);
		}
		return $this->pi_wrapInBaseClass($content);
	}


	/**
	 * initializes the plugin: gets the settings from the flexform
	 *
	 * @param array $conf : array with the TS configuration
	 * @return void
	 */
	protected function init($conf) {
		$this->conf = $conf;
		$this->pi_setPiVarDefaults();
		$this->pi_loadLL();
		$this->pi_initPIflexForm();
		$this->addCss();

		//templates
		$this->initializeTemplates();

		//init params
		$this->conf['currentPage'] = $GLOBALS['TSFE']->id;
		$this->conf['currentPageLink'] = $this->pi_getPageLink($GLOBALS['TSFE']->id);

		if ($this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'notation', 'sDEF')) {
			$this->conf['notation'] = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'notation', 'sDEF');
		}

		$listTarget = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'listTarget', 'sDEF');
		$this->conf['listTarget'] = $listTarget ? $listTarget : $this->conf['currentPage'];

		$itemTarget = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'itemTarget', 'sDEF');
		$this->conf['itemTarget'] = $itemTarget ? $itemTarget : $this->conf['currentPage'];

		$bibID = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'bibid', 'sDEF');
		$this->conf['bibid'] = $bibID ? $bibID : $this->conf['bibid'];


		//set base parameter
		$this->baseParams = array(
			'L' => $GLOBALS["TSFE"]->sys_language_uid,
			'notation' => $this->conf['notation'],
			'xmloutput' => '1',
			'colors' => '7',
			'lang' => $GLOBALS['TSFE']->lang,
		);
		if ($this->conf['bibid']) {
			$this->baseParams['bibid'] = $this->conf['bibid'];
			if ($this->conf['bibid'] == 'NATLI') {
				$this->baseParams['colors'] = 2;
			}
		} else {
			$this->baseParams['client_ip'] = \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('REMOTE_ADDR');
		}

	}


	/**
	 * Uses $params array to create a query string.
	 * Take into account that the 'notation' setting requires special treatment
	 * for different kinds of queries and depending on the number of notations.
	 *
	 * @param array $params
	 * @param int $mode - one of    0: internal link,
	 *                            1: link for EZB list query
	 *                            2: link for EZB search query
	 * @return string
	 */
	protected function paramString($params, $mode = 0) {
		$string = '';

		foreach ($params as $name => $value) {
			if ($name === 'notation' && $mode > 0) {
				if (strpos($value, ',') === False && $mode == 1) {
					$string .= $name . '=' . $value . '&';
				} else {
					$notations = explode(',', $value);
					foreach ($notations as $notation) {
						$string .= 'Notations[]=' . $notation . '&';
					}
				}
			} else {
				$string .= $name . '=' . $value . '&';
			}
		}

		return $string;
	}


	/**
	 * Traverses  the top-node of the EZB journal navigation list  and generate a linked alphabetical navigation list
	 *
	 * @param SimpleXMLElement $node : xml-node with journal list navigation nodes
	 * @param string $currentPage : name of the current list page
	 * @param string $currentPageEnd
	 * @param array $params : navigation list parameters
	 * @return string        $letterLinks: linked navigation list as HTML-snippet
	 */
	protected function createNavi($node, $currentPage, $currentPageEnd, $params) {
		$letterLinks = "<ul class='alphabetMenu'>";
		$params['sindex'] = 0;

		foreach ($node as $pages) {
			if ($pages->getName() == 'current_page') {
				$letterLinks .= '<li class="act">' . $currentPage . "</li>";
			} else {
				$params["sc"] = (String)$pages["sc"];
				$params["lc"] = (String)$pages["lc"];
				$label = (string)$pages;
				$letterLinks .= "<li>" . $this->pi_linkToPage($label, $this->conf['listTarget'], '', $params) . "</li>";
			}
		}

		$letterLinks .= "</ul>";
		return $letterLinks;
	}


	/**
	 * Traverses  xml nodes of journal list and generates a linked list of journals with access information
	 *
	 * @param \SimpleXMLElement $node : xml-node of journal list navigation nodes
	 * @param array $listParams : parameters for journal list request
	 * @param array $itemParams : parameters for journal details request
	 * @return string               $journalLinks: linked list of journals as HTML-snippet
	 */
	protected function createList($node, $listParams, $itemParams) {

		$first = $node->first_fifty;
		$journals = $node->alphabetical_order;
		$listParams = isset($_GET['client_ip']) ? $_GET : $this->baseParams;
		$journalLinks = '';

		if ($first != null) {
			$firstList = '';
			foreach ($first as $firstlink) {
				$label = '&laquo;&nbsp;' . $firstlink->first_fifty_titles;
				$listParams['sc'] = (String)$firstlink['sc'];
				$listParams['lc'] = (String)$firstlink["lc"];
				$listParams['sindex'] = (String)$firstlink["sindex"];
				$firstList .= '<li>' . $this->pi_linkToPage($label, $this->conf['listTarget'], '', $listParams) . "</li>";
			}
			$journalLinks .= '<ul class="firstlist">' . $firstList . "</ul>";
		}

		if ($journals->journals) {
			$journalLinks .= '<ul>';
			foreach ($journals->journals->journal as $journal) {
				$access = $journal->journal_color['color'];
				$image = '<img alt="' . $access . '" width="30px" height="12" src="' . \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::siteRelPath('ezbrequest') . 'Resources/Public/Images/' . $access . '.gif" />';

				$itemParams["jour_id"] = (string)$journal['jourid'];
				$itemParams["xmloutput"] = "0";
				$journalLinks .= '<li><span class="ampel"><a href="' . $this->conf['ezbItemURL'] . '?' . $this->paramString($itemParams) . '">' . $image . '</span>';

				$title = (string)$journal->title;
				$itemParams["xmloutput"] = "1";
				$journalLinks .= $this->pi_linkToPage(htmlspecialchars($title), $this->conf['itemTarget'], '', $itemParams) . "</li>";
			}
			$journalLinks .= "</ul>";
		}

		$next = $node->next_fifty;
		if ($next != null) {
			$nextList = '';
			foreach ($next as $nextlink) {
				$label = '&raquo;&nbsp;' . $nextlink->next_fifty_titles;
				$listParams['sc'] = (String)$nextlink['sc'];
				$listParams['lc'] = (String)$nextlink['lc'];
				$listParams['sindex'] = (String)$nextlink['sindex'];
				$nextList .= '<li>' . $this->pi_linkToPage($label, $this->conf['listTarget'], '', $listParams) . "</li>";
			}
			$journalLinks .= '<ul class="nextlist">' . $nextList . "</ul>";
		}

		return $journalLinks;
	}


	/**
	 * Traverses  xml node with journal details and generates a table
	 *
	 * @param \SimpleXMLElement $journal : xml-node with journal details
	 * @return string $itemTable: table with journal details as HTML-snippet
	 */
	protected function createItemTable($journal) {
		$itemDetails = array();
		$empty = '';
		//traverse xml for creating detailed item table
		if ($journal->periods->period) {
			$periods = Array("<ul class='ezbrequest-periods'>");
			foreach ($journal->periods->period as $period) {
				$label = 'Link';
				if ($period->label) {
					$label = (string)$period->label;
				}
				$link = rawurldecode((string)$period->warpto_link['url']);
				$image = '<img alt="' . $period->journal_color['color'] . '" width="30px" height="12" src="' . \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::siteRelPath('ezbrequest') . 'Resources/Public/Images/' . $period->journal_color['color'] . '.gif" />' . "\n";
				$periods[] = '<li>' . '<a href="' . $link . '" class="external-link-new-window" target="_blank">' . $image . ' ' . $label . "</a></li>";
			}
			$periods[] = "</ul>";
			$itemDetails['availability'] = implode('', $periods);
		}

		$itemDetails["publisher"] = $journal->detail->publisher;

		$ISSNs = Array();
		if ($journal->detail->E_ISSNs->E_ISSN) {
			foreach ($journal->detail->E_ISSNs->E_ISSN as $eissn) {
				$ISSNs[] = $eissn . ' (' . $this->pi_getLL('electronic') . ')';
			}
		}

		if ($journal->detail->P_ISSNs->P_ISSN) {
			foreach ($journal->detail->P_ISSNs->P_ISSN as $pissn) {
				$ISSNs[] = $pissn . ' (' . $this->pi_getLL('printed') . ')';
			}
		}

		if (count($ISSNs) > 0) {
			$itemDetails['ISSN'] = implode(', ', $ISSNs);
		}

		if ($journal->detail->ZDB_number) {
			/* Alte Parameter sichern */
			$oldATagParams = $GLOBALS['TSFE']->ATagParams;
			$GLOBALS['TSFE']->ATagParams = ' class="external-link-new-window" ';
			$itemDetails['ZDB_number'] = $this->pi_linkToPage($journal->detail->ZDB_number, $journal->detail->ZDB_number["url"], '_blank', $empty);
			// gesicherten Status wiederherstellen:
			$GLOBALS['TSFE']->ATagParams = $oldATagParams;
			unset($oldATagParams);
		}

		if ($journal->detail->subjects->subject) {
			$subjects = Array();
			foreach ($journal->detail->subjects->subject as $subject) {
				$subjects[] = (string)$subject;
			}
			$itemDetails['subject'] = implode('; ', $subjects);
		}

		if ($journal->detail->keywords->keyword) {
			$keywords = Array();
			foreach ($journal->detail->keywords->keyword as $keyword) {
				$keywords[] = (string)$keyword;
			}
			$itemDetails['keyword'] = implode('; ', $keywords);
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
			$moreValues = "";
			foreach ($journal->detail->homepages->homepage as $homepage) {
				/* Alte Parameter sichern */
				$oldATagParams = $GLOBALS['TSFE']->ATagParams;

				$GLOBALS['TSFE']->ATagParams = ' class="external-link-new-window" ';

				if (strlen($homepage) > 50) {
					$moreValues .= $this->pi_linkToPage(substr($homepage, 0, 50) . '...', $homepage, '_blank', $empty) . '<br/>';
				} else {
					$moreValues .= $this->pi_linkToPage($homepage, $homepage, '_blank', $empty) . '<br/>';
				}

				// gesicherten Status wiederherstellen:
				$GLOBALS['TSFE']->ATagParams = $oldATagParams;
				unset($oldATagParams);
			}
			$itemDetails['homepage'] = $moreValues;
		}

		if ($journal->detail->first_fulltext_issue) {
			$moreValues = "";
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
		}

		if ($journal->detail->last_fulltext_issue) {
			$moreValues = "";
			if ($journal->detail->last_fulltext_issue->last_volume) {
				$moreValues .= 'Vol. ' . $journal->detail->last_fulltext_issue->last_volume;
			}
			if ($journal->detail->last_fulltext_issue->last_issue) {
				$moreValues .= ', ' . $journal->detail->last_fulltext_issue->last_issue;
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

	protected function addCss() {
		/** @var \TYPO3\CMS\Core\Page\PageRenderer $pageRenderer */
		$pageRenderer = $GLOBALS['TSFE']->getPageRenderer();
		$pageRenderer->addCssFile(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::siteRelPath('ezbrequest') . 'Resources/Public/Css/ezb.css');
	}

	protected function initializeTemplates() {
		if ($this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'itemViewTemplate', 'sDEF')) {
			$this->conf['itemViewTemplate'] = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'itemViewTemplate', 'sDEF');
		}

		if ($this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'listViewTemplate', 'sDEF')) {
			$this->conf['listViewTemplate'] = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'listViewTemplate', 'sDEF');
		}
	}

	/**
	 * @return string
	 */
	protected function getDetailView() {
		$xml = simplexml_load_file($this->conf['ezbItemURL'] . '?' . $_SERVER['QUERY_STRING']);

		$journal = $xml->ezb_detail_about_journal->journal;
		$institut = $this->pi_getLL('institut');
		$institut .= ((string)$xml->library ? (string)$xml->library : $this->pi_getLL('none')) . '; ';

		$headline = '<img alt="' . $journal->journal_color['color'] . '" width="30px" height="12" src="' . \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::siteRelPath('ezbrequest') . 'Resources/Public/Images/' . $journal->journal_color['color'] . '.gif" />' . "\n";

		$headline .= '<a href="' . $this->conf['ezbJourURL'] . '?' . '?' . str_replace('xmloutput=1', 'xmloutput=0', $_SERVER['QUERY_STRING']) . ' target="_blank">' . htmlspecialchars($journal->title) . '</a>';
		// gesicherten Status wiederherstellen:
		$itemTable = $this->createItemTable($journal);

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
			"###LANG###" => $GLOBALS['TSFE']->lang,
			"###HEADLINE###" => $headline,
			"###JOURITEM###" => $itemTable,
			"###SEARCHTERM###" => ""
		);

		// build content from template + array
		$content = $this->cObj->substituteMarkerArrayCached($template, array(), $markerArray, array());
		return $content;
	}

	/**
	 * @param $listParams
	 * @param $itemParams
	 * @return string
	 */
	protected function getListView($listParams, $itemParams) {
		$search = 0;

		if ($_GET['jq_term1']) {
			$search = 1;
			//fetch search results
			$URL = $this->conf['ezbSearchURL'] . '?' . $this->paramString($listParams, 2) . 'hits_per_page=100000';
			$xml = simplexml_load_file($URL);
			$institut = $this->pi_getLL('institut');
			$institut .= ((string)$xml->library ? (string)$xml->library : $this->pi_getLL('none')) . '; ';

			$result = $xml->ezb_alphabetical_list_searchresult;
			$hits = (string)$result->search_count;

			$list = $result->navlist->other_pages;

			$journalNode = $result;

		} else {
			//fetch journal list
			$URL = '';
			if (strpos($listParams['notation'], ',') === False) {
				$URL = $this->conf['ezbListURL'] . '?' . $this->paramString($listParams, 1);
			} else {
				$URL = $this->conf['ezbSearchURL'] . '?' . $this->paramString($listParams, 2);
			}
			$xml = simplexml_load_file($URL);
			$institut = $this->pi_getLL('institut');
			$institut .= ((string)$xml->library ? (string)$xml->library : $this->pi_getLL('none')) . '; ';

			//find current page
			$currentEnd = (string)$xml->page_vars->lc['value'];

			//find xml node with navigation list
			$list = $xml->xpath('//navlist/other_pages|//navlist/current_page');

			//find node with journal list
			$listNodes = $xml->xpath('ezb_alphabetical_list|ezb_alphabetical_list_searchresult');
			$journalNode = $listNodes[0];
			$currentPage = $journalNode->navlist->current_page;
		}
		if ($list != null) {
			$navi = $this->createNavi($list, $currentPage, $currentEnd, $listParams);
		}
		if (($search) && ($hits > 0)) {
			$navi = '<span class="hits">' . $hits . $this->pi_getLL('hitText') . '</span> ' . $navi;
		}

		$journalList = $this->createList($journalNode, $listParams, $itemParams);
		$this->templateCode = $this->cObj->fileResource($this->conf['listViewTemplate']);
		$templateMarker = "###TEMPLATE###";
		$template = $this->cObj->getSubpart($this->templateCode, $templateMarker);

		// create the content by replacing the marker in the template
		$markerArray = array(
			"###T3LANG###" => $GLOBALS["TSFE"]->sys_language_uid,
			"###JOURNALNAVI###" => $navi,
			"###NOTATION###" => $this->conf['notation'],
			"###USERIP###" => $this->baseParams['client_ip'],
			"###LANG###" => $GLOBALS['TSFE']->lang,
			"###HEADLINE###" => '',
			"###JOURNALLIST###" => $journalList,
			"###INFO1###" => $institut,
			"###INFO2###" => $this->pi_getLL('ipText') . $this->baseParams['client_ip'],
			"###SEARCHTERM###" => $_GET['jq_term1']
		);

		$content = $this->cObj->substituteMarkerArrayCached($template, array(), $markerArray, array());
		return $content;
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/ezbrequest/pi1/class.tx_ezbrequest_pi1.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/ezbrequest/pi1/class.tx_ezbrequest_pi1.php']);
}
