<?php
/**
 * AI API Integration Configuration & Client
 * AI Study Assistant
 * Supports Google Gemini (Interactions API) and OpenAI API
 */

require_once __DIR__ . '/config.php';

// 1. Check if a local override file exists
if (file_exists(__DIR__ . '/openai.local.php')) {
    require_once __DIR__ . '/openai.local.php';
}

// 2. Default Configuration
if (!defined('OPENAI_API_KEY')) {
    define('OPENAI_API_KEY', getenv('OPENAI_API_KEY') ?: '');
}

// Configurable model: auto-defaults to gemini-3-flash-preview if Gemini key is provided, or gpt-4o-mini for OpenAI
if (!defined('OPENAI_MODEL')) {
    $envModel = getenv('OPENAI_MODEL');
    if ($envModel) {
        define('OPENAI_MODEL', $envModel);
    } elseif (str_starts_with(OPENAI_API_KEY, 'AQ.') || str_starts_with(OPENAI_API_KEY, 'AIza')) {
        define('OPENAI_MODEL', 'gemini-3-flash-preview');
    } else {
        define('OPENAI_MODEL', 'gpt-4o-mini');
    }
}

// Configurable API base URL (supports OpenAI, OpenRouter, Groq, or custom proxies)
if (!defined('OPENAI_BASE_URL')) {
    define('OPENAI_BASE_URL', getenv('OPENAI_BASE_URL') ?: 'https://api.openai.com/v1');
}

// API Endpoint configuration
if (!defined('OPENAI_API_MODE')) {
    define('OPENAI_API_MODE', getenv('OPENAI_API_MODE') ?: 'chat');
}

// Request Timeout in seconds
define('OPENAI_TIMEOUT', 60);

/**
 * Ask AI (automatically routes to Google Gemini or OpenAI based on API key format)
 *
 * @param string|array $prompt The student's query or conversation array
 * @param string $instructions System prompt / tutor persona instructions
 * @param string|null $model Optional model override
 * @param float $temperature Creativity temperature (0.0 to 1.0)
 * @return array ['success' => bool, 'content' => string, 'error' => string|null, 'tokens' => int]
 */
function askOpenAI($prompt, string $instructions = '', ?string $model = null, float $temperature = 0.7): array {
    $apiKey = OPENAI_API_KEY;

    if (empty($apiKey) || $apiKey === 'YOUR_API_KEY_HERE') {
        return [
            'success' => false,
            'content' => '',
            'error'   => 'AI API key is not configured. Please set your API key in config/openai.local.php or in Settings.',
            'tokens'  => 0
        ];
    }

    $defaultInstructions = "You are an expert academic tutor who helps college and university students understand difficult topics in simple language. Provide accurate, structured, educational explanations with examples.";
    $systemInstruction = !empty($instructions) ? $instructions : $defaultInstructions;

    // Detect if key is Google Gemini (starts with AQ. or AIza) or model is gemini
    $isGeminiKey = str_starts_with($apiKey, 'AQ.') || str_starts_with($apiKey, 'AIza') || str_contains($model ?: OPENAI_MODEL, 'gemini');

    if ($isGeminiKey) {
        return askGeminiInteractions($prompt, $systemInstruction, $apiKey, $model);
    }

    // Standard OpenAI API call
    return askOpenAiNative($prompt, $systemInstruction, $apiKey, $model, $temperature);
}

/**
 * Call Google Gemini via the modern Interactions API
 */
function askGeminiInteractions($prompt, string $systemInstruction, string $apiKey, ?string $modelOverride): array {
    $modelToUse = $modelOverride ?: (str_contains(OPENAI_MODEL, 'gemini') ? OPENAI_MODEL : 'gemini-3-flash-preview');
    $url = "https://generativelanguage.googleapis.com/v1beta/interactions?key=" . urlencode($apiKey);

    // Format prompt input
    $inputText = '';
    if (!empty($systemInstruction)) {
        $inputText .= "[System Academic Tutor Instructions:\n" . $systemInstruction . "]\n\n";
    }

    if (is_array($prompt)) {
        foreach ($prompt as $msg) {
            if (isset($msg['role'], $msg['content'])) {
                $role = ($msg['role'] === 'assistant') ? 'AI Tutor' : 'Student';
                $inputText .= "{$role}: {$msg['content']}\n\n";
            }
        }
    } else {
        $inputText .= (string)$prompt;
    }

    $payload = [
        'model' => $modelToUse,
        'input' => trim($inputText)
    ];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'User-Agent: AI-Study-Assistant/1.0'
        ],
        CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1, // Fast HTTP 1.1 on Windows
        CURLOPT_TIMEOUT        => OPENAI_TIMEOUT,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => 0
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($response === false) {
        return [
            'success' => false,
            'content' => '',
            'error'   => 'cURL Network Error: ' . $curlError,
            'tokens'  => 0
        ];
    }

    $decoded = json_decode($response, true);

    if ($httpCode !== 200) {
        $errMsg = 'Gemini API Error (' . $httpCode . ')';
        if (isset($decoded['error']['message'])) {
            $errMsg .= ': ' . $decoded['error']['message'];
        }
        return [
            'success' => false,
            'content' => '',
            'error'   => $errMsg,
            'tokens'  => 0
        ];
    }

    // Extract text from Gemini Interactions response
    $content = '';
    if (isset($decoded['steps']) && is_array($decoded['steps'])) {
        foreach ($decoded['steps'] as $step) {
            if (($step['type'] ?? '') === 'model_output' && !empty($step['content'])) {
                foreach ($step['content'] as $c) {
                    if (($c['type'] ?? '') === 'text' && !empty($c['text'])) {
                        $content .= $c['text'];
                    }
                }
            }
        }
    }

    if (empty($content) && isset($decoded['output'])) {
        $content = is_string($decoded['output']) ? $decoded['output'] : json_encode($decoded['output']);
    }

    $tokens = (int)($decoded['usage']['total_tokens'] ?? 0);

    if (empty($content)) {
        return [
            'success' => false,
            'content' => '',
            'error'   => 'Received empty response from Gemini model.',
            'tokens'  => $tokens
        ];
    }

    return [
        'success' => true,
        'content' => trim($content),
        'error'   => null,
        'tokens'  => $tokens
    ];
}

