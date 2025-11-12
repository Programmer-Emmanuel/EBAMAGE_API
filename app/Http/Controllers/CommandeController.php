<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Commande;
use App\Models\Commune;
use App\Models\Panier;
use App\Models\User;
use App\Models\Boutique;
use App\Models\Notification as NotificationModel;
use App\Models\Ville;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Vinkla\Hashids\Facades\Hashids;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use App\Mail\CommandeCreeeMail;
use App\Mail\CommandeConfirmeeMail;
use App\Mail\CommandeLivreeMail;
use App\Mail\CommandeAnnuleeMail;
use App\Models\Admin;
use App\Models\Portefeuille;
use App\Models\Prix;
use App\Models\Seuil;
use Carbon\Carbon;
use Illuminate\Support\Str;

class CommandeController extends Controller
{public function commande_ajout(Request $request)
{
    DB::beginTransaction();

    try {
        $user = $request->user();
        if (!$user) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Utilisateur non authentifié.'], 401);
        }

        $id_panier_decoded = Hashids::decode($request->id_panier);
        if (empty($id_panier_decoded)) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'ID panier invalide.'], 400);
        }
        $id_panier = $id_panier_decoded[0];

        $lib_ville = $request->input('lib_ville');
        $lib_commune = $request->input('lib_commune');
        $quartier = $request->quartier;
        $moyen_de_paiement = $request->input('moyen_de_paiement', 1);

        if (!$id_panier || !$lib_ville || !$lib_commune || !$quartier) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Paramètres manquants ou invalides.'], 400);
        }

        $panierUser = Panier::where('id', $id_panier)
            ->where('id_clt', $user->id)
            ->first();

        if (!$panierUser) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Panier non trouvé ou ne vous appartient pas.'], 404);
        }

        $ville = Ville::where('lib_ville', $lib_ville)->first();
        $commune = Commune::where('lib_commune', $lib_commune)
            ->where('id_ville', $ville ? $ville->id : 0)
            ->first();

        if (!$ville || !$commune) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => !$ville
                    ? "Ville '{$lib_ville}' non trouvée."
                    : "Commune '{$lib_commune}' non trouvée pour la ville '{$lib_ville}'."
            ], 404);
        }

        // Récupérer tous les paniers de l'utilisateur avec les articles et boutiques
        $paniers = Panier::where('id_clt', $user->id)
            ->with(['article.boutique'])
            ->get();

        if ($paniers->isEmpty()) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Panier vide.'], 400);
        }

        $articlesArray = [];
        $prix_total_articles = 0;


        foreach ($paniers as $item) {
            $article = $item->article;
            if (!$article) continue;

            $quantite = $item->quantite ?? 1;
            $prix_article = $article->prix ?? 0;
            $prix_total_articles += ($prix_article * $quantite);

            $images = json_decode($article->images, true);
            $image = is_array($images) && count($images) ? $images[0] : null;

            $articlesArray[] = [
                'hashid' => Hashids::encode($article->id),
                'nom_article' => $article->nom_article,
                'prix' => $prix_article,
                'quantite' => $quantite,
                'image' => $image,
                'description' => $article->description,
                'variations' => $item->variations,
                'boutique' => [
                    'nom_btq' => $article->boutique->nom_btq ?? null,
                    'hashid_btq' => Hashids::encode($article->boutique->id ?? 0),
                ],
                'statut_sous_commande' => 'En attente',
            ];
        }


        $prix = Prix::where('id', 1)->first();
        $seuil = Seuil::where('id', 1)->first();
        $livraison = $prix->prix;
        if($prix_total_articles==$seuil->seuil){
            $livraison = 0;
        }

        $prix_total_commande = $prix_total_articles + $livraison;

        $pattern = strtoupper(Str::random(4));

        $commande = new Commande();
        $commande->id_clt = $user->id;
        $commande->id_btq = $paniers->first()->article->boutique->id ?? null;
        $commande->id_ville = $ville->id;
        $commande->id_commune = $commune->id;
        $commande->articles = json_encode($articlesArray);
        $commande->quantite = $paniers->sum('quantite');
        $commande->prix = $prix_total_articles;
        $commande->livraison = $livraison;
        $commande->prix_total = $prix_total_commande;
        $commande->statut = 'En attente';
        $commande->quartier = $quartier;
        $commande->moyen_de_paiement = $moyen_de_paiement;
        $commande->code_commande = 'CMD' . $pattern;
        $commande->is_paid = false;
        $commande->save();

        DB::commit();

        // Supprimer le panier après commande
        Panier::where('id_clt', $user->id)->delete();

        $livreur_reclame = Portefeuille::where('id_commande', $commande->id)
                ->where('role', 'livreur')
                ->exists();

            if (!$livreur_reclame) {
                Portefeuille::create([
                    'montant' => $commande->livraison,
                    'role' => 'livreur',
                    'id_commande' => $commande->id,
                    'id_beneficiaire' => null,
                    'statut' => 'Réclamé',
                ]);

        }

        return response()->json([
            'success' => true,
            'message' => "Commande ajoutée avec succès.",
            'hashid' => Hashids::encode($commande->id),
            'client' => [
                'nom_clt' => $user->nom_clt,
                'hashid_clt' => Hashids::encode($user->id),
            ],
            'localisation' => [
                'ville' => $ville->lib_ville,
                'commune' => $commune->lib_commune,
                'quartier' => $quartier
            ],
            'prix_total_articles' => $prix_total_articles,
            'livraison' => $livraison,
            'prix_total_commande' => $prix_total_commande,
            'articles' => $articlesArray,
            'statut' => $commande->statut,
            'code_commande' => $commande->code_commande,
            'moyen_de_paiement' => $moyen_de_paiement == 1 ? 'à la livraison' : 'mobile money',
            'created_at' => $commande->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $commande->updated_at->format('Y-m-d H:i:s'),
        ], 201);

    } catch (\Exception $e) {
        DB::rollBack();
        Log::error("Erreur commande_ajout : " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
        return response()->json([
            'success' => false,
            'message' => 'Erreur serveur.',
            "erreu" => $e->getMessage()
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

        // Ajouter le statut sous commande si manquant
        $articlesArray = collect($articlesArray)->map(function ($article) {
            $article['statut_sous_commande'] = $article['statut_sous_commande'] ?? 'En attente';
            return $article;
        })->values();

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
            'code_commande' => $commande->code_commande,
            'moyen_de_paiement' => $commande->moyen_de_paiement == 1 ? 'à la livraison' : 'en ligne',
            'created_at' => $commande->created_at->toDateTimeString(),
            'updated_at' => $commande->updated_at->toDateTimeString(),
        ], 200);

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

        $commandes = Commande::where('id_clt', $client->id)
            ->latest()
            ->get();

        $result = $commandes->map(function ($commande) {
            $articles = is_string($commande->articles)
                ? json_decode($commande->articles, true)
                : $commande->articles;

            // Ajout du statut sous commande par défaut
            $articles = collect($articles)->map(function ($article) {
                $article['statut_sous_commande'] = $article['statut_sous_commande'] ?? 'En attente';
                return $article;
            })->values();

            return [
                'hashid' => Hashids::encode($commande->id),
                'created_at' => $commande->created_at->setTimezone('UTC')->format('Y-m-d\TH:i:s\Z'),
                'nombre_articles' => collect($articles)->sum(function ($article) {
                    return $article['quantite'] ?? 0;
                }),
                'articles' => $articles,
                'prix_total' => $commande->prix_total,
                'statut' => $commande->statut,
                'code_commande' => $commande->code_commande,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $result,
            'message' => 'Commandes du client récupérées avec succès.',
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Erreur serveur : ' . $e->getMessage()
        ], 500);
    }
}




public function rechercherCommande(Request $request)
{
    $commande = Commande::where('code_commande', $request->code_commande)->first();

    if (!$commande) {
        return response()->json([
            'success' => false,
            'message' => 'Commande non trouvée.',

        ], 404);
    }

    return response()->json([
        'success' =>true,
        'data' => $commande,
        'message' => 'Commande trouvée !',
    ]);
}




   public function edit_statut_confirme($hashid)
{
    $response = $this->updateStatutCommande($hashid, 'Confirmée');

    // Si la commande existe et le statut a bien été mis à jour :
    if ($response->getData()->success ?? false) {
        $commande = Commande::where('id', Hashids::decode($hashid)[0] ?? null)
            ->with(['client', 'boutique'])
            ->first();

        // if ($commande && $commande->client && $commande->boutique) {
        //     Mail::to($commande->client->email_clt)->send(new CommandeConfirmeeMail($commande, 'client'));
        //     Mail::to($commande->boutique->email_btq)->send(new CommandeConfirmeeMail($commande, 'boutique'));
        // }
    }

    return $response;
}

public function edit_statut_livree($hashid)
{
    $response = $this->updateStatutCommande($hashid, 'Livrée');

    if ($response->getData()->success ?? false) {
        $commande = Commande::where('id', Hashids::decode($hashid)[0] ?? null)
            ->with(['client', 'boutique'])
            ->first();

        if (!$commande) {
            return response()->json([
                'success' => false,
                'message' => 'Commande introuvable.'
            ], 404);
        }

        // 🟩 Calcul de la commission admin (10%)
        $articles = json_decode($commande->articles, true);
        $total_commission = 0;

        foreach ($articles as $article) {
            $prix_article = $article['prix'] * $article['quantite'];
            $commission = ($prix_article * 10) / 100;
            $total_commission += $commission;
        }

        // 🟨 Mise à jour du solde admin
        $admin = Admin::where('role', 'super_admin')->first();
        if ($admin) {
            $admin->solde_admin += $total_commission;
            $admin->save();
        }

        // 🟩 Mettre à jour le portefeuille du livreur
        $portefeuilleLivreur = Portefeuille::where('id_commande', $commande->id)
            ->where('role', 'livreur')
            ->first();

        if ($portefeuilleLivreur) {
            $portefeuilleLivreur->statut = 'Payé';
            $portefeuilleLivreur->is_paid = 1; // au cas où tu as une colonne booléenne
            $portefeuilleLivreur->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'Commande marquée comme livrée. Portefeuilles mis à jour avec succès.'
        ], 200);
    }

    return $response;
}


