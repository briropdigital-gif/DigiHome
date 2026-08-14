        </div>
    </main>

<?php
$footerUser = current_user();
$footerRole = $footerUser['role'] ?? 'property_seeker';
$footerContent = get_site_content_map();
$year = date('Y');
$footerChatState = $footerUser ? get_chat_status_summary((int) $footerUser['id'], $footerRole) : null;
$footerChatAuthToken = $footerUser ? issue_chat_auth_token($footerUser) : '';
?>
    <footer class="site-footer">
        <div class="container footer-grid">
            <div>
                <img src="/DigiHome/assets/img/system/logo.png" alt="DigiHome logo" style="width:38px;height:38px;object-fit:contain;margin-bottom:10px;">
                <h3><?= htmlspecialchars($footerContent['system_name'] ?? 'DigiHome') ?></h3>
                <p><?= htmlspecialchars($footerContent['footer_about_text'] ?? 'Professional property discovery, listing operations, marketer workflows, and administrative control in one system.') ?></p>
            </div>
            <div>
                <h4>Platform</h4>
                <a href="<?= htmlspecialchars(role_home_path($footerRole)) ?>">Home</a>
                <a href="<?= htmlspecialchars(role_listings_path($footerRole)) ?>">Listings</a>
                <a href="<?= htmlspecialchars(role_about_path($footerRole)) ?>">About</a>
                <a href="<?= htmlspecialchars(role_contact_path($footerRole)) ?>">Contact</a>
                <a href="<?= htmlspecialchars(account_hub_path('login')) ?>">Login</a>
                <a href="<?= htmlspecialchars(account_hub_path('register')) ?>">Register</a>
            </div>
            <div>
                <h4>Roles</h4>
                <a href="<?= htmlspecialchars(role_login_path('property_seeker')) ?>">Property Seeker</a>
                <a href="<?= htmlspecialchars(role_login_path('property_owner')) ?>">Property Owner</a>
                <a href="<?= htmlspecialchars(role_login_path('marketer')) ?>">Marketer</a>
                <a href="<?= htmlspecialchars(role_login_path('admin')) ?>">Admin</a>
            </div>
            <div>
                <h4>Contact</h4>
                <span><?= htmlspecialchars($footerContent['contact_address'] ?? 'Nairobi, Kenya') ?></span>
                <span><?= htmlspecialchars($footerContent['contact_phone'] ?? '+254 700 123 456') ?></span>
                <span><?= htmlspecialchars($footerContent['contact_email'] ?? 'support@digihome.local') ?></span>
                <span><?= htmlspecialchars($footerContent['contact_hours'] ?? '') ?></span>
            </div>
        </div>
        <div class="footer-legal">
            <div class="container footer-legal-inner">
                <span><?= htmlspecialchars($footerContent['footer_legal_left'] ?? 'Enterprise-ready property operations for modern teams.') ?></span>
                <span>&copy; <?= htmlspecialchars($year) ?> <?= htmlspecialchars($footerContent['system_name'] ?? 'DigiHome') ?>. <?= htmlspecialchars($footerContent['footer_legal_right'] ?? 'All rights reserved.') ?></span>
            </div>
        </div>
    </footer>

    <?php if ($footerUser && $footerChatState): ?>
        <div class="chat-float-shell" data-chat-float data-chat-path="<?= htmlspecialchars($footerChatState['chat_path']) ?>" data-chat-role="<?= htmlspecialchars($footerChatState['role']) ?>" data-chat-auth-token="<?= htmlspecialchars($footerChatAuthToken) ?>">
            <span class="chat-float-badge" data-chat-float-badge hidden>0</span>
            <a class="chat-float" href="<?= htmlspecialchars($footerChatState['chat_path']) ?>" aria-label="Open messages">
                <span class="chat-float-icon" aria-hidden="true"><i class="fa-solid fa-comments"></i></span>
                <span class="chat-float-copy">
                    <strong>Messages</strong>
                    <small data-chat-float-status><?= $footerChatState['admin_online'] && $footerChatState['role'] !== 'admin' ? 'Online' : 'Offline' ?></small>
                </span>
            </a>
            <button class="chat-float-close" type="button" data-chat-float-close aria-label="Close chat bubble">&times;</button>
        </div>
    <?php endif; ?>

    <script>
        window.DIGIHOME_LOCATION_DATA = <?= json_encode(get_location_hierarchy_data(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    </script>
    <script src="/DigiHome/assets/js/app.js"></script>
</body>
</html>
