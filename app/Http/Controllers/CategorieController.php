<?php

namespace App\Http\Controllers;
use Vinkla\Hashids\Facades\Hashids;
use App\Models\Categorie;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class CategorieController extends Controller
{

    public function ajout_categorie(Request $request){
        $request->validate([
            'nom_categorie' => 'required|string|max:255',
            'image_categorie' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ], [
            'nom_categorie.required' => 'Le nom de la catégorie est obligatoire.',
            'image_categorie.required' => "L'image est obligatoire.",
            'image_categorie.image' => "Le fichier doit être une image.",
            'image_categorie.mimes' => "L'image doit être au format jpeg, png, jpg ou gif.",
            'image_categorie.max' => "L'image ne doit pas dépasser 2 Mo.",
        ]);

        try {
            // Upload image sur imgbb
            $imageUrl = $this->uploadImageToHosting($request->file('image_categorie'));

            $categorie = new Categorie();
            $categorie->nom_categorie = $request->nom_categorie;
            $categorie->image_categorie = $imageUrl;
            $categorie->save();

            return response()->json([
                'success' => true,
                'message' => 'Catégorie ajoutée avec succès.',
                'data' => $categorie
            ], 201);

        } catch (QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l’enregistrement de la catégorie.',
                'erreur' => config('app.debug') ? $e->getMessage() : null
            ], 500);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Une erreur inattendue est survenue.',
                'erreur' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

 //METTRE LES IMAGES SUR LE SITE "imgbb.com"
    private function uploadImageToHosting($image)
{
    $apiKey = '9b1ab6564d99aab6418ad53d3451850b';

    if (!$image->isValid()) {
        throw new \Exception("Fichier image non valide.");
    }

    $imageContent = base64_encode(file_get_contents($image->getRealPath()));

    $response = Http::asForm()->post('https://api.imgbb.com/1/upload', [
        'key' => $apiKey,
        'image' => $imageContent,
    ]);

    if ($response->successful()) {
        return $response->json()['data']['url'];
    }

    throw new \Exception("Erreur lors de l'envoi de l'image : " . $response->body());
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

    public function update_categorie(Request $request, $hashid){
        $request->validate([
            'nom_categorie' => 'required|min:5',
            'image_categorie' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ], [
            'nom_categorie.required' => 'Le nom de la catégorie est obligatoire.',
            'nom_categorie.min' => 'Le nom de la catégorie doit avoir au minimum 5 caractères.',
            'image_categorie.image' => "Le fichier doit être une image.",
            'image_categorie.mimes' => "L'image doit être au format jpeg, png, jpg ou gif.",
            'image_categorie.max' => "L'image ne doit pas dépasser 2 Mo.",
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
            ], 404);
        }

        try {
            $categorie->nom_categorie = $request->nom_categorie;

            // Si une nouvelle image est fournie, on l'upload sur imgbb
            if ($request->hasFile('image_categorie')) {
                $imageUrl = $this->uploadImageToHosting($request->file('image_categorie'));
                $categorie->image_categorie = $imageUrl;
            }

            $categorie->save();

            return response()->json([
                'success' => true,
                'data' => $categorie,
                'message' => 'Catégorie mise à jour avec succès.'
            ]);
        } catch (QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Échec de la mise à jour de la catégorie.',
                'erreur' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Une erreur inattendue est survenue.',
                'erreur' => config('app.debug') ? $e->getMessage() : null
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
