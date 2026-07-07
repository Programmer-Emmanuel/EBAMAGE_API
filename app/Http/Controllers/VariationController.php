<?php

namespace App\Http\Controllers;

use App\Models\Variation;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Vinkla\Hashids\Facades\Hashids;

class VariationController extends Controller
{
public function ajout_variation(Request $request)
{
    try {
        $validated = $request->validate([
            'nom_variation' => 'required|string|max:255',
            'lib_variation' => 'nullable|array',
            'lib_variation.*' => 'required|string|max:255',
        ], [
            'nom_variation.required' => 'Le nom de la variation est obligatoire.',
            'lib_variation.array' => 'Les libellés doivent être un tableau.',
            'lib_variation.*.required' => 'Chaque libellé est obligatoire.',
            'lib_variation.*.string' => 'Chaque libellé doit être une chaîne de caractères.',
        ]);

        $variation = new Variation();
        $variation->nom_variation = $validated['nom_variation'];

        // Si des libellés sont envoyés, on les enregistre sans doublons
        $variation->lib_variation = isset($validated['lib_variation'])
            ? array_values(array_unique($validated['lib_variation']))
            : [];

        $variation->id_btq = auth('boutique')->check()
            ? auth('boutique')->id()
            : null;

        $variation->save();

        return response()->json([
            'success' => true,
            'data' => [
                'hashid' => Hashids::encode($variation->id),
                'nom_variation' => $variation->nom_variation,
                'lib_variation' => $variation->lib_variation,
            ],
            'message' => 'Variation ajoutée avec succès.'
        ]);

    } catch (ValidationException $e) {
        return response()->json([
            'success' => false,
            'message' => 'Erreur de validation.',
            'errors' => $e->errors()
        ], 422);

    } catch (QueryException $e) {
        return response()->json([
            'success' => false,
            'message' => 'Erreur lors de l’enregistrement en base de données.',
            'error' => $e->getMessage()
        ], 500);

    } catch (\Throwable $e) {
        return response()->json([
            'success' => false,
            'message' => 'Une erreur inattendue est survenue.',
            'error' => $e->getMessage()
        ], 500);
    }
}

//     public function update_variation_admin(Request $request, $hashid)
// {
//     try {
//         $id = Hashids::decode($hashid)[0] ?? null;
//         // Vérifier si la variation existe
//         $variation = Variation::find($id);

//         if (!$variation) {
//             return response()->json([
//                 'success' => false,
//                 'message' => 'Variation introuvable.'
//             ], 404);
//         }

//         // Validation
//         $validated = $request->validate([
//             'nom_variation' => 'required|string',
//         ], [
//             'nom_variation.required' => 'Le nom de la variation est obligatoire.',
//         ]);


//         // Mise à jour
//         $variation->nom_variation = $validated['nom_variation'];
//         $variation->save();

//         return response()->json([
//             'success' => true,
//             'data' => $variation,
//             'message' => 'Variation mise à jour avec succès.'
//         ], 200);

//     } catch (ValidationException $e) {
//         return response()->json([
//             'success' => false,
//             'message' => 'Erreur de validation.',
//             'erreur' => $e->errors()
//         ], 422);

//     } catch (QueryException $e) {
//         return response()->json([
//             'success' => false,
//             'message' => 'Erreur lors de la mise à jour en base de données.',
//             'erreur' => $e->getMessage()
//         ], 500);

//     } catch (\Exception $e) {
//         return response()->json([
//             'success' => false,
//             'message' => 'Une erreur inattendue est survenue.',
//             'erreur' => $e->getMessage()
//         ], 500);
//     }
// }


    //  public function ajouterLibelles(Request $request)
    // {
    //     $validator = Validator::make($request->all(), [
    //         'variation_id' => 'required|string',
    //         'lib_variation' => 'required|array|min:1',
    //         'lib_variation.*' => 'required|string|max:255',
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Données invalides.',
    //             'errors' => $validator->errors()
    //         ], 422);
    //     }

