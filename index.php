<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BUYUNIC - Enrollment Portal</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <header class="header">
        <nav class="navbar">
            <div class="nav-container">
                <div class="nav-logo">
                    <img src="assets/images/logo.png" alt="BUYUNIC Logo" class="logo">
                    <span class="logo-text">BUYUNIC</span>
                </div>
                <div class="nav-menu">
                    <a href="#home" class="nav-link">Home</a>
                    <a href="#programs" class="nav-link">Programs</a>
                    <a href="#about" class="nav-link">About</a>
                    <a href="#contact" class="nav-link">Contact</a>
                    <a href="auth/login.php" class="nav-link btn-outline">Login</a>
                    <a href="auth/register.php" class="nav-link btn-primary">Apply Now</a>
                </div>
                <div class="hamburger">
                    <span></span>
                    <span></span>
                    <span></span>
                </div>
            </div>
        </nav>
    </header>

    <main>
        <section id="home" class="hero">
            <div class="hero-container">
                <div class="hero-content">
                    <h1 class="hero-title">Transform Your Future with Professional IT Training</h1>
                    <p class="hero-description">
                        Join BUYUNIC's comprehensive IT training programs and gain industry-relevant skills 
                        in Microsoft Office, Graphics Design, Web Development, and specialized IT solutions.
                    </p>
                    <div class="hero-buttons">
                        <a href="auth/register.php" class="btn btn-primary btn-large">
                            <i class="fas fa-user-plus"></i> Start Application
                        </a>
                        <a href="#programs" class="btn btn-secondary btn-large">
                            <i class="fas fa-book"></i> View Programs
                        </a>
                    </div>
                </div>
                <div class="hero-image">
                    <img src="assets/images/hero-illustration.svg" alt="IT Training Illustration">
                </div>
            </div>
        </section>

        <section id="programs" class="programs">
            <div class="container">
                <h2 class="section-title">Training Programs</h2>
                <div class="programs-grid">
                    <div class="program-card">
                        <div class="program-icon">
                            <i class="fab fa-microsoft"></i>
                        </div>
                        <h3>Microsoft Office Suite</h3>
                        <p>Master Word, Excel, PowerPoint, Access, and Publisher</p>
                        <div class="program-details">
                            <span class="duration">2-3 weeks</span>
                            <span class="price">From 50,000 UGX</span>
                        </div>
                    </div>

                    <div class="program-card">
                        <div class="program-icon">
                            <i class="fas fa-paint-brush"></i>
                        </div>
                        <h3>Graphics & Web Development</h3>
                        <p>Adobe Creative Suite, HTML, WordPress, and CorelDRAW</p>
                        <div class="program-details">
                            <span class="duration">4 weeks</span>
                            <span class="price">From 250,000 UGX</span>
                        </div>
                    </div>

                    <div class="program-card">
                        <div class="program-icon">
                            <i class="fas fa-network-wired"></i>
                        </div>
                        <h3>Specialized IT Programs</h3>
                        <p>Networking, CCTV, Accounting Software, Data Analysis</p>
                        <div class="program-details">
                            <span class="duration">3-6 weeks</span>
                            <span class="price">From 300,000 UGX</span>
                        </div>
                    </div>

                    <div class="program-card">
                        <div class="program-icon">
                            <i class="fas fa-research"></i>
                        </div>
                        <h3>Internship & Research</h3>
                        <p>Custom IT Projects and Research-Based Training</p>
                        <div class="program-details">
                            <span class="duration">2 months</span>
                            <span class="price">400,000 UGX</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section id="features" class="features">
            <div class="container">
                <h2 class="section-title">Why Choose BUYUNIC?</h2>
                <div class="features-grid">
                    <div class="feature-card">
                        <i class="fas fa-certificate"></i>
                        <h3>Industry Certification</h3>
                        <p>Get recognized certificates that boost your career prospects</p>
                    </div>
                    <div class="feature-card">
                        <i class="fas fa-users"></i>
                        <h3>Expert Instructors</h3>
                        <p>Learn from experienced professionals in the IT industry</p>
                    </div>
                    <div class="feature-card">
                        <i class="fas fa-laptop"></i>
                        <h3>Hands-on Training</h3>
                        <p>Practical sessions with real-world projects and scenarios</p>
                    </div>
                    <div class="feature-card">
                        <i class="fas fa-clock"></i>
                        <h3>Flexible Schedule</h3>
                        <p>Choose from morning, afternoon, or weekend classes</p>
                    </div>
                    <div class="feature-card">
                        <i class="fas fa-mobile-alt"></i>
                        <h3>Easy Mobile Payment</h3>
                        <p>Pay securely with MTN Mobile Money. Dial *165*3# and use Merchant ID: 693183</p>
                    </div>
                    <div class="feature-card">
                        <i class="fas fa-shield-alt"></i>
                        <h3>Secure Application</h3>
                        <p>Your data is protected with advanced security measures and encryption</p>
                    </div>
                </div>
            </div>
        </section>

        <section id="contact" class="contact">
            <div class="container">
                <h2 class="section-title">Get in Touch</h2>
                <div class="contact-grid">
                    <div class="contact-info">
                        <div class="contact-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <div>
                                <h4>Location</h4>
                                <p>Plot 28, North Road, Northern City Division, Mbale City</p>
                            </div>
                        </div>
                        <div class="contact-item">
                            <i class="fab fa-whatsapp"></i>
                            <div>
                                <h4>WhatsApp</h4>
                                <p>+256 207 901 434</p>
                            </div>
                        </div>
                        <div class="contact-item">
                            <i class="fas fa-envelope"></i>
                            <div>
                                <h4>Email</h4>
                                <p>info@buyunic.ug | apply@buyunic.ug</p>
                            </div>
                        </div>
                        <div class="contact-item">
                            <i class="fas fa-mobile-alt"></i>
                            <div>
                                <h4>MTN Mobile Money</h4>
                                <p>Dial: *165*3# | Merchant ID: 693183</p>
                            </div>
                        </div>
                    </div>
                    <div class="contact-form">
                        <form>
                            <div class="form-group">
                                <input type="text" placeholder="Your Name" required>
                            </div>
                            <div class="form-group">
                                <input type="email" placeholder="Your Email" required>
                            </div>
                            <div class="form-group">
                                <textarea placeholder="Your Message" rows="5" required></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">Send Message</button>
                        </form>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h4>BUYUNIC</h4>
                    <p>Empowering individuals with professional IT skills for a digital future.</p>
                </div>
                <div class="footer-section">
                    <h4>Quick Links</h4>
                    <ul>
                        <li><a href="auth/register.php">Apply Now</a></li>
                        <li><a href="auth/login.php">Student Portal</a></li>
                        <li><a href="admin/login.php">Admin Portal</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h4>Follow Us</h4>
                    <div class="social-links">
                        <a href="#"><i class="fab fa-facebook"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-linkedin"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2024 BUYUNIC. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="assets/js/main.js"></script>
</body>
</html>