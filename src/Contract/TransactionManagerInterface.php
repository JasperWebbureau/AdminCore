<?php

declare(strict_types=1);

namespace Flexgrid\Modules\AdminCore\Contract;

interface TransactionManagerInterface
{
    /**
     * @param callable $operation
     * @return mixed
     */
    public function transactional(callable $operation);
}
