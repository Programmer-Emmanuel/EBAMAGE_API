<?php

namespace App\Http\Controllers;

use App\Models\User;
use Exception;
use Illuminate\Database\QueryException;
use Vinkla\Hashids\Facades\Hashids;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ClientController extends Controller
{
    public function update_image(Request $request, $hashid)
{
    $request->validate([
        'image_clt' => 'required|image|mimes:jpeg,png,jpg|max:2048',
    ], [
        'image_clt.required' => 'L’image est obligatoire.',
        'image_clt.image' => 'Le fichier doit être une image.',
        'image_clt.mimes' => 'L’image doit être au format jpeg, png ou jpg.',
        'image_clt.max' => 'La taille maximale de l’image est 2 Mo.',
    ]);

    try {
        $image_client = $this->uploadImageToHosting($request->file('image_clt'));

        $id = Hashids::decode($hashid)[0] ?? null;

        if (!$id) {
            return response()->json(['message' => 'ID invalide'], 400);
        }

        $client = User::find($id);

        if (!$client) {
            return response()->json([
                'success' => false,
                'message' => 'Client introuvable.'
            ], 404);
        }

        if ($client->otp_expires_at !== null) {
            return response()->json([
                'success' => false,
                'message' => 'Client non autorisé à modifier l’image.'
            ]);
        }

        $client->image_clt = $image_client;
        $client->save();

        return response()->json([
            'success' => true,
            'data' => $client,
            'message' => 'Image client mise à jour avec succès.'
        ]);
    } catch (QueryException $e) {
        return response()->json([
            'success' => false,
            'message' => 'Erreur lors de la mise à jour de l’image du client.',
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

    public function delete_image($hashid)
{
    try {
        $id = Hashids::decode($hashid)[0] ?? null;

        if (!$id) {
            return response()->json([
                'success' => false,
                'message' => 'ID invalide.'
            ], 400);
        }

        $client = User::find($id);

        if (!$client) {
            return response()->json([
                'success' => false,
                'message' => 'Client introuvable.'
            ], 404);
        }

        if ($client->otp_expires_at !== null) {
            return response()->json([
                'success' => false,
                'message' => 'Client non autorisé à supprimer l’image.'
            ]);
        }

        $client->image_clt = null;
        $client->save();

        return response()->json([
            'success' => true,
            'data' => $client,
            'message' => 'Image client supprimée avec succès.'
        ]);

    } catch (QueryException $e) {
        return response()->json([
            'success' => false,
            'message' => 'Erreur lors de la suppression de l’image du client.',
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
