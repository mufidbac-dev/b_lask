<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class SetupApiDocumentationCommand extends Command
{
    protected $signature = 'api:setup-docs {--force : Overwrite existing files}';
    protected $description = 'Setup API documentation using laravel-request-docs';

    public function handle(): int
    {
        $this->info("🚀 Setting up API Documentation...\n");

        // Step 1: Publish config
        $this->info("📝 Step 1: Publishing request-docs config...");
        $this->call('vendor:publish', [
            '--provider' => 'Rakutentech\LaravelRequestDocs\LaravelRequestDocsServiceProvider',
            '--tag' => 'request-docs-config',
        ]);

        // Step 2: Configure request-docs
        $this->info("\n📝 Step 2: Configuring request-docs...");
        $this->configureRequestDocs();

        // Step 3: Add docs route
        $this->info("\n📝 Step 3: Adding docs route...");
        $this->addDocsRoute();

        // Step 4: Configure CORS
        $this->info("\n📝 Step 4: Configuring CORS...");
        $this->configureCors();

        // Step 5: Configure rate limiting
        $this->info("\n📝 Step 5: Configuring rate limiting...");
        $this->configureRateLimiting();

        // Step 6: Setup global error handler
        $this->info("\n📝 Step 6: Setting up global error handler...");
        $this->setupErrorHandler();

        $this->newLine();
        $this->info("✅ API Documentation setup completed!");
        $this->displayNextSteps();

        return self::SUCCESS;
    }

    protected function configureRequestDocs(): void
    {
        $configPath = config_path('request-docs.php');

        if (File::exists($configPath)) {
            $content = File::get($configPath);

            // Enable docs in all environments
            $content = preg_replace(
                "/'enabled' => env\('REQUEST_DOCS_ENABLED', false\),/",
                "'enabled' => env('REQUEST_DOCS_ENABLED', true),",
                $content
            );

            // Set title
            $content = preg_replace(
                "/'title' => 'Laravel Request Docs',/",
                "'title' => 'Backend API Documentation',",
                $content
            );

            // Set description
            $content = preg_replace(
                "/'description' => '',/",
                "'description' => 'Complete REST API documentation for Backend',",
                $content
            );

            File::put($configPath, $content);
            $this->info("  ✓ request-docs.php configured");
        }
    }

    protected function addDocsRoute(): void
    {
        $webRoutesPath = base_path('routes/web.php');
        $content = File::get($webRoutesPath);

        if (str_contains($content, 'request-docs')) {
            $this->warn("  ⚠ Docs route already exists");
            return;
        }

        $docsRoute = "\n\n// API Documentation\nRoute::get('/docs', function () {\n    return view('request-docs::index');\n})->name('api.docs');\n";

        File::append($webRoutesPath, $docsRoute);
        $this->info("  ✓ Docs route added to routes/web.php");
    }

    protected function configureCors(): void
    {
        $corsConfigPath = config_path('cors.php');

        if (!File::exists($corsConfigPath)) {
            $this->warn("  ⚠ cors.php not found, creating...");
            $this->call('config:publish', ['name' => 'cors']);
        }

        $content = <<<'PHP'
<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie', 'docs'],

    'allowed_methods' => ['*'],

    'allowed_origins' => explode(',', env('CORS_ALLOWED_ORIGINS', 'http://localhost:3000,http://localhost:5173')),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,
];

PHP;

        File::put($corsConfigPath, $content);
        $this->info("  ✓ CORS configuration updated");
    }

    protected function configureRateLimiting(): void
    {
        $bootstrapPath = base_path('bootstrap/app.php');
        $content = File::get($bootstrapPath);

        if (str_contains($content, 'RateLimiter::for')) {
            $this->warn("  ⚠ Rate limiting already configured");
            return;
        }

        // Add rate limiter configuration
        $rateLimitConfig = <<<'PHP'

use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;

// API Rate Limiting
RateLimiter::for('api', function (Request $request) {
    return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
});

RateLimiter::for('login', function (Request $request) {
    return Limit::perMinute(5)->by($request->ip());
});

RateLimiter::for('global', function (Request $request) {
    return Limit::perMinute(1000)->by($request->ip());
});

PHP;

        // Add before return statement
        $content = str_replace(
            'return $app;',
            $rateLimitConfig . "\nreturn \$app;",
            $content
        );

        File::put($bootstrapPath, $content);
        $this->info("  ✓ Rate limiting configured");
    }

    protected function setupErrorHandler(): void
    {
        $handlerPath = app_path('Exceptions/Handler.php');

        if (!File::exists($handlerPath)) {
            $this->warn("  ⚠ Handler.php not found, creating...");
            File::ensureDirectoryExists(app_path('Exceptions'));
        }

        $content = <<<'PHP'
<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Throwable;

class Handler extends ExceptionHandler
{
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    public function render($request, Throwable $e): mixed
    {
        // API requests should return JSON
        if ($request->is('api/*') || $request->expectsJson()) {
            return $this->handleApiException($request, $e);
        }

        return parent::render($request, $e);
    }

    protected function handleApiException(Request $request, Throwable $e): JsonResponse
    {
        $status = 500;
        $message = 'Internal Server Error';
        $errors = null;

        if ($e instanceof ValidationException) {
            $status = 422;
            $message = 'Validation failed';
            $errors = $e->errors();
        } elseif ($e instanceof AuthenticationException) {
            $status = 401;
            $message = 'Unauthenticated';
        } elseif ($e instanceof AuthorizationException) {
            $status = 403;
            $message = 'Unauthorized';
        } elseif ($e instanceof NotFoundHttpException) {
            $status = 404;
            $message = 'Resource not found';
        } elseif ($e instanceof ModelNotFoundException) {
            $status = 404;
            $message = 'Model not found';
        } elseif ($e instanceof HttpException) {
            $status = $e->getStatusCode();
            $message = $e->getMessage() ?: 'HTTP Error';
        } elseif ($e instanceof \Exception) {
            $message = config('app.debug') ? $e->getMessage() : 'Server Error';
        }

        $response = [
            'message' => $message,
            'status' => $status,
        ];

        if ($errors) {
            $response['errors'] = $errors;
        }

        if (config('app.debug')) {
            $response['debug'] = [
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => collect($e->getTrace())->take(5)->toArray(),
            ];
        }

        return response()->json($response, $status);
    }

    protected function unauthenticated($request, AuthenticationException $exception): mixed
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Unauthenticated',
                'status' => 401,
            ], 401);
        }

        return redirect()->guest(route('login'));
    }
}

PHP;

        File::put($handlerPath, $content);
        $this->info("  ✓ Global error handler configured");
    }

    protected function displayNextSteps(): void
    {
        $this->newLine();
        $this->info("📋 Next Steps:");
        $this->line("  1. Generate API documentation:");
        $this->line("     php artisan request-docs:generate");
        $this->newLine();
        $this->line("  2. Access documentation:");
        $this->line("     http://localhost:8000/docs");
        $this->newLine();
        $this->line("  3. Add environment variables to .env:");
        $this->line("     REQUEST_DOCS_ENABLED=true");
        $this->line("     CORS_ALLOWED_ORIGINS=http://localhost:3000,http://localhost:5173");
        $this->newLine();
        $this->line("  4. Test rate limiting:");
        $this->line("     Make 60+ requests per minute to see throttling");
        $this->newLine();
        $this->line("  5. Test CORS:");
        $this->line("     Make API requests from configured origins");
    }
}

