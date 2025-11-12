<?php

namespace App\Http\Controllers;

use App\Models\Prix;
use App\Models\Seuil;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Vinkla\Hashids\Facades\Hashids;

class PrixController extends Controller
{
    public function update_prix(Request $request){
        $validator = Validator::make($request->all(), [
            'prix' => 'required'
        ]);
        if($validator->fails()){
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }
        try{
            $prix = Prix::where('id', 1)->first();
            $prix->prix = $request->prix;
            $prix->save();

            return response()->json([
                'success' => true,
                'data' => $prix->prix,
                'message' => 'Prix de la livraison mis à jour avec succès'
            ],200);
        }
        catch(QueryException $e){
            return response()->json([
                'success' => false,
                'message' => 'Erreur survenue lors de la mise à jour du prix de la livraison',
                'erreur' => $e->getMessage()
            ],500);
        }
    }
    public function afficher_prix(){
        try{
           $prix = Prix::where('id', 1)->first();
           if(!$prix){
            return response()->json([
                'success' => false,
                'message' => 'Il n’y a aucun prix qui a été fixé.'
            ],404);
           }
           return response()->json([
            'success' => true,
            'data' => $prix->prix,
            'message' => 'Prix de livraison affiché avec succès'
           ],200);
        }
        catch(QueryException $e){
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l’affichage du prix de la livraison',
                'erreur' => $e->getMessage()
            ],500);
        }
    }

     public function update_seuil(Request $request){
        $validator = Validator::make($request->all(), [
            'seuil' => 'required'
        ]);
        if($validator->fails()){
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }
        try{
            $seuil = Seuil::where('id', 1)->first();
            $seuil->seuil = $request->seuil;
            $seuil->save();

            return response()->json([
                'success' => true,
                'data' => $seuil->seuil,
                'message' => 'Seuil de la livraison mis à jour avec succès'
            ],200);
        }
        catch(QueryException $e){
            return response()->json([
                'success' => false,
                'message' => 'Erreur survenue lors de la mise à jour du seuil de la livraison',
                'erreur' => $e->getMessage()
            ],500);
        }
    }
    public function afficher_seuil(){
        try{
           $seuil = Seuil::where('id', 1)->first();
           if(!$seuil){
            return response()->json([
                'success' => false,
                'message' => 'Il n’y a aucun seuil qui a été fixé.'
            ],404);
           }
           return response()->json([
            'success' => true,
            'data' => $seuil->seuil,
            'message' => 'Seuil de livraison affiché avec succès'
           ],200);
        }
        catch(QueryException $e){
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l’affichage du seuil de la livraison',
                'erreur' => $e->getMessage()
            ],500);
        }
    }


}
