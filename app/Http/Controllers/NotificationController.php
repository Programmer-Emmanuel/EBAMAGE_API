<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Boutique;
use App\Models\Notification;
use Google\Auth\Credentials\ServiceAccountCredentials;
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

            if ($response->successful()) {
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

            if ($response->successful()) {
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
    // ✅ Si c’est un token Expo (React Native / Expo)
    if (str_starts_with($deviceToken, 'ExponentPushToken')) {
        $data = [
            'to' => $deviceToken,
            'sound' => 'default',
            'title' => $title,
            'body' => $body,
            'data' => [
                'type' => $type ?? 'default',
            ],
        ];

        return Http::withHeaders([
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->post('https://exp.host/--/api/v2/push/send', $data);
    }

    // ✅ Envoi via Firebase HTTP v1
    $projectId = env('FCM_PROJECT_ID');
    $credentialsPath = base_path(env('GOOGLE_APPLICATION_CREDENTIALS'));

    if (!file_exists($credentialsPath)) {
        Log::error("Fichier de compte de service introuvable : {$credentialsPath}");
        throw new \Exception("Fichier de compte de service introuvable : {$credentialsPath}");
    }

    try {
        $scopes = ['https://www.googleapis.com/auth/firebase.messaging'];
        $credentials = new ServiceAccountCredentials($scopes, $credentialsPath);
        $accessToken = $credentials->fetchAuthToken()['access_token'];

        $data = [
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
        ])->post($url, $data);

        if ($response->failed()) {
            Log::error('Erreur FCM: ' . $response->body());
        }

        return $response->json();

    } catch (\Exception $e) {
        Log::error('Erreur lors de l’envoi FCM : ' . $e->getMessage());
        throw $e;
    }
}


    // ----------------------------
    // 7. Envoyer une notification à tous les clients
    // ----------------------------
    public function notification_tous_clients(Request $request)
    {
        $request->validate([
            'title' => 'required|string',
            'message' => 'required|string',
            'type' => 'nullable|string',
        ]);

        $clients = User::whereNotNull('device_token')->get();
        foreach ($clients as $client) {
            $this->sendFcmNotification($client->device_token, $request->title, $request->message, $request->type);

            Notification::create([
                'user_id' => $client->id,
                'device_token' => $client->device_token,
                'title' => $request->title,
                'message' => $request->message,
                'type' => $request->type,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Notification envoyée à tous les clients'
        ]);
    }

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
        foreach ($boutiques as $btq) {
            $this->sendFcmNotification($btq->device_token, $request->title, $request->message, $request->type);

            Notification::create([
                'boutique_id' => $btq->id,
                'device_token' => $btq->device_token,
                'title' => $request->title,
                'message' => $request->message,
                'type' => $request->type,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Notification envoyée à toutes les boutiques'
        ]);
    }

    // ----------------------------
    // 0. Envoyer une notification à tout le monde (clients + boutiques)
    // ----------------------------
    public function notification_tout_le_monde(Request $request)
    {
        $request->validate([
            'title' => 'required|string',
            'message' => 'required|string',
            'type' => 'required|string',
        ]);

        $clients = User::whereNotNull('device_token')->get();
        $boutiques = Boutique::whereNotNull('device_token')->get();

        foreach ($clients as $client) {
            $this->sendFcmNotification($client->device_token, $request->title, $request->message, $request->type);

            Notification::create([
                'user_id' => $client->id,
                'device_token' => $client->device_token,
                'title' => $request->title,
                'message' => $request->message,
                'type' => $request->type,
            ]);
        }

        foreach ($boutiques as $btq) {
            $this->sendFcmNotification($btq->device_token, $request->title, $request->message, $request->type);

            Notification::create([
                'boutique_id' => $btq->id,
                'device_token' => $btq->device_token,
                'title' => $request->title,
                'message' => $request->message,
                'type' => $request->type,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Notification envoyée à tous les clients et boutiques'
        ]);
    }


    public function send()
    {
        // 1️⃣ Récupération du chemin du fichier JSON et du projet
        $path = base_path(env('GOOGLE_APPLICATION_CREDENTIALS'));
        $projectId = env('FCM_PROJECT_ID');

        // 2️⃣ Génération du token OAuth2
        $scopes = ['https://www.googleapis.com/auth/firebase.messaging'];
        $credentials = new ServiceAccountCredentials($scopes, $path);
        $token = $credentials->fetchAuthToken();
        $accessToken = $token['access_token'];

        // 3️⃣ Données du message
        $deviceToken = "czOL4lcZVLveRy80rdEMto:APA91bGl0hSlk0BUjWKkJ8mSZQW0KstXKobyE2zrcjMcR1MVJEk7mLnbp0_GtnGKAzL9OwAKnwHMqtH4dHzJobsoL1l9j8uAOCj2wfQLYHJ72wWiA0XzT1k";

        $message = [
            "message" => [
                "token" => $deviceToken,
                "notification" => [
                    "title" => "Test Laravel FCM",
                    "body"  => "Message envoyé via Firebase HTTP v1 🎯"
                ]
            ]
        ];

        // 4️⃣ Envoi à Firebase
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $accessToken,
            'Content-Type' => 'application/json',
        ])->post("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send", $message);

        // 5️⃣ Retour du résultat
        return response()->json([
            'status' => $response->status(),
            'body' => $response->json(),
        ]);
    }

}