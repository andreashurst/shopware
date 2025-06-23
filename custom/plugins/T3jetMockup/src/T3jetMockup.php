<?php declare(strict_types=1);

namespace T3jet\MockupPlugin;

use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;

class T3jetMockup extends Plugin
{
    public function install(InstallContext $installContext): void
    {
        // Installation logic here
    }

    public function uninstall(UninstallContext $uninstallContext): void
    {
        // Uninstall logic here
    }
}