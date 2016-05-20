<?php

namespace Application\Api\Test\Recipe;

use Application\Bootstrap;
use PHPUnit_Framework_Assert as Assert;
use Symfony\Component\HttpKernel\Client;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Behat\Context\Context;
use Behat\Behat\Context\SnippetAcceptingContext;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Behat\Hook\Scope\AfterScenarioScope;

/**
 * Defines application features from the specific context.
 */
class RecipeContext implements Context, SnippetAcceptingContext
{
    /**
     * @var \Application\Bootstrap
     */
    protected $app;

    /**
     * @var \Symfony\Component\HttpKernel\Client
     */
    protected $client;

    /**
     * Initializes context.
     *
     * Every scenario gets its own context instance.
     * You can also pass arbitrary arguments to the
     * context constructor through behat.yml.
     */
    public function __construct()
    {
        $this->app = new Bootstrap('test');
        $this->app['debug'] = true;
        unset($this->app['exception_handler']);

        $this->client = new Client($this->app);
    }

    /**
     * @BeforeScenario
     */
    public function firstPageLoad(BeforeScenarioScope $scope)
    {
        // create new file if does not exist
        $this->app['csv.service']->readCsvFile();
    }

    /**
     * @AfterScenario
     */
    public function deleteFileLoad(AfterScenarioScope $scope)
    {
        // delete test file
        unlink($this->app['csv.path']);
    }

    /**
     * @Given collection of recipes with data:
     */
    public function collectionOfRecipesWithData(PyStringNode $data)
    {
        $data = json_decode($data->getRaw(), true);

        foreach ($data as $k => $params) {

            // validate
            $errors = $this->app['recipes.service']->validate($params);

            Assert::assertEmpty($errors, 'Recipe has not a valid data');

            // save
            $this->app['recipes.service']->createOne($params);
        }
    }

    /**
     * @When call :method :endpoint
     */
    public function call($method, $endpoint)
    {
        $this->client->request($method, "{$endpoint}");
    }

    /**
     * @When call :method :endpoint with resource id :resourceId
     */
    public function callWithResourceId($method, $endpoint, $resourceId)
    {
        $this->client->request($method, "{$endpoint}{$resourceId}");
    }

    /**
     * @When call :method :endpoint with resource id :resourceId with parameters:
     */
    public function callWithResourceIdWithParameters($method, $endpoint, $resourceId, PyStringNode $parameters)
    {
        $parameters = json_decode($parameters->getRaw(), true);
        $this->client->request($method, "{$endpoint}{$resourceId}", $parameters);
    }

    /**
     * @When call :method :endpoint with parameters:
     */
    public function callWithParameters($method, $endpoint, PyStringNode $parameters)
    {
        $parameters = json_decode($parameters->getRaw(), true);
        $this->client->request($method, "{$endpoint}", $parameters);
    }

    /**
     * @Then response status should be :statusCode
     */
    public function responseStatusShouldBe($statusCode)
    {
        Assert::assertEquals(
            $statusCode,
            $this->client->getResponse()->getStatusCode(),
            'HTTP code does not match '.$statusCode.
                ' (actual: '.$this->client->getResponse()->getStatusCode().')'
        );
    }

    /**
     * @Then response should be JSON
     */
    public function responseShouldBeJson()
    {
        Assert::assertInstanceOf(
            '\Symfony\Component\HttpFoundation\JsonResponse',
            $this->client->getResponse(),
            'Response was not JSON'
        );
    }

    /**
     * @Then items count should be :count
     */
    public function itemsCountShouldBe($count)
    {
        $data = json_decode($this->client->getResponse()->getContent(), true);
        Assert::assertCount((int) $count, $data);
    }

    /**
     * @Then response has a :arg1 property
     */
    public function responseHasAProperty($propertyName)
    {
        $data = json_decode($this->client->getResponse()->getContent(), true);

        Assert::assertTrue(
            array_key_exists($propertyName, $data),
            'Property does not exist'
        );
    }

    /**
     * @Then :propertyName property equals :value
     */
    public function propertyEquals($propertyName, $value)
    {
        $data = json_decode($this->client->getResponse()->getContent(), true);

        Assert::assertEquals(
            $data[$propertyName],
            $value,
            "$data[$propertyName] does not equal $value"
        );
    }

    /**
     * @Then json response should be:
     */
    public function jsonResponseShouldBe(PyStringNode $data)
    {
        Assert::assertJsonStringEqualsJsonString(
            $data->getRaw(),
            $this->client->getResponse()->getContent()
        );
    }

    /**
     * @Then response json items should be of type :type
     */
    public function responseJsonItemsShouldBeOfType($type)
    {
        $data = json_decode($this->client->getResponse()->getContent(), true);
        Assert::assertInternalType($type, $data);
    }
}
