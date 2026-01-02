<?php

namespace App\Http\Controllers;

use App\Mail\OtpMail;
use App\Mail\ResetPasswordAdminMail;
use App\Mail\ResetPasswordBoutiqueMail;
use App\Mail\ResetPasswordClientMail;
use App\Models\Admin;
use App\Models\Boutique;
use App\Models\Livreur;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use PDOException;
use Vinkla\Hashids\Facades\Hashids;

class AuthController extends Controller
{
    //AUTHENTIFICATION CLIENT

    //INSCRIPTION CLIENT
    public function register_clt(Request $request)
{
    try {
        // 1. Validation
        $validatedData = $request->validate([
            'nom_clt' => 'required|string|max:255',
            'email_clt' => 'required|email',
            'tel_clt' => 'required|digits:10',
            'password_clt' => 'required|min:6'
        ], [
            'nom_clt.required' => 'Le nom du client est obligatoire.',
            'email_clt.required' => 'L’email du client est obligatoire.',
            'email_clt.email' => 'L’adresse email du client n’est pas valide.',
            'tel_clt.required' => 'Le numéro de téléphone est obligatoire.',
            'tel_clt.digits' => 'Le numéro de téléphone doit contenir exactement 10 chiffres.',
            'password_clt.required' => 'Le mot de passe est obligatoire.',
            'password_clt.min' => 'Le mot de passe doit contenir au moins 6 caractères.',
        ]);

        // 2. Vérification de l'existence d'un compte
        $existing = User::where('email_clt', $validatedData['email_clt'])
            ->orWhere('tel_clt', $validatedData['tel_clt'])
            ->first();

        if ($existing) {
            if ($existing->is_verify) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cet email ou numéro est déjà utilisé par un compte vérifié.',
                ], 409);
            } else {
                $existing->delete(); // Supprimer compte non vérifié
            }
        }

        // 3. Génération du code OTP
        $code_otp = rand(1000, 9999);

        // $code_otp = substr($validatedData['tel_clt'], -4);

        // 4. Création du compte client
        $client = new User();
        $client->nom_clt = $validatedData['nom_clt'];
        $client->email_clt = $validatedData['email_clt'];
        $client->tel_clt = $validatedData['tel_clt'];
        $client->password_clt = Hash::make($validatedData['password_clt']);
        $client->code_otp = $code_otp;
        $client->otp_expires_at = now()->addMinutes(60);
        $client->is_verify = false;
        $client->save();

        // 5. Envoi de l'email
        try {
            Mail::to($client->email_clt)->send(new OtpMail($code_otp));
        } catch (\Exception $e) {
            // Si l'email échoue, supprimer le compte et retourner une erreur
            $client->delete();

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l’envoi de l’email de confirmation. Veuillez réessayer.',
            ], 500);
        }

        // 6. Réponse finale
        return response()->json([
            'success' => true,
            'data' => [
                'nom_clt' => $client->nom_clt,
                'email_clt' => $client->email_clt,
                'tel_clt' => $client->tel_clt,
                'device_token' => $client->device_token,
                'created_at' => $client->created_at,
                'updated_at' => $client->updated_at
            ],
            'message' => 'Client enregistré avec succès. Un code OTP a été envoyé.',
        ]);

    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json([
            'success' => false,
            'message' => 'Erreur de validation.',
            'errors' => $e->errors(),
        ], 422);

    } catch (QueryException $e) {
        return response()->json([
            'success' => false,
            'message' => 'Une erreur inattendue est survenue. Veuillez réessayer.',
            'erreur' => $e->getMessage() // Décommente uniquement en mode debug
        ], 500);
    }
}




    //VERIFICATION OTP CLIENT
    public function verifyOtp(Request $request)
{
    try {
        // 1. Validation des champs
        $validated = $request->validate([
            'email_clt' => 'required|email',
            'code_otp' => 'required|digits:4',
        ], [
            'email_clt.required' => 'L’email est requis.',
            'email_clt.email' => 'L’email n’est pas valide.',
            'code_otp.required' => 'Le code OTP est requis.',
            'code_otp.digits' => 'Le code OTP doit contenir exactement 4 chiffres.',
        ]);

        // 2. Recherche de l’utilisateur
        $client = User::where('email_clt', $validated['email_clt'])->first();

        if (!$client) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun utilisateur trouvé avec cet email.',
            ], 404);
        }

        // 3. Vérification du code OTP
        if ($client->code_otp !== $validated['code_otp']) {
            return response()->json([
                'success' => false,
                'message' => 'Le code OTP est invalide.',
            ], 401);
        }

        // 4. Vérification de l’expiration
        if ($client->otp_expires_at && $client->otp_expires_at->isPast()) {
            return response()->json([
                'success' => false,
                'message' => 'Le code OTP a expiré. Veuillez demander un nouveau code.',
            ], 410);
        }

        // 5. Mise à jour du compte client
        $client->email_verified_at = now();
        $client->code_otp = null;
        $client->otp_expires_at = null;
        $client->is_verify = true;
        $client->save();

        // 6. Génération du token
        $token = $client->createToken('client-token')->plainTextToken;

        // 7. Réponse
        return response()->json([
            'success' => true,
            'message' => 'Code OTP vérifié avec succès.',
            'data' => $client,
            'token' => $token,
        ]);

    } catch (QueryException $e) {
        return response()->json([
            'success' => false,
            'message' => 'Erreur de validation.',
            'errors' => $e->getMessage(),
        ], 422);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Une erreur inattendue est survenue. Veuillez réessayer.',
            // 'erreur' => $e->getMessage() // À activer en mode debug uniquement
        ], 500);
    }
}


    //RENVOYER LE CODE OTP 
    public function resendOtp(Request $request)
{
    try {
        // 1. Validation des données
        $validated = $request->validate([
            'email_clt' => 'required|email',
        ], [
            'email_clt.required' => 'L’email est obligatoire.',
            'email_clt.email' => 'L’email n’est pas valide.',
        ]);

        // 2. Recherche de l’utilisateur
        $user = User::where('email_clt', $validated['email_clt'])->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun utilisateur trouvé avec cet email.'
            ], 404);
        }

        if ($user->is_verify) {
            return response()->json([
                'success' => false,
                'message' => 'Ce compte est déjà vérifié.'
            ], 400);
        }

        // 3. Génération d’un nouveau code OTP
        $code_otp = rand(1000, 9999);
        $user->code_otp = $code_otp;
        $user->otp_expires_at = now()->addMinutes(60);
        $user->save();

        // 4. Envoi du mail
        try {
            Mail::to($user->email_clt)->send(new OtpMail($code_otp));
        } catch (QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de l’envoi de l’email. Veuillez réessayer plus tard.',
                // 'erreur' => $e->getMessage() // À activer en mode debug si nécessaire
            ], 500);
        }

        // 5. Succès
        return response()->json([
            'success' => true,
            'message' => 'Un nouveau code OTP a été envoyé à votre adresse email.'
        ]);

    } catch (QueryException $e) {
        return response()->json([
            'success' => false,
            'message' => 'Erreur de validation.',
            'errors' => $e->getMessage(),
        ], 422);

    } catch (QueryException $e) {
        return response()->json([
            'success' => false,
            'message' => 'Une erreur inattendue est survenue. Veuillez réessayer.',
            // 'erreur' => $e->getMessage() // Décommente si nécessaire
        ], 500);
    }
}


    //CONNEXION CLIENT
    public function login_clt(Request $request)
{
    try {
        // 1. Validation des données
        $validated = $request->validate([
            'email_clt' => 'required|email',
            'password_clt' => 'required|min:6'
        ], [
            'email_clt.required' => 'L’email du client est obligatoire.',
            'email_clt.email' => 'L’adresse email est invalide.',
            'password_clt.required' => 'Le mot de passe est obligatoire.',
            'password_clt.min' => 'Le mot de passe doit contenir au moins 6 caractères.',
        ]);

        // 2. Recherche de l’utilisateur
        $client = User::where('email_clt', $validated['email_clt'])->first();

        if (!$client) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun client trouvé avec cet email.',
            ], 404);
        }

        // 3. Vérification du mot de passe
        if (!Hash::check($validated['password_clt'], $client->password_clt)) {
            return response()->json([
                'success' => false,
                'message' => 'Identifiants incorrect.',
            ], 401);
        }

        // 4. Vérification de la validation du compte (OTP)
        if (!$client->is_verify) {
            return response()->json([
                'success' => false,
                'message' => 'Votre compte n’a pas encore été vérifié. Veuillez saisir le code OTP envoyé par mail.',
            ], 403);
        }

        // 5. Génération du token
        $token = $client->createToken('client-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Connexion réussie.',
            'data' => [
                'nom_clt' => $client->nom_clt,
                'email_clt' => $client->email_clt,
                'tel_clt' => $client->tel_clt,
                'solde_tdl' => $client->solde_tdl,
                'device_token' => $client->device_token,
                'hashid' => Hashids::encode($client->id)
            ],
            'token' => $token
        ]);

    } catch (QueryException $e) {
        return response()->json([
            'success' => false,
            'message' => 'Erreur de validation.',
            'errors' => $e->getMessage()
        ], 422);

    } catch (QueryException $e) {
        return response()->json([
            'success' => false,
            'message' => 'Une erreur est survenue lors de la tentative de connexion.',
            // 'erreur' => $e->getMessage() // À activer uniquement en debug
        ], 500);
    }
}

    
    //INFO DU CLIENT A PARTIR DE SON TOKEN
    public function info_clt(Request $request)
{
    try {
        $client = $request->user();

        if (!$client) {
            return response()->json([
                'success' => false,
                'message' => 'Utilisateur non authentifié.',
            ], 401);
        }

        if (!$client->is_verify) {
            return response()->json([
                'success' => false,
                'message' => 'Votre compte n’a pas encore été vérifié.',
            ], 403);
        }

        // Réponse avec des données filtrées (sans champs sensibles)
        return response()->json([
            'success' => true,
            'message' => 'Informations du client récupérées avec succès.',
            'data' => $client
        ]);

    } catch (QueryException $e) {
        return response()->json([
            'success' => false,
            'message' => 'Erreur lors de la récupération des informations du client.',
            // 'erreur' => $e->getMessage() // Active en debug
        ], 500);
    }
}


    //INSCRIPTIOIN BOUTIQUE
    public function register_btq(Request $request)
{
    try {
        // 1. Validation des données
        $validated = $request->validate([
            'nom_btq' => 'required|string|max:255',
            'email_btq' => 'required|email|unique:boutiques,email_btq',
            'tel_btq' => 'required|digits:10|unique:boutiques,tel_btq',
            'password_btq' => 'required|min:6'
        ], [
            'nom_btq.required' => 'Le nom de la boutique est obligatoire.',
            'email_btq.required' => 'L’adresse email de la boutique est obligatoire.',
            'email_btq.email' => 'L’adresse email n’est pas valide.',
            'email_btq.unique' => 'Cette adresse email est déjà utilisée.',
            'tel_btq.required' => 'Le numéro de téléphone est obligatoire.',
            'tel_btq.digits' => 'Le numéro de téléphone doit contenir exactement 10 chiffres.',
            'tel_btq.unique' => 'Ce numéro de téléphone est déjà utilisé.',
            'password_btq.required' => 'Le mot de passe est obligatoire.',
            'password_btq.min' => 'Le mot de passe doit contenir au moins 6 caractères.',
        ]);

        // 2. Création de la boutique
        $boutique = new Boutique();
        $boutique->nom_btq = $validated['nom_btq'];
        $boutique->email_btq = $validated['email_btq'];
        $boutique->tel_btq = $validated['tel_btq'];
        $boutique->image_btq = null;
        $boutique->password_btq = Hash::make($validated['password_btq']);
        $boutique->save();

        // 3. Suppression du champ mot de passe de la réponse
        $boutique->makeHidden(['password_btq']);

        // 4. Génération du token
        $token = $boutique->createToken('boutique-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Boutique enregistrée avec succès.',
            'data' => $boutique,
            'token' => $token,
        ]);

    } catch (QueryException $e) {
        return response()->json([
            'success' => false,
            'message' => 'Erreur de validation.',
            'errors' => $e->getMessage(),
        ], 422);

    } catch (QueryException $e) {
        return response()->json([
            'success' => false,
            'message' => 'Échec lors de l’enregistrement de la boutique.',
            // 'erreur' => $e->getMessage(), // à activer uniquement en debug
        ], 500);
    }
}


