<?php

namespace App\Http\Controllers;

use App\Models\Commune;
use App\Models\Ville;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Vinkla\Hashids\Facades\Hashids;

class VilleCommuneController extends Controller
{
    public function ajout_ville(Request $request){
        // Vérification des données envoyées
        $request->validate([
            'lib_ville' => 'required|string|max:255|unique:villes,lib_ville'
        ]);

        try {
            $ville = Ville::create([
                'lib_ville' => $request->lib_ville
            ]);

            // Ajouter le hashid à la réponse
            $villeData = $ville->toArray();
            $villeData['hashid'] = Hashids::encode($ville->id);

            return response()->json([
                'success' => true,
                'data' => $villeData,
                'message' => 'Ville ajoutée avec succès.'
            ]);
        } catch (QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Échec lors de l\'ajout de la ville.',
                'erreur' => $e->getMessage()
            ], 500);
        }
    }

    public function liste_ville(){
        $villes = Ville::all();
        if($villes->count() > 0){
            // Ajouter les hashids aux données de réponse
            $villesWithHashid = $villes->map(function($ville) {
                $villeData = $ville->toArray();
                $villeData['hashid'] = Hashids::encode($ville->id);
                return $villeData;
            });

            return response()->json([
                'success' => true,
                'data' => $villesWithHashid,
                'message' => 'Villes récupérées avec succès.'
            ]);
        }
        else{
            return response()->json([
                'success' => false,
                'message' => 'Aucune ville trouvée.'
            ], 404);
        }
    }

    public function ville($hashid){
        $id = Hashids::decode($hashid);
        
        if (empty($id)) {
            return response()->json([
                'success' => false,
                'message' => 'ID invalide'
            ], 400);
        }

        try{
            $ville = Ville::where('id', $id[0])->first();
            
            if($ville){
                // Ajouter le hashid à la réponse
                $villeData = $ville->toArray();
                $villeData['hashid'] = $hashid;

                return response()->json([
                    'success' => true,
                    'data' => $villeData,
                    'message' => 'Ville récupérée avec succès' 
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Ville non trouvée.'
                ], 404);
            }
        }
        catch(QueryException $e){
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération de la ville.',
                'erreur' => $e->getMessage() 
            ], 500);
        }
    }

    public function ajout_commune(Request $request){
        // Vérification des données envoyées
        $request->validate([
            'lib_commune' => 'required|string|max:255',
            'id_ville_hash' => 'required|string'
        ]);

        try {
            // Décoder le hashid de la ville
            $id_ville = Hashids::decode($request->id_ville_hash);
            
            if (empty($id_ville)) {
                return response()->json([
                    'success' => false,
                    'message' => 'ID de ville invalide.'
                ], 400);
            }

            // Vérifier si la ville existe
            $ville = Ville::where('id', $id_ville[0])->first();
            if (!$ville) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ville non trouvée.'
                ], 404);
            }

            // Vérifier si la commune existe déjà pour cette ville
            $communeExistante = Commune::where('lib_commune', $request->lib_commune)
                ->where('id_ville', $id_ville[0])
                ->first();

            if ($communeExistante) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cette commune existe déjà pour cette ville.'
                ], 409);
            }

            $commune = Commune::create([
                'lib_commune' => $request->lib_commune,
                'id_ville' => $id_ville[0],
            ]);

            // Charger la relation ville pour la réponse
            $commune->load('ville');
            
            // Préparer les données avec hashids
            $communeData = $commune->toArray();
            $communeData['hashid'] = Hashids::encode($commune->id); 
            
            if ($commune->ville) {
                $communeData['ville']['hashid'] = $request->id_ville_hash;
            }

            return response()->json([
                'success' => true,
                'data' => $communeData,
                'message' => 'Commune ajoutée avec succès.'
            ]);
        } 
        catch (QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'ajout de la commune.',
                'erreur' => $e->getMessage()
            ], 500);
        }
    }

    public function liste_commune(){
        try{
            $communes = Commune::with('ville')->get();

            if($communes->count() > 0){
                // Ajouter les hashids aux données de réponse
                $communesWithHashid = $communes->map(function($commune) {
                    $communeData = $commune->toArray();
                    $communeData['hashid'] = Hashids::encode($commune->id);
                    
                    if ($commune->ville) {
                        $communeData['ville']['hashid'] = Hashids::encode($commune->ville->id);
                    }
                    
                    return $communeData;
                });

                return response()->json([
                    'success' => true,
                    'data' => $communesWithHashid,
                    'message' => 'Communes récupérées avec succès'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucune commune trouvée.'
                ], 404);
            }
        }
        catch(QueryException $e){
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des communes',
                'erreur' => $e->getMessage()
            ], 500);
        }
    }

    public function commune($hashid){
        $id = Hashids::decode($hashid);
        
        if (empty($id)) {
            return response()->json([
                'success' => false,
                'message' => 'ID invalide'
            ], 400);
        }

        try{
            $commune = Commune::with('ville')->where('id', $id[0])->first();
            
            if($commune){
                // Ajouter les hashids aux données de réponse
                $communeData = $commune->toArray();
                $communeData['hashid'] = $hashid;
                
                if ($commune->ville) {
                    $communeData['ville']['hashid'] = Hashids::encode($commune->ville->id);
                }

                return response()->json([
                    'success' => true,
                    'data' => $communeData,
                    'message' => 'Commune récupérée avec succès' 
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Commune non trouvée.'
                ], 404);
            }
        }
        catch(QueryException $e){
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération de la commune.',
                'erreur' => $e->getMessage() 
            ], 500);
        }
    }

    public function communesParVille($ville_hashid)
    {
        // Décoder le hashid de la ville
        $id_ville = Hashids::decode($ville_hashid);
        
        if (empty($id_ville)) {
            return response()->json([
                'success' => false,
                'message' => 'ID de ville invalide'
            ], 400);
        }

        // Recherche ville par ID
        $ville = Ville::where('id', $id_ville[0])->first();

        if (!$ville) {
            return response()->json([
                'success' => false,
                'message' => "Ville non trouvée",
            ], 404);
        }

        // Récupérer les communes de cette ville
        $communes = Commune::where('id_ville', $ville->id)->get();

        if ($communes->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => "Aucune commune trouvée pour cette ville",
            ], 404);
        }

        // Ajouter les hashids aux données de réponse
        $villeData = $ville->toArray();
        $villeData['hashid'] = $ville_hashid;

        $communesWithHashid = $communes->map(function($commune) {
            $communeData = $commune->toArray();
            $communeData['hashid'] = Hashids::encode($commune->id);
            return $communeData;
        });

        return response()->json([
            'success' => true,
            'ville' => $ville->lib_ville,
            'hashid' => $ville->hashid,
            'data' => $communesWithHashid,
        ]);
    }

    public function communesParNomVille($lib_ville)
    {
        // Recherche ville par nom exact (insensible à la casse)
        $ville = Ville::where('lib_ville', 'like', $lib_ville)->first();

        if (!$ville) {
            return response()->json([
                'success' => false,
                'message' => "Ville '{$lib_ville}' non trouvée",
            ], 404);
        }

        // Récupérer les communes de cette ville
        $communes = Commune::where('id_ville', $ville->id)->get();

        if ($communes->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => "Aucune commune trouvée pour la ville '{$lib_ville}'",
            ], 404);
        }

        // Ajouter les hashids aux données de réponse
        $villeData = $ville->toArray();
        $villeData['hashid'] = Hashids::encode($ville->id);

        $communesWithHashid = $communes->map(function($commune) {
            $communeData = $commune->toArray();
            $communeData['hashid'] = Hashids::encode($commune->id);
            return $communeData;
        });

        return response()->json([
            'success' => true,
            'ville' => $ville->lib_ville,
            'hashid' => $ville->hashid,
            'data' => $communesWithHashid,
        ]);
    }


    // Modifier une ville
    public function modifier_ville(Request $request, $hashid)
    {
        // Décoder le hashid de la ville
        $id = Hashids::decode($hashid);
        
        if (empty($id)) {
            return response()->json([
                'success' => false,
                'message' => 'ID de ville invalide'
            ], 400);
        }

        // Vérification des données envoyées
        $request->validate([
            'lib_ville' => 'required|string|max:255|unique:villes,lib_ville,' . $id[0]
        ]);

        try {
            $ville = Ville::where('id', $id[0])->first();

            if (!$ville) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ville non trouvée.'
                ], 404);
            }

            $ville->update([
                'lib_ville' => $request->lib_ville
            ]);

            // Ajouter le hashid à la réponse
            $villeData = $ville->toArray();
            $villeData['hashid'] = $hashid;

            return response()->json([
                'success' => true,
                'data' => $villeData,
                'message' => 'Ville modifiée avec succès.'
            ]);

        } catch (QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Échec lors de la modification de la ville.',
                'erreur' => $e->getMessage()
            ], 500);
        }
    }

    // Supprimer une ville
    public function supprimer_ville($hashid)
    {
        // Décoder le hashid de la ville
        $id = Hashids::decode($hashid);
        
        if (empty($id)) {
            return response()->json([
                'success' => false,
                'message' => 'ID de ville invalide'
            ], 400);
        }

        try {
            $ville = Ville::where('id', $id[0])->first();

            if (!$ville) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ville non trouvée.'
                ], 404);
            }

            // Vérifier si la ville a des communes associées
            $communesCount = Commune::where('id_ville', $ville->id)->count();
            
            if ($communesCount > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Impossible de supprimer cette ville car elle contient des communes. Veuillez d\'abord supprimer les communes associées.'
                ], 409);
            }

            $ville->delete();

            return response()->json([
                'success' => true,
                'message' => 'Ville supprimée avec succès.'
            ]);

        } catch (QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Échec lors de la suppression de la ville.',
                'erreur' => $e->getMessage()
            ], 500);
        }
    }

    // Modifier une commune
    public function modifier_commune(Request $request, $hashid)
    {
        // Décoder le hashid de la commune
        $id = Hashids::decode($hashid);
        
        if (empty($id)) {
            return response()->json([
                'success' => false,
                'message' => 'ID de commune invalide'
            ], 400);
        }

        // Vérification des données envoyées
        $request->validate([
            'lib_commune' => 'required|string|max:255',
            'id_ville_hash' => 'required|string'
        ]);

        try {
            $commune = Commune::where('id', $id[0])->first();

            if (!$commune) {
                return response()->json([
                    'success' => false,
                    'message' => 'Commune non trouvée.'
                ], 404);
            }

            // Décoder le hashid de la ville
            $id_ville = Hashids::decode($request->id_ville_hash);
            
            if (empty($id_ville)) {
                return response()->json([
                    'success' => false,
                    'message' => 'ID de ville invalide.'
                ], 400);
            }

            // Vérifier si la ville existe
            $ville = Ville::where('id', $id_ville[0])->first();
            if (!$ville) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ville non trouvée.'
                ], 404);
            }

            // Vérifier si une autre commune avec le même nom existe déjà dans cette ville
            $communeExistante = Commune::where('lib_commune', $request->lib_commune)
                ->where('id_ville', $id_ville[0])
                ->where('id', '!=', $id[0])
                ->first();

            if ($communeExistante) {
                return response()->json([
                    'success' => false,
                    'message' => 'Une commune avec ce nom existe déjà dans cette ville.'
                ], 409);
            }

            $commune->update([
                'lib_commune' => $request->lib_commune,
                'id_ville' => $id_ville[0],
            ]);

            // Charger la relation ville pour la réponse
            $commune->load('ville');
            
            // Préparer les données avec hashids
            $communeData = $commune->toArray();
            $communeData['hashid'] = $hashid;
            
            if ($commune->ville) {
                $communeData['ville']['hashid'] = $request->id_ville_hash;
            }

            return response()->json([
                'success' => true,
                'data' => $communeData,
                'message' => 'Commune modifiée avec succès.'
            ]);

        } catch (QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la modification de la commune.',
                'erreur' => $e->getMessage()
            ], 500);
        }
    }

    // Supprimer une commune
    public function supprimer_commune($hashid)
    {
        // Décoder le hashid de la commune
        $id = Hashids::decode($hashid);
        
        if (empty($id)) {
            return response()->json([
                'success' => false,
                'message' => 'ID de commune invalide'
            ], 400);
        }

        try {
            $commune = Commune::where('id', $id[0])->first();

            if (!$commune) {
                return response()->json([
                    'success' => false,
                    'message' => 'Commune non trouvée.'
                ], 404);
            }

            $commune->delete();

            return response()->json([
                'success' => true,
                'message' => 'Commune supprimée avec succès.'
            ]);

        } catch (QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Échec lors de la suppression de la commune.',
                'erreur' => $e->getMessage()
            ], 500);
        }
    }
}








