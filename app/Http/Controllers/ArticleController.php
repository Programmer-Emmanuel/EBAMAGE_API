<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Categorie;
use App\Models\Variation;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Vinkla\Hashids\Facades\Hashids;
use Illuminate\Support\Facades\Log;


class ArticleController extends Controller
{
public function ajout_article(Request $request)
{
    $request->validate([
        'nom_article' => 'required|string',
        'prix' => 'required|numeric|min:10',
        'old_price' => 'nullable|numeric|min:10',
        'images' => 'required|array',
        'images.*' => 'image|mimes:jpeg,png,jpg|max:2048',
        'description' => 'required|string|min:10',
        'stock' => 'required|min:1',
        'id_categories' => 'required|array',
        'id_categories.*' => 'string',
        'id_variations' => 'nullable|array',
        'id_variations.*.variation_id' => 'required|string',
        'id_variations.*.lib_variation' => 'required|array|min:1',
        'id_variations.*.lib_variation.*' => 'required|string|max:255',
    ]);

    if ($request->filled('old_price') && $request->old_price <= $request->prix) {
        return response()->json([
            'success' => false,
            'message' => 'Le prix promotionnel (old_price) doit être supérieur au prix normal.',
        ], 422);
    }

    if (!$request->hasFile('images') || !is_array($request->file('images'))) {
        return response()->json(['success' => false, 'message' => 'Images invalides ou absentes'], 422);
    }

    // 🔼 Upload des images
    $uploadedImages = [];
    foreach ($request->file('images') as $image) {
        $uploadedImages[] = $this->uploadImageToHosting($image);
    }

    try {
        DB::beginTransaction();

        // 🔹 Création de l'article
        $article = new Article();
        $article->nom_article = $request->nom_article;
        $article->prix = $request->prix;
        $article->old_price = $request->old_price;
        $article->images = json_encode($uploadedImages);
        $article->description = $request->description;
        $article->stock = $request->stock;
        $article->id_btq = auth('boutique')->id();
        $article->save();

        // 🔹 Gestion des catégories
        $id_categories = collect($request->id_categories)
            ->map(fn($hashid) => Hashids::decode($hashid)[0] ?? null)
            ->filter()
            ->values()
            ->all();

        if (!empty($id_categories)) {
            $article->categories()->sync($id_categories);
        }

        // 🔹 Gestion des variations
        $variationIdsToAttach = [];

if ($request->filled('id_variations')) {

    foreach ($request->id_variations as $item) {

        $idVariation = Hashids::decode($item['variation_id'])[0] ?? null;

        if (!$idVariation) {
            Log::warning('Variation invalide', $item);
            continue;
        }

        $variation = Variation::find($idVariation);

        if (!$variation) {
            Log::warning('Variation introuvable', [
                'id' => $idVariation
            ]);
            continue;
        }

        // Libellés autorisés
        $libellesAutorises = is_array($variation->lib_variation)
            ? $variation->lib_variation
            : json_decode($variation->lib_variation, true);

        // Libellés choisis par le marchand
        $libellesChoisis = array_values(
            array_intersect(
                $item['lib_variation'],
                $libellesAutorises
            )
        );

        if (empty($libellesChoisis)) {
            continue;
        }

        // Création d'une nouvelle variation boutique
        $newVariation = new Variation();
        $newVariation->nom_variation = $variation->nom_variation;
        $newVariation->lib_variation = $libellesChoisis;
        $newVariation->id_btq = auth('boutique')->id();
        $newVariation->save();

        $variationIdsToAttach[] = $newVariation->id;
    }

    $article->variations()->sync($variationIdsToAttach);
}
        

        DB::commit();

        // 🔹 Recharger l'article avec ses relations fraîchement
        $article->refresh();
        $article->load(['categories', 'variations']);

        // 🔹 Formater les variations pour la réponse
        $counts = [];
        $variationsFormatted = $article->variations->map(function ($v) use (&$counts) {
            $libVar = $v->lib_variation;
            if (is_string($libVar)) {
                $libVar = json_decode($libVar, true) ?: [];
            }

            $baseName = strtolower($v->nom_variation);
            $counts[$baseName] = ($counts[$baseName] ?? 0) + 1;
            $suffix = $counts[$baseName] > 1 ? ' ' . $counts[$baseName] : '';

            return [
                'hashid' => $v->hashid ?? null,
                'nom_variation' => $baseName . $suffix,
                'lib_variation' => $libVar,
            ];
        });

        // 🔹 Préparation de la réponse
        $articleData = $article->toArray();
        $articleData['images'] = json_decode($article->images, true);
        $articleData['variations'] = $variationsFormatted;

        return response()->json([
            'success' => true,
            'data' => $articleData,
            'message' => 'Article enregistré avec succès avec ses variations.'
        ]);

    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Erreur ajout_article', ['exception' => $e]);
        return response()->json([
            'success' => false,
            'message' => 'Erreur lors de l\'enregistrement de l\'article',
            'erreur' => $e->getMessage()
        ], 500);
    }
}





