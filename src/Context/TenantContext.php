<?php

declare(strict_types=1);

namespace Flexgrid\Modules\AdminCore\Context;

use Flexgrid\Modules\AdminCore\ValueObject\TenantId;

final class TenantContext
{
    /** @var TenantId */
    private $tenantId;

    public function __construct(TenantId $tenantId)
    {
        $this->tenantId = $tenantId;
    }

    public function getTenantId(): TenantId
    {
        return $this->tenantId;
    }
}
