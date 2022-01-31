# Drewlabs Http

Http Guard implementation that tries to get the request user from a remote server

## Installation

The recommended method to integrate the Http guard in your project is by using composer package manager. This is an unofficial package therefore it will required
developper to add the git repository to the list of vcs repository.

```json
// composer.json
{
  "require": {
    // Other dependencies
    "drewlabs/contracts": "^2.0",
    "drewlabs/core-helpers": "^2.0",
    "drewlabs/http-client": "^1.0",
    "drewlabs/support": "^2.0"
  },
  
  "repositories":[
        // Other repositories
        {
            "type": "vcs",
            "url": "git@github.com:liksoft/drewlabs-php-contracts.git"
        },
        {
            "type": "vcs",
            "url": "git@github.com:liksoft/drewlabs-php-core-helpers.git"
        },
        {
            "type": "vcs",
            "url": "git@github.com:liksoft/drewlabs-php-http-client.git"
        },
        {
            "type": "vcs",
            "url": "git@github.com:liksoft/drewlabs-php-support.git"
        },
        {
            "type": "vcs",
            "url": "git@github.com:liksoft/drewlabs-auth-laravel-passport.git"
        },
        {
            "type": "vcs",
            "url": "git@github.com:liksoft/drewlabs-php-http-guard.git"
        }
    ]
}
```

## Usage

* Service provider

By default the library is build to inject a service provider in laravel project using the extras flag of composer. By for lumen application, you must register manually the service provider class:

```php
// app/bootrap.php

// ...

/*
|--------------------------------------------------------------------------
| Register Service Providers
|--------------------------------------------------------------------------
|
| Here we will register all of the application's service providers which
| are used to bind services into the container. Service providers are
| totally optional, so you are not required to uncomment this line.
|
 */
$app->register(\Drewlabs\AuthHttpGuard\ServiceProvider::class);
```

* Configure guard for laravel/lumen project

If the previous section is completed successfully, the http-guard library will try to load basic configurations from the `auth.php` configuration's file. Add a `config/auth.php` if missing, and include the following:

```php
return [
    // Default values in the auth configuration file
    // ...

    'guards' => [
        // You add other guard drivers
        // ... 
        // Configuration of the http guard driver
        'http' => [
            'driver' => 'http'
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | User Providers
    |--------------------------------------------------------------------------
    |
    | All authentication drivers have a user provider. This defines how the
    | users are actually retrieved out of your database or other storage
    | mechanisms used by this application to persist your user's data.
    |
    | If you have multiple user tables or models you may configure multiple
    | sources which represent each model / table. These sources may then
    | be assigned to any extra authentication guards you have defined.
    |
    | Supported: "database", "eloquent"
    |
    */
    // Here in the providers key of the array, we define the basic configuration that will be loaded by the library service provider at runtime as follow:
    'providers' => [
        // ...
        'http' => [
            // Model class to be used by the package providers
            'model' => \Drewlabs\AuthHttpGuard\User::class,
            // For Http request we must define the endpoint where is located the
            // authorization server(s)
            'hosts' => [
                // When not using a cluster of servers, this default host is used
                'default' => 'http://localhost:4300',

                // Cluster of servers to be used for authentication
                'cluster' => [
                    [
                        'host' => '<HOST_URL>',
                        'primary' => true, // Boolean value indicating whether the host should be query first as primary node
                    ]
                ]
            ]
        ]
    ],
    
    // ...

];

```

Note: In the configuration file above we define the basic configuration required by the package in order to be functional in laravel project.

* Defining the http guard as the default guard

If running in an environment with multiple guard providers, like in laravel framework... Add developper should remember to add the http guard as default guard in the `auth.php` configuration file.

```php
// auth.php

// ...
return [
    // ..
    /*
    |--------------------------------------------------------------------------
    | Authentication Defaults
    |--------------------------------------------------------------------------
    |
    | This option controls the default authentication "guard" and password
    | reset options for your application. You may change these defaults
    | as required, but they're a perfect start for most applications.
    |
    */

    'defaults' => [
        'guard' => 'http',
    ],

    // ...
];
```

* Adding protection middleware

Laravel comes with a security middleware that protect routes from unauthorized user. As the laravel setup uses guard, and as the http-guard is build to support laravel security system out of box, developpers can use the default middleware that comes with laravel application if the configuration above are done properly.

But for those looking at creating their own middleware, here is an example implementation:

