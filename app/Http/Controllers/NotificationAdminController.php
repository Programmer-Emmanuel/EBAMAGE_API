<?php

namespace App\Http\Controllers;

use App\Models\NotificationAdmin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NotificationAdminController extends Controller
{
private function sendFcmNotificationAdmin($adminId, $deviceToken, $title, $body, $type = 'default')
{
    if (empty($deviceToken)) {
        Log::warning("⚠️ Token FCM vide (admin notif)");
        return ['success' => false, 'error' => 'Token vide'];
    }

    try {
        $projectId = env('FCM_PROJECT_ID');

        // 🔐 Firebase config
        $jsonKey = [
            'client_email' => env('FIREBASE_CLIENT_EMAIL'),
            'private_key' => str_replace("\\n", "\n", env('FIREBASE_PRIVATE_KEY')),
        ];

        if (empty($jsonKey['private_key']) || empty($jsonKey['client_email'])) {
            Log::error("❌ Firebase config manquante");
            return ['success' => false, 'error' => 'Firebase config manquante'];
        }

        // 🔐 OAuth2
        $oauth2 = new \Google\Auth\OAuth2([
            'audience' => 'https://oauth2.googleapis.com/token',
            'issuer' => $jsonKey['client_email'],
            'signingAlgorithm' => 'RS256',
            'signingKey' => $jsonKey['private_key'],
            'tokenCredentialUri' => 'https://oauth2.googleapis.com/token',
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
        ]);

        $accessToken = $oauth2->fetchAuthToken()['access_token'];

        // 📦 Payload FCM
        $payload = [
            'message' => [
                'token' => $deviceToken,
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                ],
                'data' => [
                    'type' => $type,
                ],
                'android' => ['priority' => 'high'],
            ],
        ];

        $url = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";

        $response = Http::withToken($accessToken)
            ->withHeaders(['Content-Type' => 'application/json'])
            ->post($url, $payload);

        if ($response->failed()) {
            Log::error("❌ FCM ERROR: " . $response->body());
            return ['success' => false, 'error' => $response->body()];
        }

        // ✅ 1. SAUVEGARDE EN BASE (AUTO)
        NotificationAdmin::create([
            'admin_id' => $adminId,
            'title' => $title,
            'message' => $body,
            'type' => $type,
        ]);

        Log::info("✅ Notification admin envoyée + sauvegardée", [
            'admin_id' => $adminId,
            'title' => $title
        ]);

        return ['success' => true];

    } catch (\Throwable $e) {
        Log::error("❌ FCM Exception: " . $e->getMessage());

        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}
}
