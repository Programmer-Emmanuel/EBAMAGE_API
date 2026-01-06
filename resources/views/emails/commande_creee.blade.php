<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nouvelle Commande - {{ $commande->code_commande }}</title>
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
        }
        
        .info-card {
            background: #f7fafc;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 30px;
            border-left: 4px solid #667eea;
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
        
        .status-pending {
            background: #fffaf0;
            color: #d69e2e;
            border: 1px solid #fbd38d;
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
            <h1>Nouvelle Commande</h1>
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
            
            <div class="message">
                @if($role === 'client')
                    Votre commande a été créée avec succès ! Nous vous tiendrons informé de son avancement.
                @elseif($role === 'boutique')
                    Vous avez reçu une nouvelle commande de <strong>{{ $commande->client->nom_clt }}</strong>. Préparez les articles pour l'expédition.
                @else
                    Une nouvelle commande a été enregistrée sur la plateforme TDL Market.
                @endif
            </div>
            
            <!-- Informations de la commande -->
            <div class="info-card">
                <div class="info-grid">
                    <div class="info-item">
                        <span class="info-label">Date de commande</span>
                        <span class="info-value">{{ $commande->created_at->format('d/m/Y à H:i') }}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Statut</span>
                        <span class="status-badge status-pending">{{ $commande->statut }}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Moyen de paiement</span>
                        <span class="info-value">
                            {{ $commande->moyen_de_paiement == 1 ? 'À la livraison' : 'En ligne' }}
                        </span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Articles</span>
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
            
            <!-- Liste des articles -->
            <div class="products-section">
                <div class="section-title">📦 Articles commandés</div>
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
            
            <!-- Récapitulatif des prix -->
            <div class="summary-card">
                <div class="section-title">💰 Récapitulatif</div>
                
                <div class="summary-row">
                    <span class="summary-label">Sous-total articles</span>
                    <span class="summary-value">{{ number_format($commande->prix, 0, ',', ' ') }} FCFA</span>
                </div>
                
                <div class="summary-row total-row">
                    <span class="summary-label">Total de la commande</span>
                    <span class="summary-value">{{ number_format($commande->prix, 0, ',', ' ') }} FCFA</span>
                </div>
            </div>
            
            <!-- Message de confirmation -->
            <div class="message" style="text-align: center; margin-top: 30px; padding: 20px; background: #f0fff4; border-radius: 10px; border: 1px solid #9ae6b4;">
                <strong>🎉 Commande confirmée !</strong><br>
                Votre commande est en cours de traitement. Vous recevrez une notification à chaque étape.
            </div>
        </div>
        
        
    </div>
</body>
</html>