public function edit_statut_annule($hashid)
{
    $response = $this->updateStatutCommande($hashid, 'Annulée');

    if ($response->getData()->success ?? false) {
        $commande = Commande::where('id', Hashids::decode($hashid)[0] ?? null)
            ->with(['client', 'boutique'])
            ->first();

        // if ($commande && $commande->client && $commande->boutique) {
        //     Mail::to($commande->client->email_clt)->send(new CommandeAnnuleeMail($commande, 'client'));
        //     Mail::to($commande->boutique->email_btq)->send(new CommandeAnnuleeMail($commande, 'boutique'));
        // }
    }

    return $response;
}


/**
 * ➤ Met à jour le statut global d’une commande
 */
private function updateStatutCommande($hashid, $statut)
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

        $ancienStatut = $commande->statut;
        $commande->statut = $statut;
        $commande->save();

        // Notifications (facultatif)
        $this->sendStatusNotification($commande, $statut, $ancienStatut);

        // Mettre à jour les statuts des sous-commandes aussi
        $articles = json_decode($commande->articles, true) ?? [];
        $articles = collect($articles)->map(function ($article) use ($statut) {
            $article['statut_sous_commande'] = $statut;
            return $article;
        })->values();

        // Sauvegarde de la mise à jour
        $commande->articles = json_encode($articles);
        $commande->save();

        return response()->json([
            'success' => true,
            'message' => "Statut de la commande et des sous-commandes mis à jour en '$statut' avec succès.",
            'hashid' => Hashids::encode($commande->id),
            'data' => [
                'statut' => $commande->statut,
                'articles' => $articles,
                'code_commande' => $commande->code_commande,
            ],
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Erreur lors de la mise à jour du statut : ' . $e->getMessage(),
        ], 500);
    }
}


