<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

if (!function_exists('groq_ocr_extract')) {
    function groq_ocr_extract($imageDataUrl, $prompt, $apiKey, array $context = array())
    {
        $attempts = array();
        $candidates = _groq_ocr_resolve_candidates($apiKey, false);
        $rediscovered = false;

        while (true) {
            $run = _groq_ocr_try_models($candidates, $imageDataUrl, $prompt, $apiKey, $attempts);

            if ($run['ok']) {
                _groq_ocr_remember_working_model($run['model']);
                _groq_ocr_log($context, $attempts, $run['model'], true);

                return array(
                    'ok' => true,
                    'content' => $run['content'],
                    'model' => $run['model'],
                );
            }

            if ($run['fatal'] || $rediscovered) {
                break;
            }

            $rediscovered = true;
            $triedModels = array_column($attempts, 'model');
            $fresh = _groq_ocr_resolve_candidates($apiKey, true);
            $candidates = array_values(array_diff($fresh, $triedModels));

            if (empty($candidates)) {
                break;
            }
        }

        _groq_ocr_log($context, $attempts, null, false);

        return array(
            'ok' => false,
            'user_message' => _groq_ocr_user_message($attempts),
        );
    }
}

if (!function_exists('_groq_ocr_try_models')) {
    function _groq_ocr_try_models(array $candidates, $imageDataUrl, $prompt, $apiKey, array &$attempts)
    {
        foreach ($candidates as $model) {
            $attempt = _groq_ocr_call_api($model, $imageDataUrl, $prompt, $apiKey);
            $attempts[] = array(
                'model' => $model,
                'http_code' => $attempt['http_code'],
                'curl_error' => $attempt['curl_error'],
                'provider_error' => $attempt['provider_error'],
            );

            if ($attempt['success']) {
                return array('ok' => true, 'fatal' => false, 'model' => $model, 'content' => $attempt['content']);
            }

            if (!$attempt['retry_next_model']) {
                return array('ok' => false, 'fatal' => true, 'model' => null, 'content' => '');
            }
        }

        return array('ok' => false, 'fatal' => false, 'model' => null, 'content' => '');
    }
}

if (!function_exists('_groq_ocr_fallback_models')) {
    function _groq_ocr_fallback_models()
    {
        return array(
            'qwen/qwen3.8-27b',
            'meta-llama/llama-4-scout-17b-16e-instruct',
            'meta-llama/llama-4-maverick-17b-128e-instruct',
        );
    }
}

if (!function_exists('_groq_ocr_preferred_prefixes')) {
    function _groq_ocr_preferred_prefixes()
    {
        return array('qwen/', 'meta-llama/llama-4', 'meta-llama/');
    }
}

if (!function_exists('_groq_ocr_cache_ttl')) {
    function _groq_ocr_cache_ttl()
    {
        return 6 * 3600;
    }
}

if (!function_exists('_groq_ocr_cache_path')) {
    function _groq_ocr_cache_path()
    {
        return APPPATH . 'cache/groq_ocr_active_model.json';
    }
}

if (!function_exists('_groq_ocr_read_cache')) {
    function _groq_ocr_read_cache()
    {
        $path = _groq_ocr_cache_path();
        if (!is_file($path)) {
            return array();
        }

        $raw = @file_get_contents($path);
        if ($raw === false || $raw === '') {
            return array();
        }

        $data = json_decode($raw, true);
        return is_array($data) ? $data : array();
    }
}

if (!function_exists('_groq_ocr_write_cache')) {
    function _groq_ocr_write_cache(array $data)
    {
        @file_put_contents(_groq_ocr_cache_path(), json_encode($data));
    }
}

if (!function_exists('_groq_ocr_remember_working_model')) {
    function _groq_ocr_remember_working_model($model)
    {
        $cache = _groq_ocr_read_cache();
        $models = isset($cache['models']) && is_array($cache['models']) ? $cache['models'] : array();
        $models = array_values(array_unique(array_merge(array($model), $models)));

        $cache['model'] = $model;
        $cache['models'] = $models;
        $cache['updated_at'] = date('Y-m-d H:i:s');
        if (empty($cache['discovered_at'])) {
            $cache['discovered_at'] = time();
        }

        _groq_ocr_write_cache($cache);
    }
}

if (!function_exists('_groq_ocr_resolve_candidates')) {
    function _groq_ocr_resolve_candidates($apiKey, $forceDiscover)
    {
        $cache = _groq_ocr_read_cache();
        $cachedModels = isset($cache['models']) && is_array($cache['models']) ? $cache['models'] : array();
        $discoveredAt = isset($cache['discovered_at']) ? (int) $cache['discovered_at'] : 0;

        if (!$forceDiscover && !empty($cachedModels) && (time() - $discoveredAt) < _groq_ocr_cache_ttl()) {
            return $cachedModels;
        }

        $discovered = _groq_ocr_discover_models($apiKey);

        if (!empty($discovered)) {
            $lastWorking = isset($cache['model']) ? (string) $cache['model'] : '';
            if ($lastWorking !== '' && in_array($lastWorking, $discovered, true)) {
                $discovered = array_values(array_unique(array_merge(array($lastWorking), $discovered)));
            }

            _groq_ocr_write_cache(array(
                'model' => $lastWorking !== '' && in_array($lastWorking, $discovered, true) ? $lastWorking : $discovered[0],
                'models' => $discovered,
                'discovered_at' => time(),
                'updated_at' => date('Y-m-d H:i:s'),
            ));

            return $discovered;
        }

        if (!empty($cachedModels)) {
            return $cachedModels;
        }

        return _groq_ocr_fallback_models();
    }
}

