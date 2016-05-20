<?php

/**
 * This file is part of the Symfony package.
 *
 * (c) Natalia Stanko <contact@nataliastanko.com>
 *
 * @copyright Copyright (c) 2016 Natalia Stanko (http://nataliastanko.com)
 * @license   http://www.opensource.org/licenses/mit-license.php
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Application;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\Routing\Loader\YamlFileLoader;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Debug\ExceptionHandler;
use Symfony\Bridge\Monolog\Logger;
use Silex\Provider\UrlGeneratorServiceProvider;
use Silex\Provider\ValidatorServiceProvider;
use Silex\Provider\ServiceControllerServiceProvider;
use Silex\Provider\HttpFragmentServiceProvider;
use Silex\Provider\MonologServiceProvider;
use Silex\Application as SilexApplication;
use Application\Api\Recipe\Service\RecipesService;
use Application\Api\Recipe\Model\Recipe;
use Application\Service\CsvService;
use Symfony\Component\HttpFoundation\Request;

/**
 * Setup application.
 *
 * @author Natalia Stanko <contact@nataliastanko.com>
 */
class Bootstrap extends SilexApplication
{
    /**
     * Set up with env variable.
     *
     * @param string $env env variable
     */
    public function __construct($env)
    {
        parent::__construct();

        // remember env
        $this['env'] = $env;

        // setup root variable
        $this['root'] = __DIR__.'/../';

        $this
            ->loadRoutes()
            ->setupDebugParameters()
            ->setupStorageParameters()
            ->registerProviders()
            ->registerServices();
    }

    /**
     * Register custom services.
     *
     * @return Bootstrap
     */
    private function registerServices()
    {
        $this['csv.service'] = $this->share(
            function () {
                return new CsvService(
                    $this['csv.path']
                );
            }
        );

        $this['recipes.service'] = $this->share(
            function () {
                return new RecipesService(
                    $this['csv.service'],
                    $this['validator']
                );
            }
        );

        return $this;
    }

    /**
     * Register handy tools to run application.
     *
     * @return Bootstrap
     */
    private function registerProviders()
    {
        $this->register(new UrlGeneratorServiceProvider());
        $this->register(new ValidatorServiceProvider());
        $this->register(new ServiceControllerServiceProvider());
        $this->register(new HttpFragmentServiceProvider());
        $this->register(
            new MonologServiceProvider(),
            [
                'monolog.logfile' => $this['root'].'/var/logs/'.$this['env'].'.log',
                'monolog.name' => 'Recipes API',
                'monolog.level' => $this->logLevel,
                ]
        );

        return $this;
    }

    /**
     * Load routes from yml file.
     *
     * @return Bootstrap
     */
    private function loadRoutes()
    {
        $this['routes'] = $this->extend(
            'routes',
            function (RouteCollection $routes, SilexApplication $app) {

                $locator = new FileLocator($this['root'].'config/routing');

                $router = new \Symfony\Component\Routing\Router(
                    new YamlFileLoader($locator),
                    'routes.yml',
                    [
                    'cache_dir' => $this['root'].'/cache',
                    ]
                );

                $routes->addCollection($router->getRouteCollection());

                return $routes;
            }
        );

        // add a listener to Silex Error event
        $this->error(
            function (\Exception $e, $code) {

                $this['monolog']->addError($e->getMessage());
                $this['monolog']->addError($e->getTraceAsString());

                if ($this['debug']) {
                    $data = [
                    'stacktrace' => $e->getTraceAsString(),
                    'message' => $e->getMessage(),
                    ];
                } else {
                    $data = [
                    'message' => $e->getMessage(),
                    ];
                }

                return $this->json($data, $code);

            }
        );

        /*
         * Before middleware
         * Check allowed fields
         *
         * @param Request $request HTTP request
         */
        $this->before(
            function (Request $request, SilexApplication $app) {
                $params = $request->request->all();

                // check params with allowed fields
                if (array_diff(array_keys($params), Recipe::getFields())) {
                    // call error handler
                    $app->abort(Response::HTTP_UNPROCESSABLE_ENTITY, 'No extra fields allowed');
                }

            }, 100
        );

        /*
         * Before middleware
         * The request body should be parsed as JSON
         *
         * @param Request $request HTTP request
         */
        $this->before(
            function (Request $request) {
                if ($request->getMethod() === 'OPTIONS') {
                    $response = new Response();

                    $response->headers->set(
                        'Access-Control-Allow-Origin',
                        '*'
                    );

                    $response->headers->set(
                        'Access-Control-Allow-Methods',
                        'GET,POST,PUT,DELETE,OPTIONS'
                    );

                    $response->headers->set(
                        'Access-Control-Allow-Headers',
                        'Content-Type'
                    );

                    $response->setStatusCode(Response::HTTP_OK);

                    return $response->send();
                }

                if (0 === strpos($request->headers->get('Content-Type'), 'application/json')) {
                    $data = json_decode($request->getContent(), true);

                    if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
                        throw new \LogicException(sprintf('Failed to parse json string "%s", error: "%s"', $data, json_last_error_msg()));
                    }

                    $request->request->replace(is_array($data) ? $data : []);
                }
            }, 500
        );

        /*
         * Before middleware
         * Handle CORS respons with right headers
         *
         * @param Request  $request HTTP request
         * @param Response $request HTTP response
         */
        $this->after(
            function (Request $request, Response $response) {
                $response->headers->set(
                    'Access-Control-Allow-Origin',
                    '*'
                );

                $response->headers->set(
                    'Access-Control-Allow-Methods',
                    'GET,POST,PUT,DELETE,OPTIONS'
                );
            }
        );

        return $this;
    }

    /**
     * Storage parameters.
     *
     * @return Bootstrap
     */
    private function setupStorageParameters()
    {
        $fileName = 'data_'.$this['env'].'.csv';
        $this['csv.path'] = $this['root'].'storage/'.$fileName;

        return $this;
    }

    /**
     * Set level of log requests and errors.
     *
     * @return Bootstrap
     */
    private function setupDebugParameters()
    {
        switch ($this['env']) {

        case 'test':
            $this->logLevel = Logger::DEBUG;
            ExceptionHandler::register(true);
            break;

        case 'dev':
            $this->logLevel = Logger::DEBUG;
            ExceptionHandler::register(true);
            break;

        case 'prod':
            $this->logLevel = Logger::ERROR;
            ExceptionHandler::register(false);
            break;

        default:
            $this->logLevel = Logger::ERROR;
            ExceptionHandler::register(false);
            break;

        }

        return $this;
    }
}
