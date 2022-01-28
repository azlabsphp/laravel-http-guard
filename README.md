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

Note: For caching request user in order to usethe cached data if the auth server is not available, the package provides an ArrayCacheProvider and a RedisCacheProvider.
By default the ArrayCacheProvider is used. In order to Use Redis provider, you must install the predis/predis library. You can install it by running:

> composer require predis/predis

## Usage
