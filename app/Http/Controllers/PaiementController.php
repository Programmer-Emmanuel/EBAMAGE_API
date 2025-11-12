<?php

namespace App\Http\Controllers;

use App\Models\Commande;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Vinkla\Hashids\Facades\Hashids;
use Exception;

class PaiementController extends Controller
{
    /**
     * Initier le paiement Paystack pour une commande
     */
    public function initierPaiement(Request $request)
    {
        try {
            $request->validate([
                'commande_hashid' => 'required|string'
            ]);

            $commande_id = Hashids::decode($request->commande_hashid)[0] ?? null;
            if (!$commande_id) {
                return response()->json(['success' => false, 'message' => 'Commande invalide.'], 400);
            }

            $commande = Commande::find($commande_id);
            if (!$commande) {
                return response()->json(['success' => false, 'message' => 'Commande introuvable.'], 404);
            }

            if ($commande->is_paid) {
                return response()->json(['success' => false, 'message' => 'Commande déjà payée.'], 400);
            }

            $reference = Str::uuid(); // référence unique pour Paystack

            // Enregistrer la référence pour vérification future
            $commande->paystack_reference = $reference;
            $commande->save();

            $paystackKey = env('PAYSTACK_SECRET_KEY');
            $response = Http::withToken($paystackKey)
                ->post('https://api.paystack.co/transaction/initialize', [
                    'amount' => $commande->prix_total * 100, // en kobo
                    'email' => $commande->client->email_clt ?? 'client@example.com',
                    'currency' => 'XOF',
                    'reference' => $reference,
                    'callback_url' => env('APP_URL') . '/api/paiement/verifier/' . $reference,
                    'metadata' => [
                        'commande_id' => $commande->id
                    ]
                ]);

            $data = $response->json();
            if (!isset($data['status']) || $data['status'] !== true) {
                Log::error('Erreur Paystack initialize:', ['response' => $data]);
                return response()->json(['success' => false, 'message' => 'Erreur serveur Paystack.'], 500);
            }

            return response()->json([
                'success' => true,
                'payment_url' => $data['data']['authorization_url'],
                'reference' => $reference
            ]);

        } catch (Exception $e) {
            Log::error('Erreur initierPaiement:', ['message' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Erreur serveur.'], 500);
        }
    }

    /**
     * Vérifier le paiement après redirection Paystack
     */
    public function verifierPaiement($reference)
    {
        try {
            $paystackKey = env('PAYSTACK_SECRET_KEY');
            $response = Http::withToken($paystackKey)
                ->get("https://api.paystack.co/transaction/verify/{$reference}");

            $data = $response->json();
            if ($response->failed() || !isset($data['status']) || $data['status'] !== true) {
                return response()->json(['success' => false, 'message' => 'Paiement non validé.'], 400);
            }

            $status = $data['data']['status'] ?? null;
            $commande = Commande::where('paystack_reference', $reference)->first();
            if (!$commande) {
                return response()->json(['success' => false, 'message' => 'Commande introuvable.'], 404);
            }

            if ($status === 'success' && !$commande->is_paid) {
                $commande->is_paid = true;
                $commande->statut = 'Payé';
                $commande->save();
            }

            return response()->json([
                'success' => $status === 'success',
                'message' => $status === 'success' ? 'Paiement confirmé.' : 'Paiement échoué.',
                'commande' => [
                    'hashid' => Hashids::encode($commande->id),
                    'statut' => $commande->statut,
                    'is_paid' => $commande->is_paid,
                    'prix_total' => $commande->prix_total,
                    'reference' => $reference
                ]
            ]);

        } catch (Exception $e) {
            Log::error('Erreur verifierPaiement:', ['message' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Erreur serveur.'], 500);
        }
    }
}
