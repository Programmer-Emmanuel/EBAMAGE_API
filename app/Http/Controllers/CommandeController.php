<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Commande;
use App\Models\Commune;
use App\Models\Panier;
use App\Models\User;
use App\Models\Ville;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Vinkla\Hashids\Facades\Hashids;
use Illuminate\Support\Facades\Log;

class CommandeController extends Controller
{
public function commande_ajout(Request $request) 
{
    try {
        $user = $request->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Utilisateur non authentifié.'], 401);
        }

        $id_panier = Hashids::decode($request->id_panier)[0] ?? null;
        $id_ville = Hashids::decode($request->id_ville)[0] ?? null;
        $id_commune = Hashids::decode($request->id_commune)[0] ?? null;
        $quartier = $request->quartier;
        $moyen_de_paiement = $request->input('moyen_de_paiement', 1);

        if (!$id_panier || !$id_ville || !$quartier || !$id_commune) {
            return response()->json(['success' => false, 'message' => 'Paramètres manquants ou invalides.'], 400);
        }

        $request->merge([
            'id_panier' => $id_panier,
            'id_ville' => $id_ville,
            'id_commune' => $id_commune,
            'quartier' => $quartier,
            'moyen_de_paiement' => $moyen_de_paiement,
        ]);

        $request->validate([
            'id_panier' => 'required|exists:paniers,id_clt',
            'id_ville' => 'required|exists:villes,id',
            'id_commune' => 'required|exists:communes,id',
            'quartier' => 'required|string|max:255',
            'moyen_de_paiement' => 'required|in:0,1',
        ]);

        $paniers = Panier::where('id_clt', $user->id)->with('article.boutique')->get();

        if ($paniers->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'Panier vide.'], 400);
        }

        $articlesArray = [];
        $prix_total_articles = 0;
        $livraison = 1000;
        $id_btq = null;

        foreach ($paniers as $item) {
            $article = $item->article;
            if (!$article) continue;

            $quantite = $item->quantite;
            $prix_article = $article->prix;
            $prix_total_articles += $prix_article * $quantite;

            $images = json_decode($article->images, true);
            $image = is_array($images) && count($images) ? $images[0] : null;

            $hashid_btq = $article->boutique ? Hashids::encode($article->boutique->id) : null;
            $id_btq_decoded = $article->boutique ? $article->boutique->id : null;

            if (!$id_btq) {
                $id_btq = $id_btq_decoded;
            }

            $articlesArray[] = [
                'hashid' => Hashids::encode($article->id),
                'nom_article' => $article->nom_article,
                'prix' => $prix_article,
                'quantite' => $quantite,
                'image' => $image,
                'description' => $article->description,
                'variations' => $item->variations,
                'boutique' => $article->boutique ? [
                    'nom_btq' => $article->boutique->nom_btq,
                    'hashid_btq' => $hashid_btq,
                ] : null,
            ];
        }

        if (!$id_btq) {
            return response()->json(['success' => false, 'message' => 'Impossible de déterminer la boutique.'], 400);
        }

        $prix_total_commande = $prix_total_articles + $livraison;

        // Vérifier solde si paiement en ligne
        if ($moyen_de_paiement == 0) {
            if ($user->solde_tdl < $prix_total_commande) {
                return response()->json([
                    'success' => false,
                    'message' => 'Solde insuffisant pour effectuer la commande.',
                    'solde_disponible' => $user->solde_tdl,
                    'montant_commande' => $prix_total_commande,
                ], 403);
            }

            // Déduire le solde
            $user->solde_tdl -= $prix_total_commande;
            $user->save();
        }

        $commande = new Commande();
        $commande->id_clt = $user->id;
        $commande->id_btq = $id_btq;
        $commande->id_ville = $id_ville;
        $commande->id_commune = $id_commune;
        $commande->articles = json_encode($articlesArray);
        $commande->quantite = $paniers->sum('quantite');
        $commande->prix = $prix_total_articles;
        $commande->livraison = $livraison;
        $commande->prix_total = $prix_total_commande;
        $commande->statut = 'En attente';
        $commande->quartier = $quartier;
        $commande->moyen_de_paiement = $moyen_de_paiement;
        $commande->save();

        Panier::where('id_clt', $user->id)->delete();

        return response()->json([
            'success' => true,
            'message' => "Commande ajoutée avec succès.",
            'hashid' => Hashids::encode($commande->id),
            'client' => [
                'nom_clt' => $user->nom_clt,
                'hashid_clt' => Hashids::encode($user->id),
            ],
            'localisation' => [
                'commune' => Commune::find($id_commune)->lib_commune ?? null,
                'ville' => Ville::find($id_ville)->lib_ville ?? null,
                'quartier' => $quartier,
            ],
            'prix_total_articles' => $prix_total_articles,
            'livraison' => $livraison,
            'prix_total_commande' => $prix_total_commande,
            'articles' => $articlesArray,
            'statut' => $commande->statut,
            'moyen_de_paiement' => $commande->moyen_de_paiement == 1 ? 'à la livraison' : 'en ligne',
            'created_at' => $commande->created_at->toDateTimeString(),
            'updated_at' => $commande->updated_at->toDateTimeString(),
        ], 201);

    } catch (ValidationException $e) {
        return response()->json([
            'success' => false,
            'message' => 'Données invalides.',
            'erreur' => $e->errors(),
        ], 422);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Erreur serveur : '.$e->getMessage(),
        ], 500);
    }
}







