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
class tx_ezbrequest_pi1 extends \TYPO3\CMS\Frontend\Plugin\AbstractPlugin
{

    const LINK_INTERNAL = 0;
    const LINK_EZB_LIST_QUERY = 1;
    const LINK_EZB_SEARCH_QUERY = 2;

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
     * The main method controls the data flow.
     *
     * @param string $content : The PlugIn content
     * @param array $conf : The PlugIn configuration
     * @return string The content that is displayed on the website
     */
    public function main($content, $conf)
    {
        $this->init($conf);

        $listParams = $this->baseParams;
        $listParams['notation'] = $this->conf['notation'];
        $listParams = array_merge($listParams, \TYPO3\CMS\Core\Utility\GeneralUtility::_GET());

        // overwrite language setting (for language switch)
        $listParams['lang'] = $GLOBALS['TSFE']->lang;

        $itemParams = $this->baseParams;
        $itemParams['xmloutput'] = '0';

        if (\TYPO3\CMS\Core\Utility\GeneralUtility::_GET('jour_id')) {
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
    protected function init($conf)
    {
        $this->conf = $conf;
        $this->pi_setPiVarDefaults();
        $this->pi_initPIflexForm();

        //init params
        $this->conf['currentPage'] = $GLOBALS['TSFE']->id;

        if ($this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'notation', 'sDEF')) {
            $this->conf['notation'] = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'notation', 'sDEF');
        }

        $this->addCss()
            ->setListTarget()
            ->setItemTarget()
            ->setInstitutionIdentifier()
            ->setBaseParameter()
            ->determineClientInstitution();
    }

    /**
     * @return $this
     */
    protected function addCss()
    {
        /** @var \TYPO3\CMS\Core\Page\PageRenderer $pageRenderer */
        $pageRenderer = $GLOBALS['TSFE']->getPageRenderer();
        $pageRenderer->addCssFile(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::siteRelPath('ezbrequest') . 'Resources/Public/Css/ezb.css');
        return $this;
    }

    /**
     * @return string
     */
    protected function getDetailView()
    {
        $xml = \simplexml_load_file($this->conf['ezbItemURL'] . '?' . $_SERVER['QUERY_STRING']);

        /** @var \TYPO3\CMS\Fluid\View\StandaloneView $fluidTemplate */
        $fluidTemplate = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Fluid\View\StandaloneView::class);
        $fluidTemplate->setTemplatePathAndFilename(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('ezbrequest') . 'Resources/Private/Templates/Single.html');

        $journal = $xml->ezb_detail_about_journal->journal;
        $itemTable = $this->createItems($journal);

        $fluidTemplate->assignMultiple([
            'headline' => htmlspecialchars($journal->title),
            'access' => $journal->journal_color['color'],
            'notation' => $this->conf['notation'],
            'userIp' => $this->baseParams['client_ip'],
            'linkUri' => $this->conf['ezbJourURL'] . '?' . '?' . str_replace('xmloutput=1', 'xmloutput=0',
                    $_SERVER['QUERY_STRING']),
            'journalItem' => $itemTable,
            'language' => [
                'text' => $GLOBALS['TSFE']->lang,
                'id' => $GLOBALS['TSFE']->sys_language_uid
            ]
        ]);

