<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Boutique;
use App\Models\Notification;
use Google\Auth\Credentials\ServiceAccountCredentials;
use Google\Auth\OAuth2;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Vinkla\Hashids\Facades\Hashids;

class NotificationController extends Controller
{
    // ----------------------------
    // 1. Enregistrer ou mettre à jour un device token
    // ----------------------------
    public function recupereDeviceToken(Request $request)
{
    $request->validate([
        'hashid' => 'required|string',
        'device_token' => 'required|string',
    ]);

    try {
        // 🔒 Récupération de l'utilisateur connecté
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Utilisateur non authentifié.'
            ], 401);
        }

        // 🔍 Décodage du hashid reçu
        $decoded = Hashids::decode($request->hashid);

        if (empty($decoded)) {
            return response()->json([
                'success' => false,
                'message' => 'Identifiant invalide.'
            ], 400);
        }

        $id = $decoded[0];

        // 🧩 Vérifie le type d’utilisateur connecté (client ou boutique)
        if ($user instanceof \App\Models\User) {
            if ($user->id !== $id) {
                return response()->json([
                    'success' => false,
                    'message' => 'L\'ID ne correspond pas à l\'utilisateur connecté.'
                ], 403);
            }

            $user->update([
                'device_token' => $request->device_token
            ]);

            return response()->json([
                'success' => true,
                'device_token' => $request->device_token,
                'user_type' => 'client',
                'message' => 'Device Token enregistré avec succès pour le client.'
            ]);
        }

        if ($user instanceof \App\Models\Boutique) {
            if ($user->id !== $id) {
                return response()->json([
                    'success' => false,
                    'message' => 'L\'ID ne correspond pas à la boutique connectée.'
                ], 403);
            }

            $user->update([
                'device_token' => $request->device_token
            ]);

            return response()->json([
                'success' => true,
                'device_token' => $request->device_token,
                'user_type' => 'boutique',
                'message' => 'Device Token enregistré avec succès pour la boutique.'
            ]);
        }

        // 🚫 Si l'utilisateur connecté n'est ni client ni boutique
        return response()->json([
            'success' => false,
            'message' => 'Type d’utilisateur non reconnu.'
        ], 400);

    } catch (\Exception $e) {
        Log::error('Erreur lors de l\'enregistrement du token: ' . $e->getMessage());

        return response()->json([
            'success' => false,
            'message' => 'Erreur lors de l\'enregistrement du Device Token',
            'erreur' => $e->getMessage()
        ], 500);
    }
}


    // ----------------------------
    // 2. Envoyer notification à un client via device token
    // ----------------------------
    public function notification_client(Request $request)
    {
        $request->validate([
            'device_token' => 'required|string',
            'title' => 'required|string',
            'message' => 'required|string',
            'type' => 'nullable|string',
        ]);

        $user = User::where('device_token', $request->device_token)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'data' => null,
                'message' => 'Aucun client trouvé avec ce device token'
            ], 404);
        }

        try {
            $response = $this->sendFcmNotification(
                $request->device_token,
                $request->title,
                $request->message,
                $request->type
            );

            if ($response) {
                Notification::create([
                    'user_id' => $user->id,
                    'device_token' => $request->device_token,
                    'title' => $request->title,
                    'message' => $request->message,
                    'type' => $request->type,
                ]);

                return response()->json([
                    'success' => true,
                    'data' => [
                        'client' => [
                            'hashid' => Hashids::encode($user->id),
                            'nom_clt' => $user->nom_clt,
                            'email_clt' => $user->email_clt,
                            'device_token' => $user->device_token,
                        ]
                    ],
                    'message' => 'Notification envoyée au client avec succès'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur lors de l\'envoi FCM. Device_Token invalide.'
                ], 500);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'envoi de la notification: ' . $e->getMessage()
            ], 500);
        }
    }

    // ----------------------------
    // 3. Envoyer notification à une boutique via device token
    // ----------------------------
    public function notification_boutique(Request $request)
    {
        $request->validate([
            'device_token' => 'required|string',
            'title' => 'required|string',
            'message' => 'required|string',
            'type' => 'nullable|string',
        ]);

        $boutique = Boutique::where('device_token', $request->device_token)->first();

        if (!$boutique) {
            return response()->json([
                'success' => false,
                'data' => null,
                'message' => 'Aucune boutique trouvée avec ce device token'
            ], 404);
        }

        try {
            $response = $this->sendFcmNotification(
                $request->device_token,
                $request->title,
                $request->message,
                $request->type
            );

            if ($response) {
                Notification::create([
                    'boutique_id' => $boutique->id,
                    'device_token' => $request->device_token,
                    'title' => $request->title,
                    'message' => $request->message,
                    'type' => $request->type,
                ]);

                return response()->json([
                    'success' => true,
                    'data' => [
                        'boutique' => [
                            'hashid' => Hashids::encode($boutique->id),
                            'nom_btq' => $boutique->nom_btq,
                            'email_btq' => $boutique->email_btq,
                            'device_token' => $boutique->device_token,
                        ]
                    ],
                    'message' => 'Notification envoyée à la boutique avec succès'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur lors de l\'envoi FCM. Device_Token invalide.'
                ], 500);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'data' => null,
                'message' => 'Erreur lors de l\'envoi de la notification: ' . $e->getMessage()
            ], 500);
        }
    }

    // ----------------------------
    // 4. Lister tous les utilisateurs et boutiques avec leurs device tokens
    // ----------------------------
    public function liste_users()
    {
        try {
            $users = User::all()->map(function($user) {
                return [
                    'hashid' => Hashids::encode($user->id),
                    'nom_clt' => $user->nom_clt,
                    'email_clt' => $user->email_clt,
                    'tel_clt' => $user->tel_clt,
                    'image_clt' => $user->image_clt,
                    'solde_tdl' => $user->solde_tdl,
                    'device_token' => $user->device_token,
                ];
            });

            $boutiques = Boutique::all()->map(function($btq) {
                return [
                    'hashid' => Hashids::encode($btq->id),
                    'nom_btq' => $btq->nom_btq,
                    'email_btq' => $btq->email_btq,
                    'tel_btq' => $btq->tel_btq,
                    'solde_tdl' => $btq->solde_tdl,
                    'device_token' => $btq->device_token,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'users' => $users,
                    'boutiques' => $boutiques
                ],
                'message' => 'Liste des utilisateurs et boutiques récupérée avec succès'
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur liste users: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'data' => null,
                'message' => 'Erreur lors de la récupération des données'
            ], 500);
        }
    }

    // ----------------------------
    // 5. Lister les notifications de l'utilisateur/boutique connecté
    // ----------------------------
    public function notifications()
    {
        try {
            $user = auth('client')->user();
            $boutique = auth('boutique')->user();

            if ($user) {
                $notifications = Notification::where('user_id', $user->id)
                    ->orderBy('created_at', 'desc')
                    ->get(['id', 'title', 'message', 'type', 'created_at']);
            } elseif ($boutique) {
                $notifications = Notification::where('boutique_id', $boutique->id)
                    ->orderBy('created_at', 'desc')
                    ->get(['id', 'title', 'message', 'type', 'created_at']);
            } else {
                return response()->json([
                    'success' => false,
                    'data' => null,
                    'message' => 'Utilisateur non authentifié'
                ], 401);
            }

            // Transformer chaque notification
            $data = $notifications->map(function ($notif) {
                return [
                    'hashid' => Hashids::encode($notif->id),
                    'title' => $notif->title,
                    'message' => $notif->message,
                    'type' => $notif->type,
                    'created_at' => $notif->created_at->copy()->setTimezone('UTC')->format('Y-m-d\TH:i:s\Z'),
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $data,
                'message' => 'Notifications récupérées avec succès'
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur récupération notifications: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'data' => null,
                'message' => 'Erreur lors de la récupération des notifications',
                'erreur' => $e->getMessage()
            ], 500);
        }
    }

    // ----------------------------
    // 6. Fonction interne pour envoyer la notification FCM
    // ----------------------------
private function sendFcmNotification($deviceToken, $title, $body, $type = null)
{
    if (empty($deviceToken)) {
        Log::warning("⚠️ Aucun device token fourni pour la notification.");
        return ['success' => false, 'error' => 'Token vide'];
    }

    // ✅ Cas 1 : Token Expo (React Native)
    if (str_starts_with($deviceToken, 'ExponentPushToken')) {
        try {
            $data = [
                'to' => $deviceToken,
                'sound' => 'default',
                'title' => $title,
                'body' => $body,
                'data' => [
                    'type' => $type ?? 'default',
                ],
            ];

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->timeout(10)->post('https://api.expo.dev/v2/push/send', $data);

            if ($response->failed()) {
                Log::error('❌ Erreur Expo: ' . $response->body());
                return ['success' => false, 'error' => 'Erreur HTTP Expo: ' . $response->status()];
            }

            $res = $response->json();
            if (isset($res['data'][0]['status']) && $res['data'][0]['status'] === 'ok') {
                Log::info("✅ Notification Expo envoyée avec succès", ['to' => $deviceToken]);
                return ['success' => true];
            }

            $error = $res['data'][0]['message'] ?? 'Erreur inconnue Expo';
            Log::error("❌ Erreur Expo: $error", ['response' => $res]);
            return ['success' => false, 'error' => $error];

        } catch (\Throwable $e) {
            Log::error('Erreur lors de l\'envoi via Expo : ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    // ✅ Cas 2 : Token FCM (Firebase)
    $projectId = env('FCM_PROJECT_ID');
    
    try {
        // ✅ OPTION 1: Utilisation des variables d'environnement
        $jsonKey = [
            'type' => env('FIREBASE_TYPE'),
            'project_id' => env('FIREBASE_PROJECT_ID'),
            'private_key_id' => env('FIREBASE_PRIVATE_KEY_ID'),
            'private_key' => str_replace("\\n", "\n", env('FIREBASE_PRIVATE_KEY')),
            'client_email' => env('FIREBASE_CLIENT_EMAIL'),
            'client_id' => env('FIREBASE_CLIENT_ID'),
            'auth_uri' => env('FIREBASE_AUTH_URI'),
            'token_uri' => env('FIREBASE_TOKEN_URI'),
            'auth_provider_x509_cert_url' => env('FIREBASE_AUTH_PROVIDER_X509_CERT_URL'),
            'client_x509_cert_url' => env('FIREBASE_CLIENT_X509_CERT_URL'),
            'universe_domain' => env('FIREBASE_UNIVERSE_DOMAIN')
        ];

        // Vérifier que toutes les variables nécessaires sont présentes
        if (empty($jsonKey['private_key']) || empty($jsonKey['client_email'])) {
            Log::error('❌ Configuration Firebase manquante');
            return ['success' => false, 'error' => 'Configuration Firebase manquante'];
        }

        $privateKey = $jsonKey['private_key'];

        $oauth2 = new OAuth2([
            'audience' => 'https://oauth2.googleapis.com/token',
            'issuer' => $jsonKey['client_email'],
            'signingAlgorithm' => 'RS256',
            'signingKey' => $privateKey,
            'tokenCredentialUri' => 'https://oauth2.googleapis.com/token',
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
        ]);

        $accessToken = $oauth2->fetchAuthToken()['access_token'];

        // ✅ Corps du message FCM
        $payload = [
            'message' => [
                'token' => $deviceToken,
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                ],
                'data' => [
                    'type' => $type ?? 'default'
                ],
            ],
        ];

        $url = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $accessToken,
            'Content-Type' => 'application/json',
        ])->post($url, $payload);

        if ($response->failed()) {
            Log::error('❌ Erreur FCM: ' . $response->body());
            return ['success' => false, 'error' => 'Erreur FCM: ' . $response->status()];
        }

        Log::info("✅ Notification FCM envoyée avec succès", ['to' => $deviceToken]);
        return ['success' => true, 'response' => $response];

    } catch (\Throwable $e) {
        Log::error('❌ Erreur lors de l\'envoi FCM : ' . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}
// ----------------------------
// 7. Envoyer une notification à tous les clients
// ----------------------------


// ----------------------------
// 8. Envoyer une notification à toutes les boutiques
// ----------------------------
public function notification_toutes_boutiques(Request $request)
{
    $request->validate([
        'title' => 'required|string',
        'message' => 'required|string',            
        'type' => 'nullable|string',
    ]);

    $boutiques = Boutique::whereNotNull('device_token')->get();
    $successCount = 0;
    $errorCount = 0;
    $errors = [];

    foreach ($boutiques as $btq) {
        $result = $this->sendFcmNotification($btq->device_token, $request->title, $request->message, $request->type);
        
        if ($result['success']) {
            $successCount++;
            // ✅ Créer la notification seulement si l'envoi a réussi
            Notification::create([
                'boutique_id' => $btq->id,
                'device_token' => $btq->device_token,
                'title' => $request->title,
                'message' => $request->message,
                'type' => $request->type,
            ]);
        } else {
            $errorCount++;
            $errors[] = [
                'boutique_id' => $btq->id,
                'device_token' => $btq->device_token,
                'error' => $result['error']
            ];
            Log::error("❌ Échec envoi à la boutique {$btq->id}: {$result['error']}");
        }
    }

    return response()->json([
        'success' => true,
        'message' => "Notification envoyée à toutes les boutiques - Succès: {$successCount}, Échecs: {$errorCount}",
        'stats' => [
            'total' => $boutiques->count(),
            'success' => $successCount,
            'errors' => $errorCount
        ],
        'errors' => $errors
    ]);
}

// ----------------------------
// 0. Envoyer une notification à tout le monde (clients + boutiques)
// ----------------------------

public function notification_tous_clients(Request $request)
{
    $request->validate([
        'title' => 'required|string',
        'message' => 'required|string',
        'type' => 'nullable|string',
    ]);

    try {
        $clients = User::whereNotNull('device_token')->get();
        
        if ($clients->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun client avec device token trouvé'
            ], 404);
        }

        $successCount = 0;
        $errorCount = 0;
        $errors = [];

        foreach ($clients as $client) {
            try {
                $result = $this->sendFcmNotification(
                    $client->device_token, 
                    $request->title, 
                    $request->message, 
                    $request->type
                );
                
                // ✅ Vérification robuste pour Expo et FCM
                if (is_array($result) && isset($result['success']) && $result['success'] === true) {
                    $successCount++;
                    
                    // Créer la notification seulement si l'envoi a réussi
                    Notification::create([
                        'user_id' => $client->id,
                        'device_token' => $client->device_token,
                        'title' => $request->title,
                        'message' => $request->message,
                        'type' => $request->type ?? 'default',
                    ]);
                    
                    Log::info("✅ Notification envoyée au client", [
                        'client_id' => $client->id,
                        'token_type' => str_starts_with($client->device_token, 'ExponentPushToken') ? 'Expo' : 'FCM'
                    ]);
                } else {
                    $errorCount++;
                    $errorMessage = $result['error'] ?? 'Erreur inconnue';
                    $errors[] = [
                        'user_id' => $client->id,
                        'device_token' => $this->maskToken($client->device_token),
                        'error' => $errorMessage
                    ];
                    Log::warning("❌ Échec envoi au client {$client->id}", [
                        'error' => $errorMessage,
                        'token_type' => str_starts_with($client->device_token, 'ExponentPushToken') ? 'Expo' : 'FCM'
                    ]);
                }
            } catch (\Exception $e) {
                $errorCount++;
                $errorMsg = 'Exception: ' . $e->getMessage();
                $errors[] = [
                    'user_id' => $client->id,
                    'device_token' => $this->maskToken($client->device_token),
                    'error' => $errorMsg
                ];
                Log::error("❌ Exception lors de l'envoi au client {$client->id}: {$errorMsg}");
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Notification envoyée à tous les clients - Succès: {$successCount}, Échecs: {$errorCount}",
            'stats' => [
                'total' => $clients->count(),
                'success' => $successCount,
                'errors' => $errorCount
            ],
            'errors' => $errorCount > 0 ? $errors : null
        ]);

    } catch (\Exception $e) {
        Log::error("❌ Erreur générale notification_tous_clients: " . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Erreur lors de l\'envoi des notifications: ' . $e->getMessage()
        ], 500);
    }
}

