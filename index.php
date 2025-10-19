<?php
require_once 'config.php';

$db = getDB();

// Fetch data for all sections
$hero = $db->query("SELECT * FROM hero_section WHERE is_active = 1 LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$about = $db->query("SELECT * FROM about_section WHERE is_active = 1 LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$products = $db->query("SELECT * FROM products WHERE is_active = 1 ORDER BY is_featured DESC, sort_order ASC, created_at DESC LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);
$experience = $db->query("SELECT * FROM experience_section WHERE is_active = 1 LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$gallery = $db->query("SELECT * FROM gallery WHERE is_active = 1 ORDER BY sort_order ASC, created_at DESC LIMIT 6")->fetchAll(PDO::FETCH_ASSOC);
$testimonials = $db->query("SELECT * FROM testimonials WHERE is_active = 1 ORDER BY sort_order ASC, created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
$partners = $db->query("SELECT * FROM partners_section WHERE is_active = 1 LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$contact_info = $db->query("SELECT * FROM contact_info WHERE is_active = 1 ORDER BY sort_order ASC")->fetchAll(PDO::FETCH_ASSOC);
$social_links = $db->query("SELECT * FROM social_links WHERE is_active = 1 ORDER BY sort_order ASC")->fetchAll(PDO::FETCH_ASSOC);
$footer_content = $db->query("SELECT * FROM footer_content WHERE is_active = 1 ORDER BY sort_order ASC")->fetchAll(PDO::FETCH_ASSOC);

// Handle contact form submission
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['contact_form'])) {
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $phone = sanitize($_POST['phone']);
    $message_text = sanitize($_POST['message']);

    if (empty($name) || empty($email) || empty($message_text)) {
        $message = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Please enter a valid email address.';
    } else {
        $stmt = $db->prepare("INSERT INTO contact_submissions (name, email, phone, message, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $name,
            $email,
            $phone,
            $message_text,
            $_SERVER['REMOTE_ADDR'],
            $_SERVER['HTTP_USER_AGENT']
        ]);
        $message = 'Thank you for your message! We will get back to you soon.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($hero['title'] ?? '360 Media - Luxury 360 Video Booths'); ?></title>
    <meta name="description" content="Pakistan's leading manufacturer of premium 360 video booth machines. Experience elegance, performance, and innovation with our ultra-slim, smart booths.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <!-- Navigation -->
    <nav id="navbar">
        <div class="nav-container">
            <div class="logo">
                <h1>360 Media</h1>
            </div>
            <ul class="nav-links">
                <li><a href="#hero">Home</a></li>
                <li><a href="#about">About</a></li>
                <li><a href="#products">Products</a></li>
                <li><a href="#experience">Experience</a></li>
                <li><a href="#gallery">Gallery</a></li>
                <li><a href="#testimonials">Testimonials</a></li>
                <li><a href="#partners">Partners</a></li>
                <li><a href="#contact">Contact</a></li>
            </ul>
            <div class="nav-toggle">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section id="hero">
        <div class="hero-overlay"></div>
        <?php if ($hero['background_video'] && file_exists('uploads/' . $hero['background_video'])): ?>
            <video autoplay muted loop playsinline class="hero-video">
                <source src="uploads/<?php echo htmlspecialchars($hero['background_video']); ?>" type="video/mp4">
                <?php if ($hero['background_image'] && file_exists('uploads/' . $hero['background_image'])): ?>
                    <img src="uploads/<?php echo htmlspecialchars($hero['background_image']); ?>" alt="360 Video Booth">
                <?php endif; ?>
            </video>
        <?php elseif ($hero['background_image'] && file_exists('uploads/' . $hero['background_image'])): ?>
            <div class="hero-image" style="background-image: url('uploads/<?php echo htmlspecialchars($hero['background_image']); ?>');"></div>
        <?php endif; ?>
        <div class="hero-content">
            <h1 class="hero-title"><?php echo htmlspecialchars($hero['title'] ?? 'Luxury in Motion'); ?></h1>
            <p class="hero-subtitle"><?php echo htmlspecialchars($hero['subtitle'] ?? 'Experience Every Angle with Pakistan\'s Leading 360 Video Booth Manufacturer'); ?></p>
            <div class="hero-ctas">
                <a href="<?php echo htmlspecialchars($hero['cta_primary_link'] ?? '#products'); ?>" class="btn btn-primary"><?php echo htmlspecialchars($hero['cta_primary_text'] ?? 'Explore Booths'); ?></a>
                <a href="<?php echo htmlspecialchars($hero['cta_secondary_link'] ?? '#contact'); ?>" class="btn btn-secondary"><?php echo htmlspecialchars($hero['cta_secondary_text'] ?? 'Get a Quote'); ?></a>
            </div>
        </div>
        <div class="scroll-indicator">
            <i class="fas fa-chevron-down"></i>
        </div>
    </section>

    <!-- About Section -->
    <section id="about">
        <div class="container">
            <div class="about-grid">
                <div class="about-content">
                    <h2><?php echo htmlspecialchars($about['title'] ?? 'About 360 Media'); ?></h2>
                    <p><?php echo nl2br(htmlspecialchars($about['content'] ?? '360 Media is Pakistan\'s leading manufacturer of smart, ultra-slim 360 video booths built for elegance, performance, and innovation. Our products serve wedding planners, event management companies, media houses, and creative professionals who demand sophistication and reliability.')); ?></p>
                    <?php if ($about['highlight_text']): ?>
                        <p class="highlight"><?php echo htmlspecialchars($about['highlight_text']); ?></p>
                    <?php endif; ?>
                    <?php if ($about['stats']): ?>
                        <div class="about-stats">
                            <?php $stats = json_decode($about['stats'], true); ?>
                            <?php foreach ($stats as $stat): ?>
                                <div class="stat">
                                    <h3><?php echo htmlspecialchars($stat['number']); ?></h3>
                                    <p><?php echo htmlspecialchars($stat['label']); ?></p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="about-image">
                    <?php if ($about['image'] && file_exists('uploads/' . $about['image'])): ?>
                        <img src="uploads/<?php echo htmlspecialchars($about['image']); ?>" alt="Manufacturing Process">
                    <?php else: ?>
                        <img src="images/manufacturing.jpg" alt="Manufacturing Process">
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- Products Section -->
    <section id="products">
        <div class="container">
            <h2>Our Premium Booths</h2>
            <div class="products-grid">
                <?php foreach ($products as $product): ?>
                    <div class="product-card">
                        <div class="product-image">
                            <?php if ($product['image'] && file_exists('uploads/' . $product['image'])): ?>
                                <img src="uploads/<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                            <?php else: ?>
                                <div class="placeholder">
                                    <i class="fas fa-box"></i>
                                </div>
                            <?php endif; ?>
                            <div class="product-overlay">
                                <button class="btn btn-primary">View Details</button>
                            </div>
                        </div>
                        <div class="product-info">
                            <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                            <?php if ($product['features']): ?>
                                <ul class="features">
                                    <?php $features = json_decode($product['features'], true); ?>
                                    <?php foreach ($features as $feature): ?>
                                        <li><i class="fas fa-check"></i> <?php echo htmlspecialchars($feature); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                            <a href="#contact" class="btn btn-secondary">Order Now</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Experience Section -->
    <section id="experience">
        <div class="experience-container">
            <div class="experience-content">
                <h2><?php echo htmlspecialchars($experience['title'] ?? 'Where Technology Meets Celebration'); ?></h2>
                <p><?php echo nl2br(htmlspecialchars($experience['content'] ?? 'Our 360 video booths transform ordinary events into extraordinary experiences. From intimate weddings to grand corporate functions, we capture every moment in stunning 360-degree detail.')); ?></p>
                <?php if ($experience['features']): ?>
                    <div class="experience-features">
                        <?php $features = json_decode($experience['features'], true); ?>
                        <?php foreach ($features as $feature): ?>
                            <div class="feature">
                                <i class="fas fa-<?php echo htmlspecialchars($feature['icon'] ?? 'star'); ?>"></i>
                                <h3><?php echo htmlspecialchars($feature['title']); ?></h3>
                                <p><?php echo htmlspecialchars($feature['description']); ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="experience-video">
                <?php if ($experience['background_video'] && file_exists('uploads/' . $experience['background_video'])): ?>
                    <video autoplay muted loop playsinline>
                        <source src="uploads/<?php echo htmlspecialchars($experience['background_video']); ?>" type="video/mp4">
                        <img src="images/experience-fallback.jpg" alt="360 Booth Experience">
                    </video>
                <?php elseif ($experience['background_image'] && file_exists('uploads/' . $experience['background_image'])): ?>
                    <img src="uploads/<?php echo htmlspecialchars($experience['background_image']); ?>" alt="360 Booth Experience">
                <?php else: ?>
                    <img src="images/experience-fallback.jpg" alt="360 Booth Experience">
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Gallery Section -->
    <section id="gallery">
        <div class="container">
            <h2>Event Showcase</h2>
            <div class="gallery-grid">
                <?php foreach ($gallery as $item): ?>
                    <div class="gallery-item">
                        <?php if ($item['image'] && file_exists('uploads/' . $item['image'])): ?>
                            <img src="uploads/<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['title'] ?? 'Gallery Item'); ?>">
                        <?php else: ?>
                            <img src="images/gallery-<?php echo $item['id'] % 6 + 1; ?>.jpg" alt="Gallery Item">
                        <?php endif; ?>
                        <div class="gallery-overlay">
                            <i class="fas fa-play"></i>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Testimonials Section -->
    <section id="testimonials">
        <div class="container">
            <h2>What Our Clients Say</h2>
            <div class="testimonials-slider">
                <?php foreach ($testimonials as $index => $testimonial): ?>
                    <div class="testimonial <?php echo $index === 0 ? 'active' : ''; ?>">
                        <div class="testimonial-content">
                            <i class="fas fa-quote-left"></i>
                            <p><?php echo nl2br(htmlspecialchars($testimonial['content'])); ?></p>
                            <div class="testimonial-author">
                                <h4><?php echo htmlspecialchars($testimonial['name']); ?></h4>
                                <span><?php echo htmlspecialchars($testimonial['company'] ?? $testimonial['position']); ?></span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php if (count($testimonials) > 1): ?>
                <div class="testimonial-dots">
                    <?php for ($i = 0; $i < count($testimonials); $i++): ?>
                        <span class="dot <?php echo $i === 0 ? 'active' : ''; ?>"></span>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Partners Section -->
    <section id="partners">
        <div class="container">
            <h2><?php echo htmlspecialchars($partners['title'] ?? 'Become a Distributor'); ?></h2>
            <p><?php echo nl2br(htmlspecialchars($partners['content'] ?? 'Join our exclusive network of partners and bring luxury 360 video experiences to your region. We offer comprehensive support, training, and marketing materials to ensure your success.')); ?></p>
            <?php if ($partners['benefits']): ?>
                <div class="partner-benefits">
                    <?php $benefits = json_decode($partners['benefits'], true); ?>
                    <?php foreach ($benefits as $benefit): ?>
                        <div class="benefit">
                            <i class="fas fa-<?php echo htmlspecialchars($benefit['icon'] ?? 'star'); ?>"></i>
                            <h3><?php echo htmlspecialchars($benefit['title']); ?></h3>
                            <p><?php echo htmlspecialchars($benefit['description']); ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <a href="#contact" class="btn btn-primary">Join Our Partner Network</a>
        </div>
    </section>

    <!-- Contact Section -->
    <section id="contact">
        <div class="container">
            <div class="contact-grid">
                <div class="contact-info">
                    <h2>Get Your Quote</h2>
                    <p>Ready to elevate your events with our premium 360 video booths? Contact us today for a personalized consultation and quote.</p>
                    <div class="contact-details">
                        <?php foreach ($contact_info as $info): ?>
                            <div class="contact-item">
                                <i class="fas fa-<?php echo htmlspecialchars($info['icon'] ?? 'info'); ?>"></i>
                                <span><?php echo htmlspecialchars($info['value']); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="social-links">
                        <?php foreach ($social_links as $link): ?>
                            <a href="<?php echo htmlspecialchars($link['url']); ?>" class="social-link" target="_blank">
                                <i class="fab fa-<?php echo htmlspecialchars($link['icon_class'] ?? 'link'); ?>"></i>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="contact-form">
                    <?php if ($message): ?>
                        <div class="message <?php echo strpos($message, 'Thank you') === 0 ? 'success' : 'error'; ?>">
                            <?php echo $message; ?>
                        </div>
                    <?php endif; ?>
                    <form method="POST">
                        <input type="hidden" name="contact_form" value="1">
                        <div class="form-group">
                            <input type="text" id="name" name="name" required placeholder="Your Name">
                        </div>
                        <div class="form-group">
                            <input type="email" id="email" name="email" required placeholder="Email Address">
                        </div>
                        <div class="form-group">
                            <input type="tel" id="phone" name="phone" placeholder="Phone Number">
                        </div>
                        <div class="form-group">
                            <textarea id="message" name="message" rows="5" required placeholder="Tell us about your event..."></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Send Message</button>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer id="footer">
        <div class="container">
            <div class="footer-content">
                <?php foreach ($footer_content as $section): ?>
                    <div class="footer-section">
                        <h4><?php echo htmlspecialchars($section['section_name']); ?></h4>
                        <?php if ($section['content']): ?>
                            <p><?php echo nl2br(htmlspecialchars($section['content'])); ?></p>
                        <?php endif; ?>
                        <?php if ($section['links']): ?>
                            <ul>
                                <?php $links = json_decode($section['links'], true); ?>
                                <?php foreach ($links as $link): ?>
                                    <li><a href="<?php echo htmlspecialchars($link['url'] ?? '#'); ?>"><?php echo htmlspecialchars($link['text']); ?></a></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
                <div class="footer-section">
                    <h4>Connect</h4>
                    <div class="social-links">
                        <?php foreach ($social_links as $link): ?>
                            <a href="<?php echo htmlspecialchars($link['url']); ?>" class="social-link" target="_blank">
                                <i class="fab fa-<?php echo htmlspecialchars($link['icon_class'] ?? 'link'); ?>"></i>
                            </a>
                        <?php endforeach; ?>
                    </div>
                    <p class="copyright">&copy; <?php echo date('Y'); ?> 360 Media. All rights reserved.</p>
                </div>
            </div>
        </div>
    </footer>

    <!-- WhatsApp Chat -->
    <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $contact_info[1]['value'] ?? '923001234567'); ?>" class="whatsapp-float" target="_blank">
        <i class="fab fa-whatsapp"></i>
    </a>

    <!-- Scripts -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/ScrollTrigger.min.js"></script>
    <script src="script.js"></script>
</body>
</html>
