<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Commande Annulée - {{ $commande->code_commande }}</title>
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
            background: linear-gradient(135deg, #f56565 0%, #e53e3e 100%);
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
        
        .cancellation-icon {
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
        
        .cancellation-card {
            background: #fed7d7;
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 30px;
            border: 2px solid #feb2b2;
            text-align: center;
        }
        
        .cancellation-icon {
            font-size: 40px;
            margin-bottom: 15px;
        }
        
        .cancellation-title {
            font-size: 20px;
            font-weight: 700;
            color: #c53030;
            margin-bottom: 10px;
        }
        
        .cancellation-message {
            color: #e53e3e;
            font-size: 15px;
        }
        
        .info-card {
            background: #f7fafc;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 30px;
            border-left: 4px solid #f56565;
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
        
        .products-section {
            margin-bottom: 30px;
        }
        
        .section-title {
            font-size: 16px;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e2e8f0;
        }
        
        .product-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .product-item {
            display: flex;
            align-items: center;
            padding: 15px;
            background: white;
            border-radius: 10px;
            border: 1px solid #e2e8f0;
            opacity: 0.7;
        }
        
        .product-image {
            width: 60px;
            height: 60px;
            border-radius: 8px;
            object-fit: cover;
            margin-right: 15px;
            border: 1px solid #e2e8f0;
        }
        
        .product-details {
            flex: 1;
        }
        
        .product-name {
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 5px;
        }
        
        .product-meta {
            display: flex;
            gap: 15px;
            font-size: 13px;
            color: #718096;
        }
        
        .product-price {
            font-weight: 600;
            color: #2b6cb0;
        }
        
        .summary-card {
            background: linear-gradient(135deg, #f7fafc 0%, #edf2f7 100%);
            border-radius: 12px;
            padding: 25px;
            border: 1px solid #e2e8f0;
            margin-bottom: 30px;
            opacity: 0.7;
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
        
        .status-cancelled {
            background: #fed7d7;
            color: #c53030;
            border: 1px solid #feb2b2;
        }
        
        .refund-info {
            background: #f0fff4;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 30px;
            border: 1px solid #9ae6b4;
        }
        
        .refund-title {
            font-size: 16px;
            font-weight: 600;
            color: #2f855a;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .refund-message {
            color: #38a169;
            font-size: 14px;
            line-height: 1.5;
        }
        
        .next-steps {
            background: #f7fafc;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 30px;
            border: 1px solid #e2e8f0;
        }
        
        .steps-title {
            font-size: 16px;
            font-weight: 600;
            color: #2d3748;
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
            background: #718096;
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
        
        .contact-support {
            background: #ebf8ff;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 30px;
            border: 1px solid #90cdf4;
            text-align: center;
        }
        
        .support-title {
            font-size: 16px;
            font-weight: 600;
            color: #2b6cb0;
            margin-bottom: 10px;
        }
        
        .support-message {
            color: #3182ce;
            font-size: 14px;
            margin-bottom: 15px;
        }
        
        .support-contact {
            font-size: 14px;
            color: #4a5568;
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
            
            .product-item {
                flex-direction: column;
                text-align: center;
            }
            
            .product-image {
                margin-right: 0;
                margin-bottom: 10px;
            }
            
            .product-meta {
                flex-direction: column;
                gap: 5px;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <!-- En-tête -->
        <div class="header">
            <div class="logo">🛍️ EBAMAGE Market</div>
            <div class="cancellation-icon">❌</div>
            <h1>Commande Annulée</h1>
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
            
            <!-- Carte d'annulation -->
            <div class="cancellation-card">
                <div class="cancellation-icon">🚫</div>
                <div class="cancellation-title">Commande Annulée</div>
                <div class="cancellation-message">
                    @if($role === 'client')
                        Votre commande a été annulée. Si c'est une erreur, contactez rapidement la boutique.
                    @elseif($role === 'boutique')
                        Vous avez annulé cette commande. Le client a été notifié de l'annulation.
                    @else
                        Une commande a été annulée sur la plateforme Ebamage Market.
                    @endif
                </div>
            </div>
            
            <!-- Informations de la commande -->
            <div class="info-card">
                <div class="info-grid">
                    <div class="info-item">
                        <span class="info-label">Date d'annulation</span>
                        <span class="info-value">{{ now()->format('d/m/Y à H:i') }}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Statut</span>
                        <span class="status-badge status-cancelled">Annulée</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Moyen de paiement</span>
                        <span class="info-value">
                            {{ $commande->moyen_de_paiement == 1 ? 'À la livraison' : 'En ligne' }}
                        </span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Articles concernés</span>
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
            
            <!-- Liste des articles (en grisé) -->
            <div class="products-section">
                <div class="section-title">📦 Articles concernés</div>
                <div class="product-list">
                    @php
                        $articles = json_decode($commande->articles, true) ?? [];
                    @endphp
                    
                    @foreach($articles as $article)
                    <div class="product-item">
                        @if(isset($article['image']) && $article['image'])
                        <img src="{{ $article['image'] }}" alt="{{ $article['nom_article'] ?? 'Produit' }}" class="product-image" onerror="this.style.display='none'">
                        @else
                        <div class="product-image" style="background: #e2e8f0; display: flex; align-items: center; justify-content: center; color: #718096;">
                            📷
                        </div>
                        @endif
                        <div class="product-details">
                            <div class="product-name">{{ $article['nom_article'] ?? 'Produit sans nom' }}</div>
                            <div class="product-meta">
                                <span class="product-price">{{ number_format($article['prix'] ?? 0, 0, ',', ' ') }} FCFA</span>
                                <span>Quantité: {{ $article['quantite'] ?? 1 }}</span>
                                <span>Sous-total: {{ number_format(($article['prix'] ?? 0) * ($article['quantite'] ?? 1), 0, ',', ' ') }} FCFA</span>
                            </div>
                            @if(!empty($article['variations']) && is_array($article['variations']))
                            <div class="product-meta">
                                <span>Variations: 
                                    @foreach($article['variations'] as $key => $value)
                                        @if(is_array($value))
                                            {{ $key }}: {{ implode(', ', $value) }}@if(!$loop->last); @endif
                                        @else
                                            {{ $value }}@if(!$loop->last), @endif
                                        @endif
                                    @endforeach
                                </span>
                            </div>
                            @elseif(!empty($article['variations']) && is_string($article['variations']))
                            <div class="product-meta">
                                <span>Variations: {{ $article['variations'] }}</span>
                            </div>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            
            <!-- Récapitulatif financier (en grisé) -->
            <div class="summary-card">
                <div style="font-size: 16px; font-weight: 600; color: #2d3748; margin-bottom: 20px; text-align: center;">
                    💰 Montant de la commande annulée
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

            @if($role === 'client' && $commande->moyen_de_paiement == 0)
            <!-- Information de remboursement pour les clients qui ont payé en ligne -->
            <div class="refund-info">
                <div class="refund-title">💰 Information de Remboursement</div>
                <div class="refund-message">
                    <strong>Votre remboursement est en cours de traitement.</strong><br>
                    Le montant de <strong>{{ number_format($commande->prix_total, 0, ',', ' ') }} FCFA</strong> sera crédité sur votre compte TDL Market dans les 24 à 48 heures.
                </div>
            </div>
            @endif
            
            @if($role === 'client')
            <!-- Prochaines étapes pour le client -->
            <div class="next-steps">
                <div class="steps-title">🔄 Que faire maintenant ?</div>
                <ul class="steps-list">
                    <li class="step-item">
                        <span class="step-number">1</span>
                        <span>Vérifiez les détails de l'annulation ci-dessus</span>
                    </li>
                    <li class="step-item">
                        <span class="step-number">2</span>
                        <span>Si cette annulation est une erreur, contactez rapidement la boutique</span>
                    </li>
                    <li class="step-item">
                        <span class="step-number">3</span>
                        <span>Explorez notre catalogue pour trouver d'autres articles</span>
                    </li>
                </ul>
            </div>
            
            <!-- Contact support -->
            <div class="contact-support">
                <div class="support-title">📞 Besoin d'aide ?</div>
                <div class="support-message">
                    Si vous avez des questions concernant cette annulation, notre équipe de support est là pour vous aider.
                </div>
                <div class="support-contact">
                    <strong>Contact support:</strong> +225 07 07 07 07 07 | ✉️ support@ebamagemarket.ci
                </div>
            </div>
            @endif
            
            @if($role === 'boutique')
            <!-- Message pour la boutique -->
            <div class="message" style="text-align: center; padding: 20px; background: #fed7d7; border-radius: 10px; border: 1px solid #feb2b2;">
                <strong>📋 Commande annulée</strong><br>
                Cette commande a été marquée comme annulée. Le stock des articles concernés a été réajusté automatiquement.
            </div>
            @endif
        </div>
        
        
    </div>
</body>
</html>