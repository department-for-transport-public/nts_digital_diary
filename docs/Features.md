[Home](../README.md) > Features

# Features

This document describes each the the features that can be enabled using environment vars

## Features enabled by using the `APP_FEATURES` env var
Listed as `[env var string]` / `[feature const]`. A feature is enabled if the `[env var string]` is listed in the `APP_FEATURES` env var. 

- ~~`accessibility-fixtures` / `ACCESSIBILITY_FIXTURES`~~ (deprecated)  
   Enable doctrine fixtures for Accessibility testing by DAC
- `check-letter` / `CHECK_LETTER`  
   Enable the serial number check-letter checksum test during household onboarding 
- `demo-fixtures` / `DEMO_FIXTURES`  
   Enable demo doctrine fixtures 
- `form-error-log` / `FORM_ERROR_LOG`  
   Enable recording of form validation errors to a log file
- ~~`pentest-fixtures` / `PENTEST_FIXTURES`~~ (deprecated)  
   ~~Enable doctrine fixtures for security/pentest by Pen Test Partners~~
- `reveal-invite-links` / `REVEAL_INVITE_LINKS`  
   Show account creating / email invitation links (during onboarding and on interviewer dashboard)
- `smartlook-session-recording` / `SMARTLOOK_SESSION_RECORDING`  
   Include the smartlook session recording template/code

## Features enabled by the existence of specific environment variables
Listed as `[env var]` / `[Feature const name]`. These features are enabled if the `[env var]` is defined.

- `GAE_INSTANCE` / `GAE_ENVIRONMENT`  
   Used for Google AppEngine specific config (logging, cache, etc)
