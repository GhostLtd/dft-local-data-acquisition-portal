[Home](../README.md) > Local development

# Local development

## Running tests

* `bin/phpunit`

With coverage (requires xdebug to be installed):

* `XDEBUG_MODE=coverage bin/phpunit --coverage-text`

## Running screenshots generation

### Initial JS dependency installation:

* `cd screenshots`
* `yarn install`
* `cd ..`

### Generation

* Set `APP_ENV` to `prod` in your `.env.local`
* Clear the cache `bin/console cache:clear`
* Run the screenshot tool with `bin/console ldap:screenshots`
* Files will be deposited into `screenshots/output/<date-based-name>/`

## Accessing the admin site

To access the admin site locally, your webserver should be configured to add some headers that can be picked up by PHP, mimicking IAP:

e.g. with Apache2:
```apacheconf
# public/.htaccess
RequestHeader add X-Goog-Iap-Jwt-Assertion foo
RequestHeader add X-Goog-Authenticated-User-Email mark@ghostlimited.com
```
