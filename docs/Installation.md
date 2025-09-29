[Home](../README.md) > Features

# Installation

## Set environment variables in .env / .env.local

### Required:

* `APP_DATABASE_URL`
  - Specifies database connection details as per [symfony documentation](https://symfony.com/doc/current/doctrine.html#configuring-the-database)
  - MySQL 8.x has been the database of choice, but others should also work (e.g. Sqlite 3.x is used in the test suite)


* `APP_ADMIN_HOSTNAME`
* `APP_FRONTEND_HOSTNAME`
  - Admin and frontend hostnames (without protocol) - e.g. `admin.dft-ldap.localhost`

* `APP_NOTIFY_API_KEY` (Needed for production deployments)
  - GOV UK Notify to allow app to send login link emails to users

* `APP_SECRET`
  - Symfony secret; used in various places throughout the app (e.g. rate limiter, remember me, login links) 

### Optional

* `APP_FEATURES`
  - Set to `dev-auto-login` to enable auto-login when in the dev environment


* `APP_ENV_LABEL_FRONTEND` 
* `APP_ENV_LABEL_COLOUR`
* `APP_ENV_LABEL_BACKGROUND`
  - If set, these toggle a bar at the top of the site, so that it can be visibly flagged as a dev/test site, for example: 
    ```dotenv
    APP_ENV_LABEL_FRONTEND="Local DEV site"
    APP_ENV_LABEL_COLOUR="#ffffff"
    APP_ENV_LABEL_BACKGROUND="#ff0080"
    ```
* `APP_SMARTLOOK_API_KEY`
  - Used to set the Smartlook API Key. If set, enables Smartlook session recording (used for user-testing purposes)

## PHP setup

* Set environment variables as above
* Have PHP 8.3 installed
* Have [composer 2.x](https://getcomposer.org/download/) installed
* `composer install`
* `bin/console cache:clear`

## Javascript setup

* Requires node: tested working with v22.x (recommend installing with nvm)
* Requires yarn: tested working with v4.x  (if corepack enabled, this will get auto-installed as defined in package.json)
* `yarn install` / `yarn build` (or `yarn watch` for dev-mode auto-compilation upon change)
