<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Boutique;
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
        $keyword = trim($request->query('keyword'));

        // Enregistrer l'historique
        Historique::create(['lib_recherche' => $keyword]);

        // 🔍 Recherche insensible à la casse
        $articles = Article::with(['categories', 'variations'])
            ->whereRaw('LOWER(nom_article) LIKE ?', ['%' . strtolower($keyword) . '%'])
            ->orWhereHas('categories', function ($query) use ($keyword) {
                $query->whereRaw('LOWER(nom_categorie) LIKE ?', ['%' . strtolower($keyword) . '%']);
            })
            ->get();

        $boutiques = Boutique::whereRaw('LOWER(nom_btq) LIKE ?', ['%' . strtolower($keyword) . '%'])
            ->orWhereRaw('LOWER(email_btq) LIKE ?', ['%' . strtolower($keyword) . '%'])
            ->orWhereRaw('LOWER(tel_btq) LIKE ?', ['%' . strtolower($keyword) . '%'])
            ->get();

        // 🔧 Formatage des articles
        $articlesData = $articles->map(function ($article) {
            $imagePrincipale = 'image_par_defaut.jpg';

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
                'image' => $imagePrincipale,
                'description' => $article->description,
                'created_at' => $article->created_at,
                'hashid' => Hashids::encode($article->id),
                'categories' => $article->categories->map(function ($cat) {
                    return [
                        'nom_categorie' => $cat->nom_categorie,
                        'image_categorie' => $cat->image_categorie,
                        'hashid' => Hashids::encode($cat->id),
                    ];
                }),
                'variations' => $article->variations->map(function ($var) {
                    return [
                        'nom_variation' => $var->nom_variation ?? $var->no_variation,
                        'lib_variation' => $var->lib_variation,
                        'hashid' => Hashids::encode($var->id),
                    ];
                }),
            ];
        });

        // 🔧 Formatage des boutiques
        $boutiquesData = $boutiques->map(function ($boutique) {
            return [
                'nom_btq' => $boutique->nom_btq,
                'email_btq' => $boutique->email_btq,
                'tel_btq' => $boutique->tel_btq,
                'image_btq' => $boutique->image_btq ?? 'image_boutique_defaut.jpg',
                'created_at' => $boutique->created_at,
                'hashid' => Hashids::encode($boutique->id),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'articles' => $articlesData,
                'boutiques' => $boutiquesData,
            ],
            'message' => ($articlesData->isNotEmpty() || $boutiquesData->isNotEmpty())
                ? 'Résultats récupérés avec succès.'
                : 'Aucun article ni boutique ne correspond à votre recherche.'
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Une erreur est survenue lors de la recherche.',
            'erreur' => $e->getMessage()
        ], 500);
    }
}


public function suggestion(Request $request)
{
    $request->validate([
        'libelle' => 'required|string'
    ], [
        'libelle.required' => 'Le champ libelle est requis.'
    ]);

    try {
        $libelle = trim($request->query('libelle'));

        // 🔍 Recherche insensible à la casse
        $articles = Article::whereRaw('LOWER(nom_article) LIKE ?', ['%' . strtolower($libelle) . '%'])
            ->select('id', 'nom_article')
            ->get();

        $boutiques = Boutique::whereRaw('LOWER(nom_btq) LIKE ?', ['%' . strtolower($libelle) . '%'])
            ->select('id', 'nom_btq')
            ->get();

        $articlesData = $articles->map(function ($article) {
            return [
                'hashid' => Hashids::encode($article->id),
                'libelle' => $article->nom_article,
            ];
        });

        $boutiquesData = $boutiques->map(function ($boutique) {
            return [
                'hashid' => Hashids::encode($boutique->id),
                'libelle' => $boutique->nom_btq,
            ];
        });

        // Fusionner et retirer les doublons
        $suggestions = collect($articlesData)
            ->merge($boutiquesData)
            ->unique('libelle')
            ->values();

        return response()->json([
            'success' => true,
            'data' => $suggestions,
            'message' => $suggestions->isNotEmpty()
                ? 'Suggestions récupérées avec succès.'
                : 'Aucune suggestion trouvée.'
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Une erreur est survenue lors de la récupération des suggestions.',
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

