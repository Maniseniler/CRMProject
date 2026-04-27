<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . "/../config/db.php";

$gmail = null;

try {
    $client = new Google_Client();
    $client->setClientId('');
    $client->setClientSecret('');

    $token = $db->getGmailToken();

    if (!$token || !is_array($token) || empty($token['access_token'])) {
        throw new Exception("Aucun token Gmail valide trouvé.");
    }

    $expiresIn = max(0, ($token['expires_at'] - time()));

    $client->setAccessToken([
        'access_token' => $token['access_token'],
        'refresh_token' => $token['refresh_token'] ?? null,
        'expires_in' => $expiresIn
    ]);

    if ($client->isAccessTokenExpired()) {
        if (!empty($token['refresh_token'])) {
            $newToken = $client->fetchAccessTokenWithRefreshToken($token['refresh_token']);
            if (empty($newToken['access_token'])) {
                throw new Exception("Impossible de rafraîchir le token Gmail.");
            }

            $access_token = $newToken['access_token'];
            $expires_at = time() + ($newToken['expires_in'] ?? 3600);

            // Save new token in DB
            $db->updateGmailToken($access_token, $expires_at);

            $client->setAccessToken([
                'access_token' => $access_token,
                'refresh_token' => $token['refresh_token'],
                'expires_in' => $newToken['expires_in']
            ]);
        } else {
            throw new Exception("Refresh token introuvable. Reconnectez Gmail.");
        }
    }

    $gmail = new Google_Service_Gmail($client);

} catch (Exception $e) {
    error_log("Gmail service initialization failed: " . $e->getMessage());
    $gmail = null;
}

return $gmail;