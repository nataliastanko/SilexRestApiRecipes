<?php

namespace Application\Service;

use Symfony\Component\HttpFoundation\Response;
use Ddeboer\DataImport\Workflow;
use Ddeboer\DataImport\Reader\CsvReader;
use Ddeboer\DataImport\Reader\ArrayReader;
use Ddeboer\DataImport\Writer\CallbackWriter;
use Ddeboer\DataImport\Writer\CsvWriter;
use Ddeboer\DataImport\ValueConverter\DateTimeValueConverter;
use Ddeboer\DataImport\Filter\CallbackFilter;
use Ddeboer\DataImport\ItemConverter\CallbackItemConverter;
use Application\Api\Recipe\Model\Recipe;
use Application\Converter\ReorderConverter;
use Application\Converter\DateTimeConverter;
use Application\Converter\RatingConverter;

/**
 * Handle operations on csv files.
 */
class CsvService
{
    /**
     * Data filter.
     *
     * @var /Closure
     */
    protected $filter;

    /**
     * Data path to storage file.
     *
     * @var string
     */
    protected $csvPath;

    /**
     * Servce contructor.
     *
     * @param string $csvPath path to storage file
     */
    public function __construct($csvPath)
    {
        $this->csvPath = $csvPath;
        $this->filter = null;
    }

    /**
     * Paginate data from file.
     *
     * @param int        $offset  offset number item
     * @param int        $limit   max limit item
     * @param array|null $filters field name => value filter fields array
     *
     * @return array storage
     */
    public function getAll($offset, $limit, $filterFields = null)
    {
        $this->filter = null;

        // optional data filer
        if ($filterFields) {
            foreach ($filterFields as $field => $param) {

                // create closure
                $paramsFilter = function (Workflow $workflow) use ($field, $param) {

                    // create universal callback filter
                    $filterFields = new CallbackFilter(
                        function ($data) use ($field, $param) {
                            return $param == $data[$field];
                        }
                    );

                    // add callback filter
                    $workflow->addFilter($filterFields);

                };

                $this->setFilter($paramsFilter);
            }
        }

        return $this->readCsvFile();
    }

    /**
     * Filter storage function.
     *
     * @param string $field field name
     * @param string $param field value
     *
     * @return array filtered data
     */
    public function filterByField($field, $param)
    {
        $this->filter = null;

        // create data filter
        $injectFilter = function (Workflow $workflow) use ($field, $param) {

            // create callback filter
            $callbackFilter = new CallbackFilter(
                function ($data) use ($field, $param) {
                    return $param == $data[$field];
                }
            );

            // add callback filter
            $workflow->addFilter($callbackFilter);

        };

        // retrieve filtered data
        return $this
            ->setFilter($injectFilter)
            ->readCsvFile();
    }

    /**
     * Set closure filter.
     *
     * @param \Closure $filter
     *
     * @return CsvService
     */
    protected function setFilter(\Closure $filter)
    {
        $this->filter = $filter;

        return $this;
    }

    /**
     * Get closure filter if any.
     *
     * @return \Closure|null $filter
     */
    protected function getFilter()
    {
        return $this->filter;
    }

    /**
     * Read csv file
     * Check if storage file exist and can be loaded.
     *
     * @param array $storage to save
     *
     * @return array storage
     */
    public function readCsvFile($storage = [])
    {
        try {

            // create and configure the reader
            $file = new \SplFileObject($this->csvPath);
        } catch (\Exception $e) {
            $storage = $this->addHeader($storage);

            // Create the workflow from the reader
            $workflow = new Workflow(new ArrayReader($storage));

            // add the writer to the workflow
            $writer = new CsvWriter(',', '"', fopen($this->csvPath, 'w'));

            $workflow->addWriter($writer);

            // Process the workflow
            $workflow->process();

            $storage = []; // clear storage

            $file = new \SplFileObject($this->csvPath);
        }

        if (!$file->isReadable()) {
            throw new \Exception('Storage file is not readable', Response::HTTP_UNAUTHORIZED);
        }

        $csvReader = new CsvReader($file, ',');

        // tell the reader that the first row in the CSV file contains column headers
        $csvReader->setHeaderRowNumber(0);

        // create the workflow from the reader
        $workflow = new Workflow($csvReader);

        // assign filters if any
        if ($this->getFilter() instanceof \Closure) {
            $filter = $this->getFilter();
            $filter($workflow);
        }

        // Use a fictional $translator service to translate each value
        $converter = new CallbackItemConverter(
            function ($row) {

                $row['votes_count'] = isset($row['votes_count']) ? $row['votes_count'] : 0;
                $row['votes_sum'] = isset($row['votes_sum']) ? $row['votes_sum'] : 0;
                $row['rating_value'] = isset($row['rating_value']) ? $row['rating_value'] : 0;

                return $row;

            }
        );

        $workflow->addItemConverter($converter);

        // add a converter - convert `created_at` and `updated_at` to \DateTime objects
        $dateTimeConverter = new DateTimeValueConverter('d/m/Y H:i:s');

        // save filtered and converted data from file to storage variable
        $workflow
            ->addValueConverter('created_at', $dateTimeConverter)
            ->addValueConverter('updated_at', $dateTimeConverter)
            ->addWriter(
                new CallbackWriter(
                    function ($row) use (&$storage) {
                        $storage[] = $row;
                    }
                )
            );

        $workflow->process();

        return $storage;
    }

