# SimpleSAMLphp Expiry check module

![Build Status](https://github.com/simplesamlphp/simplesamlphp-module-expirycheck/workflows/CI/badge.svg?branch=master)
[![Coverage Status](https://codecov.io/gh/simplesamlphp/simplesamlphp-module-expirycheck/branch/master/graph/badge.svg)](https://codecov.io/gh/simplesamlphp/simplesamlphp-module-expirycheck)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/simplesamlphp/simplesamlphp-module-expirycheck/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/simplesamlphp/simplesamlphp-module-expirycheck/?branch=master)
[![Type Coverage](https://shepherd.dev/github/simplesamlphp/simplesamlphp-module-expirycheck/coverage.svg)](https://shepherd.dev/github/simplesamlphp/simplesamlphp-module-expirycheck)
[![Psalm Level](https://shepherd.dev/github/simplesamlphp/simplesamlphp-module-expirycheck/level.svg)](https://shepherd.dev/github/simplesamlphp/simplesamlphp-module-expirycheck)

## Install

Install with composer

```bash
vendor/bin/composer require simplesamlphp/simplesamlphp-module-expirycheck
```

## Configuration

Next thing you need to do is to enable the module:

in `config.php`, search for the `module.enable` key and set `expirycheck` to true:

```php
    'module.enable' => [ 'expirycheck' => true, … ],
```