if (!function_exists('_groq_ocr_discover_models')) {
    function _groq_ocr_discover_models($apiKey)
    {
        $ch = curl_init('https://api.groq.com/openai/v1/models');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Bearer ' . $apiKey));
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        $raw = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($raw === false || $httpCode >= 400) {
            return array();
        }

        $response = json_decode($raw, true);
        if (!is_array($response) || !isset($response['data']) || !is_array($response['data'])) {
            return array();
        }

        $ids = array();
        foreach ($response['data'] as $model) {
            if (!is_array($model) || empty($model['id'])) {
                continue;
            }
            if (isset($model['active']) && $model['active'] === false) {
                continue;
            }

            $input = isset($model['input_modalities']) && is_array($model['input_modalities']) ? $model['input_modalities'] : array();
            $output = isset($model['output_modalities']) && is_array($model['output_modalities']) ? $model['output_modalities'] : array('text');
            if (!in_array('image', $input, true) || !in_array('text', $output, true)) {
                continue;
            }

            $ids[] = (string) $model['id'];
        }

        $prefixes = _groq_ocr_preferred_prefixes();
        usort($ids, function ($a, $b) use ($prefixes) {
            $rank = function ($id) use ($prefixes) {
                foreach ($prefixes as $index => $prefix) {
                    if (strpos($id, $prefix) === 0) {
                        return $index;
                    }
                }
                return count($prefixes);
            };

            $diff = $rank($a) - $rank($b);
            return $diff !== 0 ? $diff : strcmp($b, $a);
        });

        return $ids;
    }
}

if (!function_exists('_groq_ocr_call_api')) {
    function _groq_ocr_call_api($model, $imageDataUrl, $prompt, $apiKey)
    {
        $payload = array(
            'model' => $model,
            'temperature' => 0.2,
            'max_completion_tokens' => 300,
            'top_p' => 1,
            'stream' => false,
            'messages' => array(
                array(
                    'role' => 'user',
                    'content' => array(
                        array('type' => 'text', 'text' => $prompt),
                        array('type' => 'image_url', 'image_url' => array('url' => $imageDataUrl)),
                    ),
                ),
            ),
        );

        if (strpos($model, 'qwen/') === 0) {
            $payload['reasoning_effort'] = 'none';
            $payload['reasoning_format'] = 'hidden';
            $payload['max_completion_tokens'] = 300;
        }

        $ch = curl_init('https://api.groq.com/openai/v1/chat/completions');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey,
        ));
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);

        $rawResponse = curl_exec($ch);
        $curlError = curl_error($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($rawResponse === false || $curlError) {
            return array(
                'success' => false,
                'http_code' => $httpCode,
                'curl_error' => $curlError,
                'provider_error' => null,
                'content' => '',
                'retry_next_model' => false,
            );
        }

        $response = json_decode($rawResponse, true);

        if (!is_array($response) || $httpCode >= 400) {
            $providerError = is_array($response) && isset($response['error']) ? $response['error'] : null;

            $isAuthProblem = $httpCode === 401 || $httpCode === 403;

            return array(
                'success' => false,
                'http_code' => $httpCode,
                'curl_error' => '',
                'provider_error' => $providerError,
                'content' => '',
                'retry_next_model' => !$isAuthProblem,
            );
        }

        $content = '';
        if (isset($response['choices'][0]['message']['content']) && is_string($response['choices'][0]['message']['content'])) {
            $content = $response['choices'][0]['message']['content'];
        }

        return array(
            'success' => true,
            'http_code' => $httpCode,
            'curl_error' => '',
            'provider_error' => null,
            'content' => $content,
            'retry_next_model' => false,
        );
    }
}

if (!function_exists('_groq_ocr_user_message')) {
    function _groq_ocr_user_message($attempts)
    {
        $lastAttempt = end($attempts);
        $httpCode = is_array($lastAttempt) ? (int) $lastAttempt['http_code'] : 0;

        if ($httpCode === 401 || $httpCode === 403) {
            return "Receipt scanning isn't available right now. Please enter the details manually — we've been notified.";
        }

        if ($httpCode === 429) {
            return "Receipt scanning is busy right now. Please try again in a moment, or enter the details manually.";
        }

        return "We couldn't read this receipt automatically. Please try again, or enter the details manually.";
    }
}

if (!function_exists('_groq_ocr_log')) {
    function _groq_ocr_log($context, $attempts, $successModel, $success)
    {
        $summary = array(
            'endpoint' => isset($context['endpoint']) ? $context['endpoint'] : '',
            'user_id' => isset($context['user_id']) ? $context['user_id'] : null,
            'success' => $success,
            'model_used' => $successModel,
            'attempts' => $attempts,
        );

        $level = $success ? 'info' : 'error';
        log_message($level, 'Groq OCR ' . ($success ? 'succeeded' : 'failed') . ': ' . json_encode($summary));
    }
}
