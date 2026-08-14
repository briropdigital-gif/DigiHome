<?php
require_once dirname(__DIR__) . '/includes/db.config.php';

$user = current_user();
if (!$user || !user_has_any_role(['property_owner'])) {
    add_flash('danger', 'Please login as a property owner to access About.');
    header('Location: /DigiHome/owner/login.php');
    exit;
}

$content = get_site_content_map();
$aboutFeatureCards = get_about_feature_cards($content);
$reviews = get_reviews_for_display((int) $user['id'], 12);
$teamMembers = get_team_members_with_contacts();
$pageTitle = 'DigiHome | Owner About';
$pageDescription = 'Read platform information relevant to property owners.';
include dirname(__DIR__) . '/includes/owner_header.php';
?>

<section class="section-shell about-intro-shell" data-reveal>
    <div class="about-brand-panel">
        <div class="about-brand-copy">
            <p class="about-brand-kicker">Company identity</p>
            <h2><?= htmlspecialchars($content['about_title'] ?? 'About DigiHome') ?></h2>
            <p><?= htmlspecialchars($content['about_intro_note'] ?? 'This page is managed by platform administrators and published for all user roles.') ?></p>
        </div>
        <div class="about-brand-mark" aria-label="DigiHome company logo">
            <img src="/DigiHome/assets/img/logo.png" alt="DigiHome company logo">
        </div>
    </div>
    <p class="about-brand-body"><?= nl2br(htmlspecialchars($content['about_page_body'] ?? ($content['about_body'] ?? ''))) ?></p>
</section>

<section class="card-grid about-feature-grid" data-reveal>
    <?php foreach ($aboutFeatureCards as $card): ?>
        <article class="info-card">
            <h3><?= htmlspecialchars((string) ($card['title'] ?? '')) ?></h3>
            <p><?= htmlspecialchars((string) ($card['description'] ?? '')) ?></p>
        </article>
    <?php endforeach; ?>
</section>

<section class="section-shell" data-reveal>
    <div class="section-head"><div><h2>User Ratings and Reviews</h2><p>Trusted feedback from our users.</p></div></div>
    <div class="card-grid">
        <?php foreach ($reviews as $review): ?>
            <article class="info-card">
                <img class="avatar" src="<?= htmlspecialchars($review['profile_picture'] ?: '/DigiHome/assets/img/system/logo.png') ?>" alt="Reviewer profile">
                <h3><?= htmlspecialchars($review['first_name'] ?: 'User') ?></h3>
                <p class="stars"><?= str_repeat('★', (int) $review['rating']) . str_repeat('☆', 5 - (int) $review['rating']) ?></p>
                <p><?= htmlspecialchars($review['review_text']) ?></p>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<section class="section-shell" data-reveal>
    <div class="section-head"><div><h2>Our Team</h2><p>Meet the people behind DigiHome.</p></div></div>
    <div class="card-grid team-grid">
        <?php foreach ($teamMembers as $member): ?>
            <article class="info-card">
                <img class="avatar" src="<?= htmlspecialchars($member['profile_picture'] ?: '/DigiHome/assets/img/system/logo.png') ?>" alt="<?= htmlspecialchars($member['member_name']) ?>">
                <h3><?= htmlspecialchars($member['member_name']) ?></h3>
                <p><strong><?= htmlspecialchars($member['role_title']) ?></strong></p>
                <p><?= htmlspecialchars($member['short_description']) ?></p>
                <div class="inline-actions team-contact-actions">
                    <?php foreach (($member['contacts'] ?? []) as $contact): ?>
                        <a class="team-contact-link" href="<?= htmlspecialchars((string) ($contact['href'] ?? '#')) ?>" title="<?= htmlspecialchars((string) ($contact['tooltip'] ?? 'Open contact')) ?>" aria-label="<?= htmlspecialchars((string) ($contact['tooltip'] ?? 'Open contact')) ?>" <?= str_starts_with((string) ($contact['href'] ?? ''), 'http') ? 'target="_blank" rel="noopener noreferrer"' : '' ?>><?= $contact['icon_html'] ?><span class="sr-only"><?= htmlspecialchars((string) ($contact['label'] ?? 'Contact')) ?></span></a>
                    <?php endforeach; ?>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<?php include dirname(__DIR__) . '/includes/footer.php'; ?>
