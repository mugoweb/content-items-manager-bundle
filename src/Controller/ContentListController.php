<?php

namespace MugoWeb\ContentItemsManagerBundle\Controller;

use Ibexa\Contracts\AdminUi\Controller\Controller;
use Ibexa\Contracts\Core\Repository\ContentService;
use Ibexa\Contracts\Core\Repository\ContentTypeService;
use Ibexa\Contracts\Core\Repository\PermissionResolver;
use Ibexa\Contracts\Core\Repository\SearchService;
use Ibexa\Contracts\Core\Repository\Values\Content\Query;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\SortClause;
use Ibexa\Contracts\Core\Repository\Values\Content\LocationQuery;
use Pagerfanta\Pagerfanta;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Ibexa\Core\Pagination\Pagerfanta\LocationSearchAdapter;
use Ibexa\Contracts\Core\Repository\TrashService;
use Ibexa\Contracts\Core\Repository\LocationService;

class ContentListController extends Controller
{
    private SearchService $searchService;
    private ContentService $contentService;
    private ContentTypeService $contentTypeService;
    private PermissionResolver $permissionResolver;
    private TrashService $trashService;
    private LocationService $locationService;

    public function __construct(
        SearchService $searchService,
        ContentService $contentService,
        ContentTypeService $contentTypeService,
        PermissionResolver $permissionResolver,
        TrashService $trashService,
        LocationService $locationService
        )
    {
        $this->searchService = $searchService;
        $this->contentService = $contentService;
        $this->contentTypeService = $contentTypeService;
        $this->permissionResolver = $permissionResolver;
        $this->trashService = $trashService;
        $this->locationService = $locationService;
    }

    public function listAction(Request $request, int $contentTypeId): Response
    {
        $contentType = $this->contentTypeService->loadContentType($contentTypeId);
        $page = $request->query->getInt('page', 1);
        $limit = 50;

        $excludedContentIds = [];
        $session = $request->getSession();
        if ($session !== null && $session->has('mugo_just_trashed_content_ids')) {
            $excludedContentIds = (array) $session->get('mugo_just_trashed_content_ids', []);
            $session->remove('mugo_just_trashed_content_ids'); // one-time exclusion
        }

        $criteria = [
            new Criterion\ContentTypeId($contentTypeId),
        ];

        if (!empty($excludedContentIds)) {
            $criteria[] = new Criterion\LogicalNot(
                new Criterion\ContentId(array_values(array_unique($excludedContentIds)))
            );
        }

        $query = new LocationQuery();
        $query->query = new Criterion\LogicalAnd($criteria);
        $query->sortClauses = [
            new SortClause\DatePublished(Query::SORT_DESC)
        ];

        $adapter = new LocationSearchAdapter($query, $this->searchService);
        $pager = new Pagerfanta($adapter);
        $pager->setMaxPerPage($limit);
        $pager->setCurrentPage($page);

        $groups = $contentType->getContentTypeGroups();
        $group = $groups[0] ?? null;

        return $this->render('@MugoContentItemsManager/content/list.html.twig', [
            'content_type' => $contentType,
            'group' => $group,
            'pager' => $pager,
        ]);
    }

    public function bulkDeleteAction(Request $request, int $contentTypeId): Response
    {
        $contentIds = $request->request->get('content_ids', []);

        if (empty($contentIds)) {
            $this->addFlash('warning', 'No items selected for deletion.');
            return $this->redirectToRoute('mugo_content_items_list', ['contentTypeId' => $contentTypeId]);
        }

        $deletedCount = 0;
        $trashedContentIds = [];
        foreach ($contentIds as $contentId) {
            try {
                $content = $this->contentService->loadContent($contentId);
                // Check permissions
                if (!$this->permissionResolver->canUser('content', 'remove', $content)) {
                    continue; // Skip if no permission, or handle error appropriately
                }

                $location = $this->locationService->loadLocation($content->contentInfo->mainLocationId);
                $this->trashService->trash($location);
                $deletedCount++;
                $trashedContentIds[] = $contentId;
            }
            catch (\Exception $e) {
                // Log error or deal with it
            }
        }

        if ($deletedCount > 0) {
            $this->addFlash('success', sprintf('Sent %d content items to Trash.', $deletedCount));
            $session = $request->getSession();
            if ($session !== null) {
                $session->set('mugo_just_trashed_content_ids', $trashedContentIds);
            }
        }

        return $this->redirectToRoute('mugo_content_items_list', ['contentTypeId' => $contentTypeId]);
    }
}