<?php
/**
 * Server-Side Google ID Token Verification & UP Mail Auth Handler
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Accept only POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method Not Allowed']);
    exit;
}

// Read JSON payload from request body
$json_input = file_get_contents('php://input');
$data = json_decode($json_input, true);
$id_token = trim($data['id_token'] ?? '');

if (empty($id_token)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Google authentication token is missing.']);
    exit;
}

try {
    // 1. Cryptographically verify ID Token using Google's OAuth2 TokenInfo Service
    $verification_url = 'https://oauth2.googleapis.com/tokeninfo?id_token=' . urlencode($id_token);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $verification_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    if ($response === false || $http_code !== 200) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'error'   => 'Token verification failed with Google servers. Please try logging in again.'
        ]);
        exit;
    }

    $payload = json_decode($response, true);

    if (!$payload || empty($payload['sub'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Invalid or malformed Google ID token payload.']);
        exit;
    }

    // 2. Validate Audience (aud) matches our Client ID
    $expected_client_id = GOOGLE_CLIENT_ID;
    if (isset($payload['aud']) && $payload['aud'] !== $expected_client_id) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Google OAuth audience mismatch. Security check failed.']);
        exit;
    }

    // 3. Validate Issuer (iss)
    $valid_issuers = ['accounts.google.com', 'https://accounts.google.com'];
    if (isset($payload['iss']) && !in_array($payload['iss'], $valid_issuers, true)) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Google OAuth token issuer invalid.']);
        exit;
    }

    // 4. Validate Token Expiration (exp)
    if (isset($payload['exp']) && (int)$payload['exp'] < time()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Google login session expired. Please sign in again.']);
        exit;
    }

    // 5. Verify Email Status and Enforce Domain Restriction (@up.edu.ph)
    $email = strtolower(trim($payload['email'] ?? ''));
    $email_verified = filter_var($payload['email_verified'] ?? false, FILTER_VALIDATE_BOOLEAN);

    if (empty($email) || !$email_verified) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Your Google email address must be verified.']);
        exit;
    }

    $allowed_domain = ALLOWED_GOOGLE_DOMAIN; // e.g. "up.edu.ph"
    $user_domain = substr(strrchr($email, "@"), 1);

    // Check if domain matches ALLOWED_GOOGLE_DOMAIN or ends with .up.edu.ph
    $is_allowed = ($user_domain === $allowed_domain) || (str_ends_with($user_domain, '.' . $allowed_domain));

    if (!$is_allowed) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'error'   => 'Access restricted: Only official University of the Philippines (@' . $allowed_domain . ') accounts are permitted.'
        ]);
        exit;
    }

    // Extract permanent identifier (google_sub) and user name
    $google_sub = $payload['sub'];
    $name = trim($payload['name'] ?? '');
    $picture = $payload['picture'] ?? null;
    if (empty($name)) {
        $name = strstr($email, '@', true) ?: 'UP User';
    }

    // 6. Database Upsert using google_sub
    $pdo = get_db_connection();
    
    // Check if user exists by google_sub
    $stmt = $pdo->prepare("SELECT id, name, email FROM users WHERE google_sub = :google_sub LIMIT 1");
    $stmt->execute(['google_sub' => $google_sub]);
    $existing_user = $stmt->fetch();

    if ($existing_user) {
        // Update user details and last_login_at timestamp
        $update_stmt = $pdo->prepare("
            UPDATE users 
            SET name = :name, email = :email, last_login_at = CURRENT_TIMESTAMP 
            WHERE id = :id
        ");
        $update_stmt->execute([
            'name'  => $name,
            'email' => $email,
            'id'    => $existing_user['id']
        ]);
        $user_id = (int)$existing_user['id'];
    } else {
        // Insert new user record
        $insert_stmt = $pdo->prepare("
            INSERT INTO users (google_sub, name, email, last_login_at) 
            VALUES (:google_sub, :name, :email, CURRENT_TIMESTAMP)
        ");
        $insert_stmt->execute([
            'google_sub' => $google_sub,
            'name'       => $name,
            'email'      => $email
        ]);
        $user_id = (int)$pdo->lastInsertId();
    }

    // 7. Regenerate session ID and set session state
    if (!headers_sent()) {
        session_regenerate_id(true);
    }

    $_SESSION['user_logged_in']  = true;
    $_SESSION['user_id']         = $user_id;
    $_SESSION['user_name']       = $name;
    $_SESSION['user_email']      = $email;
    $_SESSION['user_google_sub'] = $google_sub;
    $_SESSION['user_picture']    = $picture;

    // Check if user is an authorized administrator
    $stmt_admin = $pdo->prepare("SELECT id, name, email FROM admins WHERE email = :email AND is_active = 1 LIMIT 1");
    $stmt_admin->execute(['email' => $email]);
    $admin_record = $stmt_admin->fetch();

    $is_admin = false;
    $redirect_url = null;

    if ($admin_record) {
        $is_admin = true;
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_id']        = (int)$admin_record['id'];
        $_SESSION['admin_name']      = $admin_record['name'];
        $_SESSION['admin_email']     = $email;
        $_SESSION['is_admin']        = true;
        $redirect_url                = 'admin/calendar';
    }

    echo json_encode([
        'success'      => true,
        'message'      => 'Successfully signed in with UP Mail.',
        'is_admin'     => $is_admin,
        'redirect_url' => $redirect_url,
        'user'         => [
            'id'    => $user_id,
            'name'  => $name,
            'email' => $email
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error'   => 'An error occurred during authentication: ' . $e->getMessage()
    ]);
}
