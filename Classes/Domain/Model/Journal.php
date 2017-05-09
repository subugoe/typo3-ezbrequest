<?php

declare(strict_types=1);

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
 * Journal of a periodical.
 */
class Journal extends AbstractEntity
{
    /**
     * @var array
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
     * @var array
     */
    protected $keywords;

    /**
     * @var string
     */
    protected $fulltext;

    /**
     * @var array
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
     * @var array
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
     * @return array
     */
    public function getAvailability(): array
    {
        return $this->availability;
    }

    /**
     * @param array $availability
     *
     * @return Journal
     */
    public function setAvailability(array $availability): Journal
    {
        $this->availability = $availability;

        return $this;
    }

    /**
     * @return string
     */
    public function getPublisher(): string
    {
        return $this->publisher;
    }

    /**
     * @param string $publisher
     *
     * @return Journal
     */
    public function setPublisher(string $publisher): Journal
    {
        $this->publisher = $publisher;

        return $this;
    }

    /**
     * @return string
     */
    public function getIssn(): string
    {
        return $this->issn;
    }

    /**
     * @param string $issn
     *
     * @return Journal
     */
    public function setIssn(string $issn): Journal
    {
        $this->issn = $issn;

        return $this;
    }

    /**
     * @return string
     */
    public function getZdb(): string
    {
        return $this->zdb;
    }

    /**
     * @param string $zdb
     *
     * @return Journal
     */
    public function setZdb(string $zdb): Journal
    {
        $this->zdb = $zdb;

        return $this;
    }

    /**
     * @return array
     */
    public function getKeywords(): array
    {
        return $this->keywords;
    }

    /**
     * @param array $keywords
     *
     * @return Journal
     */
    public function setKeywords(array $keywords): Journal
    {
        $this->keywords = $keywords;

        return $this;
    }

    /**
     * @return string
     */
    public function getFulltext(): string
    {
        return $this->fulltext;
    }

    /**
     * @param string $fulltext
     *
     * @return Journal
     */
    public function setFulltext(string $fulltext): Journal
    {
        $this->fulltext = $fulltext;

        return $this;
    }

    /**
     * @return array
     */
    public function getHomepage(): array
    {
        return $this->homepage;
    }

    /**
     * @param array $homepage
     *
     * @return Journal
     */
    public function setHomepage(array $homepage): Journal
    {
        $this->homepage = $homepage;

        return $this;
    }

    /**
     * @return string
     */
    public function getAppearance(): string
    {
        return $this->appearance;
    }

    /**
     * @param string $appearance
     *
     * @return Journal
     */
    public function setAppearance(string $appearance): Journal
    {
        $this->appearance = $appearance;

        return $this;
    }

    /**
     * @return string
     */
    public function getCosts(): string
    {
        return $this->costs;
    }

    /**
     * @param string $costs
     *
     * @return Journal
     */
    public function setCosts(string $costs): Journal
    {
        $this->costs = $costs;

        return $this;
    }

    /**
     * @return string
     */
    public function getRemarks(): string
    {
        return $this->remarks;
    }

    /**
     * @param string $remarks
     *
     * @return Journal
     */
    public function setRemarks(string $remarks): Journal
    {
        $this->remarks = $remarks;

        return $this;
    }

    /**
     * @return array
     */
    public function getSubject(): array
    {
        return $this->subject;
    }

    /**
     * @param array $subject
     *
     * @return Journal
     */
    public function setSubject(array $subject): Journal
    {
        $this->subject = $subject;

        return $this;
    }

    /**
     * @return string
     */
    public function getFirstFulltextIssue(): string
    {
        return $this->firstFulltextIssue;
    }

    /**
     * @param string $firstFulltextIssue
     *
     * @return Journal
     */
    public function setFirstFulltextIssue(string $firstFulltextIssue): Journal
    {
        $this->firstFulltextIssue = $firstFulltextIssue;

        return $this;
    }

    /**
     * @return string
     */
    public function getLastFulltextIssue(): string
    {
        return $this->lastFulltextIssue;
    }

    /**
     * @param string $lastFulltextIssue
     *
     * @return Journal
     */
    public function setLastFulltextIssue(string $lastFulltextIssue): Journal
    {
        $this->lastFulltextIssue = $lastFulltextIssue;

        return $this;
    }
}
