<?php
require_once dirname(__DIR__) . '/includes/db.config.php';

$user = current_user();
$isMarketerLoggedIn = $user && user_has_any_role(['marketer']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$isMarketerLoggedIn) {
        add_flash('warning', 'Please login as a marketer to send a message.');
        header('Location: /DigiHome/marketer/chat.php');
        exit;
    }

    $conversationId = (int) ($_POST['conversation_id'] ?? 0);
    $messageBody = trim((string) ($_POST['message_body'] ?? ''));
    if ($messageBody !== '') {
        if ($conversationId <= 0) {
            $conversation = create_or_get_open_conversation((int) $user['id'], $user['role'], 'Marketer Support');
            $conversationId = (int) ($conversation['id'] ?? 0);
        }
        if ($conversationId > 0) {
            ensure_conversation_greeting_message($conversationId);
        }
        if ($conversationId > 0 && add_conversation_message($conversationId, (int) $user['id'], $user['role'], $messageBody)) {
            add_flash('success', 'Message sent.');
        } else {
            add_flash('danger', 'Message could not be sent.');
        }
    }
    header('Location: /DigiHome/marketer/chat.php');
    exit;
}

$conversations = $isMarketerLoggedIn ? get_user_conversations((int) $user['id'], $user['role']) : [];
$selectedId = (int) ($_GET['id'] ?? 0);
$activeConversation = null;
if ($selectedId > 0) {
    foreach ($conversations as $conversation) {
        if ((int) $conversation['id'] === $selectedId) {
            $activeConversation = $conversation;
            break;
        }
    }
}
if ($activeConversation) {
    mark_messages_delivered_for_role((int) $activeConversation['id'], (string) $user['role'], (int) $user['id']);
    mark_conversation_read((int) $activeConversation['id'], (int) $user['id'], (string) $user['role']);
}
$messages = $activeConversation ? get_conversation_messages((int) $activeConversation['id'], $user['role']) : [];
$chatAuthToken = $user ? issue_chat_auth_token($user) : '';
$chatAccountHref = '/DigiHome/includes/account.php?mode=login&return=' . urlencode('/DigiHome/marketer/chat.php');
$pageTitle = 'DigiHome | Marketer Support Chat';
$pageDescription = 'Chat with DigiHome admin support.';
include dirname(__DIR__) . '/includes/marketer_header.php';
?>

