<?php

declare(strict_types=1);

namespace Flexgrid\Modules\AdminCore\Repository;

use Flexgrid\Modules\AdminCore\Entity\NumberSequenceRecord;
use Repository\Repository;

/**
 * Autowire-repository voor metadata/hydration.
 * Nummerreservering loopt uitsluitend via PdoNumberSequence.
 */
final class NumberSequenceRecordRepository extends Repository
{
    public function getEntity()
    {
        return new NumberSequenceRecord();
    }
}
