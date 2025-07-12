<?php

namespace App\Http\Controllers;

use App\Models\Variation;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;
use Vinkla\Hashids\Facades\Hashids;

class VariationController extends Controller
{
    public function ajout_variation(Request $request)
{
    try {
        $validated = $request->validate([
            'nom_variation' => 'required|string|in:taille,color,matiere,longueur',
            'lib_variation' => 'required|array|min:1',
        ], [
            'nom_variation.required' => 'Le nom de la variation est obligatoire.',
            'nom_variation.in' => 'Le nom de la variation doit être "taille", "color", "matiere" ou "longueur".',
            'lib_variation.required' => 'Les valeurs de la variation sont requises.',
            'lib_variation.array' => 'Les valeurs doivent être un tableau.',
            'lib_variation.min' => 'Il faut au moins une valeur pour la variation.',
        ]);

        $variation = new Variation();
        $variation->nom_variation = $validated['nom_variation'];
        $variation->lib_variation = $validated['lib_variation'];
        $variation->id_btq = auth('boutique')->id();
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
        $variations = Variation::all();

        if ($variations->isNotEmpty()) {
            return response()->json([
                'success' => true,
                'data' => $variations,
                'message' => 'Variations récupérées.'
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Aucune variation trouvée pour cette boutique.'
            ]);
        }
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
    $request->validate([
        'nom_variation' => 'required|string|in:taille,color,matiere,longueur',
        'lib_variation' => 'required|array|min:1',
    ], [
        'nom_variation.required' => 'Le nom de la variation est obligatoire.',
        'lib_variation.required' => 'Les valeurs de la variation sont requises.',
        'lib_variation.array' => 'Les valeurs doivent être un tableau.',
    ]);

    $id = Hashids::decode($hashid)[0] ?? null;

    if (!$id) {
        return response()->json(['message' => 'ID invalide'], 400);
    }

    $variation = Variation::find($id);

    if (!$variation || $variation->id_btq !== auth('boutique')->id()) {
        return response()->json([
            'success' => false,
            'message' => 'Variation non trouvée ou non autorisé.'
        ]);
    }

    try {
        $variation->nom_variation = $request->nom_variation;
        $variation->lib_variation = $request->lib_variation;
        $variation->save();

        return response()->json([
            'success' => true,
            'data' => $variation,
            'message' => 'Variation mise à jour.'
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