public function update_infos_btq(Request $request)
    {
        $boutique = $request->user();

        if (!$boutique) {
            return response()->json([
                'success' => false,
                'message' => 'Boutique non authentifiée.',
            ], 401);
        }

        // ✅ Validation
        $validated = $request->validate([
            'nom_btq' => 'required|string|max:255',
            'email_btq' => 'required|email|unique:boutiques,email_btq,' . $boutique->id,
            'tel_btq' => 'required|digits:10|unique:boutiques,tel_btq,' . $boutique->id,
        ], [
            'nom_btq.required' => 'Le nom de la boutique est obligatoire.',
            'email_btq.email' => 'Adresse email invalide.',
            'email_btq.unique' => 'Cette adresse email est déjà utilisée.',
            'tel_btq.digits' => 'Le numéro de téléphone doit contenir 10 chiffres.',
            'tel_btq.unique' => 'Ce numéro de téléphone est déjà utilisé.',
        ]);

        // ✅ Mise à jour
        $boutique->update($validated);

        $boutique->makeHidden(['password_btq']);

        return response()->json([
            'success' => true,
            'message' => 'Informations de la boutique mises à jour avec succès.',
            'data' => $boutique,
        ], 200);
    }

    /**
     * 🔹 Mise à jour du mot de passe de la boutique
     */
    public function update_password_btq(Request $request)
    {
        $boutique = $request->user();

        if (!$boutique) {
            return response()->json([
                'success' => false,
                'message' => 'Boutique non authentifiée.',
            ], 401);
        }

        // ✅ Validation
        $validated = $request->validate([
            'ancien_password' => 'required',
            'nouveau_password' => 'required|min:6|confirmed',
        ], [
            'ancien_password.required' => 'L’ancien mot de passe est requis.',
            'nouveau_password.required' => 'Le nouveau mot de passe est requis.',
            'nouveau_password.min' => 'Le mot de passe doit contenir au moins 6 caractères.',
            'nouveau_password.confirmed' => 'La confirmation du mot de passe ne correspond pas.',
        ]);

        // ✅ Vérification de l’ancien mot de passe
        if (!Hash::check($validated['ancien_password'], $boutique->password_btq)) {
            return response()->json([
                'success' => false,
                'message' => 'L’ancien mot de passe est incorrect.',
            ], 400);
        }

        // ✅ Mise à jour du nouveau mot de passe
        $boutique->password_btq = Hash::make($validated['nouveau_password']);
        $boutique->save();

        return response()->json([
            'success' => true,
            'message' => 'Mot de passe mis à jour avec succès.',
        ], 200);
    }


    //CONNEXION BOUTIQUE
   public function login_btq(Request $request)
{
    try {
        // 1. Validation des champs
        $validated = $request->validate([
            'email_btq' => 'required|email',
            'password_btq' => 'required',
        ], [
            'email_btq.required' => 'L’adresse email de la boutique est obligatoire.',
            'email_btq.email' => 'L’adresse email n’est pas valide.',
            'password_btq.required' => 'Le mot de passe est obligatoire.',
        ]);

        // 2. Récupération de la boutique
        $boutique = Boutique::where('email_btq', $validated['email_btq'])->first();

        if (!$boutique || !Hash::check($validated['password_btq'], $boutique->password_btq)) {
            return response()->json([
                'success' => false,
                'message' => 'Identifiants invalides.',
            ], 401);
        }

        // 3. Création du token si les identifiants sont corrects
        $token = $boutique->createToken('boutique-token')->plainTextToken;

        // 4. Suppression du champ mot de passe de la réponse
        $boutique->makeHidden(['password_btq']);

        return response()->json([
            'success' => true,
            'message' => 'Connexion réussie.',
            'data' => $boutique,
            'token' => $token,
        ]);

    } catch (QueryException$e) {
        return response()->json([
            'success' => false,
            'message' => 'Erreur de validation.',
            'errors' => $e->getMessage(),
        ], 422);

    } catch (QueryException $e) {
        return response()->json([
            'success' => false,
            'message' => 'Une erreur est survenue lors de la tentative de connexion.',
            // 'erreur' => $e->getMessage() // à activer en debug si besoin
        ], 500);
    }
}


    public function info_btq(Request $request)
{
    try {
        $boutique = $request->user();

        if (!$boutique) {
            return response()->json([
                'success' => false,
                'message' => 'Boutique non authentifiée.',
            ], 401);
        }

        // Cacher le mot de passe dans la réponse
        $boutique->makeHidden(['password_btq']);

        return response()->json([
            'success' => true,
            'message' => 'Informations de la boutique récupérées avec succès.',
            'data' => $boutique
        ]);

    } catch (QueryException $e) {
        return response()->json([
            'success' => false,
            'message' => 'Erreur lors de la récupération des informations de la boutique.',
            // 'erreur' => $e->getMessage() // à activer uniquement en debug
        ], 500);
    }
}


    // //INSCRIPTION LIVREUR
    // public function register_liv(Request $request)
    // {
    //     // Validation des champs
    //     $request->validate([
    //         'nom_liv' => 'required',
    //         'pren_liv' => 'required',
    //         'email_liv' => 'required|email|unique:livreurs',
    //         'tel_liv' => 'required|digits:10|unique:livreurs',
    //         'password_liv' => 'required|min:6',
    //         'photo_liv' => 'required|image',
    //         'photo_cni' => 'required|image',
    //         'photo_permis' => 'required|image'
    //     ], [
    //         'email_liv.unique' => 'Cet email est déjà utilisé.',
    //         'tel_liv.unique' => 'Ce numéro de téléphone est déjà utilisé.',
    //         'photo_liv.required' => 'La photo du livreur est obligatoire.',
    //         'photo_cni.required' => 'La photo de la CNI est obligatoire.',
    //         'photo_permis.required' => 'La photo du permis est obligatoire.',
    //     ]);

    //     try {
    //         // Hébergement des images (exemple avec imgbb.com, clé d'API requise)
    //         $photoLivLink = $this->uploadImageToHosting($request->file('photo_liv'));
    //         $photoCniLink = $this->uploadImageToHosting($request->file('photo_cni'));
    //         $photoPermisLink = $this->uploadImageToHosting($request->file('photo_permis'));

    //         $code_otp = rand(100000, 999999);

    //         $livreur = new Livreur();
    //         $livreur->nom_liv = $request->nom_liv;
    //         $livreur->pren_liv = $request->pren_liv;
    //         $livreur->email_liv = $request->email_liv;
    //         $livreur->tel_liv = $request->tel_liv;
    //         $livreur->password_liv = Hash::make($request->password_liv);
    //         $livreur->solde_tdl = 0;
    //         $livreur->code_otp = $code_otp;
    //         $livreur->otp_expires_at = now()->addMinutes(60);
    //         $livreur->photo_liv = $photoLivLink;
    //         $livreur->photo_cni = $photoCniLink;
    //         $livreur->photo_permis = $photoPermisLink;
    //         $livreur->save();

    //         // Générer un token
    //         $token = $livreur->createToken('livreur-token')->plainTextToken;

    //         // Envoyer l’email avec le code OTP
    //         Mail::to($livreur->email_liv)->send(new OtpMail($code_otp));

    //         unset($livreur->password_liv);

    //         return response()->json([
    //             'success' => true,
    //             'data' => $livreur,
    //             'message' => 'Livreur enregistré avec succès.',
    //             'token' => $token
    //         ]);
    //     } catch (QueryException $e) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Erreur lors de l’enregistrement du livreur.',
    //             'erreur' => $e->getMessage()
    //         ]);
    //     }
    // }

    // //VERIFICATION OTP LIVREUR
    // public function verifyOtp_liv(Request $request){
    //     $request->validate([
    //         'email_liv' => 'required|email',
    //         'code_otp' => 'required|digits:6',
    //     ]);

    //     $livreur = Livreur::where('email_liv', $request->email_liv)->first();

    //     if (!$livreur) {
    //         return response()->json(['success' => false, 'message' => 'Livreur non trouvé']);
    //     }

    //     if ($livreur->code_otp !== $request->code_otp) {
    //         return response()->json(['success' => false, 'message' => 'Code OTP invalide']);
    //     }

    //     if ($livreur->otp_expires_at->isPast()) {
    //         return response()->json(['success' => false, 'message' => 'Le code OTP a expiré']);
    //     }

    //     $livreur->email_verified_at = now();
    //     $livreur->code_otp = null;
    //     $livreur->otp_expires_at = null;
    //     $livreur->save();

    //     return response()->json(['success' => true, 'message' => 'OTP vérifié avec succès']);
    // }

    // //METTRE LES IMAGES SUR LE SITE "imgbb.com"
    // private function uploadImageToHosting($image){
    //     $apiKey = '9b1ab6564d99aab6418ad53d3451850b';

    //     // Vérifie que le fichier est une instance valide
    //     if (!$image->isValid()) {
    //         throw new \Exception("Fichier image non valide.");
    //     }

    //     // Lecture et encodage en base64
    //     $imageContent = base64_encode(file_get_contents($image->getRealPath()));

    //     $response = Http::asForm()->post('https://api.imgbb.com/1/upload', [
    //         'key' => $apiKey,
    //         'image' => $imageContent,
    //     ]);

    //     if ($response->successful()) {
    //         return $response->json()['data']['url'];
    //     }

    //     throw new \Exception("Erreur lors de l'envoi de l'image : " . $response->body());
    // }


    //     public function login_liv(Request $request){

    //     $request->validate([
    //         'email_liv' => 'required|email',
    //         'password_liv' => 'required|min:6'
    //     ], [
    //         'email_liv.required' => 'L’email du livreur est obligatoire.',
    //         'password_liv.required' => 'Le mot de passe est obligatoire.',
    //         'password_liv.min' => 'Le mot de passe doit contenir au moins 6 caractères.',
    //     ]);

    //     $livreur = Livreur::where('email_liv', $request->email_liv)->first();
    //     if($livreur && Hash::check($request->password_liv, $livreur->password_liv) && $livreur->otp_expires_at == null){
    //         //Token du livreur
    //         $token = $livreur->createToken('livreur-token')->plainTextToken;

    //         return response()->json([
    //             'success' => true,
    //             'data' => $livreur,
    //             'message' => 'Livreur connecté avec succès',
    //             'token' => $token
    //         ]);
    //     }
    //     else{
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Livreur non connecté.',
    //         ]);
    //     }
    // }


    public function clients(){
        try{
            $clients = User::all();
            if(empty($clients)){
                return response()->json([
                    "success" => true,
                    "data" => [],
                    "message" => "Aucun client trouvés"
                ]);
            }

            return response()->json([
                "success" => true,
                "data" => $clients,
                "message" => "Clients trouvés avec succès."
            ]);
        }
        catch(QueryException $e){
            return response()->json([
                "success" => false,
                "message" => "Une erreur est survenue",
                "erreur" => $e->getMessage()
            ], 500);
        }
    }

    public function boutiques(){
        try{
            $boutiques = Boutique::all();
            if(empty($boutiques)){
                return response()->json([
                    "success" => true,
                    "data" => [],
                    "message" => "Aucunes boutiques trouvés"
                ]);
            }

            return response()->json([
                "success" => true,
                "data" => $boutiques,
                "message" => "Boutiques trouvées avec succès."
            ]);
        }
        catch(QueryException $e){
            return response()->json([
                "success" => false,
                "message" => "Une erreur est survenue",
                "erreur" => $e->getMessage()
            ], 500);
        }
    }

    //Ajouter d’autres admins
    public function ajout_admin(Request $request){
        $request->validate([
            "nom" => "required|string",
            "email" => "required|email|unique:admins",
            "tel" => "required|digits:10|unique:admins",
            "password" => "required|string"
        ],[
            "nom.required" => "Le nom est obligatoire",
            "nom.string" => "Le nom doit etre une chaine de caractere",
            "email.required" => "L'email est obligatoire.",
            "email.email"=> "L'email doit etre de type email.",
            "email.unique" => "L'email est deja utilise.",
            "password.required" => "Le mot de passe est obligatoire"

        ]);

        try{
            $admin = new Admin();
            $admin->nom = $request->nom;
            $admin->email = $request->email;
            $admin->tel = $request->tel;
            $admin->password = Hash::make($request->password);
            $admin->save();

            return response()->json([
                "success" => true,
                "data" => $admin,
                "message" => "Ajout de l’administrateur réussie"
            ],201);
        }
        catch(QueryException $e){
            return response()->json([
                "success" => false,
                "message" => "Ajout de l’administrateur échoué",
                "erreur" => $e->getMessage()
            ],500);
        }
    }

    public function liste_admin(){
        try{
            $admin = Admin::where('role', "admin")->get();
            if(empty($admin)){
                return response()->json([
                    "success" => true,
                    "data" => [],
                    "message" => "Aucun admin trouvé."
                ],201);
            }
            return response()->json([
                "success" => true,
                "data" => $admin,
                "message" => "Liste des admins affichés avec succès"
            ],201);
        }
        catch(QueryException $e){
            return response()->json([
                "success" => false,
                "message" => "Erreur lors de l’affichage de la liste des administrateurs",
                "erreur" => $e->getMessage()
            ],503);
        }
    }

    public function delete_admin(Request $request, $hashid){
        $id = Hashids::decode($hashid);
        if(!$id){
            return response()->json([
                "success" => false,
                "message" => "Id est introuvable."
            ],404);
        }
        try{
            $admin = Admin::where("id", $id)->first();
            if(!$admin){
                return response()->json([
                    "success" =>false,
                    "message" => "Ädministrateur introuvable"
                ], 404);
            }
            if($admin && $admin->role === "admin"){
                $admin->delete();
                return response()->json([
                    "success" => true,
                    "message" => "Administrateur supprimé avec succès."
                ]);
            }
        }
        catch(QueryException $e){
            return response()->json([
                "success" => false,
                "message" => "Erreur lors de la suppression de l’adminitrateur",
                "erreur" => $e->getMessage()
            ]);
        }
    }




    public function login_admin(Request $request){
        $request->validate([
            'email' => 'required|email:admins',
            'password' => 'required'
        ]);
        try{
            $admin = Admin::where('email', $request->email)->first();
            if(!$admin){
                return response()->json([
                    'success' => false,
                    "message" => 'Email ou Mot de passe incorrect.'
                ]);
            }
            if($admin && Hash::check($request->password, $admin->password)){
                $token = $admin->createToken('admin-token')->plainTextToken;
                return response()->json([
                    'success' => true,
                    'data' => [
                        'nom' => $admin->nom,
                        'email' => $admin->email,
                        'role' => 'administrateur'
                    ],
                    'token' => $token,
                    'message' => 'Administrateur trouvé avec succès.'
                ]);
            }
        }
        catch(QueryException $e){
            return response()->json([
                "success" => false,
                "message" => 'Une erreur est survenue',
                "erreur" => $e->getMessage()
            ]);
        }
    }

    public function info_admin(Request $request)
{
    try {
        $admin = $request->user();

        if (!$admin) {
            return response()->json([
                'success' => false,
                'message' => 'Admin non authentifiée.',
            ], 401);
        }

        // Cacher le mot de passe dans la réponse
        $admin->makeHidden(['password']);

        return response()->json([
            'success' => true,
            'message' => 'Informations de l’admin récupérées avec succès.',
            'data' => [
                "hashid" => Hashids::encode($admin->id),
                "nom" => $admin->nom,
                "email" => $admin->email,
                "created_at" => $admin->created_at,
                "updated_at" => $admin->updated_at
            ]
        ]);

    } catch (QueryException $e) {
        return response()->json([
            'success' => false,
            'message' => 'Erreur lors de la récupération des informations de l’admin.',
            // 'erreur' => $e->getMessage() // à activer uniquement en debug
        ], 500);
    }
}

