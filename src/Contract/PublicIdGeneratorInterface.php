<?php

declare(strict_types=1);

namespace Flexgrid\Modules\AdminCore\Contract;

interface PublicIdGeneratorInterface
{
    public function generate(): string;
}