public function liste_commande()
{
    try {
        $commandes = Commande::with([
            'client',
            'boutique',
            'ville',
            'commune',
        ])->get();

        $result = $commandes->map(function ($commande) {
            $articles = json_decode($commande->articles, true) ?? [];

            return [
                'hashid' => Hashids::encode($commande->id),
                'client' => $commande->client ? [
                    'nom_clt' => $commande->client->nom_clt,
                    'hashid_clt' => Hashids::encode($commande->client->id),
                ] : null,
                'localisation' => [
                    'commune' => $commande->commune->lib_commune ?? null,
                    'ville' => $commande->ville->lib_ville ?? null,
                    'quartier' => $commande->quartier,
                ],
                'prix_total_articles' => $commande->prix,
                'livraison' => $commande->livraison,
                'prix_total_commande' => $commande->prix_total,
                'articles' => collect($articles)->map(function ($article) {
                    return [
                        'hashid' => $article['hashid'] ?? null,
                        'nom_article' => $article['nom_article'] ?? null,
                        'prix' => $article['prix'] ?? null,
                        'quantite' => $article['quantite'] ?? null,
                        'image' => $article['image'] ?? null,
                        'description' => $article['description'] ?? null,
                        'variations' => $article['variations'] ?? [],
                        'boutique' => $article['boutique'] ?? null,
                    ];
                })->values(),
                'statut' => $commande->statut,
                'moyen_de_paiement' => $commande->moyen_de_paiement == 1 ? 'à la livraison' : 'en ligne',
                'created_at' => $commande->created_at->toDateTimeString(),
                'updated_at' => $commande->updated_at->toDateTimeString(),
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Liste complète des commandes récupérées avec succès.',
            'data' => $result,
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Erreur serveur : ' . $e->getMessage(),
        ], 500);
    }
}







    public function commande($hashid)
{
    try {
        $id = Hashids::decode($hashid)[0] ?? null;

        if (!$id) {
            return response()->json([
                'success' => false,
                'message' => 'Identifiant invalide.',
            ], 400);
        }

        $commande = Commande::with(['client', 'boutique', 'commune.ville'])->find($id);

        if (!$commande) {
            return response()->json([
                'success' => false,
                'message' => 'Commande non trouvée.',
            ], 404);
        }

        // Décoder les articles
        $articlesArray = json_decode($commande->articles, true) ?? [];

        // Construire la réponse identique à commande_ajout
        return response()->json([
            'success' => true,
            'message' => "Commande récupérée avec succès.",
            'hashid' => Hashids::encode($commande->id),
            'client' => [
                'nom_clt' => $commande->client->nom_clt,
                'hashid_clt' => Hashids::encode($commande->client->id),
            ],
            'localisation' => [
                'commune' => $commande->commune->lib_commune ?? null,
                'ville' => $commande->commune->ville->lib_ville ?? null,
                'quartier' => $commande->quartier,
            ],
            'prix_total_articles' => $commande->prix,
            'livraison' => $commande->livraison,
            'prix_total_commande' => $commande->prix_total,
            'articles' => $articlesArray,
            'statut' => $commande->statut,
            'moyen_de_paiement' => $commande->moyen_de_paiement == 1 ? 'à la livraison' : 'en ligne',
            'created_at' => $commande->created_at->toDateTimeString(),
            'updated_at' => $commande->updated_at->toDateTimeString(),
        ], 200);

    } catch (\Illuminate\Database\QueryException $e) {
        return response()->json([
            'success' => false,
            'message' => 'Erreur de base de données.',
            'error' => config('app.debug') ? $e->getMessage() : null,
        ], 500);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Erreur serveur.',
            'error' => config('app.debug') ? $e->getMessage() : null,
        ], 500);
    }
}

