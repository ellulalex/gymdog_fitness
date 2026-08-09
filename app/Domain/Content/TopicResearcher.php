<?php

namespace App\Domain\Content;

interface TopicResearcher
{
    /**
     * Propose article topics for the niche, avoiding the given existing titles.
     *
     * @param  string[]  $avoid  titles already published or queued
     * @param  array<string,int>  $coverage  pillar => how many articles already cover it,
     *                                       so the researcher can fill the gaps
     * @return ProposedTopic[]
     */
    public function research(int $count, array $avoid = [], array $coverage = []): array;
}
