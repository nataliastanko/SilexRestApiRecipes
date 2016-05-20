<?php

namespace Application\Api\Recipe\Model;

/**
 * Recipe data.
 */
class Recipe
{
    /**
     * Static recipe data.
     *
     * @return array fields
     */
    public static function getFields()
    {
        return [
            'id',
            'created_at',
            'updated_at',
            'box_type',
            'title',
            'slug',
            'short_title',
            'marketing_description',
            'calories_kcal',
            'protein_grams',
            'fat_grams',
            'carbs_grams',
            'bulletpoint1',
            'bulletpoint2',
            'bulletpoint3',
            'recipe_diet_type_id',
            'season',
            'base',
            'protein_source',
            'preparation_time_minutes',
            'shelf_life_days',
            'equipment_needed',
            'origin_country',
            'recipe_cuisine',
            'in_your_box',
            'recipe_reference',
            'votes_count',
            'votes_sum',
            'rating_value',
        ];
    }
}
