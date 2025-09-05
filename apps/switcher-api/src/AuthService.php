<?php

namespace App;

class AuthService
{
    private string $monolithApiUrl;

    public function __construct(string $monolithApiUrl)
    {
        $this->monolithApiUrl = $monolithApiUrl;
    }

    /**
     * Gets the user ID from the monolith by validating the token.
     *
     * @param string $authHeader The full "Authorization: Bearer <token>" header.
     * @return int|null The user ID, or null if unauthorized.
     */
    public function getUserId(string $authHeader): ?int
    {
        $url = $this->monolithApiUrl . '/api/v1/me';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: ' . $authHeader,
            'Content-Type: application/json',
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            return null;
        }

        $data = json_decode($response, true);
        return $data['user_id'] ?? null;
    }
}
