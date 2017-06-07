<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Recipe;
use App\RecipeIngredient;
use App\RecipeDirection;
use File;

class RecipeController extends Controller
{
    public function __construct()
    {
    	$this->middleware('auth:api')->except('index', 'show');
    }

    public function index()
    {
    	$recipes = Recipe::orderBy('created_at', 'desc')->get(['name', 'image', 'id']);

    	return response()->json(['recipes' => $recipes]);
    }

    public function create()
    {
    	$form = Recipe::form();

    	return response()->json(['form' => $form]);
    }

    public function store(Request $req)
    {
    	$this->validate($req, [
    		'name' => 'required|max:255',
    		'description' => 'required|max:3000',
    		'image' => 'required|image',
    		'ingredients' => 'required|array|min:1',
    		'ingredients.*.name' => 'required|max:255',
    		'ingredients.*.qty' => 'required|max:255',
    		'directions' => 'required|array|min:1',
    		'directions.*.description' => 'required|max:3000'
    	]);

    	$ingredients = [];

    	foreach ($req->ingredients as $ingredient) {
            $ingredients[] = new RecipeIngredient($ingredient);
        }

        $directions = [];

        foreach ($req->directions as $direction) {
            $directions[] = new RecipeDirection($direction);
        }

        if(!$req->hasFile('image') && !$req->file('image')->isValid()){
            return abort(404, 'Image not uploaded');
        }

        $filename = $this->getFileName($req->image);

        $req->image->move(base_path('public/images'), $filename);

        $recipe = new Recipe($req->all());
        $recipe->image = $filename;
        $req->user()->recipes()->save($recipe);

        $recipe->directions()->saveMany($directions);

        $recipe->ingredients()->saveMany($ingredients);

        return response()
            ->json([
                'saved' => true, 
                'id' => $recipe->id, 
                'message' => 'You have successfully created recipe']);
    }

    protected function getFileName($file)
    {
        return str_random(32).'.'.$file->extension();
    }

    public function show($id)
    {
        $recipe = Recipe::with(['user', 'ingredients', 'directions'])->findOrFail($id);

        return response()
            ->json([
                'recipe' => $recipe
            ]);
    }

    public function edit($id, Request $req)
    {
        $form = $req->user()->recipes()->with(['ingredients' => function($q) {
            $q->get(['id', 'name', 'qty']);
        }, 'directions' => function($q) {
            $q->get(['id', 'description']);
        }])->findOrFail($id, ['id', 'name', 'description', 'image']);

        return response()
            ->json(['form' => $form]);
    }

    public function update($id, Request $req)
    {
        $this->validate($req, [
                'name' => 'required|max:255',
                'description' => 'required|max:3000',
                'image' => 'image',
                'ingredients' => 'required|array|min:1',
                'ingredients.*.id' => 'integer|exists:recipe_ingredients',
                'ingredients.*.name' => 'required|max:255',
                'ingredients.*.qty' => 'required|max:255',
                'directions' => 'required|array|min:1',
                'directions.*.id' => 'integer|exists:recipe_directions',
                'directions.*.description' => 'required|max:3000'
        ]);

        $recipe = $req->user()->recipes()->findOrFail($id);

        $ingredients = [];
        $ingredientsUpdated = [];

        foreach ($req->ingredients as $ingredient) {
            if(isset($ingredient['id'])){
                RecipeIngredient::where('recipe_id', $recipe->id)
                       ->where('id', $ingredient['id'])
                       ->update($ingredient);

                $ingredientsUpdated[] = $ingredient['id'];
            }else{
                $ingredients[] = new RecipeIngredient($ingredient);
            }
        }

        $directions = [];
        $directionsUpdated = [];

        foreach ($req->directions as $direction) {
            if(isset($direction['id'])){
                RecipeDirection::where('recipe_id', $recipe->id)
                       ->where('id', $direction['id'])
                       ->update($direction);

                $directionsUpdated[] = $direction['id'];
            }else{
                $directions[] = new RecipeDirection($direction);
            }
        }

        $recipe->name = $req->name;
        $recipe->description = $req->description;

        if($req->hasFile('image') && $req->file('image')->isValid()){
            $filename = $this->getFileName($req->image);
            $req->image->move(base_path('public/images'), $filename);

            File::delete(base_path('public/images/'. $recipe->image));

            $recipe->image = $filename;
        }

        $recipe->save();

        RecipeIngredient::whereNotIn('id', $ingredientsUpdated)
            ->where('recipe_id', $recipe->id)
            ->delete();

        RecipeDirection::whereNotIn('id', $directionsUpdated)
            ->where('recipe_id', $recipe->id)
            ->delete();

        if(count($ingredients)){
            $recipe->ingredients()->saveMany($ingredients);
        }

        if(count($directions)){
            $recipe->directions()->saveMany($directions);
        }

        return response()->json([
            'saved' => true,
            'id' => $recipe->id,
            'message' => 'You have successfully updated recipe'
        ]);
    }

    public function destroy($id, Request $req)
    {
        $recipe = $req->user()->recipes()->findOrFail($id);

        RecipeIngredient::where('recipe_id', $recipe->id)->delete();
        RecipeDirection::where('recipe_id', $recipe->id)->delete();

        File::delete(base_path('public/images/'.$recipe->image));

        $recipe->delete();

        return response()->json([
            'deleted' => true,
        ]);
    }
}
