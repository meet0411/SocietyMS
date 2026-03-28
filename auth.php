<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function redirect(string $path): never
{
    header("Location: " . $path);
    exit;
}

function no_cache(): void
{
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Cache-Control: post-check=0, pre-check=0", false);
    header("Pragma: no-cache");
    header("Expires: 0");
}

function require_login(): void
{
    no_cache();
    if (!isset($_SESSION["user"])) {
        redirect("login.php");
    }
}

/**
 * @return array{user_id:int, username:string, role:string}
 */
function current_user(): array
{
    /** @var array{user_id:int, username:string, role:string} */
    return $_SESSION["user"];
}

function is_admin(): bool
{
    return isset($_SESSION["user"]) && ($_SESSION["user"]["role"] ?? "") === "Admin";
}

function require_admin(): void
{
    require_login();
    if (!is_admin()) {
        http_response_code(403);
        die("Forbidden: Admin only.");
    }
}

function require_user(): void
{
    require_login();
    if (is_admin()) {
        http_response_code(403);
        die("Forbidden: Users only.");
    }
}

function flash_set(string $key, string $message): void
{
    $_SESSION["flash"][$key] = $message;
}

function flash_get(string $key): ?string
{
    if (!isset($_SESSION["flash"][$key])) {
        return null;
    }
    $msg = (string)$_SESSION["flash"][$key];
    unset($_SESSION["flash"][$key]);
    return $msg;
}

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8");
}

function csrf_token(): string
{
    if (!isset($_SESSION["csrf_token"]) || !is_string($_SESSION["csrf_token"]) || $_SESSION["csrf_token"] === "") {
        $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
    }
    return (string)$_SESSION["csrf_token"];
}

function csrf_field(): string
{
    $token = csrf_token();
    return '<input type="hidden" name="csrf_token" value="' . h($token) . '">';
}

function csrf_verify(): void
{
    $sessionToken = (string)($_SESSION["csrf_token"] ?? "");
    $postedToken = (string)($_POST["csrf_token"] ?? "");
    if ($sessionToken === "" || $postedToken === "" || !hash_equals($sessionToken, $postedToken)) {
        http_response_code(400);
        die("Invalid request (CSRF).");
    }
}
