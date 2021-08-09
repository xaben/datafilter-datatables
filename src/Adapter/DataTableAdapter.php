<?php

declare(strict_types=1);

namespace Xaben\DataFilterDataTables\Adapter;

use Xaben\DataFilter\Adapter\Adapter;
use Xaben\DataFilter\Adapter\BaseAdapter;
use Xaben\DataFilter\Definition\FilterDefinition;
use Xaben\DataFilter\Filter\CollectionFilter;
use Xaben\DataFilter\Pagination\PaginationConfiguration;

class DataTableAdapter extends BaseAdapter implements Adapter
{
    protected function processPagination(
        FilterDefinition $definition,
        array $requestParameters,
        CollectionFilter $collectionFilter
    ): void {
        $paginationConfiguration = $definition->getPaginationConfiguration();
        if (!$paginationConfiguration) {
            return;
        }

        [$offset, $limit] = $paginationConfiguration->getByOffset(
            (int) ($requestParameters['start'] ?? 0),
            (int) ($requestParameters['length'] ?? PaginationConfiguration::DEFAULT_RESULT_COUNT)
        );

        $collectionFilter->setOffset($offset);
        $collectionFilter->setLimit($limit);
    }

    protected function processSortable(
        FilterDefinition $definition,
        array $requestParameters,
        CollectionFilter $collectionFilter
    ): void {
        $sortConfiguration = $definition->getSortConfiguration();
        $sort = [];
        foreach ($requestParameters['order'] ?? [] as $value) {
            $columnIndex = (int) ($value['column'] ?? -1);
            $sortDefinition = $sortConfiguration->getSortDefinition($columnIndex);
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
        FilterDefinition $definition,
        array $requestParameters,
        CollectionFilter $collectionFilter
    ): void {
        $filterConfiguration = $definition->getFilterConfiguration();
        $criteria = [];
        foreach ($requestParameters['columns'] ?? [] as $columnIndex => $column) {
            $filter = $filterConfiguration->getFilter($columnIndex);
            if ($filter && $column['searchable'] === 'true' && ($column['search']['value'] ?? '') !== '') {
                $criteria = array_merge($criteria, $filter->getFilter($column['search']['value']));
            }
        }

        $collectionFilter->setDefaultCriteria($definition->getDefaultFilterConfiguration($requestParameters)->getCriteria());
        $collectionFilter->setUserCriteria($criteria);
        $collectionFilter->setPredefinedCriteria($definition->getPredefinedFilterConfiguration($requestParameters)->getCriteria());
    }
}
