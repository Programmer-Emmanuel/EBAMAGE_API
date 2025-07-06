<?php

namespace App\Http\Controllers;
use Vinkla\Hashids\Facades\Hashids;
use App\Models\Categorie;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

class CategorieController extends Controller
{

public function ajout_categorie(Request $request)
{
    $request->validate([
        'nom_categorie' => 'required'
    ], [
        'nom_categorie.required' => 'Le nom de la catégorie est obligatoire.',
    ]);

    try {
        $categorie = new Categorie();
        $categorie->nom_categorie = $request->nom_categorie;
        $categorie->save();

        return response()->json([
            'success' => true,
            'data' => $categorie,
            'message' => 'Catégorie ajoutée avec succès.'
        ]);
    } catch (QueryException $e) {
        return response()->json([
            'success' => false,
            'message' => 'Erreur lors de l’enregistrement de la catégorie.',
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


    public function liste_categorie()
{
    try {
        $categories = Categorie::all();

        if ($categories->isNotEmpty()) {
            return response()->json([
                'success' => true,
                'data' => $categories,
                'message' => 'Catégories récupérées.'
            ]);
        } else {
            return response()->json([
                'success' => true,
                'data' => $categories,
                'message' => 'Aucune catégorie trouvée.'
            ]);
        }
    } catch (QueryException $e) {
        return response()->json([
            'success' => false,
            'message' => 'Erreur lors de la récupération des catégories.',
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


    public function categorie($hashid)
{
    try {
        $id = Hashids::decode($hashid)[0] ?? null;

        if (!$id) {
            return response()->json(['message' => 'ID invalide'], 400);
        }

        $categorie = Categorie::find($id);

        if (!$categorie) {
            return response()->json([
                'success' => false,
                'message' => 'Catégorie non trouvée.'
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $categorie,
            'message' => 'Catégorie trouvée.'
        ]);
    } catch (QueryException $e) {
        return response()->json([
            'success' => false,
            'message' => 'Erreur lors de la récupération de la catégorie.',
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

    public function update_categorie(Request $request, $hashid)
{
    $request->validate([
        'nom_categorie' => 'required|min:5'
    ], [
        'nom_categorie.required' => 'Le nom de la catégorie est obligatoire.',
        'nom_categorie.min' => 'Le nom de la catégorie doit avoir au minimum 5 caractères.'
    ]);

    $id = Hashids::decode($hashid)[0] ?? null;

    if (!$id) {
        return response()->json(['message' => 'ID invalide'], 400);
    }

    $categorie = Categorie::find($id);

    if (!$categorie) {
        return response()->json([
            'success' => false,
            'message' => 'Catégorie non trouvée.'
        ]);
    }

    try {
        $categorie->nom_categorie = $request->nom_categorie;
        $categorie->save();

        return response()->json([
            'success' => true,
            'data' => $categorie,
            'message' => 'Catégorie mise à jour.'
        ]);
    } catch (QueryException $e) {
        return response()->json([
            'success' => false,
            'message' => 'Échec de la mise à jour de la catégorie.',
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


    public function delete_categorie(Request $request, $hashid)
{
    try {
        $id = Hashids::decode($hashid)[0] ?? null;

        if (!$id) {
            return response()->json(['message' => 'ID invalide'], 400);
        }

        $categorie = Categorie::find($id);

        if (!$categorie) {
            return response()->json([
                'success' => false,
                'message' => 'Catégorie non trouvée.'
            ]);
        }

        $categorie->delete();

        return response()->json([
            'success' => true,
            'message' => 'Catégorie supprimée avec succès.'
        ]);
    } catch (QueryException $e) {
        return response()->json([
            'success' => false,
            'message' => 'Erreur lors de la suppression de la catégorie.',
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


}
