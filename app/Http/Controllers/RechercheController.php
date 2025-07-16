<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Historique;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Vinkla\Hashids\Facades\Hashids;

class RechercheController extends Controller
{
public function recherche(Request $request)
{
    $request->validate([
        'keyword' => 'required|string'
    ], [
        'keyword.required' => 'Le champ keyword est requis.'
    ]);

    try {
        $keyword = $request->query('keyword');

        // Enregistrement de l'historique
        Historique::create(['lib_recherche' => $keyword]);

        // Recherche
        $articles = Article::with(['categories', 'variations'])
            ->where('nom_article', 'like', "%$keyword%")
            ->orWhereHas('categories', function ($query) use ($keyword) {
                $query->where('nom_categorie', 'like', "%$keyword%");
            })
            ->get();

        // Formatage de la réponse
        $data = $articles->map(function ($article) {
            // Traitement image principale
            $imagePrincipale = 'image_par_defaut.jpg';
            $imagesArray = [];

            if (!empty($article->images)) {
                $decoded = json_decode($article->images, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded) && count($decoded) > 0) {
                    $imagePrincipale = $decoded[0];
                }
            }

            return [
                'nom_article' => $article->nom_article,
                'prix' => $article->prix,
                'old_price' => $article->old_price,
                'image' => $imagePrincipale, // une seule image dans un tableau JSON
                'description' => $article->description,
                'created_at' => $article->created_at,
                'updated_at' => $article->updated_at,
                'hashid' => Hashids::encode($article->id),
                'categories' => $article->categories->map(function ($cat) {
                    return [
                        'nom_categorie' => $cat->nom_categorie,
                        'image_categorie' => $cat->image_categorie,
                        'created_at' => $cat->created_at,
                        'updated_at' => $cat->updated_at,
                        'hashid' => Hashids::encode($cat->id),
                    ];
                }),
                'variations' => $article->variations->map(function ($var) {
                    return [
                        'nom_variation' => $var->nom_variation ?? $var->no_variation,
                        'lib_variation' => $var->lib_variation,
                        'created_at' => $var->created_at,
                        'updated_at' => $var->updated_at,
                        'hashid' => Hashids::encode($var->id),
                    ];
                }),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
            'message' => $data->isNotEmpty() ? 'Articles récupérés avec succès.' : 'Aucun article ne correspond à votre recherche.'
        ]);

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

