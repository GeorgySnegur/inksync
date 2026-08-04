<?php

require_once __DIR__ . '/../vendor/autoload.php';

// build_params() (backend/prompt.php) reads this constant at call time.
// index.php normally defines it after requiring prompt.php -- define a
// stable stand-in here once so every test that touches prompt.php doesn't
// have to repeat it.
if (!defined('REPLICATE_MODEL')) {
    define('REPLICATE_MODEL', 'test/model-version-for-tests');
}
