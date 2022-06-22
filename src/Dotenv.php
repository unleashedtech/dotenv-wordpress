<?php

namespace UnleashedTech\Drupal\Dotenv;

use Symfony\Component\Dotenv\Dotenv as SymfonyDotenv;

/**
 * A class to help configure Drupal based on ENV file variables.
 */
class Dotenv
{

    /**
     * @var string Optional. The name of the database to use.
     */
    private string $databaseName;

    /**
     * @var string The machine name of the Drupal app being configured.
     *
     * e.g. "earth"
     */
    private string $appName = 'default';

    /**
     * @var string The machine name of the Drupal app site being configured.
     *
     * e.g. "antarctica", which is a site of the "earth" Drupal multi-site app.
     */
    private string $siteName = 'default';

    /**
     * @var bool Whether the default site is allowed in a multi-site configuration.
     */
    private bool $isMultiSiteDefaultSiteAllowed = FALSE;

    /**
     * The class constructor.
     */
    public function __construct()
    {
        // Load data from ENV file(s) if APP_ENV is not defined.
        if (! isset($_SERVER['APP_ENV'])) {
            $root = $this->getProjectPath();
            $dotenv = new SymfonyDotenv();
            if (file_exists($root . '/.env') || file_exists($root . '/.env.dist')) {
                $dotenv->loadEnv($root . '/.env');
            } elseif (file_exists($root . '/.env.dev')) {
                $_SERVER['APP_ENV'] = 'dev';
                $dotenv->load($root . '/.env.dev');
            }
        }
    }

    /**
     * Gets the name of the App.
     *
     * @return string
     *   The name of the App.
     */
    public function getAppName(): string
    {
        return $this->appName;
    }

    /**
     * Sets the name of the App.
     */
    public function setAppName(string $appName): string
    {
        return $this->appName = $appName;
    }

    /**
     * Gets the name of the site.
     *
     * @return string
     *   The name of the site.
     */
    public function getSiteName(): string
    {
        return $this->siteName;
    }

    /**
     * Sets the name the site.
     * @param string $siteName
     *   The name the site.
     */
    public function setSiteName(string $siteName): string
    {
        return $this->siteName = $siteName;
    }

    /**
     * Gets the environment name.
     *
     * @return string
     *   The environment name.
     */
    public function getEnvironmentName(): string
    {
        return strtolower($_SERVER['APP_ENV']);
    }

    public function setConfig(): void {
        // Configure WordPress.
        $domain = current($this->getDomains());
        $home = 'https://' . $domain;
        define('WP_ENV', $this->getEnvironmentName());
        define('WP_HOME', $home);
        define('WP_SITEURL', $this->get('admin') ?? $home . '/wp');
        define('DOMAIN_CURRENT_SITE', $domain);
        define('DISABLE_WP_CRON', ! empty($this->get('cron')));

        // Configure the database connection.
        $db_url = parse_url($this->get('database_url'));
        define('DB_NAME', $this->getDatabaseName());
        define('DB_USER', $db_url['user'] ?? $this->get('database_user'));
        define('DB_PASSWORD', $db_url['pass'] ?? $this->get('database_password'));
        define('DB_HOST', $db_url['host']);
        define('DB_CHARSET', $this->getDatabaseCharacterSet());
        define('DB_COLLATE', $this->getDatabaseCollation());
    }

    public function getDatabaseName(): string
    {
        if (isset($this->databaseName)) {
            return $this->databaseName;
        }
        $result = parse_url($this->get('database_url'), PHP_URL_PATH);
        if (NULL === $result || trim($result) === '/' || trim($result) === '') {
            // Multi-site configuration detected. Try to use the DATABASE_NAME variable.
            $result = $this->get('database_name');
            if (! $result) {
                // Fall back to using the site name.
                $result = $this->getSiteName();
                if ($result === 'default' && !$this->isMultiSiteDefaultSiteAllowed()) {
                    if (PHP_SAPI === 'cli') {
                        throw new \DomainException('The "default" site in this multi-site install is not allowed. Please run something like `drush -l {{site}}` instead.');
                    }

                    \header('HTTP/1.1 401 Unauthorized');
                    die('Unauthorized');
                }
            }
        } else {
            $result = substr($result, 1);
        }
        if (NULL === $result || preg_replace('/[^a-z0-9_]/', '', $result) === '') {
            throw new \UnexpectedValueException('Database name could not be computed.');
        }
        return $result;
    }

    public function setDatabaseName(string $database): void
    {
        $this->databaseName = $database;
    }

    public function isMultiSite(): bool
    {
        return count($this->getSites()) > 1;
    }

    public function isMultiSiteDefaultSiteAllowed(): bool
    {
        return $this->isMultiSiteDefaultSiteAllowed;
    }

    public function setMultiSiteDefaultSiteAllowed(bool $allowed = TRUE): void
    {
        $this->isMultiSiteDefaultSiteAllowed = $allowed;
    }

    /**
     * Gets the value for the given Environment Variable key.
     * This method will first try to return the value of `{{ app }}__{{ site }}__{{ var }}`.
     * If that variable is not defined, it will try to return the value of `{{ var }}`.
     *
     * @param $var
     * @return string|null
     */
    public function get($var): ?string {
        $var = strtoupper($var);
        $namespacedVar = strtoupper($this->getAppName() . '__' . $this->getSiteName()) . '__' . $var;
        if (isset($_SERVER[$namespacedVar])) {
            return $_SERVER[$namespacedVar];
        }
        if (isset($_SERVER[$var])) {
            return $_SERVER[$var];
        }
        return NULL;
    }

    /**
     * Gets the domains for this environment.
     *
     * @return array
     *   The domains for this environment.
     */
    public function getDomains(): array {
        return \explode(',', $this->get('domains') ?? 'default.example');
    }

    /**
     * Gets the Drupal-multi-site $sites array, based on environment variables.
     *
     * @return array
     *   The Drupal-multi-site $sites array, based on environment variables.
     */
    public function getSites(): array
    {
        $domains   = $this->getDomains();
        $siteNames = \explode(',', $this->get('sites') ?? 'default');
        $sites     = [];
        foreach ($siteNames as $siteName) {
            foreach ($domains as $domain) {
                $sites[$siteName . '.' . $domain] = $siteName;
            }
        }

        return $sites;
    }

    public function getProjectPath(): string
    {
        return dirname('.', 1);
    }

    private function getDatabaseCharacterSet(): string
    {
        return $this->get('database_charset') ?? 'utf8';
    }

    private function getDatabaseCollation(): string
    {
        return $this->get('database_collation') ?? 'utf8_general_ci';
    }

}
