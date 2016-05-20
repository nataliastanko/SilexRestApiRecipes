<?php

namespace Application\Api\Recipe\Service;

use Application\Service\CsvService;
use Symfony\Component\Validator\ValidatorInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Handle recipes.
 */
class RecipesService
{
    /**
     * @var CsvService
     */
    private $csv;

    /**
     * @var ValidatorInterface
     */
    private $validator;

    /**
     * Service constructor.
     *
     * @param CsvService         $csv
     * @param ValidatorInterface $validator Symfony validator
     */
    public function __construct(CsvService $csv, ValidatorInterface $validator)
    {
        $this->csv = $csv;
        $this->validator = $validator;
    }

    /**
     * Validate params.
     *
     * @param array $params post params
     *
     * @return array errors messages
     */
    public function validate($params)
    {
        /*
         * Assign fields constraints
         *
         * @var Assert\Collection
         */
        $constraint = new Assert\Collection(
            [
                'fields' => [
                    'title' => new Assert\NotBlank(),
                    'recipe_cuisine' => new Assert\NotBlank(),
                    'marketing_description' => [
                        new Assert\NotBlank(),
                    ],
                    'box_type' => [
                        new Assert\NotBlank(),
                        new Assert\Choice(
                            [
                            'choices' => ['gourmet', 'vegetarian'],
                            ]
                        ),
                    ],
                    'recipe_diet_type_id' => [
                        new Assert\NotBlank(),
                        new Assert\Choice(
                            [
                            'choices' => ['fish', 'meat', 'vegetarian'],
                            ]
                        ),
                    ],
                    'preparation_time_minutes' => [
                        new Assert\Type(
                            [
                            'type' => 'integer',
                            ]
                        ),
                    ],
                    'calories_kcal' => [
                        new Assert\Type(
                            [
                            'type' => 'integer',
                            ]
                        ),
                    ],
                    'protein_grams' => [
                        new Assert\Type(
                            [
                            'type' => 'integer',
                            ]
                        ),
                    ],
                    'carbs_grams' => [
                        new Assert\Type(
                            [
                            'type' => 'integer',
                            ]
                        ),
                    ],
                    'shelf_life_days' => [
                        new Assert\Type(
                            [
                            'type' => 'integer',
                            ]
                        ),
                    ],
                ],
                'allowExtraFields' => true,
            ]
        );

        /*
         * Validation errors
         *
         * @var array
         */
        $errors = $this->validator->validate($params, $constraint);

        if (count($errors) > 0) {
            foreach ($errors as $error) {
                $path = $onlyconsonants = str_replace(['[', ']'], '', $error->getPropertyPath());
                $errorMessages[$path][] = $error->getMessage();
            }

            return $errorMessages;
        }

        return $errors = [];
    }

    /**
     * Paginate recipes.
     *
     * @param int        $offset  start from offset element
     * @param int        $limit   how many recipes
     * @param array|null $filters field name => value filter fields array
     *
     * @return array paginated recipes
     */
    public function getAll($offset, $limit, $filterFields = null)
    {
        $recipes = $this->csv->getAll($offset, $limit, $filterFields);
        $recipes = array_slice($recipes, $offset, $limit);

        return $recipes;
    }

    /**
     * Find one recipe by id.
     *
     * @param string     $field field name
     * @param int|string $param recipe param
     *
     * @return array|null recipe
     */
    public function findOne($field, $param)
    {
        $recipes = $this->csv->filterByField($field, $param);

        if ($recipes) {
            return array_pop($recipes);
        }

        return;
    }

    /**
     * Create one recipe.
     *
     * @param string $param params to save
     *
     * @return array recipe
     */
    public function createOne($params)
    {
        return $this->csv->addToStorage($params);
    }

    /**
     * Create one recipe.
     *
     * @param array $params params to save
     * @param int   $id     recipe id
     *
     * @return array recipe
     */
    public function updateOne($params, $id)
    {
        return $this->csv->updateStorage($params, $id);
    }

    /**
     * Delete one recipe.
     *
     * @param int $id recipe id
     */
    public function removeOne($id)
    {
        $this->csv->rmFromStorage($id);
    }

    /**
     * Vote for recipe.
     *
     * @param int $vote vote
     * @param int $id   recipe id
     *
     * @return float current rating value
     */
    public function vote($vote, $id)
    {
        return $this->csv->vote($vote, $id);
    }
}
