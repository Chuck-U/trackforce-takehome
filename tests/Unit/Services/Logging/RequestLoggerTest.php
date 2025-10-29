<?php

use App\Services\Logging\LogLevelResolver;
use App\Services\Logging\RequestLogger;
use App\Services\Logging\SensitiveDataSanitizer;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

beforeEach(function () {
    // Create mocks for dependencies
    $this->logLevelResolver = Mockery::mock(LogLevelResolver::class);
    $this->dataSanitizer = Mockery::mock(SensitiveDataSanitizer::class);
    
    // Create the service with mocked dependencies
    $this->requestLogger = new RequestLogger(
        $this->logLevelResolver,
        $this->dataSanitizer
    );
});

afterEach(function () {
    Mockery::close();
});

describe('logRequest', function () {
    test('logs GET request without body', function () {
        // Create a GET request
        $request = Request::create('/api/provider1/test', 'GET', [], [], [], [
            'HTTP_USER_AGENT' => 'Test Agent',
            'REMOTE_ADDR' => '127.0.0.1',
        ]);

        // Mock log level resolver
        $this->logLevelResolver
            ->shouldReceive('getRequestLogLevel')
            ->with(Mockery::type(Request::class))
            ->once()
            ->andReturn('info');

        // Mock Log facade
        Log::shouldReceive('log')
            ->once()
            ->with('info', 'API Request Received', Mockery::on(function ($context) {
                return $context['method'] === 'GET'
                    && $context['url'] === 'http://localhost/api/provider1/test'
                    && $context['ip'] === '127.0.0.1'
                    && $context['user_agent'] === 'Test Agent'
                    && $context['provider'] === 'provider1'
                    && !isset($context['body']);
            }));

        $this->requestLogger->logRequest($request);
    });

    test('logs POST request with sanitized body', function () {
        $requestData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'secret123',
        ];

        $request = Request::create('/api/provider2/test', 'POST', $requestData, [], [], [
            'HTTP_USER_AGENT' => 'Test Agent',
            'REMOTE_ADDR' => '192.168.1.1',
        ]);

        $sanitizedData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => '[REDACTED]',
        ];

        // Mock log level resolver
        $this->logLevelResolver
            ->shouldReceive('getRequestLogLevel')
            ->with(Mockery::type(Request::class))
            ->once()
            ->andReturn('info');

        // Mock data sanitizer
        $this->dataSanitizer
            ->shouldReceive('sanitize')
            ->with($requestData)
            ->once()
            ->andReturn($sanitizedData);

        // Mock Log facade
        Log::shouldReceive('log')
            ->once()
            ->with('info', 'API Request Received', Mockery::on(function ($context) use ($sanitizedData) {
                return $context['method'] === 'POST'
                    && $context['provider'] === 'provider2'
                    && $context['body'] === $sanitizedData;
            }));

        $this->requestLogger->logRequest($request);
    });

    test('logs PUT request with sanitized body', function () {
        $requestData = ['token' => 'abc123', 'data' => 'value'];
        $request = Request::create('/api/test', 'PUT', $requestData);

        $sanitizedData = ['token' => '[REDACTED]', 'data' => 'value'];

        $this->logLevelResolver
            ->shouldReceive('getRequestLogLevel')
            ->once()
            ->andReturn('info');

        $this->dataSanitizer
            ->shouldReceive('sanitize')
            ->with($requestData)
            ->once()
            ->andReturn($sanitizedData);

        Log::shouldReceive('log')
            ->once()
            ->with('info', 'API Request Received', Mockery::on(function ($context) use ($sanitizedData) {
                return $context['method'] === 'PUT'
                    && $context['body'] === $sanitizedData;
            }));

        $this->requestLogger->logRequest($request);
    });

    test('logs DELETE request with sanitized body', function () {
        $requestData = ['id' => 123];
        $request = Request::create('/api/provider1/delete', 'DELETE', $requestData);

        $this->logLevelResolver
            ->shouldReceive('getRequestLogLevel')
            ->once()
            ->andReturn('info');

        $this->dataSanitizer
            ->shouldReceive('sanitize')
            ->with($requestData)
            ->once()
            ->andReturn($requestData);

        Log::shouldReceive('log')
            ->once()
            ->with('info', 'API Request Received', Mockery::on(function ($context) {
                return $context['method'] === 'DELETE'
                    && isset($context['body']);
            }));

        $this->requestLogger->logRequest($request);
    });

    test('detects provider1 from request path', function () {
        $request = Request::create('/api/provider1/employees', 'GET');

        $this->logLevelResolver
            ->shouldReceive('getRequestLogLevel')
            ->once()
            ->andReturn('info');

        Log::shouldReceive('log')
            ->once()
            ->with('info', 'API Request Received', Mockery::on(function ($context) {
                return $context['provider'] === 'provider1';
            }));

        $this->requestLogger->logRequest($request);
    });

    test('detects provider2 from request path', function () {
        $request = Request::create('/api/some/path/provider2/resource', 'GET');

        $this->logLevelResolver
            ->shouldReceive('getRequestLogLevel')
            ->once()
            ->andReturn('info');

        Log::shouldReceive('log')
            ->once()
            ->with('info', 'API Request Received', Mockery::on(function ($context) {
                return $context['provider'] === 'provider2';
            }));

        $this->requestLogger->logRequest($request);
    });

    test('returns null provider when path does not contain provider', function () {
        $request = Request::create('/api/other/endpoint', 'GET');

        $this->logLevelResolver
            ->shouldReceive('getRequestLogLevel')
            ->once()
            ->andReturn('info');

        Log::shouldReceive('log')
            ->once()
            ->with('info', 'API Request Received', Mockery::on(function ($context) {
                return $context['provider'] === null;
            }));

        $this->requestLogger->logRequest($request);
    });

    test('uses correct log level from resolver', function () {
        $request = Request::create('/api/health', 'GET');

        $this->logLevelResolver
            ->shouldReceive('getRequestLogLevel')
            ->with(Mockery::type(Request::class))
            ->once()
            ->andReturn('debug');

        Log::shouldReceive('log')
            ->once()
            ->with('debug', 'API Request Received', Mockery::any());

        $this->requestLogger->logRequest($request);
    });
});

