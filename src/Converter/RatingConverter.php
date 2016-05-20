<?php

namespace Application\Converter;

use Ddeboer\DataImport\ItemConverter\ItemConverterInterface;

/**
 * Set default rating.
 */
class RatingConverter implements ItemConverterInterface
{
    /**
     * Convert input value
     *Set defeault rating value.
     *
     * @param array $row
     *
     * @return array
     */
    public function convert($row)
    {
        if (!isset($row['votes_count'])) {
            $row['votes_count'] = 0;
        }

        if (!isset($row['votes_sum'])) {
            $row['votes_sum'] = 0;
        }

        if (!isset($row['rating_value'])) {
            $row['rating_value'] = 0;
        }

        return $row;
    }
}