    private function uploadImageToHosting($image)
    {
        $apiKey = '9b1ab6564d99aab6418ad53d3451850b';

        if (!$image->isValid()) {
            throw new \Exception("Fichier image non valide.");
        }

        $imageContent = base64_encode(file_get_contents($image->getRealPath()));

        $response = Http::asForm()->post('https://api.imgbb.com/1/upload', [
            'key' => $apiKey,
            'image' => $imageContent,
        ]);

        if ($response->successful()) {
            return $response->json()['data']['url'];
        }

        throw new \Exception("Erreur lors de l'envoi de l'image : " . $response->body());
    }

    public function liste_article()
{
    try {
        $articles = Article::with('boutique', 'categories', 'variations')->get();

        if ($articles->isEmpty()) {
            return response()->json([
                'success' => true,
                'data' => [],
                'message' => 'Aucun article trouvé.'
            ]);
        }

        $formatted = $articles->map(function ($article) {
            // Compteur pour nom_variation en minuscules
            $counts = [];

            $variationsFormatted = $article->variations->map(function ($v) use (&$counts) {
                $libVar = $v->lib_variation;
                if (is_string($libVar)) {
                    $libVar = json_decode($libVar, true) ?: [];
                }

                $baseName = strtolower($v->nom_variation);
                $counts[$baseName] = ($counts[$baseName] ?? 0) + 1;
                $suffix = $counts[$baseName] > 1 ? ' ' . $counts[$baseName] : '';

                return [
                    'hashid' => $v->hashid ?? null,
                    'nom_variation' => $baseName . $suffix,
                    'lib_variation' => $libVar,
                ];
            });

            return [
                'nom_article' => $article->nom_article,
                'prix' => $article->prix,
                'old_price' => $article->old_price,
                'images' => json_decode($article->images, true),
                'description' => $article->description,
                'created_at' => $article->created_at,
                'updated_at' => $article->updated_at,
                'hashid' => $article->hashid,
                'nom_btq' => optional($article->boutique)->nom_btq,
                'categories' => $article->categories,
                'variations' => $variationsFormatted,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $formatted,
            'message' => 'Articles récupérés avec succès.'
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Une erreur est survenue',
            'erreur' => $e->getMessage()
        ], 500);
    }
}



    public function article($hashid)
{
    try {
        $id = Hashids::decode($hashid)[0] ?? null;

        if (!$id) {
            return response()->json(['message' => 'ID invalide'], 400);
        }

        $article = Article::with('boutique', 'categories', 'variations')->find($id);

        if (!$article) {
            return response()->json([
                'success' => false,
                'message' => 'Article introuvable.'
            ]);
        }

        // Formatage des articles similaires
        $formatArticle = function ($art) {
            return [
                'nom_article' => $art->nom_article,
                'prix' => $art->prix,
                'old_price' => $art->old_price,
                'image' => collect(json_decode($art->images, true))->first(), // ✅ une seule image
                'description' => $art->description,
                'stock' => $art->stock,
                'created_at' => $art->created_at,
                'updated_at' => $art->updated_at,
                'hashid' => $art->hashid,
            ];
        };

        $autre_articles = Article::where('id_btq', $article->id_btq)
            ->where('id', '!=', $article->id)
            ->get()
            ->map($formatArticle);

        $articles_meme_categorie = [];
        $categorie_id = optional($article->categories->first())->id;

        if ($categorie_id) {
            $articles_meme_categorie = Article::whereHas('categories', function ($q) use ($categorie_id) {
                $q->where('categories.id', $categorie_id);
            })
            ->where('id', '!=', $article->id)
            ->get()
            ->map($formatArticle);
        }

        // Numérotation des variations avec même nom_variation
        $counts = [];
        $variationsFormatted = $article->variations->map(function ($v) use (&$counts) {
            $libVar = $v->lib_variation;
            if (is_string($libVar)) {
                $libVar = json_decode($libVar, true) ?: [];
            }

            $baseName = strtolower($v->nom_variation);
            $counts[$baseName] = ($counts[$baseName] ?? 0) + 1;
            $suffix = $counts[$baseName] > 1 ? ' ' . $counts[$baseName] : '';

            return [
                'id' => $v->id,
                'hashid' => $v->hashid ?? null,
                'nom_variation' => $baseName . $suffix,
                'lib_variation' => $libVar,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'nom_article' => $article->nom_article,
                'prix' => $article->prix,
                'old_price' => $article->old_price,
                'images' => json_decode($article->images, true),
                'description' => $article->description,
                'stock' => $article->stock,
                'created_at' => $article->created_at,
                'updated_at' => $article->updated_at,
                'hashid' => $article->hashid,
                'nom_btq' => $article->boutique->nom_btq ?? null,
                'categories' => $article->categories,
                'variations' => $variationsFormatted,
            ],
            'communs' => $autre_articles,
            'similaires' => $articles_meme_categorie,
            'message' => 'Article trouvé.'
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Une erreur est survenue',
            'erreur' => $e->getMessage()
        ], 500);
    }
}


    public function articlesParCategorie($hashid)
    {
        try {
            $id = Hashids::decode($hashid)[0] ?? null;

            if (!$id) {
                return response()->json(['message' => 'ID invalide'], 400);
            }

            $categorie = Categorie::with('articles.categories')->find($id);

            if (!$categorie) {
                return response()->json([
                    'success' => false,
                    'message' => 'Catégorie non trouvée.'
                ]);
            }

            $articles = $categorie->articles->map(function ($article) use ($categorie) {
                return [
                    'id' => $article->hashid,
                    'nom_article' => $article->nom_article,
                    'prix' => $article->prix,
                    'old_price' => $article->old_price,
                    'description' => $article->description,
                    'images' => json_decode($article->images, true),
                    'categorie' => $categorie->nom_categorie,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $articles,
                'message' => 'Articles de la catégorie récupérés avec succès.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue',
                'erreur' => $e->getMessage()
            ], 500);
        }
    }

public function update_article(Request $request, $hashid)
{
    $request->validate([
        'nom_article' => 'required|string',
        'prix' => 'required|numeric|min:10',
        'old_price' => 'nullable|numeric|min:10',
        'description' => 'required|string|min:10',
        'stock' => 'required|min:1',
        'images' => 'nullable|array',
        'images.*' => 'image|mimes:jpeg,png,jpg|max:2048',
        'id_categories' => 'nullable|array',
        'id_categories.*' => 'string',
        'id_variations' => 'nullable|array',
        'id_variations.*.variation_id' => 'required|string',
        'id_variations.*.lib_variation' => 'required|array|min:1',
        'id_variations.*.lib_variation.*' => 'required|string|max:255',
    ]);

    if ($request->filled('old_price') && $request->old_price <= $request->prix) {
        return response()->json([
            'success' => false,
            'message' => 'Le prix promotionnel (old_price) doit être supérieur au prix normal.',
        ], 422);
    }

    try {

        DB::beginTransaction();

        $id = Hashids::decode($hashid)[0] ?? null;

        if (!$id) {
            return response()->json([
                'success' => false,
                'message' => 'ID invalide'
            ],400);
        }

        $article = Article::find($id);

        if (!$article) {
            return response()->json([
                'success' => false,
                'message' => 'Article introuvable.'
            ]);
        }

        if ($article->id_btq != auth('boutique')->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'êtes pas autorisé à modifier cet article.'
            ]);
        }

        // -------------------
        // Mise à jour article
        // -------------------

        $article->nom_article = $request->nom_article;
        $article->prix = $request->prix;
        $article->old_price = $request->old_price;
        $article->description = $request->description;
        $article->stock = $request->stock;

        if ($request->hasFile('images')) {

            $uploadedImages = [];

            foreach ($request->file('images') as $image) {
                $uploadedImages[] = $this->uploadImageToHosting($image);
            }

            $article->images = json_encode($uploadedImages);
        }

        $article->save();

        // -------------------
        // Catégories
        // -------------------

        if ($request->has('id_categories')) {

            $id_categories = collect($request->id_categories)
                ->map(fn($hashid) => Hashids::decode($hashid)[0] ?? null)
                ->filter()
                ->values()
                ->all();

            $article->categories()->sync($id_categories);
        }

        // -------------------
        // Variations
        // -------------------

        $variationIdsToAttach = [];

        if ($request->filled('id_variations')) {

            foreach ($request->id_variations as $item) {

                $idVariation = Hashids::decode($item['variation_id'])[0] ?? null;

                if (!$idVariation) {
                    Log::warning('Variation invalide', $item);
                    continue;
                }

                $variation = Variation::find($idVariation);

                if (!$variation) {
                    Log::warning('Variation introuvable', [
                        'id' => $idVariation
                    ]);
                    continue;
                }

                $libellesAutorises = is_array($variation->lib_variation)
                    ? $variation->lib_variation
                    : json_decode($variation->lib_variation, true);

                $libellesAutorises = $libellesAutorises ?? [];

                $libellesChoisis = array_values(
                    array_intersect(
                        $item['lib_variation'],
                        $libellesAutorises
                    )
                );

                if (empty($libellesChoisis)) {
                    Log::warning('Aucun libellé valide', [
                        'choisis' => $item['lib_variation'],
                        'autorises' => $libellesAutorises
                    ]);
                    continue;
                }

                $newVariation = new Variation();
                $newVariation->nom_variation = $variation->nom_variation;
                $newVariation->lib_variation = $libellesChoisis;
                $newVariation->id_btq = auth('boutique')->id();
                $newVariation->save();

                $variationIdsToAttach[] = $newVariation->id;
            }

            $article->variations()->sync($variationIdsToAttach);
        }

        DB::commit();

        $article->refresh();
        $article->load(['categories','variations']);

        $counts = [];

        $variationsFormatted = $article->variations->map(function ($v) use (&$counts) {

            $libVar = is_array($v->lib_variation)
                ? $v->lib_variation
                : json_decode($v->lib_variation, true);

            $baseName = strtolower($v->nom_variation);

            $counts[$baseName] = ($counts[$baseName] ?? 0) + 1;

            $suffix = $counts[$baseName] > 1
                ? ' '.$counts[$baseName]
                : '';

            return [
                'hashid' => $v->hashid,
                'nom_variation' => $baseName.$suffix,
                'lib_variation' => $libVar,
            ];
        });

        $articleData = $article->toArray();
        $articleData['images'] = json_decode($article->images, true);
        $articleData['variations'] = $variationsFormatted;

        return response()->json([
            'success' => true,
            'data' => $articleData,
            'message' => 'Article mis à jour avec succès avec ses variations.'
        ]);

    } catch (\Exception $e) {

        DB::rollBack();

        Log::error('Erreur update_article', [
            'exception' => $e
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Erreur lors de la mise à jour de l\'article',
            'erreur' => $e->getMessage()
        ],500);
    }
}



