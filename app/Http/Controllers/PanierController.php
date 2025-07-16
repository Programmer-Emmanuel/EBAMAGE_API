<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Panier;
use Illuminate\Http\Request;
use Vinkla\Hashids\Facades\Hashids;

class PanierController extends Controller
{
    public function ajout_panier(Request $request)
    {
        try {
            $request->validate([
                'id_article' => 'required',
                'variations' => 'required|array|min:1',
                'variations.*.nom_variation' => 'required|string',
                'variations.*.lib_variation' => 'required|string',
            ]);

            $user = $request->user();
            $idDecoded = Hashids::decode($request->id_article)[0] ?? null;

            if (!$idDecoded) {
                return response()->json([
                    'success' => false,
                    'cart' => null,
                    'message' => "ID d'article invalide."
                ], 400);
            }

            $article = Article::findOrFail($idDecoded);

            // Normaliser les variations envoyées
            $submittedVariations = collect($request->variations)
                ->map(fn($v) => [
                    'nom_variation' => strtolower(trim($v['nom_variation'])),
                    'lib_variation' => strtolower(trim($v['lib_variation'])),
                ])
                ->sortBy('nom_variation')
                ->values()
                ->toArray();

            // Récupérer les variations possibles de l'article
            $articleVariations = collect($article->variations)->flatMap(function ($v) {
                $nomVar = strtolower(trim($v['nom_variation'] ?? $v['no_variation'] ?? ''));
                $libVars = is_array($v['lib_variation']) ? $v['lib_variation'] : [$v['lib_variation']];
                return collect($libVars)->map(fn($lib) => [
                    'nom_variation' => $nomVar,
                    'lib_variation' => strtolower(trim($lib)),
                ]);
            });

            // Vérifier que chaque variation envoyée est valide
            foreach ($submittedVariations as $var) {
                if (!$articleVariations->contains($var)) {
                    return response()->json([
                        'success' => false,
                        'cart' => null,
                        'message' => "La variation '{$var['nom_variation']}: {$var['lib_variation']}' n'appartient pas à cet article."
                    ], 422);
                }
            }

            // Chercher dans le panier si l’article avec les mêmes variations existe
            $panier = Panier::where('id_clt', $user->id)
                ->where('id_article', $article->id)
                ->get()
                ->first(function ($item) use ($submittedVariations) {
                    $existing = collect($item->variations)
                        ->map(fn($v) => [
                            'nom_variation' => strtolower(trim($v['nom_variation'])),
                            'lib_variation' => strtolower(trim($v['lib_variation'])),
                        ])
                        ->sortBy('nom_variation')
                        ->values()
                        ->toArray();

                    return $existing === $submittedVariations;
                });

            if ($panier) {
                if ($panier->quantite >= 10) {
                    return response()->json([
                        'success' => false,
                        'cart' => null,
                        'message' => 'Quantité maximale atteinte.'
                    ], 400);
                }

                $panier->quantite += 1;
            } else {
                $panier = new Panier([
                    'id_clt' => $user->id,
                    'id_article' => $article->id,
                    'variations' => $submittedVariations,
                    'quantite' => 1,
                ]);
            }

            $panier->prix_total = $article->prix * $panier->quantite;
            $panier->save();

            // Récupérer tout le panier de l'utilisateur avec articles
            $paniers = Panier::where('id_clt', $user->id)->with('article')->get();

            $items = $paniers->map(function ($item) {
            $article = $item->article;

            $image = 'image_par_defaut.jpg';

            // Décoder manuellement le JSON des images
            $imagesArray = [];
            if (!empty($article->images)) {
                $decoded = json_decode($article->images, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $imagesArray = $decoded;
                }
            }

            if (count($imagesArray) > 0) {
                $image = $imagesArray[0];  // juste le nom/fichier de l'image
            } elseif (!empty($article->image) && is_string($article->image)) {
                $image = $article->image;
            }

            $prixUnitaire = $article->prix ?? 0;

            return [
                'nom_article' => $article->nom_article ?? 'Nom indisponible',
                'quantite' => $item->quantite,
                'prix_unitaire' => $prixUnitaire,
                'image' => $image,
                'variations' => $item->variations ?? [],
                'prix_avec_quantite' => $item->quantite * $prixUnitaire,
            ];
        });


            $prix_total_panier = $items->sum('prix_avec_quantite');

            return response()->json([
                'success' => true,
                'cart' => $items,
                'id_panier' => $panier->hashid,
                'prix_total' => $prix_total_panier,
                'message' => 'Article ajouté au panier avec succès.'
            ]);


        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'cart' => null,
                'message' => 'Erreur de validation.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'cart' => null,
                'message' => 'Une erreur est survenue : ' . $e->getMessage(),
            ], 500);
        }
    }

    public function get_panier(Request $request){
        try {
            $user = $request->user();

            // Récupérer tout le panier de l'utilisateur avec articles
            $paniers = Panier::where('id_clt', $user->id)->with('article')->get();

            if ($paniers->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'cart' => [],
                    'id_panier' => null,
                    'prix_total' => 0,
                    'message' => 'Le panier est vide.'
                ]);
            }

            $items = $paniers->map(function ($item) {
                $article = $item->article;

                $image = 'image_par_defaut.jpg';

                // Décoder manuellement le JSON des images
                $imagesArray = [];
                if (!empty($article->images)) {
                    $decoded = json_decode($article->images, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                        $imagesArray = $decoded;
                    }
                }

                if (count($imagesArray) > 0) {
                    $image = $imagesArray[0]; // Premier nom/fichier image
                } elseif (!empty($article->image) && is_string($article->image)) {
                    $image = $article->image;
                }

                $prixUnitaire = $article->prix ?? 0;

                return [
                    'nom_article' => $article->nom_article ?? 'Nom indisponible',
                    'quantite' => $item->quantite,
                    'prix_unitaire' => $prixUnitaire,
                    'image' => $image,
                    'variations' => $item->variations ?? [],
                    'prix_avec_quantite' => $item->quantite * $prixUnitaire,
                ];
            });

            $prix_total_panier = $items->sum('prix_avec_quantite');

            // Récupérer le hashid du dernier item du panier (ou null)
            $lastPanier = $paniers->last();

            return response()->json([
                'success' => true,
                'cart' => $items,
                'id_panier' => $lastPanier ? $lastPanier->hashid : null,
                'prix_total' => $prix_total_panier,
                'message' => 'Panier récupéré avec succès.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'cart' => null,
                'message' => 'Une erreur est survenue : ' . $e->getMessage(),
            ], 500);
        }
    }

    private function getPanierResponse($clientId, $message){
        $paniers = Panier::with('article')->where('id_clt', $clientId)->get();

        $items = $paniers->map(function ($item) {
            $article = $item->article;

            $image = 'image_par_defaut.jpg';
            $imagesArray = [];

            // Traitement des images JSON ou simple string
            if (!empty($article->images)) {
                $decoded = json_decode($article->images, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $imagesArray = $decoded;
                }
            }

            if (count($imagesArray) > 0) {
                $image = $imagesArray[0]; // Première image
            } elseif (!empty($article->image)) {
                $image = $article->image;
            }

            $prixUnitaire = $article->prix ?? 0;

            return [
                'nom_article' => $article->nom_article ?? 'Nom indisponible',
                'quantite' => $item->quantite,
                'prix_unitaire' => $prixUnitaire,
                'image' => $image,
                'variations' => $item->variations ?? [],
                'prix_avec_quantite' => $prixUnitaire * $item->quantite,
            ];
        });

        $prix_total = $items->sum('prix_avec_quantite');

        return response()->json([
            'success' => true,
            'cart' => $items,
            'prix_total' => $prix_total,
            'message' => $message
        ]);
    }


   public function augmenterQuantite(Request $request){
        try {
            $request->validate([
                'id_article' => 'required|string'
            ]);

            $user = $request->user();
            $id = Hashids::decode($request->id_article)[0] ?? null;

            if (!$id) {
                return response()->json(['success' => false, 'message' => 'ID article invalide.'], 400);
            }

            // Chercher la première occurrence de cet article dans le panier du user
            $panier = Panier::where('id_clt', $user->id)
                ->where('id_article', $id)
                ->with('article')
                ->first();

            if (!$panier) {
                return response()->json(['success' => false, 'message' => 'Article panier non trouvé.'], 404);
            }

            if ($panier->quantite >= 10) {
                return response()->json(['success' => false, 'message' => 'Quantité maximale atteinte.'], 400);
            }

            $prixUnitaire = $panier->article->prix ?? 0;
            $panier->quantite += 1;
            $panier->prix_total = $prixUnitaire * $panier->quantite;
            $panier->save();

            return $this->getPanierResponse($user->id, 'Quantité augmentée.');

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur : ' . $e->getMessage()
            ], 500);
        }
    }


    public function diminuerQuantite(Request $request){
        try {
            $request->validate([
                'id_article' => 'required|string'
            ]);

            $user = $request->user();
            $id = Hashids::decode($request->id_article)[0] ?? null;

            if (!$id) {
                return response()->json(['success' => false, 'message' => 'ID article invalide.'], 400);
            }

            $panier = Panier::where('id_clt', $user->id)
                ->where('id_article', $id)
                ->with('article')
                ->first();

            if (!$panier) {
                return response()->json(['success' => false, 'message' => 'Article panier non trouvé.'], 404);
            }

            if ($panier->quantite <= 1) {
                $panier->delete();
                return $this->getPanierResponse($user->id, 'Article supprimé du panier.');
            }

            $prixUnitaire = $panier->article->prix ?? 0;
            $panier->quantite -= 1;
            $panier->prix_total = $prixUnitaire * $panier->quantite;
            $panier->save();

            return $this->getPanierResponse($user->id, 'Quantité diminuée.');

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur : ' . $e->getMessage()
            ], 500);
        }
    }

    public function supprimerArticle(Request $request, $hashid){
    try {
        $user = $request->user();
        $decodedId = Hashids::decode($hashid)[0] ?? null;

        if (!$decodedId) {
            return response()->json([
                'success' => false,
                'message' => 'ID invalide.',
            ], 400);
        }

        $panier = Panier::where('id', $decodedId)
            ->where('id_clt', $user->id)
            ->first();

        if (!$panier) {
            return response()->json([
                'success' => false,
                'message' => 'Article non trouvé dans le panier.',
            ], 404);
        }

        $panier->delete();

        return response()->json([
            'success' => true,
            'message' => 'Article supprimé du panier avec succès.',
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Une erreur est survenue : ' . $e->getMessage(),
        ], 500);
    }
}



}
