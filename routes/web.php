<?php

use App\Http\Controllers\ArticleController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategorieController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\CommandeController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PaiementController;
use App\Http\Controllers\PanierController;
use App\Http\Controllers\PrixController;
use App\Http\Controllers\PubliciteController;
use App\Http\Controllers\RechercheController;
use App\Http\Controllers\VariationController;
use App\Http\Controllers\VilleCommuneController;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;

Route::get('/', function(){
    return view('welcome');
});

Route::prefix('/api')->group(function(){

Route::get('/test-mail', function() {
    try {
        Mail::raw('Ceci est un test Laravel', function($message){
            $message->to('emmanuelbamidele183@gmail.com')
                    ->subject('Test Email Laravel');
        });
        return 'Email envoyé !';
    } catch (\Exception $e) {
        return 'Erreur : '.$e->getMessage();
    }
});

    
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
        Route::post('/boutique/image/{hashid}/update', [ClientController::class, 'update_image_boutique']);

        //CRUD ARTICLE
        Route::post('/article/ajout', [ArticleController::class, 'ajout_article']);
        Route::post('/article/{hashid}/update', [ArticleController::class, 'update_article']);
        Route::post('/article/{hashid}/delete', [ArticleController::class, 'delete_article']);
        Route::get('/article/boutique', [ArticleController::class, 'article_boutique']);

        //gestion des commandes de la boutique
        Route::get('/commande/boutique', [CommandeController::class, 'commandes_boutique']);

        Route::post('/ajout/libelles/variations', [VariationController::class, 'ajouterLibelles']);

        Route::post('/boutique/update-infos', [AuthController::class, 'update_infos_btq']);
        Route::post('/boutique/update-password', [AuthController::class, 'update_password_btq']);

        Route::post('/reclammer/du', [CommandeController::class, 'reclammer_du']);
        Route::get('/solde/boutique', [CommandeController::class, 'solde_boutique']);
        
    Route::post('/variation/{hashid}/update', [VariationController::class, 'update_variation']);

    });

    //CRUD VARIATION
    Route::get('/variation/{hashid}', [VariationController::class, 'variation']);

    //Route qui ne nécéssite pas que la boutique soit connectée
    Route::get('/articles', [ArticleController::class, 'liste_article']);
    Route::get('/article/{hashid}', [ArticleController::class, 'article']);
    Route::get('/article/{hashid}/categorie', [ArticleController::class, 'articlesParCategorie']);
    Route::get('/variations/boutique', [VariationController::class, 'liste_variation']);
    Route::get('/variations', [VariationController::class, 'liste_variation_sans_lib']);
    //Trier les articles par prix du moins cher au plus cher
    Route::get('/article/trie/moins/plus/prix', [ArticleController::class, 'trier_par_prix_moinsCher_cher']);
    //Trier les articles par prix du plus cher au moins cher
    Route::get('/article/trie/plus/moins/prix', [ArticleController::class, 'trier_par_prix_cher_moinsCher']);


    //RECHERCHES ET HISTORIQUES DES RECHERCHES
    Route::get('/recherche', [RechercheController::class, "recherche"]);
    Route::get('/historique', [RechercheController::class, 'historique']);

    //Suggestion de recherches
    Route::get('/suggestion', [RechercheController::class, 'suggestion']);




    Route::middleware('auth:client')->group(function(){
        Route::post('/passer/commande', [CommandeController::class, 'commande_ajout']);
        Route::get('/commande/client', [CommandeController::class, 'commandes_client']);

        //PANIER

        //Ajout panier
        Route::post('/ajout/panier', [PanierController::class, 'ajout_panier']);
        Route::get('/panier', [PanierController::class, 'get_panier']);
        Route::post('/panier/augmenter', [PanierController::class, 'augmenterQuantite']);
        Route::post('/panier/diminuer', [PanierController::class, 'diminuerQuantite']);
        Route::post('/panier/delete', [PanierController::class, 'supprimerArticle']);


        // //Paiement utilisateur
        // Route::post('/user/paiement', [PaiementController::class, 'user_paiement']);
        // //Verification du paiement
        // Route::get('paiement/verify/{reference}', [PaiementController::class, 'verify_paiement']);
        // //Liste des transactions du client
        // Route::get('/transactions/client', [PaiementController::class, 'transactions_client']);

        //Initier paiement
        Route::post('/paiement/initier', [PaiementController::class, 'initierPaiement']);
    });
    Route::get('/commande/{hashid}', [CommandeController::class, 'commande']);

    //Liste des tendances
    Route::get('/articles/tendances', [CommandeController::class, 'articles_tendance']);
    //Articles recommandés
    Route::get('/articles/recommandes', [CommandeController::class, 'articles_recommandes']);

    Route::middleware('auth:sanctum')->group(function(){
        Route::post('/device/token', [NotificationController::class, 'recupereDeviceToken']);
        Route::get('/notifications', [NotificationController::class, 'notifications']);
    });



    // Afficher toutes les publicités pour les clients
    Route::get('/publicite/clients', [PubliciteController::class, 'publicitesClients']);

    // Afficher toutes les publicités pour les boutiques
    Route::get('/publicite/boutiques', [PubliciteController::class, 'publicitesBoutiques']);

    // Afficher toutes les publicités (tout le monde)
    Route::get('/publicites/all', [PubliciteController::class, 'publicitesAll']);

    //Afficher la liste des publicites
    Route::get('/publicites', [PubliciteController::class, 'publicites']);

    //Afficher une publicite
    Route::get('publicite/{hashid}', [PubliciteController::class, 'voirParHashid']);

    
    Route::get('/categories', [CategorieController::class, 'liste_categorie']);

    
    Route::get('/villes', [VilleCommuneController::class, 'liste_ville']);
    Route::get('/ville/{hashid}', [VilleCommuneController::class, 'ville']);
    Route::get('/communes', [VilleCommuneController::class, 'liste_commune']);
    Route::get('/commune/{lib_ville}/ville', [VilleCommuneController::class, 'communesParNomVille']);

    //Liste des boutiques
    Route::get('/boutiques', [AuthController::class, 'boutiques']);

    Route::post('/admin/login', [AuthController::class, 'login_admin']);

    Route::get('/articles/boutique/{hashid}', [ArticleController::class, 'articlesBoutique']);



    //-------------------------
    // ADMINISTRATEUR
    //-------------------------

     Route::middleware('auth:admin')->group((function(){
        //Ajouter un admin
        Route::post('/ajout/admin', [AuthController::class, "ajout_admin"]);
        Route::get('/admins', [AuthController::class, "liste_admin"]);
        Route::post('/admin/delete/{hashid}', [AuthController::class, "delete_admin"]);

        //CRUD CATEGORIE
    Route::prefix('/categorie')->group(function(){
        Route::post('/ajout', [CategorieController::class, 'ajout_categorie']);
        Route::get('/{hashid}', [CategorieController::class, 'categorie']);
        Route::post('/{hashid}/update', [CategorieController::class, 'update_categorie']);
        Route::post('/{hashid}/delete', [CategorieController::class, 'delete_categorie']);
    });

    //VILLES ET COMMUNES

    //Villes
    Route::post('/ajout/ville', [VilleCommuneController::class, 'ajout_ville']);

    //Communes
    Route::post('/ajout/commune', [VilleCommuneController::class, 'ajout_commune']);

    
    Route::get('/commandes', [CommandeController::class, 'liste_commande']);
    //Changer le statut des commandes
    Route::post('/commande/{hashid}/confirme', [CommandeController::class, 'edit_statut_confirme']);
    Route::post('/commande/{hashid}/annule', [CommandeController::class, 'edit_statut_annule']);
    Route::post('/commande/{hashid}/livree', [CommandeController::class, 'edit_statut_livree']);
    Route::post('/commande/{hashid_commande}/sous_commande/{hashid_article}', [CommandeController::class, 'edit_statut_sous_commande']);
    Route::get('/commande/rechercher/{code}', [CommandeController::class, 'rechercherCommande']);

    //-------------------
    //  NOTIFICATIONS
    //------------------

    //Notification à une boutique
    Route::post('/notification/boutique', [NotificationController::class, 'notification_boutique']);
    //Notifications à un client
    Route::post('/notification/client', [NotificationController::class, 'notification_client']);
    //Liste des utilisateurs avec leur device token
    Route::get('/users', [NotificationController::class, 'liste_users']);
    //Notifications à toutes les boutiques
    Route::post('/notification/boutiques/all', [NotificationController::class, 'notification_toutes_boutiques']);
    //Notifications à tous les clients
    Route::post('/notification/clients/all', [NotificationController::class, 'notification_tous_clients']);
    //Notifications à tout le monde
    Route::post('/notification/all', [NotificationController::class, 'notification_tout_le_monde']);
    
    //-------------------
    //  PUBLICITES
    //------------------

    // Créer une publicité pour les clients
    Route::post('/publicite/ajout/clients', [PubliciteController::class, 'ajoutClient']);

    // Créer une publicité pour les boutiques
    Route::post('/publicite/ajout/boutiques', [PubliciteController::class, 'ajoutBoutique']);

    // Créer une publicité pour tout le monde
    Route::post('/publicite/ajout/all', [PubliciteController::class, 'ajoutToutLeMonde']);

    // Modifier une publicité
    Route::post('/publicite/modif/{hashid}', [PubliciteController::class, 'modifier']);

    // Supprimer une publicité
    Route::delete('/publicite/supprimer/{hashid}', [PubliciteController::class, 'supprimer']);


    //Liste des clients
    Route::get('/clients', [AuthController::class, 'clients']);

    //Supprimer client ou boutique
    Route::post("/client/delete/{hashid}", [AuthController::class, 'delete_client']);
    Route::post("/boutique/delete/{hashid}", [AuthController::class, 'delete_boutique']);

    
    Route::post('/variation/ajout', [VariationController::class, 'ajout_variation']);

    // Routes pour les villes
    Route::post('/villes/modifier/{hashid}', [VilleCommuneController::class, 'modifier_ville']);
    Route::post('/villes/supprimer/{hashid}', [VilleCommuneController::class, 'supprimer_ville']);

    // Routes pour les communes
    Route::post('/communes/modifier/{hashid}', [VilleCommuneController::class, 'modifier_commune']);
    Route::post('/communes/supprimer/{hashid}', [VilleCommuneController::class, 'supprimer_commune']);

    Route::get('/commandes/filtre', [CommandeController::class, 'filtrerCommandes']);

    Route::get('/info/admin', [AuthController::class, 'info_admin']);
    Route::post('/admin/update-infos', [AuthController::class, 'update_infos_admin']);
    Route::post('/admin/update-password', [AuthController::class, 'update_password_admin']);

    //Consulter le solde de l’admin
    Route::get('/consulter/solde/admin', [PaiementController::class, 'consulter_solde']);
    Route::get('/transactions/admin/recues', [PaiementController::class, 'transactions_recues_admin']);
    
    Route::get('afficher/seuil', [PrixController::class, 'afficher_seuil']);
    Route::get('afficher/prix', [PrixController::class, 'afficher_prix']);
    Route::post('update/prix', [PrixController::class, 'update_prix']);
    Route::post('update/seuil', [PrixController::class, 'update_seuil']);

    //Afficher le solde de l’admin
    Route::get('/soldes', [CommandeController::class, 'admin_solde']);
    //Afficher portefeuille
    Route::get('/afficher/portefeuille', [CommandeController::class, 'afficher_portefeuille']);
    Route::post('marquer/paye/{hashid}', [CommandeController::class, 'marquerCommePayee']);
     }));

     Route::middleware('auth:admin,boutique')->group(function(){
        
        Route::post('/variation/{hashid}/delete', [VariationController::class, 'delete_variation']);
     });

});