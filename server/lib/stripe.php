<?php
/**
 * Cliente minimo de Stripe SIN SDK ni Composer: cURL contra la API REST y
 * verificacion manual de la firma del webhook. Pensado para hosting compartido
 * (Webempresa) donde instalar dependencias es incomodo.
 *
 * Cubre exactamente lo que el checkout necesita:
 *  - stripe_create_checkout_session(): crea la sesion de Stripe Checkout.
 *  - stripe_verify_webhook(): valida la firma (t=...,v1=...) del header
 *    Stripe-Signature con HMAC-SHA256 y tolerancia de tiempo.
 */

declare(strict_types=1);

/**
 * Crea una Stripe Checkout Session. $params ya viene en la forma anidada que
 * espera Stripe; http_build_query lo serializa a la notacion con corchetes
 * (line_items[0][price_data][...]) que la API v1 acepta.
 *
 * @return array{id:string,url:string} datos de la sesion
 * @throws RuntimeException en error de red o de la API
 */
function stripe_create_checkout_session(string $secretKey, array $params): array
{
    $res = stripe_request($secretKey, 'POST', '/v1/checkout/sessions', $params);
    if (!isset($res['id'], $res['url'])) {
        throw new RuntimeException(
            'Respuesta inesperada de Stripe: ' . json_encode($res, JSON_UNESCAPED_UNICODE)
        );
    }
    return ['id' => $res['id'], 'url' => $res['url']];
}

/** Recupera una sesion (para reconciliar el webhook si hace falta). */
function stripe_get_session(string $secretKey, string $sessionId): array
{
    return stripe_request($secretKey, 'GET', '/v1/checkout/sessions/' . urlencode($sessionId), null);
}

/**
 * Peticion generica a la API de Stripe. Devuelve el JSON decodificado.
 * @throws RuntimeException si cURL falla o la API responde >= 400.
 */
function stripe_request(string $secretKey, string $method, string $path, ?array $params): array
{
    $url = 'https://api.stripe.com' . $path;
    $ch = curl_init();

    $headers = [
        'Authorization: Bearer ' . $secretKey,
        'Stripe-Version: 2024-06-20',
    ];

    $opts = [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_CUSTOMREQUEST  => $method,
    ];

    if ($method === 'POST' && $params !== null) {
        // Notacion con corchetes para arrays anidados (line_items[0][...]).
        $opts[CURLOPT_POSTFIELDS] = http_build_query($params, '', '&');
        $headers[] = 'Content-Type: application/x-www-form-urlencoded';
    }

    $opts[CURLOPT_HTTPHEADER] = $headers;
    curl_setopt_array($ch, $opts);

    $raw = curl_exec($ch);
    if ($raw === false) {
        $err = curl_error($ch);
        curl_close($ch);
        throw new RuntimeException('cURL hacia Stripe fallo: ' . $err);
    }
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $data = json_decode($raw, true);
    if (!is_array($data)) {
        throw new RuntimeException('Stripe devolvio un cuerpo no-JSON (HTTP ' . $code . ')');
    }
    if ($code >= 400) {
        $msg = $data['error']['message'] ?? ('HTTP ' . $code);
        throw new RuntimeException('Stripe API error: ' . $msg);
    }
    return $data;
}

/**
 * Verifica la firma del webhook. Replica stripe.webhooks.constructEvent:
 * header "t=timestamp,v1=firma"; firma = HMAC-SHA256(secret, "timestamp.payload").
 *
 * @param int $tolerance segundos de tolerancia (default 300, como el SDK).
 * @return bool true si la firma es valida y esta dentro de la tolerancia.
 */
function stripe_verify_webhook(string $payload, string $sigHeader, string $secret, int $tolerance = 300): bool
{
    $timestamp = null;
    $signatures = [];
    foreach (explode(',', $sigHeader) as $part) {
        $kv = explode('=', trim($part), 2);
        if (count($kv) !== 2) {
            continue;
        }
        [$k, $v] = $kv;
        if ($k === 't') {
            $timestamp = (int) $v;
        } elseif ($k === 'v1') {
            $signatures[] = $v;
        }
    }
    if ($timestamp === null || !$signatures) {
        return false;
    }
    // Rechaza timestamps fuera de tolerancia (anti-replay).
    if (abs(time() - $timestamp) > $tolerance) {
        return false;
    }
    $expected = hash_hmac('sha256', $timestamp . '.' . $payload, $secret);
    foreach ($signatures as $sig) {
        if (hash_equals($expected, $sig)) {
            return true;
        }
    }
    return false;
}
