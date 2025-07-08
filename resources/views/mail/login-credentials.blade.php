<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Credentials - Fundi System</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .container {
            background-color: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 10px;
        }
        .welcome-message {
            background-color: #e8f5e8;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 25px;
            border-left: 4px solid #27ae60;
        }
        .credentials-section {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .credentials-title {
            font-size: 18px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 15px;
            border-bottom: 2px solid #3498db;
            padding-bottom: 5px;
        }
        .credential-item {
            margin: 10px 0;
            padding: 10px;
            background-color: white;
            border-radius: 3px;
            border: 1px solid #dee2e6;
        }
        .credential-label {
            font-weight: bold;
            color: #495057;
            display: inline-block;
            width: 80px;
        }
        .credential-value {
            color: #2c3e50;
            font-family: monospace;
            background-color: #f8f9fa;
            padding: 2px 6px;
            border-radius: 3px;
        }
        .security-notice {
            background-color: #fff3cd;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            border-left: 4px solid #ffc107;
        }
        .security-notice strong {
            color: #856404;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            color: #6c757d;
            font-size: 14px;
        }
        .contact-info {
            margin-top: 20px;
            padding: 15px;
            background-color: #e3f2fd;
            border-radius: 5px;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 10px 0;
        }
        .btn:hover {
            background-color: #2980b9;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">Fundi System</div>
            <p>Welcome to the Fundi Management Platform</p>
        </div>

        <div class="welcome-message">
            <h2>Welcome {{ $user->fname }} {{ $user->lname }}!</h2>
            <p>Your account has been successfully created as a <strong>{{ $roleName }}</strong>
            @if($districtName !== 'N/A')
                for <strong>{{ $districtName }}</strong> district.
            @endif
            </p>
        </div>

        <div class="credentials-section">
            <div class="credentials-title">🌐 Web Application Access</div>
            <div class="credential-item">
                <span class="credential-label">Email:</span>
                <span class="credential-value">{{ $user->email }}</span>
            </div>
            <div class="credential-item">
                <span class="credential-label">Password:</span>
                <span class="credential-value">{{ $password }}</span>
            </div>
            <p><strong>Web URL:</strong> <a href="{{ config('app.url') }}" class="btn">Access Web App</a></p>
        </div>

        <div class="credentials-section">
            <div class="credentials-title">📱 Mobile Application Access</div>
            <div class="credential-item">
                <span class="credential-label">Phone:</span>
                <span class="credential-value">{{ $user->phone }}</span>
            </div>
            <div class="credential-item">
                <span class="credential-label">Password:</span>
                <span class="credential-value">{{ $password }}</span>
            </div>
            <p><strong>Note:</strong> Download the Fundi mobile app from your app store.</p>
        </div>

        <div class="security-notice">
            <strong>🔒 Security Notice:</strong><br>
            For your security, please change your password immediately after your first login.
            Use a strong password with a combination of letters, numbers, and special characters.
        </div>

        <div class="contact-info">
            <h3>Need Help?</h3>
            <p>If you have any questions or need assistance, please contact our support team:</p>
            <ul>
                <li>Email: support@fundisystem.com</li>
                <li>Phone: +255 XXX XXX XXX</li>
            </ul>
        </div>

        <div class="footer">
            <p>This is an automated message. Please do not reply to this email.</p>
            <p>&copy; {{ date('Y') }} Fundi System. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
