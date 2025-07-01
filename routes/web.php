<?php

use App\Http\Controllers\ArticleController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategorieController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\RechercheController;
use App\Http\Controllers\VariationController;
use App\Http\Controllers\VilleCommuneController;
use Illuminate\Support\Facades\Route;

Route::get('/', function(){
    return view('welcome');
});

Route::prefix('/api')->group(function(){
    //Route authentification du client
    Route::post('/register/client', [AuthController::class, 'register_clt']);
    Route::post('/verify/otp/client', [AuthController::class, 'verifyOtp']);
    Route::post('/resend/otp/client', [AuthController::class, 'resendOtp']);
    Route::post('/login/client', [AuthController::class, 'login_clt']);

    //Route authentification de la boutique
    Route::post('/register/boutique', [AuthController::class, 'register_btq']);
    Route::post('/login/boutique', [AuthController::class, 'login_btq']);

    // Route authentification du livreur
    Route::post('/register/livreur', [AuthController::class, 'register_liv']);
    Route::post('/verify/otp/livreur', [AuthController::class, 'verifyOtp_liv']);
    Route::post('/login/livreur', [AuthController::class, 'login_liv']);

    Route::prefix('/client')->middleware('auth:client')->group(function(){
        //Route pour afficher les infos du client connecté
        Route::get('/info', [AuthController::class, 'info_clt']);
        //Route pour ajouter une image au profil du client
        Route::post('/image/{hashid}/update', [ClientController::class, 'update_image']);
        //Route pour supprimer une image au profil client
        Route::post('/image/{hashid}/delete', [ClientController::class, 'delete_image']);
    });

    //Route qui nécéssite que la boutique soit connecté
    Route::middleware('auth:boutique')->group(function () {
        //Route pour afficher les infos de la boutique connectée
        Route::get('/info/boutique', [AuthController::class, 'info_btq']);

        //CRUD ARTICLE
        Route::post('/article/ajout', [ArticleController::class, 'ajout_article']);
        Route::post('/article/{hashid}/update', [ArticleController::class, 'update_article']);
        Route::post('/article/{hashid}/delete', [ArticleController::class, 'delete_article']);

        //CRUD VARIATION
        Route::post('/variation/ajout', [VariationController::class, 'ajout_variation']);
        Route::get('/variation/{hashid}', [VariationController::class, 'variation']);
        Route::post('/variation/{hashid}/update', [VariationController::class, 'update_variation']);
        Route::post('/variation/{hashid}/delete', [VariationController::class, 'delete_variation']);

    });

    //Route qui ne nécéssite pas que la boutique soit connectée
    Route::get('/articles', [ArticleController::class, 'liste_article']);
    Route::get('/article/{hashid}', [ArticleController::class, 'article']);
    Route::get('/article/{hashid}/categorie', [ArticleController::class, 'articlesParCategorie']);
    Route::get('/variations', [VariationController::class, 'liste_variation']);

    //Trier les articles par prix du moins cher au plus cher
    Route::get('/article/trie/moins/plus/prix', [ArticleController::class, 'trier_par_prix_moinsCher_cher']);
    //Trier les articles par prix du plus cher au moins cher
    Route::get('/article/trie/plus/moins/prix', [ArticleController::class, 'trier_par_prix_cher_moinsCher']);

    //CRUD CATEGORIE
    Route::prefix('/categorie')->group(function(){
        Route::post('/ajout', [CategorieController::class, 'ajout_categorie']);
        Route::get('/{hashid}', [CategorieController::class, 'categorie']);
        Route::post('/{hashid}/update', [CategorieController::class, 'update_categorie']);
        Route::post('/{hashid}/delete', [CategorieController::class, 'delete_categorie']);
    });
    Route::get('/categories', [CategorieController::class, 'liste_categorie']);

    //RECHERCHES ET HISTORIQUES DES RECHERCHES
    Route::post('/recherche', [RechercheController::class, "recherche"]);
    Route::get('/historique', [RechercheController::class, 'historique']);


    //VILLES ET COMMUNES

    //Villes
    Route::post('/ajout/ville', [VilleCommuneController::class, 'ajout_ville']);
    Route::get('/villes', [VilleCommuneController::class, 'liste_ville']);
    Route::get('/ville/{hashid}', [VilleCommuneController::class, 'ville']);

    //Communes
    Route::post('/ajout/commune', [VilleCommuneController::class, 'ajout_commune']);
    Route::get('/communes', [VilleCommuneController::class, 'liste_commune']);
    Route::get('/commune/{hashid}/ville', [VilleCommuneController::class, 'communesParVille']);


});