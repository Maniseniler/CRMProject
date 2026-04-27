<?php

function classifyEmailWithAI($subject, $body) {
    $prompt = <<<PROMPT
You are a strict JSON API.

Return ONLY valid JSON.
No text before or after.
No explanation.

Format:
{
  "category": "ticket" or "feedback" or "other",
  "is_ticket": 0 or 1,
  "is_feedback": 0 or 1,
  "summary": "short sentence",
  "detected_issue": "snake_case",
  "priority": "low" or "normal" or "high",
  "confidence": number
}

Rules:
- Any problem, complaint, delivery issue, billing issue, login issue, bug, or help request => ticket
- Only thanks, appreciation, suggestion, or opinion with no problem => feedback
- If detected_issue is missing_delivery, billing_issue, login_problem, bug_report, wrong_item, not_as_expected, or damaged_product, category must be "ticket"

- summary: max 12 words
- detected_issue must be exactly one of: missing_delivery, billing_issue, login_problem, bug_report, wrong_item, not_as_expected, damaged_product, positive_feedback, other
- confidence: integer 0-100

Email subject:
{$subject}

Email body:
{$body}

JSON:
PROMPT;

    $payload = [
    "model" => "qwen2.5:0.5b",
    "prompt" => $prompt,
    "stream" => false,
    "options" => [
        "temperature" => 0,
        "top_p" => 0.9,
        "num_predict" => 80,
        "num_ctx" => 512
    ]
];

    $ch = curl_init("http://ollama:11434/api/generate");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ["Content-Type: application/json"],
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_TIMEOUT =>180,
		CURLOPT_CONNECTTIMEOUT => 10
    ]);

    $response = curl_exec($ch);
    $curlError = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response === false || $curlError) {
        return fallback("api_error", $curlError);
    }

    if ($httpCode < 200 || $httpCode >= 300) {
        return fallback("api_error", "HTTP " . $httpCode);
    }

    $decoded = json_decode($response, true);
    $text = $decoded['response'] ?? '';

    if (!$text) {
        return fallback("empty_response", "No content");
    }

    if (preg_match('/\{(?:[^{}]|(?R))*\}/', $text, $matches)) {
        $result = json_decode($matches[0], true);
    } else {
        $result = null;
    }

    if (!is_array($result)) {
        return fallback("invalid_json", $text);
    }

    return normalize($result);
}

function fallback($issue, $msg) {
    return [
        'category' => 'ticket',
        'is_ticket' => 1,
        'is_feedback' => 0,
        'summary' => 'AI error: ' . substr($msg, 0, 80),
        'detected_issue' => $issue,
        'priority' => 'normal',
        'confidence' => 0
    ];
}

function normalize($r) {
    $category = in_array($r['category'] ?? '', ['ticket','feedback','other'], true)
        ? $r['category']
        : 'other';

    $summary = trim((string)($r['summary'] ?? ''));
    if ($summary === '') {
        $summary = 'No summary provided';
    }

    $issue = strtolower((string)($r['detected_issue'] ?? 'other'));
    $issue = preg_replace('/[^a-z0-9_]/', '_', $issue);
    $issue = preg_replace('/_+/', '_', $issue);
    $issue = trim($issue, '_') ?: 'other';

    $knownIssues = [
        'missing_delivery',
        'billing_issue',
        'login_problem',
        'bug_report',
        'wrong_item',
        'not_as_expected',
        'damaged_product',
        'positive_feedback',
        'other'
    ];

    if (!in_array($issue, $knownIssues, true)) {
        if (
            str_contains($issue, 'deliver') ||
            str_contains($issue, 'shipping') ||
            str_contains($issue, 'package') ||
            str_contains($issue, 'order')
        ) {
            $issue = 'missing_delivery';
        } elseif (
            str_contains($issue, 'receive') ||
            str_contains($issue, 'product') ||
            str_contains($issue, 'item')
        ) {
            $issue = 'not_as_expected';
        } else {
            $issue = 'other';
        }
    }

    if ($summary === '' || strlen($summary) < 8) {
        switch ($issue) {
            case 'missing_delivery':
                $summary = 'Customer reports a delivery issue.';
                break;
            case 'billing_issue':
                $summary = 'Customer reports a billing issue.';
                break;
            case 'login_problem':
                $summary = 'Customer reports a login problem.';
                break;
            case 'bug_report':
                $summary = 'Customer reported a bug.';
                break;
            case 'wrong_item':
                $summary = 'Customer received the wrong item.';
                break;
            case 'not_as_expected':
                $summary = 'Customer says the product was not as expected.';
                break;
            case 'damaged_product':
                $summary = 'Customer received a damaged product.';
                break;
            case 'positive_feedback':
                $summary = 'Customer shared positive feedback.';
                break;
            default:
                $summary = 'Customer message analyzed.';
        }
    }

    $priority = in_array($r['priority'] ?? '', ['low','normal','high'], true)
        ? $r['priority']
        : 'normal';

    $confidence = (int)($r['confidence'] ?? 0);
    if ($confidence < 0) $confidence = 0;
    if ($confidence > 100) $confidence = 100;

    $ticketIssues = [
        'missing_delivery',
        'billing_issue',
        'login_problem',
        'bug_report',
        'wrong_item',
        'not_as_expected',
        'damaged_product'
    ];

    if (in_array($issue, $ticketIssues, true)) {
        $category = 'ticket';
    } elseif ($issue === 'positive_feedback') {
        $category = 'feedback';
    }

    $isTicket = $category === 'ticket' ? 1 : 0;
    $isFeedback = $category === 'feedback' ? 1 : 0;

    return [
        'category' => $category,
        'is_ticket' => $isTicket,
        'is_feedback' => $isFeedback,
        'summary' => $summary,
        'detected_issue' => $issue,
        'priority' => $priority,
        'confidence' => $confidence
    ];
}
