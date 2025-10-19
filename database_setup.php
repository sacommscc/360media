<?php
require_once 'config.php';

try {
    $db = getDB();

    // Create tables
    $tables = [
        // Admin users table
        "CREATE TABLE IF NOT EXISTS admin_users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT UNIQUE NOT NULL,
            password TEXT NOT NULL,
            email TEXT UNIQUE NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            last_login DATETIME
        )",

        // Site settings table
        "CREATE TABLE IF NOT EXISTS site_settings (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            setting_key TEXT UNIQUE NOT NULL,
            setting_value TEXT,
            setting_type TEXT DEFAULT 'text',
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )",

        // Hero section table
        "CREATE TABLE IF NOT EXISTS hero_section (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT NOT NULL,
            subtitle TEXT NOT NULL,
            background_video TEXT,
            background_image TEXT,
            cta_primary_text TEXT DEFAULT 'Explore Booths',
            cta_primary_link TEXT DEFAULT '#products',
            cta_secondary_text TEXT DEFAULT 'Get a Quote',
            cta_secondary_link TEXT DEFAULT '#contact',
            is_active INTEGER DEFAULT 1,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )",

        // About section table
        "CREATE TABLE IF NOT EXISTS about_section (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT DEFAULT 'About 360 Media',
            content TEXT NOT NULL,
            highlight_text TEXT,
            stats TEXT, -- JSON format
            image TEXT,
            is_active INTEGER DEFAULT 1,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )",

        // Products table
        "CREATE TABLE IF NOT EXISTS products (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            description TEXT,
            image TEXT,
            features TEXT, -- JSON format
            price_range TEXT,
            is_featured INTEGER DEFAULT 0,
            sort_order INTEGER DEFAULT 0,
            is_active INTEGER DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )",

        // Experience section table
        "CREATE TABLE IF NOT EXISTS experience_section (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT DEFAULT 'Where Technology Meets Celebration',
            content TEXT NOT NULL,
            background_video TEXT,
            background_image TEXT,
            features TEXT, -- JSON format
            is_active INTEGER DEFAULT 1,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )",

        // Gallery images table
        "CREATE TABLE IF NOT EXISTS gallery (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT,
            image TEXT NOT NULL,
            category TEXT DEFAULT 'general',
            video_url TEXT,
            sort_order INTEGER DEFAULT 0,
            is_active INTEGER DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )",

        // Testimonials table
        "CREATE TABLE IF NOT EXISTS testimonials (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            position TEXT,
            company TEXT,
            content TEXT NOT NULL,
            image TEXT,
            rating INTEGER DEFAULT 5,
            sort_order INTEGER DEFAULT 0,
            is_active INTEGER DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )",

        // Partners section table
        "CREATE TABLE IF NOT EXISTS partners_section (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT DEFAULT 'Become a Distributor',
            content TEXT NOT NULL,
            benefits TEXT, -- JSON format
            is_active INTEGER DEFAULT 1,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )",

        // Contact information table
        "CREATE TABLE IF NOT EXISTS contact_info (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            type TEXT NOT NULL, -- email, phone, address
            value TEXT NOT NULL,
            label TEXT,
            icon TEXT,
            is_active INTEGER DEFAULT 1,
            sort_order INTEGER DEFAULT 0,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )",

        // Social media links table
        "CREATE TABLE IF NOT EXISTS social_links (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            platform TEXT NOT NULL,
            url TEXT NOT NULL,
            icon_class TEXT,
            is_active INTEGER DEFAULT 1,
            sort_order INTEGER DEFAULT 0,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )",

        // Contact form submissions table
        "CREATE TABLE IF NOT EXISTS contact_submissions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            email TEXT NOT NULL,
            phone TEXT,
            message TEXT NOT NULL,
            ip_address TEXT,
            user_agent TEXT,
            is_read INTEGER DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )",

        // Footer content table
        "CREATE TABLE IF NOT EXISTS footer_content (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            section_name TEXT NOT NULL,
            content TEXT,
            links TEXT, -- JSON format
            is_active INTEGER DEFAULT 1,
            sort_order INTEGER DEFAULT 0,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )"
    ];

    foreach ($tables as $table) {
        $db->exec($table);
    }

    // Insert default admin user
    $admin_password = password_hash('admin123', PASSWORD_DEFAULT);
    $stmt = $db->prepare("INSERT OR IGNORE INTO admin_users (username, password, email) VALUES (?, ?, ?)");
    $stmt->execute(['admin', $admin_password, 'admin@360media.pk']);

    // Insert default site settings
    $default_settings = [
        ['site_name', '360 Media', 'text'],
        ['site_description', 'Pakistan\'s leading manufacturer of premium 360 video booth machines', 'text'],
        ['contact_email', 'info@360media.pk', 'email'],
        ['contact_phone', '+92 300 1234567', 'text'],
        ['whatsapp_number', '+923001234567', 'text'],
        ['address', 'Lahore, Pakistan', 'text'],
        ['facebook_url', '#', 'url'],
        ['instagram_url', '#', 'url'],
        ['tiktok_url', '#', 'url'],
        ['youtube_url', '#', 'url']
    ];

    $stmt = $db->prepare("INSERT OR IGNORE INTO site_settings (setting_key, setting_value, setting_type) VALUES (?, ?, ?)");
    foreach ($default_settings as $setting) {
        $stmt->execute($setting);
    }

    // Insert default hero content
    $stmt = $db->prepare("INSERT OR IGNORE INTO hero_section (title, subtitle) VALUES (?, ?)");
    $stmt->execute(['Luxury in Motion', 'Experience Every Angle with Pakistan\'s Leading 360 Video Booth Manufacturer']);

    // Insert default about content
    $stmt = $db->prepare("INSERT OR IGNORE INTO about_section (content, highlight_text, stats) VALUES (?, ?, ?)");
    $stmt->execute([
        '360 Media is Pakistan\'s leading manufacturer of smart, ultra-slim 360 video booths built for elegance, performance, and innovation. Our products serve wedding planners, event management companies, media houses, and creative professionals who demand sophistication and reliability.',
        'Proudly Made in Pakistan – Designed for the World',
        json_encode([
            ['number' => '500+', 'label' => 'Events Covered'],
            ['number' => '50+', 'label' => 'Happy Clients'],
            ['number' => '3', 'label' => 'Years Experience']
        ])
    ]);

    // Insert default products
    $products = [
        [
            'Luxury 360 Pro',
            'Our flagship model with premium features',
            '',
            json_encode(['Ultra Slim, Lightweight Body', 'Luxury Matte Finish', 'RGB LED Edge Lighting', 'Smart Wireless Controller'])
        ],
        [
            'Elite 360 Max',
            'Advanced features for professional use',
            '',
            json_encode(['Adjustable Arm & Silent Rotation', 'Custom Branding Option', '4K Video Recording', 'Premium Audio System'])
        ],
        [
            'Signature 360',
            'Compact and powerful for any event',
            '',
            json_encode(['Wireless Charging Station', 'Touchscreen Interface', 'AI-Powered Effects', 'Cloud Sync Capability'])
        ]
    ];

    $stmt = $db->prepare("INSERT OR IGNORE INTO products (name, description, features) VALUES (?, ?, ?)");
    foreach ($products as $product) {
        $stmt->execute([$product[0], $product[1], $product[3]]);
    }

    // Insert default experience content
    $stmt = $db->prepare("INSERT OR IGNORE INTO experience_section (content, features) VALUES (?, ?)");
    $stmt->execute([
        'Our 360 video booths transform ordinary events into extraordinary experiences. From intimate weddings to grand corporate functions, we capture every moment in stunning 360-degree detail.',
        json_encode([
            ['icon' => 'fas fa-heart', 'title' => 'Weddings', 'description' => 'Capture love stories from every angle'],
            ['icon' => 'fas fa-glass-cheers', 'title' => 'Parties', 'description' => 'Create unforgettable party memories'],
            ['icon' => 'fas fa-building', 'title' => 'Corporate', 'description' => 'Elevate brand experiences']
        ])
    ]);

    // Insert default testimonials
    $testimonials = [
        ['Sarah & Ahmed', 'Wedding Planners', '', '"360 Media\'s booths transformed our wedding into a cinematic experience. The quality and elegance exceeded our expectations."'],
        ['Elite Events Co.', 'Event Management', '', '"As event professionals, we demand perfection. 360 Media delivers luxury and reliability that our clients love."'],
        ['Creative Studios', 'Media Production', '', '"The craftsmanship and innovation in these booths are world-class. Proud to be associated with Pakistani excellence."']
    ];

    $stmt = $db->prepare("INSERT OR IGNORE INTO testimonials (name, position, company, content) VALUES (?, ?, ?, ?)");
    foreach ($testimonials as $testimonial) {
        $stmt->execute($testimonial);
    }

    // Insert default partners content
    $stmt = $db->prepare("INSERT OR IGNORE INTO partners_section (content, benefits) VALUES (?, ?)");
    $stmt->execute([
        'Join our exclusive network of partners and bring luxury 360 video experiences to your region. We offer comprehensive support, training, and marketing materials to ensure your success.',
        json_encode([
            ['icon' => 'fas fa-handshake', 'title' => 'Exclusive Territory', 'description' => 'Protected market areas for your business'],
            ['icon' => 'fas fa-graduation-cap', 'title' => 'Training & Support', 'description' => 'Complete setup and operational training'],
            ['icon' => 'fas fa-chart-line', 'title' => 'Marketing Materials', 'description' => 'Branded collateral and digital assets']
        ])
    ]);

    // Insert default contact info
    $contact_info = [
        ['email', 'info@360media.pk', 'Email', 'fas fa-envelope'],
        ['phone', '+92 300 1234567', 'Phone', 'fas fa-phone'],
        ['address', 'Lahore, Pakistan', 'Address', 'fas fa-map-marker-alt']
    ];

    $stmt = $db->prepare("INSERT OR IGNORE INTO contact_info (type, value, label, icon) VALUES (?, ?, ?, ?)");
    foreach ($contact_info as $info) {
        $stmt->execute($info);
    }

    // Insert default social links
    $social_links = [
        ['Facebook', '#', 'fab fa-facebook-f'],
        ['Instagram', '#', 'fab fa-instagram'],
        ['TikTok', '#', 'fab fa-tiktok'],
        ['YouTube', '#', 'fab fa-youtube']
    ];

    $stmt = $db->prepare("INSERT OR IGNORE INTO social_links (platform, url, icon_class) VALUES (?, ?, ?)");
    foreach ($social_links as $link) {
        $stmt->execute($link);
    }

    echo "Database setup completed successfully!";

} catch (PDOException $e) {
    echo "Database setup failed: " . $e->getMessage();
}
?>
