<?php

declare(strict_types=1);
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

use Subugoe\Ezbrequest\Domain\Model\Journal;
use Subugoe\Ezbrequest\Service\JournalService;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

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
     * @var JournalService
     */
    protected $journalService;

    /**
     * The main method controls the data flow.
     *
     * @param string $content The Plugin content
     * @param array  $conf    The Plugin configuration
     *
     * @return string The content that is displayed on the website
     */
    public function main(string $content, array $conf): string
    {
        $this->init($conf);

        $listParams = $this->baseParams;
        $listParams['notation'] = $this->conf['notation'];
        $listParams = array_merge($listParams, GeneralUtility::_GET());

        // overwrite language setting (for language switch)
        $listParams['lang'] = $GLOBALS['TSFE']->lang;

        $itemParams = $this->baseParams;
        $itemParams['xmloutput'] = '0';

        if (GeneralUtility::_GET('jour_id')) {
            $content = $this->getDetailView();
        } else {
            $content = $this->getListView($listParams, $itemParams);
        }

        return $this->pi_wrapInBaseClass($content);
    }

    /**
     * initializes the plugin: gets the settings from the flexform.
     *
     * @param array $conf : array with the TS configuration
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
        $pageRenderer = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Page\PageRenderer::class);
        $pageRenderer->addCssFile(ExtensionManagementUtility::siteRelPath('ezbrequest').'Resources/Public/Css/ezb.css');

        return $this;
    }

    /**
     * @return string
     */
    protected function getDetailView(): string
    {
        $xml = \simplexml_load_file($this->conf['ezbItemURL'].'?'.$_SERVER['QUERY_STRING']);

        /** @var \TYPO3\CMS\Fluid\View\StandaloneView $fluidTemplate */
        $fluidTemplate = GeneralUtility::makeInstance(\TYPO3\CMS\Fluid\View\StandaloneView::class);
        $fluidTemplate->setTemplateRootPaths([ExtensionManagementUtility::extPath('ezbrequest').'Resources/Private/Templates/']);
        $fluidTemplate->setPartialRootPaths([ExtensionManagementUtility::extPath('ezbrequest').'Resources/Private/Templates/Partials/']);

        $fluidTemplate->setTemplate('Single');

        $journal = $xml->ezb_detail_about_journal->journal;

        $this->journalService = GeneralUtility::makeInstance(JournalService::class, $journal);

        $itemTable = $this->createItems();

        $fluidTemplate->assignMultiple([
            'headline' => htmlspecialchars((string) $journal->title),
            'access' => $journal->journal_color['color'],
            'notation' => $this->conf['notation'],
            'userIp' => $this->baseParams['client_ip'],
            'linkUri' => $this->conf['ezbJourURL'].'?'.'?'.str_replace('xmloutput=1', 'xmloutput=0',
                    $_SERVER['QUERY_STRING']),
            'journalItem' => $itemTable,
            'language' => [
                'text' => $GLOBALS['TSFE']->lang,
                'id' => $GLOBALS['TSFE']->sys_language_uid,
            ],
        ]);

        return $fluidTemplate->render();
    }

    /**
     * Traverses xml node with journal details.
     *
     * @return Journal
     */
    protected function createItems()
    {
        /** @var Journal $issue */
        $issue = GeneralUtility::makeInstance(Journal::class);

        $issue
            ->setAvailability($this->journalService->getPeriods())
            ->setIssn($this->journalService->getIssn())
            ->setPublisher($this->journalService->getPublisher())
            ->setZdb($this->journalService->getZdbNumber())
            ->setKeywords($this->journalService->getKeywords())
            ->setFulltext($this->journalService->getFullText())
            ->setHomepage($this->journalService->getHomepage())
            ->setAppearance($this->journalService->getAppearance())
            ->setCosts($this->journalService->getCosts())
            ->setRemarks($this->journalService->getRemarks())
            ->setSubject($this->journalService->getSubject())
            ->setFirstFulltextIssue($this->journalService->getFirstFulltextIssue())
            ->setLastFulltextIssue($this->journalService->getLastFulltextIssue());

        return $issue;
    }

    /**
     * @param array $listParams
     * @param array $itemParams
     *
     * @return string
     */
    protected function getListView(array $listParams, array $itemParams): string
    {
        /** @var \TYPO3\CMS\Fluid\View\StandaloneView $fluidTemplate */
        $fluidTemplate = GeneralUtility::makeInstance(\TYPO3\CMS\Fluid\View\StandaloneView::class);
        $fluidTemplate->setTemplatePathAndFilename(ExtensionManagementUtility::extPath('ezbrequest').'Resources/Private/Templates/List.html');

        $search = empty(GeneralUtility::_GET('jq_term1')) ? false : true;

        $navi = '';

        if ($search) {
            //fetch search results
            $URL = $this->conf['ezbSearchURL'].'?'.$this->paramString($listParams,
                    self::LINK_EZB_SEARCH_QUERY).'hits_per_page=100000';
            $xml = simplexml_load_file($URL);

            /** @var \SimpleXMLElement $result */
            $result = $xml->ezb_alphabetical_list_searchresult;
            $hits = (int) $result->search_count;

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
            $navi = '<span class="hits">'.$hits.' '.LocalizationUtility::translate('hitText',
                    'ezbrequest').'</span> '.$navi;
        }

        $institut = ((string) $xml->library ? (string) $xml->library : $this->pi_getLL('none')).'; ';

        $journalList = $this->createList($journalNode, $itemParams);

        $fluidTemplate->assignMultiple([
            'institute' => $institut,
            'journalList' => $journalList,
            'journalNavigation' => $navi,
            'notation' => $this->conf['notation'],
            'searchTerm' => GeneralUtility::_GET('jq_term1'),
            'ipAddress' => $this->baseParams['client_ip'],
            'language' => [
                'text' => $GLOBALS['TSFE']->lang,
                'id' => $GLOBALS['TSFE']->sys_language_uid,
            ],
        ]);

        return $fluidTemplate->render();
    }

    /**
     * Uses $params array to create a query string.
     * Take into account that the 'notation' setting requires special treatment
     * for different kinds of queries and depending on the number of notations.
     *
     * @param array $params
     * @param int   $mode   - one of  0: internal link,
     *                      1: link for EZB list query
     *                      2: link for EZB search query
     *
     * @return string
     */
    protected function paramString($params, $mode = 0)
    {
        $string = '';

        foreach ($params as $name => $value) {
            if ($name === 'notation' && $mode > 0) {
                if (strpos($value, ',') === false && $mode === self::LINK_EZB_LIST_QUERY) {
                    $string .= $name.'='.$value.'&';
                } else {
                    $notations = explode(',', $value);
                    foreach ($notations as $notation) {
                        $string .= 'Notations[]='.$notation.'&';
                    }
                }
            } else {
                $string .= $name.'='.$value.'&';
            }
        }

        return $string;
    }

    /**
     * Traverses  the top-node of the EZB journal navigation list  and generate a linked alphabetical navigation list.
     *
     * @param array  $node        : xml-node with journal list navigation nodes
     * @param string $currentPage : name of the current list page
     * @param array  $params      : navigation list parameters
     *
     * @return string $letterLinks: linked navigation list as HTML-snippet
     */
    protected function createNavi($node, $currentPage, $params)
    {
        $letterLinks = '';
        $params['sindex'] = 0;

        /** @var SimpleXMLElement $pages $pages */
        foreach ($node as $pages) {
            if ($pages->getName() == 'current_page') {
                $letterLinks .= '<li class="act">'.$currentPage.'</li>';
            } else {
                $params['sc'] = (string) $pages['sc'];
                $params['lc'] = (string) $pages['lc'];
                $label = (string) $pages;
                $letterLinks .= '<li>'.$this->pi_linkToPage($label, $this->conf['listTarget'], '', $params).'</li>';
            }
        }

        return $letterLinks;
    }

    /**
     * Traverses  xml nodes of journal list and generates a linked list of journals with access information.
     *
     * @param \SimpleXMLElement $node       : xml-node of journal list navigation nodes
     * @param array             $itemParams : parameters for journal details request
     *
     * @return string $journalLinks: linked list of journals as HTML-snippet
     */
    protected function createList(\SimpleXMLElement $node, $itemParams)
    {
        $this->journalService = GeneralUtility::makeInstance(JournalService::class);

        $journals = $node->alphabetical_order;
        $listParams = !empty(GeneralUtility::_GET('client_ip')) ? GeneralUtility::_GET() : $this->baseParams;
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
     *
     * @return string
     */
    protected function getFirstLinkForList($node, $listParams)
    {
        $journalLinks = '';
        $first = $node->first_fifty;

        if ($first != null) {
            $firstList = '';
            foreach ($first as $firstlink) {
                $label = '&laquo;&nbsp;'.$firstlink->first_fifty_titles;
                $listParams['sc'] = (string) $firstlink['sc'];
                $listParams['lc'] = (string) $firstlink['lc'];
                $listParams['sindex'] = (string) $firstlink['sindex'];
                $firstList .= '<li>'.$this->pi_linkToPage($label, $this->conf['listTarget'], '',
                        $listParams).'</li>';
            }
            $journalLinks = '<ul class="firstlist">'.$firstList.'</ul>';
        }

        return $journalLinks;
    }

    /**
     * @param $itemParams
     * @param $journals
     *
     * @return string
     */
    protected function getJournals($itemParams, $journals)
    {
        $journalLinks = '';
        if ($journals->journals) {
            $journalLinks = '<ul>';
            foreach ($journals->journals->journal as $journal) {
                $item = [];
                $item['access'] = $journal->journal_color['color'];
                $item['jour_id'] = (string) $journal['jourid'];
                $item['title'] = htmlspecialchars((string) $journal->title);
                $item['parameter'] = $itemParams;

                $image = '<img alt="'.$item['access'].'" class="journallist-image" src="'.ExtensionManagementUtility::siteRelPath('ezbrequest').'Resources/Public/Images/'.$item['access'].'.gif" />';

                $itemParams['jour_id'] = (string) $journal['jourid'];
                $itemParams['xmloutput'] = '0';
                $journalLinks .= '<li><span class="ampel"><a href="'.$this->conf['ezbItemURL'].'?'.$this->paramString($itemParams).'">'.$image.'</span>';

                $itemParams['xmloutput'] = '1';
                $journalLinks .= $this->pi_linkToPage(htmlspecialchars_decode($item['title']),
                        $this->conf['itemTarget'], '', $itemParams).'</li>';
            }
            $journalLinks .= '</ul>';
        }

        return $journalLinks;
    }

    /**
     * @param $node
     * @param $listParams
     *
     * @return string
     */
    protected function getNextLinkForList($node, $listParams)
    {
        $journalLinks = '';
        $next = $node->next_fifty;
        if ($next != null) {
            $nextList = '';
            foreach ($next as $nextlink) {
                $label = '&raquo;&nbsp;'.$nextlink->next_fifty_titles;
                $listParams['sc'] = (string) $nextlink['sc'];
                $listParams['lc'] = (string) $nextlink['lc'];
                $listParams['sindex'] = (string) $nextlink['sindex'];
                $nextList .= '<li>'.$this->pi_linkToPage($label, $this->conf['listTarget'], '',
                        $listParams).'</li>';
            }
            $journalLinks .= '<ul class="nextlist">'.$nextList.'</ul>';
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
            $this->baseParams['client_ip'] = GeneralUtility::getIndpEnv('REMOTE_ADDR');
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
     * @param $listParams
     *
     * @return SimpleXMLElement
     */
    protected function fetchJournalList($listParams)
    {
        if (strpos($listParams['notation'], ',') === false) {
            $URL = $this->conf['ezbListURL'].'?'.$this->paramString($listParams, self::LINK_EZB_LIST_QUERY);
        } else {
            $URL = $this->conf['ezbSearchURL'].'?'.$this->paramString($listParams, self::LINK_EZB_SEARCH_QUERY);
        }
        $xml = simplexml_load_file($URL);

        return $xml;
    }
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/ezbrequest/pi1/class.tx_ezbrequest_pi1.php']) {
    include_once $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/ezbrequest/pi1/class.tx_ezbrequest_pi1.php'];
}