// -------------------------------------------------------------
//                                                              |
// ANCIEN CODE AVEC AJOUT AUTOMATIQUE DES VILLES ET COMMUNES    |
//                                                              |       
// -------------------------------------------------------------
// <?php

// namespace App\Http\Controllers;

// use App\Models\Commune;
// use App\Models\Ville;
// use Illuminate\Database\QueryException;
// use Illuminate\Http\Request;
// use Vinkla\Hashids\Facades\Hashids;

// class VilleCommuneController extends Controller
// {
//     public function ajout_ville(Request $request){
//         $cities = [
//             'Abidjan',
//             'Bouaké',
//             'Daloa',
//             'Yamoussoukro',
//             'San-Pédro',
//             'Korhogo',
//             'Man',
//             'Gagnoa',
//             'Soubré',
//             'Abengourou',
//             'Divo',
//             'Anyama',
//             'Bondoukou',
//             'Agboville',
//             'Séguéla',
//             'Odienné',
//             'Ferkessédougou',
//             'Adzopé',
//             'Grand-Bassam',
//             'Aboisso',
//             'Daoukro',
//             'Toumodi',
//             'Guiglo',
//             'Tengréla',
//             'Issia',
//             'Bouna',
//             'Sinfra',
//             'Tabou',
//             'Bingerville',
//             'Dabou'
//         ];

