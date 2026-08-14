<?php
require_once dirname(__DIR__) . '/includes/db.php';

$user = current_user();
if (!$user || !user_has_any_role(['property_owner'])) {
    add_flash('danger', 'Please login as a property owner to rate DigiHome.');
    header('Location: /DigiHome/owner/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rating = (int) ($_POST['rating'] ?? 0);
    $reviewText = trim((string) ($_POST['review_text'] ?? ''));
    if ($rating < 1 || $rating > 5 || $reviewText === '') {
        add_flash('danger', 'Please provide a star rating and review text.');
    } elseif (submit_review((int) $user['id'], $user['role'], $rating, $reviewText)) {
        add_flash('success', 'Thank you. Your review has been submitted for moderation.');
        header('Location: /DigiHome/owner/rate-us.php');
        exit;
    } else {
        add_flash('danger', 'Unable to submit your review right now.');
    }
}

$reviews = get_reviews_for_display((int) $user['id']);
$pageTitle = 'DigiHome | Rate Us';
$pageDescription = 'Share your rating and review with DigiHome.';
include dirname(__DIR__) . '/includes/owner_header.php';
?>

<section class="section-shell" data-reveal>
    <div class="section-head"><div><h2>Rate DigiHome</h2><p>Give us a rating out of 5 stars and leave a review.</p></div></div>
    <form method="post" class="form-grid">
        <label class="field-card"><span>Rating</span><select name="rating" required><option value="">Select stars</option><option value="5">5 Stars</option><option value="4">4 Stars</option><option value="3">3 Stars</option><option value="2">2 Stars</option><option value="1">1 Star</option></select></label>
        <label class="field-card full-span"><span>Review</span><textarea name="review_text" required placeholder="Tell us what worked well and what we should improve."></textarea></label>
        <div class="inline-actions full-span"><button type="submit">Submit review</button></div>
    </form>
</section>

<section class="card-grid" data-reveal>
    <?php foreach ($reviews as $review): ?>
        <article class="info-card">
            <div class="stars"><?= str_repeat('★', (int) $review['rating']) . str_repeat('☆', 5 - (int) $review['rating']) ?></div>
            <p><?= htmlspecialchars($review['review_text']) ?></p>
            <p><strong><?= htmlspecialchars($review['first_name'] ?: 'User') ?></strong></p>
        </article>
    <?php endforeach; ?>
</section>

<?php include dirname(__DIR__) . '/includes/footer.php'; ?>
