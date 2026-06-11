<?php
// config/email.php

function sendEmail($to, $subject, $body, $altBody = '') {
    $apiKey = getenv('RESEND_API_KEY');
    $fromEmail = getenv('RESEND_FROM_EMAIL') ?: 'SaraJane <onboarding@resend.dev>';

    if (!$apiKey) {
        error_log('RESEND_API_KEY is missing.');
        return 'Email API key is missing.';
    }

    $payload = [
        'from' => $fromEmail,
        'to' => [$to],
        'subject' => $subject,
        'html' => $body,
    ];

    $ch = curl_init('https://api.resend.com/emails');

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json',
        ],
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_TIMEOUT => 10,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);

    curl_close($ch);

    if ($response === false || $curlError) {
        error_log('Resend cURL error: ' . $curlError);
        return 'Email could not be sent. API connection failed.';
    }

    if ($httpCode >= 200 && $httpCode < 300) {
        return true;
    }

    error_log('Resend API error: HTTP ' . $httpCode . ' - ' . $response);
    return 'Email could not be sent. API error: ' . $response;
}