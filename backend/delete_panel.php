<?php
// delete_panel.php -- DEAD CODE, intentionally disabled.
//
// Nothing in the UI calls this endpoint, and backend/.htaccess does not
// grant it access (blocked by the blanket "Require all denied"). It's
// stubbed out here too, defense-in-depth, in case .htaccess is ever
// misconfigured. Safe to delete this file entirely.

http_response_code(404);
exit;
