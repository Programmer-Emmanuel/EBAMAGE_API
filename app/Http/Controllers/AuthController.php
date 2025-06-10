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
    public function register_clt(Request $request){

        //Request validate
        $request->validate([
            'nom_clt' => 'required',
            'pren_clt' => 'required',
            'email_clt' => 'required|unique:users|email',
            'tel_clt' => 'required|digits:10|unique:users',
            'password_clt' => 'required|min:6'
        ], [
            'nom_clt.required' => 'Le nom du client est obligatoire.',
            'pren_clt.required' => 'Le prénom du client est obligatoire.',
            'email_clt.required' => 'L’email du client est obligatoire.',
            'email_clt.unique' => 'L’email du client doit être unique.',
            'email_clt.email' => 'L’adresse email du client n’est pas valide.',
            'tel_clt.required' => 'Le numéro de téléphone est obligatoire.',
            'tel_clt.digits' => 'Le numéro de téléphone doit contenir exactement 10 chiffres.',
            'tel_clt.unique' => 'Ce numéro de téléphone est déjà utilisé.',
            'password_clt.required' => 'Le mot de passe est obligatoire.',
            'password_clt.min' => 'Le mot de passe doit contenir au moins 6 caractères.',
        ]);


        //Code OTP
        $code_otp = rand(1000, 9999);

        try{
            $client = new User();
            $client->nom_clt = $request->nom_clt;
            $client->pren_clt = $request->pren_clt;
            $client->email_clt = $request->email_clt;
            $client->tel_clt = $request->tel_clt;
            // $client->image_clt = $request->image_clt;
            $client->password_clt = Hash::make($request->password_clt);
            $client->solde_tdl = 0;
            $client->code_otp = $code_otp;
            $client->otp_expires_at = now()->addMinutes(5);
            $client->save();

            //Token du client
            $token = $client->createToken('client-token')->plainTextToken;

            // Envoi email OTP
            Mail::to($client->email_clt)->send(new OtpMail($code_otp));

            return response()->json([
                'success' => true,
                'data'=> $client,
                'message' => 'Client enregistré avec succès.',
                'token' => $token
            ]);
        }
        catch(QueryException $e){
            return response()->json([
                'success' => false,
                'message' => 'Enregistrement du client échoué',
                'erreur' => $e->getMessage()
            ]);
        }


    }


    //VERIFICATION OTP CLIENT
    public function verifyOtp(Request $request){
        $request->validate([
            'email_clt' => 'required|email',
            'code_otp' => 'required|digits:4',
        ]);

        $client = User::where('email_clt', $request->email_clt)->first();

        if (!$client) {
            return response()->json(['success' => false, 'message' => 'Utilisateur non trouvé']);
        }

        if ($client->code_otp !== $request->code_otp) {
            return response()->json(['success' => false, 'message' => 'Code OTP invalide']);
        }

        if ($client->otp_expires_at->isPast()) {
            return response()->json(['success' => false, 'message' => 'Le code OTP a expiré']);
        }

        $client->email_verified_at = now();
        $client->code_otp = null;
        $client->otp_expires_at = null;
        $client->save();

        return response()->json(['success' => true, 'message' => 'OTP vérifié avec succès']);
    }


    //CONNEXION CLIENT
    public function login_clt(Request $request){

        $request->validate([
            'email_clt' => 'required|email',
            'password_clt' => 'required|min:6'
        ], [
            'email_clt.required' => 'L’email du client est obligatoire.',
            'password_clt.required' => 'Le mot de passe est obligatoire.',
            'password_clt.min' => 'Le mot de passe doit contenir au moins 6 caractères.',
        ]);

        $client = User::where('email_clt', $request->email_clt)->first();
        if($client && Hash::check($request->password_clt, $client->password_clt) && $client->otp_expires_at == null){
            //Token du client
            $token = $client->createToken('client-token')->plainTextToken;

            return response()->json([
                'success' => true,
                'data' => $client,
                'message' => 'Client connecté avec succès',
                'token' => $token
            ]);
        }
        else{
            return response()->json([
                'success' => false,
                'message' => 'Client non connecté.'
            ]);
        }
    }

    //INSCRIPTIOIN BOUTIQUE
    public function register_btq(Request $request){
        $request->validate([
            'nom_btq' => 'required',
            'email_btq' => 'required|email|unique:boutiques',
            'tel_btq' => 'required|digits:10|unique:boutiques',
            'password_btq' => 'required'
        ],[
            'nom_btq.required' => 'Le nom de la boutique est obligatoire.',
            'email_btq.required' => 'L’adresse email de la boutique est obligatoire.',
            'email_btq.email' => 'L’adresse email n’est pas valide.',
            'email_btq.unique' => 'Cette adresse email est déjà utilisée.',
            'tel_btq.required' => 'Le numéro de téléphone est obligatoire.',
            'tel_btq.digits' => 'Le numéro de téléphone doit contenir exactement 10 chiffres.',
            'tel_btq.unique' => 'Ce numéro de téléphone est déjà utilisé.',
            'password_btq.required' => 'Le mot de passe est obligatoire.'
        ]);

        try{
            $boutique = new Boutique();
            $boutique->nom_btq = $request->nom_btq;
            $boutique->email_btq = $request->email_btq;
            $boutique->tel_btq = $request->tel_btq;
            $boutique->password_btq = Hash::make($request->password_btq);
            $boutique->solde_tdl = 0;
            $boutique->save();

            unset($boutique->password_btq);

            $token = $boutique->createToken('boutique-token')->plainTextToken;

            return response()->json([
                'success' => true,
                'data' => $boutique,
                'message' => 'Boutique enregistré avec succès' ,
                'token' => $token
            ]);
        }
        catch(QueryException $e){
            return response()->json([
                'success' => false,
                'message' => 'Boutique non enregistré.',
                'erreur' => $e->getMessage()
            ]);
        }
    }

    //CONNEXION BOUTIQUE
    public function login_btq(Request $request){

        $request->validate([
            'email_btq' => 'required|email',
            'password_btq' => 'required'
        ],[
            'email_btq.required' => 'L’adresse email de la boutique est obligatoire.',
            'email_btq.email' => 'L’adresse email n’est pas valide.',
            'email_btq.unique' => 'Cette adresse email est déjà utilisée.',
            'password_btq.required' => 'Le mot de passe est obligatoire.'
        ]);

        $boutique = Boutique::where('email_btq', $request->email_btq)->first();

        $token = $boutique->createToken('boutique-token')->plainTextToken;

        if($boutique && Hash::check($request->password_btq, $boutique->password_btq)){
            return response()->json([
                'success' => true,
                'data' => $boutique,
                'message' => 'Boutique connecté',
                'token' => $token
            ]);
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
    //         $livreur->otp_expires_at = now()->addMinutes(5);
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
