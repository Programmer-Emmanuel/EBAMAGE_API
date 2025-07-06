<?php

namespace App\Http\Controllers;

use App\Mail\OtpMail;
use App\Models\Boutique;
use App\Models\Livreur;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use PDOException;

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

        // 4. Création du compte client
        $client = new User();
        $client->nom_clt = $validatedData['nom_clt'];
        $client->email_clt = $validatedData['email_clt'];
        $client->tel_clt = $validatedData['tel_clt'];
        $client->password_clt = Hash::make($validatedData['password_clt']);
        $client->solde_tdl = 0;
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
            // 'erreur' => $e->getMessage() // Décommente uniquement en mode debug
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
            'data' => [
                    'nom_clt' => $client->nom_clt,
                    'email_clt' => $client->email_clt,
                    'tel_clt' => $client->tel_clt,
                'token' => $token,
            ]
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
                'message' => 'Mot de passe incorrect.',
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
        $boutique->password_btq = Hash::make($validated['password_btq']);
        $boutique->solde_tdl = 0;
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


}
