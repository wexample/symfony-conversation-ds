<?php

namespace Wexample\SymfonyConversationDs\Traits;

use Wexample\SymfonyConversationDs\WexampleSymfonyConversationDsBundle;
use Wexample\SymfonyHelpers\Traits\BundleClassTrait;

trait SymfonyConversationDsBundleClassTrait
{
    use BundleClassTrait;

    public static function getBundleClassName(): string
    {
        return WexampleSymfonyConversationDsBundle::class;
    }
}
