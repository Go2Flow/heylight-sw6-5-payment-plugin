# heylight-sw6-payment-plugin

## Tests

A minimal PHPUnit suite covers pure-logic parts of the plugin (status
mapping, API response validation) without booting a Shopware kernel. It
uses its own standalone dev dependencies under `tests/vendor` (see
`tests/composer.json`), independent of the plugin's own `composer.json`
and of the parent Shopware project, so running it never requires
`shopware/core`/`shopware/storefront` to be installed.

Setup (once):

```sh
cd tests && composer install
```

Run the tests (from the plugin root):

```sh
php tests/vendor/bin/phpunit
```

`tests/vendor` and `.phpunit.cache` are git-ignored and are not part of the
release zip built by `pack.sh`.