    //     $boutique = $request->user();
    //     if(!$boutique){
    //         return response()->json([
    //             'success' => true,
    //             'message' => 'Boutique non trouvé'
    //         ],404);
    //     }

    //     // Décodage de l'ID
    //     $idVariation = Hashids::decode($request->variation_id)[0] ?? null;
    //     if (!$idVariation) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => "ID de variation invalide."
    //         ], 400);
    //     }

    //     $variation = Variation::find($idVariation);
    //     if (!$variation) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => "Variation non trouvée."
    //         ], 404);
    //     }

    //     // Récupérer les anciens libellés et ajouter les nouveaux
    //     $anciensLibelles = is_array($variation->lib_variation) ? $variation->lib_variation : [];
    //     $nouveauxLibelles = $request->lib_variation;

    //     // Fusion sans doublons
    //     $new_variation = Variation::where('id', $idVariation)->first();
    //     $variation = new Variation();
    //     $variation->nom_variation = $new_variation->nom_variation;
    //     $variation->lib_variation = array_values(array_unique(array_merge($anciensLibelles, $nouveauxLibelles)));
    //     $variation->id_btq = $boutique->id;
    //     $variation->save();

    //     return response()->json([
    //         'success' => true,
    //         'hashid' => Hashids::encode($variation->id),
    //         'nom_variation' => $variation->nom_variation,
    //         'lib_variation' => $variation->lib_variation,
    //         'message' => "Libellés ajoutés avec succès."
    //     ]);
    // }



