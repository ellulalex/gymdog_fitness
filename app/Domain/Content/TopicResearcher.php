<?php

namespace App\Domain\Content;

interface TopicResearcher
{
    /**
     * Propose article topics for the niche, avoiding the given existing titles.
     *
     * @param  string[]  $avoid  titles already published or queued
     * @return ProposedTopic[]
     */
    public function research(int $count, array $avoid = []): array;
}
