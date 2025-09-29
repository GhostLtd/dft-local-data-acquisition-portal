[Home](../README.md) > Features

# Deployment

## Background

### Repositories

* There are three environments: dev, test, and prod
* There are also three Cloud Source Repositories, each containing a deployment branch:
  - [ldap-dev](https://source.cloud.google.com/dft-loct-ldap-dev), [ldap-test](https://source.cloud.google.com/dft-loct-ldap-test), [ldap-prod](https://source.cloud.google.com/dft-loct-ldap-prod)
* Deploying to these branches will initiate a deployment to the corresponding environment.

### Cloud build

The configuration used to power the cloud build CD pipeline can be found in:

* cloudbuild.yaml
* config/gcloud-build

## Basic deployment procedure

* If deploying to prod, set maintenance warning beforehand via admin
* Start cloud-sql-proxy after setting the relevant project:
  ```sh
  gcloud config set project dft-loct-ldap-dev  # (for dev/test - both databases are in the same RDBMS)
  gcloud config set project dft-loct-ldap-prod # (for prod)
  cloud_sql_proxy -dir=/cloudsql
  ```
* Add `APP_SECRET` / `APP_DATABASE_URL` for relevant environment to your `.env.local`
* Set maintenance mode on environment by running `bin/console ldap:maintenance:lock -f`, and check that the site shows that it is down for a schedule maintenance.  
  (N.B. This command can also be used to whitelist specific IPs by using `-w=<IP ADDRESS>`)
* Merge master into the relevant repo's branch and push
* Automated deployment will then proceed via GCP Cloud Build, where it can be monitored
* When complete, deploy any migrations
* Then check site is fine - you should be able to do this if you've whitelisted your IP
* Then deactivate maintenance mode (`bin/console ldap:maintenance:unlock`)
* Remove `APP_SECRET` / `APP_DATABASE_URL` from your `.env.local`
* Close cloud_sql_proxy

### Notes:

* Maintenance mode is mandatory - the CI / cloud build script will fail if not enabled.
* If there are no migrations, maintenance mode could technically be disabled after the CI check has passed.
* When deploying to prod, it would be sensible to:
  - Copy the database from prod -> dev
  - Deploy against dev and check that everything works
  - Take backups of the prod database after enabling maintenance mode, before and after deployment