//         try {
//             foreach ($cities as $city) {
//                 Ville::firstOrCreate(['lib_ville' => $city]);
//             }

//             return response()->json([
//                 'success' => true,
//                 'message' => 'Villes ajoutées avec succès.'
//             ]);
//         } catch (QueryException $e) {
//             return response()->json([
//                 'success' => false,
//                 'message' => 'Échec lors de l’ajout des villes.',
//                 'erreur' => $e->getMessage()
//             ]);
//         }
//     }

//     public function liste_ville(){
//         $villes = Ville::all();
//         if($villes){
//             return response()->json([
//                 'success' => true,
//                 'data' => $villes,
//                 'message' => 'Villes récupérés avec succès.'
//             ]);
//         }
//         else{
//             return response()->json([
//                 'success' => false,
//                 'message' => 'Echec lors de la récupérations des villes.'
//             ]);
//         }
//     }

//     public function ville($hashid){
//         $id = Hashids::decode($hashid)[0] ?? null;

//         if (!$id) {
//             return response()->json(['message' => 'ID invalide'], 400);
//         }

//         try{
//             $ville = Ville::where('id', $id)->first();
//             return response()->json([
//                 'success' => true,
//                 'data' => $ville,
//                 'message' => 'Ville récupérée avec succès' 
//             ]);
//         }
//         catch(QueryException $e){
//             return response()->json([
//                 'success' => false,
//                 'message' => 'Erreur lors de la récupération de la ville.',
//                 'erreur' => $e->getMessage() 
//             ]);
//         }
//     }

