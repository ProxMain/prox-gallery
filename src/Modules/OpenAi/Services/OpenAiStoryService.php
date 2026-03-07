<?php

declare(strict_types=1);

namespace Prox\ProxGallery\Modules\OpenAi\Services;

use InvalidArgumentException;
use RuntimeException;

/**
 * Generates and applies image stories using OpenAI.
 */
final class OpenAiStoryService
{
    public function __construct(private OpenAiSettingsService $settings)
    {
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array{attachment_id:int,story:string,short_title:string,language:string,template_key:string,prompt:string,model:string}
     */
    public function generate(array $payload): array
    {
        $attachmentId = isset($payload['attachment_id']) ? (int) $payload['attachment_id'] : 0;

        if ($attachmentId <= 0) {
            throw new InvalidArgumentException('Attachment ID is required.');
        }

        $post = \get_post($attachmentId);

        if (! $post instanceof \WP_Post || $post->post_type !== 'attachment') {
            throw new InvalidArgumentException('Attachment not found.');
        }

        $settings = $this->settings->settings();
        $apiKey = trim($settings['api_key']);

        if ($apiKey === '') {
            throw new InvalidArgumentException('OpenAI API key is not configured.');
        }

        $templateKey = \sanitize_key((string) ($payload['template_key'] ?? 'factual'));
        $language = trim((string) ($payload['language'] ?? 'English'));
        $promptOverride = trim((string) ($payload['prompt_override'] ?? ''));

        if ($language === '') {
            throw new InvalidArgumentException('Language is required.');
        }

        $templates = $settings['prompt_templates'];
        $templatePrompt = '';

        foreach ($templates as $template) {
            if ($template['key'] === $templateKey) {
                $templatePrompt = (string) $template['prompt'];
                break;
            }
        }

        if ($templatePrompt === '') {
            throw new InvalidArgumentException('Prompt template not found.');
        }

        $prompt = $promptOverride !== '' ? $promptOverride : $templatePrompt;

        $imagePath = \get_attached_file($attachmentId);

        if (! is_string($imagePath) || $imagePath === '' || ! \is_readable($imagePath)) {
            throw new RuntimeException('Unable to read attachment file for AI generation.');
        }

        $mime = \get_post_mime_type($attachmentId);

        if (! is_string($mime) || strpos($mime, 'image/') !== 0) {
            throw new InvalidArgumentException('Only image attachments are supported for AI stories.');
        }

        $raw = \file_get_contents($imagePath);

        if (! is_string($raw) || $raw === '') {
            throw new RuntimeException('Unable to read attachment bytes for AI generation.');
        }

        $base64 = \base64_encode($raw);
        $dataUrl = sprintf('data:%s;base64,%s', $mime, $base64);

        $request = [
            'model' => (string) $settings['model'],
            'input' => [
                [
                    'role' => 'system',
                    'content' => [
                        [
                            'type' => 'input_text',
                            'text' => 'You generate image stories for WordPress media descriptions. Be accurate, safe, and concise unless asked otherwise.',
                        ],
                    ],
                ],
                [
                    'role' => 'user',
                    'content' => [
                        [
                            'type' => 'input_text',
                            'text' => sprintf(
                                "Language: %s\nTemplate: %s\nInstruction: %s\n\nReturn strictly valid JSON only with keys \"short_title\" and \"story\". The \"short_title\" must be 1 to 4 words maximum and based on the image. The \"story\" must be grounded in visible details from the image.",
                                $language,
                                $templateKey,
                                $prompt
                            ),
                        ],
                        [
                            'type' => 'input_image',
                            'image_url' => $dataUrl,
                        ],
                    ],
                ],
            ],
        ];

        $response = \wp_remote_post(
            'https://api.openai.com/v1/responses',
            [
                'headers' => [
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type' => 'application/json',
                ],
                'timeout' => 60,
                'body' => \wp_json_encode($request),
            ]
        );

        if ($response instanceof \WP_Error) {
            throw new RuntimeException($response->get_error_message());
        }

        $status = (int) \wp_remote_retrieve_response_code($response);
        $body = (string) \wp_remote_retrieve_body($response);
        $json = \json_decode($body, true);

        if ($status < 200 || $status >= 300 || ! is_array($json)) {
            $error = is_array($json) && isset($json['error']['message'])
                ? (string) $json['error']['message']
                : 'OpenAI request failed.';

            throw new RuntimeException($error);
        }

        $outputText = $this->extractOutputText($json);

        if ($outputText === '') {
            throw new RuntimeException('OpenAI did not return any story payload.');
        }

        $parsed = $this->parseGeneratedJsonPayload($outputText);
        $shortTitle = trim((string) ($parsed['short_title'] ?? ''));
        $story = trim((string) ($parsed['story'] ?? ''));

        if ($shortTitle === '') {
            throw new RuntimeException('OpenAI did not return a short title.');
        }

        if ($story === '') {
            throw new RuntimeException('OpenAI did not return any story text.');
        }

        if ($this->wordCount($shortTitle) > 4) {
            throw new RuntimeException('OpenAI short title exceeded 4 words.');
        }

        return [
            'attachment_id' => $attachmentId,
            'story' => $story,
            'short_title' => $shortTitle,
            'language' => $language,
            'template_key' => $templateKey,
            'prompt' => $prompt,
            'model' => (string) $settings['model'],
        ];
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array{attachment_id:int,story:string,short_title:string}
     */
    public function apply(array $payload): array
    {
        $attachmentId = isset($payload['attachment_id']) ? (int) $payload['attachment_id'] : 0;
        $story = isset($payload['story']) ? trim((string) $payload['story']) : '';
        $shortTitle = isset($payload['short_title']) ? trim((string) $payload['short_title']) : '';

        if ($attachmentId <= 0) {
            throw new InvalidArgumentException('Attachment ID is required.');
        }

        if ($story === '') {
            throw new InvalidArgumentException('Story text is required.');
        }

        if ($shortTitle === '') {
            throw new InvalidArgumentException('Short title is required.');
        }

        if ($this->wordCount($shortTitle) > 4) {
            throw new InvalidArgumentException('Short title must be 4 words maximum.');
        }

        $post = \get_post($attachmentId);

        if (! $post instanceof \WP_Post || $post->post_type !== 'attachment') {
            throw new InvalidArgumentException('Attachment not found.');
        }

        $result = \wp_update_post(
            [
                'ID' => $attachmentId,
                'post_content' => \sanitize_textarea_field($story),
                'post_title' => \sanitize_text_field($shortTitle),
            ],
            true
        );

        if ($result instanceof \WP_Error) {
            throw new RuntimeException($result->get_error_message());
        }

        return [
            'attachment_id' => $attachmentId,
            'story' => (string) \get_post_field('post_content', $attachmentId),
            'short_title' => (string) \get_post_field('post_title', $attachmentId),
        ];
    }

    /**
     * @param array<string, mixed> $json
     */
    private function extractOutputText(array $json): string
    {
        $text = isset($json['output_text']) ? trim((string) $json['output_text']) : '';

        if ($text !== '') {
            return $text;
        }

        if (! isset($json['output']) || ! is_array($json['output'])) {
            return '';
        }

        foreach ($json['output'] as $item) {
            if (! is_array($item) || ! isset($item['content']) || ! is_array($item['content'])) {
                continue;
            }

            foreach ($item['content'] as $content) {
                if (! is_array($content) || ($content['type'] ?? '') !== 'output_text') {
                    continue;
                }

                $candidate = isset($content['text']) ? trim((string) $content['text']) : '';

                if ($candidate !== '') {
                    return $candidate;
                }
            }
        }

        return '';
    }

    /**
     * @return array{short_title:string,story:string}
     */
    private function parseGeneratedJsonPayload(string $outputText): array
    {
        $payload = trim($outputText);

        if (str_starts_with($payload, '```')) {
            $payload = preg_replace('/^```[a-zA-Z0-9]*\s*/', '', $payload) ?? $payload;
            $payload = preg_replace('/\s*```$/', '', $payload) ?? $payload;
            $payload = trim($payload);
        }

        $decoded = \json_decode($payload, true);

        if (! is_array($decoded)) {
            throw new RuntimeException('OpenAI returned invalid JSON payload.');
        }

        return [
            'short_title' => trim((string) ($decoded['short_title'] ?? '')),
            'story' => trim((string) ($decoded['story'] ?? '')),
        ];
    }

    private function wordCount(string $value): int
    {
        $parts = preg_split('/\s+/', trim($value)) ?: [];
        $parts = array_values(array_filter($parts, static fn (string $item): bool => $item !== ''));

        return count($parts);
    }
}
