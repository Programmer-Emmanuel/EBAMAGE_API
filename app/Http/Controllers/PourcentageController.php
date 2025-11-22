<?php

namespace App\Http\Controllers;

use App\Models\Pourcentage;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PourcentageController extends Controller
{
    public function update_pourcentage(Request $request){
        $validator = Validator::make($request->all(),[
            'pourcentage' => 'required'
        ]);

        if($validator->fails()){
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ],422);
        }

        try{
            $pourcentage = Pourcentage::where('id', 1)->first();
            $pourcentage->pourcentage = $request->pourcentage;
            $pourcentage->save();

            return response()->json([
                'success' => true,
                'data' => $pourcentage->pourcentage . "%",
                'message' => 'Mis à jour effectué'
            ],200);
        }
        catch(QueryException $e){
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la modification du pourcentage',
                'erreur' => $e->getMessage()
            ],500);
        }
    }

    public function afficher_pourcentage(){
        try{
            $pourcentage = Pourcentage::where('id', 1)->first();
            return response()->json([
                'success' => true,
                'data' => $pourcentage->pourcentage . "%",
                'message' => 'Pourcentage affiché avec succès'
            ],200);
        }
        catch(QueryException $e){
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l’affichage',
                'erreur' => $e->getMessage()
            ],500);
        }
    }
}