//     public function ajout_commune(Request $request){
//         $communesParVille = [
//             'Abidjan' => ['Abobo', 'Adjamé', 'Attécoubé', 'Cocody', 'Koumassi', 'Marcory', 'Plateau', 'Port-Bouët', 'Treichville', 'Yopougon', 'Anyama', 'Bingerville', 'Songon'],
//             'Yamoussoukro' => ['Yamoussoukro', 'Attiégouakro'],
//             'Bouaké' => ['Bouaké', 'Belleville', 'Ahougnansou', 'Nimbo'],
//             'San-Pédro' => ['San-Pédro', 'Séwéké', 'Bardot', 'Cité'],
//             'Korhogo' => ['Korhogo'],
//             'Daloa' => ['Daloa'],
//             'Man' => ['Man'],
//             'Gagnoa' => ['Gagnoa'],
//             'Soubré' => ['Soubré'],
//             'Abengourou' => ['Abengourou'],
//             'Divo' => ['Divo'],
//             'Bondoukou' => ['Bondoukou'],
//             'Agboville' => ['Agboville'],
//             'Séguéla' => ['Séguéla'],
//             'Odienné' => ['Odienné'],
//             'Ferkessédougou' => ['Ferkessédougou'],
//             'Adzopé' => ['Adzopé'],
//             'Grand-Bassam' => ['Grand-Bassam'],
//             'Aboisso' => ['Aboisso'],
//             'Daoukro' => ['Daoukro'],
//             'Toumodi' => ['Toumodi'],
//             'Guiglo' => ['Guiglo'],
//             'Tengréla' => ['Tengréla'],
//             'Issia' => ['Issia'],
//             'Bouna' => ['Bouna'],
//             'Sinfra' => ['Sinfra'],
//             'Tabou' => ['Tabou'],
//             'Bingerville' => ['Bingerville'],
//             'Dabou' => ['Dabou'],
//             // Vous pouvez ajouter d'autres villes et leurs communes ici
//         ];

