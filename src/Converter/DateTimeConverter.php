<?php

namespace Application\Converter;

use Ddeboer\DataImport\ItemConverter\ItemConverterInterface;

/**
 * Save date as a string.
 */
class DateTimeConverter implements ItemConverterInterface
{
    /**
     * Convert input value
     * Transform DateTime objects to string.
     *
     * @param array $row
     *
     * @return array
     */
    public function convert($row)
    {
        if (isset($row['created_at']) && $row['created_at'] instanceof \DateTime) {
            $row['created_at'] = $row['created_at']->format('d/m/Y H:i:s');
        }

        if (isset($row['updated_at']) && $row['updated_at'] instanceof \DateTime) {
            $row['updated_at'] = $row['updated_at']->format('d/m/Y H:i:s');
        }

        return $row;
    }
}