<section class="section-shell" data-reveal>
    <div class="section-head">
        <div>
            <h2>Support Chat</h2>
            <p class="chat-page-status <?= is_admin_online() ? 'is-online' : 'is-offline' ?>"><span class="status-dot" aria-hidden="true"></span>We are currently <strong><?= is_admin_online() ? 'online' : 'offline' ?></strong>.</p>
        </div>
    </div>
    <?php if (!$isMarketerLoggedIn): ?>
        <div class="chat-login-notice" role="note" aria-live="polite">
            <div class="chat-login-notice-icon" aria-hidden="true"><i class="fa-solid fa-circle-exclamation"></i></div>
            <div class="chat-login-notice-content">
                <h3>Please login to chat with admin.</h3>
                <p>Access your account first, then continue the conversation from this chat page.</p>
                <a class="primary-button chat-login-notice-button" href="<?= htmlspecialchars($chatAccountHref) ?>">Go to Accounts</a>
            </div>
        </div>
    <?php endif; ?>
    <div class="chat-shell" data-chat-app data-chat-role="<?= htmlspecialchars((string) ($user['role'] ?? 'marketer')) ?>" data-chat-user-id="<?= (int) ($user['id'] ?? 0) ?>" data-chat-state-url="/DigiHome/includes/chat-api.php" data-chat-auth-token="<?= htmlspecialchars($chatAuthToken) ?>">
        <aside class="chat-list" data-chat-list>
            <h3>Your conversations</h3>
            <?php if ($conversations === []): ?>
                <p>No conversations yet. Send a message to start one.</p>
            <?php else: ?>
                <?php foreach ($conversations as $conversation): ?>
                    <?php $isDelayed = !empty($conversation['is_delayed']); ?>
                    <?php $isClosed = (($conversation['status'] ?? 'open') === 'closed'); ?>
                    <?php $route = conversation_route_labels($conversation); ?>
                    <?php $statusText = $isClosed ? 'Closed' : ($isDelayed ? 'Open • Delayed' : 'Open'); ?>
                    <a class="chat-item-link" href="/DigiHome/marketer/chat.php?id=<?= (int) $conversation['id'] ?>" data-chat-conversation-link data-conversation-id="<?= (int) $conversation['id'] ?>">
                        <div class="chat-item <?= (int) ($activeConversation['id'] ?? 0) === (int) $conversation['id'] ? 'is-active' : '' ?><?= $isDelayed ? ' is-delayed' : '' ?><?= $isClosed ? ' is-closed' : '' ?>">
                            <div class="chat-item-head">
                                <strong class="chat-item-id">#<?= (int) $conversation['id'] ?></strong>
                                <span class="chat-item-status-chip"><?= htmlspecialchars($statusText) ?></span>
                            </div>
                            <?php if ((int) ($conversation['unread_count'] ?? 0) > 0): ?>
                                <span class="chat-item-unread-badge"><?= (int) ($conversation['unread_count'] ?? 0) > 99 ? '99+' : (int) ($conversation['unread_count'] ?? 0) ?></span>
                            <?php endif; ?>
                            <div class="chat-item-route">
                                <small class="chat-item-route-line">From: <?= htmlspecialchars((string) ($route['from'] ?? 'User')) ?></small>
                                <small class="chat-item-route-line">To: <?= htmlspecialchars((string) ($route['to'] ?? 'Admin Team')) ?></small>
                            </div>
                            <div class="chat-item-foot">
                                <small><?= htmlspecialchars((string) ($conversation['updated_at'] ?? $conversation['created_at'] ?? '')) ?></small>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </aside>
        <div class="chat-main">
            <?php if ($activeConversation): ?>
                <div class="chat-panel-topbar">
                    <button type="button" class="chat-panel-close" data-chat-close-panel aria-label="Close conversation">&times;</button>
                </div>
                <?php if ($activeConversation): ?>
                    <div class="chat-peer-banner" data-chat-peer-banner>
                        <img class="chat-peer-avatar" src="<?= htmlspecialchars(company_logo_path()) ?>" alt="DigiHome logo" loading="lazy">
                        <div class="chat-panel-peer-info">
                            <?php $assignedAdminId = (int) ($activeConversation['assigned_admin_id'] ?? 0); ?>
                            <?php $assignedAdminName = trim((string) ($activeConversation['assigned_first_name'] ?? '') . ' ' . (string) ($activeConversation['assigned_last_name'] ?? '')); ?>
                            <?php $assignedAdminOnline = $assignedAdminId > 0 ? is_user_online($assignedAdminId) : false; ?>
                            <?php $assignedAdminLastSeen = $assignedAdminId > 0 ? format_user_last_seen($assignedAdminId) : ''; ?>
                            <strong class="chat-peer-name"><?= htmlspecialchars($assignedAdminName !== '' ? $assignedAdminName : 'Admin Team') ?></strong>
                            <span class="chat-peer-status">
                                <?= $assignedAdminOnline ? 'Admin - online' : ($assignedAdminLastSeen !== '' ? 'Admin - last seen ' . htmlspecialchars($assignedAdminLastSeen) : 'Admin - offline') ?>
                            </span>
                        </div>
                    </div>
                <?php endif; ?>
                <div class="chat-thread" data-chat-thread>
                    <?php foreach ($messages as $message): ?>
                        <?php $mine = $isMarketerLoggedIn && (int) ($message['sender_user_id'] ?? 0) === (int) $user['id']; ?>
                        <?php $messageAttachments = is_array($message['attachments'] ?? null) ? $message['attachments'] : []; ?>
                        <div class="chat-bubble <?= $mine ? 'mine' : 'other' ?>" data-message-id="<?= (int) ($message['id'] ?? 0) ?>">
                            <?php if (!empty($message['media_path'])): ?>
                                <?php $mediaType = (string) ($message['media_type'] ?? ''); ?>
                                <?php $mediaName = (string) ($message['media_name'] ?? 'attachment'); ?>
                                <div class="chat-media">
                                    <?php if (str_starts_with($mediaType, 'image/')): ?>
                                        <img src="<?= htmlspecialchars((string) $message['media_path']) ?>" alt="<?= htmlspecialchars((string) ($message['media_name'] ?? 'Shared image')) ?>" loading="lazy">
                                    <?php elseif (str_starts_with($mediaType, 'video/')): ?>
                                        <div class="chat-media-file-card chat-media-video-card">
                                            <i class="fa-solid fa-file-video" aria-hidden="true"></i>
                                            <span class="chat-media-video-info">
                                                <strong>Video</strong>
                                                <small><?= htmlspecialchars($mediaName) ?></small>
                                            </span>
                                        </div>
                                    <?php else: ?>
                                        <div class="chat-media-file-card">
                                            <i class="fa-regular fa-file" aria-hidden="true"></i>
                                            <span><?= htmlspecialchars($mediaName) ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <a class="chat-media-download" href="<?= htmlspecialchars((string) $message['media_path']) ?>" target="_blank" rel="noopener noreferrer" download="<?= htmlspecialchars($mediaName) ?>">Download</a>
                                </div>
                            <?php endif; ?>
                            <?php foreach ($messageAttachments as $attachment): ?>
                                <?php $attachmentPath = (string) ($attachment['media_path'] ?? ''); ?>
                                <?php if ($attachmentPath === '') continue; ?>
                                <?php $attachmentType = (string) ($attachment['media_type'] ?? ''); ?>
                                <?php $attachmentName = (string) ($attachment['media_name'] ?? 'attachment'); ?>
                                <div class="chat-media">
                                    <?php if (str_starts_with($attachmentType, 'image/')): ?>
                                        <img src="<?= htmlspecialchars($attachmentPath) ?>" alt="<?= htmlspecialchars($attachmentName) ?>" loading="lazy">
                                    <?php elseif (str_starts_with($attachmentType, 'video/')): ?>
                                        <div class="chat-media-file-card chat-media-video-card">
                                            <i class="fa-solid fa-file-video" aria-hidden="true"></i>
                                            <span class="chat-media-video-info">
                                                <strong>Video</strong>
                                                <small><?= htmlspecialchars($attachmentName) ?></small>
                                            </span>
                                        </div>
                                    <?php else: ?>
                                        <div class="chat-media-file-card">
                                            <i class="fa-regular fa-file" aria-hidden="true"></i>
                                            <span><?= htmlspecialchars($attachmentName) ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <a class="chat-media-download" href="<?= htmlspecialchars($attachmentPath) ?>" target="_blank" rel="noopener noreferrer" download="<?= htmlspecialchars($attachmentName) ?>">Download</a>
                                </div>
                            <?php endforeach; ?>
                            <?php if (trim((string) ($message['message_body'] ?? '')) !== ''): ?>
                                <p><?= nl2br(htmlspecialchars((string) ($message['message_body'] ?? ''))) ?></p>
                            <?php endif; ?>
                            <div class="chat-meta">
                                <span><?= htmlspecialchars((string) ($message['created_at'] ?? '')) ?><?= !empty($message['edited_at']) ? ' • edited' : '' ?></span>
                                <?php if ($mine): ?>
                                    <span class="chat-meta-actions">
                                        <button type="button" class="chat-action-text" data-chat-edit-message="<?= (int) ($message['id'] ?? 0) ?>">Edit</button>
                                        <span class="chat-tick <?= !empty($message['read_at']) ? 'is-read' : (!empty($message['delivered_at']) ? 'is-delivered' : 'is-sent') ?>" data-chat-tick><?= !empty($message['read_at']) ? '✔✔' : (!empty($message['delivered_at']) ? '✔✔' : '✔') ?></span>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="chat-typing" data-chat-typing></div>
            <?php else: ?>
                <div class="chat-panel-topbar is-empty">
                    <button type="button" class="chat-panel-close" data-chat-close-panel hidden aria-label="Close conversation">&times;</button>
                </div>
                <div class="chat-thread" data-chat-thread>
                    <div class="chat-empty-state">
                        <i class="fa-regular fa-comments" aria-hidden="true"></i>
                        <p>Select a conversation or send a new message to start one.</p>
                    </div>
                </div>
                <div class="chat-typing" data-chat-typing></div>
            <?php endif; ?>
            <?php if ($isMarketerLoggedIn): ?>
                <form method="post" class="chat-compose" data-chat-compose enctype="multipart/form-data" novalidate>
                    <input type="hidden" name="conversation_id" value="<?= (int) ($activeConversation['id'] ?? 0) ?>" data-chat-conversation-id>
                    <div class="chat-media-preview" data-chat-media-preview hidden></div>
                    <textarea name="message_body" placeholder="Type your message..." data-chat-message-input></textarea>
                    <div class="chat-compose-controls">
                        <label class="chat-attach-control" data-chat-attach-control aria-label="Attach media">
                            <input type="file" name="message_media[]" accept="image/*,video/*,application/pdf,.pdf" data-chat-media-input multiple hidden>
                            <i class="fa-solid fa-paperclip" aria-hidden="true"></i>
                        </label>
                        <button type="submit" class="chat-send-round" aria-label="Send message">
                            <i class="fa-solid fa-paper-plane" aria-hidden="true"></i>
                        </button>
                    </div>
                </form>
            <?php else: ?>
                <form class="chat-compose">
                    <textarea placeholder="Login to send a message..." disabled></textarea>
                    <a class="ghost-button" href="<?= htmlspecialchars($chatAccountHref) ?>">Go to Accounts</a>
                </form>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php include dirname(__DIR__) . '/includes/footer.php'; ?>
