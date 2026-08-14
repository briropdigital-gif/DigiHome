<?php
require_once dirname(__DIR__) . '/includes/db.php';

$user = current_user();
if (!$user || !user_has_any_role(['marketer'])) {
    add_flash('danger', 'Please login as a marketer to access this dashboard.');
    header('Location: /DigiHome/marketer/login.php');
    exit;
}

$stats = get_dashboard_stats((int) $user['id'], 'marketer');
$owners = marketer_registered_owners((int) $user['id']);
$properties = get_marketer_properties((int) $user['id']);
$notifications = get_notifications((int) $user['id']);
$commissionBalance = marketer_commission_balance((int) $user['id']);
$pendingWithdrawal = has_pending_withdrawal_request((int) $user['id']);
$pageTitle = 'DigiHome | Marketer Dashboard';
$pageDescription = 'Manage registered property owners, submit listings on their behalf, and track commission-related unlock activity.';
include dirname(__DIR__) . '/includes/marketer_header.php';
?>

<section class="hero">
    <div class="hero-inline">
        <div>
            <span class="badge badge-success">Marketer Dashboard</span>
            <h2>Manage owners, submit listings, and grow commission activity.</h2>
            <p>Marketers can register property owners, maintain the owners they manage, and add listings on their behalf without approval powers.</p>
        </div>
        <div class="stats-row">
            <div class="mini-stat"><strong><?= (int) ($stats['owners'] ?? 0) ?></strong><span>Managed owners</span></div>
            <div class="mini-stat"><strong><?= (int) ($stats['listings'] ?? 0) ?></strong><span>Submitted listings</span></div>
            <div class="mini-stat"><strong>KES <?= number_format($commissionBalance, 2) ?></strong><span>Commission wallet</span></div>
        </div>
    </div>
</section>

<section class="panel">
    <div class="hero-inline">
        <h3>Commission and withdrawals</h3>
        <a class="button-link" href="/DigiHome/marketer/commissions.php">Open commission wallet</a>
    </div>
    <p><?= $pendingWithdrawal ? 'You have a pending withdrawal request under review.' : 'No pending withdrawal request. You can submit one from the commission wallet page.' ?></p>
</section>

<section class="panel">
    <div class="hero-inline">
        <h3>Managed property owners</h3>
        <a class="button-link" href="/DigiHome/marketer/register-owner.php">Register property owner</a>
        <a class="button-link" href="/DigiHome/marketer/listing.php">Add owner listing</a>
    </div>
    <table class="data-table">
        <thead>
            <tr>
                <th>Name</th>
                <th>Role</th>
                <th>Contact</th>
                <th>Location</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($owners === []): ?>
                <tr><td colspan="4">No property owners have been registered by this marketer yet.</td></tr>
            <?php else: ?>
                <?php foreach ($owners as $owner): ?>
                    <tr>
                        <td><?= htmlspecialchars($owner['name']) ?></td>
                        <td><?= htmlspecialchars($owner['role_label']) ?></td>
                        <td><?= htmlspecialchars($owner['email']) ?><br><?= htmlspecialchars($owner['phone']) ?></td>
                        <td><?= htmlspecialchars(trim($owner['county'] . ', ' . $owner['town'], ', ')) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</section>

<section class="panel">
    <div class="section-head">
        <div>
            <h3>Listings submitted by this marketer</h3>
            <p>Listings can be created and edited, but not verified or deleted by marketers.</p>
        </div>
    </div>
    <div class="grid">
        <?php foreach ($properties as $property): ?>
            <article class="property-card">
                <h3><?= htmlspecialchars($property['title']) ?></h3>
                <p><?= htmlspecialchars($property['owner_name']) ?></p>
                <p><strong>Status:</strong> <?= htmlspecialchars(property_status_label($property)) ?></p>
                <p><strong>Price:</strong> KES <?= number_format((float) $property['price']) ?></p>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<?php include dirname(__DIR__) . '/includes/footer.php'; ?>
