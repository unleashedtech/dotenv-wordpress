# Environment-Based WordPress Settings
This package extends Symfony's [symfony/dotenv](https://symfony.com/components/Dotenv)
component to allow streamlined WordPress config via [Environment Variables](https://en.wikipedia.org/wiki/Environment_variable).
Please refer to the Symfony component's documentation about how `.env` files
should be used. It is important to note that `.env` files are _ignored_ if the
`APP_ENV` var has already been set by the server. For performance purposes,
production environments should ideally rely on pre-configured environment variables,
rather than environment variable values loaded from `.env` files.

## Installation

`composer require unleashedtech/dotenv-wordpress`

### Configuring WordPress
First, you'll need to configure WordPress to use this package.

#### WordPress Config File
WordPress is typically configured via the `wp-config.php` file.
To use this package with WordPress, some code will need to be added to the top of
the relevant `wp-config.php`:

```php
<?php
use UnleashedTech\WordPress\Dotenv\Dotenv;
$dotenv = $dotenv ?? new Dotenv();
$dotenv->setConfig();
```

If you have multiple WP applications running on your webserver, you'll want to specify
the name of the WP app in each `wp-config.php` file:

```php
<?php
use UnleashedTech\WordPress\Dotenv\Dotenv;
$dotenv = $dotenv ?? new Dotenv();
$dotenv->setAppName('foo');
$dotenv->setConfig();
```

#### Installation Conclusion
That's it! WordPress will now attempt to load essential connection information from
generic Environment Variables.

### Configuring WordPress via ENV Variables
This package will provide many default setting & configuration values based on the
detected environment. Some of these values can be populated by environment variables.

Environment variables can be set in `.env` files, or via modifying server configuration.
For production environments, environment variables should ideally be defined via server
configuration.

Multi-site installations often need config that differs from the default site.
This package first checks for variables following the `{{ app }}__{{ site }}__{{ var }}`
naming convention, before falling back to the `{{ var }}` naming convention.

You can provide site-specific information via namespaced environment variables.

* [DATABASE_URL](#database_url)
* [DOMAINS](#domains)
* [PUBLIC](#public)
* [SITES](#sites)
* More configuration options coming soon!

#### DATABASE_URL
The default database connection can be configured via a [DSN](https://en.wikipedia.org/wiki/Data_source_name):

```dotenv
DATABASE_URL=driver://user:password@host:port/database
```

For example:

```dotenv
DATABASE_URL=mysql://foo:bar@localhost:3306/baz
```

For multi-site installations, do _not_ specify a database name nor credentials in
the `DATABASE_URL` variable:

```dotenv
DATABASE_URL=mysql://localhost:3306
```

For an "earth" WordPress App with a "default" site & an "antarctica" site:
```dotenv
DATABASE_URL=mysql://localhost:3306

EARTH__DEFAULT__DATABASE_USER=foo
EARTH__DEFAULT__DATABASE_PASSWORD=bar

EARTH__ANTARCTICA__DATABASE_USER=baz
EARTH__ANTARCTICA__DATABASE_PASSWORD=qux
```

The default WordPress [App Name](#app-name) is "default". For most use cases, you'll want to set the
WordPress [App Name](#app-name) in the default `settings.php` file, as shown below:
```php
<?php
use UnleashedTech\WordPress\Dotenv\Dotenv;
$dotenv = $dotenv ?? new Dotenv();
$dotenv->setAppName('earth');
// ...
```

#### DOMAINS
A CSV list of domains used by the given environment:

```dotenv
DOMAINS=foo.example,bar.example,baz.example
```

#### PUBLIC
A string allowing the enabling/disabling of basic auth functionality.

If `true`, basic will not be enabled.

If `false`, the username will be the [App Name](#app-name) & the password will
be the [Site Name](#site-name).

#### SITES
A CSV list of WordPress "sites" (e.g. "subdomains") used by the given environment:

```dotenv
SITES=foo,bar,baz,qux
```

#### Terms

##### App Name
The machine name of the WordPress App (e.g. "default" or "earth").

##### Site Name
The site name for a WordPress App Site (e.g. "default" or "antarctica").

#### Configuration Conclusion
With these few Environment Variables, you will be able to configure WordPress in a streamlined
fashion similar to the way Symfony is configured. Support for many more common WordPress features
can be expected soon. Please consider creating a Pull Request with features you would like to
this package to support.