//         try {
//             foreach ($communesParVille as $nomVille => $communes) {
//                 $ville = Ville::where('lib_ville', $nomVille)->first();
//                 if (!$ville) {
//                     return response()->json([
//                         'success' => false,
//                         'message' => "Ville non trouvée: $nomVille"
//                     ]);
//                 }

//                 if ($ville) {
//                     foreach ($communes as $nomCommune) {
//                         Commune::firstOrCreate([
//                             'lib_commune' => $nomCommune,
//                             'id_ville' => $ville->id,
//                         ]);
//                     }
//                 }
//             }

//             return response()->json([
//                 'success' => true,
//                 'message' => 'Communes ajoutées avec succès.'
//             ]);
//         } 
//         catch (QueryException $e) {
//             return response()->json([
//                 'success' => false,
//                 'message' => 'Erreur lors de l’ajout des communes.',
//                 'erreur' => $e->getMessage()
//             ]);
//         }

//     }

//     public function liste_commune(){
//         try{
//             $communes = Commune::with('ville')->get();

//             // On retourne la réponse JSON
//             return response()->json([
//                 'success' => true,
//                 'data' => $communes,
//                 'message' => 'Communes récupérées avec succès'
//             ]);
//         }
//         catch(QueryException $e){
//             return response()->json([
//                 'success' => false,
//                 'message' => 'Erreur lors de la récupération des communes'
//             ]);
//         }
//     }

// public function communesParVille($lib_ville)
// {
//     // Recherche ville par nom exact (insensible à la casse ? selon besoin)
//     $ville = Ville::where('lib_ville', $lib_ville)->first();

//     if (!$ville) {
//         return response()->json([
//             'success' => false,
//             'message' => "Ville '{$lib_ville}' non trouvée",
//         ], 404);
//     }

//     // Récupérer les communes de cette ville
//     $communes = Commune::where('id_ville', $ville->id)->get();

//     if ($communes->isEmpty()) {
//         return response()->json([
//             'success' => false,
//             'message' => "Aucune commune trouvée pour la ville '{$lib_ville}'",
//         ], 404);
//     }

//     return response()->json([
//         'success' => true,
//         'ville' => $ville->lib_ville,
//         'data' => $communes,
//     ]);
// }


// }