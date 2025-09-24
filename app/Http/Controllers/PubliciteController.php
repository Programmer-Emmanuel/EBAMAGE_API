<?php

namespace App\Http\Controllers;

use App\Models\Publicite;
use Illuminate\Http\Request;

class PubliciteController extends Controller
{
    // ----------------------------
    // Ajouter une publicité pour les clients
    // ----------------------------
    public function ajoutClient(Request $request)
    {
        return $this->ajouterPublicite($request, 'client');
    }

    // ----------------------------
    // Ajouter une publicité pour les boutiques
    // ----------------------------
    public function ajoutBoutique(Request $request)
    {
        return $this->ajouterPublicite($request, 'boutique');
    }

    // ----------------------------
    // Ajouter une publicité pour tout le monde
    // ----------------------------
    public function ajoutToutLeMonde(Request $request)
    {
        return $this->ajouterPublicite($request, 'all');
    }

    // Fonction générique d'ajout
    private function ajouterPublicite(Request $request, string $role)
    {
        $request->validate([
            'image' => 'required|string',
        ]);

        $pub = Publicite::create([
            'image' => $request->image,
            'role'  => $role
        ]);

        return response()->json([
            'success' => true,
            'data'    => $pub,
            'message' => "Publicité créée avec succès pour $role"
        ], 201);
    }

    // ----------------------------
    // Afficher toutes les publicités des clients
    // ----------------------------
    public function publicitesClients()
    {
        $pubs = Publicite::where('role', 'client')->orWhere('role', 'all')->get();

        return response()->json([
            'success' => true,
            'data'    => $pubs,
            'message' => 'Publicités des clients récupérées'
        ]);
    }

    // ----------------------------
    // Afficher toutes les publicités des boutiques
    // ----------------------------
    public function publicitesBoutiques()
    {
        $pubs = Publicite::where('role', 'boutique')->orWhere('role', 'all')->get();

        return response()->json([
            'success' => true,
            'data'    => $pubs,
            'message' => 'Publicités des boutiques récupérées'
        ]);
    }

    // ----------------------------
    // Afficher toutes les publicités (y compris all)
    // ----------------------------
    public function publicitesAll()
    {
        $pubs = Publicite::all();

        return response()->json([
            'success' => true,
            'data'    => $pubs,
            'message' => 'Toutes les publicités récupérées'
        ]);
    }

    // ----------------------------
    // Modifier une publicité
    // ----------------------------
    public function modifier(Request $request, $id)
    {
        $pub = Publicite::find($id);

        if (!$pub) {
            return response()->json([
                'success' => false,
                'message' => 'Publicité non trouvée'
            ], 404);
        }

        $request->validate([
            'image' => 'sometimes|string',
            'role'  => 'sometimes|in:client,boutique,all',
        ]);

        $pub->update($request->only(['image', 'role']));

        return response()->json([
            'success' => true,
            'data'    => $pub,
            'message' => 'Publicité modifiée avec succès'
        ]);
    }

    // ----------------------------
    // Supprimer une publicité
    // ----------------------------
    public function supprimer($id)
    {
        $pub = Publicite::find($id);

        if (!$pub) {
            return response()->json([
                'success' => false,
                'message' => 'Publicité non trouvée'
            ], 404);
        }

        $pub->delete();

        return response()->json([
            'success' => true,
            'message' => 'Publicité supprimée avec succès'
        ]);
    }
}
