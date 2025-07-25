<?php

namespace Equidna\LaravelDocbot\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use ReflectionMethod;

class GenerateRoutes extends Command
{
    protected $signature   = 'docbot:routes';
    protected $description = 'Generates API documentation and Postman collections.';

    /**
     * Execute the console command: generates API documentation and Postman collections.
     *
     * @return void
     */
    public function handle(): void
    {
        // 1. Get all routes as JSON
        Artisan::call('route:list', ['--json' => true]);
        $routes = json_decode(Artisan::output(), true);

        $this->info('Total routes: ' . count($routes));

        // 2. Prepare segments (web + custom)
        $segments = array_merge(
            [
                [
                    'key' => 'web', // No prefix for web, acts as default
                ],
            ],
            config('docbot.segments')
        );

        $segRoutes = [];
        $params    = [];

        // 3. Initialize segment arrays
        foreach ($segments as $seg) {
            $segRoutes[$seg['key']] = [];
            $params[$seg['key']] = [];
        }

        $this->info('Segments: ' . implode(', ', array_column($segments, 'key')));

        // 4. Assign routes to segments
        foreach ($routes as $route) {
            $uri = $route['uri'];
            $matched = false;
            foreach ($segments as $seg) {
                if (isset($seg['prefix']) && Str::startsWith($uri, $seg['prefix'])) {
                    $segRoutes[$seg['key']][] = $route;
                    // Collect path params
                    preg_match_all('/\{(\w+)\}/', $uri, $m);
                    foreach ($m[1] as $p) {
                        $params[$seg['key']][$p] = true;
                    }
                    $matched = true;
                    break;
                }
            }
            if (!$matched) {
                // Add to 'web' segment if not matched by any prefix
                $segRoutes['web'][] = $route;
                preg_match_all('/\{(\w+)\}/', $uri, $m);
                foreach ($m[1] as $p) {
                    $params['web'][$p] = true;
                }
            }
        }

        // 5. Generate documentation for each segment
        foreach ($segments as $seg) {
            $key = $seg['key'];
            $routesForSegment = $segRoutes[$key];
            $routeCount = count($routesForSegment);
            $this->info("Segment '$key' route count: $routeCount");
            $dir = rtrim(config('docbot.output_dir'), '/\\') . '/routes/' . $key;
            File::ensureDirectoryExists($dir);
            $tokenVar = $seg['token'] ?? 'WEB_TOKEN';

            if ($routeCount === 0) {
                continue;
            }

            // Markdown documentation
            $markdown = $this->buildMarkdown($key, $routesForSegment, $tokenVar);
            file_put_contents($dir . "/{$key}.md", $markdown);

            // Postman collection
            $collection = $this->buildPostmanCollection($key, $routesForSegment, $tokenVar, array_keys($params[$key]));
            file_put_contents($dir . "/{$key}.json", json_encode($collection, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        }

        $this->info('Docs generated');
    }


    /**
     * Build Markdown documentation for a segment.
     *
     * @param  string $segment
     * @param  array  $routes
     * @param  string $tokenVar
     * @return string
     */
    private function buildMarkdown(string $segment, array $routes, string $tokenVar): string
    {
        $out = "# $segment documentation\n\n";
        $out .= "Authenticated via Bearer {{" . $tokenVar . "}} in the `Authorization` header.\n\n";

        $groups = [];

        foreach ($routes as $r) {
            $name   = $r['name'] ?: 'misc';
            $group  = explode('.', $name)[0];

            $groups[$group][] = $r;
        }

        foreach ($groups as $group => $items) {
            $out .= "## $group\n";
            $out .= "| Method | Path | Description | Path Params |\n";
            $out .= "| ------ | ---- | ----------- | ----------- |\n";

            foreach ($items as $r) {
                $uri = $r['uri'];
                // Exclude HEAD and join with comma
                $methodsArr = array_filter(explode('|', $r['method']), fn($m) => $m !== 'HEAD');
                $methods    = implode(',', $methodsArr);
                $action     = $r['action'];

                $desc       = $this->extractDescription($action);

                $pathParams = [];

                preg_match_all('/\{(\w+)\}/', $uri, $m);

                foreach ($m[1] as $p) {
                    $pathParams[] = $p;
                }

                $out .= "| $methods | `$uri` | $desc | " . implode(',', $pathParams) . " |\n";
            }

            $out .= "\n";
        }

        return $out;
    }

    /**
     * Build Postman collection for a segment.
     *
     * @param  string $segment
     * @param  array  $routes
     * @param  string $tokenVar
     * @param  array  $pathParams
     * @return array
     */
    private function buildPostmanCollection(string $segment, array $routes, string $tokenVar, array $pathParams): array
    {
        $variables = [
            [
                'key' => 'HOST',
                'value' => 'https://api.example.com',
                'type' => 'text',
            ],
            [
                'key' => $tokenVar,
                'value' => '<insert>',
                'type' => 'secret',
            ],
        ];

        // Collect all unique path parameters from all routes and from $pathParams
        $allParams = [];

        // From $pathParams argument (legacy, may be empty)

        foreach ($pathParams as $p) {
            $allParams[$p] = true;
        }

        // From all route URIs
        foreach ($routes as $r) {
            $uri = $r['uri'];
            preg_match_all('/\{(\w+)\}/', $uri, $m);
            foreach ($m[1] as $p) {
                $allParams[$p] = true;
            }
        }
        // Add any not already present in variables
        $existingKeys = array_column($variables, 'key');

        foreach (array_keys($allParams) as $p) {
            if (!in_array($p, $existingKeys)) {
                $variables[] = [
                    'key' => $p,
                    'value' => '',
                    'type' => 'text',
                ];
            }
        }

        $collection = [
            'info' => [
                'name' => "$segment API",
                'schema' => 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json',
            ],
            'item' => [],
            'variable' => $variables,
        ];

        $itemsByPath = [];
        foreach ($routes as $r) {
            // Si el nombre está vacío, usar 'METHOD uri' como nombre legible
            $routeName = $r['name'];
            if (empty($routeName)) {
                $routeName = $r['method'] . ' ' . $r['uri'];
            }
            $segments = explode('.', $routeName);
            // Remove the root segment (e.g., 'api', 'client-api', etc.)
            if (count($segments) > 1) {
                array_shift($segments);
            } else {
                // If no segments or only one, put in misc
                $segments = ['misc'];
            }
            $itemsByPath[] = [
                'segments' => $segments,
                'route' => array_merge($r, ['_postman_name' => $routeName]),
            ];
        }

        $collection['item'] = $this->buildPostmanItems($itemsByPath, $tokenVar);
        return $collection;
    }

    /**
     * Build Postman items recursively.
     *
     * @param  array  $itemsByPath
     * @param  string $tokenVar
     * @return array
     */
    private function buildPostmanItems(array $itemsByPath, string $tokenVar): array
    {
        $grouped = [];

        foreach ($itemsByPath as $item) {
            $seg = $item['segments'];
            if (count($seg) === 1) {
                $grouped[] = $this->makeRequestItemWithCleanName($item['route'], $tokenVar, $seg[0]);
            } else {
                $first = array_shift($seg);
                $grouped[$first][] = ['segments' => $seg, 'route' => $item['route']];
            }
        }

        $result = [];

        foreach ($grouped as $key => $value) {
            if (is_array($value) && isset($value[0]['segments'])) {
                if (count($value) === 1 && count($value[0]['segments']) === 1) {
                    $result[] = $this->makeRequestItemWithCleanName($value[0]['route'], $tokenVar, $value[0]['segments'][0]);
                } else {
                    $children = $this->buildPostmanItems($value, $tokenVar);
                    if (count($children) === 1) {
                        $result[] = $children[0];
                    } else {
                        $result[] = [
                            'name' => $key,
                            'item' => $children
                        ];
                    }
                }
            } else {
                $result[] = $value;
            }
        }
        return $result;
    }

    /**
     * Make a Postman request item with a clean name.
     *
     * @param  array  $route
     * @param  string $tokenVar
     * @param  string $cleanName
     * @return array
     */
    private function makeRequestItemWithCleanName(array $route, string $tokenVar, string $cleanName): array
    {
        $item = $this->makeRequestItem($route, $tokenVar);
        // Usar el nombre calculado si existe, si no, usar el cleanName
        $item['name'] = $route['_postman_name'] ?? $cleanName;
        return $item;
    }

    /**
     * Make a Postman request item.
     *
     * @param  array  $route
     * @param  string $tokenVar
     * @return array
     */
    private function makeRequestItem(array $route, string $tokenVar): array
    {
        $method = explode('|', $route['method'])[0];
        $uri    = $route['uri'];

        // Replace {param} with {{param}} for Postman variable syntax
        $uriWithVars = preg_replace_callback(
            '/\{(\w+)\}/',
            function ($matches) {
                return '{{' . $matches[1] . '}}';
            },
            $uri
        );

        $body = null;

        return [
            'name' => $route['_postman_name'] ?? ($route['name'] ?: ($method . ' ' . $uriWithVars)),
            'event' => [
                [
                    'listen' => 'prerequest',
                    'script' => [
                        'exec' => [
                            "pm.request.headers.upsert({key: 'Authorization', value: `Bearer {{" . $tokenVar . "}}`});"
                        ]
                    ]
                ],
                [
                    'listen' => 'test',
                    'script' => [
                        'exec' => [
                            "pm.test(\"Status is 2xx\", () => pm.response.code >= 200 && pm.response.code < 300);"
                        ]
                    ]
                ],
            ],
            'request' => [
                'method' => $method,
                'header' => [],
                'url' => [
                    'raw' => "{{HOST}}/$uriWithVars",
                    'host' => ['{{HOST}}'],
                    'path' => explode('/', $uriWithVars),
                ],
                'body' => $body,
            ],
        ];
    }

    /**
     * Extract description from controller action docblock.
     *
     * @param  string $action
     * @return string
     */
    private function extractDescription(string $action): string
    {
        if (!str_contains($action, '@')) {
            return '';
        }

        [$class, $method] = explode('@', $action);

        if (!class_exists($class) || !method_exists($class, $method)) {
            return '';
        }

        $ref = new ReflectionMethod($class, $method);
        $doc = $ref->getDocComment();
        if ($doc) {
            $lines = array_map(fn($l) => trim(trim($l, "/*")), explode("\n", $doc));
            return trim($lines[1] ?? '');
        }
        return '';
    }
}
