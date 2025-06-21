<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Historique;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

class RechercheController extends Controller
{
    public function recherche(Request $request){
        $request->validate([
            'lib_recherche' => 'required'
        ], [
            'lib_recherche.required' => 'Le champ lib_recherche est requis.' 
        ]);

        $lib = $request->lib_recherche;

        //Ajout de la recherche dans l’historique
        $historique = new Historique();
        $historique->lib_recherche = $request->lib_recherche;
        $historique->save();

        $articles = Article::with(['categories', 'variations'])
            ->where('nom_article', 'like', "%$lib%")
            ->orWhereHas('categories', function ($query) use ($lib) {
                $query->where('nom_categorie', 'like', "%$lib%");
            })
            ->get();

        if($articles->isNotEmpty()){

            return response()->json([
                'success' => true,
                'data' => $articles,
                'message' => 'Articles récupérés avec succès.'
            ]);
        }
        else{
            return response()->json([
                'success' => false,
                'message' => 'Aucun article ne correspond à votre recherche'
            ]);
        }

    }


    public function historique(){
        try{
            $historique = Historique::all();
            return response()->json([
                'success' => true,
                'data' => $historique,
                'message' => 'Affichage de l’historique réussie'
            ]);
        }
        catch(QueryException $e){
            return response()->json([
                'success' => false,
                'message' => 'Echec lors de la récupération de l’historique.',
                'erreur' => $e->getMessage()
            ]);
        }
    }
}

