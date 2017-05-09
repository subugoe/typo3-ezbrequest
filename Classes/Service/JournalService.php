<?php

declare(strict_types=1);

namespace Subugoe\Ezbrequest\Service;

use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/* * *************************************************************
 *  Copyright notice
 *
 *  (c) 2015 Ingo Pfennigstorf <pfennigstorf@sub-goettingen.de>
 *      Goettingen State Library
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
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
 * Provides methods to get data out of a raw journal dataset.
 */
class JournalService
{
    /**
     * @var \SimpleXmlElement
     */
    protected $journal;

    /**
     * JournalService constructor.
     *
     * @param \SimpleXmlElement $journal
     */
    public function __construct($journal = null)
    {
        $this->journal = $journal;
    }

    /**
     * @return string
     */
    public function getIssn(): string
    {
        $issn = '';
        $ISSNs = [];
        if ($this->journal->detail->E_ISSNs->E_ISSN) {
            foreach ($this->journal->detail->E_ISSNs->E_ISSN as $eissn) {
                $ISSNs[] = $eissn.' ('.LocalizationUtility::translate('electronic', 'ezbrequest').')';
            }
        }

        if ($this->journal->detail->P_ISSNs->P_ISSN) {
            foreach ($this->journal->detail->P_ISSNs->P_ISSN as $pissn) {
                $ISSNs[] = $pissn.' ('.LocalizationUtility::translate('printed', 'ezbrequest').')';
            }
        }

        if (count($ISSNs) > 0) {
            $issn = implode(', ', $ISSNs);
        }

        return $issn;
    }

    /**
     * @return array
     */
    public function getPeriods(): array
    {
        $periods = [];
        if ($this->journal->periods->period) {
            foreach ($this->journal->periods->period as $period) {
                $link = [];
                $link['label'] = $period->label ? (string) $period->label : 'Link';
                $link['url'] = rawurldecode((string) $period->warpto_link['url']);
                $link['image'] = (string) $period->journal_color['color'];

                $periods[] = $link;
            }
        }

        return $periods;
    }

    /**
     * return string.
     */
    public function getPublisher(): string
    {
        return (string) $this->journal->detail->publisher;
    }

    /**
     * @return string
     */
    public function getZdbNumber(): string
    {
        $this->journal->detail->ZDB_number ? $zdbNumber = (string) $this->journal->detail->ZDB_number : $zdbNumber = '';

        return $zdbNumber;
    }

    /**
     * @return array
     */
    public function getKeywords(): array
    {
        $keywords = [];
        if ($this->journal->detail->keywords->keyword) {
            $keywords = [];
            foreach ($this->journal->detail->keywords->keyword as $keyword) {
                $keywords[] = (string) $keyword;
            }
        }

        return $keywords;
    }

    /**
     * @return string
     */
    public function getFullText(): string
    {
        $moreValues = $this->journal->detail->fulltext ? (string) $this->journal->detail->fulltext : '';

        return $moreValues;
    }

    /**
     * @return array
     */
    public function getHomepage(): array
    {
        $moreValues = [];

        if ($this->journal->detail->homepages->homepage) {
            foreach ($this->journal->detail->homepages->homepage as $homepage) {
                array_push($moreValues, (string) $homepage);
            }
        }

        return $moreValues;
    }

    /**
     * @return string
     */
    public function getAppearance(): string
    {
        $appearance = '';
        if ($this->journal->detail->appearence) {
            $appearance = (string) $this->journal->detail->appearence;
        }

        return $appearance;
    }

    /**
     * @return string
     */
    public function getCosts(): string
    {
        $costs = '';
        if ($this->journal->detail->costs) {
            $costs = (string) $this->journal->detail->costs;
        }

        return $costs;
    }

    /**
     * @return string
     */
    public function getRemarks(): string
    {
        $remarks = '';
        if ($this->journal->detail->remarks) {
            $remarks = (string) $this->journal->detail->remarks;
        }

        return $remarks;
    }

    /**
     * @return array
     */
    public function getSubject(): array
    {
        $subjects = [];
        if ($this->journal->detail->subjects->subject) {
            $subjects = [];
            foreach ($this->journal->detail->subjects->subject as $subject) {
                $subjects[] = (string) $subject;
            }
        }

        return $subjects;
    }

    /**
     * @return string
     */
    public function getFirstFulltextIssue(): string
    {
        $moreValues = '';

        if ($this->journal->detail->first_fulltext_issue) {
            if ($this->journal->detail->first_fulltext_issue->first_volume) {
                $moreValues .= 'Vol. '.$this->journal->detail->first_fulltext_issue->first_volume;
            }
            if ($this->journal->detail->first_fulltext_issue->first_issue) {
                $moreValues .= ', '.$this->journal->detail->first_fulltext_issue->first_issue;
            }
            if ($this->journal->detail->first_fulltext_issue->first_date) {
                $moreValues .= ' ('.$this->journal->detail->first_fulltext_issue->first_date.')';
            }
        }

        return $moreValues;
    }

    /**
     * @return string
     */
    public function getLastFulltextIssue(): string
    {
        $moreValues = '';

        if ($this->journal->detail->last_fulltext_issue) {
            if ($this->journal->detail->last_fulltext_issue->last_volume) {
                $moreValues .= 'Vol. '.$this->journal->detail->last_fulltext_issue->last_volume;
            }
            if ($this->journal->detail->last_fulltext_issue->last_issue) {
                $moreValues .= ', '.$this->journal->detail->last_fulltext_issue->last_issue;
            }
            if ($this->journal->detail->last_fulltext_issue->last_date) {
                $moreValues .= ' ('.$this->journal->detail->last_fulltext_issue->last_date.')';
            }
        }

        return $moreValues;
    }
}