public function notification_tout_le_monde(Request $request)
{
    $request->validate([
        'title' => 'required|string',
        'message' => 'required|string',
        'type' => 'required|string',
    ]);

    try {
        $clients = User::whereNotNull('device_token')->get();
        $boutiques = Boutique::whereNotNull('device_token')->get();
        
        $totalDevices = $clients->count() + $boutiques->count();
        
        if ($totalDevices === 0) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun device token trouvé (clients ou boutiques)'
            ], 404);
        }

        $successCount = 0;
        $errorCount = 0;
        $errors = [];

        // Traitement des clients
        foreach ($clients as $client) {
            try {
                $result = $this->sendFcmNotification(
                    $client->device_token, 
                    $request->title, 
                    $request->message, 
                    $request->type
                );
                
                if (is_array($result) && isset($result['success']) && $result['success'] === true) {
                    $successCount++;
                    Notification::create([
                        'user_id' => $client->id,
                        'device_token' => $client->device_token,
                        'title' => $request->title,
                        'message' => $request->message,
                        'type' => $request->type,
                    ]);
                } else {
                    $errorCount++;
                    $errors[] = [
                        'type' => 'client',
                        'id' => $client->id,
                        'device_token' => $this->maskToken($client->device_token),
                        'error' => $result['error'] ?? 'Erreur inconnue'
                    ];
                }
            } catch (\Exception $e) {
                $errorCount++;
                $errors[] = [
                    'type' => 'client',
                    'id' => $client->id,
                    'device_token' => $this->maskToken($client->device_token),
                    'error' => 'Exception: ' . $e->getMessage()
                ];
            }
        }

        // Traitement des boutiques
        foreach ($boutiques as $btq) {
            try {
                $result = $this->sendFcmNotification(
                    $btq->device_token, 
                    $request->title, 
                    $request->message, 
                    $request->type
                );
                
                if (is_array($result) && isset($result['success']) && $result['success'] === true) {
                    $successCount++;
                    Notification::create([
                        'boutique_id' => $btq->id,
                        'device_token' => $btq->device_token,
                        'title' => $request->title,
                        'message' => $request->message,
                        'type' => $request->type,
                    ]);
                } else {
                    $errorCount++;
                    $errors[] = [
                        'type' => 'boutique',
                        'id' => $btq->id,
                        'device_token' => $this->maskToken($btq->device_token),
                        'error' => $result['error'] ?? 'Erreur inconnue'
                    ];
                }
            } catch (\Exception $e) {
                $errorCount++;
                $errors[] = [
                    'type' => 'boutique',
                    'id' => $btq->id,
                    'device_token' => $this->maskToken($btq->device_token),
                    'error' => 'Exception: ' . $e->getMessage()
                ];
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Notification envoyée à tous les clients et boutiques - Succès: {$successCount}, Échecs: {$errorCount}",
            'stats' => [
                'total' => $totalDevices,
                'clients' => $clients->count(),
                'boutiques' => $boutiques->count(),
                'success' => $successCount,
                'errors' => $errorCount
            ],
            'errors' => $errorCount > 0 ? $errors : null
        ]);

    } catch (\Exception $e) {
        Log::error("❌ Erreur générale notification_tout_le_monde: " . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Erreur lors de l\'envoi des notifications: ' . $e->getMessage()
        ], 500);
    }
}

