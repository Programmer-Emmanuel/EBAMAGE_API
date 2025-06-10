<?php

namespace App\Http\Controllers;
use Vinkla\Hashids\Facades\Hashids;
use App\Models\Categorie;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

class CategorieController extends Controller
{
    public function ajout_categorie(Request $request){
        $request->validate([
            'nom_categorie' => 'required|min:5'
        ],[
            'nom_categorie.required' => 'Le nom de la catégorie est obligatoire.',
            'nom_categorie.min' => 'Le nom de la catégorie doit avoir au minimum 5 caractères'
        ]);

        try{
            $categorie = new Categorie();
            $categorie->nom_categorie = $request->nom_categorie;
            $categorie->save();

            return response()->json([
                'success' => true,
                'data' => $categorie,
                'message'=> 'Categorie ajoutée avec succès.'
            ]);
        }
        catch(QueryException $e){
            return response()->json([
                'success' => false,
                'message' => 'Categorie non ajouté.',
                'erreur' => $e->getMessage()
            ]);
        }
    }

    public function liste_categorie(){
        
        $categories = Categorie::all();

        if ($categories->isNotEmpty()) {
            return response()->json([
                'success' => true,
                'data' => $categories,
                'message' => 'Catégories récupérées.'
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Aucune catégorie trouvée pour cette boutique.'
            ]);
        }
    }


    public function categorie($hashid){

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
    }

    public function update_categorie(Request $request, $hashid){
        $request->validate([
            'nom_categorie' => 'required|min:5'
        ],[
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
            ]);
        }
    }

    public function delete_categorie(Request $request, $hashid){
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
    }

}
