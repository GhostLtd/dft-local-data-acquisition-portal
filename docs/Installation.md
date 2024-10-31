[Home](../README.md) > Features

# Installation

### Set environment variables in .env

#### Required:
* `DATABASE_PASSWORD`
* `DATABASE_SERVER_VERSION`

#### Optional:
* `DATABASE_DRIVER` (defaults to `pdo_pgsql`)
* `DATABASE_USER` (defaults to `ldap`)
* `DATABASE_NAME` (defaults to `ldap`)
* `DATABASE_HOST` (defaults to `127.0.0.1`)

#### Notes:
* Separate configuration options are used here instead of a single combined url-style DSN, as:
  * When using a unix_socket to connect with Postgresql, the socket needs to be passed in the "dbal.host" field.
  * The url-style DSN does not support 'path' characters in the host part of the URL.
  * See [related discussion](https://github.com/doctrine/dbal/issues/3624#issuecomment-558843253) on Github for more information.