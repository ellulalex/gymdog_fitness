<?php

namespace Tests\Support;

use App\Domain\Content\ProposedTopic;
use App\Domain\Content\TopicResearcher;

/**
 * Returns canned topic proposals so the research pipeline can be tested without
 * an API key. Records the arguments it was called with for assertions.
 */
class FakeTopicResearcher implements TopicResearcher
{
    public int $calledWithCount = 0;

    /** @var string[] */
    public array $calledWithAvoid = [];

    /** @var array<string,int> */
    public array $calledWithCoverage = [];

    /** @param  ProposedTopic[]  $proposals */
    public function __construct(private array $proposals = []) {}

    public function research(int $count, array $avoid = [], array $coverage = []): array
    {
        $this->calledWithCount = $count;
        $this->calledWithAvoid = $avoid;
        $this->calledWithCoverage = $coverage;

        return $this->proposals;
    }
}
