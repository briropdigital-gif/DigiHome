<?php
require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');

function chat_api_response(array $payload, int $statusCode = 200): void {
    http_response_code($statusCode);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function chat_api_store_media_upload($fieldName): array {
    $files = $_FILES[$fieldName] ?? null;
    if (empty($files) || !is_array($files)) {
        $fallbackFieldName = $fieldName . '[]';
        $files = $_FILES[$fallbackFieldName] ?? null;
    }
    if (empty($files) || !is_array($files)) {
        return ['ok' => true, 'uploads' => []];
    }
    if (isset($files['name']) && !is_array($files['name'])) {
        $files = [
            'name' => [$files['name']],
            'type' => [$files['type'] ?? ''],
            'tmp_name' => [$files['tmp_name']],
            'error' => [$files['error'] ?? UPLOAD_ERR_NO_FILE],
            'size' => [$files['size'] ?? 0],
        ];
    }

    $count = is_array($files['name'] ?? null) ? count($files['name']) : 0;
    if ($count === 0 || (int) ($files['error'][0] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return ['ok' => true, 'uploads' => []];
    }

    $uploads = [];
    $uploadDirFs = dirname(__DIR__) . '/assets/chat';
    if (!is_dir($uploadDirFs) && !mkdir($uploadDirFs, 0775, true) && !is_dir($uploadDirFs)) {
        return ['ok' => false, 'message' => 'Could not create chat media directory.'];
    }

    for ($i = 0; $i < $count; $i++) {
        $error = (int) ($files['error'][$i] ?? UPLOAD_ERR_NO_FILE);
        if ($error === UPLOAD_ERR_NO_FILE) {
            continue;
        }
        if ($error !== UPLOAD_ERR_OK) {
            return ['ok' => false, 'message' => 'Media upload failed.'];
        }

        $tmpPath = (string) ($files['tmp_name'][$i] ?? '');
        if ($tmpPath === '' || !is_uploaded_file($tmpPath)) {
            return ['ok' => false, 'message' => 'Invalid upload payload.'];
        }

        $size = (int) ($files['size'][$i] ?? 0);
        if ($size <= 0 || $size > 100 * 1024 * 1024) {
            return ['ok' => false, 'message' => 'Media must be between 1 byte and 100MB.'];
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = $finfo ? (string) finfo_file($finfo, $tmpPath) : '';
        if ($finfo) {
            finfo_close($finfo);
        }

        $allowedPrefixes = ['image/', 'video/'];
        $allowedExact = ['application/pdf'];
        $allowed = in_array($mimeType, $allowedExact, true);
        foreach ($allowedPrefixes as $prefix) {
            if ($mimeType !== '' && str_starts_with($mimeType, $prefix)) {
                $allowed = true;
                break;
            }
        }
        if (!$allowed) {
            return ['ok' => false, 'message' => 'Only image, video, and PDF uploads are supported in chat.'];
        }

        $originalName = (string) ($files['name'][$i] ?? 'media');
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $safeExtension = preg_match('/^[a-z0-9]{1,10}$/', $extension) ? $extension : 'bin';
        $fileName = 'chat-' . date('Ymd-His') . '-' . bin2hex(random_bytes(5)) . '.' . $safeExtension;
        $targetFs = $uploadDirFs . '/' . $fileName;
        if (!move_uploaded_file($tmpPath, $targetFs)) {
            return ['ok' => false, 'message' => 'Could not store uploaded media file.'];
        }

        $uploads[] = [
            'path' => '/DigiHome/assets/chat/' . $fileName,
            'type' => $mimeType,
            'name' => $originalName,
        ];
    }

    return ['ok' => true, 'uploads' => $uploads];
}

function chat_api_accessible_conversations(array $user): array {
    $role = canonical_role($user['role'] ?? 'property_seeker');
    $userId = (int) ($user['id'] ?? 0);
    if ($role === 'admin') {
        return get_admin_conversations($userId);
    }
    return get_user_conversations($userId, $role);
}

function chat_api_find_conversation(array $conversations, int $conversationId): ?array {
    foreach ($conversations as $conversation) {
        if ((int) ($conversation['id'] ?? 0) === $conversationId) {
            return $conversation;
        }
    }
    return null;
}

function chat_api_enrich_conversation_with_presence(array $conversation, array $user): array {
    $role = canonical_role($user['role'] ?? 'property_seeker');
    if ($role === 'admin') {
        return $conversation;
    }

    $assignedAdminId = (int) ($conversation['assigned_admin_id'] ?? 0);
    $assignedAdminName = trim((string) ($conversation['assigned_first_name'] ?? '') . ' ' . (string) ($conversation['assigned_last_name'] ?? ''));

    $conversation['assigned_admin_name'] = $assignedAdminName;
    $conversation['assigned_admin_online'] = $assignedAdminId > 0 ? is_user_online($assignedAdminId) : false;
    $conversation['assigned_admin_last_seen'] = $assignedAdminId > 0 ? format_user_last_seen($assignedAdminId) : '';

    return $conversation;
}

function chat_api_build_state(array $user, array $conversation = null): array {
    $role = canonical_role($user['role'] ?? 'property_seeker');
    $userId = (int) ($user['id'] ?? 0);
    $summary = get_chat_status_summary($userId, $role);
    $typing = $conversation ? get_conversation_typing_state((int) $conversation['id'], $userId) : null;
    $conversation = $conversation ? chat_api_enrich_conversation_with_presence($conversation, $user) : null;

    return [
        'ok' => true,
        'chat' => [
            'unread_count' => $summary['unread_count'],
            'admin_online' => $summary['admin_online'],
            'chat_path' => $summary['chat_path'],
            'role' => $summary['role'],
            'typing' => $typing,
            'conversation' => $conversation,
            'messages' => $conversation ? get_conversation_messages((int) $conversation['id'], $role) : [],
            'conversations' => chat_api_accessible_conversations($user),
        ],
    ];
}

$chatAuthToken = (string) ($_REQUEST['chat_auth_token'] ?? '');
$user = $chatAuthToken !== '' ? resolve_chat_auth_user($chatAuthToken) : current_user();
if (!$user) {
    chat_api_response(['ok' => false, 'message' => 'Login required.'], 401);
}

$role = canonical_role($user['role'] ?? 'property_seeker');
$action = strtolower(trim((string) ($_REQUEST['action'] ?? 'state')));
$conversationId = (int) ($_REQUEST['conversation_id'] ?? 0);
$accessibleConversations = chat_api_accessible_conversations($user);
$conversation = $conversationId > 0 ? chat_api_find_conversation($accessibleConversations, $conversationId) : null;

if ($action === 'state' || $action === 'open') {
    if ($conversationId > 0 && !$conversation) {
        chat_api_response(['ok' => false, 'message' => 'Conversation not available.'], 403);
    }

    if ($conversation) {
        mark_messages_delivered_for_role((int) $conversation['id'], $role, (int) $user['id']);
        mark_conversation_read((int) $conversation['id'], (int) $user['id'], $role);
    } else {
        foreach ($accessibleConversations as $accessibleConversation) {
            $accessibleConversationId = (int) ($accessibleConversation['id'] ?? 0);
            if ($accessibleConversationId > 0) {
                mark_messages_delivered_for_role($accessibleConversationId, $role, (int) $user['id']);
            }
        }
    }

    chat_api_response(chat_api_build_state($user, $conversation));
}

if ($action === 'typing') {
    if ($conversationId <= 0 || !$conversation) {
        chat_api_response(['ok' => false, 'message' => 'Conversation not available.'], 403);
    }

    $isTyping = filter_var($_REQUEST['is_typing'] ?? '0', FILTER_VALIDATE_BOOLEAN);
    set_conversation_typing_state($conversationId, (int) $user['id'], $role, $isTyping);
    chat_api_response(['ok' => true]);
}

if ($action === 'send') {
    $messageBody = trim((string) ($_POST['message_body'] ?? $_REQUEST['message_body'] ?? ''));
    $forceNew = filter_var($_POST['force_new'] ?? $_REQUEST['force_new'] ?? '0', FILTER_VALIDATE_BOOLEAN);
    $mediaUpload = chat_api_store_media_upload('message_media');
    if (!$mediaUpload['ok']) {
        chat_api_response(['ok' => false, 'message' => $mediaUpload['message'] ?? 'Media upload failed.'], 422);
    }
    $mediaUploads = $mediaUpload['uploads'] ?? [];
    if ($messageBody === '' && count($mediaUploads) === 0) {
        chat_api_response(['ok' => false, 'message' => 'Message cannot be empty.'], 422);
    }

    $conversationData = $conversation;
    if (!$conversationData) {
        if ($role === 'admin') {
            $recipientId = (int) ($_POST['recipient_user_id'] ?? $_REQUEST['recipient_user_id'] ?? 0);
            $recipientRole = canonical_role((string) ($_POST['recipient_role'] ?? $_REQUEST['recipient_role'] ?? ''));
            $visibility = strtolower(trim((string) ($_POST['visibility'] ?? $_REQUEST['visibility'] ?? 'direct')));
            if ($recipientId <= 0 || $recipientRole === '') {
                chat_api_response(['ok' => false, 'message' => 'Pick a recipient before sending.'], 422);
            }
            $scope = $visibility === 'broadcast'
                ? 'admin_broadcast'
                : ($recipientRole === 'admin' ? 'admin_direct' : 'direct');
            $subject = $scope === 'admin_broadcast' ? 'Admin broadcast' : '';
            $conversationData = create_direct_conversation((int) $user['id'], $role, $recipientId, $recipientRole, $subject, $scope);
        } else {
            $conversationData = create_or_get_open_conversation((int) $user['id'], $role, '', $forceNew);
        }
    }

    if (!$conversationData) {
        chat_api_response(['ok' => false, 'message' => 'Unable to open conversation.'], 500);
    }

    $conversationId = (int) $conversationData['id'];
    $normalizedUploads = [];
    foreach ($mediaUploads as $upload) {
        if (!is_array($upload)) {
            continue;
        }
        $normalizedUploads[] = [
            'media_path' => (string) ($upload['path'] ?? ''),
            'media_type' => (string) ($upload['type'] ?? ''),
            'media_name' => (string) ($upload['name'] ?? ''),
        ];
    }
    $primaryMedia = $normalizedUploads[0] ?? [];
    if (!add_conversation_message(
        $conversationId,
        (int) $user['id'],
        $role,
        $messageBody,
        (string) ($primaryMedia['media_path'] ?? ''),
        (string) ($primaryMedia['media_type'] ?? ''),
        (string) ($primaryMedia['media_name'] ?? ''),
        $normalizedUploads
    )) {
        chat_api_response(['ok' => false, 'message' => 'Message could not be sent.'], 500);
    }

    if ($role !== 'admin' && (string) ($conversationData['conversation_scope'] ?? 'support') === 'support') {
        send_support_greeting_if_missing($conversationId);
    }

    set_conversation_typing_state($conversationId, (int) $user['id'], $role, false);
    mark_messages_delivered_for_role($conversationId, $role, (int) $user['id']);
    mark_conversation_read($conversationId, (int) $user['id'], $role);

    $conversation = chat_api_find_conversation(chat_api_accessible_conversations($user), $conversationId) ?? $conversationData;
    chat_api_response(array_merge(chat_api_build_state($user, $conversation), [
        'ok' => true,
        'message' => 'Message sent.',
        'conversation_id' => $conversationId,
    ]));
}

if ($action === 'start_conversation') {
    if ($role !== 'admin') {
        chat_api_response(['ok' => false, 'message' => 'Only admins can start direct conversations from this endpoint.'], 403);
    }

    $recipientId = (int) ($_POST['recipient_user_id'] ?? $_REQUEST['recipient_user_id'] ?? 0);
    $recipientRole = canonical_role((string) ($_POST['recipient_role'] ?? $_REQUEST['recipient_role'] ?? ''));
    $visibility = strtolower(trim((string) ($_POST['visibility'] ?? $_REQUEST['visibility'] ?? 'direct')));
    $messageBody = trim((string) ($_POST['message_body'] ?? $_REQUEST['message_body'] ?? ''));
    $mediaUpload = chat_api_store_media_upload('message_media');
    if (!$mediaUpload['ok']) {
        chat_api_response(['ok' => false, 'message' => $mediaUpload['message'] ?? 'Media upload failed.'], 422);
    }
    $mediaUploads = $mediaUpload['uploads'] ?? [];

    if ($recipientId <= 0 || $recipientRole === '') {
        chat_api_response(['ok' => false, 'message' => 'Pick a recipient before starting a conversation.'], 422);
    }
    if ($messageBody === '' && count($mediaUploads) === 0) {
        chat_api_response(['ok' => false, 'message' => 'Message cannot be empty.'], 422);
    }

    $scope = $visibility === 'broadcast'
        ? 'admin_broadcast'
        : ($recipientRole === 'admin' ? 'admin_direct' : 'direct');
    $subject = $scope === 'admin_broadcast' ? 'Admin broadcast' : '';
    $conversation = create_direct_conversation((int) $user['id'], $role, $recipientId, $recipientRole, $subject, $scope);

    if (!$conversation) {
        chat_api_response(['ok' => false, 'message' => 'Conversation could not be created.'], 500);
    }

    $normalizedUploads = [];
    foreach ($mediaUploads as $upload) {
        if (!is_array($upload)) {
            continue;
        }
        $normalizedUploads[] = [
            'media_path' => (string) ($upload['path'] ?? ''),
            'media_type' => (string) ($upload['type'] ?? ''),
            'media_name' => (string) ($upload['name'] ?? ''),
        ];
    }
    $primaryMedia = $normalizedUploads[0] ?? [];
    if (!add_conversation_message(
        (int) $conversation['id'],
        (int) $user['id'],
        $role,
        $messageBody,
        (string) ($primaryMedia['media_path'] ?? ''),
        (string) ($primaryMedia['media_type'] ?? ''),
        (string) ($primaryMedia['media_name'] ?? ''),
        $normalizedUploads
    )) {
        chat_api_response(['ok' => false, 'message' => 'Message could not be sent.'], 500);
    }

    chat_api_response(array_merge(chat_api_build_state($user, $conversation), [
        'ok' => true,
        'message' => 'Conversation started.',
        'conversation_id' => (int) $conversation['id'],
    ]));
}

if ($action === 'edit_message') {
    $messageId = (int) ($_POST['message_id'] ?? $_REQUEST['message_id'] ?? 0);
    $messageBody = trim((string) ($_POST['message_body'] ?? $_REQUEST['message_body'] ?? ''));
    $message = $messageId > 0 ? get_conversation_message_by_id($messageId) : null;
    if (!$message) {
        chat_api_response(['ok' => false, 'message' => 'Message not found.'], 404);
    }

    $conversation = chat_api_find_conversation($accessibleConversations, (int) ($message['conversation_id'] ?? 0));
    if (!$conversation) {
        chat_api_response(['ok' => false, 'message' => 'Conversation not available.'], 403);
    }

    if ((int) ($message['sender_user_id'] ?? 0) !== (int) $user['id']) {
        chat_api_response(['ok' => false, 'message' => 'Only the sender can edit this message.'], 403);
    }

    if (!empty($message['is_system_event'])) {
        chat_api_response(['ok' => false, 'message' => 'System notices cannot be edited.'], 403);
    }

    if (!update_conversation_message($messageId, (int) $user['id'], $messageBody)) {
        chat_api_response(['ok' => false, 'message' => 'Message could not be updated.'], 422);
    }

    $conversation = chat_api_find_conversation(chat_api_accessible_conversations($user), (int) ($message['conversation_id'] ?? 0)) ?? $conversation;
    chat_api_response(array_merge(chat_api_build_state($user, $conversation), [
        'ok' => true,
        'message' => 'Message updated.',
        'conversation_id' => (int) ($message['conversation_id'] ?? 0),
    ]));
}

chat_api_response(['ok' => false, 'message' => 'Unknown chat action.'], 400);
