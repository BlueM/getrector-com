<?php

declare(strict_types=1);

namespace App\RuleFilter;

use App\Enum\NodeTypeToHumanReadable;
use App\RuleFilter\ValueObject\RuleMetadata;

final class RuleFilter
{
    /**
     * @var int
     */
    private const MAX_RESULTS = 10;

    public function __construct(
        private readonly MatchingScoreResolver $matchingScoreResolver,
    ) {
    }

    /**
     * @param RuleMetadata[] $ruleMetadatas
     * @return RuleMetadata[]
     */
    public function filter(array $ruleMetadatas, ?string $query, ?string $nodeType, ?string $set): array
    {
        $ruleMetadatas = $this->filterByNodeTypeFirst($ruleMetadatas, $nodeType);
        $ruleMetadatas = $this->filterByQuery($ruleMetadatas, $query);
        $ruleMetadatas = $this->filterBySet($ruleMetadatas, $set);

        // limit results to keep page clear
        return array_slice($ruleMetadatas, 0, $set ? 1000 : self::MAX_RESULTS);
    }

    /**
     * @param RuleMetadata[] $ruleMetadatas
     * @return RuleMetadata[]
     */
    private function filterByNodeTypeFirst(array $ruleMetadatas, ?string $nodeTypeSlug): array
    {
        if ($nodeTypeSlug === null || $nodeTypeSlug === '') {
            return $ruleMetadatas;
        }

        // convert slug to node types
        $matchedNodeTypes = $this->matchNodeTypesBySlug($nodeTypeSlug);
        if ($matchedNodeTypes === null) {
            return $ruleMetadatas;
        }

        return array_filter(
            $ruleMetadatas,
            fn (RuleMetadata $ruleMetadata): bool => array_intersect(
                $matchedNodeTypes,
                $ruleMetadata->getNodeTypes()
            ) !== []
        );
    }

    /**
     * @param RuleMetadata[] $ruleMetadatas
     * @return RuleMetadata[]
     */
    private function filterByQuery(array $ruleMetadatas, ?string $query): array
    {
        // nothing to filter
        if ($query === null || strlen($query) < 3) {
            return $ruleMetadatas;
        }

        $filteredRuleMetadatas = [];
        foreach ($ruleMetadatas as $ruleMetadata) {
            $score = $this->matchingScoreResolver->resolve($ruleMetadata, $query);
            if ($score === 0) {
                continue;
            }

            $ruleMetadata->changeFilterScore($score);
            $filteredRuleMetadatas[] = $ruleMetadata;
        }

        // sort by score
        usort(
            $filteredRuleMetadatas,
            function (RuleMetadata $firstRuleMetadata, RuleMetadata $secondRuleMetadata): int {
                return $secondRuleMetadata->getFilterScore() <=> $firstRuleMetadata->getFilterScore();
            }
        );

        return $filteredRuleMetadatas;
    }

    /**
     * @param RuleMetadata[] $ruleMetadatas
     * @return RuleMetadata[]
     */
    private function filterBySet(array $ruleMetadatas, ?string $set): array
    {
        if ($set === '' || $set === null) {
            return $ruleMetadatas;
        }

        return array_filter($ruleMetadatas, fn (RuleMetadata $ruleMetadata): bool => $ruleMetadata->isInSet($set));
    }

    private function matchNodeTypesBySlug(string $nodeTypeSlug): mixed
    {
        foreach (NodeTypeToHumanReadable::SELECT_ITEMS_BY_GROUP as $nodeTypesToNames) {
            foreach ($nodeTypesToNames as $label => $nodeTypes) {
                if (slugify($label) !== $nodeTypeSlug) {
                    continue;
                }

                return $nodeTypes;
            }
        }

        return null;
    }
}
