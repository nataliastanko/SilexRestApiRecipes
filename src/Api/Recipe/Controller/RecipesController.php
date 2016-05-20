<?php

namespace Application\Api\Recipe\Controller;

use Silex\Application as SilexApplication;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Recipe API controller.
 */
class RecipesController
{
    /**
     * Show a single recipe.
     *
     * @param Request          $request HTTP request
     * @param SilexApplication $app     application container
     *
     * @return JsonResponse
     */
    public function index(Request $request, SilexApplication $app)
    {
        /*
         * Filter fields array
         *
         * @var array|null
         */
        $filterFields = [];

        /*
         * Offset element number as first
         *
         * @var integer|null
         */
        $offset = (int) $request->get('offset', 0);

        /*
         * How many elements returned
         *
         * @var integer|null
         */
        $limit = (int) $request->get('limit', 10);

        /*
         * Type of cuisine
         *
         * @var string|null
         */
        $recipeCuisine = $request->get('recipe_cuisine');

        if ($recipeCuisine) {
            $filterFields['recipe_cuisine'] = $recipeCuisine;
        }

        /*
         * Existing recipes.
         *
         * @var array
         */
        $recipes = $app['recipes.service']->getAll($offset, $limit, $filterFields);

        return $app->json($recipes, Response::HTTP_OK);
    }

    /**
     * Show a single recipe.
     *
     * @param Request          $request HTTP request
     * @param SilexApplication $app     application container
     * @param int              $id      recipe id
     *
     * @return JsonResponse
     */
    public function show(Request $request, SilexApplication $app, $id)
    {
        /*
         * Single recipe data.
         *
         * @var array
         */
        $recipe = $app['recipes.service']->findOne('id', $id);
        if (!$recipe) {
            // call error handler
            $app->abort(Response::HTTP_NOT_FOUND, "Recipe {$id} does not exist.");
        }

        return $app->json($recipe, Response::HTTP_OK);
    }

    /**
     * Create recipe.
     *
     * @param Request          $request HTTP request
     * @param SilexApplication $app     application container
     *
     * @return JsonResponse
     */
    public function create(Request $request, SilexApplication $app)
    {
        $params = $request->request->all();

        try {

            // validate
            $errors = $app['recipes.service']->validate($params);
            if ($errors) {
                return $app->json(['message' => $errors], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            // save
            $recipe = $app['recipes.service']->createOne($params);
        } catch (\Exception $e) {
            // log for further review
            $app['monolog']->addError($e->getMessage());
            $app['monolog']->addError($e->getTraceAsString());
            // call error handler
            $app->abort(Response::HTTP_UNPROCESSABLE_ENTITY, 'Recipe data is not valid');
        }

        return $app->json($recipe, Response::HTTP_CREATED);
    }

    /**
     * Update a single recipe.
     *
     * @param Request          $request HTTP request
     * @param SilexApplication $app     application container
     * @param int              $id      recipe id
     *
     * @return JsonResponse
     */
    public function update(Request $request, SilexApplication $app, $id)
    {
        $params = $request->request->all();

        $recipe = $app['recipes.service']->findOne('id', $id);
        if (!$recipe) {
            // call error handler
            $app->abort(Response::HTTP_NOT_FOUND, "Recipe {$id} does not exist.");
        }

        try {

            // validate
            $errors = $app['recipes.service']->validate($params);

            if ($errors) {
                return $app->json(['message' => $errors], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            // save
            $recipe = $app['recipes.service']->updateOne($params, $id);
        } catch (\Exception $e) {

            // log for further review
            $app['monolog']->addError($e->getMessage());
            $app['monolog']->addError($e->getTraceAsString());
            // call error handler
            $app->abort(Response::HTTP_UNPROCESSABLE_ENTITY, 'Recipe data can not be updated.');
        }

        return $app->json($recipe, Response::HTTP_OK);
    }

    /**
     * Delete recipe.
     *
     * @param Request          $request HTTP request
     * @param SilexApplication $app     application container
     * @param int              $id      recipe id
     *
     * @return JsonResponse
     */
    public function delete(Request $request, SilexApplication $app, $id)
    {
        $recipe = $app['recipes.service']->findOne('id', $id);
        if (!$recipe) {
            // call error handler
            $app->abort(Response::HTTP_NOT_FOUND, "Recipe {$id} does not exist.");
        }

        try {
            $app['recipes.service']->removeOne($id);
        } catch (\Exception $e) {

            // log for further review
            $app['monolog']->addError($e->getMessage());
            $app['monolog']->addError($e->getTraceAsString());
            // call error handler
            $app->abort(Response::HTTP_UNPROCESSABLE_ENTITY, 'Recipe data can not be deleted.');
        }

        return $app->json(['deleted' => true], Response::HTTP_OK);
    }

    /**
     * Vote.
     *
     * @param Request          $request HTTP request
     * @param SilexApplication $app     application container
     * @param int              $id      recipe id
     *
     * @return JsonResponse
     */
    public function vote(Request $request, SilexApplication $app, $id, $vote)
    {
        $recipe = $app['recipes.service']->findOne('id', $id);
        if (!$recipe) {
            // call error handler
            $app->abort(Response::HTTP_NOT_FOUND, "Recipe {$id} does not exist.");
        }

        try {

            // validate
            if (!in_array($vote, range(1, 5))) {
                return $app->json(['message' => $errors], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $recipe = $app['recipes.service']->vote($vote, $id);
        } catch (\Exception $e) {

            // log for further review
            $app['monolog']->addError($e->getMessage());
            $app['monolog']->addError($e->getTraceAsString());
            // call error handler
            $app->abort(Response::HTTP_UNPROCESSABLE_ENTITY, 'Recipe data can not be deleted.');
        }

        return $app->json(
            [
            'rating_value' => $recipe['rating_value'],
            'votes_sum' => $recipe['votes_sum'],
            'votes_count' => $recipe['votes_count'],
            ],
            Response::HTTP_OK
        );
    }
}
