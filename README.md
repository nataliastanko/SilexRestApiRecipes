# Silex API example

This is my personal solution for [Recipes API][API] - RESTful API using Silex.

### Requirements

PHP >= 5.6

Install [composer][getcomposer] to manage dependencies.

## Prepare to run

To install dependencies run command in project root dir:

    composer install

    php --server 0:3001 -t web/

Check out [http://localhost:3001][localhost].

## How to use

### API documentation

- GET       http://localhost:3001/api/v1/recipes
- GET       http://localhost:3001/api/v1/recipes?offset={offset}&limit={limit}&recipe_cuisine={cuisine}}
- POST      http://localhost:3001/api/v1/recipes/
- PUT       http://localhost:3001/api/v1/recipes/{id}
- DELETE    http://localhost:3001/api/v1/recipes/{id}
- PATCH     http://localhost:3001/api/v1/recipes/{id}/vote/{vote}

### Run an example

* Get all (default 10 at once) recipes

        curl -X GET http://localhost:3001/api/v1/recipes/ -d '{}' -H 'Content-Type: application/json' -w "\n"

* Paginate recipes

        curl -X GET http://localhost:3001/api/v1/recipes/?offset=3&limit=2 -d '{}' -H 'Content-Type: application/json' -w "\n"

* Paginate recipes, filter by recipe_cuisine

        curl -X GET http://localhost:3001/api/v1/recipes/?offset=3&limit=2&recipe_cuisine=british -d '{}' -H 'Content-Type: application/json' -w "\n"

* Get single

        curl -X GET http://localhost:3001/api/v1/recipes/1 -d '{}' -H 'Content-Type: application/json' -w "\n"

* Create

        curl -X POST http://localhost:3001/api/v1/recipes/ -d '{"recipe_cuisine": "british", "box_type": "vegetarian", "title": "Hello broccoli!", "marketing_description": "Very healthy food", "calories_kcal": 400, "protein_grams": 100, "fat_grams": 100, "carbs_grams": 200, "recipe_diet_type_id": "vegetarian", "base": "broccoli", "protein_source": "broccoli", "preparation_time_minutes": 35, "shelf_life_days": 4 }' -H 'Content-Type: application/json' -w "\n"

* Update

        curl -X PUT http://localhost:3001/api/v1/recipes/3 -d '{"recipe_cuisine": "british", "box_type": "vegetarian", "title": "Hello tomatoe!", "marketing_description": "Yummie food", "calories_kcal": 400, "protein_grams": 100, "fat_grams": 100, "carbs_grams": 200, "recipe_diet_type_id": "vegetarian", "base": "tomatoe!", "protein_source": "tomatoe!", "preparation_time_minutes": 35, "shelf_life_days": 4 }' -H 'Content-Type: application/json' -w "\n"

* Delete

        curl -X DELETE http://localhost:3001/api/v1/recipes/1 -d '{}' -H 'Content-Type: application/json' -w "\n"

* Vote

        curl -X DELETE http://localhost:3001/api/v1/recipes/1/vote/5 -d '{}' -H 'Content-Type: application/json' -w "\n"

### Run the tests

* Behat

        bin/behat

## API consumers

* API is a safe method of data access for for mobile applications and frontend websites. It provides JSON response that are language independent.
* Clients is not concerned about data storage.
* Application uses Representational State Transfer (GET, POST, PUT, DELETE, PATCH) request message and numeric status codes with text message phrases.
* Request should have 'Content-Type: application/json' header. Cross-domain communication is allowed.
* For mobile applications API security I would recommend using OAuth2 token authentication.

## Tools used to build the API

- [Silex][silex] - php micro-framework based on Symfony components
- [Behat][behat] - BDD tests
- [ddeboer/data-import][csvimport] - CSV reader and writer symfony component
- [Codefixer][codefixer] - PHP Coding Standards Fixer

## Why Silex

Silex is a micro-framework, one of the fastest RESTful frameworks. It's light, extensible and perfect for small projects.
I have knowledge of Symfony 2 and Silex is built on Symfony components. Symfony components are wery well documented and have a great community support.

I considered choosing Symfony framework but I decided it has too many components installed by default e.g. database, assets, twig.

## License

The MIT License (MIT).
Copyright © 2016, Natalia Stanko [nataliastanko.com][nataliastanko]

[API]: <api.md>

[getcomposer]: http://getcomposer.org/

[localhost]: http://localhost:3001/

[silex]: http://silex.sensiolabs.org/

[behat]: http://docs.behat.org/en/v3.0/

[phpunit]: https://phpunit.de/manual/current/en/installation.html

[nataliastanko]: http://nataliastanko.com/

[codefixer]: http://cs.sensiolabs.org/

[csvimport]: https://github.com/ddeboer/data-import