```php
// app/Http/Middleware.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;

class Authenticate extends BaseMiddleware
{

    /**
     * Handle an incoming request.
     *
     * @param  Request  $request
     * @param  \Closure  $next
     * @param  string[]  ...$guards
     * @return mixed
     *
     */
    public function handle($request, Closure $next, ...$guards)
    {
        $this->authenticate($guards);
        return $next($request);
    }

    /**
     * Determine if the user is ged in to any of the given guards.
     *
     * @param  array  $guards
     * @return void
     *
     * @throws AuthenticationException
     */
    protected function authenticate(array $guards)
    {
        if (empty($guards)) {
            $guards = [null];
        }
        // To authenticate users, loop through all the guards provided as parameter
        // to the middleware and check if users are authenticated
        foreach ($guards as $guard) {
            if ($this->auth->guard($guard)->check()) {
                return $this->auth->shouldUse($guard);
            }
        }
        $this->unauthenticated($guards);
    }

    /**
     * Handle an unauthenticated user.
     *
     * @param  array  $guards
     * @return void
     *
     * @throws AuthenticationException
     */
    protected function unauthenticated(array $guards)
    {
        throw new AuthenticationException('Unauthenticated.', $guards);
    }
}
```

* Registrering middleware

For laravel application middlewares must be registered in `app/Http/Kernel.php` file as follow:

```php
// app/Http/Kernel.php

class Kernel extends HttpKernel {

    /**
     * The application's route middleware.
     *
     * These middleware may be assigned to groups or used individually.
     *
     * @var array
     */
    protected $routeMiddleware = [
        // ...
        'auth' => \App\Http\Middleware\Authenticate::class,
    ];

    // ...
}
```

For lumen applications:

```php
// app/bootstrap.php

// ...

/*
|--------------------------------------------------------------------------
| Register Middleware
|--------------------------------------------------------------------------
|
| Next, we will register the middleware with the application. These can
| be global middleware that run before and after each request into a
| route or middleware that'll be assigned to some specific routes.
|
 */

$app = $app->routeMiddleware([
        // ...
        'auth' => \App\Http\Middleware\Authenticate::class,
]);

```

* Caching

Caching is an important aspect of every application, therefore the http-guard provides a mechanism of verifiying users token even if the authentication or authrrization server is down.

When the authorization server is down the http-guard library try to load users from that cache provider and verify if the issue date of the token is still valid.
In case the issue date of the token is still valid, the user is considered as authorized to access application resources, else the guard marks the user as unauthorized.

Therefore the http-guard library provides various caching systems using array storage (in-memory with file dumper), a memcached server storage and a redis storage.
Configuration for memcached server are loaded from `config/database.php` when running `laravel` or `lumen` applications. Else the configuration must be defined manually.

Note: The library uses static class properties for configuration values, therefore when manually defining configuration values, remember to centralize the operation in order to run it once per request.

The Example below, define configuration values to be used in the `AuthServiceProvider` class for `laravel` / `lumen` application

```php
// app/Providers/AuthServiceProvider.php

class AuthServiceProvider extends ServiceProvider
{
    // ...

    /**
     * Boot the authentication services for the application.
     *
     * @return void
     */
    public function boot()
    {
        // Configure the Http-Guard library to use cache
        \Drewlabs\AuthHttpGuard\HttpGuardGlobals::usesCache(true);
        // Configure the http-guard library to use PHP 'memcached' storage as default driver
        \Drewlabs\AuthHttpGuard\HttpGuardGlobals::useCacheDriver('memcached');
        // ...
    }
}
```

-- Using redis as cache driver

As specified in the previous session, the library provide a redis storage provider that depends on `predis/predis` library. In order to use the redis storage provider, developper must manually install `predis/predis`. 
If running in composer environment(recommended), you can install the library as follow:

> composer require predis/predis

Next you configure the library to use redis as cache provider as follow:

```php
    // Configure the Http-Guard library to use cache
    \Drewlabs\AuthHttpGuard\HttpGuardGlobals::usesCache(true);
    // Configure the http-guard library to use redis storage as default driver
    \Drewlabs\AuthHttpGuard\HttpGuardGlobals::useCacheDriver('redis');

    // Define the redis connection configuration as defined in predis documentation
    \Drewlabs\AuthHttpGuard\HttpGuardGlobals::forRedis([
            'scheme' => 'tcp',
            'host'   => '10.0.0.1',
            'port'   => 6379,
    ]);
    // ...
```

Note: Predis documentation can be found here [https://github.com/predis/predis]

* Auth server clustering

If your platform configuration support multiple servers for authentication, declare the list of clusters in the `config/auth.php[providers[http][hosts][cluster]]` map entry.

Note:
    When running a cluster, developper must provide a background task that call [\Drewlabs\AuthHttpGuard\AuthServerNodesChecker::setAvailableNode()] on a regular basic to update the available node in the cluster in the cache.
