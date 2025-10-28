<?php

namespace App\Http\Middleware;

use App\Exceptions\EscapeCharacterException;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckEscapeCharacters
{
    /**
     * Common escape characters to check for.
     */
    private const ESCAPE_CHARACTERS = [
        '\\',   
        '\n',   
        '\r',   
        '\t',   
        '\0',   
        '\x1b', 
        '\b',   
        '\f',   
        '\v',   
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get all input data (including JSON, form data, query params, etc.)
        $allInput = $request->all();

        // Check all inputs recursively
        $this->checkForEscapeCharacters($allInput);

        return $next($request);
    }

    /**
     * Recursively check input data for escape characters.
     *
     * @param mixed $data The data to check
     * @param string $path The current path in the data structure
     * @throws EscapeCharacterException
     */
    private function checkForEscapeCharacters($data, string $path = ''): void
    {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $currentPath = $path ? "{$path}.{$key}" : $key;
                $this->checkForEscapeCharacters($value, $currentPath);
            }
        } elseif (is_string($data)) {
            $this->validateString($data, $path);
        }
    }

    /**
     * Validate a string for escape characters.
     *
     * @param string $value The value to validate
     * @param string $field The field name
     * @throws EscapeCharacterException
     */
    private function validateString(string $value, string $field): void
    {
        // Check for actual escape character sequences
        if ($this->containsEscapeCharacters($value)) {
            throw new EscapeCharacterException($field, $value);
        }

        // Check for literal escape sequence strings (e.g., "\n", "\t")
        if ($this->containsLiteralEscapeSequences($value)) {
            throw new EscapeCharacterException($field, $value);
        }
    }

    /**
     * Check if a string contains actual escape characters.
     *
     * @param string $value
     * @return bool
     */
    private function containsEscapeCharacters(string $value): bool
    {
        // Check for backslash (most common escape character)
        if (strpos($value, '\\') !== false) {
            return true;
        }

        // Check for control characters (ASCII 0-31 except normal space, tab, newline, carriage return in legitimate contexts)
        // These are often used in escape sequences
        if (preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $value)) {
            return true;
        }

        return false;
    }

    /**
     * Check if a string contains literal escape sequence strings.
     *
     * @param string $value
     * @return bool
     */
    private function containsLiteralEscapeSequences(string $value): bool
    {
        // Check for common literal escape sequences like "\n", "\t", "\r", etc.
        $literalEscapePatterns = [
            '/\\\\n/',   // Literal \n
            '/\\\\r/',   // Literal \r
            '/\\\\t/',   // Literal \t
            '/\\\\0/',   // Literal \0
            '/\\\\x[0-9a-fA-F]{2}/', // Hex escape sequences like \x1b
            '/\\\\u[0-9a-fA-F]{4}/', // Unicode escape sequences like \u0000
        ];

        foreach ($literalEscapePatterns as $pattern) {
            if (preg_match($pattern, $value)) {
                return true;
            }
        }

        return false;
    }
}

