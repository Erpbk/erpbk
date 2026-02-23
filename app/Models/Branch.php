<?php
// app/Models/Branch.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Branch extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'code',
        'contact',
        'address',
        'parent_branch_id',
        'branch_type',
        'is_active',
        'description',
        'created_by',
        'updated_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the type attribute.
     */
    public function getTypeAttribute(): string
    {
        return match($this->branch_type) {
            'headquarters' => 'Headquarters',
            'branch' => 'Branch',
            'warehouse' => 'Warehouse',
            'grage' => 'Garage',
            default => ucfirst($this->branch_type),
        };
    }

    /**
     * Get the parent branch.
     */
    public function parent()
    {
        return $this->belongsTo(Branch::class, 'parent_branch_id');
    }

    /**
     * Get the child branches.
     */
    public function children()
    {
        return $this->hasMany(Branch::class, 'parent_branch_id');
    }

    /**
     * Get the user who created this branch.
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this branch.
     */
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    //Dynamic Relationships (for financial tables)
    public function getUsersAttribute()
    {
        return User::whereJsonContains('branch_ids', $this->id)->get();
    }

    // public function employees()
    // {
    //     return $this->hasMany(Employee::class);
    // }

    // public function customers()
    // {
    //     return $this->hasMany(Customers::class);
    // }

    // public function suppliers()
    // {
    //     return $this->hasMany(Supplier::class);
    // }

    // Scopes

    /**
     * Scope active branches.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Methods

    /**
     * Check if branch is headquarters.
     */
    public function isHeadquarters(): bool
    {
        return $this->branch_type === 'headquarters';
    }

    /**
     * Check if branch is a garage.
     */
    public function isGarage(): bool
    {
        return $this->branch_type === 'grage';
    }

    /**
     * Check if branch is a warehouse.
     */
    public function isWarehouse(): bool
    {
        return $this->branch_type === 'warehouse';
    }

    /**
     * Check if branch is a regular branch.
     */
    public function isBranch(): bool
    {
        return $this->branch_type === 'branch';
    }

    /**
     * Get all descendants.
     */
    public function descendants()
    {
        $descendants = collect();

        foreach ($this->children as $child) {
            $descendants->push($child);
            $descendants = $descendants->merge($child->descendants());
        }

        return $descendants;
    }

    /**
     * Get branch statistics.
     */
    public function getStatistics()
    {
        return [
            //'total_users' => $this->users()->count(),
            // 'total_employees' => $this->employees()->count(),
            // 'total_customers' => $this->customers()->count(),
            // 'total_suppliers' => $this->suppliers()->count(),
            'total_children' => $this->children()->count(),
        ];
    }
}