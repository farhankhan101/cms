<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

$tracking_no = trim($_GET['no'] ?? '');
$shipment = null;
$history = [];

if ($tracking_no) {
    $db = getDB();
    $stmt = $db->prepare("SELECT s.*, fc.city_name as from_city, tc.city_name as to_city 
                          FROM shipments s 
                          LEFT JOIN cities fc ON s.from_city_id = fc.id
                          LEFT JOIN cities tc ON s.to_city_id = tc.id
                          WHERE s.tracking_no = ?");
    $stmt->execute([$tracking_no]);
    $shipment = $stmt->fetch();

    if ($shipment) {
        $stmt = $db->prepare("SELECT * FROM shipment_status WHERE shipment_id = ? ORDER BY updated_at DESC");
        $stmt->execute([$shipment['id']]);
        $history = $stmt->fetchAll();
    }
}

$all_steps = [
    'booked' => 'Booked',
    'in_transit' => 'In Transit',
    'out_for_delivery' => 'Out for Delivery',
    'delivered' => 'Delivered'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CMSPRO - Premium Logistics & Real-time Tracking</title>
    <!-- Fonts & Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo time(); ?>">
    <style>
        :root {
            --primary: #2563eb;
            --primary-glow: rgba(37, 99, 235, 0.4);
            --accent: #8b5cf6;
        }

        body { 
            background: #0b0f1a; 
            color: #fff;
            overflow-x: hidden;
        }

        /* --- REDESIGNED NAVBAR --- */
        .navbar-public {
            background: rgba(11, 15, 26, 0.8) !important;
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255,255,255,0.05);
            box-shadow: none;
        }
        .nav-logo, .nav-links a { color: #fff !important; }
        .nav-links a:hover { color: var(--primary) !important; }

        .navbar-public .btn-outline {
            background: transparent !important;
            color: #fff !important;
            border-color: rgba(255,255,255,0.2) !important;
        }
        .navbar-public .btn-outline:hover {
            background: rgba(255,255,255,0.1) !important;
            border-color: #fff !important;
            color: #fff !important;
        }

        /* --- HERO SECTION --- */
        .hero {
            position: relative;
            min-height: 90vh;
            display: flex;
            align-items: center;
            padding: 120px 5% 100px;
            background: radial-gradient(circle at 10% 20%, rgba(37, 99, 235, 0.1) 0%, transparent 40%),
                        radial-gradient(circle at 90% 80%, rgba(139, 92, 246, 0.1) 0%, transparent 40%);
        }

        .hero-container {
            max-width: 1300px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 1.2fr 1fr;
            gap: 60px;
            align-items: center;
        }

        .hero-content h1 {
            font-size: 4.5rem;
            font-weight: 800;
            line-height: 1.1;
            margin-bottom: 24px;
            background: linear-gradient(to right, #fff, #94a3b8);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .hero-content h1 span {
            background: linear-gradient(to right, #60a5fa, #8b5cf6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .hero-content p {
            font-size: 1.2rem;
            color: #94a3b8;
            margin-bottom: 40px;
            max-width: 600px;
            line-height: 1.7;
        }

        .hero-image {
            position: relative;
            border-radius: 30px;
            overflow: hidden;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            border: 1px solid rgba(255,255,255,0.1);
        }

        .hero-image img {
            width: 100%;
            display: block;
            transition: 0.5s ease;
        }

        .hero-image:hover img {
            transform: scale(1.05);
        }

        /* --- TRACKING BOX --- */
        .track-section {
            padding: 0 5%;
            margin-top: -120px;
            position: relative;
            z-index: 100;
        }

        .track-card-premium {
            max-width: 1000px;
            margin: 0 auto;
            background: rgba(30, 41, 59, 0.7);
            backdrop-filter: blur(20px);
            padding: 50px;
            border-radius: 32px;
            border: 1px solid rgba(255,255,255,0.1);
            box-shadow: 0 30px 60px -12px rgba(0, 0, 0, 0.6);
        }

        .track-input-group {
            display: flex;
            gap: 15px;
            background: rgba(15, 23, 42, 0.5);
            padding: 10px;
            border-radius: 20px;
            border: 1px solid rgba(255,255,255,0.05);
        }

        .track-input-premium {
            flex: 1;
            background: transparent;
            border: none;
            padding: 15px 25px;
            color: #fff;
            font-size: 1.1rem;
            font-family: inherit;
        }

        .track-input-premium:focus { outline: none; }

        .btn-track {
            background: var(--primary);
            color: #fff;
            padding: 15px 40px;
            border-radius: 16px;
            font-weight: 700;
            border: none;
            cursor: pointer;
            transition: 0.3s;
            box-shadow: 0 0 20px var(--primary-glow);
        }

        .btn-track:hover {
            transform: translateY(-2px);
            box-shadow: 0 0 30px var(--primary-glow);
            background: #3b82f6;
        }

        /* --- STATS GRID --- */
        .stats-premium {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 30px;
            max-width: 1200px;
            margin: 100px auto;
            padding: 0 5%;
        }

        .stat-item {
            text-align: center;
            padding: 30px;
            border-radius: 24px;
            background: rgba(255,255,255,0.02);
            border: 1px solid rgba(255,255,255,0.05);
            transition: 0.3s;
        }

        .stat-item:hover {
            background: rgba(255,255,255,0.05);
            transform: translateY(-5px);
        }

        .stat-item h2 {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 5px;
            background: linear-gradient(to right, #60a5fa, #8b5cf6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .stat-item p { color: #94a3b8; text-transform: uppercase; letter-spacing: 1px; font-size: 0.8rem; font-weight: 600; }

        /* --- SERVICES & STEPS --- */
        .section-title {
            text-align: center;
            margin-bottom: 80px;
        }
        .section-title h2 { font-size: 3rem; font-weight: 800; margin-bottom: 15px; }
        .section-title p { color: #94a3b8; font-size: 1.1rem; }

        .card-premium {
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.05);
            padding: 40px;
            border-radius: 24px;
            transition: 0.3s;
        }
        .card-premium:hover {
            background: rgba(255,255,255,0.06);
            border-color: rgba(37, 99, 235, 0.3);
            transform: translateY(-10px);
        }

        .icon-box {
            width: 70px;
            height: 70px;
            background: rgba(37, 99, 235, 0.1);
            color: var(--primary);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            margin-bottom: 25px;
        }

        /* --- FOOTER --- */
        .footer-premium {
            background: #060912;
            padding: 100px 5% 40px;
            border-top: 1px solid rgba(255,255,255,0.05);
        }

        .footer-premium a:hover {
            color: var(--primary) !important;
        }

        .social-link {
            transition: 0.3s;
            color: #475569 !important;
        }
        .social-link:hover {
            color: var(--primary) !important;
            transform: translateY(-3px);
        }

        /* --- TRACKING RESULT --- */
        .tracking-result-box {
            margin-top: 40px;
            padding-top: 40px;
            border-top: 1px solid rgba(255,255,255,0.1);
        }

        .step-indicator {
            display: flex;
            justify-content: space-between;
            margin: 40px 0;
            position: relative;
        }

        .step-circle {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #1e293b;
            border: 2px solid #334155;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            z-index: 2;
            transition: 0.3s;
        }

        .step-circle.active {
            background: var(--primary);
            border-color: var(--primary);
            box-shadow: 0 0 20px var(--primary-glow);
        }

        .step-line {
            position: absolute;
            top: 20px;
            left: 5%;
            right: 5%;
            height: 2px;
            background: #334155;
            z-index: 1;
        }

        .step-line-fill {
            height: 100%;
            background: var(--primary);
            transition: 1s ease;
        }

        @media (max-width: 992px) {
            .hero-container { grid-template-columns: 1fr; text-align: center; }
            .hero-content h1 { font-size: 3rem; }
            .hero-content p { margin-inline: auto; }
            .stats-premium { grid-template-columns: repeat(2, 1fr); }
            .hero-image { display: none; }
        }

        @media (max-width: 768px) {
            .track-input-group { flex-direction: column; }
            .btn-track { width: 100%; }
        }
    </style>
</head>
<body>
    <nav class="navbar-public">
        <a href="index.php" class="nav-logo">
            <i class="fas fa-paper-plane"></i> CMS<span>PRO</span>
        </a>
        
        <div class="nav-links d-none d-md-flex">
            <a href="index.php">Home</a>
            <a href="#how-it-works">Process</a>
            <a href="#services">Solutions</a>
        </div>

        <div class="nav-auth">
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="<?php echo ($_SESSION['role'] == 'admin') ? 'admin/dashboard.php' : (($_SESSION['role'] == 'agent') ? 'agent/dashboard.php' : 'user/dashboard.php'); ?>" class="nav-avatar-group" style="background: rgba(255,255,255,0.05); border-color: rgba(255,255,255,0.1);">
                    <div style="text-align: right; line-height: 1;">
                        <div style="font-size: 13px; font-weight: 700; color: #fff;"><?php echo explode(' ', $_SESSION['name'])[0]; ?></div>
                        <div style="font-size: 10px; font-weight: 600; color: #94a3b8; text-transform: uppercase;"><?php echo $_SESSION['role']; ?></div>
                    </div>
                    <div class="nav-avatar" style="background: var(--primary); color: #fff;">
                        <i class="fas fa-user"></i>
                    </div>
                </a>
            <?php else: ?>
                <div class="nav-links" style="gap: 12px;">
                    <a href="login.php" class="btn btn-outline btn-sm">Sign In</a>
                    <a href="user/register.php" class="btn btn-primary btn-sm">Join Now</a>
                </div>
            <?php endif; ?>
        </div>
    </nav>

    <!-- HERO SECTION -->
    <section class="hero">
        <div class="hero-container">
            <div class="hero-content animate-up">
                <h1>The Future of <span>Logistics</span> is Here.</h1>
                <p>Ultra-fast delivery, real-time intelligence, and a seamless tracking experience. We don't just move boxes; we move businesses forward.</p>
                <div style="display: flex; gap: 20px;">
                    <a href="#track" class="btn-track" style="text-decoration: none; display: inline-flex; align-items: center;">Start Tracking <i class="fas fa-arrow-right" style="margin-left: 10px;"></i></a>
                    <a href="user/register.php" class="btn" style="color: #fff; border: 1px solid rgba(255,255,255,0.1);">Get Started</a>
                </div>
            </div>
            <div class="hero-image animate-up delay-200">
                <img src="assets/images/hero_logistics.png" alt="Logistics Future">
            </div>
        </div>
    </section>

    <!-- TRACKING SECTION -->
    <section id="track" class="track-section">
        <div class="track-card-premium animate-up delay-300">
            <h3 style="font-size: 1.5rem; font-weight: 700; margin-bottom: 10px;">Track Your Shipment</h3>
            <p style="color: #94a3b8; margin-bottom: 30px;">Enter your tracking number for real-time status updates.</p>
            
            <form action="index.php" method="GET" class="track-input-group">
                <input type="text" name="no" class="track-input-premium" placeholder="e.g. CMS-1234-ABCD" required value="<?php echo sanitize($tracking_no); ?>">
                <button type="submit" class="btn-track">
                    <i class="fas fa-search"></i> Track
                </button>
            </form>

            <?php if ($tracking_no): ?>
                <div id="tracking-result" class="tracking-result-box">
                    <?php if ($shipment): ?>
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px;">
                            <div>
                                <h4 style="font-size: 1.5rem; font-weight: 700;">#<?php echo sanitize($shipment['tracking_no']); ?></h4>
                                <p style="color: #94a3b8;"><?php echo sanitize($shipment['from_city']); ?> <i class="fas fa-long-arrow-alt-right" style="margin: 0 10px;"></i> <?php echo sanitize($shipment['to_city']); ?></p>
                            </div>
                            <div class="badge" style="background: var(--primary); color: #fff; padding: 10px 20px; font-size: 0.9rem;">
                                <?php echo str_replace('_', ' ', $shipment['status']); ?>
                            </div>
                        </div>

                        <!-- Step Indicator -->
                        <div class="step-indicator">
                            <?php 
                            $current_status = $shipment['status'];
                            $history_status = array_column($history, 'status');
                            $keys = array_keys($all_steps);
                            $current_idx = array_search($current_status, $keys);
                            
                            foreach ($all_steps as $status_key => $label): 
                                $is_done = in_array($status_key, $history_status);
                                $status_idx = array_search($status_key, $keys);
                                $is_active = ($status_idx <= $current_idx);
                            ?>
                                <div style="text-align: center; position: relative; z-index: 5;">
                                    <div class="step-circle <?php echo $is_active ? 'active' : ''; ?>">
                                        <?php if ($is_done || $is_active): ?>
                                            <i class="fas fa-check" style="font-size: 14px;"></i>
                                        <?php endif; ?>
                                    </div>
                                    <p style="font-size: 0.8rem; font-weight: 600; margin-top: 15px; color: <?php echo $is_active ? '#fff' : '#475569'; ?>;"><?php echo $label; ?></p>
                                </div>
                            <?php endforeach; ?>
                            
                            <div class="step-line">
                                <div class="step-line-fill" style="width: <?php 
                                    echo ($current_idx !== false) ? ($current_idx / (count($keys)-1)) * 100 : 0;
                                ?>%;"></div>
                            </div>
                        </div>

                        <!-- Activity -->
                        <div style="background: rgba(255,255,255,0.02); border-radius: 20px; padding: 30px; border: 1px solid rgba(255,255,255,0.05);">
                            <h5 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 25px;">Shipment History</h5>
                            <div style="border-left: 2px solid #1e293b; margin-left: 15px; padding-left: 30px;">
                                <?php foreach ($history as $h): ?>
                                    <div style="margin-bottom: 30px; position: relative;">
                                        <div style="position: absolute; left: -41px; top: 0; width: 20px; height: 20px; background: #0b0f1a; border: 4px solid var(--primary); border-radius: 50%;"></div>
                                        <div style="display: flex; justify-content: space-between;">
                                            <div>
                                                <p style="font-weight: 700; font-size: 1rem; margin-bottom: 5px;"><?php echo str_replace('_', ' ', $h['status']); ?></p>
                                                <p style="color: #94a3b8; font-size: 0.9rem;"><?php echo sanitize($h['note']); ?></p>
                                            </div>
                                            <div style="text-align: right;">
                                                <p style="font-size: 0.8rem; color: #475569; font-weight: 600;"><?php echo date('M d, Y', strtotime($h['updated_at'])); ?></p>
                                                <p style="font-size: 0.75rem; color: #94a3b8;"><?php echo date('H:i', strtotime($h['updated_at'])); ?></p>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <div style="text-align: center; padding: 40px; background: rgba(239, 68, 68, 0.05); border-radius: 20px; border: 1px solid rgba(239, 68, 68, 0.1);">
                            <i class="fas fa-ghost" style="font-size: 3rem; color: #ef4444; margin-bottom: 20px;"></i>
                            <h4 style="font-size: 1.25rem; font-weight: 700; color: #ef4444;">No Shipment Found</h4>
                            <p style="color: #94a3b8;">The tracking number <b><?php echo sanitize($tracking_no); ?></b> was not found in our system.</p>
                        </div>
                    <?php endif; ?>
                </div>
                <script>
                    window.onload = function() {
                        document.getElementById('tracking-result').scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                </script>
            <?php endif; ?>
        </div>
    </section>

    <!-- STATS -->
    <div class="stats-premium animate-up">
        <div class="stat-item">
            <h2>2.8M+</h2>
            <p>Packages Delivered</p>
        </div>
        <div class="stat-item">
            <h2>850+</h2>
            <p>Cities Worldwide</p>
        </div>
        <div class="stat-item">
            <h2>5.0k+</h2>
            <p>Verified Agents</p>
        </div>
        <div class="stat-item">
            <h2>99.9%</h2>
            <p>On-time Delivery</p>
        </div>
    </div>

    <!-- HOW IT WORKS -->
    <section id="how-it-works" style="padding: 100px 5%; background: rgba(255,255,255,0.01);">
        <div class="section-title animate-up">
            <h2>Seamless Process</h2>
            <p>Moving your parcels from point A to B with zero friction.</p>
        </div>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 40px; max-width: 1200px; margin: 0 auto; position: relative;">
            <div class="card-premium animate-up" style="text-align: center;">
                <div class="icon-box" style="margin: 0 auto 25px;"><i class="fas fa-box-open"></i></div>
                <h3 style="font-size: 1.25rem; font-weight: 700; margin-bottom: 15px;">1. Book Parcel</h3>
                <p style="color: #94a3b8; font-size: 0.9rem;">Register online or visit a branch to start your shipment instantly.</p>
            </div>
            <div class="card-premium animate-up delay-100" style="text-align: center;">
                <div class="icon-box" style="margin: 0 auto 25px;"><i class="fas fa-truck-loading"></i></div>
                <h3 style="font-size: 1.25rem; font-weight: 700; margin-bottom: 15px;">2. Collection</h3>
                <p style="color: #94a3b8; font-size: 0.9rem;">Our agents pick up your package and dispatch it via our express network.</p>
            </div>
            <div class="card-premium animate-up delay-200" style="text-align: center;">
                <div class="icon-box" style="margin: 0 auto 25px;"><i class="fas fa-map-location-dot"></i></div>
                <h3 style="font-size: 1.25rem; font-weight: 700; margin-bottom: 15px;">3. Live Tracking</h3>
                <p style="color: #94a3b8; font-size: 0.9rem;">Monitor every milestone of the journey in real-time on your dashboard.</p>
            </div>
            <div class="card-premium animate-up delay-300" style="text-align: center;">
                <div class="icon-box" style="margin: 0 auto 25px;"><i class="fas fa-house-circle-check"></i></div>
                <h3 style="font-size: 1.25rem; font-weight: 700; margin-bottom: 15px;">4. Safe Delivery</h3>
                <p style="color: #94a3b8; font-size: 0.9rem;">We deliver safely at the receiver's doorstep with instant confirmation.</p>
            </div>
        </div>
    </section>

    <!-- SERVICES -->
    <section id="services" style="padding: 100px 5%;">
        <div class="section-title animate-up">
            <h2>Enterprise Solutions</h2>
            <p>Advanced logistics tailored for your business needs.</p>
        </div>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 30px; max-width: 1200px; margin: 0 auto;">
            <div class="card-premium animate-up">
                <div class="icon-box"><i class="fas fa-bolt"></i></div>
                <h3 style="font-size: 1.25rem; font-weight: 700; margin-bottom: 15px;">Hyper-Express</h3>
                <p style="color: #94a3b8; line-height: 1.6;">Same-day delivery within city limits. Speed that defies expectation.</p>
            </div>
            <div class="card-premium animate-up delay-100">
                <div class="icon-box"><i class="fas fa-shield-alt"></i></div>
                <h3 style="font-size: 1.25rem; font-weight: 700; margin-bottom: 15px;">Secure Vault</h3>
                <p style="color: #94a3b8; line-height: 1.6;">High-value item transport with end-to-end encryption and insurance.</p>
            </div>
            <div class="card-premium animate-up delay-200">
                <div class="icon-box"><i class="fas fa-globe-americas"></i></div>
                <h3 style="font-size: 1.25rem; font-weight: 700; margin-bottom: 15px;">Global Connect</h3>
                <p style="color: #94a3b8; line-height: 1.6;">Seamless cross-border shipping with automated customs clearance.</p>
            </div>
        </div>
    </section>

    <!-- FOOTER -->
    <footer class="footer-premium">
        <div style="max-width: 1200px; margin: 0 auto; display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 60px;">
            <div style="grid-column: span 2;">
                <a href="#" class="nav-logo" style="font-size: 2rem; margin-bottom: 30px;">
                    <i class="fas fa-paper-plane"></i> CMS<span>PRO</span>
                </a>
                <p style="color: #94a3b8; max-width: 400px; line-height: 1.8;">Defining the next era of logistics through innovation, speed, and absolute transparency. Your world, delivered.</p>
                <div style="display: flex; gap: 20px; margin-top: 30px;">
                    <a href="https://twitter.com" target="_blank" class="social-link" style="font-size: 1.5rem;"><i class="fab fa-twitter"></i></a>
                    <a href="https://linkedin.com" target="_blank" class="social-link" style="font-size: 1.5rem;"><i class="fab fa-linkedin"></i></a>
                    <a href="https://instagram.com" target="_blank" class="social-link" style="font-size: 1.5rem;"><i class="fab fa-instagram"></i></a>
                </div>
            </div>
            <div>
                <h4 style="font-weight: 700; margin-bottom: 25px;">Company</h4>
                <ul style="list-style: none; padding: 0; color: #94a3b8;">
                    <li style="margin-bottom: 15px;"><a href="#" style="color: inherit; text-decoration: none;">About</a></li>
                    <li style="margin-bottom: 15px;"><a href="#" style="color: inherit; text-decoration: none;">Careers</a></li>
                    <li style="margin-bottom: 15px;"><a href="#" style="color: inherit; text-decoration: none;">Partners</a></li>
                </ul>
            </div>
            <div>
                <h4 style="font-weight: 700; margin-bottom: 25px;">Support</h4>
                <ul style="list-style: none; padding: 0; color: #94a3b8;">
                    <li style="margin-bottom: 15px;"><a href="#" style="color: inherit; text-decoration: none;">Help Center</a></li>
                    <li style="margin-bottom: 15px;"><a href="#" style="color: inherit; text-decoration: none;">API Docs</a></li>
                    <li style="margin-bottom: 15px;"><a href="#" style="color: inherit; text-decoration: none;">Contact</a></li>
                </ul>
            </div>
        </div>
        <div style="max-width: 1200px; margin: 60px auto 0; padding-top: 30px; border-top: 1px solid rgba(255,255,255,0.05); text-align: center; color: #475569; font-size: 0.9rem;">
            &copy; <?php echo date('Y'); ?> CMSPRO Logistics. Precision in Every Parcel.
        </div>
    </footer>
</body>
</html>
