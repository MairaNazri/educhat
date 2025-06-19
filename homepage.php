<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EDUCHAT - Master English Through Conversations</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            overflow-x: hidden;
            background-color: #d9c8f4;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* Header */
        header {
            background: linear-gradient(135deg, #b187d6 0%, #8a75c9 100%);
            color: white;
            padding: 1rem 0;
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
            box-shadow: 0 4px 20px rgba(177, 135, 214, 0.3);
        }

        nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-size: 1.8rem;
            font-weight: bold;
            text-decoration: none;
            color: white;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            background-color: black;
            padding: 10px 15px;
            border-radius: 8px;
        }

        .logo::before {
            content: "💬";
            font-size: 1.5rem;
        }

        .nav-links {
            display: flex;
            list-style: none;
            gap: 2rem;
        }

        .nav-links a {
            color: white;
            text-decoration: none;
            transition: color 0.3s ease;
            font-weight: 500;
            padding: 8px 16px;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .nav-links a:hover {
            background-color: rgba(255, 255, 255, 0.1);
            color: #f5f2ff;
        }

        .auth-buttons {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .auth-btn {
            padding: 10px 20px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 500;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }

        .login-btn {
            color: #6e50a1;
            background: white;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .login-btn:hover {
            background: #f3e6ff;
            transform: translateY(-2px);
        }

        .register-btn {
            background: #6e50a1;
            color: white;
        }

        .register-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(110, 80, 161, 0.4);
            background: #5a4285;
        }

        .mobile-menu {
            display: none;
            background: none;
            border: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
        }

        /* Hero Section */
        .hero {
            background: linear-gradient(135deg, #b187d6 0%, #8a75c9 100%);
            color: white;
            padding: 120px 0 80px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="dots" width="20" height="20" patternUnits="userSpaceOnUse"><circle cx="10" cy="10" r="1.5" fill="%23ffffff" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23dots)"/></svg>');
            animation: float 20s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }

        .hero-content {
            position: relative;
            z-index: 2;
            max-width: 800px;
            margin: 0 auto;
        }

        .hero h1 {
            font-size: 3.5rem;
            margin-bottom: 1.5rem;
            animation: slideInUp 1s ease-out;
            font-weight: 700;
        }

        .hero .subtitle {
            font-size: 1.4rem;
            margin-bottom: 2rem;
            opacity: 0.95;
            animation: slideInUp 1s ease-out 0.3s both;
            font-weight: 300;
        }

        .hero .description {
            font-size: 1.1rem;
            margin-bottom: 2.5rem;
            opacity: 0.9;
            animation: slideInUp 1s ease-out 0.6s both;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }

        .cta-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
            animation: slideInUp 1s ease-out 0.9s both;
        }

        .cta-button {
            display: inline-block;
            padding: 15px 30px;
            border-radius: 15px;
            text-decoration: none;
            font-weight: 600;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .cta-primary {
            background: white;
            color: #6e50a1;
        }

        .cta-secondary {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            border: 2px solid rgba(255, 255, 255, 0.3);
            backdrop-filter: blur(10px);
        }

        .cta-button:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
        }

        .cta-primary:hover {
            background: #f3e6ff;
        }

        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Features Section */
        .features {
            padding: 100px 0;
            background: #e5dffc;
        }

        .section-title {
            text-align: center;
            font-size: 2.8rem;
            margin-bottom: 1rem;
            color: #333;
            font-weight: 700;
        }

        .section-subtitle {
            text-align: center;
            font-size: 1.2rem;
            color: #666;
            margin-bottom: 4rem;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 2.5rem;
            margin-top: 3rem;
        }

        .feature-card {
            background: #f5f2ff;
            padding: 2.5rem;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
            transition: all 0.4s ease;
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(177, 135, 214, 0.2);
        }

        .feature-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(45deg, #b187d6, #8a75c9);
            transform: scaleX(0);
            transition: transform 0.4s ease;
        }

        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 25px 50px rgba(177, 135, 214, 0.3);
        }

        .feature-card:hover::before {
            transform: scaleX(1);
        }

        .feature-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(45deg, #b187d6, #8a75c9);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.5rem;
            color: white;
            font-size: 2rem;
            box-shadow: 0 8px 25px rgba(177, 135, 214, 0.3);
        }

        .feature-card h3 {
            font-size: 1.8rem;
            margin-bottom: 1rem;
            color: #333;
            font-weight: 600;
        }

        .feature-card p {
            color: #666;
            line-height: 1.7;
            font-size: 1.05rem;
        }

        .feature-benefits {
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid rgba(177, 135, 214, 0.2);
        }

        .feature-benefits ul {
            list-style: none;
            margin: 0;
            padding: 0;
        }

        .feature-benefits li {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
            color: #333;
            font-size: 0.95rem;
        }

        .feature-benefits li::before {
            content: "✓";
            color: #6fcf97;
            font-weight: bold;
        }

        /* How It Works Section */
        .how-it-works {
            padding: 100px 0;
            background: #f5f2ff;
        }

        .steps-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-top: 3rem;
        }

        .step {
            text-align: center;
            padding: 2rem 1rem;
            background: white;
            border-radius: 20px;
            box-shadow: 0 8px 25px rgba(177, 135, 214, 0.1);
            transition: all 0.3s ease;
        }

        .step:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(177, 135, 214, 0.2);
        }

        .step-number {
            width: 60px;
            height: 60px;
            background: linear-gradient(45deg, #b187d6, #8a75c9);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            font-weight: bold;
            margin: 0 auto 1.5rem;
        }

        .step h3 {
            font-size: 1.3rem;
            margin-bottom: 1rem;
            color: #333;
        }

        .step p {
            color: #666;
            line-height: 1.6;
        }

        /* Footer */
        footer {
            background: #333;
            color: white;
            padding: 60px 0 30px;
        }

        .footer-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .footer-section h3 {
            margin-bottom: 1rem;
            color: #b187d6;
        }

        .footer-section p, .footer-section a {
            color: #ccc;
            text-decoration: none;
            line-height: 1.6;
        }

        .footer-section a:hover {
            color: #b187d6;
        }

        .footer-bottom {
            text-align: center;
            padding-top: 2rem;
            border-top: 1px solid #555;
            color: #999;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .nav-links, .auth-buttons {
                display: none;
            }

            .mobile-menu {
                display: block;
            }

            .hero h1 {
                font-size: 2.5rem;
            }

            .hero .subtitle {
                font-size: 1.2rem;
            }

            .features-grid {
                grid-template-columns: 1fr;
            }

            .cta-buttons {
                flex-direction: column;
                align-items: center;
            }

            .cta-button {
                width: 100%;
                max-width: 300px;
            }

            .section-title {
                font-size: 2.2rem;
            }

            .steps-container {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 480px) {
            .container {
                padding: 0 15px;
            }

            .hero {
                padding: 100px 0 60px;
            }

            .features, .how-it-works {
                padding: 60px 0;
            }

            .feature-card, .step {
                padding: 1.5rem;
            }
        }

        /* Animation on scroll */
        .fade-in {
            opacity: 0;
            transform: translateY(30px);
            transition: all 0.6s ease;
        }

        .fade-in.visible {
            opacity: 1;
            transform: translateY(0);
        }
    </style>
</head>
<body>
    <header>
        <nav class="container">
            <a href="#" class="logo">EDUCHAT</a>
            <ul class="nav-links">
                <li><a href="#home">Home</a></li>
                <li><a href="#features">Features</a></li>
                <li><a href="#how-it-works">How It Works</a></li>
            </ul>
            <div class="auth-buttons">
                <a href="login.php" class="auth-btn login-btn">Login</a>
                <a href="register.php" class="auth-btn register-btn">Register</a>
            </div>
            <button class="mobile-menu">☰</button>
        </nav>
    </header>

    <section class="hero" id="home">
        <div class="container">
            <div class="hero-content">
                <h1>Master English Through Conversations</h1>
                <p class="subtitle">Interactive chatbot conversations that make learning English natural and fun</p>
                <p class="description">Practice your English, expand your vocabulary, and improve your grammar through conversations, quizzes, and spaced repetition flashcards.</p>
                <div class="cta-buttons">
                    <a href="register.php" class="cta-button cta-primary">Get Started Today</a>
                    <a href="#how-it-works" class="cta-button cta-secondary">See How It Works</a>
                </div>
            </div>
        </div>
    </section>

    <section class="features" id="features">
        <div class="container">
            <h2 class="section-title">Three Powerful Learning Tools</h2>
            <p class="section-subtitle">Everything you need to master English</p>
            
            <div class="features-grid">
                <div class="feature-card fade-in">
                    <div class="feature-icon"><i class="fas fa-comment"></i></div>
                    <h3>Chatbot Conversations</h3>
                    <p>Practice English naturally through interactive conversations with our chatbot. Get real-time feedback, corrections, and personalized learning paths.</p>
                    <div class="feature-benefits">
                        <ul>
                            <li>24/7 conversation practice</li>
                            <li>Real-time grammar corrections</li>
                            <li>Adaptive difficulty levels</li>
                            <li>Topic-based conversations</li>
                        </ul>
                    </div>
                </div>

                <div class="feature-card fade-in">
                    <div class="feature-icon"><i class="fas fa-clipboard"></i></div>
                    <h3>Interactive Quizzes</h3>
                    <p>Strengthen your vocabulary and grammar skills with engaging quizzes that adapt to your learning progress.</p>
                    <div class="feature-benefits">
                        <ul>
                            <li>Vocabulary building exercises</li>
                            <li>Grammar practice tests</li>
                            <li>Progress tracking</li>
                            <li>Difficulty adaptation</li>
                        </ul>
                    </div>
                </div>

                <div class="feature-card fade-in">
                    <div class="feature-icon"><i class="fas fa-clone"></i></div>
                    <h3>Smart Flashcards</h3>
                    <p>Memorize new words and phrases efficiently with our spaced repetition system that shows you cards just when you're about to forget them.</p>
                    <div class="feature-benefits">
                        <ul>
                            <li>Spaced repetition</li>
                            <li>Custom card creation</li>
                            <li>Progress statistics</li>
                            <li>Offline study mode</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="how-it-works" id="how-it-works">
        <div class="container">
            <h2 class="section-title">How It Works</h2>
            <p class="section-subtitle">Start your English learning journey in just three simple steps</p>
            
            <div class="steps-container">
                <div class="step fade-in">
                    <div class="step-number">1</div>
                    <h3>Start Chatting</h3>
                    <p>Begin conversations with our chatbot on topics you're interested in. Practice naturally while getting instant feedback.</p>
                </div>
                
                <div class="step fade-in">
                    <div class="step-number">2</div>
                    <h3>Take Quizzes</h3>
                    <p>Test your knowledge with quizzes that focus on vocabulary and grammar concepts you need to improve.</p>
                </div>
                
                <div class="step fade-in">
                    <div class="step-number">3</div>
                    <h3>Review with Flashcards</h3>
                    <p>Reinforce your learning with spaced repetition flashcards that help you remember new words and phrases long-term.</p>
                </div>
            </div>
        </div>
    </section>

    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>EDUCHAT</h3>
                    <p>Making English learning accessible, engaging, and effective through the power of chatbot.</p>
                </div>
                <div class="footer-section">
                    <h3>Features</h3>
                    <p><a href="#features">Chatbot Conversations</a></p>
                    <p><a href="#features">Interactive Quizzes</a></p>
                    <p><a href="#features">Smart Flashcards</a></p>
                </div>
                <div class="footer-section">
                    <h3>Get Started</h3>
                    <p><a href="register.php">Create Account</a></p>
                    <p><a href="login.php">Sign In</a></p>
                    <p><a href="#how-it-works">How It Works</a></p>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2025 EDUCHAT. All rights reserved. | Privacy Policy | Terms of Service</p>
            </div>
        </div>
    </footer>

    <script>
        // Smooth scrolling for navigation links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Header background on scroll
        window.addEventListener('scroll', () => {
            const header = document.querySelector('header');
            if (window.scrollY > 100) {
                header.style.background = 'rgba(177, 135, 214, 0.95)';
                header.style.backdropFilter = 'blur(10px)';
            } else {
                header.style.background = 'linear-gradient(135deg, #b187d6 0%, #8a75c9 100%)';
                header.style.backdropFilter = 'none';
            }
        });

        // Animate elements on scroll
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                }
            });
        }, observerOptions);

        document.querySelectorAll('.fade-in').forEach(el => {
            observer.observe(el);
        });

        // Mobile menu toggle (basic implementation)
        document.querySelector('.mobile-menu').addEventListener('click', function() {
            const navLinks = document.querySelector('.nav-links');
            const authButtons = document.querySelector('.auth-buttons');
            
            if (navLinks.style.display === 'flex') {
                navLinks.style.display = 'none';
                authButtons.style.display = 'none';
            } else {
                navLinks.style.display = 'flex';
                navLinks.style.flexDirection = 'column';
                navLinks.style.position = 'absolute';
                navLinks.style.top = '100%';
                navLinks.style.left = '0';
                navLinks.style.right = '0';
                navLinks.style.background = 'rgba(177, 135, 214, 0.95)';
                navLinks.style.padding = '1rem';
                navLinks.style.backdropFilter = 'blur(10px)';
                
                authButtons.style.display = 'flex';
                authButtons.style.flexDirection = 'column';
                authButtons.style.position = 'absolute';
                authButtons.style.top = '200%';
                authButtons.style.left = '0';
                authButtons.style.right = '0';
                authButtons.style.background = 'rgba(177, 135, 214, 0.95)';
                authButtons.style.padding = '1rem';
                authButtons.style.backdropFilter = 'blur(10px)';
            }
        });
    </script>
</body>
</html>