    /**
     * Write collected data to csv file.
     *
     * @return array storage
     */
    protected function writeCsvFile($storage = [])
    {
        // Create the workflow from the reader
        $workflow = new Workflow(new ArrayReader($storage));

        // add the writer to the workflow
        $writer = new CsvWriter(',', '"', fopen($this->csvPath, 'w'));

        $workflow->addWriter($writer);

        // create datetime to string converter
        $dateTimeConverter = new DateTimeConverter();
        $workflow->addItemConverter($dateTimeConverter);

        // create rating converter
        $ratingConverter = new RatingConverter();
        $workflow->addItemConverter($ratingConverter);

        // sort data to match columns
        $reorderConverter = new ReorderConverter();
        $workflow->addItemConverter($reorderConverter);

        // Process the workflow
        $workflow->process();

        return $storage;
    }

    /**
     * Find the next id that should be inserted.
     *
     * @return int id
     */
    protected function findNextId($storage = [])
    {
        if ($storage) {
            $max = max(
                array_map(
                    function ($row) {
                        return $row['id'];
                    },
                    $storage
                )
            );
        } else {
            $max = 0;
        }

        return $max += 1;
    }

    /**
     * Add new item to storage therefore rewrite storage file.
     *
     * @param array $params element to add
     *
     * @return array $params added element
     */
    public function addToStorage($params)
    {
        // get data from file
        $storage = $this->getAll(0, null);

        // find next id
        $params['id'] = $this->findNextId($storage);
        $params['created_at'] = $params['updated_at'] = new \DateTime('now');

        // save file
        $storage = $this->addNewElement($params, $storage);
        $storage = $this->sortById($storage);
        $storage = $this->addHeader($storage);
        $storage = $this->writeCsvFile($storage);

        // reorder array
        $reorderConverter = new ReorderConverter();
        $params = $reorderConverter->convert($params);

        return $params;
    }

    /**
     * Update row.
     *
     * @param int   $id     element id
     * @param array $params element to add
     *
     * @return array $params added element
     */
    public function updateStorage($params, $id)
    {
        // get data from file
        $storage = $this->getAll(0, null);

        $params['updated_at'] = new \DateTime('now');
        $params['id'] = $id;

        // rm old elem
        foreach ($storage as $key => $elem) {
            if ($elem['id'] == $id) {
                $params['created_at'] = $elem['created_at'];
                unset($storage[$key]);
            }
        }

        // save file
        $storage = $this->addNewElement($params, $storage);
        $storage = $this->sortById($storage);
        $storage = $this->addHeader($storage);
        $storage = $this->writeCsvFile($storage);

        // reorder array
        $reorderConverter = new ReorderConverter();
        $params = $reorderConverter->convert($params);

        return $params;
    }

    /**
     * Delete row.
     *
     * @param int $id element id
     */
    public function rmFromStorage($id)
    {
        // get data from file
        $storage = $this->getAll(0, null);

        // rm old elem
        foreach ($storage as $key => $elem) {
            if ($elem['id'] == $id) {
                unset($storage[$key]);
            }
        }

        // save file
        $storage = $this->sortById($storage);
        $storage = $this->addHeader($storage);
        $this->writeCsvFile($storage);
    }

    /**
     * Save rating value.
     *
     * @param int $vote vote [1..5]
     * @param int $id   element id
     *
     * @return float
     */
    public function vote($vote, $id)
    {
        // get data from file
        $storage = $this->getAll(0, null);

        $params['updated_at'] = new \DateTime('now');
        $params['id'] = $id;

        // rm old elem
        foreach ($storage as $key => $elem) {
            if ($elem['id'] == $id) {
                $params['created_at'] = $elem['created_at'];
                $params['votes_count'] = $elem['votes_count'];
                $params['votes_sum'] = $elem['votes_sum'];
                unset($storage[$key]);
            }
        }

        // count rating
        $params['votes_count'] += 1; // amount of votes
        $params['votes_sum'] += $vote; // sum of votes
        $params['rating_value'] = $params['votes_sum'] / $params['votes_count'];

        // save file
        $storage = $this->addNewElement($params, $storage);
        $storage = $this->sortById($storage);
        $storage = $this->addHeader($storage);
        $storage = $this->writeCsvFile($storage);

        // reorder array
        $reorderConverter = new ReorderConverter();
        $params = $reorderConverter->convert($params);

        return $params;
    }

    /**
     * Sort storage by id keys.
     *
     * @return CsvService
     */
    protected function sortById($storage)
    {
        usort(
            $storage, function ($leftRow, $rightRow) {

                if ($leftRow['id'] == $rightRow['id']) {
                    return 0;
                }

                // sort ASC by id
                return ($leftRow['id'] < $rightRow['id']) ? -1 : 1;

            }
        );

        return $storage;
    }

    /**
     * Add header row to save.
     *
     * @return CsvService
     */
    protected function addHeader($storage)
    {
        // add header as a first row
        $fields = Recipe::getFields();
        $header = array_combine($fields, $fields);
        array_unshift($storage, $header);

        return $storage;
    }

    /**
     * Add new data to save.
     *
     * @param array $params params to add
     *
     * @return CsvService
     */
    protected function addNewElement($params, $storage)
    {
        array_push($storage, $params);

        return $storage;
    }
}
