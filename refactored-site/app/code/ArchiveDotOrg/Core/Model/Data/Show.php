<?php
/**
 * ArchiveDotOrg Core Module
 */

declare(strict_types=1);

namespace ArchiveDotOrg\Core\Model\Data;

use ArchiveDotOrg\Core\Api\Data\ShowInterface;
use ArchiveDotOrg\Core\Api\Data\TrackInterface;

/**
 * Show Data Transfer Object Implementation
 */
class Show implements ShowInterface
{
    private string $identifier = '';
    private string $title = '';
    private ?string $description = null;
    private ?string $date = null;
    private ?string $year = null;
    private ?string $venue = null;
    private ?string $creator = null;
    private ?string $taper = null;
    private ?string $transferer = null;
    private ?string $lineage = null;
    private ?string $notes = null;
    private ?string $collection = null;
    private ?string $serverOne = null;
    private ?string $serverTwo = null;
    private ?string $dir = null;
    private ?string $pubDate = null;
    private ?string $guid = null;
    private ?float $avgRating = null;
    private ?int $numReviews = null;

    /** @var TrackInterface[] */
    private array $tracks = [];

    /**
     * @inheritDoc
     */
    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    /**
     * @inheritDoc
     */
    public function setIdentifier(string $identifier): ShowInterface
    {
        $this->identifier = $identifier;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @inheritDoc
     */
    public function setTitle(string $title): ShowInterface
    {
        $this->title = $title;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @inheritDoc
     */
    public function setDescription(?string $description): ShowInterface
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getDate(): ?string
    {
        return $this->date;
    }

    /**
     * @inheritDoc
     */
    public function setDate(?string $date): ShowInterface
    {
        $this->date = $date;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getYear(): ?string
    {
        return $this->year;
    }

    /**
     * @inheritDoc
     */
    public function setYear(?string $year): ShowInterface
    {
        $this->year = $year;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getVenue(): ?string
    {
        return $this->venue;
    }

    /**
     * @inheritDoc
     */
    public function setVenue(?string $venue): ShowInterface
    {
        $this->venue = $venue;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getCreator(): ?string
    {
        return $this->creator;
    }

    /**
     * @inheritDoc
     */
    public function setCreator(?string $creator): ShowInterface
    {
        $this->creator = $creator;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getTaper(): ?string
    {
        return $this->taper;
    }

    /**
     * @inheritDoc
     */
    public function setTaper(?string $taper): ShowInterface
    {
        $this->taper = $taper;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getTransferer(): ?string
    {
        return $this->transferer;
    }

    /**
     * @inheritDoc
     */
    public function setTransferer(?string $transferer): ShowInterface
    {
        $this->transferer = $transferer;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getLineage(): ?string
    {
        return $this->lineage;
    }

    /**
     * @inheritDoc
     */
    public function setLineage(?string $lineage): ShowInterface
    {
        $this->lineage = $lineage;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getNotes(): ?string
    {
        return $this->notes;
    }

    /**
     * @inheritDoc
     */
    public function setNotes(?string $notes): ShowInterface
    {
        $this->notes = $notes;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getCollection(): ?string
    {
        return $this->collection;
    }

    /**
     * @inheritDoc
     */
    public function setCollection(?string $collection): ShowInterface
    {
        $this->collection = $collection;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getServerOne(): ?string
    {
        return $this->serverOne;
    }

    /**
     * @inheritDoc
     */
    public function setServerOne(?string $serverOne): ShowInterface
    {
        $this->serverOne = $serverOne;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getServerTwo(): ?string
    {
        return $this->serverTwo;
    }

    /**
     * @inheritDoc
     */
    public function setServerTwo(?string $serverTwo): ShowInterface
    {
        $this->serverTwo = $serverTwo;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getDir(): ?string
    {
        return $this->dir;
    }

    /**
     * @inheritDoc
     */
    public function setDir(?string $dir): ShowInterface
    {
        $this->dir = $dir;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getTracks(): array
    {
        return $this->tracks;
    }

    /**
     * @inheritDoc
     */
    public function setTracks(array $tracks): ShowInterface
    {
        $this->tracks = $tracks;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function addTrack(TrackInterface $track): ShowInterface
    {
        $this->tracks[] = $track;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getPubDate(): ?string
    {
        return $this->pubDate;
    }

    /**
     * @inheritDoc
     */
    public function setPubDate(?string $pubDate): ShowInterface
    {
        $this->pubDate = $pubDate;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getGuid(): ?string
    {
        return $this->guid;
    }

    /**
     * @inheritDoc
     */
    public function setGuid(?string $guid): ShowInterface
    {
        $this->guid = $guid;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getAvgRating(): ?float
    {
        return $this->avgRating;
    }

    /**
     * @inheritDoc
     */
    public function setAvgRating(?float $avgRating): ShowInterface
    {
        $this->avgRating = $avgRating;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getNumReviews(): ?int
    {
        return $this->numReviews;
    }

    /**
     * @inheritDoc
     */
    public function setNumReviews(?int $numReviews): ShowInterface
    {
        $this->numReviews = $numReviews;
        return $this;
    }
}
