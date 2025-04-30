[Home](../README.md) > Features

# Features

This document describes each the features that can be enabled using environment vars

## Features enabled by using the `APP_FEATURES` env var
Listed as `[env var string]` / `[feature const]`. A feature is enabled if the `[env var string]` is listed in the `APP_FEATURES` env var. 

- `dev-auto-login` / `FEATURE_DEV_AUTO_LOGIN` (dev mode only)  
  Skips the sending of a login email and allows access directly after entering email address.

- `dev-mca-fixtures` / `FEATURE_DEV_MCA_FIXTURES` (dev mode only)  
  Adds dummy data when adding a new MCA

## Features enabled by the existence of specific environment variables
Listed as `[env var]` / `[Feature const name]`. These features are enabled if the `[env var]` is defined.

- `GAE_INSTANCE` / `GAE_ENVIRONMENT`  
   Used for Google AppEngine specific config (logging, cache, etc)
