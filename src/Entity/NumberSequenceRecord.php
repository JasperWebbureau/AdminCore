<?php

declare(strict_types=1);

namespace Flexgrid\Modules\AdminCore\Entity;

use Repository\RepositoryEntity;

/**
 * @FG\Entity[name=admin_core_number_sequence,repository=Flexgrid\Modules\AdminCore\Repository\NumberSequenceRecordRepository,type=Module,in_menu=false]
 * @FG\Index::tenant_sequence[columns={tenantId,sequenceKey,periodKey},unique=true]
 */
final class NumberSequenceRecord extends RepositoryEntity
{
    /** @FG\Column[type=primary] */
    protected $id;

    /** @FG\Column[type=varchar,length=64,required=true] */
    protected $tenantId;

    /** @FG\Column[type=varchar,length=64,required=true] */
    protected $sequenceKey;

    /** @FG\Column[type=varchar,length=32,required=true] */
    protected $periodKey;

    /** @FG\Column[type=bigint,required=true] */
    protected $currentValue;

    public function getId(): int
    {
        return (int)$this->id;
    }

    public function setId($value): NumberSequenceRecord
    {
        $this->id = (int)$value;
        return $this;
    }

    public function getTenantId(): string
    {
        return (string)$this->tenantId;
    }

    public function setTenantId($value): NumberSequenceRecord
    {
        $this->tenantId = (string)$value;
        return $this;
    }

    public function getSequenceKey(): string
    {
        return (string)$this->sequenceKey;
    }

    public function setSequenceKey($value): NumberSequenceRecord
    {
        $this->sequenceKey = (string)$value;
        return $this;
    }

    public function getPeriodKey(): string
    {
        return (string)$this->periodKey;
    }

    public function setPeriodKey($value): NumberSequenceRecord
    {
        $this->periodKey = (string)$value;
        return $this;
    }

    public function getCurrentValue(): int
    {
        return (int)$this->currentValue;
    }

    public function setCurrentValue($value): NumberSequenceRecord
    {
        $this->currentValue = (int)$value;
        return $this;
    }


    // --- Auto-generated getters and setters ---

    /**
     * Get the value of makeTime
     */
    public function getMakeTime()
    {
        return $this->makeTime;
    }

    /**
     * Set the value of makeTime
     *
     * @param mixed $value
     * @return $this
     */
    public function setMakeTime($value)
    {
        $this->makeTime = $value;
        return $this;
    }

}
