Feature: application API
    In order to check if the API is working
    As an API client

    Background:
        Given collection of recipes with data:
        """
        [
            {
                "box_type": "vegetarian",
                "title": "Vegetarian pasta",
                "marketing_description": "Tasty pasta",
                "recipe_cuisine": "asian",
                "calories_kcal": 300,
                "protein_grams": 50,
                "fat_grams": 50,
                "carbs_grams": 200,
                "recipe_diet_type_id": "vegetarian",
                "base": "pasta",
                "protein_source": "beans",
                "preparation_time_minutes": 15,
                "shelf_life_days": 3
            },
            {
                "box_type": "gourmet",
                "title": "Seafood pasta",
                "marketing_description": "Splendid seafood pasta",
                "recipe_cuisine": "thai",
                "calories_kcal": 350,
                "protein_grams": 50,
                "fat_grams": 100,
                "carbs_grams": 200,
                "recipe_diet_type_id": "fish",
                "base": "pasta",
                "protein_source": "fish",
                "preparation_time_minutes": 25,
                "shelf_life_days": 2
            },
            {
                "box_type": "vegetarian",
                "title": "Lentil vegetable lasagne",
                "marketing_description": "Very satiating food",
                "recipe_cuisine": "italian",
                "calories_kcal": 400,
                "protein_grams": 100,
                "fat_grams": 100,
                "carbs_grams": 200,
                "recipe_diet_type_id": "vegetarian",
                "base": "lentil",
                "protein_source": "lentil",
                "preparation_time_minutes": 35,
                "shelf_life_days": 4
            }
        ]
        """

    Scenario: Get list of recipes
        When call "GET" "/api/v1/recipes/"
        Then response should be JSON
        And response status should be "200"
        And response json items should be of type "array"
        And items count should be "3"

    Scenario: Get list of recipes and filter by cuisine
        When call "GET" "/api/v1/recipes/?recipe_cuisine=mediterranean"
        Then response should be JSON
        And response status should be "200"
        And response json items should be of type "array"
        And items count should be "0"

    Scenario: Get list of recipes and filter by cuisine
        When call "GET" "/api/v1/recipes/?recipe_cuisine=asian"
        Then response should be JSON
        And response status should be "200"
        And response json items should be of type "array"
        And items count should be "1"

    Scenario: Paginate recipes
        When call "GET" "/api/v1/recipes/?offset=2&limit=10"
        Then response should be JSON
        And response status should be "200"
        And items count should be "1"

    Scenario: Fail to get recipe that doesn't exists
        When call "GET" "/api/v1/recipes/" with resource id "100"
        Then response should be JSON
        And response status should be "404"

    Scenario: Get recipe that exists
        When call "GET" "/api/v1/recipes/" with resource id "2"
        Then response should be JSON
        And response status should be "200"
        And response has a "title" property
        And "title" property equals "Seafood pasta"

    Scenario: Fail to create recipe with invalid data
        When call "POST" "/api/v1/recipes/" with parameters:
        """
        {
            "box_type": "vegetarian",
            "title": "Hello tomatoe!"
        }
        """
        Then response should be JSON
        And response status should be "422"

    Scenario: Fail to create recipe with extra fields
        When call "POST" "/api/v1/recipes/" with parameters:
        """
        {
            "something": "else",
            "title": "Spinach vegetable lasagne"
        }
        """
        Then response should be JSON
        And response status should be "422"

    Scenario: Create recipe
        When call "POST" "/api/v1/recipes/" with parameters:
        """
        {
            "box_type": "vegetarian",
            "title": "Spinach vegetable lasagne",
            "marketing_description": "Healthy!",
            "recipe_cuisine": "italian",
            "calories_kcal": 300,
            "protein_grams": 50,
            "fat_grams": 50,
            "carbs_grams": 200,
            "recipe_diet_type_id": "vegetarian",
            "base": "pasta",
            "protein_source": "spinach",
            "preparation_time_minutes": 35,
            "shelf_life_days": 4
        }
        """
        Then response should be JSON
        And response status should be "201"
        And response has a "marketing_description" property
        And "marketing_description" property equals "Healthy!"
        And response has a "id" property

    Scenario: Fail to update recipe that doesn't exists
        When call "PUT" "/api/v1/recipes/" with resource id "100" with parameters:
        """
        {
            "title": "Hello tomatoe!"
        }
        """
        Then response should be JSON
        And response status should be "404"

    Scenario: Fail to update recipe with extra fields
        When call "PUT" "/api/v1/recipes/" with resource id "100" with parameters:
        """
        {
            "name": "Hello spinach!"
        }
        """
        Then response should be JSON
        And response status should be "422"

    Scenario: Fail to update recipe with invalid data
        When call "PUT" "/api/v1/recipes/" with resource id "1" with parameters:
        """
        {
            "title": "Hello spinach!"
        }
        """
        Then response should be JSON
        And response status should be "422"

    Scenario: Fail to update recipe with invalid data
        When call "PUT" "/api/v1/recipes/" with resource id "1" with parameters:
        """
        {
            "box_type": "vegetarian",
            "title": "Lentil vegetable lasagne",
            "marketing_description": "Very satiating food",
            "calories_kcal": 400,
            "protein_grams": 100,
            "fat_grams": 100,
            "carbs_grams": 200,
            "recipe_diet_type_id": "vegetarian",
            "base": "lentil",
            "protein_source": "lentil",
            "preparation_time_minutes": 35,
            "shelf_life_days": "some string"
        }
        """
        Then response should be JSON
        And response status should be "422"

    Scenario: Fail to update recipe with method not allowed
        When call "POST" "/api/v1/recipes/" with resource id "100" with parameters:
        """
        {
            "title": "Hello broccoli!"
        }
        """
        Then response should be JSON
        And response status should be "405"

    Scenario: Update recipe that exists
        When call "PUT" "/api/v1/recipes/" with resource id "1" with parameters:
        """
        {
            "box_type": "vegetarian",
            "title": "Hello broccoli!",
            "marketing_description": "Very healthy food",
            "recipe_cuisine": "asian",
            "calories_kcal": 400,
            "protein_grams": 100,
            "fat_grams": 100,
            "carbs_grams": 200,
            "recipe_diet_type_id": "vegetarian",
            "base": "broccoli",
            "protein_source": "broccoli",
            "preparation_time_minutes": 35,
            "shelf_life_days": 4
        }
        """
        Then response should be JSON
        And response status should be "200"
        And response has a "title" property
        And "title" property equals "Hello broccoli!"

    Scenario: Fail to delete recipe that doesn't exists
        When call "DELETE" "/api/v1/recipes/" with resource id "100"
        Then response should be JSON
        And response status should be "404"

    Scenario: Fail to delete recipe with method not allowed
        When call "POST" "/api/v1/recipes/" with resource id "1"
        Then response should be JSON
        And response status should be "405"

    Scenario: Delete recipe that exists
        When call "DELETE" "/api/v1/recipes/" with resource id "1"
        Then response should be JSON
        And response status should be "200"
        And json response should be:
        """
        {
        "deleted": true
        }
        """
        And call "GET" "/api/v1/recipes/" with resource id "1"
        And response should be JSON
        And response status should be "404"

    Scenario: Fail to vote for recipe with method not allowed
        When call "POST" "/api/v1/recipes/1/vote/1"
        Then response should be JSON
        And response status should be "405"

    Scenario: Fail to vote for recipe that does not exist
        When call "PATCH" "/api/v1/recipes/100/vote/1"
        Then response should be JSON
        And response status should be "404"

    Scenario: Fail to vote for recipe with with invalid data
        When call "PATCH" "/api/v1/recipes/1/vote/6"
        Then response should be JSON
        And response status should be "422"

    Scenario: Vote for recipe
        When call "PATCH" "/api/v1/recipes/1/vote/5"
        Then response should be JSON
        And response status should be "200"
        And json response should be:
        """
        {
          "rating_value": 5,
          "votes_sum": 5,
          "votes_count": 1
        }
        """
        And call "GET" "/api/v1/recipes/" with resource id "1"
        And response should be JSON
        And response status should be "200"
        And response has a "rating_value" property
        And "rating_value" property equals "5"
        And response has a "votes_sum" property
        And "votes_sum" property equals "5"
        And response has a "votes_count" property
        And "votes_count" property equals "1"

    Scenario: Vote for recipe
        When call "PATCH" "/api/v1/recipes/1/vote/2"
        And call "PATCH" "/api/v1/recipes/1/vote/5"
        And response has a "rating_value" property
        And "rating_value" property equals "3.5"
        And response has a "votes_sum" property
        And "votes_sum" property equals "7"
        And response has a "votes_count" property
        And "votes_count" property equals "2"
