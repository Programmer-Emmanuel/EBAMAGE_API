<?php

namespace App\Http\Controllers;

use App\Models\Publicite;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Vinkla\Hashids\Facades\Hashids;

class PubliciteController extends Controller
{
    private const MAX_FILE_SIZE = 5120; // 5MB en KB
    private const ALLOWED_MIMES = ['jpeg', 'png', 'jpg', 'gif', 'webp'];
    private const ALLOWED_ROLES = ['client', 'boutique', 'all'];

    // ----------------------------
    // Ajouter une publicité pour les clients
    // ----------------------------
    public function ajoutClient(Request $request): JsonResponse
    {
        return $this->ajouterPublicite($request, 'client');
    }

    // ----------------------------
    // Ajouter une publicité pour les boutiques
    // ----------------------------
    public function ajoutBoutique(Request $request): JsonResponse
    {
        return $this->ajouterPublicite($request, 'boutique');
    }

    // ----------------------------
    // Ajouter une publicité pour tout le monde
    // ----------------------------
    public function ajoutToutLeMonde(Request $request): JsonResponse
    {
        return $this->ajouterPublicite($request, 'all');
    }

    // Fonction générique d'ajout
    private function ajouterPublicite(Request $request, string $role): JsonResponse
{
    try {
        // ✅ Validation des données
        $validator = Validator::make($request->all(), [
            'images' => 'required|image|mimes:' . implode(',', self::ALLOWED_MIMES) . '|max:' . self::MAX_FILE_SIZE,
        ], [
            'images.required' => 'Une image est requise',
            'images.image' => 'Le fichier doit être une image valide',
            'images.mimes' => 'L\'image doit être de type: jpeg, png, jpg, gif, webp',
            'images.max' => 'L\'image ne doit pas dépasser 5MB'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        // ✅ Upload de l'image vers ImgBB
        $image = $request->file('images');
        $imageUrl = $this->uploadImageToHosting($image);

        // ✅ Création de la publicité (champ images reste JSON)
        $pub = Publicite::create([
            'images' => json_encode([$imageUrl]), // on garde le champ images sous forme de tableau JSON
            'role' => $role,
        ]);

        Log::info("✅ Publicité créée avec succès", [
            'id' => $pub->id,
            'role' => $role,
        ]);

        return response()->json([
            'success' => true,
            'data' => $this->formatPubliciteData($pub),
            'message' => "Publicité créée avec succès pour " . $this->getRoleLabel($role)
        ], 201);

    } catch (\Illuminate\Http\Client\ConnectionException $e) {
        Log::error('Erreur de connexion lors de l\'upload de l\'image: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Erreur de connexion lors de l\'upload de l\'image. Veuillez réessayer.'
        ], 503);
    } catch (\Exception $e) {
        Log::error('Erreur lors de l\'ajout de la publicité: ' . $e->getMessage(), [
            'role' => $role,
            'trace' => $e->getTraceAsString()
        ]);
        return response()->json([
            'success' => false,
            'message' => 'Une erreur interne est survenue lors de la création de la publicité.'
        ], 500);
    }
}

    // ----------------------------
    // Upload d'image vers ImgBB avec gestion d'erreur améliorée
    // ----------------------------
    private function uploadImageToHosting($image): string
    {
        $apiKey = env('IMGBB_API_KEY', '9b1ab6564d99aab6418ad53d3451850b');

        if (!$image->isValid()) {
            throw new \Exception("Fichier image non valide. Code d'erreur: " . $image->getError());
        }

        // Vérification supplémentaire de la taille
        if ($image->getSize() > (self::MAX_FILE_SIZE * 1024)) {
            throw new \Exception("La taille de l'image dépasse la limite autorisée de " . self::MAX_FILE_SIZE . "KB");
        }

        try {
            $imageContent = base64_encode(file_get_contents($image->getRealPath()));
            
            if ($imageContent === false) {
                throw new \Exception("Impossible de lire le contenu de l'image");
            }

            $response = Http::timeout(30)
                ->retry(3, 1000) // 3 tentatives avec 1 seconde d'intervalle
                ->asForm()
                ->post('https://api.imgbb.com/1/upload', [
                    'key' => $apiKey,
                    'image' => $imageContent,
                ]);

            if ($response->successful()) {
                $responseData = $response->json();
                
                if (isset($responseData['data']['url'])) {
                    return $responseData['data']['url'];
                }
                
                if (isset($responseData['error'])) {
                    throw new \Exception("Erreur ImgBB: " . json_encode($responseData['error']));
                }
                
                throw new \Exception("Réponse ImgBB invalide: URL non trouvée");
            }

            $statusCode = $response->status();
            $errorMessage = "Erreur HTTP $statusCode";
            
            if ($response->json('error.message')) {
                $errorMessage .= " - " . $response->json('error.message');
            }

            throw new \Exception($errorMessage);

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            throw new \Exception("Timeout ou erreur de connexion avec le service d'upload d'images");
        } catch (\Exception $e) {
            Log::error('Erreur détaillée lors de l\'upload ImgBB: ' . $e->getMessage());
            throw new \Exception("Échec de l'upload de l'image: " . $e->getMessage());
        }
    }

    // ----------------------------
    // Afficher une publicité par hashid
    // ----------------------------
    public function voirParHashid($hashid): JsonResponse
    {
        try {
            $id = $this->decodeHashid($hashid);
            if (!$id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Identifiant de publicité invalide'
                ], 400);
            }

            $pub = Publicite::find($id);
            if (!$pub) {
                return response()->json([
                    'success' => false,
                    'message' => 'Publicité non trouvée'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $this->formatPubliciteData($pub),
                'message' => 'Publicité récupérée avec succès'
            ], 200);

        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération de la publicité par hashid: ' . $e->getMessage(), [
                'hashid' => $hashid,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération de la publicité'
            ], 500);
        }
    }

    // ----------------------------
    // Afficher toutes les publicités des clients
    // ----------------------------
