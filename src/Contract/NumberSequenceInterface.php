<?php

declare(strict_types=1);

namespace Flexgrid\Modules\AdminCore\Contract;

use Flexgrid\Modules\AdminCore\ValueObject\TenantId;

interface NumberSequenceInterface
{
    /**
     * Reserveert het volgende nummer binnen de actieve databasetransactie.
     */
    public function reserve(TenantId $tenantId, string $sequenceKey, string $periodKey): int;
}
