<?php
declare(strict_types=1);

/**
 * Typofx Router System
 * Responsible for mapping extensionless URLs to physical PHP files.
 */
class Router {
    private string $baseDir;

    /**
     * @param string $baseDir The absolute path to the project root.
     */
    public function __construct(string $baseDir) {
        $this->baseDir = rtrim($baseDir, DIRECTORY_SEPARATOR);
    }

    /**
     * Extracts the route from the URL.
     */
    private function parseRoute(): string {
        $route = $_GET['route'] ?? '';
        
        if (empty($route)) {
            $path = (string)parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
            $script = $_SERVER['SCRIPT_NAME'] ?? '';
            $base = str_replace('/index.php', '', $script);
            $route = trim(str_replace($base, '', $path), '/');
        }
        
        return trim((string)$route, '/');
    }

    /**
     * Resolves the request to a physical file.
     * @return string|null The absolute path to the file, or null if not found.
     */
    public function resolve(): ?string
    {
        $route = $this->parseRoute();
        
        if (str_contains($route, '..') || str_contains($route, "\0") || str_contains($route, '\\')) {
            return null;
        }

        $file = $this->locateFile($route);
        if ($file === null) {
            return null;
        }

        $realBase = realpath($this->baseDir);
        $realFile = realpath($file);

        if ($realFile !== false && $realBase !== false) {
            $realBaseWithSlash = rtrim($realBase, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
            if (str_starts_with($realFile, $realBaseWithSlash) || $realFile === $realBase) {
                return $realFile;
            }
        }

        return null;
    }

    /**
     * Locates a candidate file path based on route conventions.
     * @param string $route
     * @return string|null
     */
    private function locateFile(string $route): ?string
    {
        // Handle empty route (root)
        if ($route === '') {
            return $this->baseDir . '/auth/auth.signin.php';
        }

        // Direct modular dot-syntax (e.g. 'auth.signin' -> 'auth/auth.signin.php', 'acess.list' -> 'panel/acess/acess.list.php')
        if (str_contains($route, '.') && !str_contains($route, '/')) {
            $prefix = explode('.', $route, 2)[0];
            $candidate = $this->baseDir . '/' . $prefix . '/' . $route . '.php';
            if (file_exists($candidate)) {
                return $candidate;
            }
            $nestedCandidate = $this->baseDir . '/panel/' . $prefix . '/' . $route . '.php';
            if (file_exists($nestedCandidate)) {
                return $nestedCandidate;
            }
        }

        $parts = explode('/', $route);
        $module = $parts[0];

        $modulePath = $this->baseDir . '/' . $module;

        if (is_dir($modulePath)) {
            // Try the rest of the path as a sub-path under the module
            $subPath = implode('/', array_slice($parts, 1));
            if ($subPath !== '') {
                // 1. Try direct file (e.g. panel/acess/list → panel/acess/list.php)
                $file = $modulePath . '/' . $subPath . '.php';
                if (file_exists($file)) {
                    return $file;
                }

                // 2. Try modular prefix pattern (e.g. panel/acess/list → panel/acess/acess.list.php)
                $subParts = explode('/', $subPath);
                $lastPart = array_pop($subParts);
                $subDir = implode('/', $subParts);
                
                if ($subDir !== '') {
                    // Nested: panel/acess/edit → panel/acess/ + acess.edit.php
                    $dirName = basename($this->baseDir . '/' . $module . '/' . $subDir);
                    $prefixedFile = $modulePath . '/' . $subDir . '/' . $dirName . '.' . $lastPart . '.php';
                    if (file_exists($prefixedFile)) {
                        return $prefixedFile;
                    }
                } else {
                    // Direct child: auth/signin → auth/ + auth.signin.php
                    $prefixedFile = $modulePath . '/' . $module . '.' . $lastPart . '.php';
                    if (file_exists($prefixedFile)) {
                        return $prefixedFile;
                    }
                }

                // 3. Try index.php in a subdirectory (e.g. panel/acess → panel/acess/index.php)
                $indexFile = $modulePath . '/' . $subPath . '/index.php';
                if (file_exists($indexFile)) {
                    return $indexFile;
                }
            }

            // Fallback to index.php in the module directory
            $indexFile = $modulePath . '/index.php';
            if (file_exists($indexFile)) {
                return $indexFile;
            }
        }

        // Try direct file access in base dir (e.g., /about.php)
        $directFile = $this->baseDir . '/' . $route . '.php';
        if (file_exists($directFile)) {
            return $directFile;
        }

        // Fallback: Try inside 'auth' module with prefix (e.g. signin or auth.signin → auth/auth.signin.php)
        $authFile = str_starts_with($route, 'auth.') ? $route : 'auth.' . $route;
        $authFallback = $this->baseDir . '/auth/' . $authFile . '.php';
        if (file_exists($authFallback)) {
            return $authFallback;
        }

        // Fallback: Try inside 'panel' module (e.g. acess/list -> panel/acess/acess.list.php, tasks -> panel/tasks/index.php)
        if ($module !== 'panel') {
            $panelResolved = $this->resolve('panel/' . $route);
            if ($panelResolved !== null) {
                return $panelResolved;
            }
        }

        return null;
    }
}