public function publicitesClients(): JsonResponse
{
    try {
        $pubs = Publicite::whereIn('role', ['client', 'all'])
                        ->orderBy('created_at', 'desc')
                        ->get();

        $data = $pubs->map(function ($pub) {
            // 🔹 Décodage JSON (si c’est une chaîne)
            $images = is_array($pub->images)
                ? $pub->images
                : json_decode($pub->images, true);

            // 🔹 Récupérer la première image si tableau
            $image_pub = is_array($images) && count($images) > 0
                ? $images[0]
                : $pub->images;

            return [
                "id" => Hashids::encode($pub->id),
                "image_pub" => $image_pub, // 🔸 URL directe sans crochets
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
            'message' => 'Publicités des clients récupérées avec succès'
        ], 200);

    } catch (\Exception $e) {
        Log::error('Erreur lors de la récupération des publicités clients: ' . $e->getMessage(), [
            'trace' => $e->getTraceAsString()
        ]);

        return response()->json([
            'success' => false,
            'data' => [],
            'message' => 'Erreur lors de la récupération des publicités'
        ], 500);
    }
}


    // ----------------------------
    // Afficher toutes les publicités des boutiques
    // ----------------------------
    public function publicitesBoutiques(): JsonResponse
    {
        try {
            $pubs = Publicite::whereIn('role', ['boutique', 'all'])
                            ->orderBy('created_at', 'desc')
                            ->get();

            return response()->json([
                'success' => true,
                'data' => $this->formatPublicitesData($pubs),
                'count' => $pubs->count(),
                'message' => 'Publicités des boutiques récupérées avec succès'
            ], 200);

        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération des publicités boutiques: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'data' => [],
                'count' => 0,
                'message' => 'Erreur lors de la récupération des publicités'
            ], 500);
        }
    }

    // ----------------------------
    // Afficher les publicités pour tous
    // ----------------------------
    public function publicitesAll(): JsonResponse
    {
        try {
            $pubs = Publicite::where('role', 'all')
                            ->orderBy('created_at', 'desc')
                            ->get();

            return response()->json([
                'success' => true,
                'data' => $this->formatPublicitesData($pubs),
                'count' => $pubs->count(),
                'message' => 'Publicités pour tous récupérées avec succès'
            ], 200);

        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération des publicités "all": ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'data' => [],
                'count' => 0,
                'message' => 'Erreur lors de la récupération des publicités'
            ], 500);
        }
    }

    // ----------------------------
    // Afficher toutes les publicités organisées par catégorie
    // ----------------------------
    public function publicites(): JsonResponse
{
    try {
        $pubs = Publicite::orderBy('created_at', 'desc')->get();

        $categorizedPubs = [
            'all' => $pubs->where('role', 'all')->map(fn($pub) => $this->formatSimplePubliciteData($pub))->values(),
            'client' => $pubs->where('role', 'client')->map(fn($pub) => $this->formatSimplePubliciteData($pub))->values(),
            'boutique' => $pubs->where('role', 'boutique')->map(fn($pub) => $this->formatSimplePubliciteData($pub))->values(),
        ];

        $counts = [
            'all' => $categorizedPubs['all']->count(),
            'client' => $categorizedPubs['client']->count(),
            'boutique' => $categorizedPubs['boutique']->count(),
            'total' => $pubs->count()
        ];

        Log::info('Publicités récupérées avec succès', $counts);

        return response()->json([
            'success' => true,
            'data' => $categorizedPubs,
            'message' => 'Toutes les publicités récupérées avec succès'
        ], 200);

    } catch (\Exception $e) {
        Log::error('Erreur lors de la récupération de toutes les publicités: ' . $e->getMessage(), [
            'trace' => $e->getTraceAsString()
        ]);
        
        return response()->json([
            'success' => false,
            'data' => [
                'all' => [],
                'client' => [],
                'boutique' => []
            ],
            'message' => 'Erreur lors de la récupération des publicités'
        ], 500);
    }
}

    // ----------------------------
    // Modifier une publicité
    // ----------------------------
    public function modifier(Request $request, $hashid): JsonResponse
    {
        try {
            $id = $this->decodeHashid($hashid);
            if (!$id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Identifiant de publicité invalide'
                ], 400);
            }

            $pub = Publicite::find($id);
            if (!$pub) {
                return response()->json([
                    'success' => false,
                    'message' => 'Publicité non trouvée'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'images' => 'sometimes|array',
                'images.*' => 'sometimes|image|mimes:' . implode(',', self::ALLOWED_MIMES) . '|max:' . self::MAX_FILE_SIZE,
                'role' => 'sometimes|in:' . implode(',', self::ALLOWED_ROLES),
            ], [
                'images.array' => 'Le champ images doit être un tableau',
                'images.*.image' => 'Chaque fichier doit être une image valide',
                'images.*.mimes' => 'Les images doivent être de type: jpeg, png, jpg, gif, webp',
                'images.*.max' => 'Chaque image ne doit pas dépasser 5MB'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur de validation',
                    'errors' => $validator->errors()
                ], 422);
            }

            $data = $request->only(['role']);
            
            if ($request->hasFile('images')) {
                $uploadedImages = [];
                foreach ($request->file('images') as $image) {
                    $imageUrl = $this->uploadImageToHosting($image);
                    $uploadedImages[] = $imageUrl;
                }
                $data['images'] = json_encode($uploadedImages);
            }

            $pub->update($data);

            Log::info("Publicité modifiée avec succès", [
                'id' => $pub->id,
                'role' => $pub->role,
                'images_updated' => $request->hasFile('images')
            ]);

            return response()->json([
                'success' => true,
                'data' => $this->formatPubliciteData($pub),
                'message' => 'Publicité modifiée avec succès'
            ], 200);

        } catch (\Exception $e) {
            Log::error('Erreur lors de la modification de la publicité: ' . $e->getMessage(), [
                'hashid' => $hashid,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la modification de la publicité'
            ], 500);
        }
    }

    // ----------------------------
    // Supprimer une publicité
    // ----------------------------
    public function supprimer($hashid): JsonResponse
    {
        try {
            $id = $this->decodeHashid($hashid);
            if (!$id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Identifiant de publicité invalide'
                ], 400);
            }

            $pub = Publicite::find($id);
            if (!$pub) {
                return response()->json([
                    'success' => false,
                    'message' => 'Publicité non trouvée'
                ], 404);
            }

            $pub->delete();

            Log::info("Publicité supprimée avec succès", ['id' => $id]);

            return response()->json([
                'success' => true,
                'message' => 'Publicité supprimée avec succès'
            ], 200);

        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('Erreur base de données lors de la suppression: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression de la publicité'
            ], 500);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la suppression de la publicité: ' . $e->getMessage(), [
                'hashid' => $hashid,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression de la publicité'
            ], 500);
        }
    }

    // ----------------------------
    // Méthodes utilitaires
    // ----------------------------

    /**
     * Décoder un hashid avec gestion d'erreur
     */
    private function decodeHashid(string $hashid): ?int
    {
        try {
            $ids = Hashids::decode($hashid);
            return !empty($ids) ? $ids[0] : null;
        } catch (\Exception $e) {
            Log::warning('Erreur lors du décodage du hashid: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Formater les données d'une publicité avec hashid
     */
    private function formatPubliciteData(Publicite $pub): array
    {
        $images = json_decode($pub->images, true) ?? [];
        
        return [
            'hashid' => Hashids::encode($pub->id),
            'images' => $images,
            'images_count' => count($images),
            'role' => $pub->role,
            'role_label' => $this->getRoleLabel($pub->role),
            'created_at' => $pub->created_at->toISOString(),
            'updated_at' => $pub->updated_at->toISOString()
        ];
    }

    /**
     * Format simplifié pour les listes groupées
     */
    private function formatSimplePubliciteData(Publicite $pub): array
    {
        $images = json_decode($pub->images, true) ?? [];
        
        return [
            'hashid' => Hashids::encode($pub->id),
            'images' => $images,
            'images_count' => count($images),
            'role' => $pub->role,
        ];
    }

    /**
     * Formater une collection de publicités
     */
    private function formatPublicitesData($pubs): array
    {
        return $pubs->map(function ($pub) {
            return $this->formatPubliciteData($pub);
        })->toArray();
    }

    /**
     * Obtenir le libellé du rôle
     */
    private function getRoleLabel(string $role): string
    {
        $labels = [
            'client' => 'clients',
            'boutique' => 'boutiques',
            'all' => 'tout le monde'
        ];

        return $labels[$role] ?? $role;
    }
}