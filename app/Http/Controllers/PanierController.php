<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Panier;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Vinkla\Hashids\Facades\Hashids;

class PanierController extends Controller
{
public function ajout_panier(Request $request)
{
    try {
        $request->validate([
            'id_article' => 'required',
            'variations' => 'nullable|array',
            'variations.*.nom_variation' => 'required_with:variations|string',
            'variations.*.lib_variation' => 'required_with:variations|string',
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

        $article = Article::with('variations')->findOrFail($idDecoded);

        // Fonction pour normaliser les noms
        $normalize = function ($str) {
            return preg_replace('/[^a-z]/', '', strtolower($str));
        };

        // Soumissions du frontend normalisées
        $submittedVariationsRaw = $request->variations ?? [];
        $submittedVariations = [];
        $variationCount = [];

        foreach ($submittedVariationsRaw as $v) {
            $nom = $normalize($v['nom_variation']);
            $lib = strtolower(trim($v['lib_variation']));

            // Compter les répétitions d'un même nom normalisé
            $variationCount[$nom] = ($variationCount[$nom] ?? 0) + 1;
            $suffix = $variationCount[$nom] > 1 ? ' ' . $variationCount[$nom] : '';

            $submittedVariations[] = [
                'nom_variation' => $nom,
                'lib_variation' => $lib,
                'suffix' => $suffix, // utile uniquement pour la réponse finale
            ];
        }

        // Variations de l’article à comparer
        $articleVariations = collect($article->variations)->flatMap(function ($v) use ($normalize) {
            $nom = $normalize($v['nom_variation'] ?? '');
            $libList = is_array($v['lib_variation']) ? $v['lib_variation'] : [$v['lib_variation']];
            return collect($libList)->map(fn($lib) => [
                'nom_variation' => $nom,
                'lib_variation' => strtolower(trim($lib))
            ]);
        });

        foreach ($submittedVariations as $var) {
            if (!$articleVariations->contains([
                'nom_variation' => $var['nom_variation'],
                'lib_variation' => $var['lib_variation'],
            ])) {
                $nomErreur = $var['nom_variation'] . $var['suffix'];
                return response()->json([
                    'success' => false,
                    'cart' => null,
                    'message' => "La variation '{$nomErreur}: {$var['lib_variation']}' n'appartient pas à cet article."
                ], 422);
            }
        }

        // Comparaison avec paniers existants
        $submittedSimple = collect($submittedVariations)->map(fn($v) => [
            'nom_variation' => $v['nom_variation'],
            'lib_variation' => $v['lib_variation']
        ])->sortBy('nom_variation')->values()->toArray();

        $panierExistant = Panier::where('id_clt', $user->id)
            ->where('id_article', $article->id)
            ->get()
            ->first(function ($item) use ($submittedSimple, $normalize) {
                $existing = collect($item->variations ?? [])->map(fn($v) => [
                    'nom_variation' => $normalize($v['nom_variation']),
                    'lib_variation' => strtolower(trim($v['lib_variation']))
                ])->sortBy('nom_variation')->values()->toArray();

                return $existing === $submittedSimple;
            });

        if ($panierExistant) {
            if ($panierExistant->quantite >= 10) {
                return response()->json([
                    'success' => false,
                    'cart' => null,
                    'message' => 'Quantité maximale atteinte.'
                ], 400);
            }

            $panierExistant->quantite += 1;
            $panierExistant->prix_total = $article->prix * $panierExistant->quantite;
            $panierExistant->save();
            $panier = $panierExistant;
        } else {
            $panier = Panier::create([
                'id_clt' => $user->id,
                'id_article' => $article->id,
                'variations' => $submittedSimple,
                'quantite' => 1,
                'prix_total' => $article->prix,
            ]);
        }

        // Récupération du panier complet
        $paniers = Panier::where('id_clt', $user->id)->with('article')->get();

        $items = $paniers->map(function ($item) use ($normalize) {
            $article = $item->article;
            $image = 'image_par_defaut.jpg';

            if (!empty($article->images)) {
                $decoded = json_decode($article->images, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded) && count($decoded) > 0) {
                    $image = $decoded[0];
                }
            } elseif (!empty($article->image)) {
                $image = $article->image;
            }

            // Ajouter suffixes pour différencier les noms identiques
            $variations = $item->variations ?? [];
            $countNom = [];
            $variationsLabel = [];

            foreach ($variations as $v) {
                $nom = $normalize($v['nom_variation']);
                $countNom[$nom] = ($countNom[$nom] ?? 0) + 1;
                $suffix = $countNom[$nom] > 1 ? ' ' . $countNom[$nom] : '';
                $variationsLabel[] = [
                    'nom_variation' => $nom . $suffix,
                    'lib_variation' => $v['lib_variation']
                ];
            }

            return [
                'hashid_panier_item' => Hashids::encode($item->id),
                'hashid' => Hashids::encode($article->id),
                'nom_article' => $article->nom_article ?? 'Nom indisponible',
                'quantite' => $item->quantite,
                'prix_unitaire' => $article->prix ?? 0,
                'image' => $image,
                'variations' => $variationsLabel,
                'prix_avec_quantite' => $item->quantite * ($article->prix ?? 0),
            ];
        });

        $prix_total_panier = $items->sum('prix_avec_quantite');

        return response()->json([
            'success' => true,
            'cart' => $items,
            'id_panier' => $paniers->isNotEmpty() ? Hashids::encode($user->id) : null,
            'prix_total' => $prix_total_panier,
            'message' => 'Article ajouté au panier avec succès.'
        ]);

    } catch (ValidationException $e) {
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






    public function get_panier(Request $request)
{
    try {
        $normalize = function ($str) {
            return preg_replace('/[^a-z]/', '', strtolower($str));
        };

        $user = $request->user();

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

        $items = $paniers->map(function ($item) use ($normalize) {
            $article = $item->article;

            $image = 'image_par_defaut.jpg';
            $imagesArray = [];

            if (!empty($article->images)) {
                $decoded = json_decode($article->images, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $imagesArray = $decoded;
                }
            }

            if (count($imagesArray) > 0) {
                $image = $imagesArray[0];
            } elseif (!empty($article->image) && is_string($article->image)) {
                $image = $article->image;
            }

            // Ajout suffixes pour différencier les noms identiques
            $variations = $item->variations ?? [];
            $countNom = [];
            $variationsLabel = [];

            foreach ($variations as $v) {
                $nom = $normalize($v['nom_variation']);
                $countNom[$nom] = ($countNom[$nom] ?? 0) + 1;
                $suffix = $countNom[$nom] > 1 ? ' ' . $countNom[$nom] : '';
                $variationsLabel[] = [
                    'nom_variation' => $nom . $suffix,
                    'lib_variation' => $v['lib_variation']
                ];
            }

            $prixUnitaire = $article->prix ?? 0;

            return [
                'hashid_panier_item' => Hashids::encode($item->id),
                'hashid' => Hashids::encode($article->id),
                'nom_article' => $article->nom_article ?? 'Nom indisponible',
                'quantite' => $item->quantite,
                'prix_unitaire' => $prixUnitaire,
                'image' => $image,
                'variations' => $variationsLabel,
                'prix_avec_quantite' => $item->quantite * $prixUnitaire,
            ];
        });

        $prix_total_panier = $items->sum('prix_avec_quantite');

        $lastPanier = $paniers->last();

        return response()->json([
            'success' => true,
            'cart' => $items,
            'id_panier' => $lastPanier ? Hashids::encode($lastPanier->id_clt) : null,
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

        if (!empty($article->images)) {
            $decoded = json_decode($article->images, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $imagesArray = $decoded;
            }
        }

        if (count($imagesArray) > 0) {
            $image = $imagesArray[0];
        } elseif (!empty($article->image)) {
            $image = $article->image;
        }

        $prixUnitaire = $article->prix ?? 0;

        return [
            'hashid_panier_item' => Hashids::encode($item->id), // 🔁 identifiant unique de la ligne panier
            'hashid' => Hashids::encode($article->id),
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
            'hashid_panier_item' => 'required|string'
        ]);

        $user = $request->user();
        $id = Hashids::decode($request->hashid_panier_item)[0] ?? null;

        if (!$id) {
            return response()->json(['success' => false, 'message' => 'ID panier invalide.'], 400);
        }

        $panier = Panier::with('article')->where('id', $id)->where('id_clt', $user->id)->first();

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
            'hashid_panier_item' => 'required|string'
        ]);

        $user = $request->user();
        $id = Hashids::decode($request->hashid_panier_item)[0] ?? null;

        if (!$id) {
            return response()->json(['success' => false, 'message' => 'ID panier invalide.'], 400);
        }

        $panier = Panier::with('article')->where('id', $id)->where('id_clt', $user->id)->first();

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


    public function supprimerArticle(Request $request)
{
    try {
        $user = $request->user();

        $hashid_panier = $request->input('hashid_panier_item'); // ← identifiant unique de la ligne du panier
        $decodedPanierId = Hashids::decode($hashid_panier)[0] ?? null;

        if (!$decodedPanierId) {
            return response()->json([
                'success' => false,
                'cart' => null,
                'id_panier' => null,
                'prix_total' => 0,
                'message' => 'ID panier invalide.',
            ], 400);
        }

        $panier = Panier::where('id', $decodedPanierId)
            ->where('id_clt', $user->id)
            ->first();

        if (!$panier) {
            return response()->json([
                'success' => false,
                'cart' => null,
                'id_panier' => null,
                'prix_total' => 0,
                'message' => 'Article non trouvé dans le panier.',
            ], 404);
        }

        $panier->delete();

        // Panier mis à jour après suppression
        return $this->get_panier($request);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'cart' => null,
            'id_panier' => null,
            'prix_total' => 0,
            'message' => 'Une erreur est survenue : ' . $e->getMessage(),
        ], 500);
    }
}

}
