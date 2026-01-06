<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Commande Livrée - {{ $commande->code_commande }}</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f8fafc;
        }
        
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        
        .header {
            background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
            color: white;
            padding: 40px 30px;
            text-align: center;
        }
        
        .logo {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 10px;
        }
        
        .order-badge {
            background: rgba(255, 255, 255, 0.2);
            padding: 8px 20px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
            display: inline-block;
            margin-top: 10px;
        }
        
        .delivery-icon {
            font-size: 48px;
            margin-bottom: 15px;
        }
        
        .content {
            padding: 40px 30px;
        }
        
        .greeting {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 20px;
            color: #2d3748;
        }
        
        .message {
            color: #4a5568;
            margin-bottom: 30px;
            font-size: 15px;
            text-align: center;
        }
        
        .success-card {
            background: #f0fff4;
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 30px;
            border: 2px solid #9ae6b4;
            text-align: center;
        }
        
        .success-icon {
            font-size: 40px;
            margin-bottom: 15px;
        }
        
        .success-title {
            font-size: 20px;
            font-weight: 700;
            color: #2f855a;
            margin-bottom: 10px;
        }
        
        .success-message {
            color: #38a169;
            font-size: 15px;
        }
        
        .info-card {
            background: #f7fafc;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 30px;
            border-left: 4px solid #48bb78;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 25px;
        }
        
        .info-item {
            display: flex;
            flex-direction: column;
        }
        
        .info-label {
            font-size: 12px;
            font-weight: 600;
            color: #718096;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 5px;
        }
        
        .info-value {
            font-size: 15px;
            font-weight: 500;
            color: #2d3748;
        }
        
        .summary-card {
            background: linear-gradient(135deg, #f7fafc 0%, #edf2f7 100%);
            border-radius: 12px;
            padding: 25px;
            border: 1px solid #e2e8f0;
            margin-bottom: 30px;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
        }
        
        .summary-label {
            color: #4a5568;
            font-size: 14px;
        }
        
        .summary-value {
            font-weight: 500;
            color: #2d3748;
        }
        
        .total-row {
            border-top: 2px solid #e2e8f0;
            margin-top: 10px;
            padding-top: 15px;
            font-weight: 700;
            font-size: 18px;
            color: #2d3748;
        }
        
        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .status-delivered {
            background: #f0fff4;
            color: #38a169;
            border: 1px solid #9ae6b4;
        }
        
        .next-steps {
            background: #ebf8ff;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 30px;
            border: 1px solid #90cdf4;
        }
        
        .steps-title {
            font-size: 16px;
            font-weight: 600;
            color: #2b6cb0;
            margin-bottom: 15px;
        }
        
        .steps-list {
            list-style: none;
            padding: 0;
        }
        
        .step-item {
            display: flex;
            align-items: flex-start;
            margin-bottom: 12px;
            font-size: 14px;
            color: #4a5568;
        }
        
        .step-number {
            background: #2b6cb0;
            color: white;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 600;
            margin-right: 10px;
            flex-shrink: 0;
        }
        
        .rating-section {
            text-align: center;
            margin: 30px 0;
        }
        
        .rating-title {
            font-size: 16px;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 15px;
        }
        
        .rating-stars {
            display: flex;
            justify-content: center;
            gap: 8px;
            margin-bottom: 15px;
        }
        
        .star {
            font-size: 24px;
            color: #e2e8f0;
            cursor: pointer;
            transition: color 0.2s;
        }
        
        .star:hover {
            color: #f6ad55;
        }
        
        .footer {
            background: #2d3748;
            color: #cbd5e0;
            padding: 30px;
            text-align: center;
            font-size: 13px;
        }
        
        .footer-links {
            margin: 15px 0;
        }
        
        .footer-link {
            color: #cbd5e0;
            text-decoration: none;
            margin: 0 10px;
        }
        
        .footer-link:hover {
            color: white;
        }
        
        .contact-info {
            margin-top: 15px;
            font-size: 12px;
        }
        
        @media (max-width: 600px) {
            .content {
                padding: 25px 20px;
            }
            
            .info-grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }
            
            .delivery-icon {
                font-size: 36px;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <!-- En-tête -->
        <div class="header">
            <div class="logo">🛍️ EBAMAGE Market</div>
            <div class="delivery-icon">🚚</div>
            <h1>Commande Livrée !</h1>
            <div class="order-badge">Référence: {{ $commande->code_commande }}</div>
        </div>
        
        <!-- Contenu principal -->
        <div class="content">
            <!-- Message de salutation personnalisé -->
            <div class="greeting">
                @if($role === 'client')
                    Bonjour {{ $commande->client->nom_clt }} 👋
                @elseif($role === 'boutique')
                    Bonjour {{ $commande->boutique->nom_btq }} 🏪
                @else
                    Bonjour Admin ⚡
                @endif
            </div>
            
            <!-- Carte de succès -->
            <div class="success-card">
                <div class="success-icon">🎉</div>
                <div class="success-title">Livraison Réussie !</div>
                <div class="success-message">
                    @if($role === 'client')
                        Votre commande a été livrée avec succès. Nous espérons que vous êtes satisfait de votre achat !
                    @elseif($role === 'boutique')
                        La commande a été livrée au client avec succès. Transaction terminée avec succès.
                    @else
                        Une commande a été livrée avec succès sur la plateforme TDL Market.
                    @endif
                </div>
            </div>
            
            <!-- Informations de la commande -->
            <div class="info-card">
                <div class="info-grid">
                    <div class="info-item">
                        <span class="info-label">Date de livraison</span>
                        <span class="info-value">{{ now()->format('d/m/Y à H:i') }}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Statut</span>
                        <span class="status-badge status-delivered">Livrée</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Moyen de paiement</span>
                        <span class="info-value">
                            {{ $commande->moyen_de_paiement == 1 ? 'À la livraison' : 'En ligne' }}
                        </span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Articles livrés</span>
                        <span class="info-value">{{ $commande->quantite }} produit(s)</span>
                    </div>
                </div>
                
                <!-- Adresse de livraison -->
                <!-- <div class="info-item" style="margin-top: 15px;">
                    <span class="info-label">Adresse de livraison</span>
                    <span class="info-value">
                        {{ $commande->quartier }}, {{ $commande->commune->lib_commune ?? 'N/A' }}, 
                        {{ $commande->ville->lib_ville ?? 'N/A' }}
                    </span>
                </div> -->
            </div>
            
            <!-- Récapitulatif financier -->
            <div class="summary-card">
                <div style="font-size: 16px; font-weight: 600; color: #2d3748; margin-bottom: 20px; text-align: center;">
                    💰 Récapitulatif de la Commande
                </div>
                
                <div class="summary-row">
                    <span class="summary-label">Sous-total articles</span>
                    <span class="summary-value">{{ number_format($commande->prix, 0, ',', ' ') }} FCFA</span>
                </div>
                
                <div class="summary-row total-row">
                    <span class="summary-label">Montant total</span>
                    <span class="summary-value">{{ number_format($commande->prix, 0, ',', ' ') }} FCFA</span>
                </div>
            </div>
            
            @if($role === 'client')
            
            
            <!-- Prochaines étapes pour le client -->
            <div class="next-steps">
                <div class="steps-title">📋 Prochaines étapes</div>
                <ul class="steps-list">
                    <li class="step-item">
                        <span class="step-number">1</span>
                        <span>Vérifiez votre colis et assurez-vous que tout est en bon état</span>
                    </li>
                    <li class="step-item">
                        <span class="step-number">2</span>
                        <span>Contactez-nous dans les 48h en cas de problème avec votre commande</span>
                    </li>
                    <li class="step-item">
                        <span class="step-number">3</span>
                        <span>Partagez votre expérience en laissant un avis sur nos produits</span>
                    </li>
                </ul>
            </div>
            @endif
            
            @if($role === 'boutique')
            <!-- Message pour la boutique -->
            <div class="message" style="text-align: center; padding: 20px; background: #f0fff4; border-radius: 10px; border: 1px solid #9ae6b4;">
                <strong>✅ Transaction terminée</strong><br>
                La commande a été livrée avec succès. Le montant sera crédité sur votre compte selon les conditions convenues.
            </div>
            @endif
        </div>
        
        
    </div>
    
    <script>
        // Script simple pour l'interaction des étoiles (optionnel)
        document.addEventListener('DOMContentLoaded', function() {
            const stars = document.querySelectorAll('.star');
            stars.forEach((star, index) => {
                star.addEventListener('click', function() {
                    // Réinitialiser toutes les étoiles
                    stars.forEach(s => s.style.color = '#e2e8f0');
                    // Colorer les étoiles jusqu'à celle cliquée
                    for (let i = 0; i <= index; i++) {
                        stars[i].style.color = '#f6ad55';
                    }
                });
            });
        });
    </script>
</body>
</html>