// ✅ Méthode utilitaire pour masquer les tokens dans les logs
private function maskToken($token)
{
    if (empty($token)) {
        return 'empty';
    }
    
    if (strlen($token) <= 20) {
        return substr($token, 0, 10) . '...';
    }
    
    return substr($token, 0, 10) . '...' . substr($token, -10);
}


public function send()
{
    // 1️⃣ Lecture du compte de service
    $serviceAccountPath = base_path(env('GOOGLE_APPLICATION_CREDENTIALS'));
    $projectId = env('FCM_PROJECT_ID');
    $serviceAccount = json_decode(file_get_contents($serviceAccountPath), true);

    // ✅ Correction des retours à la ligne dans la clé privée
    $privateKey = str_replace("\\n", "\n", $serviceAccount['private_key']);
    $clientEmail = $serviceAccount['client_email'];

    // 2️⃣ Création du JWT manuellement
    $now = time();
    $header = ['alg' => 'RS256', 'typ' => 'JWT'];
    $claimSet = [
        'iss' => $clientEmail,
        'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
        'aud' => 'https://oauth2.googleapis.com/token',
        'exp' => $now + 3600,
        'iat' => $now,
    ];

    $base64UrlHeader = rtrim(strtr(base64_encode(json_encode($header)), '+/', '-_'), '=');
    $base64UrlClaimSet = rtrim(strtr(base64_encode(json_encode($claimSet)), '+/', '-_'), '=');

    $signatureInput = $base64UrlHeader . '.' . $base64UrlClaimSet;
    openssl_sign($signatureInput, $signature, $privateKey, 'SHA256');
    $base64UrlSignature = rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');

    $jwt = $signatureInput . '.' . $base64UrlSignature;

    // 3️⃣ Échange du JWT contre un token d’accès OAuth2
    $tokenResponse = Http::asForm()->post('https://oauth2.googleapis.com/token', [
        'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
        'assertion' => $jwt,
    ]);

    if ($tokenResponse->failed()) {
        return response()->json([
            'error' => 'OAuth token generation failed',
            'details' => $tokenResponse->json(),
        ], 500);
    }

    $accessToken = $tokenResponse->json()['access_token'];

    // 4️⃣ Envoi du message FCM
    $deviceToken = "fr3MthylLFWyFjsvFRXCll:APA91bFeiByioSFfffM1QT-wp1yUxl7oUzFNxMl1TH3FND0IFpkKuyVxwXPJQe5Xuv5IXfvr8yVJXyYmKYsOcOt8Aoyf75FZImNa_Eje2FsokDjBV5wQvn4";

    $message = [
        'message' => [
            'token' => $deviceToken,
            'notification' => [
                'title' => 'Test Laravel FCM',
                'body' => 'Message envoyé via Firebase HTTP v1 🎯',
            ],
        ],
    ];

    $fcmResponse = Http::withHeaders([
        'Authorization' => 'Bearer ' . $accessToken,
        'Content-Type' => 'application/json',
    ])->post("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send", $message);

    return response()->json([
        'status' => $fcmResponse->status(),
        'fcm_body' => $fcmResponse->json(),
    ]);
}


}