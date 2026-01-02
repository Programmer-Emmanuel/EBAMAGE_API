<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réinitialisation du mot de passe - EBAMAGE</title>
    <style>
        :root {
            --primary-50: #f0fdf4;
            --primary-100: #dcfce7;
            --primary-200: #bbf7d0;
            --primary-300: #86efac;
            --primary-400: #4ade80;
            --primary-500: #22c55e;
            --primary-600: #16a34a;
            --primary-700: #15803d;
            --primary-800: #166534;
            --primary-900: #14532d;
            --text-primary: #1a2e05;
            --text-secondary: #4b5563;
            --background: #f9fafb;
            --card-bg: #ffffff;
            --border-color: #d1fae5;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --gradient: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background-color: var(--background);
            color: var(--text-primary);
            line-height: 1.6;
            padding: 20px;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .container {
            max-width: 100%;
            width: 100%;
        }

        .card {
            background: var(--card-bg);
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(34, 197, 94, 0.1);
            overflow: hidden;
            border: 1px solid var(--border-color);
            max-width: 480px;
            margin: 0 auto;
        }

        .header {
            background: var(--gradient);
            padding: 40px 32px 32px;
            text-align: center;
            color: white;
            position: relative;
        }

        .header::after {
            content: '';
            position: absolute;
            bottom: -20px;
            left: 0;
            right: 0;
            height: 40px;
            background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 1440 320'%3E%3Cpath fill='%23ffffff' fill-opacity='1' d='M0,224L60,213.3C120,203,240,181,360,181.3C480,181,600,203,720,192C840,181,960,139,1080,128C1200,117,1320,139,1380,149.3L1440,160L1440,320L1380,320C1320,320,1200,320,1080,320C960,320,840,320,720,320C600,320,480,320,360,320C240,320,120,320,60,320L0,320Z'%3E%3C/path%3E%3C/svg%3E");
            background-size: cover;
        }

        .logo {
            font-size: 28px;
            font-weight: 700;
            letter-spacing: 1px;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .logo-icon {
            background: rgba(255, 255, 255, 0.2);
            width: 36px;
            height: 36px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(10px);
        }

        .header h1 {
            font-size: 24px;
            font-weight: 600;
            margin: 0;
        }

        .content {
            padding: 48px 32px 32px;
        }

        .welcome {
            color: var(--text-secondary);
            margin-bottom: 24px;
            font-size: 16px;
        }

        .otp-container {
            text-align: center;
            margin: 40px 0;
        }

        .otp-label {
            display: block;
            color: var(--text-secondary);
            margin-bottom: 16px;
            font-size: 15px;
        }

        .otp-code {
            font-size: 56px;
            font-weight: 800;
            color: var(--primary-700);
            letter-spacing: 8px;
            margin: 0;
            padding: 16px;
            background: var(--primary-50);
            border-radius: 16px;
            border: 2px dashed var(--primary-200);
            font-family: 'Courier New', monospace;
            display: inline-block;
            min-width: 280px;
            position: relative;
            overflow: hidden;
        }

        .otp-code::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.6), transparent);
            animation: shimmer 2s infinite;
        }

        .timer {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-top: 24px;
            color: var(--warning-color);
            font-weight: 500;
            background: rgba(245, 158, 11, 0.1);
            padding: 12px 20px;
            border-radius: 12px;
            display: inline-flex;
        }

        .timer-icon {
            width: 20px;
            height: 20px;
            animation: pulse 2s infinite;
        }

        .instructions {
            background: var(--primary-50);
            padding: 20px;
            border-radius: 12px;
            margin-top: 32px;
            font-size: 14px;
            color: var(--text-secondary);
            border-left: 4px solid var(--primary-400);
        }

        .instructions ul {
            list-style: none;
            margin-top: 12px;
        }

        .instructions li {
            margin-bottom: 8px;
            padding-left: 24px;
            position: relative;
        }

        .instructions li::before {
            content: '✓';
            position: absolute;
            left: 0;
            color: var(--primary-600);
            font-weight: bold;
        }

        .footer {
            text-align: center;
            margin-top: 40px;
            padding-top: 24px;
            border-top: 1px solid var(--border-color);
            color: var(--text-secondary);
            font-size: 14px;
        }

        .brand {
            color: var(--primary-700);
            font-weight: 700;
            font-size: 18px;
            margin-top: 8px;
            display: inline-block;
            padding: 6px 16px;
            background: var(--primary-50);
            border-radius: 20px;
        }

        @keyframes shimmer {
            0% { left: -100%; }
            100% { left: 100%; }
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        @media (max-width: 480px) {
            .content {
                padding: 32px 20px;
            }
            
            .header {
                padding: 32px 20px 32px;
            }
            
            .otp-code {
                font-size: 42px;
                letter-spacing: 6px;
                min-width: 240px;
                padding: 12px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="header">
                <div class="logo">
                    <div class="logo-icon">🔐</div>
                    EBAMAGE
                </div>
                <h1>Réinitialisation du mot de passe</h1>
            </div>
            
            <div class="content">
                <p class="welcome">Bonjour,</p>
                <p>Voici votre code de réinitialisation :</p>
                
                <div class="otp-container">
                    <span class="otp-label">Code à usage unique</span>
                    <div class="otp-code">{{ $otp }}</div>
                    
                    <div class="timer">
                        <svg class="timer-icon" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 2C6.5 2 2 6.5 2 12S6.5 22 12 22 22 17.5 22 12 17.5 2 12 2M12 20C7.59 20 4 16.41 4 12S7.59 4 12 4 20 7.59 20 12 16.41 20 12 20M12.5 7V12.25L17 14.92L16.25 16.15L11 13V7H12.5Z"/>
                        </svg>
                        Ce code expire dans 15 minutes
                    </div>
                </div>
                
                <div class="instructions">
                    <strong>Pour votre sécurité :</strong>
                    <ul>
                        <li>Ne partagez jamais ce code avec qui que ce soit</li>
                        <li>EBAMAGE ne vous demandera jamais votre mot de passe ou code OTP</li>
                        <li>Si vous n'avez pas demandé cette réinitialisation, ignorez cet email</li>
                    </ul>
                </div>
            </div>
            
            <div class="footer">
                <p>Si vous rencontrez des difficultés, contactez notre support</p>
                <div class="brand">— EBAMAGE</div>
            </div>
        </div>
    </div>

    <script>
        // Animation du code OTP
        const otpElement = document.querySelector('.otp-code');
        const originalOTP = otpElement.textContent;
        
        // Simuler un code OTP dynamique (à remplacer par votre logique réelle)
        function updateOTP(otp) {
            otpElement.textContent = otp.split('').join(' ');
        }
        
        // Compte à rebours
        function startCountdown(minutes) {
            let seconds = minutes * 60;
            const timerElement = document.querySelector('.timer');
            
            const countdown = setInterval(() => {
                seconds--;
                const mins = Math.floor(seconds / 60);
                const secs = seconds % 60;
                
                timerElement.innerHTML = `
                    <svg class="timer-icon" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 2C6.5 2 2 6.5 2 12S6.5 22 12 22 22 17.5 22 12 17.5 2 12 2M12 20C7.59 20 4 16.41 4 12S7.59 4 12 4 20 7.59 20 12 16.41 20 12 20M12.5 7V12.25L17 14.92L16.25 16.15L11 13V7H12.5Z"/>
                    </svg>
                    Ce code expire dans ${mins}:${secs < 10 ? '0' : ''}${secs}
                `;
                
                if (seconds <= 0) {
                    clearInterval(countdown);
                    timerElement.innerHTML = `
                        <svg class="timer-icon" viewBox="0 0 24 24" fill="#ef4444">
                            <path d="M12 2C6.5 2 2 6.5 2 12S6.5 22 12 22 22 17.5 22 12 17.5 2 12 2M12 20C7.59 20 4 16.41 4 12S7.59 4 12 4 20 7.59 20 12 16.41 20 12 20M12.5 7V12.25L17 14.92L16.25 16.15L11 13V7H12.5Z"/>
                        </svg>
                        Ce code a expiré
                    `;
                    otpElement.style.opacity = '0.5';
                    otpElement.style.textDecoration = 'line-through';
                }
            }, 1000);
        }
        
        // Démarrer le compte à rebours de 15 minutes
        startCountdown(15);
        
        // Pour l'intégration réelle, remplacer par :
        // updateOTP("{{ $otp }}");
    </script>
</body>
</html>