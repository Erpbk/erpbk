<?php

namespace App\Traits;

/**
 * Trait HasActiveStatus
 * 
 * This trait provides methods to check if an entity is active.
 * It should be used by models that have a 'status' field to indicate active/inactive state.
 * 
 * Status Values:
 * - For Riders: 1 = Active, 3 = Inactive
 * - For Other Entities: true/1 = Active, false/0 = Inactive
 */
trait HasActiveStatus
{
    /**
     * Check if the entity is active
     * 
     * @return bool
     */
    public function isActive(): bool
    {
        // For Riders, status 1 means active
        if ($this->getTable() === 'riders') {
            return $this->status == 1;
        }

        // For Accounts, only check status (locked accounts are allowed)
        if ($this->getTable() === 'accounts') {
            return !empty($this->status);
        }

        // For other entities, truthy status means active
        return !empty($this->status);
    }

    /**
     * Check if the entity is inactive
     * 
     * @return bool
     */
    public function isInactive(): bool
    {
        return !$this->isActive();
    }

    /**
     * Scope a query to only include active records
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        if ($this->getTable() === 'riders') {
            return $query->where('status', 1);
        }

        if ($this->getTable() === 'accounts') {
            return $query->where('status', 1);
        }

        return $query->where('status', 1);
    }

    /**
     * Scope a query to only include inactive records
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeInactive($query)
    {
        if ($this->getTable() === 'riders') {
            return $query->where('status', '!=', 1);
        }

        if ($this->getTable() === 'accounts') {
            return $query->where('status', '!=', 1);
        }

        return $query->where('status', '!=', 1);
    }

    /**
     * Get the status label
     * 
     * @return string
     */
    public function getStatusLabel(): string
    {
        return $this->isActive() ? 'Active' : 'Inactive';
    }
}
