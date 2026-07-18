<?php
// config/email.php

function sendEmail($to, $subject, $body, $altBody = '')
{
    $apiKey = trim((string) getenv('RESEND_API_KEY'));
    $fromEmail = trim((string) getenv('RESEND_FROM_EMAIL'));
    $appEnv = strtolower(trim((string) getenv('APP_ENV')));
    $testRecipient = trim((string) getenv('RESEND_TEST_RECIPIENT'));

    if ($apiKey === '') {
        error_log('RESEND_API_KEY is missing.');
        return 'Email API key is missing.';
    }

    if ($fromEmail === '') {
        error_log('RESEND_FROM_EMAIL is missing.');
        return 'Sender email address is missing.';
    }

    $originalRecipient = strtolower(trim((string) $to));

    if (!filter_var($originalRecipient, FILTER_VALIDATE_EMAIL)) {
        error_log('Invalid recipient email: ' . $originalRecipient);
        return 'Invalid recipient email address.';
    }

    $recipient = $originalRecipient;
    $finalSubject = trim((string) $subject);
    $finalBody = (string) $body;
    $finalAltBody = (string) $altBody;

    // Resend test mode can only deliver to the verified Resend account address.
    // Development messages are therefore rerouted securely to that inbox.
    if ($appEnv === 'development') {
        if (!filter_var($testRecipient, FILTER_VALIDATE_EMAIL)) {
            error_log('RESEND_TEST_RECIPIENT is missing or invalid in development mode.');
            return 'Development test recipient is missing or invalid.';
        }

        $recipient = strtolower($testRecipient);
        $safeOriginalRecipient = htmlspecialchars($originalRecipient, ENT_QUOTES, 'UTF-8');
        $finalSubject = '[TEST for ' . $originalRecipient . '] ' . $finalSubject;
        $finalBody = '<div style="padding:12px;margin-bottom:16px;border:1px solid #d7c7db;background:#fefaf5;font-family:Arial,sans-serif;">' .
            '<strong>Development test email</strong><br>' .
            'Intended customer: ' . $safeOriginalRecipient .
            '</div>' . $finalBody;

        $finalAltBody = "DEVELOPMENT TEST EMAIL\nIntended customer: {$originalRecipient}\n\n" . $finalAltBody;
    } elseif ($appEnv !== 'production') {
        error_log('APP_ENV must be explicitly set to development or production.');
        return 'Application environment is not configured correctly.';
    }

    $payload = [
        'from' => $fromEmail,
        'to' => [$recipient],
        'subject' => $finalSubject,
        'html' => $finalBody,
    ];

    if ($finalAltBody !== '') {
        $payload['text'] = $finalAltBody;
    }

    try {
        $jsonPayload = json_encode($payload, JSON_THROW_ON_ERROR);
    } catch (JsonException $exception) {
        error_log('Resend payload encoding failed: ' . $exception->getMessage());
        return 'Email payload could not be created.';
    }

    $ch = curl_init('https://api.resend.com/emails');

    if ($ch === false) {
        return 'Email service could not be initialised.';
    }

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json',
            'Accept: application/json',
        ],
        CURLOPT_POSTFIELDS => $jsonPayload,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 20,
    ]);

    $response = curl_exec($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($response === false || $curlError !== '') {
        error_log('Resend cURL error: ' . $curlError);
        return 'Email API connection failed.';
    }

    $responseData = json_decode((string) $response, true);

    if ($httpCode >= 200 && $httpCode < 300) {
        return true;
    }

    $apiMessage = is_array($responseData)
        ? (string) ($responseData['message'] ?? 'Unknown Resend error.')
        : (string) $response;

    error_log('Resend API error. HTTP ' . $httpCode . ': ' . $apiMessage);

    return 'Email could not be sent: ' . $apiMessage;
}