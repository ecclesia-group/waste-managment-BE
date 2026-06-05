<?php

namespace App\Services;

use Database\Seeders\DemoDataSeeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use Throwable;

class FrontendApiDocumentationService
{
    /** @var array<string, mixed> */
    private array $manifest = [];

    /** @var array<string, string> */
    private array $tokens = [];

    /** @var array<string, array<string, mixed>> */
    private array $formRequestRules = [];

    public function generate(?string $outputDir = null): array
    {
        $outputDir = $outputDir ?? base_path('docs/frontend-api');
        File::ensureDirectoryExists($outputDir);

        $this->manifest = $this->loadManifest();
        $this->formRequestRules = $this->collectFormRequestRules();

        $endpoints = $this->collectEndpoints();
        $this->authenticateGuards();

        foreach ($endpoints as &$endpoint) {
            $endpoint['request_payload'] = $this->resolveRequestPayload($endpoint);
            $endpoint['sample_response'] = $this->captureSampleResponse($endpoint);
        }
        unset($endpoint);

        $payload = [
            'generated_at' => now()->toIso8601String(),
            'base_url' => url('/api'),
            'authentication' => $this->authenticationSection(),
            'response_envelope' => $this->responseEnvelopeSection(),
            'demo_credentials' => $this->manifest,
            'models_catalog' => $this->collectModelsCatalog(),
            'models_seeded' => $this->manifest['models'] ?? [],
            'route_parameters' => $this->manifest['route_parameters'] ?? [],
            'form_requests' => $this->formRequestRules,
            'endpoints' => $endpoints,
            'endpoint_count' => count($endpoints),
        ];

        $jsonPath = $outputDir.'/api-reference.json';
        File::put($jsonPath, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        File::put(
            $outputDir.'/models-catalog.json',
            json_encode($payload['models_catalog'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );

        $markdownPath = $outputDir.'/README.md';
        File::put($markdownPath, $this->buildMarkdown($payload));

        return [
            'json' => $jsonPath,
            'markdown' => $markdownPath,
            'endpoint_count' => count($endpoints),
        ];
    }

    /** @return array<string, mixed> */
    private function loadManifest(): array
    {
        $path = storage_path('app/demo-data-manifest.json');

        if (! File::exists($path)) {
            return [
                'password' => DemoDataSeeder::DEMO_PASSWORD,
                'note' => 'Run DemoDataSeeder first to populate demo-data-manifest.json',
            ];
        }

        return json_decode(File::get($path), true) ?? [];
    }

    /** @return list<array<string, mixed>> */
    private function collectModelsCatalog(): array
    {
        $catalog = [];

        foreach (File::allFiles(app_path('Models')) as $file) {
            $relative = str_replace(['/', '.php'], ['\\', ''], $file->getRelativePathname());
            $class = 'App\\Models\\'.$relative;

            if (! class_exists($class) || ! is_subclass_of($class, Model::class)) {
                continue;
            }

            $reflection = new ReflectionClass($class);
            $instance = $reflection->newInstanceWithoutConstructor();

            $routeKey = method_exists($instance, 'getRouteKeyName')
                ? $instance->getRouteKeyName()
                : 'id';

            $catalog[] = [
                'model' => $class,
                'table' => $instance->getTable(),
                'route_key' => $routeKey,
                'fillable' => $instance->getFillable(),
                'casts' => $instance->getCasts(),
            ];
        }

        usort($catalog, fn (array $a, array $b) => $a['model'] <=> $b['model']);

        return $catalog;
    }

    /** @return array<string, array<string, mixed>> */
    private function collectFormRequestRules(): array
    {
        $rules = [];
        $requestPath = app_path('Http/Requests');

        foreach (File::allFiles($requestPath) as $file) {
            $relative = str_replace(['/', '.php'], ['\\', ''], $file->getRelativePathname());
            $class = 'App\\Http\\Requests\\'.$relative;

            if (! class_exists($class) || ! is_subclass_of($class, FormRequest::class)) {
                continue;
            }

            $reflection = new ReflectionClass($class);

            if (! $reflection->isInstantiable()) {
                continue;
            }

            try {
                /** @var FormRequest $instance */
                $instance = $reflection->newInstanceWithoutConstructor();
                $rules[$class] = [
                    'class' => $class,
                    'rules' => $instance->rules(),
                ];
            } catch (Throwable $exception) {
                $rules[$class] = [
                    'class' => $class,
                    'rules' => [],
                    'note' => 'Could not resolve rules statically: '.$exception->getMessage(),
                ];
            }
        }

        ksort($rules);

        return $rules;
    }

    /** @return list<array<string, mixed>> */
    private function collectEndpoints(): array
    {
        $endpoints = [];

        foreach (app('router')->getRoutes() as $route) {
            if (! $this->isApiRoute($route)) {
                continue;
            }

            $uri = '/'.ltrim($route->uri(), '/');
            if (! str_starts_with($uri, '/api')) {
                continue;
            }

            $action = $route->getActionName();
            if ($action === 'Closure' || ! str_contains($action, '@')) {
                if ($uri === '/api/yes') {
                    $endpoints[] = $this->endpointFromRoute($route, 'Closure', 'handle', 'public');
                }

                continue;
            }

            [$controller, $method] = explode('@', $action);
            $guard = $this->resolveGuard($route);

            $endpoints[] = $this->endpointFromRoute($route, $controller, $method, $guard);
        }

        usort($endpoints, fn (array $a, array $b) => [$a['guard'], $a['path'], $a['method']] <=> [$b['guard'], $b['path'], $b['method']]);

        return $endpoints;
    }

    private function isApiRoute(Route $route): bool
    {
        $middleware = collect($route->gatherMiddleware());

        return $middleware->contains(fn ($m) => is_string($m) && (
            str_starts_with($m, 'auth:') || in_array($m, ['api', 'App\\Http\\Middleware\\ForceJsonResponse'], true)
        )) || str_starts_with($route->uri(), 'api');
    }

    /** @return array<string, mixed> */
    private function endpointFromRoute(Route $route, string $controller, string $method, string $guard): array
    {
        $httpMethods = $route->methods();
        $httpMethod = strtolower($httpMethods[0] === 'HEAD' && count($httpMethods) > 1 ? $httpMethods[1] : $httpMethods[0]);

        $authRequired = collect($route->gatherMiddleware())
            ->contains(fn ($m) => is_string($m) && str_starts_with($m, 'auth:'));

        return [
            'method' => strtoupper($httpMethod),
            'path' => '/'.$route->uri(),
            'guard' => $guard,
            'prefix_guard' => $this->guardFromPath('/'.$route->uri()),
            'auth_required' => $authRequired,
            'controller' => $controller,
            'action' => $method,
            'middleware' => $route->gatherMiddleware(),
            'form_request' => $this->resolveFormRequestClass($controller, $method),
            'request_payload' => null,
            'sample_response' => null,
        ];
    }

    private function resolveGuard(Route $route): string
    {
        foreach ($route->gatherMiddleware() as $middleware) {
            if (is_string($middleware) && str_starts_with($middleware, 'auth:')) {
                return str_replace('auth:', '', $middleware);
            }
        }

        if (str_contains($route->uri(), 'client/')) {
            return 'public';
        }
        if (str_contains($route->uri(), 'provider/')) {
            return 'public';
        }
        if (str_contains($route->uri(), 'facility/')) {
            return 'public';
        }
        if (str_contains($route->uri(), 'district_assembly/')) {
            return 'public';
        }
        if (str_contains($route->uri(), 'admin/')) {
            return 'public';
        }

        return 'public';
    }

    private function resolveFormRequestClass(string $controller, string $method): ?string
    {
        if (! class_exists($controller) || ! method_exists($controller, $method)) {
            return null;
        }

        $reflection = new ReflectionMethod($controller, $method);

        foreach ($reflection->getParameters() as $parameter) {
            $type = $parameter->getType();

            if (! $type instanceof ReflectionNamedType || $type->isBuiltin()) {
                continue;
            }

            $class = $type->getName();

            if (is_subclass_of($class, FormRequest::class)) {
                return $class;
            }
        }

        return null;
    }

    /** @param  array<string, mixed>  $endpoint */
    private function resolveRequestPayload(array $endpoint): ?array
    {
        $formRequest = $endpoint['form_request'] ?? null;

        if ($formRequest && isset($this->formRequestRules[$formRequest])) {
            return [
                'source' => 'form_request',
                'class' => $formRequest,
                'rules' => $this->formRequestRules[$formRequest]['rules'],
            ];
        }

        $path = $endpoint['path'];
        $method = $endpoint['method'];

        if ($method === 'POST' && str_ends_with($path, '/login')) {
            return [
                'source' => 'inline',
                'body' => [
                    'emailOrPhone' => 'string (email or phone)',
                    'password' => 'string',
                ],
            ];
        }

        if ($method === 'POST' && str_contains($path, 'reset_password_notification')) {
            return [
                'source' => 'inline',
                'body' => ['emailOrPhone' => 'string (email or phone)'],
            ];
        }

        if (in_array($method, ['GET', 'DELETE'], true)) {
            return ['source' => 'none', 'body' => null];
        }

        return ['source' => 'controller_inline', 'note' => 'Validated inline in controller — see controller action'];
    }

    private function authenticateGuards(): void
    {
        $actors = [
            'client' => $this->manifest['models']['client'] ?? null,
            'provider' => $this->manifest['models']['provider'] ?? null,
            'facility' => $this->manifest['models']['facility'] ?? null,
            'district_assembly' => $this->manifest['models']['district_assembly'] ?? null,
        ];

        foreach ($actors as $guard => $actor) {
            if (! is_array($actor)) {
                continue;
            }

            $response = $this->dispatchRequest('POST', '/api/'.$guard.'/login', $actor['login_payload'] ?? []);

            $token = $response['body']['data']['data']['token'] ?? null;

            if ($token) {
                $this->tokens[$guard] = $token;
            }
        }

        $adminActor = $this->manifest['models']['admin'] ?? null;

        if (is_array($adminActor)) {
            $response = $this->dispatchRequest('POST', '/api/admin/login', $adminActor['login_payload'] ?? []);

            $token = $response['body']['data']['data']['token'] ?? null;

            if ($token) {
                $this->tokens['admin'] = $token;
            }
        }
    }

    /** @param  array<string, mixed>  $endpoint */
    private function captureSampleResponse(array $endpoint): ?array
    {
        if ($endpoint['method'] !== 'GET') {
            if ($endpoint['method'] === 'POST' && str_ends_with($endpoint['path'], '/login')) {
                $guard = $this->guardFromPath($endpoint['path']);

                return $guard ? ($this->loginSample($guard) ?? null) : null;
            }

            return null;
        }

        if ($endpoint['path'] === '/api/yes') {
            return $this->dispatchRequest('GET', $endpoint['path']);
        }

        if (! ($endpoint['auth_required'] ?? false)) {
            return ['skipped' => true, 'reason' => 'Public route — sample captured only for login POST'];
        }

        $tokenGuard = $endpoint['guard'] !== 'public'
            ? $endpoint['guard']
            : ($endpoint['prefix_guard'] ?? null);

        if (! $tokenGuard || ! isset($this->tokens[$tokenGuard])) {
            return ['skipped' => true, 'reason' => 'No token available for guard '.($tokenGuard ?? 'unknown')];
        }

        $path = $this->substituteRouteParameters($endpoint['path']);

        if (str_contains($path, '{')) {
            return ['skipped' => true, 'reason' => 'Unresolved route parameter', 'path' => $path];
        }

        return $this->dispatchRequest('GET', $path, [], $this->tokens[$tokenGuard]);
    }

    private function guardFromPath(string $path): ?string
    {
        foreach (['client', 'provider', 'facility', 'district_assembly', 'admin'] as $guard) {
            if (str_starts_with($path, '/api/'.$guard.'/')) {
                return $guard;
            }
        }

        return null;
    }

    private function loginSample(string $guard): ?array
    {
        $actor = $this->manifest['models'][$guard] ?? null;

        if (! is_array($actor)) {
            return null;
        }

        return $this->dispatchRequest('POST', '/api/'.$guard.'/login', $actor['login_payload'] ?? []);
    }

    private function substituteRouteParameters(string $path): string
    {
        $params = $this->manifest['route_parameters'] ?? [];

        return preg_replace_callback('/\{([^}]+)\}/', function (array $matches) use ($params) {
            $key = $matches[1];

            return (string) ($params[$key] ?? $matches[0]);
        }, $path) ?? $path;
    }

    /** @return array{status: int, body: mixed} */
    private function dispatchRequest(string $method, string $uri, array $data = [], ?string $token = null): array
    {
        $kernel = app(Kernel::class);

        $server = [
            'HTTP_ACCEPT' => 'application/json',
            'CONTENT_TYPE' => 'application/json',
        ];

        if ($token) {
            $server['HTTP_AUTHORIZATION'] = 'Bearer '.$token;
        }

        $content = in_array($method, ['POST', 'PUT', 'PATCH'], true) && $data !== []
            ? json_encode($data)
            : null;

        $request = Request::create($uri, $method, $data, [], [], $server, $content);

        if ($content !== null) {
            $request->headers->set('Content-Type', 'application/json');
        }

        $response = $kernel->handle($request);
        $kernel->terminate($request, $response);

        $decoded = json_decode($response->getContent(), true);

        return [
            'status' => $response->getStatusCode(),
            'body' => $decoded ?? $response->getContent(),
        ];
    }

    /** @return array<string, mixed> */
    private function authenticationSection(): array
    {
        return [
            'type' => 'Bearer token (Laravel Passport)',
            'header' => 'Authorization: Bearer {token}',
            'login_flow' => [
                '1. POST /api/{guard}/login with emailOrPhone + password',
                '2. Read token from response.data.data.token',
                '3. Send Authorization header on protected routes',
                '4. POST /api/{guard}/logout to revoke token',
            ],
            'guards' => [
                'client' => 'Residential/commercial customers',
                'provider' => 'Waste collection companies',
                'facility' => 'Transfer stations / weighbridge facilities',
                'district_assembly' => 'MMDA regulators',
                'admin' => 'Platform administrators (requires verified email)',
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function responseEnvelopeSection(): array
    {
        return [
            'shape' => [
                'data' => [
                    'status_code' => 'int|string — business status (200 success, 401 fail, 403 not found)',
                    'message' => 'Action Successful | Action Unsuccessful | Action Failed',
                    'in_error' => 'boolean',
                    'reason' => 'Human-readable detail string',
                    'data' => 'Payload array or object',
                    'point_in_time' => 'ISO 8601 timestamp',
                ],
            ],
            'example_success' => [
                'data' => [
                    'status_code' => 200,
                    'message' => 'Action Successful',
                    'in_error' => false,
                    'reason' => 'Resource retrieved successfully',
                    'data' => ['...'],
                    'point_in_time' => now()->toIso8601String(),
                ],
            ],
            'http_status_note' => 'Most apiResponse() calls return HTTP 200 even for business errors; check in_error and status_code in body.',
            'validation_errors' => 'Laravel 422 JSON validation format for failed FormRequest validation',
        ];
    }

    /** @param  array<string, mixed>  $payload */
    private function buildMarkdown(array $payload): string
    {
        $lines = [
            '# Waste Management API — Frontend Reference',
            '',
            'Generated: `'.$payload['generated_at'].'`',
            '',
            '## Base URL',
            '',
            '`'.$payload['base_url'].'`',
            '',
            '## Demo credentials',
            '',
            'Password for all demo accounts: `'.($payload['demo_credentials']['password'] ?? 'Password@123').'`',
            '',
        ];

        foreach ($payload['demo_credentials']['models'] ?? [] as $key => $actor) {
            if (! is_array($actor) || ! isset($actor['email'], $actor['login_endpoint'])) {
                continue;
            }

            $lines[] = '- **'.Str::headline($key).'**: `'.$actor['email'].'` — login `'.$actor['login_endpoint'].'`';
        }

        $lines = array_merge($lines, [
            '',
            '## Authentication',
            '',
            '```http',
            'Authorization: Bearer {token}',
            'Accept: application/json',
            'Content-Type: application/json',
            '```',
            '',
            '### Login payload',
            '',
            '```json',
            json_encode(['emailOrPhone' => 'demo.client@waste.test', 'password' => 'Password@123'], JSON_PRETTY_PRINT),
            '```',
            '',
            '### Response envelope',
            '',
            '```json',
            json_encode($payload['response_envelope']['example_success'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
            '```',
            '',
            '## Seeded models (IDs for route params)',
            '',
            '```json',
            json_encode($payload['models_seeded'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
            '```',
            '',
            '## Models catalog ('.count($payload['models_catalog']).' Eloquent models)',
            '',
            'See `models-catalog.json` for fillable fields and route keys used in API path parameters.',
            '',
            '## Endpoints ('.$payload['endpoint_count'].')',
            '',
            'Full machine-readable spec: `api-reference.json`',
            '',
        ]);

        $currentGuard = null;

        foreach ($payload['endpoints'] as $endpoint) {
            if ($endpoint['guard'] !== $currentGuard) {
                $currentGuard = $endpoint['guard'];
                $lines[] = '### Guard: `'.$currentGuard.'`';
                $lines[] = '';
            }

            $lines[] = '#### `'.$endpoint['method'].' '.$endpoint['path'].'`';
            $lines[] = '';
            $lines[] = '- Controller: `'.$endpoint['controller'].'@'.$endpoint['action'].'`';
            $lines[] = '- Auth required: `'.($endpoint['auth_required'] ? 'yes' : 'no').'`';

            if ($endpoint['form_request']) {
                $lines[] = '- Form request: `'.$endpoint['form_request'].'`';
            }

            if ($endpoint['request_payload']) {
                $lines[] = '- Request payload:';
                $lines[] = '```json';
                $lines[] = json_encode($endpoint['request_payload'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
                $lines[] = '```';
            }

            if ($endpoint['sample_response']) {
                if (isset($endpoint['sample_response']['skipped'])) {
                    $lines[] = '- Sample response: skipped ('.($endpoint['sample_response']['reason'] ?? 'n/a').')';
                } else {
                    $lines[] = '- Sample response (HTTP '.($endpoint['sample_response']['status'] ?? 'n/a').')';
                    $lines[] = '```json';
                    $body = $endpoint['sample_response']['body'] ?? $endpoint['sample_response'];
                    $lines[] = json_encode($body, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
                    $lines[] = '```';
                }
            }

            $lines[] = '';
        }

        $lines[] = '## All Form Request validation rules';
        $lines[] = '';

        foreach ($payload['form_requests'] as $class => $definition) {
            $lines[] = '### `'.$class.'`';
            $lines[] = '```json';
            $lines[] = json_encode($definition['rules'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            $lines[] = '```';
            $lines[] = '';
        }

        return implode("\n", $lines);
    }
}
