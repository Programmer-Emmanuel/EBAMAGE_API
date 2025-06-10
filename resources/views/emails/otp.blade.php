<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Votre Code de Vérification</title>
    <style>
        body {
            font-family: 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.5;
            color: #444;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f8fafc;
        }
        .container {
            background-color: #ffffff;
            border-radius: 12px;
            padding: 40px;
            box-shadow: 0 2px 16px rgba(0, 0, 0, 0.08);
            border: 1px solid #e2e8f0;
        }
        .header {
            color: #1e293b;
            font-size: 26px;
            margin-bottom: 24px;
            font-weight: 600;
            text-align: center;
        }
        .otp-container {
            text-align: center;
            margin: 32px 0;
        }
        .otp-code {
            font-size: 36px;
            letter-spacing: 10px;
            padding: 18px 24px;
            background-color: #f1f5fe;
            color: #2563eb;
            border-radius: 10px;
            display: inline-block;
            font-weight: 700;
            box-shadow: 0 2px 8px rgba(37, 99, 235, 0.1);
        }
        .message {
            font-size: 16px;
            color: #475569;
            text-align: center;
            margin-bottom: 24px;
        }
        .validity {
            color: #16a34a;
            font-weight: 500;
            text-align: center;
            font-size: 15px;
            margin-bottom: 28px;
        }
        .warning {
            background-color: #fef2f2;
            color: #dc2626;
            padding: 16px;
            border-radius: 8px;
            margin: 28px 0;
            font-size: 15px;
            text-align: center;
            border: 1px solid #fee2e2;
        }
        .footer {
            margin-top: 32px;
            font-size: 14px;
            color: #64748b;
            text-align: center;
            line-height: 1.6;
        }
        .divider {
            height: 1px;
            background-color: #e2e8f0;
            margin: 24px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">Vérification Requise</div>
        
        <p class="message">Voici votre code de vérification à usage unique :</p>
        
        <div class="otp-container">
            <div class="otp-code">{{ $otp }}</div>
        </div>
        
        <p class="validity">⏳ Valide pendant <strong>5 minutes</strong></p>
        
        <div class="warning">
            <strong>⚠️ Sécurité importante :</strong> Ne communiquez jamais ce code, même à nos équipes.
        </div>
        
        <div class="divider"></div>
        
        <div class="footer">
            <p>Si vous n'avez pas initié cette demande,<br>merci d'ignorer ce message.</p>
            <p>L'équipe de sécurité</p>
        </div>
    </div>
</body>
</html>