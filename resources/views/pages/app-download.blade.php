<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fundi Management App - Download & Setup</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #01aef3 0%, #1c1a6d 50%, #1c1a6d 100%);
            min-height: 100vh;
            color: #333;
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            text-align: center;
            color: white;
            margin-bottom: 40px;
            padding: 40px 0;
        }

        .header h1 {
            font-size: 3rem;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }

        .header p {
            font-size: 1.2rem;
            opacity: 0.9;
        }

        .app-icon {
            width: 120px;
            height: 120px;
            background: #3D4ED8;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }

        .app-icon svg {
            width: 60px;
            height: 60px;
            fill: white;
        }

        .download-section {
            background: white;
            border-radius: 20px;
            padding: 40px;
            margin-bottom: 30px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            text-align: center;
        }

        .download-btn {
            background: linear-gradient(135deg, #3D4ED8, #4F46E5);
            color: white;
            padding: 20px 40px;
            border: none;
            border-radius: 15px;
            font-size: 1.2rem;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 15px;
            text-decoration: none;
            margin: 20px 0;
        }

        .download-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(61, 78, 216, 0.4);
        }

        .steps-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin-top: 40px;
        }

        .step-card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }

        .step-card:hover {
            transform: translateY(-5px);
        }

        .step-number {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #3D4ED8, #4F46E5);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1.2rem;
            margin-bottom: 20px;
        }

        .step-title {
            font-size: 1.4rem;
            font-weight: bold;
            color: #3D4ED8;
            margin-bottom: 15px;
        }

        .step-content {
            line-height: 1.6;
            color: #666;
        }

        .step-content ol {
            margin-left: 20px;
            margin-top: 10px;
        }

        .step-content li {
            margin-bottom: 8px;
        }

        .feature-highlight {
            background: #f8fafc;
            border-left: 4px solid #3D4ED8;
            padding: 15px;
            margin: 15px 0;
            border-radius: 0 10px 10px 0;
        }

        .warning {
            background: #fef3c7;
            border: 1px solid #f59e0b;
            padding: 15px;
            border-radius: 10px;
            margin: 20px 0;
        }

        .warning-icon {
            color: #f59e0b;
            font-weight: bold;
        }

        .tips-section {
            background: white;
            border-radius: 20px;
            padding: 40px;
            margin-top: 30px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
        }

        .tips-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
            margin-top: 30px;
        }

        .tip-card {
            background: linear-gradient(135deg, #f8fafc, #e2e8f0);
            padding: 25px;
            border-radius: 15px;
            border-left: 5px solid #3D4ED8;
        }

        .tip-title {
            font-weight: bold;
            color: #3D4ED8;
            margin-bottom: 10px;
            font-size: 1.1rem;
        }

        @media (max-width: 768px) {
            .header h1 {
                font-size: 2rem;
            }

            .container {
                padding: 15px;
            }

            .download-section, .step-card, .tips-section {
                padding: 25px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Fundi Management App</h1>
            <p>Construction Worker & Site Management System</p>
        </div>

        <div class="download-section">
            <h2 style="margin-bottom: 20px; color: #3D4ED8;">Download the App</h2>
            <p style="margin-bottom: 30px; color: #666;">Get the latest version of the Fundi Management App for Android devices</p>

            <a href="{{ route('download-apk') }}" class="download-btn">
                Download APK 
            </a>
            <p>Alternative link: <a href="https://we.tl/t-ektAAovq4R">Download APK </a></p>

            <div class="warning">
                <strong>Important:</strong> You may need to enable "Install from Unknown Sources" in your Android settings to install this app.
            </div>
        </div>

        <div class="steps-container">
            <div class="step-card">
                <div class="step-number">1</div>
                <div class="step-title">Download & Install</div>
                <div class="step-content">
                    <p>Follow these steps to install the app on your Android device:</p>
                    <ol>
                        <li>Click the download button above to get the APK file</li>
                        <li>Open your device Settings > Security</li>
                        <li>Enable "Install from Unknown Sources" or "Allow from this source"</li>
                        <li>Locate the downloaded APK file in your Downloads folder</li>
                        <li>Tap the file and follow the installation prompts</li>
                        <li>Once installed, find the app icon and open it</li>
                    </ol>

                    <div class="feature-highlight">
                        <strong>Note:</strong> The app requires Android 6.0 or higher to run properly.
                    </div>
                </div>
            </div>

            <div class="step-card">
                <div class="step-number">2</div>
                <div class="step-title">First Time Setup</div>
                <div class="step-content">
                    <p>When you first open the app, you'll see the login screen:</p>
                    <ol>
                        <li>Enter your provided phone number</li>
                        <li>Enter your password</li>
                        <li>Tap "Login" to access the system</li>
                        <li>If you forgot your password, use "Forgot Password?" link</li>
                    </ol>

                    <div class="feature-highlight">
                        <strong>Credentials:</strong> Your administrator will provide your login credentials. Contact them if you don't have access details.
                    </div>
                </div>
            </div>

            <div class="step-card">
                <div class="step-number">3</div>
                <div class="step-title">App Navigation</div>
                <div class="step-content">
                    <p>Once logged in, you'll see the main dashboard with:</p>
                    <ol>
                        <li><strong>Dashboard:</strong> Overview of registered workers and sites</li>
                        <li><strong>Mafundi:</strong> View and manage construction workers</li>
                        <li><strong>Sites:</strong> Track site visits and activities</li>
                        <li><strong>Profile:</strong> Manage your account settings</li>
                    </ol>

                    <div class="feature-highlight">
                        <strong>Tip:</strong> Use the "+" button to quickly add new workers or site visits.
                    </div>
                </div>
            </div>
        </div>

        <div class="tips-section">
            <h2 style="color: #3D4ED8; margin-bottom: 10px;">How to Use the App</h2>
            <p style="color: #666; margin-bottom: 30px;">Master these three essential features to get the most out of the Fundi Management App</p>

            <div class="tips-grid">
                <div class="tip-card">
                    <div class="tip-title">1. Login to the App</div>
                    <p>Use the credentials provided by your administrator. Enter your phone number and password on the welcome screen. The app supports secure authentication to protect your data.</p>
                </div>

                <div class="tip-card">
                    <div class="tip-title">2. Register New Fundi (Construction Worker)</div>
                    <p>Add new construction workers by filling in their personal information, contact details, professional ranking (Fundi Mkuu/Msaidizi), and ward location. You can also add a profile photo for easy identification.</p>
                </div>

                <div class="tip-card">
                    <div class="tip-title">3. Register New Site Visit</div>
                    <p>Record site visits by entering site information, location details, taking photos of the work progress, logging products used, and assigning workers to the visit. This helps track project progress and worker attendance.</p>
                </div>

                <div class="tip-card">
                    <div class="tip-title">Camera Access</div>
                    <p>The app uses your device camera to take photos of workers and site visits. Make sure to grant camera permissions when prompted for full functionality.</p>
                </div>
                <div class="tip-card">
                    <div class="tip-title">Location Services</div>
                    <p>Enable GPS/location services to automatically capture site coordinates and ensure accurate location tracking for site visits and worker assignments.</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        function downloadAPK() {
            // In a real implementation, this would point to your actual APK file
            // alert('APK download would start here. Please provide the actual APK file URL to enable downloads.');
            // Example of how you might implement the actual download:
            window.location.href = '/download-apk';
        }

        // Add some smooth scrolling for better UX
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });

        // Add fade-in animation for cards
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);

        // Observe all cards for animation
        document.querySelectorAll('.step-card, .tip-card').forEach(card => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            card.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
            observer.observe(card);
        });
    </script>
</body>
</html>
