<?php

declare(strict_types=1);

namespace Xaben\DataFilterDataTables\Adapter;

use Xaben\DataFilter\Adapter\AdapterInterface;
use Xaben\DataFilter\Adapter\BaseAdapter;
use Xaben\DataFilter\Definition\FilterDefinitionInterface;
use Xaben\DataFilter\Filter\CollectionFilter;
use Xaben\DataFilter\Pagination\PaginationConfiguration;
use Symfony\Component\HttpFoundation\Request;

class DataTableAdapter extends BaseAdapter implements AdapterInterface
{
    protected function processPagination(
        FilterDefinitionInterface $definition,
        Request $request,
        CollectionFilter $collectionFilter
    ): void {
        $paginationConfiguration = $definition->getPaginationConfiguration();
        if (!$paginationConfiguration) {
            return;
        }

        [$offset, $limit] = $paginationConfiguration->getByOffset(
            (int) $request->request->get('start', '0'),
            (int) $request->request->get('length', (string) PaginationConfiguration::DEFAULT_RESULT_COUNT)
        );

        $collectionFilter->setOffset($offset);
        $collectionFilter->setLimit($limit);
    }

    protected function processSortable(
        FilterDefinitionInterface $definition,
        Request $request,
        CollectionFilter $collectionFilter
    ): void {
        $sortConfiguration = $definition->getSortConfiguration();
        $sort = [];
        foreach ($request->request->all()['order'] ?? [] as $value) {
            $columnIndex = (int) ($value['column'] ?? -1);
            $sortDefinition = $sortConfiguration->getSortDefinitionByIndex($columnIndex);
            if ($sortDefinition) {
                $sort = array_merge($sort, $sortDefinition->getSortOrder($value['dir']));
            }
        }

        if (empty($sort)) {
            foreach ($sortConfiguration->getAllDefinitions() as $sortDefinition) {
                $sort = array_merge($sort, $sortDefinition->getDefaultSortOrder());
            }
        }

        $collectionFilter->setSortOrder($sort);
    }

    protected function processFilters(
        FilterDefinitionInterface $definition,
        Request $request,
        CollectionFilter $collectionFilter
    ): void {
        $filterConfiguration = $definition->getFilterConfiguration();
        $criteria = [];
        foreach ($request->request->all()['columns'] ?? [] as $columnIndex => $column) {
            $filter = $filterConfiguration->getFilterByIndex($columnIndex);
            if ($filter && $column['searchable'] === 'true' && ($column['search']['value'] ?? '') !== '') {
                $criteria = array_merge($criteria, $filter->getFilter($column['search']['value']));
            }
        }

        $predefinedFilters = $definition->getPredefinedFilterConfiguration($request)->getAllFilters();
        $collectionFilter->setCriteria(
            array_merge(
                $definition->getDefaultFilterConfiguration($request)->getAllFilters(),
                $criteria,
                $predefinedFilters
            )
        );

        $collectionFilter->setPredefinedCriteria($predefinedFilters);
    }
}
