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
            'keyword' => 'required|string'
        ], [
            'keyword.required' => 'Le champ keyword est requis.'
        ]);

        try {
            $keyword = $request->query('keyword'); // récupère depuis la query string

            // Enregistrement dans l'historique (optionnel)
            $historique = new Historique();
            $historique->lib_recherche = $keyword;
            $historique->save();

            // Recherche articles (nom_article ou catégorie)
            $articles = Article::with(['categories', 'variations'])
                ->where('nom_article', 'like', "%$keyword%")
                ->orWhereHas('categories', function ($query) use ($keyword) {
                    $query->where('nom_categorie', 'like', "%$keyword%");
                })
                ->get();

            if ($articles->isNotEmpty()) {
                return response()->json([
                    'success' => true,
                    'data' => $articles,
                    'message' => 'Articles récupérés avec succès.'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucun article ne correspond à votre recherche.'
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue.',
                'erreur' => $e->getMessage()
            ], 500);
        }
    }



    public function historique()
{
    try {
        $historique = Historique::all();

        return response()->json([
            'success' => true,
            'data' => $historique,
            'message' => 'Affichage de l’historique réussie.'
        ]);
    } catch (QueryException $e) {
        return response()->json([
            'success' => false,
            'message' => 'Échec lors de la récupération de l’historique.',
            'erreur' => $e->getMessage()
        ], 500);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Une erreur inattendue est survenue.',
            'erreur' => $e->getMessage()
        ], 500);
    }
}
}