public function commandes_client(Request $request)
{
    try {
        $client = $request->user();

        if (!$client) {
            return response()->json([
                'success' => false,
                'message' => 'Utilisateur non authentifié.'
            ], 401);
        }

        $commandes = Commande::with(['client', 'boutique', 'ville', 'commune'])
            ->where('id_clt', $client->id)
            ->latest()
            ->get();

        $result = $commandes->map(function ($commande) {
            $articles = json_decode($commande->articles, true) ?? [];

            return [
                'hashid' => Hashids::encode($commande->id),
                'client' => [
                    'nom_clt' => $commande->client->nom_clt,
                    'hashid_clt' => Hashids::encode($commande->client->id),
                ],
                'localisation' => [
                    'commune' => $commande->commune->lib_commune ?? null,
                    'ville' => $commande->ville->lib_ville ?? null,
                    'quartier' => $commande->quartier,
                ],
                'prix_total_articles' => $commande->prix,
                'livraison' => $commande->livraison,
                'prix_total_commande' => $commande->prix_total,
                'articles' => collect($articles)->map(function ($article) {
                    return [
                        'hashid' => $article['hashid'] ?? null,
                        'id_article' => $article['id_article'] ?? null,
                        'nom_article' => $article['nom_article'] ?? null,
                        'prix' => $article['prix'] ?? null,
                        'quantite' => $article['quantite'] ?? null,
                        'image' => $article['image'] ?? null,
                        'description' => $article['description'] ?? null,
                        'variations' => $article['variations'] ?? [],
                        'boutique' => $article['boutique'] ?? null,
                    ];
                })->values(),
                'statut' => $commande->statut,
                'moyen_de_paiement' => $commande->moyen_de_paiement == 1 ? 'à la livraison' : 'en ligne',
                'created_at' => $commande->created_at->toDateTimeString(),
                'updated_at' => $commande->updated_at->toDateTimeString(),
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Commandes du client récupérées avec succès.',
            'data' => $result
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Erreur serveur : ' . $e->getMessage()
        ], 500);
    }
}


public function commandes_boutique(Request $request)
{
    try {
        $boutique = $request->user(); // Authentification via Sanctum ou autre

        if (!$boutique) {
            return response()->json([
                'success' => false,
                'message' => 'Boutique non authentifiée.'
            ], 401);
        }

        $commandes = Commande::with(['client', 'boutique', 'ville', 'commune'])
            ->where('id_btq', $boutique->id)
            ->latest()
            ->get();

        $result = $commandes->map(function ($commande) {
            $articles = json_decode($commande->articles, true) ?? [];

            return [
                'hashid' => Hashids::encode($commande->id),
                'client' => [
                    'nom_clt' => $commande->client->nom_clt,
                    'hashid_clt' => Hashids::encode($commande->client->id),
                ],
                'localisation' => [
                    'commune' => $commande->commune->lib_commune ?? null,
                    'ville' => $commande->ville->lib_ville ?? null,
                    'quartier' => $commande->quartier,
                ],
                'prix_total_articles' => $commande->prix,
                'livraison' => $commande->livraison,
                'prix_total_commande' => $commande->prix_total,
                'articles' => collect($articles)->map(function ($article) {
                    return [
                        'hashid' => $article['hashid'] ?? null,
                        'id_article' => $article['id_article'] ?? null,
                        'nom_article' => $article['nom_article'] ?? null,
                        'prix' => $article['prix'] ?? null,
                        'quantite' => $article['quantite'] ?? null,
                        'image' => $article['image'] ?? null,
                        'description' => $article['description'] ?? null,
                        'variations' => $article['variations'] ?? [],
                        'boutique' => $article['boutique'] ?? null,
                    ];
                })->values(),
                'statut' => $commande->statut,
                'moyen_de_paiement' => $commande->moyen_de_paiement == 1 ? 'à la livraison' : 'en ligne',
                'created_at' => $commande->created_at->toDateTimeString(),
                'updated_at' => $commande->updated_at->toDateTimeString(),
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Commandes de la boutique récupérées avec succès.',
            'data' => $result
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Erreur serveur : ' . $e->getMessage()
        ], 500);
    }
}


    public function edit_statut_reception($hashid)
{
    return $this->updateStatut($hashid, 'Reçue');
}

public function edit_statut_confirme($hashid)
{
    return $this->updateStatut($hashid, 'Confirmée');
}

public function edit_statut_annule($hashid)
{
    return $this->updateStatut($hashid, 'Annulée');
}


    /**
     * Méthode privée pour factoriser la mise à jour du statut
     */
private function updateStatut($hashid, $statut)
{
    try {
        $id = Hashids::decode($hashid)[0] ?? null;
        if (!$id) {
            return response()->json([
                'success' => false,
                'message' => 'Identifiant invalide.',
            ], 400);
        }

        $commande = Commande::with(['client', 'boutique', 'ville', 'commune'])->find($id);
        if (!$commande) {
            return response()->json([
                'success' => false,
                'message' => 'Commande non trouvée.',
            ], 404);
        }

        $commande->statut = $statut;
        $commande->save();

        // Décodage des articles
        $articles = json_decode($commande->articles, true) ?? [];

        return response()->json([
            'success' => true,
            'message' => "Statut mis à jour en '$statut' avec succès.",
            'hashid' => Hashids::encode($commande->id),
            'data' => [
                'client' => [
                    'nom_clt' => $commande->client->nom_clt,
                    'hashid_clt' => Hashids::encode($commande->client->id),
                ],
                'localisation' => [
                    'commune' => $commande->commune->lib_commune ?? null,
                    'ville' => $commande->ville->lib_ville ?? null,
                    'quartier' => $commande->quartier,
                ],
                'prix_total_articles' => $commande->prix,
                'livraison' => $commande->livraison,
                'prix_total_commande' => $commande->prix_total,
                'articles' => collect($articles)->map(function ($article) {
                    return [
                        'hashid' => $article['hashid'] ?? null,
                        'id_article' => $article['id_article'] ?? null,
                        'nom_article' => $article['nom_article'] ?? null,
                        'prix' => $article['prix'] ?? null,
                        'quantite' => $article['quantite'] ?? null,
                        'image' => $article['image'] ?? null,
                        'description' => $article['description'] ?? null,
                        'variations' => $article['variations'] ?? [],
                        'boutique' => $article['boutique'] ?? null,
                    ];
                })->values(),
                'statut' => $commande->statut,
                'moyen_de_paiement' => $commande->moyen_de_paiement == 1 ? 'à la livraison' : 'en ligne',
                'created_at' => $commande->created_at->toDateTimeString(),
                'updated_at' => $commande->updated_at->toDateTimeString(),
            ],
        ], 200);

    } catch (\Illuminate\Database\QueryException $e) {
        return response()->json([
            'success' => false,
            'message' => 'Erreur de base de données lors de la mise à jour.',
            'error' => config('app.debug') ? $e->getMessage() : null,
        ], 500);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Une erreur inattendue est survenue.',
            'error' => config('app.debug') ? $e->getMessage() : null,
        ], 500);
    }
}


    public function articles_tendance()
{
    try {
        $articles = Article::select(
                'articles.id',
                'articles.nom_article',
                'articles.prix',
                'articles.old_price',
                'articles.description',
                'articles.images',
                DB::raw('SUM(commandes.quantite) as total_commandes')
            )
            ->join('commandes', 'commandes.id_article', '=', 'articles.id')
            ->groupBy(
                'articles.id',
                'articles.nom_article',
                'articles.prix',
                'articles.old_price',
                'articles.description',
                'articles.images'
            )
            ->orderByDesc('total_commandes')
            ->limit(10)
            ->get();

        // Si moins de 3 articles ont été commandés, on bascule sur les recommandations
        if ($articles->count() < 3) {
            $recommended = Article::inRandomOrder()
                ->limit(10)
                ->get(['id', 'nom_article', 'prix', 'old_price', 'description', 'images']);

            $formattedRecommended = $recommended->map(function ($article) {
                $images = json_decode($article->images, true);
                return [
                    'nom_article' => $article->nom_article,
                    'prix' => $article->prix,
                    'old_price' => $article->old_price,
                    'description' => $article->description,
                    'image' => is_array($images) ? $images[0] ?? null : null,
                    'hashid' => Hashids::encode($article->id),
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Peu d’articles commandés, articles tendances proposés à la place.',
                'data' => $formattedRecommended,
            ]);
        }

        // Sinon on retourne les tendances normales
        $formatted = $articles->map(function ($article) {
            $images = json_decode($article->images, true);
            return [
                'nom_article' => $article->nom_article,
                'prix' => $article->prix,
                'old_price' => $article->old_price,
                'description' => $article->description,
                'image' => is_array($images) ? $images[0] ?? null : null,
                'hashid' => Hashids::encode($article->id),
                'total_commandes' => $article->total_commandes,
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Top 10 des articles les plus commandés.',
            'data' => $formatted,
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Une erreur est survenue lors de la récupération.',
            'error' => config('app.debug') ? $e->getMessage() : null,
        ], 500);
    }
}




    public function articles_recommandes()
{
    try {
        $articles = Article::inRandomOrder()
            ->limit(10)
            ->get(['id', 'nom_article', 'prix', 'old_price', 'description', 'images']);

        if ($articles->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun article disponible pour la recommandation.',
            ], 404);
        }

        $formatted = $articles->map(function ($article) {
            $images = json_decode($article->images, true);
            return [
                'nom_article' => $article->nom_article,
                'prix' => $article->prix,
                'old_price' => $article->old_price,
                'description' => $article->description,
                'image' => is_array($images) ? $images[0] ?? null : null,
                'hashid' => Hashids::encode($article->id),
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Articles recommandés récupérés avec succès.',
            'data' => $formatted,
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Erreur lors de la récupération des articles recommandés.',
            'error' => config('app.debug') ? $e->getMessage() : null,
        ], 500);
    }
}


}
