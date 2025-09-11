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
        $cities = [
            'Abidjan',
            'Bouaké',
            'Daloa',
            'Yamoussoukro',
            'San-Pédro',
            'Korhogo',
            'Man',
            'Gagnoa',
            'Soubré',
            'Abengourou',
            'Divo',
            'Anyama',
            'Bondoukou',
            'Agboville',
            'Séguéla',
            'Odienné',
            'Ferkessédougou',
            'Adzopé',
            'Grand-Bassam',
            'Aboisso',
            'Daoukro',
            'Toumodi',
            'Guiglo',
            'Tengréla',
            'Issia',
            'Bouna',
            'Sinfra',
            'Tabou',
            'Bingerville',
            'Dabou'
        ];

        try {
            foreach ($cities as $city) {
                Ville::firstOrCreate(['lib_ville' => $city]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Villes ajoutées avec succès.'
            ]);
        } catch (QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Échec lors de l’ajout des villes.',
                'erreur' => $e->getMessage()
            ]);
        }
    }

    public function liste_ville(){
        $villes = Ville::all();
        if($villes){
            return response()->json([
                'success' => true,
                'data' => $villes,
                'message' => 'Villes récupérés avec succès.'
            ]);
        }
        else{
            return response()->json([
                'success' => false,
                'message' => 'Echec lors de la récupérations des villes.'
            ]);
        }
    }

    public function ville($hashid){
        $id = Hashids::decode($hashid)[0] ?? null;

        if (!$id) {
            return response()->json(['message' => 'ID invalide'], 400);
        }

        try{
            $ville = Ville::where('id', $id)->first();
            return response()->json([
                'success' => true,
                'data' => $ville,
                'message' => 'Ville récupérée avec succès' 
            ]);
        }
        catch(QueryException $e){
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération de la ville.',
                'erreur' => $e->getMessage() 
            ]);
        }
    }

    public function ajout_commune(Request $request){
        $communesParVille = [
            'Abidjan' => ['Abobo', 'Adjamé', 'Attécoubé', 'Cocody', 'Koumassi', 'Marcory', 'Plateau', 'Port-Bouët', 'Treichville', 'Yopougon', 'Anyama', 'Bingerville', 'Songon'],
            'Yamoussoukro' => ['Yamoussoukro', 'Attiégouakro'],
            'Bouaké' => ['Bouaké', 'Belleville', 'Ahougnansou', 'Nimbo'],
            'San-Pédro' => ['San-Pédro', 'Séwéké', 'Bardot', 'Cité'],
            'Korhogo' => ['Korhogo'],
            'Daloa' => ['Daloa'],
            'Man' => ['Man'],
            'Gagnoa' => ['Gagnoa'],
            'Soubré' => ['Soubré'],
            'Abengourou' => ['Abengourou'],
            'Divo' => ['Divo'],
            'Bondoukou' => ['Bondoukou'],
            'Agboville' => ['Agboville'],
            'Séguéla' => ['Séguéla'],
            'Odienné' => ['Odienné'],
            'Ferkessédougou' => ['Ferkessédougou'],
            'Adzopé' => ['Adzopé'],
            'Grand-Bassam' => ['Grand-Bassam'],
            'Aboisso' => ['Aboisso'],
            'Daoukro' => ['Daoukro'],
            'Toumodi' => ['Toumodi'],
            'Guiglo' => ['Guiglo'],
            'Tengréla' => ['Tengréla'],
            'Issia' => ['Issia'],
            'Bouna' => ['Bouna'],
            'Sinfra' => ['Sinfra'],
            'Tabou' => ['Tabou'],
            'Bingerville' => ['Bingerville'],
            'Dabou' => ['Dabou'],
            // Vous pouvez ajouter d'autres villes et leurs communes ici
        ];

        try {
            foreach ($communesParVille as $nomVille => $communes) {
                $ville = Ville::where('lib_ville', $nomVille)->first();
                if (!$ville) {
                    return response()->json([
                        'success' => false,
                        'message' => "Ville non trouvée: $nomVille"
                    ]);
                }

                if ($ville) {
                    foreach ($communes as $nomCommune) {
                        Commune::firstOrCreate([
                            'lib_commune' => $nomCommune,
                            'id_ville' => $ville->id,
                        ]);
                    }
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Communes ajoutées avec succès.'
            ]);
        } 
        catch (QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l’ajout des communes.',
                'erreur' => $e->getMessage()
            ]);
        }

    }

    public function liste_commune(){
        try{
            $communes = Commune::with('ville')->get();

            // On retourne la réponse JSON
            return response()->json([
                'success' => true,
                'data' => $communes,
                'message' => 'Communes récupérées avec succès'
            ]);
        }
        catch(QueryException $e){
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des communes'
            ]);
        }
    }

public function communesParVille($lib_ville)
{
    // Recherche ville par nom exact (insensible à la casse ? selon besoin)
    $ville = Ville::where('lib_ville', $lib_ville)->first();

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

    return response()->json([
        'success' => true,
        'ville' => $ville->lib_ville,
        'data' => $communes,
    ]);
}


}
