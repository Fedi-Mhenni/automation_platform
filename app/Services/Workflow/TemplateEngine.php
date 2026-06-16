<?php

namespace App\Services\Workflow;

/**
 * Resolves {{variable}} placeholders in node payload strings.
 *
 * Delegates path lookup to ExecutionContext::resolve(), which searches flat
 * state, then $input, then nested state recursively. Arrays/objects are
 * JSON-encoded; missing paths produce an empty string (never an exception).
 * Used in EmailAction (subject, body) and LogAction (message).
 */
class TemplateEngine
{
    public function render(string $text, ExecutionContext $context): string
    {
        return preg_replace_callback('/\{\{(.*?)\}\}/', function ($matches) use ($context) {
            $path = trim($matches[1]);

            $value = $context->resolve($path);

            if (is_array($value) || is_object($value)) {
                return json_encode($value);
            }

            return (string) ($value ?? '');

        }, $text);
    }
}