    public function delete_article(Request $request, $hashid){
        try {
            $id = Hashids::decode($hashid)[0] ?? null;

            if (!$id) {
                return response()->json([
                    'success' => false,
                    'message' => 'ID d\'article invalide.'
                ], 400);
            }

            $article = Article::find($id);

            if (!$article) {
                return response()->json([
                    'success' => false,
                    'message' => 'Article introuvable.'
                ], 404);
            }

            // Facultatif : vérification que l'article appartient à la boutique connectée
            if ($article->id_btq !== auth('boutique')->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Non autorisé à supprimer cet article.'
                ], 403);
            }

            $article->delete();

            return response()->json([
                'success' => true,
                'message' => 'Article supprimé avec succès.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue.',
                'erreur' => $e->getMessage()
            ], 500);
        }
    }



    public function article_boutique(Request $request)
{
    try {
        $id = $request->user()->id;

        $articles = Article::where('id_btq', $id)
            ->with(['categories', 'variations'])
            ->get();

        if ($articles->isEmpty()) {
            return response()->json([
                'success' => true,
                'data' => [],
                'message' => 'Aucun article trouvé pour cette boutique.'
            ]);
        }

        // Transformer la colonne images en tableau
        $articles = $articles->map(function ($article) {
            $article->images = json_decode($article->images, true) ?? [];
            return $article;
        });

        return response()->json([
            'success' => true,
            'data' => $articles,
            'message' => 'Article de la boutique connectée récupérée avec succès.'
        ]);

    } catch (QueryException $e) {
        return response()->json([
            'success' => false,
            'message' => 'Erreur lors de la récupération des articles.',
            'error' => $e->getMessage()
        ], 500);
    }
}




    public function trier_par_prix_moinsCher_cher()
    {
        try {
            $articles = Article::orderBy('prix', 'asc')->get();

            if ($articles->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucun article trouvé à trier (du moins cher au plus cher).'
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => $articles->load('categories', 'variations'),
                'message' => 'Article trié du moins cher au plus cher.'
            ]);
        } catch (QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du tri des articles par prix croissant.',
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

    public function trier_par_prix_cher_moinsCher()
    {
        try {
            $articles = Article::orderBy('prix', 'desc')->get();

            if ($articles->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucun article trouvé à trier (du plus cher au moins cher).'
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => $articles->load('categories', 'variations'),
                'message' => 'Article trié du plus cher au moins cher.'
            ]);
        } catch (QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du tri des articles par prix décroissant.',
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


    public function articlesBoutique($hashid)
{
    try {
        $id_btq = Hashids::decode($hashid)[0] ?? null;

        if (!$id_btq) {
            return response()->json([
                'success' => false,
                'message' => 'Identifiant de boutique invalide.'
            ], 400);
        }

        $articles = Article::where('id_btq', $id_btq)
            ->with(['categories', 'variations'])
            ->orderBy('created_at', 'desc')
            ->get();

        if ($articles->isEmpty()) {
            return response()->json([
                'success' => true,
                'data' => [],
                'message' => 'Aucun article trouvé pour cette boutique.'
            ]);
        }

        $articles = $articles->map(function ($article) {
            // Décoder les images
            $images = json_decode($article->images, true) ?? [];

            // Prendre seulement la première image (ou null si vide)
            $image = count($images) > 0 ? $images[0] : null;

            // Gestion des variations
            $counts = [];
            $variationsFormatted = $article->variations->map(function ($v) use (&$counts) {
                $libVar = is_string($v->lib_variation)
                    ? json_decode($v->lib_variation, true) ?: []
                    : $v->lib_variation;

                $baseName = strtolower($v->nom_variation);
                $counts[$baseName] = ($counts[$baseName] ?? 0) + 1;
                $suffix = $counts[$baseName] > 1 ? ' ' . $counts[$baseName] : '';

                return [
                    'hashid' => $v->hashid ?? null,
                    'nom_variation' => $baseName . $suffix,
                    'lib_variation' => $libVar,
                ];
            });

            return [
                'hashid' => $article->hashid,
                'nom_article' => $article->nom_article,
                'prix' => $article->prix,
                'old_price' => $article->old_price,
                'description' => $article->description,
                'image' => $image, // ✅ Une seule image ici
                'categories' => $article->categories,
                'variations' => $variationsFormatted,
                'created_at' => $article->created_at,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $articles,
            'message' => 'Articles de la boutique récupérés avec succès.'
        ]);
    } catch (\Exception $e) {
        Log::error('Erreur articlesBoutiqueHashid', ['exception' => $e]);
        return response()->json([
            'success' => false,
            'message' => 'Erreur lors de la récupération des articles.',
            'erreur' => $e->getMessage()
        ], 500);
    }
}






}