<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Categorie;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Vinkla\Hashids\Facades\Hashids;

class ArticleController extends Controller
{
    public function ajout_article(Request $request){
        $request->validate([
            'nom_article' => 'required',
            'prix' => 'required|numeric|min:10',
            'old_price' => 'nullable|numeric|min:10',
            'images' => 'required|array',
            'images.*' => 'image|mimes:jpeg,png,jpg|max:2048',
            'description' => 'required|min:10',
            'id_categories' => 'required|array',
            'id_categories.*' => 'string',
            'id_variations' => 'nullable|array',
            'id_variations.*' => 'string',
        ], [
            'nom_article.required' => 'Le nom de l\'article est obligatoire.',
            'prix.required' => 'Le prix est obligatoire.',
            'prix.numeric' => 'Le prix doit être un nombre.',
            'prix.min' => 'Le prix minimum autorisé est de 10.',
            'old_price.numeric' => 'L\'ancien prix doit être un nombre.',
            'old_price.min' => 'L\'ancien prix minimum autorisé est de 10.',
            'images.required' => 'Au moins une image est obligatoire.',
            'images.array' => 'Les images doivent être envoyées sous forme de tableau.',
            'images.*.image' => 'Chaque fichier doit être une image.',
            'images.*.mimes' => 'Les images doivent être au format jpeg, png ou jpg.',
            'images.*.max' => 'La taille maximale de chaque image est 2 Mo.',
            'description.required' => 'La description est obligatoire.',
            'description.min' => 'La description doit contenir au moins 10 caractères.',
            'id_categories.required' => 'Les catégories sont obligatoires.',
            'id_categories.array' => 'Les catégories doivent être envoyées sous forme de tableau.',
            'id_categories.*.string' => 'Les identifiants des catégories doivent être valides.',
            'id_variations.array' => 'Les variations doivent être envoyées sous forme de tableau.',
            'id_variations.*.string' => 'Les identifiants des variations doivent être valides.',
        ]);

        $uploadedImages = [];
        foreach ($request->file('images') as $image) {
            $uploadedImages[] = $this->uploadImageToHosting($image);
        }

        try {
            $article = new Article();
            $article->nom_article = $request->nom_article;
            $article->prix = $request->prix;
            $article->old_price = $request->old_price;
            $article->images = $uploadedImages;
            $article->description = $request->description;
            $article->id_btq = auth('boutique')->id();
            $article->save();

            $id_categories = collect($request->id_categories)->map(function ($hashid) {
                return Hashids::decode($hashid)[0] ?? null;
            })->filter();

            if ($id_categories->isNotEmpty()) {
                $article->categories()->attach($id_categories);
            }

            if ($request->has('id_variations')) {
                $id_variations = collect($request->id_variations)->map(function ($hashid) {
                    return Hashids::decode($hashid)[0] ?? null;
                })->filter();

                if ($id_variations->isNotEmpty()) {
                    $article->variations()->attach($id_variations);
                }
            }

            return response()->json([
                'success' => true,
                'data' => $article->load('categories', 'variations'),
                'message' => 'Article enregistré avec succès.'
            ]);
        } 
        catch (QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'enregistrement de l\'article',
                'erreur' => $e->getMessage()
            ], 500);
        }
    }

    private function uploadImageToHosting($image){
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
                    'success' => false,
                    'message' => 'Aucun article trouvé.'
                ]);
            }

            $formatted = $articles->map(function ($article) {
                return [
                    'nom_article' => $article->nom_article,
                    'prix' => $article->prix,
                    'old_price' => $article->old_price,
                    'images' => $article->images, // Modifié pour renvoyer un tableau d'images
                    'description' => $article->description,
                    'created_at' => $article->created_at,
                    'updated_at' => $article->updated_at,
                    'hashid' => $article->hashid,
                    'nom_btq' => optional($article->boutique)->nom_btq,
                    'categories' => $article->categories,
                    'variations' => $article->variations,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $formatted,
                'message' => 'Articles récupérés avec succès.'
            ]);
        } catch (QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des articles.',
                'erreur' => $e->getMessage(),
            ], 500);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Une erreur inattendue est survenue.',
                'erreur' => $e->getMessage(),
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

            $autre_articles = Article::where('id_btq', $article->id_btq)
                ->where('id', '!=', $article->id)
                ->get()
                ->map(function ($art) {
                    return [
                        'nom_article' => $art->nom_article,
                        'prix' => $art->prix,
                        'old_price' => $art->old_price,
                        'images' => $art->images, // Modifié pour renvoyer un tableau d'images
                        'description' => $art->description,
                        'created_at' => $art->created_at,
                        'updated_at' => $art->updated_at,
                        'hashid' => $art->hashid,
                    ];
                });

            $categorie_id = optional($article->categories->first())->id;

            $articles_meme_categorie = [];

            if ($categorie_id) {
                $articles_meme_categorie = Article::whereHas('categories', function ($q) use ($categorie_id) {
                        $q->where('categories.id', $categorie_id);
                    })
                    ->where('id', '!=', $article->id)
                    ->get()
                    ->map(function ($art) {
                        return [
                            'nom_article' => $art->nom_article,
                            'prix' => $art->prix,
                            'old_price' => $art->old_price,
                            'images' => $art->images, // Modifié pour renvoyer un tableau d'images
                            'description' => $art->description,
                            'created_at' => $art->created_at,
                            'updated_at' => $art->updated_at,
                            'hashid' => $art->hashid,
                        ];
                    });
            }

            $formattedArticle = [
                'nom_article' => $article->nom_article,
                'prix' => $article->prix,
                'old_price' => $article->old_price,
                'images' => $article->images, // Modifié pour renvoyer un tableau d'images
                'description' => $article->description,
                'created_at' => $article->created_at,
                'updated_at' => $article->updated_at,
                'hashid' => $article->hashid,
                'nom_btq' => $article->boutique->nom_btq ?? null,
                'categories' => $article->categories,
                'variations' => $article->variations,
            ];

            return response()->json([
                'success' => true,
                'data' => $formattedArticle,
                'communs' => $autre_articles,
                'similaires' => $articles_meme_categorie,
                'message' => 'Article trouvé.'
            ]);
        } catch (QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération de l\'article.',
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
                    'images' => $article->images, // Modifié pour renvoyer un tableau d'images
                    'categorie' => $categorie->nom_categorie,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $articles,
                'message' => 'Articles de la catégorie récupérés avec succès.'
            ]);
        } catch (QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des articles par catégorie.',
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

    public function delete_article($hashid)
    {
        try {
            $id = Hashids::decode($hashid)[0] ?? null;

            if (!$id) {
                return response()->json(['message' => 'ID invalide'], 400);
            }

            $article = Article::find($id);

            if (!$article) {
                return response()->json([
                    'success' => false,
                    'message' => 'Article introuvable.'
                ]);
            }

            if ($article->id_btq !== auth('boutique')->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vous n\'êtes pas autorisé à supprimer cet article.'
                ]);
            }

            $article->delete();

            return response()->json([
                'success' => true,
                'message' => 'Article supprimé avec succès.'
            ]);
        } catch (QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression de l\'article.',
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

    public function update_article(Request $request, $hashid)
    {
        $request->validate([
            'nom_article' => 'required',
            'prix' => 'required|numeric|min:10',
            'old_price' => 'nullable|numeric|min:10',
            'description' => 'required|min:10',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,png,jpg|max:2048',
            'id_categories' => 'nullable|array',
            'id_categories.*' => 'string',
            'id_variations' => 'nullable|array',
            'id_variations.*' => 'string',
        ]);

        try {
            $id = Hashids::decode($hashid)[0] ?? null;

            if (!$id) {
                return response()->json(['message' => 'ID invalide'], 400);
            }

            $article = Article::find($id);

            if (!$article) {
                return response()->json([
                    'success' => false,
                    'message' => 'Article introuvable.'
                ]);
            }

            if ($article->id_btq !== auth('boutique')->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vous n\'êtes pas autorisé à modifier cet article.'
                ]);
            }

            $article->nom_article = $request->nom_article;
            $article->prix = $request->prix;
            $article->old_price = $request->old_price;
            $article->description = $request->description;

            // Mise à jour des images (facultative)
            if ($request->hasFile('images')) {
                $uploadedImages = [];
                foreach ($request->file('images') as $image) {
                    $uploadedImages[] = $this->uploadImageToHosting($image);
                }
                $article->images = $uploadedImages;
            }

            $article->save();

            // MAJ des catégories (si fournies)
            if ($request->has('id_categories')) {
                $id_categories = collect($request->id_categories)->map(function ($hashid) {
                    return Hashids::decode($hashid)[0] ?? null;
                })->filter();
                $article->categories()->sync($id_categories);
            }

            // MAJ des variations (si fournies)
            if ($request->has('id_variations')) {
                $id_variations = collect($request->id_variations)->map(function ($hashid) {
                    return Hashids::decode($hashid)[0] ?? null;
                })->filter();
                $article->variations()->sync($id_variations);
            }

            return response()->json([
                'success' => true,
                'data' => $article->load('categories', 'variations'),
                'message' => 'Article mis à jour avec succès.'
            ]);

        } catch (QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour de l\'article.',
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
}