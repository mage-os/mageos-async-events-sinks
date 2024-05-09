<?php

declare(strict_types=1);

use Symplify\MonorepoBuilder\Config\MBConfig;

return static function (MBConfig $mbConfig): void {
    $mbConfig->defaultBranch('main');
    $mbConfig->packageAliasFormat('<major>.x-dev');
    $mbConfig->packageDirectories([__DIR__ . '/MageOS']);
};
