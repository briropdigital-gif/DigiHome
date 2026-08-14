# Chat Improvements Plan

## Goals
1. Admin: Show chat peer's display picture at the top of the conversation (WhatsApp-style).
2. Admin: Show typing indicator in the topbar next to the peer's name (WhatsApp-style).
3. Allow attaching multiple files in chat (not just one).

## Steps
- [x] `admin/chat.php` - Add typing indicator element in topbar peer info block
- [x] `assets/js/app.js` - Add topbar typing indicator support to `renderTyping()`
- [x] `assets/css/style.css` - Add WhatsApp-style layout CSS for peer block (avatar, name, status, typing)
- [x] `seeker/chat.php` - Add `multiple` + change `name="message_media"` → `name="message_media[]"`
- [x] `owner/chat.php` - Add `multiple` + change `name="message_media"` → `name="message_media[]"`
- [x] `marketer/chat.php` - Add `multiple` + change `name="message_media"` → `name="message_media[]"`
