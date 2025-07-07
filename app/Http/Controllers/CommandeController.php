<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Commande;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Vinkla\Hashids\Facades\Hashids;

class CommandeController extends Controller
{
    public function commande_ajout(Request $request)
    {
        try {
            // Décoder les Hashids AVANT la validation
            $decoded = [
                'id_clt' => Hashids::decode($request->id_clt)[0] ?? null,
                'id_btq' => Hashids::decode($request->id_btq)[0] ?? null,
                'id_article' => Hashids::decode($request->id_article)[0] ?? null,
                'id_commune' => Hashids::decode($request->id_commune)[0] ?? null,
                'quantite' => $request->quantite,
                'statut' => $request->statut,
                'quartier' => $request->quartier,
            ];

            // Si un ID est invalide (non décodable), retourner une erreur explicite
            if (!($decoded['id_clt'] && $decoded['id_btq'] && $decoded['id_article'] && $decoded['id_commune'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Un ou plusieurs identifiants sont invalides (Hashids non décodables).',
                ], 400);
            }

            // Valider les données décodées
            $validated = validator($decoded, [
                'id_clt' => 'required|exists:users,id',
                'id_btq' => 'required|exists:boutiques,id',
                'id_article' => 'required|exists:articles,id',
                'id_commune' => 'required|exists:communes,id',
                'quantite' => 'required|integer|min:1',
                'statut' => 'nullable|string',
                'quartier' => 'required|string|max:255',
            ])->validate();

            // Insertion ligne par ligne
            $commande = new Commande();
            $commande->id_clt = $validated['id_clt'];
            $commande->id_btq = $validated['id_btq'];
            $commande->id_article = $validated['id_article'];
            $commande->id_commune = $validated['id_commune'];
            $commande->quantite = $validated['quantite'];
            $commande->statut = $validated['statut'] ?? 'En attente';
            $commande->quartier = $validated['quartier'];
            $commande->save();
            $commande->load('client', 'boutique', 'article', 'commune.ville');

            return response()->json([
                'success' => true,
                'message' => 'Commande ajoutée avec succès.',
                'data' => [
                    'quantite' => $commande->quantite,
                    'statut' => $commande->statut,
                    'created_at' => $commande->created_at,
                    'hashid' => Hashids::encode($commande->id),
                    'client' => [
                        'nom' => $commande->client->nom_clt,
                        'hashid' => Hashids::encode($commande->client->id),
                    ],
                    'boutique' => [
                        'nom' => $commande->boutique->nom_btq,
                        'hashid' => Hashids::encode($commande->boutique->id),
                    ],
                    'article' => [
                        'nom' => $commande->article->nom_article,
                        'prix' => $commande->article->prix,
                        'image' => $commande->article->image,
                        'description' => $commande->article->description,
                        'hashid' => Hashids::encode($commande->article->id),
                    ],
                    'commune' => [
                        'nom' => $commande->commune->lib_commune,
                        'hashid' => Hashids::encode($commande->commune->id),
                        'ville' => [
                            'nom' => $commande->commune->ville->lib_ville,
                            'hashid' => Hashids::encode($commande->commune->ville->id),
                        ]
                        ],
                        'quartier' => $commande->quartier,
                ]
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides. Veuillez vérifier les champs du formulaire.',
                'erreur' => $e->errors()
            ], 422);

        } catch (QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l’ajout de la commande. Problème de base de données.',
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

    public function liste_commande(){
        try {
            $commandes = Commande::with([
                'client',
                'boutique',
                'article',
                'commune.ville'
            ])->get();

        $result = $commandes->map(function ($commande) {
            return [
                'quantite' => $commande->quantite,
                'statut' => $commande->statut,
                'created_at' => $commande->created_at,
                'hashid' => Hashids::encode($commande->id),
                'client' => [
                    'nom' => $commande->client->nom_clt,
                    'hashid' => Hashids::encode($commande->client->id),
                ],
                'boutique' => [
                    'nom' => $commande->boutique->nom_btq,
                    'hashid' => Hashids::encode($commande->boutique->id),
                ],
                'article' => [
                    'nom' => $commande->article->nom_article,
                    'prix' => $commande->article->prix,
                    'image' => $commande->article->image,
                    'description' => $commande->article->description,
                    'hashid' => Hashids::encode($commande->article->id),
                ],
                'commune' => [
                    'nom' => $commande->commune->lib_commune,
                    'hashid' => Hashids::encode($commande->commune->id),
                    'ville' => [
                        'nom' => $commande->commune->ville->lib_ville,
                        'hashid' => Hashids::encode($commande->commune->ville->id),
                    ],
                ],
                'quartier' => $commande->quartier,
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Liste des commandes récupérées avec succès.',
            'data' => $result,
        ], 200);

        } catch (QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de base de données lors de la récupération des commandes.',
                'erreur' => config('app.debug') ? $e->getMessage() : null,
            ], 500);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Une erreur inattendue est survenue.',
                'erreur' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function commande($hashid){
        try {
            // Décoder l'id
            $id = Hashids::decode($hashid)[0] ?? null;

            if (!$id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Identifiant invalide.',
                ], 400);
            }

            $commande = Commande::with(['client', 'boutique', 'article', 'commune.ville'])->find($id);

            if (!$commande) {
                return response()->json([
                    'success' => false,
                    'message' => 'Commande non trouvée.',
                ], 404);
            }

            // Formater la réponse
            return response()->json([
                'success' => true,
                'message' => 'Commande récupérée avec succès.',
                'data' => [
                    'quantite' => $commande->quantite,
                    'statut' => $commande->statut,
                    'created_at' => $commande->created_at,
                    'hashid' => $hashid,
                    'client' => [
                        'nom' => $commande->client->nom_clt,
                        'hashid' => Hashids::encode($commande->client->id),
                    ],
                    'boutique' => [
                        'nom' => $commande->boutique->nom_btq,
                        'hashid' => Hashids::encode($commande->boutique->id),
                    ],
                    'article' => [
                        'nom' => $commande->article->nom_article,
                        'prix' => $commande->article->prix,
                        'image' => $commande->article->image,
                        'description' => $commande->article->description,
                        'hashid' => Hashids::encode($commande->article->id),
                    ],
                    'commune' => [
                        'nom' => $commande->commune->lib_commune,
                        'hashid' => Hashids::encode($commande->commune->id),
                        'ville' => [
                            'nom' => $commande->commune->ville->lib_ville,
                            'hashid' => Hashids::encode($commande->commune->ville->id),
                        ],
                    ],
                    'quartier' => $commande->quartier,
                ],
            ], 200);

        } catch (QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de base de données lors de la récupération de la commande.',
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

            $commande = Commande::find($id);
            if (!$commande) {
                return response()->json([
                    'success' => false,
                    'message' => 'Commande non trouvée.',
                ], 404);
            }

            $commande->statut = $statut;
            $commande->save();

            return response()->json([
                'success' => true,
                'message' => "Statut mis à jour en '$statut' avec succès.",
                'data' => [
                    'hashid' => $hashid,
                    'statut' => $statut,
                ],
            ]);

        } catch (QueryException $e) {
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
        // Sélection des 10 articles les plus commandés (sans paginate)
        $articles = Article::select(
                'articles.id',
                'articles.nom_article',
                'articles.prix',
                'articles.description',
                'articles.image',
                DB::raw('SUM(commandes.quantite) as total_commandes')
            )
            ->join('commandes', 'commandes.id_article', '=', 'articles.id')
            ->groupBy('articles.id', 'articles.nom_article', 'articles.prix', 'articles.description', 'articles.image')
            ->orderByDesc('total_commandes')
            ->limit(10)
            ->get();

        if ($articles->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun article commandé trouvé.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Top 10 des articles les plus commandés.',
            'data' => $articles,
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
            // Récupère 3 articles aléatoires
            $articles = Article::inRandomOrder()
                ->limit(10)
                ->get(['id', 'nom_article', 'prix', 'description', 'image']);

            if ($articles->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucun article disponible pour la recommandation.',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Articles recommandés récupérés avec succès.',
                'data' => $articles,
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