public function update_infos_admin(Request $request)
    {
        $admin = $request->user();

        if (!$admin) {
            return response()->json([
                'success' => false,
                'message' => 'Admin non authentifiée.',
            ], 401);
        }

        // ✅ Validation
        $validated = $request->validate([
            'nom' => 'required|string|max:255',
            'email' => 'required|email|unique:admins,email,' . $admin->id,
            'tel' => 'required|email|unique:admins'
        ], [
            'nom.required' => 'Le nom de admin est obligatoire.',
            'email.email' => 'Adresse email invalide.',
            'email.unique' => 'Cette adresse email est déjà utilisée.',
        ]);

        // ✅ Mise à jour
        $admin->update($validated);

        $admin->makeHidden(['password']);

        return response()->json([
            'success' => true,
            'message' => 'Informations de admin mises à jour avec succès.',
            'data' => [
                "hashid" => Hashids::encode($admin->id),
                "nom" => $admin->nom,
                "email" => $admin->email,
                "created_at" => $admin->created_at,
                "updated_at" => $admin->updated_at
            ],
        ], 200);
    }

    /**
     * 🔹 Mise à jour du mot de passe de la boutique
     */
    public function update_password_admin(Request $request)
    {
        $admin = $request->user();

        if (!$admin) {
            return response()->json([
                'success' => false,
                'message' => 'Admin non authentifiée.',
            ], 401);
        }

        // ✅ Validation
        $validated = $request->validate([
            'ancien_password' => 'required',
            'nouveau_password' => 'required|min:6|confirmed',
        ], [
            'ancien_password.required' => 'L’ancien mot de passe est requis.',
            'nouveau_password.required' => 'Le nouveau mot de passe est requis.',
            'nouveau_password.min' => 'Le mot de passe doit contenir au moins 6 caractères.',
            'nouveau_password.confirmed' => 'La confirmation du mot de passe ne correspond pas.',
        ]);

        // ✅ Vérification de l’ancien mot de passe
        if (!Hash::check($validated['ancien_password'], $admin->password)) {
            return response()->json([
                'success' => false,
                'message' => 'L’ancien mot de passe est incorrect.',
            ], 400);
        }

        // ✅ Mise à jour du nouveau mot de passe
        $admin->password = Hash::make($validated['nouveau_password']);
        $admin->save();

        return response()->json([
            'success' => true,
            'message' => 'Mot de passe mis à jour avec succès.',
        ], 200);
    }

    public function delete_client(Request $request, $hashid){
        try{
            $id = Hashids::decode($hashid)[0] ?? null;
            $client = User::where("id", $id)->first();

            if(!$client){
                return response()->json([
                    "success" => false,
                    "message" => "Client non trouvé."
                ],404);
            }

            $client->delete();

            return response()->json([
                "success" => true,
                "message" => "Client supprimé avec succès."
            ]);
        }
        catch(QueryException $e){
            return response()->json([
                "success" => false,
                "message" => "Une erreur du serveur est survenue",
                "erreur" => $e->getMessage()
            ]);
        }
    }

    public function delete_boutique(Request $request, $hashid){
        try{
            $id = Hashids::decode($hashid)[0] ?? null;
            $boutique = Boutique::where("id", $id)->first();

            if(!$boutique){
                return response()->json([
                    "success" => false,
                    "message" => "Boutique non trouvé."
                ],404);
            }

            $boutique->delete();
            
            return response()->json([
                "success" => true,
                "message" => "Boutique supprimée avec succès."
            ]);
        }
        catch(QueryException $e){
            return response()->json([
                "success" => false,
                "message" => "Une erreur du serveur est survenue",
                "erreur" => $e->getMessage()
            ]);
        }
    }

    public function deconnexion(Request $request){
        try{
            $user = $request->user();
            if(!$user){
                return response()->json([
                    'success' => false,
                    'message' => 'Boutique non connecté'
                ],403);
            }
            $boutique = User::where('id', $user->id)->first();
            if(!$boutique){
                return response()->json([
                    'success' => false,
                    'message' => 'Client non trouvé'
                ],404);
            }
            $boutique->device_token = null;
            $boutique->save();
            return response()->json([
                'success' => true,
                'message' => 'decconnexion reussie'
            ],200);
        }
        catch(QueryException $e){
            return response()->json([
                'success' => false,
                'message' => 'Erreur survenue lors de la deconnexion.',
                'erreur' => $e->getMessage()
            ],500);
        }
    }


