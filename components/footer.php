<footer class="footer" id="contact">
    <div class="container footer-top">
        <div class="footer-brand">
            <div class="footer-logo-wrap">
                <img src="UKM.png" alt="UKM Logo" class="footer-logo">
                <span class="footer-title">UKMInvolve</span>
            </div>
            <p class="footer-desc">
                UKMInvolve is the official Student Activity and Event Management Portal of Universiti Kebangsaan Malaysia. Discover upcoming seminars, sports events, cultural programs, and student-led initiatives all in one place.
            </p>
            <div class="footer-socials">
                <a href="#" class="footer-social-btn" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                <a href="#" class="footer-social-btn" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                <a href="#" class="footer-social-btn" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                <a href="#" class="footer-social-btn" aria-label="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
            </div>
        </div>
        
        <div class="footer-column">
            <h3>Quick Links</h3>
            <ul class="footer-links">
                <li><a href="index.php">Utama (Home)</a></li>
                <li><a href="events.php">Browse Events</a></li>
                <li><a href="index.php#categories">Event Categories</a></li>
                <li><a href="login.php">Log Masuk (Login)</a></li>
                <li><a href="register.php">Daftar Akaun (Sign Up)</a></li>
            </ul>
        </div>
        
        <div class="footer-column">
            <h3>Event Categories</h3>
            <ul class="footer-links">
                <?php
                // Fetch up to 5 categories dynamically for the footer
                $footerCategories = [];
                if (function_exists('db') && db()->isConfigured() && function_exists('categories')) {
                    $footerCategories = array_slice(categories()->listAll(true), 0, 5);
                }
                if (!empty($footerCategories)):
                    foreach ($footerCategories as $cat):
                ?>
                    <li><a href="events.php?category=<?= htmlspecialchars($cat['slug']) ?>"><?= htmlspecialchars($cat['nama']) ?></a></li>
                <?php 
                    endforeach;
                else: 
                ?>
                    <li><a href="events.php">Browse All Events</a></li>
                <?php endif; ?>
            </ul>
        </div>
        
        <div class="footer-column">
            <h3>Contact Information</h3>
            <ul class="footer-info">
                <li class="footer-info-item">
                    <i class="fas fa-map-marker-alt"></i>
                    <span>Pusat Pembangunan Pelajar (HEP),<br>Universiti Kebangsaan Malaysia,<br>43600 UKM Bangi, Selangor, Malaysia</span>
                </li>
                <li class="footer-info-item">
                    <i class="fas fa-phone-alt"></i>
                    <span>+603-8921 5321</span>
                </li>
                <li class="footer-info-item">
                    <i class="fas fa-envelope"></i>
                    <span>hep@ukm.edu.my</span>
                </li>
            </ul>
        </div>
    </div>
    
    <div class="container footer-bottom">
        <p class="footer-copyright">
            &copy; 2026 UKMInvolve. Universiti Kebangsaan Malaysia. All rights reserved.
        </p>
        <div class="footer-legal-links">
            <a href="#">Privacy Policy</a>
            <a href="#">Terms of Service</a>
        </div>
    </div>
</footer>

<!-- UKMINVOLVE SMART ASSISTANT -->
<?php include_once __DIR__ . '/../chatbot.php'; ?>