public function edit_statut_sous_commande(Request $request, $hashid_commande, $hashid_article)
{
    try {
        $id_commande = Hashids::decode($hashid_commande)[0] ?? null;
        if (!$id_commande) {
            return response()->json([
                'success' => false,
                'message' => 'Identifiant commande invalide.',
            ], 400);
        }

        $commande = Commande::find($id_commande);
        if (!$commande) {
            return response()->json([
                'success' => false,
                'message' => 'Commande non trouvée.',
            ], 404);
        }

        $statut = $request->input('statut');
        $variation_lib = $request->input('lib_variation'); // facultatif mais utile pour identifier l’article exact

        $articles = json_decode($commande->articles, true) ?? [];
        $articleTrouve = false;

        $articles = collect($articles)->map(function ($article) use ($hashid_article, $statut, $variation_lib, &$articleTrouve) {
            // Vérifie si le hashid correspond
            if (($article['hashid'] ?? null) === $hashid_article) {
                // Vérifie aussi la variation pour être précis
                $variationCourante = $article['variations'][0]['lib_variation'] ?? null;
                if (!$variation_lib || $variationCourante === $variation_lib) {
                    $article['statut_sous_commande'] = $statut;
                    $articleTrouve = true;
                }
            }
            return $article;
        })->values();

        if (!$articleTrouve) {
            return response()->json([
                'success' => false,
                'message' => 'Sous-commande non trouvée.',
            ], 404);
        }

        $commande->articles = json_encode($articles);
        $commande->save();

        return response()->json([
            'success' => true,
            'message' => "Statut de la sous-commande mis à jour en '$statut' avec succès.",
            'hashid_commande' => Hashids::encode($commande->id),
            'code_commande' => $commande->code_commande,
            'articles' => $articles,
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Erreur serveur : ' . $e->getMessage(),
        ], 500);
    }
}




    public function articles_tendance()
    {
        try {
            $commandes = \App\Models\Commande::all();

            $articlesMap = [];

            foreach ($commandes as $commande) {
                $articles = json_decode($commande->articles, true) ?? [];

                foreach ($articles as $article) {
                    $id = $article['hashid'] ?? null;
                    $quantite = $article['quantite'] ?? 0;

                    if (!$id) continue;

                    if (!isset($articlesMap[$id])) {
                        $articlesMap[$id] = [
                            'hashid' => $id,
                            'quantite' => 0,
                        ];
                    }

                    $articlesMap[$id]['quantite'] += $quantite;
                }
            }

            // Trier les articles par quantité décroissante
            $sorted = collect($articlesMap)->sortByDesc('quantite')->take(10);

            // Récupérer les détails des articles
            $result = $sorted->map(function ($item) {
                $id_article = Hashids::decode($item['hashid'])[0] ?? null;
                if (!$id_article) return null;

                $article = \App\Models\Article::find($id_article);
                if (!$article) return null;

                $images = json_decode($article->images, true);

                return [
                    'nom_article' => $article->nom_article,
                    'prix' => $article->prix,
                    'old_price' => $article->old_price,
                    'description' => $article->description,
                    'image' => is_array($images) ? $images[0] ?? null : null,
                    'hashid' => Hashids::encode($article->id),
                    'total_commandes' => $item['quantite'],
                ];
            })->filter()->values();

            // Si moins de 3 articles commandés, basculer sur recommandations
            if ($result->count() < 3) {
                $recommended = \App\Models\Article::inRandomOrder()
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
                    'message' => 'Peu d\'articles commandés, articles tendances proposés à la place.',
                    'data' => $formattedRecommended,
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Top 10 des articles les plus commandés.',
                'data' => $result,
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

    /**
     * Envoyer une notification via Expo
     */
    private function sendExpoNotification($deviceToken, $title, $body, $type = null)
    {
        try {
            $data = [
                'to' => $deviceToken,
                'title' => $title,
                'body' => $body,
                'sound' => 'default',
            ];

            // Ajouter le type dans les données si fourni
            if ($type) {
                $data['data'] = [
                    'type' => $type
                ];
            }

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post('https://api.expo.dev/v2/push/send', $data);

            if ($response->successful()) {
                $responseData = $response->json();
                if (isset($responseData['data'][0]['status']) && $responseData['data'][0]['status'] === 'ok') {
                    return true;
                } else {
                    $errorMessage = $responseData['data'][0]['message'] ?? 'Erreur inconnue de l\'API Expo';
                    Log::error('Erreur Expo: ' . $errorMessage);
                    return false;
                }
            } else {
                Log::error('Erreur lors de l\'envoi à l\'API Expo: ' . $response->status());
                return false;
            }

        } catch (\Exception $e) {
            Log::error('Erreur envoi Expo: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Envoyer notification à une boutique et l'enregistrer en BDD
     * Pour les boutiques, on utilise uniquement boutique_id et user_id est null
     */
    private function sendNotificationToBoutique($boutique, $title, $message, $type = null)
    {
        if (!$boutique || !$boutique->id) {
            Log::warning("Impossible d'envoyer notification : boutique invalide");
            return false;
        }

        try {
            // Envoyer la notification Expo si device_token disponible
            if (!empty($boutique->device_token)) {
                $this->sendExpoNotification($boutique->device_token, $title, $message, $type);
            }

            // Préparer les données pour BDD - SEULEMENT boutique_id (user_id = null)
            $data = [
                'boutique_id' => $boutique->id,
                'user_id' => null, // Explicitement null pour les boutiques
                'title' => $title,
                'message' => $message,
                'type' => $type ?? 'general',
            ];

            Log::info("Création notification boutique", $data);

            // Créer la notification avec le modèle corrigé
            NotificationModel::create($data);

            Log::info("Notification enregistrée en BDD pour la boutique {$boutique->id}");
            return true;
        } catch (\Exception $e) {
            Log::error("Erreur envoi notification boutique: {$e->getMessage()}", [
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Envoyer notification à un client et l'enregistrer en BDD
     * Pour les clients, on utilise uniquement user_id et boutique_id est null
     */
    private function sendNotificationToClient($client, $title, $message, $type = null)
    {
        if (!$client || !$client->id) {
            Log::warning("Impossible d'envoyer notification : client invalide");
            return false;
        }

        try {
            // Envoyer la notification Expo si device_token disponible
            if (!empty($client->device_token)) {
                $this->sendExpoNotification($client->device_token, $title, $message, $type);
            }

            // Préparer les données pour BDD - SEULEMENT user_id (boutique_id = null)
            $data = [
                'user_id' => $client->id,
                'boutique_id' => null, // Explicitement null pour les clients
                'title' => $title,
                'message' => $message,
                'type' => $type ?? 'general',
            ];

            Log::info("Création notification client", $data);

            // Créer la notification avec le modèle corrigé
            NotificationModel::create($data);

            Log::info("Notification enregistrée en BDD pour le client {$client->id}");
            return true;
        } catch (\Exception $e) {
            Log::error("Erreur envoi notification client: {$e->getMessage()}", [
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Envoyer les notifications selon le changement de statut et les enregistrer en BDD
     */
    private function sendStatusNotification($commande, $nouveauStatut, $ancienStatut)
    {
        $client = $commande->client;
        $boutique = $commande->boutique;

        if (!$client && !$boutique) {
            Log::warning("Aucun destinataire pour la notification de la commande {$commande->id}");
            return;
        }

        switch ($nouveauStatut) {
            case 'Reçue':
                // NOTIFICATION CLIENT
                if ($client) {
                    $this->sendNotificationToClient(
                        $client,
                        "Commande Reçue 🏪",
                        "Votre commande a été réceptionnée par la boutique. Elle est maintenant en cours de préparation.",
                        'commande_recue'
                    );
                }
                // NOTIFICATION BOUTIQUE
                if ($boutique) {
                    $this->sendNotificationToBoutique(
                        $boutique,
                        "Commande Réceptionnée ✅",
                        "Vous avez marqué la commande #" . Hashids::encode($commande->id) . " comme réceptionnée. Préparez-la pour la livraison.",
                        'commande_recue'
                    );
                }
                break;

            case 'Livrée':
                // NOTIFICATION CLIENT
                if ($client) {
                    $this->sendNotificationToClient(
                        $client,
                        "Commande Livrée 🎉",
                        "Votre commande a été livrée avec succès ! Merci pour votre confiance.",
                        'commande_livree'
                    );
                }
                // NOTIFICATION BOUTIQUE
                if ($boutique) {
                    $this->sendNotificationToBoutique(
                        $boutique,
                        "Commande Livrée ✅",
                        "La commande #" . Hashids::encode($commande->id) . " a été marquée comme livrée. Transaction terminée.",
                        'commande_livree'
                    );
                }
                break;

            case 'Annulée':
                // NOTIFICATION CLIENT
                if ($client) {
                    $this->sendNotificationToClient(
                        $client,
                        "Commande Annulée ❌",
                        "Votre commande a été annulée. Si c'est une erreur, contactez la boutique.",
                        'commande_annulee'
                    );
                }
                // NOTIFICATION BOUTIQUE
                if ($boutique) {
                    $this->sendNotificationToBoutique(
                        $boutique,
                        "Commande Annulée ❌",
                        "Vous avez annulé la commande #" . Hashids::encode($commande->id) . ". Le client a été notifié.",
                        'commande_annulee'
                    );
                }
                break;

            default:
                Log::info("Aucun traitement de notification défini pour le statut '{$nouveauStatut}' de la commande {$commande->id}");
                break;
        }
    }

    /**
     * Récupérer les notifications pour l'utilisateur connecté
     */
    public function notifications()
    {
        try {
            $user = auth('client')->user();
            $boutique = auth('boutique')->user();

            if ($user) {
                $notifications = NotificationModel::where('user_id', $user->id)
                    ->orderBy('created_at', 'desc')
                    ->get(['id', 'title', 'message', 'type', 'created_at']);
            } elseif ($boutique) {
                $notifications = NotificationModel::where('boutique_id', $boutique->id)
                    ->orderBy('created_at', 'desc')
                    ->get(['id', 'title', 'message', 'type', 'created_at']);
            } else {
                return response()->json([
                    'success' => false,
                    'data' => null,
                    'message' => 'Utilisateur non authentifié'
                ], 401);
            }

            // Transformer chaque notification
            $data = $notifications->map(function ($notif) {
                return [
                    'hashid' => Hashids::encode($notif->id),
                    'title' => $notif->title,
                    'message' => $notif->message,
                    'type' => $notif->type,
                    'created_at' => $notif->created_at->copy()->setTimezone('UTC')->format('Y-m-d\TH:i:s\Z'),
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $data,
                'message' => 'Notifications récupérées avec succès'
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur récupération notifications: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'data' => null,
                'message' => 'Erreur lors de la récupération des notifications',
                'erreur' => $e->getMessage()
            ], 500);
        }
    }

    public function liste_commande()
    {
        try {
            $commandes = Commande::with(['client', 'boutique', 'ville', 'commune'])
                ->latest()
                ->get();

            $result = $commandes->map(function ($commande) {
                $articles = json_decode($commande->articles, true) ?? [];

                return [
                    'hashid' => Hashids::encode($commande->id),
                    'client' => $commande->client ? [
                        'nom_clt' => $commande->client->nom_clt,
                        'email_clt' => $commande->client->email_clt,
                        'tel_clt' => $commande->client->tel_clt,
                        'hashid_clt' => Hashids::encode($commande->client->id),
                    ] : null,
                    'boutique' => $commande->boutique ? [
                        'nom_btq' => $commande->boutique->nom_btq,
                        'email_btq' => $commande->boutique->email_btq,
                        'tel_btq' => $commande->boutique->tel_btq,
                        'hashid_btq' => Hashids::encode($commande->boutique->id),
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
                    'code_commande' => $commande->code_commande,
                    'moyen_de_paiement' => $commande->moyen_de_paiement == 1 ? 'à la livraison' : 'en ligne',
                    'created_at' => $commande->created_at->toDateTimeString(),
                    'updated_at' => $commande->updated_at->toDateTimeString(),
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => 'Liste complète des commandes récupérées avec succès.',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur serveur : ' . $e->getMessage(),
                'error' => config('app.debug') ? $e->getTraceAsString() : null,
            ], 500);
        }
    }

    public function filtrerCommandes(Request $request)
    {
        try {
            $recherche = trim($request->input('recherche', ''));

            // On charge les relations nécessaires
            $query = Commande::with(['client', 'boutique', 'ville', 'commune']);

            // 🔍 Filtrage par villes et communes uniquement
            if (!empty($recherche)) {
                $query->where(function ($q) use ($recherche) {
                    $q->whereHas('ville', function ($sub) use ($recherche) {
                        $sub->whereRaw('LOWER(lib_ville) LIKE ?', ['%' . strtolower($recherche) . '%']);
                    })
                    ->orWhereHas('commune', function ($sub) use ($recherche) {
                        $sub->whereRaw('LOWER(lib_commune) LIKE ?', ['%' . strtolower($recherche) . '%']);
                    });
                });
            }

            // Récupération des commandes filtrées
            $commandes = $query->latest()->get();

            // Si aucune commande trouvée
            if ($commandes->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucune commande trouvée pour cette ville ou commune.',
                    'recherche' => $recherche ?: null,
                ], 404);
            }

            // 🔁 Transformation des données
            $result = $commandes->map(function ($commande) {
                $articles = json_decode($commande->articles, true);
                if (!is_array($articles)) {
                    $articles = [];
                }

                return [
                    'hashid' => Hashids::encode($commande->id),
                    'client' => $commande->client ? [
                        'nom_clt' => $commande->client->nom_clt ?? '',
                        'email_clt' => $commande->client->email_clt ?? '',
                        'tel_clt' => $commande->client->tel_clt ?? '',
                        'hashid_clt' => Hashids::encode($commande->client->id),
                    ] : null,
                    'boutique' => $commande->boutique ? [
                        'nom_btq' => $commande->boutique->nom_btq ?? '',
                        'email_btq' => $commande->boutique->email_btq ?? '',
                        'tel_btq' => $commande->boutique->tel_btq ?? '',
                        'hashid_btq' => Hashids::encode($commande->boutique->id),
                    ] : null,
                    'localisation' => [
                        'ville' => $commande->ville->lib_ville ?? null,
                        'commune' => $commande->commune->lib_commune ?? null,
                        'quartier' => $commande->quartier,
                    ],
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
                            'statut_sous_commande' => $article['statut_sous_commande'] ?? 'En attente',
                        ];
                    })->values(),
                    'prix_total_articles' => (int) $commande->prix,
                    'livraison' => (int) $commande->livraison,
                    'prix_total_commande' => (int) $commande->prix_total,
                    'statut' => $commande->statut,
                    'code_commande' => $commande->code_commande,
                    'moyen_de_paiement' => $commande->moyen_de_paiement == 1 ? 'À la livraison' : 'En ligne',
                    'created_at' => $commande->created_at->format('Y-m-d H:i:s'),
                    'updated_at' => $commande->updated_at->format('Y-m-d H:i:s'),
                ];
            });

            // ✅ Réponse JSON finale
            return response()->json([
                'success' => true,
                'data' => $result,
                'total_commandes' => $result->count(),
                'recherche' => $recherche ?: null,
                'message' => 'Commandes filtrées par villes et communes récupérées avec succès.',
            ], 200);

        } catch (\Throwable $e) {
            Log::error('Erreur filtrerCommandes : ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du filtrage des commandes : ' . $e->getMessage(),
            ], 500);
        }
    }







    // GERER PORTEFEUILLE
    public function admin_solde(Request $request){
        try{
            $admin = $request->user();
            if(!$admin || $admin->role != 'super_admin'){
                return response()->json([
                    'success' => false,
                    'message' => 'Vous n’êtes pas autorisé.'
                ],403);
            }
$portefeuille_boutique = Portefeuille::where('role', 'boutique')
    ->where('is_paid', 0)
    ->sum('montant');

$portefeuille_livreur = Portefeuille::where('role', 'livreur')
    ->where('is_paid', 0)
    ->sum('montant');


            return response()->json([
                'success' => true,
                'data' => [
                    'solde_admin' => $admin->solde_admin,
                    'solde_boutique' => (int) $portefeuille_boutique,
                    'solde_livreur' => (int) $portefeuille_livreur
                ],
                'message' => 'Affichage des soldes'
            ]);
        }
        catch(QueryException $e){
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l’affichage des soldes',
                'erreur' => $e->getMessage()
            ]);
        }
    }

    //Commande boutique
    public function commandes_boutique(Request $request)
{
    try {
        $boutique = $request->user();

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
                        'statut_sous_commande' => $article['statut_sous_commande'] ?? 'En attente',
                    ];
                })->values(),
                'statut' => $commande->statut,
                'code_commande' => $commande->code_commande,
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


public function reclammer_du(Request $request)
{
    $boutique = $request->user();

    if (!$boutique) {
        return response()->json([
            'success' => false,
            'message' => 'Boutique non authentifiée.'
        ], 401);
    }

    $decoded = Hashids::decode($request->id_commande);
    if (empty($decoded)) {
        return response()->json([
            'success' => false,
            'message' => 'ID de commande invalide.'
        ], 400);
    }

    $id_commande = $decoded[0];

    $commande = Commande::where('id', $id_commande)
        ->where('id_btq', $boutique->id)
        ->where('statut', 'Livrée')
        ->where('is_paid', false)
        ->lockForUpdate()
        ->first();

    if (!$commande) {
        return response()->json([
            'success' => false,
            'message' => 'Commande introuvable ou non éligible à la réclamation.'
        ], 404);
    }

    if ($commande->is_claimed === true) {
        return response()->json([
            'success' => false,
            'message' => 'Cette commande a déjà été réclamée.'
        ], 403);
    }

    if ($commande->updated_at > Carbon::now()->subDays(3)) {
        return response()->json([
            'success' => false,
            'message' => 'Le délai de 3 jours après la livraison n’est pas encore écoulé.'
        ], 403);
    }

    DB::beginTransaction();

    try {
        // 💰 Calcul du montant dû à la boutique (90 % du prix total des articles)
        $articles = json_decode($commande->articles, true);

        $total_articles = collect($articles)->sum(function ($article) {
            return $article['prix'] * $article['quantite'];
        });

        // ✅ 90% pour la boutique
        $montant_boutique = $total_articles * 0.9;

        // 🔍 Vérifier si un portefeuille existe déjà
        $deja_cree = Portefeuille::where('id_commande', $commande->id)
            ->where('role', 'boutique')
            ->exists();

        if ($deja_cree) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Vous avez déjà réclamé le dû de cette commande.'
            ], 409);
        }

        // 🟧 Créer le portefeuille de la boutique
        $portefeuille_boutique = Portefeuille::create([
            'montant' => $montant_boutique,
            'role' => 'boutique',
            'id_commande' => $commande->id,
            'id_beneficiaire' => $boutique->id,
            'statut' => 'Réclamé',
            'is_paid' => 0,
        ]);

        // 🟩 Marquer la commande comme réclamée
        $commande->update(['is_claimed' => true]);

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Dû réclamé avec succès. Le montant a été enregistré.',
            'montant_total_articles' => $total_articles,
            'montant_boutique_90%' => $montant_boutique,
            'data' => $portefeuille_boutique,
        ]);

    } catch (QueryException $e) {
        DB::rollBack();

        return response()->json([
            'success' => false,
            'message' => 'Erreur lors de la réclamation du dû.',
            'erreur' => $e->getMessage()
        ], 500);
    }
}









public function afficher_portefeuille(Request $request)
{
    try {
        // 🔹 Récupérer tous les portefeuilles avec les relations nécessaires
        $portefeuilles = Portefeuille::with(['commande.boutique'])->get();

        // 🔸 Séparer les portefeuilles par rôle
        $boutiques = $portefeuilles->where('role', 'boutique')->map(function ($item) {
            $commande = $item->commande;

            return [
                'hashid'          => Hashids::encode($item->id),
                'code_commande'   => $commande?->code_commande,
                'nom_boutique'    => $commande?->boutique?->nom_btq,
                'commande_statut' => $commande?->statut,
                'montant'         => $item->montant,
                'statut'          => $item->statut,
                'is_paid'         => $item->is_paid,
                'date_creation'   => optional($commande?->created_at)->format('d/m/Y H:i'),
            ];
        })->values();

        $livreurs = $portefeuilles->where('role', 'livreur')->map(function ($item) {
            $commande = $item->commande;

            return [
                'hashid'          => Hashids::encode($item->id),
                'code_commande'   => $commande?->code_commande,
                'nom_livreur'     => $commande?->livreur?->nom_livreur,
                'commande_statut' => $commande?->statut,
                'montant'         => $item->montant,
                'statut'          => $item->statut,
                'is_paid'         => $item->is_paid,
                'date_creation'   => optional($commande?->created_at)->format('d/m/Y H:i'),
            ];
        })->values();

        return response()->json([
            'success' => true,
            'message' => 'Reclammation affichés avec succès.',
            'data' =>  $boutiques,
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Erreur lors de l’affichage des reclammations.',
            'erreur' => $e->getMessage(),
        ],500);
    }
}


// public function afficher_portefeuille(Request $request){
//     try{


//     }
//     catch(QueryException $e){

//     }
// }




public function marquerCommePayee(Request $request, $hashid)
{
    $admin = $request->user();

    // 🔒 Vérifier si c'est bien un super administrateur
    if ($admin->role !== 'super_admin') {
        return response()->json([
            'success' => false,
            'message' => 'Accès refusé. Vous devez être un super administrateur.'
        ], 403);
    }

    try {
        // 🔍 Décoder et récupérer l’ID
        $decoded = Hashids::decode($hashid);
        if (empty($decoded)) {
            return response()->json([
                'success' => false,
                'message' => 'ID de la reclamation invalide.'
            ], 400);
        }

        $id = $decoded[0];

        // 🧾 Trouver le portefeuille
        $portefeuille = Portefeuille::find($id);

        if (!$portefeuille) {
            return response()->json([
                'success' => false,
                'message' => 'Reclamation introuvable.'
            ], 404);
        }

        // 🚫 Vérifier s’il est déjà payé
        if ($portefeuille->is_paid) {
            return response()->json([
                'success' => false,
                'message' => 'Cette reclamation a déjà été marqué comme payé.'
            ], 400);
        }

        // ✅ Marquer comme payé
        $portefeuille->update([
            'is_paid' => true,
            'statut' => 'Payé',
            'updated_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'La reclammation a été marqué comme payé avec succès.',
            'data' => $portefeuille,
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Erreur lors de la mise à jour du paiement.',
            'erreur' => $e->getMessage(),
        ], 500);
    }
}


public function solde_boutique(Request $request)
{
    $boutique = $request->user();
    if (!$boutique) {
        return response()->json([
            'success' => false,
            'message' => 'Boutique non authentifiée.'
        ], 401);
    }

    // $decoded = Hashids::decode($hashid);
    // if (empty($decoded)) {
    //     return response()->json([
    //         'success' => false,
    //         'message' => 'ID de la boutique invalide.'
    //     ], 400);
    // }

    // $id_boutique = $decoded[0];

    try {
        // 💰 Somme totale des montants payés pour cette boutique
        $portefeuille = Portefeuille::where('id_beneficiaire', $boutique->id)
            ->where('role', 'boutique')
            ->where('is_paid', 1)
            ->sum('montant');

        return response()->json([
            'success' => true,
            'data' => $portefeuille,
            'message' => 'Solde affiché avec succès.'
        ], 200);

    } catch (QueryException $e) {
        return response()->json([
            'success' => false,
            'message' => 'Erreur survenue lors de l’affichage du solde.',
            'erreur' => $e->getMessage()
        ], 500);
    }
}





}