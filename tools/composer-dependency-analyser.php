<?php

declare(strict_types=1);

use ShipMonk\ComposerDependencyAnalyser\Config\Configuration;
use ShipMonk\ComposerDependencyAnalyser\Config\ErrorType;

return (new Configuration()
    // Composer plugin – never referenced from PHP source
    ->ignoreErrorsOnPackage(
        'simplesamlphp/composer-module-installer',
        [ErrorType::UNUSED_DEPENDENCY]
    ));
