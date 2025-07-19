<?php

namespace App\Http\Controllers;

use App\Models\Variation;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;
use Vinkla\Hashids\Facades\Hashids;

class VariationController extends Controller
{
    public function ajout_variation(Request $request){
        try {
            $validated = $request->validate([
                'nom_variation' => 'required|string|in:taille,color,matiere,longueur',
            ], [
                'nom_variation.required' => 'Le nom de la variation est obligatoire.',
                'nom_variation.in' => 'Le nom de la variation doit être "taille", "color", "matiere" ou "longueur".',
            ]);

            $variation = new Variation();
            $variation->nom_variation = $validated['nom_variation'];
            $variation->lib_variation = []; // vide au départ
            $variation->id_btq = auth('boutique')->check() ? auth('boutique')->id() : null;

            $variation->save();

            return response()->json([
                'success' => true,
                'data' => $variation,
                'message' => 'Variation ajoutée avec succès.'
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation.',
                'erreur' => $e->errors()
            ], 422);

        } catch (QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l’enregistrement en base de données.',
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



    public function liste_variation()
{
    try {
        $idBoutique = auth('boutique')->id();

        // Récupérer toutes les variations de la boutique connectée
        $variations = Variation::where('id_btq', $idBoutique)->get();

        if ($variations->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Aucune variation trouvée pour cette boutique.'
            ]);
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

    public function update_variation(Request $request, $hashid){
        $id = Hashids::decode($hashid)[0] ?? null;

        if (!$id) {
            return response()->json(['message' => 'ID invalide'], 400);
        }

        $variation = Variation::find($id);

        // Vérifie que la variation appartient à la boutique connectée
        if (!$variation || $variation->id_btq !== auth('boutique')->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Variation non trouvée ou non autorisée.'
            ], 403);
        }

        // Validation conditionnelle
        $validated = $request->validate([
            'nom_variation' => 'nullable|string|in:taille,color,matiere,longueur',
            'lib_variation' => 'nullable|array',
        ], [
            'nom_variation.in' => 'Le nom de la variation doit être "taille", "color", "matiere" ou "longueur".',
            'lib_variation.array' => 'Les valeurs doivent être un tableau.',
        ]);

        try {
            if ($request->has('nom_variation')) {
                $variation->nom_variation = $validated['nom_variation'];
            }

            if ($request->has('lib_variation')) {
                $variation->lib_variation = $validated['lib_variation'];
            }

            $variation->save();

            return response()->json([
                'success' => true,
                'data' => $variation,
                'message' => 'Variation mise à jour avec succès.'
            ]);
        } catch (QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour.',
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



    public function delete_variation($hashid)
{
    $id = Hashids::decode($hashid)[0] ?? null;

    if (!$id) {
        return response()->json(['message' => 'ID invalide'], 400);
    }

    try {
        $variation = Variation::find($id);

        if (!$variation || $variation->id_btq !== auth('boutique')->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Variation non trouvée ou non autorisé.'
            ]);
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