        return $fluidTemplate->render();
    }

    /**
     * Traverses xml node with journal details
     *
     * @param \SimpleXMLElement $journal xml-node with journal details
     * @return array array with journal details
     */
    protected function createItems($journal)
    {
        $itemDetails = [];

        $itemDetails['availability'] = $this->getPeriods($journal);
        $itemDetails['publisher'] = $journal->detail->publisher;
        $itemDetails['ISSN'] = $this->getIssn($journal);
        $itemDetails['ZDB_number'] = $this->getZdbNumber($journal);
        $itemDetails['subject'] = $this->getSubject($journal);
        $itemDetails['keyword'] = $this->getKeywords($journal);
        $itemDetails['fulltext'] = $this->getFullText($journal);
        $itemDetails['homepage'] = $this->getHomepage($journal);
        $itemDetails['first_fulltext_issue'] = $this->getFirstFulltextIssue($journal);
        $itemDetails['last_fulltext_issue'] = $this->getLastFulltextIssue($journal);
        $itemDetails['appearence'] = $this->getAppearance($journal);
        $itemDetails['costs'] = $this->getCosts($journal);
        $itemDetails['remarks'] = $this->getRemarks($journal);

        return array_filter($itemDetails);
    }

    /**
     * @param $listParams
     * @param $itemParams
     * @return string
     */
    protected function getListView($listParams, $itemParams)
    {

        /** @var \TYPO3\CMS\Fluid\View\StandaloneView $fluidTemplate */
        $fluidTemplate = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Fluid\View\StandaloneView::class);
        $fluidTemplate->setTemplatePathAndFilename(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('ezbrequest') . 'Resources/Private/Templates/List.html');

        $search = empty(\TYPO3\CMS\Core\Utility\GeneralUtility::_GET('jq_term1')) ? false : true;

        $navi = '';

        if ($search) {
            //fetch search results
            $URL = $this->conf['ezbSearchURL'] . '?' . $this->paramString($listParams,
                    self::LINK_EZB_SEARCH_QUERY) . 'hits_per_page=100000';
            $xml = simplexml_load_file($URL);

            /** @var \SimpleXMLElement $result */
            $result = $xml->ezb_alphabetical_list_searchresult;
            $hits = (int)$result->search_count;

            $list = $result->navlist->other_pages;

            $journalNode = $result;

        } else {
            $xml = $this->fetchJournalList($listParams);

            //find xml node with navigation list
            $list = $xml->xpath('//navlist/other_pages|//navlist/current_page');

            //find node with journal list
            $listNodes = $xml->xpath('ezb_alphabetical_list|ezb_alphabetical_list_searchresult');
            $journalNode = $listNodes[0];
            $currentPage = $journalNode->navlist->current_page;
        }
        if ($list != null && isset($currentPage)) {
            $navi = $this->createNavi($list, $currentPage, $listParams);
        }

        if (($search) && isset($hits) && ($hits > 0)) {
            $navi = '<span class="hits">' . $hits . ' ' . \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('hitText',
                    'ezbrequest') . '</span> ' . $navi;
        }

        $institut = ((string)$xml->library ? (string)$xml->library : $this->pi_getLL('none')) . '; ';

        $journalList = $this->createList($journalNode, $itemParams);

        $fluidTemplate->assignMultiple([
            'institute' => $institut,
            'journalList' => $journalList,
            'journalNavigation' => $navi,
            'notation' => $this->conf['notation'],
            'searchTerm' => \TYPO3\CMS\Core\Utility\GeneralUtility::_GET('jq_term1'),
            'ipAddress' => $this->baseParams['client_ip'],
            'language' => [
                'text' => $GLOBALS['TSFE']->lang,
                'id' => $GLOBALS['TSFE']->sys_language_uid
            ]
        ]);

        return $fluidTemplate->render();
    }

    /**
     * Uses $params array to create a query string.
     * Take into account that the 'notation' setting requires special treatment
     * for different kinds of queries and depending on the number of notations.
     *
     * @param array $params
     * @param int $mode - one of  0: internal link,
     *                            1: link for EZB list query
     *                            2: link for EZB search query
     * @return string
     */
    protected function paramString($params, $mode = 0)
    {
        $string = '';

        foreach ($params as $name => $value) {
            if ($name === 'notation' && $mode > 0) {
                if (strpos($value, ',') === false && $mode === self::LINK_EZB_LIST_QUERY) {
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
     * @param array $node : xml-node with journal list navigation nodes
     * @param string $currentPage : name of the current list page
     * @param array $params : navigation list parameters
     * @return string        $letterLinks: linked navigation list as HTML-snippet
     */
    protected function createNavi($node, $currentPage, $params)
    {
        $letterLinks = '';
        $params['sindex'] = 0;

        /** @var SimpleXMLElement $pages $pages */
        foreach ($node as $pages) {
            if ($pages->getName() == 'current_page') {
                $letterLinks .= '<li class="act">' . $currentPage . "</li>";
            } else {
                $params['sc'] = (String)$pages['sc'];
                $params['lc'] = (String)$pages['lc'];
                $label = (string)$pages;
                $letterLinks .= '<li>' . $this->pi_linkToPage($label, $this->conf['listTarget'], '', $params) . '</li>';
            }
        }
        return $letterLinks;
    }

    /**
     * Traverses  xml nodes of journal list and generates a linked list of journals with access information
     *
     * @param \SimpleXMLElement $node : xml-node of journal list navigation nodes
     * @param array $itemParams : parameters for journal details request
     * @return string               $journalLinks: linked list of journals as HTML-snippet
     */
    protected function createList(\SimpleXMLElement $node, $itemParams)
    {
        $journals = $node->alphabetical_order;
        $listParams = !empty(\TYPO3\CMS\Core\Utility\GeneralUtility::_GET('client_ip')) ? \TYPO3\CMS\Core\Utility\GeneralUtility::_GET() : $this->baseParams;
        $journalLinks = $this->getFirstLinkForList($node, $listParams);
        $journalLinks .= $this->getJournals($itemParams, $journals);
        $journalLinks .= $this->getNextLinkForList($node, $listParams);

        return $journalLinks;
    }

    /**
     * @return $this
     */
    protected function setBaseParameter()
    {
        $this->baseParams = [
            'L' => $GLOBALS['TSFE']->sys_language_uid,
            'notation' => $this->conf['notation'],
            'xmloutput' => '1',
            'colors' => '7',
            'lang' => $GLOBALS['TSFE']->lang,
        ];
        return $this;
    }

    /**
     * @param $node
     * @param $listParams
     * @return string
     */
    protected function getFirstLinkForList($node, $listParams)
    {
        $journalLinks = '';
        $first = $node->first_fifty;

        if ($first != null) {
            $firstList = '';
            foreach ($first as $firstlink) {
                $label = '&laquo;&nbsp;' . $firstlink->first_fifty_titles;
                $listParams['sc'] = (String)$firstlink['sc'];
                $listParams['lc'] = (String)$firstlink['lc'];
                $listParams['sindex'] = (String)$firstlink['sindex'];
                $firstList .= '<li>' . $this->pi_linkToPage($label, $this->conf['listTarget'], '',
                        $listParams) . "</li>";
            }
            $journalLinks = '<ul class="firstlist">' . $firstList . "</ul>";
        }
        return $journalLinks;
    }

    /**
     * @param $itemParams
     * @param $journals
     * @return string
     */
    protected function getJournals($itemParams, $journals)
    {
        $journalLinks = '';
        if ($journals->journals) {
            $journalLinks = '<ul>';
            foreach ($journals->journals->journal as $journal) {
                $access = $journal->journal_color['color'];
                $image = '<img alt="' . $access . '" width="30px" height="12" src="' . \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::siteRelPath('ezbrequest') . 'Resources/Public/Images/' . $access . '.gif" />';

                $itemParams['jour_id'] = (string)$journal['jourid'];
                $itemParams['xmloutput'] = "0";
                $journalLinks .= '<li><span class="ampel"><a href="' . $this->conf['ezbItemURL'] . '?' . $this->paramString($itemParams) . '">' . $image . '</span>';

                $title = htmlspecialchars((string)$journal->title);
                $itemParams['xmloutput'] = '1';
                $journalLinks .= $this->pi_linkToPage(htmlspecialchars_decode($title), $this->conf['itemTarget'], '',
                        $itemParams) . "</li>";
            }
            $journalLinks .= '</ul>';
        }
        return $journalLinks;
    }

    /**
     * @param $node
     * @param $listParams
     * @return string
     */
    protected function getNextLinkForList($node, $listParams)
    {
        $journalLinks = '';
        $next = $node->next_fifty;
        if ($next != null) {
            $nextList = '';
            foreach ($next as $nextlink) {
                $label = '&raquo;&nbsp;' . $nextlink->next_fifty_titles;
                $listParams['sc'] = (String)$nextlink['sc'];
                $listParams['lc'] = (String)$nextlink['lc'];
                $listParams['sindex'] = (String)$nextlink['sindex'];
                $nextList .= '<li>' . $this->pi_linkToPage($label, $this->conf['listTarget'], '',
                        $listParams) . "</li>";
            }
            $journalLinks .= '<ul class="nextlist">' . $nextList . "</ul>";
        }
        return $journalLinks;
    }

    /**
     * @return $this
     */
    protected function determineClientInstitution()
    {
        if ($this->conf['bibid']) {
            $this->baseParams['bibid'] = $this->conf['bibid'];
            if ($this->conf['bibid'] == 'NATLI') {
                $this->baseParams['colors'] = 2;
            }
        } else {
            $this->baseParams['client_ip'] = \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('REMOTE_ADDR');
        }
        return $this;
    }

    /**
     * @return $this
     */
    protected function setListTarget()
    {
        $listTarget = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'listTarget', 'sDEF');
        $this->conf['listTarget'] = $listTarget ? $listTarget : $this->conf['currentPage'];
        return $this;
    }

    /**
     * @return $this
     */
    protected function setItemTarget()
    {
        $itemTarget = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'itemTarget', 'sDEF');
        $this->conf['itemTarget'] = $itemTarget ? $itemTarget : $this->conf['currentPage'];
        return $this;
    }

    /**
     * @return $this
     */
    protected function setInstitutionIdentifier()
    {
        $bibID = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'bibid', 'sDEF');
        $this->conf['bibid'] = $bibID ? $bibID : $this->conf['bibid'];
        return $this;
    }

    /**
     * @param $journal
     * @return string
     */
    protected function getFirstFulltextIssue($journal)
    {
        $moreValues = '';

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
        }
        return $moreValues;
    }

    /**
     * @param $journal
     * @return string
     */
    protected function getLastFulltextIssue($journal)
    {
        $moreValues = '';

        if ($journal->detail->last_fulltext_issue) {
            if ($journal->detail->last_fulltext_issue->last_volume) {
                $moreValues .= 'Vol. ' . $journal->detail->last_fulltext_issue->last_volume;
            }
            if ($journal->detail->last_fulltext_issue->last_issue) {
                $moreValues .= ', ' . $journal->detail->last_fulltext_issue->last_issue;
            }
            if ($journal->detail->last_fulltext_issue->last_date) {
                $moreValues .= ' (' . $journal->detail->last_fulltext_issue->last_date . ')';
            };
        }
        return $moreValues;
    }

    /**
     * @param $journal
     * @return string
     */
    protected function getHomepage($journal)
    {
        $moreValues = '';

        if ($journal->detail->homepages->homepage) {
            foreach ($journal->detail->homepages->homepage as $homepage) {
                // save old parameters @TODO did we have to do that?
                $oldATagParams = $GLOBALS['TSFE']->ATagParams;

                $GLOBALS['TSFE']->ATagParams = ' class="external-link-new-window" ';

                if (strlen($homepage) > 50) {
                    $moreValues .= $this->pi_linkToPage(substr($homepage, 0, 50) . '…', $homepage, '_blank',
                            '') . '<br/>';
                } else {
                    $moreValues .= $this->pi_linkToPage($homepage, $homepage, '_blank', '') . '<br/>';
                }

                $GLOBALS['TSFE']->ATagParams = $oldATagParams;
                unset($oldATagParams);
            }

        }
        return $moreValues;
    }

    /**
     * @param $journal
     * @return mixed
     */
    protected function getFullText($journal)
    {
        $moreValues = '';
        if ($journal->detail->fulltext) {
            // save old parameters @TODO did we have to do that?
            $oldATagParams = $GLOBALS['TSFE']->ATagParams;
            $GLOBALS['TSFE']->ATagParams = ' class="external-link-new-window" ';

            $moreValues = $this->pi_linkToPage(substr($journal->detail->fulltext, 0, 50) . '...',
                $journal->detail->fulltext, '_blank', '');

            // restore saved status @TODO why?
            $GLOBALS['TSFE']->ATagParams = $oldATagParams;
            unset($oldATagParams);
        }
        return $moreValues;
    }

    /**
     * @param $journal
     * @return string
     */
    protected function getKeywords($journal)
    {
        $keywords = '';
        if ($journal->detail->keywords->keyword) {
            $keywords = [];
            foreach ($journal->detail->keywords->keyword as $keyword) {
                $keywords[] = (string)$keyword;
            }
            $keywords = implode('; ', $keywords);
        }

        return $keywords;
    }

    /**
     * @param $journal
     * @return string
     */
    protected function getSubject($journal)
    {
        $subjects = '';
        if ($journal->detail->subjects->subject) {
            $subjects = [];
            foreach ($journal->detail->subjects->subject as $subject) {
                $subjects[] = (string)$subject;
            }
            $subjects = implode('; ', $subjects);
        }
        return $subjects;
    }

    /**
     * @param $journal
     * @return string
     */
    protected function getZdbNumber($journal)
    {
        $zdbNumber = '';
        if ($journal->detail->ZDB_number) {

            $oldATagParams = $GLOBALS['TSFE']->ATagParams;
            $GLOBALS['TSFE']->ATagParams = ' class="external-link-new-window" ';
            $zdbNumber = $this->pi_linkToPage($journal->detail->ZDB_number, $journal->detail->ZDB_number['url'],
                '_blank', '');

            $GLOBALS['TSFE']->ATagParams = $oldATagParams;
            unset($oldATagParams);
        }
        return $zdbNumber;
    }

    /**
     * @param $journal
     * @return string
     */
    protected function getIssn($journal)
    {
        $issn = '';
        $ISSNs = [];
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
            $issn = implode(', ', $ISSNs);
        }
        return $issn;
    }

    /**
     * @param $journal
     * @return string
     */
    protected function getPeriods($journal)
    {
        $journals = '';
        if ($journal->periods->period) {
            $periods = ["<ul class='ezbrequest-periods'>"];
            foreach ($journal->periods->period as $period) {
                $label = 'Link';
                if ($period->label) {
                    $label = (string)$period->label;
                }
                $link = rawurldecode((string)$period->warpto_link['url']);
                $image = '<img alt="' . $period->journal_color['color'] . '" width="30" height="12" src="' . \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::siteRelPath('ezbrequest') . 'Resources/Public/Images/' . $period->journal_color['color'] . '.gif" />' . "\n";
                $periods[] = '<li>' . '<a href="' . $link . '" class="external-link-new-window" target="_blank">' . $image . ' ' . $label . "</a></li>";
            }
            $periods[] = '</ul>';
            $journals = implode('', $periods);
        }
        return $journals;
    }

    /**
     * @param $journal
     * @return string
     */
    protected function getAppearance($journal)
    {
        $appearance = '';
        if ($journal->detail->appearence) {
            $appearance = $journal->detail->appearence;
        }
        return $appearance;
    }

    /**
     * @param $journal
     * @return string
     */
    protected function getCosts($journal)
    {
        $costs = '';
        if ($journal->detail->costs) {
            $costs = $journal->detail->costs;
        }
        return $costs;
    }

    /**
     * @param $journal
     * @return string
     */
    protected function getRemarks($journal)
    {
        $remarks = '';
        if ($journal->detail->remarks) {
            $remarks = $journal->detail->remarks;
        }
        return $remarks;
    }

    /**
     * @param $listParams
     * @return SimpleXMLElement
     */
    protected function fetchJournalList($listParams)
    {
        if (strpos($listParams['notation'], ',') === false) {
            $URL = $this->conf['ezbListURL'] . '?' . $this->paramString($listParams, self::LINK_EZB_LIST_QUERY);
        } else {
            $URL = $this->conf['ezbSearchURL'] . '?' . $this->paramString($listParams, self::LINK_EZB_SEARCH_QUERY);
        }
        $xml = simplexml_load_file($URL);
        return $xml;
    }

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/ezbrequest/pi1/class.tx_ezbrequest_pi1.php']) {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/ezbrequest/pi1/class.tx_ezbrequest_pi1.php']);
}
