<?php

namespace Application\Converter;

use Ddeboer\DataImport\ItemConverter\ItemConverterInterface;
use Application\Api\Recipe\Model\Recipe;

/**
 * Reorder data to match header columns.
 */
class ReorderConverter implements ItemConverterInterface
{
    /**
     * Convert input value
     * Reorder fields in array to match header columns.
     *
     * @param array $input
     *
     * @return array
     */
    public function convert($input)
    {
        $orderedList = [];
        $fields = Recipe::getFields();

        foreach ($fields as $fieldname) {
            $orderedList[$fieldname] = isset($input[$fieldname]) ? $input[$fieldname] : null;
        }

        return $orderedList;
    }
}
