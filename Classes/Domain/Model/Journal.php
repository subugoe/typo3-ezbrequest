<?php
namespace Subugoe\Ezbrequest\Domain\Model;

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
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

/**
 * Journal of a periodical
 */
class Journal extends AbstractEntity
{

    /**
     * @var string
     */
    protected $availability;

    /**
     * @var string
     */
    protected $publisher;

    /**
     * @var string
     */
    protected $issn;

    /**
     * @var string
     */
    protected $zdb;

    /**
     * @var string
     */
    protected $keywords;

    /**
     * @var string
     */
    protected $fulltext;

    /**
     * @var string
     */
    protected $homepage;

    /**
     * @var string
     */
    protected $appearance;

    /**
     * @var string
     */
    protected $costs;

    /**
     * @var string
     */
    protected $remarks;

    /**
     * @var string
     */
    protected $subject;

    /**
     * @var string
     */
    protected $firstFulltextIssue;

    /**
     * @var string
     */
    protected $lastFulltextIssue;

    /**
     * @return string
     */
    public function getAvailability()
    {
        return $this->availability;
    }

    /**
     * @param string $availability
     * @return Journal
     */
    public function setAvailability($availability)
    {
        $this->availability = $availability;
        return $this;
    }

    /**
     * @return string
     */
    public function getPublisher()
    {
        return $this->publisher;
    }

    /**
     * @param string $publisher
     * @return Journal
     */
    public function setPublisher($publisher)
    {
        $this->publisher = $publisher;
        return $this;
    }

    /**
     * @return string
     */
    public function getIssn()
    {
        return $this->issn;
    }

    /**
     * @param string $issn
     * @return Journal
     */
    public function setIssn($issn)
    {
        $this->issn = $issn;
        return $this;
    }

    /**
     * @return string
     */
    public function getZdb()
    {
        return $this->zdb;
    }

    /**
     * @param string $zdb
     * @return Journal
     */
    public function setZdb($zdb)
    {
        $this->zdb = $zdb;
        return $this;
    }

    /**
     * @return string
     */
    public function getKeywords()
    {
        return $this->keywords;
    }

    /**
     * @param string $keywords
     * @return Journal
     */
    public function setKeywords($keywords)
    {
        $this->keywords = $keywords;
        return $this;
    }

    /**
     * @return string
     */
    public function getFulltext()
    {
        return $this->fulltext;
    }

    /**
     * @param string $fulltext
     * @return Journal
     */
    public function setFulltext($fulltext)
    {
        $this->fulltext = $fulltext;
        return $this;
    }

    /**
     * @return string
     */
    public function getHomepage()
    {
        return $this->homepage;
    }

    /**
     * @param string $homepage
     * @return Journal
     */
    public function setHomepage($homepage)
    {
        $this->homepage = $homepage;
        return $this;
    }

    /**
     * @return string
     */
    public function getAppearance()
    {
        return $this->appearance;
    }

    /**
     * @param string $appearance
     * @return Journal
     */
    public function setAppearance($appearance)
    {
        $this->appearance = $appearance;
        return $this;
    }

    /**
     * @return string
     */
    public function getCosts()
    {
        return $this->costs;
    }

    /**
     * @param string $costs
     * @return Journal
     */
    public function setCosts($costs)
    {
        $this->costs = $costs;
        return $this;
    }

    /**
     * @return string
     */
    public function getRemarks()
    {
        return $this->remarks;
    }

    /**
     * @param string $remarks
     * @return Journal
     */
    public function setRemarks($remarks)
    {
        $this->remarks = $remarks;
        return $this;
    }

    /**
     * @return string
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * @param string $subject
     * @return Journal
     */
    public function setSubject($subject)
    {
        $this->subject = $subject;
        return $this;
    }

    /**
     * @return string
     */
    public function getFirstFulltextIssue()
    {
        return $this->firstFulltextIssue;
    }

    /**
     * @param string $firstFulltextIssue
     * @return Journal
     */
    public function setFirstFulltextIssue($firstFulltextIssue)
    {
        $this->firstFulltextIssue = $firstFulltextIssue;
        return $this;
    }

    /**
     * @return string
     */
    public function getLastFulltextIssue()
    {
        return $this->lastFulltextIssue;
    }

    /**
     * @param string $lastFulltextIssue
     * @return Journal
     */
    public function setLastFulltextIssue($lastFulltextIssue)
    {
        $this->lastFulltextIssue = $lastFulltextIssue;
        return $this;
    }

}
