<?php

namespace App\Exceptions;

use Exception;

class EscapeCharacterException extends Exception
{
    /**
     * Create a new exception instance.
     *
     * @param string $field The field containing escape characters
     * @param string $value The value containing escape characters
     */
    public function __construct(string $field, string $value)
    {
        parent::__construct("Escape characters detected in field '{$field}': " . substr($value, 0, 100));
    }

    /**
     * Render the exception as an HTTP response.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function render($request)
    {
        return response()->json([
            'error' => 'Invalid Input',
            'message' => $this->getMessage(),
            'status' => 400,
        ], 400);
    }
}

