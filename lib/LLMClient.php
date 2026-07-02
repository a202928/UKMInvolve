<?php

class LLMClient
{
    private string $provider;
    private string $apiKey;

    public function __construct()
    {
        $this->provider = strtolower($_ENV['LLM_PROVIDER'] ?? 'gemini');
        $this->apiKey = $_ENV['LLM_API_KEY'] ?? '';
    }

    /**
     * Send a request to the configured LLM provider.
     * 
     * @param string $systemPrompt The system instruction context.
     * @param array $messages Array of message history [['role' => 'user|assistant', 'content' => '...']]
     * @return string The generated response
     * @throws Exception If API key is missing or request fails
     */
    public function generateResponse(string $systemPrompt, array $messages): string
    {
        if (empty($this->apiKey)) {
            throw new Exception("LLM API key is not configured in .env file.");
        }

        return match ($this->provider) {
            'openai' => $this->callOpenAI($systemPrompt, $messages),
            'anthropic' => $this->callAnthropic($systemPrompt, $messages),
            'gemini' => $this->callGemini($systemPrompt, $messages),
            default => throw new Exception("Unsupported LLM provider: {$this->provider}")
        };
    }

    private function callOpenAI(string $systemPrompt, array $messages): string
    {
        $payloadMessages = [
            ['role' => 'system', 'content' => $systemPrompt]
        ];
        
        foreach ($messages as $m) {
            $payloadMessages[] = ['role' => $m['role'], 'content' => $m['content']];
        }

        $payload = [
            'model' => 'gpt-4o-mini',
            'messages' => $payloadMessages,
            'max_tokens' => 800,
            'temperature' => 0.7
        ];

        $response = $this->makeHttpRequest('https://api.openai.com/v1/chat/completions', $payload, [
            'Authorization: Bearer ' . $this->apiKey,
            'Content-Type: application/json'
        ]);

        if (empty($response['choices'][0]['message']['content'])) {
            throw new Exception("Invalid response from OpenAI API.");
        }

        return $response['choices'][0]['message']['content'];
    }

    private function callAnthropic(string $systemPrompt, array $messages): string
    {
        $payload = [
            'model' => 'claude-3-5-sonnet-20241022',
            'max_tokens' => 800,
            'system' => $systemPrompt,
            'messages' => $messages,
            'temperature' => 0.7
        ];

        $response = $this->makeHttpRequest('https://api.anthropic.com/v1/messages', $payload, [
            'x-api-key: ' . $this->apiKey,
            'anthropic-version: 2023-06-01',
            'Content-Type: application/json'
        ]);

        if (empty($response['content'][0]['text'])) {
            throw new Exception("Invalid response from Anthropic API.");
        }

        return $response['content'][0]['text'];
    }

    private function callGemini(string $systemPrompt, array $messages): string
    {
        // Convert history to Gemini format (user/model)
        $contents = [];
        foreach ($messages as $m) {
            $contents[] = [
                'role' => $m['role'] === 'assistant' ? 'model' : 'user',
                'parts' => [['text' => $m['content']]]
            ];
        }

        $payload = [
            'systemInstruction' => [
                'parts' => [['text' => $systemPrompt]]
            ],
            'contents' => $contents,
            'generationConfig' => [
                'maxOutputTokens' => 800,
                'temperature' => 0.7
            ]
        ];

        $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-3.1-flash-lite:generateContent';
        $response = $this->makeHttpRequest($url, $payload, [
            'Content-Type: application/json',
            'x-goog-api-key: ' . $this->apiKey
        ]);

        if (empty($response['candidates'][0]['content']['parts'][0]['text'])) {
            throw new Exception("Invalid response from Gemini API.");
        }

        return $response['candidates'][0]['content']['parts'][0]['text'];
    }

    private function makeHttpRequest(string $url, array $payload, array $headers): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        ]);

        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($result === false) {
            throw new Exception("cURL Error: " . $error);
        }

        $decoded = json_decode($result, true);
        if ($httpCode >= 400) {
            $msg = $decoded['error']['message'] ?? json_encode($decoded);
            throw new Exception("API HTTP Error ($httpCode): " . $msg);
        }

        return $decoded ?: [];
    }
}
