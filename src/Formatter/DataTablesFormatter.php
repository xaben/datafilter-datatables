<?php

declare(strict_types=1);

namespace Xaben\DataFilterDataTables\Formatter;

use Xaben\DataFilter\Filter\Result;
use Xaben\DataFilter\Formatter\Formatter;
use Xaben\DataFilter\Transformer\Transformer;

class DataTablesFormatter implements Formatter
{
    public function format(Result $result, Transformer $transformer): array
    {
        return [
            'recordsTotal' => $result->getTotalResults(),
            'recordsFiltered' => $result->getFilteredResults(),
            'data' => $transformer->transformCollection($result->getData()),
        ];
    }
}
