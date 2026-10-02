<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Trả về token CSRF của phiên hiện tại (tự sinh nếu chưa có).
 */
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

/**
 * Trả về thẻ hidden input chứa token CSRF để nhúng vào form.
 */
function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="'
        . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}

/**
 * So sánh token do client gửi lên với token trong phiên.
 */
function csrf_verify(?string $token): bool
{
    if (empty($_SESSION['csrf_token']) || !is_string($token) || $token === '') {
        return false;
    }

    return hash_equals((string) $_SESSION['csrf_token'], $token);
}

/**
 * Chặn request thiếu/sai token CSRF (dùng cho endpoint AJAX).
 * Trả về JSON lỗi và kết thúc khi token không hợp lệ.
 */
function csrf_require_json(): void
{
    if (csrf_verify($_POST['csrf_token'] ?? null)) {
        return;
    }

    http_response_code(419);
    echo json_encode([
        'success' => false,
        'message' => 'Phiên làm việc đã hết hạn. Vui lòng tải lại trang và thử lại.',
    ]);
    exit;
}