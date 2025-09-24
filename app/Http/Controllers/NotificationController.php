<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Boutique;
use App\Models\Notification;
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
            'device_token' => 'required|string',
        ]);

        $user = auth('client')->user();
        $boutique = auth('boutique')->user();

        try {
            if ($user) {
                User::where('id', $user->id)->update([
                    'device_token' => $request->device_token
                ]);
            } elseif ($boutique) {
                Boutique::where('id', $boutique->id)->update([
                    'device_token' => $request->device_token
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Utilisateur non authentifié'
                ], 401);
            }

            return response()->json([
                'success' => true,
                'device_token' => $request->device_token,
                'message' => 'Device Token enregistré avec succès'
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'enregistrement du token: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'enregistrement du Device Token'
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
                $request->message
            );

            if ($response->successful()) {
                Notification::create([
                    'user_id' => $user->id,
                    'device_token' => $request->device_token,
                    'title' => $request->title,
                    'message' => $request->message,
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
                $request->message
            );

            if ($response->successful()) {
                Notification::create([
                    'boutique_id' => $boutique->id,
                    'device_token' => $request->device_token,
                    'title' => $request->title,
                    'message' => $request->message,
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
                    ->get(['id', 'title', 'message', 'created_at']);
            } elseif ($boutique) {
                $notifications = Notification::where('boutique_id', $boutique->id)
                    ->orderBy('created_at', 'desc')
                    ->get(['id', 'title', 'message', 'created_at']);
            } else {
                return response()->json([
                    'success' => false,
                    'data' => null,
                    'message' => 'Utilisateur non authentifié'
                ], 401);
            }

            return response()->json([
                'success' => true,
                'data' => $notifications,
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
    private function sendFcmNotification($deviceToken, $title, $body)
    {
        $serverKey = env('FCM_SERVER_KEY');

        if (!$serverKey) {
            Log::error('Clé FCM non configurée');
            throw new \Exception('Clé FCM non configurée');
        }

        return Http::withHeaders([
            'Authorization' => 'key=' . $serverKey,
            'Content-Type' => 'application/json',
        ])->post('https://fcm.googleapis.com/fcm/send', [
            'to' => $deviceToken,
            'notification' => [
                'title' => $title,
                'body' => $body,
                'sound' => 'default',
            ],
            'priority' => 'high',
        ]);
    }

    // ----------------------------
    // 7. Envoyer une notification à tous les clients
    // ----------------------------
    public function notification_tous_clients(Request $request)
    {
        $request->validate([
            'title' => 'required|string',
            'message' => 'required|string',
        ]);

        $clients = User::whereNotNull('device_token')->get();
        foreach ($clients as $client) {
            $this->sendFcmNotification($client->device_token, $request->title, $request->message);

            Notification::create([
                'user_id' => $client->id,
                'device_token' => $client->device_token,
                'title' => $request->title,
                'message' => $request->message,
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
        ]);

        $boutiques = Boutique::whereNotNull('device_token')->get();
        foreach ($boutiques as $btq) {
            $this->sendFcmNotification($btq->device_token, $request->title, $request->message);

            Notification::create([
                'boutique_id' => $btq->id,
                'device_token' => $btq->device_token,
                'title' => $request->title,
                'message' => $request->message,
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
        ]);

        $clients = User::whereNotNull('device_token')->get();
        $boutiques = Boutique::whereNotNull('device_token')->get();

        foreach ($clients as $client) {
            $this->sendFcmNotification($client->device_token, $request->title, $request->message);

            Notification::create([
                'user_id' => $client->id,
                'device_token' => $client->device_token,
                'title' => $request->title,
                'message' => $request->message,
            ]);
        }

        foreach ($boutiques as $btq) {
            $this->sendFcmNotification($btq->device_token, $request->title, $request->message);

            Notification::create([
                'boutique_id' => $btq->id,
                'device_token' => $btq->device_token,
                'title' => $request->title,
                'message' => $request->message,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Notification envoyée à tous les clients et boutiques'
        ]);
    }

}
