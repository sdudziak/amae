<?php

/**
 * Configuration overrides for WP_ENV === 'development'
 */

use Roots\WPConfig\Config;

use function Env\env;

/**
 * WordPress debugging
 */
Config::define('SAVEQUERIES', true);
Config::define('WP_DEBUG', true);
Config::define('WP_DEBUG_DISPLAY', true);
Config::define('WP_DEBUG_LOG', env('WP_DEBUG_LOG') ?? true);
Config::define('WP_DISABLE_FATAL_ERROR_HANDLER', true);
Config::define('SCRIPT_DEBUG', true);

/**
 * Cache settings for development
 */
Config::define('WP_CACHE', false);  // Disable caching in development

/**
 * Security and indexing
 */
Config::define('DISALLOW_INDEXING', true);
Config::define('DISALLOW_FILE_EDIT', false);  // Allow file editing in dev

/**
 * Docker-specific settings
 */
Config::define('DB_HOST', env('DB_HOST') ?: 'db:3306');  // Docker service name
Config::define('WP_ENVIRONMENT_TYPE', 'development');

/**
 * Error reporting for development
 */
@ini_set('display_errors', '1');
@ini_set('error_reporting', E_ALL);

ini_set('display_errors', '1');

// Enable plugin and theme updates and installation from the admin
Config::define('DISALLOW_FILE_MODS', false);