/**
 * Call OpenAI API (Standard chat completions)
 */
function askOpenAiNative($prompt, string $systemInstruction, string $apiKey, ?string $modelOverride, float $temperature): array {
    $modelToUse = $modelOverride ?: OPENAI_MODEL;

    $messages = [];
    $messages[] = [
        'role'    => 'system',
        'content' => $systemInstruction
    ];

    if (is_array($prompt)) {
        foreach ($prompt as $msg) {
            if (isset($msg['role'], $msg['content'])) {
                $messages[] = [
                    'role'    => $msg['role'] === 'assistant' ? 'assistant' : 'user',
                    'content' => (string)$msg['content']
                ];
            }
        }
    } else {
        $messages[] = [
            'role'    => 'user',
            'content' => (string)$prompt
        ];
    }

    $baseUrl = rtrim(OPENAI_BASE_URL, '/');
    $url = (OPENAI_API_MODE === 'responses') 
        ? $baseUrl . '/responses'
        : $baseUrl . '/chat/completions';

    if (OPENAI_API_MODE === 'responses') {
        $payload = [
            'model'        => $modelToUse,
            'instructions' => $systemInstruction,
            'input'        => is_array($prompt) ? $prompt : [['role' => 'user', 'content' => (string)$prompt]],
            'temperature'  => $temperature
        ];
    } else {
        $payload = [
            'model'       => $modelToUse,
            'messages'    => $messages,
            'temperature' => $temperature,
            'max_tokens'  => 3000
        ];
    }

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey,
            'User-Agent: AI-Study-Assistant/1.0'
        ],
        CURLOPT_TIMEOUT        => OPENAI_TIMEOUT,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => 0
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($response === false) {
        return [
            'success' => false,
            'content' => '',
            'error'   => 'cURL Network Error: ' . $curlError,
            'tokens'  => 0
        ];
    }

    $decoded = json_decode($response, true);

    if ($httpCode !== 200) {
        $errorMessage = 'OpenAI API Error (' . $httpCode . ')';
        if (isset($decoded['error']['message'])) {
            $errorMessage .= ': ' . $decoded['error']['message'];
        } elseif (isset($decoded['message'])) {
            $errorMessage .= ': ' . $decoded['message'];
        }

        if ($httpCode === 401) {
            $errorMessage .= ' (Please verify your API key).';
        } elseif ($httpCode === 429) {
            $errorMessage .= ' (Rate limit or quota exceeded. Please check billing).';
        }

        return [
            'success' => false,
            'content' => '',
            'error'   => $errorMessage,
            'tokens'  => 0
        ];
    }

    $content = '';
    $tokens = 0;

    if (OPENAI_API_MODE === 'responses' && isset($decoded['output'])) {
        if (is_string($decoded['output'])) {
            $content = $decoded['output'];
        } elseif (isset($decoded['output'][0]['content'])) {
            $content = $decoded['output'][0]['content'];
        }
    } else {
        if (isset($decoded['choices'][0]['message']['content'])) {
            $content = $decoded['choices'][0]['message']['content'];
        }
        if (isset($decoded['usage']['total_tokens'])) {
            $tokens = (int)$decoded['usage']['total_tokens'];
        }
    }

    if (empty($content)) {
        return [
            'success' => false,
            'content' => '',
            'error'   => 'Received empty response from AI model.',
            'tokens'  => $tokens
        ];
    }

    return [
        'success' => true,
        'content' => trim($content),
        'error'   => null,
        'tokens'  => $tokens
    ];
}