private function findAccountByEmail(string $email)
{
    if ($boutique = Boutique::where('email_btq', $email)->first()) {
        return [
            'model' => $boutique,
            'type' => 'boutique',
            'email_field' => 'email_btq',
            'password_field' => 'password_btq'
        ];
    }

    if ($user = User::where('email_clt', $email)->first()) {
        return [
            'model' => $user,
            'type' => 'client',
            'email_field' => 'email_clt',
            'password_field' => 'password_clt'
        ];
    }

    if ($admin = Admin::where('email', $email)->first()) {
        return [
            'model' => $admin,
            'type' => 'admin',
            'email_field' => 'email',
            'password_field' => 'password'
        ];
    }

    return null;
}

public function demande_reset_password(Request $request)
{
    $validator = Validator::make($request->all(), [
        'email' => 'required|email'
    ]);

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'message' => $validator->errors()->first()
        ], 422);
    }

    try {
        $accountData = $this->findAccountByEmail($request->email);

        if (!$accountData) {
            return response()->json([
                'success' => false,
                'message' => 'Compte non trouvé'
            ], 404);
        }

        $account = $accountData['model'];

        // 🔐 OTP sécurisé (4 chiffres)
        $otp = random_int(1000, 9999);

        $account->password_reset_token = $otp;
        $account->password_reset_expire_at = now()->addMinutes(15);
        $account->save();

        $type = $accountData['type'];
        $emailField = $accountData['email_field'];

        switch ($type) {
            case 'boutique':
                Mail::to($account->$emailField)
                    ->send(new ResetPasswordBoutiqueMail($account, $otp));
                break;

            case 'client':
                Mail::to($account->$emailField)
                    ->send(new ResetPasswordClientMail($account, $otp));
                break;

            case 'admin':
                Mail::to($account->$emailField)
                    ->send(new ResetPasswordAdminMail($account, $otp));
                break;
        }

        return response()->json([
            'success' => true,
            'message' => 'Code de réinitialisation envoyé'
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Erreur lors de la demande de réinitialisation',
            'erreur' => $e->getMessage()
        ], 500);
    }
}
public function verify_otp_password(Request $request)
{
    $validator = Validator::make($request->all(), [
        'email' => 'required|email',
        'otp'   => 'required|digits:4'
    ]);

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'message' => $validator->errors()->first()
        ], 422);
    }

    try {
        $accountData = $this->findAccountByEmail($request->email);

        if (!$accountData) {
            return response()->json([
                'success' => false,
                'message' => 'Compte non trouvé'
            ], 404);
        }

        $account = $accountData['model'];

        if (
            $account->password_reset_token == $request->otp &&
            Carbon::parse($account->password_reset_expire_at)->isFuture()
        ) {
            $account->password_reset_token = null;
            $account->password_reset_expire_at = null;
            $account->save();

            return response()->json([
                'success' => true,
                'message' => 'OTP valide'
            ], 200);
        }

        return response()->json([
            'success' => false,
            'message' => 'OTP incorrect ou expiré'
        ], 400);

    } catch (QueryException $e) {
        return response()->json([
            'success' => false,
            'message' => 'Erreur lors de la vérification du code OTP',
            'erreur' => $e->getMessage()
        ], 500);
    }
}
public function nouveau_password(Request $request)
{
    $validator = Validator::make($request->all(), [
        'email'    => 'required|email',
        'password' => 'required|confirmed|min:6'
    ]);

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'message' => $validator->errors()->first()
        ], 422);
    }

    try {
        $accountData = $this->findAccountByEmail($request->email);

        if (!$accountData) {
            return response()->json([
                'success' => false,
                'message' => 'Compte non trouvé'
            ], 404);
        }

        $account = $accountData['model'];
        $passwordField = $accountData['password_field'];

        if ($account->password_reset_token !== null) {
            return response()->json([
                'success' => false,
                'message' => 'Veuillez d’abord valider le code de réinitialisation'
            ], 400);
        }

        // 🔐 Hash sur le BON champ
        $account->$passwordField = Hash::make($request->password);
        $account->save();

        return response()->json([
            'success' => true,
            'message' => 'Mot de passe réinitialisé avec succès'
        ], 200);

    } catch (QueryException $e) {
        return response()->json([
            'success' => false,
            'message' => 'Erreur lors de la réinitialisation du mot de passe',
            'erreur' => $e->getMessage()
        ], 500);
    }
}



}
