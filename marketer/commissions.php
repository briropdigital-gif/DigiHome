<?php
require_once dirname(__DIR__) . '/includes/db.config.php';

$user = current_user();
if (!$user || !user_has_any_role(['marketer'])) {
    add_flash('danger', 'Please login as a marketer to access commissions.');
    header('Location: /DigiHome/marketer/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? '');
    if ($action === 'request_withdrawal') {
        $amount = (float) ($_POST['amount'] ?? 0);
        $accountName = trim((string) ($_POST['account_name'] ?? ''));
        $accountNumber = trim((string) ($_POST['account_number'] ?? ''));
        $accountPassword = (string) ($_POST['account_password'] ?? '');

        [$ok, $message] = create_withdrawal_request((int) $user['id'], $amount, $accountName, $accountNumber, $accountPassword);
        add_flash($ok ? 'success' : 'danger', $message);
        header('Location: /DigiHome/marketer/commissions.php');
        exit;
    }
}

$balance = marketer_commission_balance((int) $user['id']);
$perProperty = marketer_commission_by_property((int) $user['id']);
$requests = marketer_withdrawal_requests((int) $user['id']);
$totalPaid = marketer_withdrawal_total_paid((int) $user['id']);
$minAmount = (float) get_site_content('withdrawal_min_amount', '500');
$maxAmount = (float) get_site_content('withdrawal_max_amount', '200000');
$hasPending = has_pending_withdrawal_request((int) $user['id']);

$pageTitle = 'DigiHome | Marketer Commissions';
$pageDescription = 'Review your commission earnings and submit withdrawal requests.';
include dirname(__DIR__) . '/includes/marketer_header.php';
?>

<section class="hero" data-reveal>
    <div class="hero-grid">
        <div>
            <span class="badge badge-success">Commission Wallet</span>
            <h1>Track your listing earnings.</h1>
            <p>Commissions are earned when seekers unlock hidden property details.</p>
        </div>
        <div class="hero-stat-grid">
            <div class="hero-stat"><strong>KES <?= number_format($balance, 2) ?></strong><span>Available balance</span></div>
            <div class="hero-stat"><strong>KES <?= number_format($totalPaid, 2) ?></strong><span>Total paid out</span></div>
            <div class="hero-stat"><strong><?= count($requests) ?></strong><span>Total requests</span></div>
        </div>
    </div>
</section>

<section class="section-shell" data-reveal>
    <div class="section-head">
        <div>
            <h2>Withdrawal Request</h2>
            <p>Allowed range: KES <?= number_format($minAmount) ?> to KES <?= number_format($maxAmount) ?>.</p>
        </div>
    </div>

    <?php if ($hasPending): ?>
        <div class="alert alert-warning">You already have a pending withdrawal request.</div>
    <?php endif; ?>

    <form method="post" class="form-grid">
        <input type="hidden" name="action" value="request_withdrawal">
        <label class="field-card"><span>Amount (KES)</span><input type="number" name="amount" min="1" step="0.01" required></label>
        <label class="field-card"><span>Account Name</span><input type="text" name="account_name" placeholder="e.g. M-Pesa" required></label>
        <label class="field-card"><span>Account Number</span><input type="text" name="account_number" placeholder="e.g. 0700123456" required></label>
        <label class="field-card"><span>Account Password</span><div class="password-field"><input id="marketer-withdrawal-account-password" type="password" name="account_password" required><button type="button" class="ghost-button password-toggle" data-password-toggle="marketer-withdrawal-account-password" aria-label="Show password" title="Show password"><i class="fa-solid fa-eye" aria-hidden="true"></i></button></div></label>
        <div class="inline-actions full-span">
            <button type="submit" <?= $hasPending ? 'disabled' : '' ?>>Submit request</button>
        </div>
    </form>
</section>

<section class="section-shell" data-reveal>
    <div class="section-head">
        <div>
            <h2>Earnings by Listing</h2>
            <p>Amount earned for each listing tied to your marketer account.</p>
        </div>
    </div>
    <table class="data-table">
        <thead>
            <tr><th>Listing</th><th>Unlocks</th><th>Unlock Value</th><th>Commission</th></tr>
        </thead>
        <tbody>
            <?php if ($perProperty === []): ?>
                <tr><td colspan="4">No commission records yet.</td></tr>
            <?php else: ?>
                <?php foreach ($perProperty as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars((string) $row['title']) ?></td>
                        <td><?= (int) $row['unlocks'] ?></td>
                        <td>KES <?= number_format((float) $row['unlock_value'], 2) ?></td>
                        <td>KES <?= number_format((float) $row['commission_total'], 2) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</section>

<section class="section-shell" data-reveal>
    <div class="section-head">
        <div>
            <h2>Withdrawal History</h2>
            <p>Statuses: pending (orange), approved (green), rejected (red).</p>
        </div>
    </div>
    <table class="data-table">
        <thead>
            <tr><th>Date</th><th>Amount</th><th>Account</th><th>Status</th><th>Paid At</th><th>Reason</th></tr>
        </thead>
        <tbody>
            <?php if ($requests === []): ?>
                <tr><td colspan="6">No withdrawal requests yet.</td></tr>
            <?php else: ?>
                <?php foreach ($requests as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars((string) $row['requested_at']) ?></td>
                        <td>KES <?= number_format((float) $row['amount'], 2) ?></td>
                        <td><?= htmlspecialchars((string) $row['account_name']) ?><br><?= htmlspecialchars((string) $row['account_number']) ?></td>
                        <td><span class="status-pill status-<?= htmlspecialchars((string) $row['status']) ?>"><?= htmlspecialchars(ucfirst((string) $row['status'])) ?></span></td>
                        <td><?= htmlspecialchars((string) ($row['payout_at'] ?? '')) ?></td>
                        <td><?= htmlspecialchars((string) ($row['reason'] ?? '')) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</section>

<?php include dirname(__DIR__) . '/includes/footer.php'; ?>