    public function liste_variation()
{
    try {
        $idBoutique = auth('boutique')->id();

        // Récupérer toutes les variations de la boutique connectée
        $variations = Variation::where('id_btq', null)->get();

        if ($variations->isEmpty()) {
            return response()->json([
                'success' => true,
                "data" => [],
                'message' => 'Aucune variation trouvée pour cette boutique.'
            ],200);
        }

        // Comptage pour suffixes
        $countVariations = [];

        // Nouvelle liste avec noms modifiés si besoin
        $variationsModifiees = $variations->map(function ($variation) use (&$countVariations) {
            $baseName = strtolower($variation->nom_variation);
            $countVariations[$baseName] = ($countVariations[$baseName] ?? 0) + 1;
            $suffix = $countVariations[$baseName] > 1 ? ' ' . $countVariations[$baseName] : '';

            // Modifier le nom_variation avec suffixe s'il y a plusieurs
            $variation->nom_variation = $baseName . $suffix;

            // Assure que lib_variation est bien un tableau (casté en JSON dans le modèle ?)
            if (is_string($variation->lib_variation)) {
                $variation->lib_variation = json_decode($variation->lib_variation, true) ?? [];
            }

            return $variation;
        });

        return response()->json([
            'success' => true,
            'data' => $variationsModifiees,
            'message' => 'Variations récupérées.'
        ]);
    } catch (QueryException $e) {
        return response()->json([
            'success' => false,
            'message' => 'Erreur lors de la récupération des variations.',
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

public function liste_variation_sans_lib()
{
    try {
        // Récupère toutes les variations
        $variations = Variation::all();

        if ($variations->isEmpty()) {
            return response()->json([
                'success' => true,
                "data" => [],
                'message' => 'Aucune variation trouvée.'
            ],200);
        }

        // Filtrer uniquement celles sans lib_variation
        $variationsSansLib = $variations->filter(function ($variation) {
            $lib = is_string($variation->lib_variation)
                ? json_decode($variation->lib_variation, true)
                : $variation->lib_variation;

            return empty($lib) || (is_array($lib) && count($lib) === 0);
        })->values();

        if ($variationsSansLib->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Toutes les variations ont des libellés.'
            ]);
        }

        // Mapper le résultat pour ne garder que hashid + nom_variation
        $data = $variationsSansLib->map(function ($variation) {
            return [
                'hashid' => Hashids::encode($variation->id),
                'nom_variation' => $variation->nom_variation,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
            'message' => 'Variations sans lib_variation récupérées avec succès.'
        ]);

    } catch (QueryException $e) {
        return response()->json([
            'success' => false,
            'message' => 'Erreur lors de la récupération des variations.',
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




public function variation($hashid)
{
    $id = Hashids::decode($hashid)[0] ?? null;

    if (!$id) {
        return response()->json(['message' => 'ID invalide'], 400);
    }

    try {
        $variation = Variation::find($id);

        if (!$variation) {
            return response()->json([
                'success' => false,
                'message' => 'Variation non trouvée.'
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $variation,
            'message' => 'Variation trouvée.'
        ]);
    } catch (QueryException $e) {
        return response()->json([
            'success' => false,
            'message' => 'Erreur lors de la récupération de la variation.',
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

    public function update_variation(Request $request, $hashid)
{
    try {
        $validated = $request->validate([
            'nom_variation' => 'required|string|max:255',
            'lib_variation' => 'nullable|array',
            'lib_variation.*' => 'required|string|max:255',
        ], [
            'nom_variation.required' => 'Le nom de la variation est obligatoire.',
            'lib_variation.array' => 'Les libellés doivent être un tableau.',
            'lib_variation.*.required' => 'Chaque libellé est obligatoire.',
        ]);

        $boutique = $request->user();

        if (!$boutique) {
            return response()->json([
                'success' => false,
                'message' => 'Boutique non trouvée.'
            ], 404);
        }

        // Décodage du Hashid
        $idVariation = Hashids::decode($hashid)[0] ?? null;

        if (!$idVariation) {
            return response()->json([
                'success' => false,
                'message' => 'ID de variation invalide.'
            ], 400);
        }

        // Vérifier que la variation appartient à la boutique
        $variation = Variation::where('id', $idVariation)->first();

        if (!$variation) {
            return response()->json([
                'success' => false,
                'message' => 'Variation introuvable.'
            ], 404);
        }

        // Mise à jour
        $variation->nom_variation = $validated['nom_variation'];

        if (isset($validated['lib_variation'])) {
            $variation->lib_variation = array_values(
                array_unique($validated['lib_variation'])
            );
        }

        $variation->save();

        return response()->json([
            'success' => true,
            'message' => 'Variation mise à jour avec succès.',
            'data' => [
                'hashid' => Hashids::encode($variation->id),
                'nom_variation' => $variation->nom_variation,
                'lib_variation' => $variation->lib_variation,
            ]
        ]);

    } catch (ValidationException $e) {
        return response()->json([
            'success' => false,
            'message' => 'Erreur de validation.',
            'errors' => $e->errors()
        ], 422);

    } catch (QueryException $e) {
        return response()->json([
            'success' => false,
            'message' => 'Erreur lors de la mise à jour.',
            'error' => $e->getMessage()
        ], 500);

    } catch (\Throwable $e) {
        return response()->json([
            'success' => false,
            'message' => 'Une erreur inattendue est survenue.',
            'error' => $e->getMessage()
        ], 500);
    }
}



    public function delete_variation($hashid)
{
    $id = Hashids::decode($hashid)[0] ?? null;

    if (!$id) {
        return response()->json(['message' => 'ID invalide'], 400);
    }

    try {
        $variation = Variation::find($id);

        // if (!$variation || $variation->id_btq !== auth('boutique')->id()) {
        //     return response()->json([
        //         'success' => false,
        //         'message' => 'Variation non trouvée ou non autorisé.'
        //     ], 404);
        // }

        if($variation->nom_variation === 'color' && empty($variation->lib_variation)){
            return response()->json([
                'success' => false,
                'message' => 'Vous ne devez pas supprimé la variation color.'
            ],403);
        }

        $variation->delete();

        return response()->json([
            'success' => true,
            'message' => 'Variation supprimée avec succès.'
        ]);
    } catch (QueryException $e) {
        return response()->json([
            'success' => false,
            'message' => 'Erreur lors de la suppression de la variation.',
            'erreur' => $e->getMessage()
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Une erreur inattendue est survenue.',
            'erreur' => $e->getMessage()
        ]);
    }
}
}
