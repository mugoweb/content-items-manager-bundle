<?php

namespace MugoWeb\ContentItemsManagerBundle\Twig;

use Ibexa\Contracts\Core\Repository\SearchService;
use Ibexa\Contracts\Core\Repository\Values\ContentType\ContentType;
use Ibexa\Contracts\Core\Repository\Values\Content\Query;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class ContentCountExtension extends AbstractExtension
{
    private SearchService $searchService;

    public function __construct(SearchService $searchService)
    {
        $this->searchService = $searchService;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('get_content_count', [$this, 'getContentCount']),
        ];
    }

    public function getContentCount(ContentType $contentType): int
    {
        $query = new Query();
        $query->query = new Criterion\ContentTypeId($contentType->id);
        $query->limit = 0;

        return $this->searchService->findContent($query)->totalCount;
    }
}