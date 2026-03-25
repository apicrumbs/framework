<?php

namespace ApiCrumbs\Framework;

class ManifestLoader
{

    public static function scanLocalCrumbs(): array
    {
        $found = [];
        $crumbsPath = __DIR__ . '/Crumbs';

        if (!file_exists($crumbsPath)) {
            return $found;
        }

        // 🚀 Pattern: Matches any .php file ending in 'Crumb' within any sub-directory
        $files = glob($crumbsPath . '/**/*Crumb.php');
        
        foreach ($files as $filePath) {
            $fileName = basename($filePath);
            
            // Extract Category (Parent folder name)
            $pathParts = explode(DIRECTORY_SEPARATOR, dirname($filePath));
            $fileCategory = strtolower(end($pathParts));

            $className = self::resolveCrumbsNamespace($filePath);

            if (class_exists($className)) {
                // Generate the Registry ID: e.g., "finance/suppliertotal"
                $id = $fileCategory . '/' . strtolower(str_replace('Crumb.php', '', $fileName));
                
                // Instantiate the "Specialist" for the Registry
                $found[$id] = new $className();
            }
        }

        return $found;
    }

    private static function resolveCrumbsNamespace(string $path): string 
    {
        // 1. Standardise separators for Windows/XAMPP compatibility
        $path = str_replace('\\', '/', $path);
        $root = str_replace('\\', '/', getcwd() . '/src/');

        // 2. Strip the root path and the .php extension
        $relative = str_replace([$root, '.php'], ['', ''], $path);

        // 3. Convert folder separators to Namespace backslashes
        $ns = 'ApiCrumbs\\' . str_replace('/', '\\', $relative);

        return $ns;
    }

    public static function scanLocalRecipes(): array
    {
        $found = [];
        $crumbsPath = __DIR__ . '/Recipes';

        if (!file_exists($crumbsPath)) {
            return $found;
        }

        // 🚀 Pattern: Matches any .php file ending in 'Recipe' within any sub-directory
        $files = glob($crumbsPath . '/**/*Recipe.php');
        
        foreach ($files as $filePath) {
            $fileName = basename($filePath);
            
            // Extract Category (Parent folder name)
            $pathParts = explode(DIRECTORY_SEPARATOR, dirname($filePath));
            $fileCategory = strtolower(end($pathParts));

            $className = self::resolveRecipesNamespace($filePath);

            if (class_exists($className)) {
                // Generate the Registry ID: e.g., "finance/suppliertotal"
                $id = $fileCategory . '/' . strtolower(str_replace('Recipe.php', '', $fileName));
                
                // Instantiate the "Specialist" for the Registry
                $found[$id] = new $className();
            }
        }

        return $found;
    }

    private static function resolveRecipesNamespace(string $path): string 
    {
        // 1. Standardise separators for Windows/XAMPP compatibility
        $path = str_replace('\\', '/', $path);
        $root = str_replace('\\', '/', getcwd() . '/src/');

        // 2. Strip the root path and the .php extension
        $relative = str_replace([$root, '.php'], ['', ''], $path);

        // 3. Convert folder separators to Namespace backslashes
        $ns = 'ApiCrumbs\\' . str_replace('/', '\\', $relative);

        return $ns;
    }

}