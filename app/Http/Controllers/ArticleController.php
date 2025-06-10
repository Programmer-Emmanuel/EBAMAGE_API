<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Categorie;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Vinkla\Hashids\Facades\Hashids;

class ArticleController extends Controller
{

    public function ajout_article(Request $request){
        $request->validate([
            'nom_article' => 'required',
            'prix' => 'required|numeric|min:10',
            'image' => 'required|image|mimes:jpeg,png,jpg|max:2048',
            'description' => 'required|min:10',
            'id_categories' => 'required|array',
            'id_categories.*' => 'string',
            'id_variations' => 'nullable|array',
            'id_variations.*' => 'string',
        ], [
            'nom_article.required' => 'Le nom de l’article est obligatoire.',
            'prix.required' => 'Le prix est obligatoire.',
            'prix.numeric' => 'Le prix doit être un nombre.',
            'prix.min' => 'Le prix minimum autorisé est de 10.',
            'image.required' => 'L’image est obligatoire.',
            'image.image' => 'Le fichier doit être une image.',
            'image.mimes' => 'L’image doit être au format jpeg, png ou jpg.',
            'image.max' => 'La taille maximale de l’image est 2 Mo.',
            'description.required' => 'La description est obligatoire.',
            'description.min' => 'La description doit contenir au moins 10 caractères.',
            'id_categories.required' => 'Les catégories sont obligatoires.',
            'id_categories.array' => 'Les catégories doivent être envoyées sous forme de tableau.',
            'id_categories.*.string' => 'Les identifiants des catégories doivent être valides.',
            'id_variations.array' => 'Les variations doivent être envoyées sous forme de tableau.',
            'id_variations.*.string' => 'Les identifiants des variations doivent être valides.',
        ]);

        $image_article = $this->uploadImageToHosting($request->file('image'));

        try {
            $article = new Article();
            $article->nom_article = $request->nom_article;
            $article->prix = $request->prix;
            $article->image = $image_article;
            $article->description = $request->description;
            $article->id_btq = auth('boutique')->id();
            $article->save();

            $id_categories = collect($request->id_categories)->map(function ($hashid) {
                return Hashids::decode($hashid)[0] ?? null;
            })->filter();

            if ($id_categories->isNotEmpty()) {
                $article->categories()->attach($id_categories);
            }

            if ($request->has('id_variations')) {
                $id_variations = collect($request->id_variations)->map(function ($hashid) {
                    return Hashids::decode($hashid)[0] ?? null;
                })->filter();

                if ($id_variations->isNotEmpty()) {
                    $article->variations()->attach($id_variations);
                }
            }

            return response()->json([
                'success' => true,
                'data' => $article->load('categories', 'variations'),
                'message' => 'Article enregistré avec succès.'
            ]);
        } catch (QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l’enregistrement de l’article',
                'erreur' => $e->getMessage()
            ]);
        }
    }



    //METTRE LES IMAGES SUR LE SITE "imgbb.com"
    private function uploadImageToHosting($image){
        $apiKey = '9b1ab6564d99aab6418ad53d3451850b';

        // Vérifie que le fichier est une instance valide
        if (!$image->isValid()) {
            throw new \Exception("Fichier image non valide.");
        }

        // Lecture et encodage en base64
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

    public function liste_article(){
        $id_boutique = auth('boutique')->id();
        $articles = Article::where('id_btq', $id_boutique)->with('categories')->get();

        if ($articles->isNotEmpty()) {
            return response()->json([
                'success' => true,
                'data' => $articles->load('categories', 'variations'),
                'message' => 'Articles récupérés avec succès.'
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Aucun article trouvé.'
            ]);
        }
    }

    public function article($hashid){
        $id = Hashids::decode($hashid)[0] ?? null;
    
        if (!$id) {
            return response()->json(['message' => 'ID invalide'], 400);
        }

        $article = Article::find($id);

        $autre_article = Article::where('id_btq', $article->id_btq)->where('id', '!=', $article->id)->get();
        
        if($article){
            return response()->json([
                'success' => true,
                'data' => $article,
                'communs'=> $autre_article,
                'message' => 'Article trouvé.'
            ]);
        }
        else{
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération de l’article.'
            ]);
        }
    }


    public function articlesParCategorie($hashid){
        $id = Hashids::decode($hashid)[0] ?? null;
    
        if (!$id) {
            return response()->json(['message' => 'ID invalide'], 400);
        }

        $categorie = Categorie::with('articles.categories')->find($id);

        if (!$categorie) {
            return response()->json([
                'success' => false,
                'message' => 'Catégorie non trouvée.'
            ]);
        }

        $articles = $categorie->articles->map(function ($article) use ($categorie) {
            return [
                'id' => $article->hashid,
                'nom_article' => $article->nom_article,
                'prix' => $article->prix,
                'description' => $article->description,
                'image' => $article->image,
                'categorie' => $categorie->nom_categorie,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $articles,
            'message' => 'Articles de la catégorie récupérés avec succès.'
        ]);
    }

    public function delete_article($hashid){
        $id = Hashids::decode($hashid)[0] ?? null;
    
        if (!$id) {
            return response()->json(['message' => 'ID invalide'], 400);
        }

        $article = Article::find($id);

        if (!$article) {
            return response()->json([
                'success' => false,
                'message' => 'Article introuvable.'
            ]);
        }

        if ($article->id_btq !== auth('boutique')->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'êtes pas autorisé à supprimer cet article.'
            ]);
        }

        $article->delete();

        return response()->json([
            'success' => true,
            'message' => 'Article supprimé avec succès.'
        ]);
    }



    public function update_article(Request $request, $hashid){
        $request->validate([
            'nom_article' => 'required',
            'prix' => 'required|numeric|min:10',
            'description' => 'required|min:10',
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'id_categories' => 'nullable|array',
            'id_categories.*' => 'string',
            'id_variations' => 'nullable|array',
            'id_variations.*' => 'string',
        ]);

        $id = Hashids::decode($hashid)[0] ?? null;

        if (!$id) {
            return response()->json(['message' => 'ID invalide'], 400);
        }

        $article = Article::find($id);

        if (!$article) {
            return response()->json([
                'success' => false,
                'message' => 'Article introuvable.'
            ]);
        }

        if ($article->id_btq !== auth('boutique')->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'êtes pas autorisé à modifier cet article.'
            ]);
        }

        $article->nom_article = $request->nom_article;
        $article->prix = $request->prix;
        $article->description = $request->description;

        // Image (facultative)
        if ($request->hasFile('image')) {
            $image = $this->uploadImageToHosting($request->file('image'));
            $article->image = $image;
        }

        $article->save();

        // MAJ des catégories (si fournies)
        if ($request->has('id_categories')) {
            $id_categories = collect($request->id_categories)->map(function ($hashid) {
                return Hashids::decode($hashid)[0] ?? null;
            })->filter();
            $article->categories()->sync($id_categories); // mise à jour propre
        }

        // MAJ des variations (si fournies)
        if ($request->has('id_variations')) {
            $id_variations = collect($request->id_variations)->map(function ($hashid) {
                return Hashids::decode($hashid)[0] ?? null;
            })->filter();
            $article->variations()->sync($id_variations); // mise à jour propre
        }

        return response()->json([
            'success' => true,
            'data' => $article->load('categories', 'variations'),
            'message' => 'Article mis à jour avec succès.'
        ]);
    }

    public function trier_par_prix_moinsCher_cher(){
        $article = Article::orderBy('prix', 'asc')->get();
        if($article){
            return response()->json([
                'success' => true,
                'data' => $article->load('categories', 'variations'),
                'message' => 'Article trié du moins cher au plus cher'
            ]);
        }
        else{
            return response()->json([
                'success' => false,
                'message' => 'Echec lors du trix du prix de l’article. (moins cher au plus cher)'
            ]);
        }
    }

    public function trier_par_prix_cher_moinsCher(){
        $article = Article::orderBy('prix', 'desc')->get();
        if($article){
            return response()->json([
                'success' => true,
                'data' => $article->load('categories', 'variations'),
                'message' => 'Article trié du plus cher au moins cher'
            ]);
        }
        else{
            return response()->json([
                'success' => false,
                'message' => 'Echec lors du trix du prix de l’article. (plus cher au moins cher)'
            ]);
        }
    }



}