describe('logResponse', function () {
    test('logs successful response (200)', function () {
        $request = Request::create('/api/provider1/test', 'GET');
        $response = new Response('{"success": true}', 200);
        $executionTime = 150.5;

        $this->logLevelResolver
            ->shouldReceive('getResponseLogLevel')
            ->with(200)
            ->once()
            ->andReturn('info');

        Log::shouldReceive('log')
            ->once()
            ->with('info', 'API Request Successful', Mockery::on(function ($context) use ($executionTime) {
                return $context['method'] === 'GET'
                    && $context['status_code'] === 200
                    && $context['execution_time_ms'] === $executionTime
                    && $context['provider'] === 'provider1'
                    && !isset($context['response']);
            }));

        $this->requestLogger->logResponse($request, $response, $executionTime);
    });

    test('logs redirect response (301)', function () {
        $request = Request::create('/api/test', 'GET');
        $response = new Response('', 301);
        $executionTime = 50.0;

        $this->logLevelResolver
            ->shouldReceive('getResponseLogLevel')
            ->with(301)
            ->once()
            ->andReturn('info');

        Log::shouldReceive('log')
            ->once()
            ->with('info', 'API Request Redirected', Mockery::on(function ($context) {
                return $context['status_code'] === 301;
            }));

        $this->requestLogger->logResponse($request, $response, $executionTime);
    });

    test('logs client error response (400) without JSON body', function () {
        $request = Request::create('/api/provider2/test', 'POST');
        $response = new Response('Invalid request', 400);
        $executionTime = 25.3;

        $this->logLevelResolver
            ->shouldReceive('getResponseLogLevel')
            ->with(400)
            ->once()
            ->andReturn('warning');

        Log::shouldReceive('log')
            ->once()
            ->with('warning', 'API Request Failed - Client Error', Mockery::on(function ($context) {
                return $context['status_code'] === 400
                    && !isset($context['response']);
            }));

        $this->requestLogger->logResponse($request, $response, $executionTime);
    });

    test('logs client error response (404) with JSON body', function () {
        $request = Request::create('/api/provider1/notfound', 'GET');
        $errorBody = ['error' => 'Not Found', 'message' => 'Resource does not exist'];
        $response = new Response(json_encode($errorBody), 404);
        $executionTime = 10.0;

        $this->logLevelResolver
            ->shouldReceive('getResponseLogLevel')
            ->with(404)
            ->once()
            ->andReturn('warning');

        Log::shouldReceive('log')
            ->once()
            ->with('warning', 'API Request Failed - Client Error', Mockery::on(function ($context) use ($errorBody) {
                return $context['status_code'] === 404
                    && isset($context['response'])
                    && $context['response'] === $errorBody;
            }));

        $this->requestLogger->logResponse($request, $response, $executionTime);
    });

    test('logs server error response (500) with JSON body', function () {
        $request = Request::create('/api/test', 'POST');
        $errorBody = ['error' => 'Internal Server Error', 'code' => 'ERR001'];
        $response = new Response(json_encode($errorBody), 500);
        $executionTime = 200.7;

        $this->logLevelResolver
            ->shouldReceive('getResponseLogLevel')
            ->with(500)
            ->once()
            ->andReturn('error');

        Log::shouldReceive('log')
            ->once()
            ->with('error', 'API Request Failed - Server Error', Mockery::on(function ($context) use ($errorBody) {
                return $context['status_code'] === 500
                    && isset($context['response'])
                    && $context['response'] === $errorBody;
            }));

        $this->requestLogger->logResponse($request, $response, $executionTime);
    });

    test('does not log response body for non-JSON error responses', function () {
        $request = Request::create('/api/test', 'POST');
        $response = new Response('This is not JSON', 400);
        $executionTime = 15.0;

        $this->logLevelResolver
            ->shouldReceive('getResponseLogLevel')
            ->with(400)
            ->once()
            ->andReturn('warning');

        Log::shouldReceive('log')
            ->once()
            ->with('warning', 'API Request Failed - Client Error', Mockery::on(function ($context) {
                return !isset($context['response']);
            }));

        $this->requestLogger->logResponse($request, $response, $executionTime);
    });

    test('does not log response body for empty error responses', function () {
        $request = Request::create('/api/test', 'POST');
        $response = new Response('', 400);
        $executionTime = 12.5;

        $this->logLevelResolver
            ->shouldReceive('getResponseLogLevel')
            ->with(400)
            ->once()
            ->andReturn('warning');

        Log::shouldReceive('log')
            ->once()
            ->with('warning', 'API Request Failed - Client Error', Mockery::on(function ($context) {
                return !isset($context['response']);
            }));

        $this->requestLogger->logResponse($request, $response, $executionTime);
    });

    test('logs response with correct provider information', function () {
        $request = Request::create('/api/provider2/employees', 'GET');
        $response = new Response('', 200);
        $executionTime = 100.0;

        $this->logLevelResolver
            ->shouldReceive('getResponseLogLevel')
            ->with(200)
            ->once()
            ->andReturn('info');

        Log::shouldReceive('log')
            ->once()
            ->with('info', 'API Request Successful', Mockery::on(function ($context) {
                return $context['provider'] === 'provider2';
            }));

        $this->requestLogger->logResponse($request, $response, $executionTime);
    });

    test('uses correct log level from resolver for different status codes', function () {
        $request = Request::create('/api/test', 'GET');

        // Test 500 status
        $response500 = new Response('Error', 500);
        $this->logLevelResolver
            ->shouldReceive('getResponseLogLevel')
            ->with(500)
            ->andReturn('error');
        Log::shouldReceive('log')
            ->once()
            ->with('error', Mockery::any(), Mockery::any());
        $this->requestLogger->logResponse($request, $response500, 50.0);

        // Test 400 status
        $response400 = new Response('Error', 400);
        $this->logLevelResolver
            ->shouldReceive('getResponseLogLevel')
            ->with(400)
            ->andReturn('warning');
        Log::shouldReceive('log')
            ->once()
            ->with('warning', Mockery::any(), Mockery::any());
        $this->requestLogger->logResponse($request, $response400, 50.0);
    });


    test('handles invalid JSON in error response gracefully', function () {
        $request = Request::create('/api/test', 'POST');
        // Invalid JSON (missing closing brace)
        $response = new Response('{"error": "test"', 400);
        $executionTime = 20.0;

        $this->logLevelResolver
            ->shouldReceive('getResponseLogLevel')
            ->with(400)
            ->once()
            ->andReturn('warning');

        Log::shouldReceive('log')
            ->once()
            ->with('warning', 'API Request Failed - Client Error', Mockery::on(function ($context) {
                return !isset($context['response']);
            }));

        $this->requestLogger->logResponse($request, $response, $executionTime);
    });

    test('logs execution time correctly', function () {
        $request = Request::create('/api/test', 'GET');
        $response = new Response('', 200);
        $executionTime = 123.456;

        $this->logLevelResolver
            ->shouldReceive('getResponseLogLevel')
            ->with(200)
            ->once()
            ->andReturn('info');

        Log::shouldReceive('log')
            ->once()
            ->with('info', 'API Request Successful', Mockery::on(function ($context) use ($executionTime) {
                return $context['execution_time_ms'] === $executionTime;
            }));

        $this->requestLogger->logResponse($request, $response, $executionTime);
    });
});

