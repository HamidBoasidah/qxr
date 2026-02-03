<?php

namespace App\Http\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

trait CanFilter
{
    /**
     * Apply filters to the query based on request parameters
     */
    protected function applyFilters(Builder $query, Request $request, array $searchableFields = [], array $foreignKeyFilters = []): Builder
    {
        // Apply role-based filtering
        $query = $this->applyRoleBasedFilter($query, $request);
        
        // Apply text search
        $query = $this->applyTextSearch($query, $request, $searchableFields);
        
        // Apply foreign key filters
        $query = $this->applyForeignKeyFilters($query, $request, $foreignKeyFilters);
        
        // Apply date range filter
        $query = $this->applyDateFilter($query, $request);
        
        return $query;
    }
    
    /**
     * Apply role-based filtering (employees see only active records, admins see all)
     */
    protected function applyRoleBasedFilter(Builder $query, Request $request): Builder
    {
        $user = $request->user();
        
        // If no authenticated user, return query as is
        if (!$user) {
            return $query;
        }
        
        // If user is employee, show only active records
        if ($user->type === 'employee') {
            $query->where('is_active', true);
        }
        
        // If user is admin, show all records (no additional filter needed)
        
        return $query;
    }
    
    /**
     * Apply text search on specified fields
     */
    protected function applyTextSearch(Builder $query, Request $request, array $searchableFields): Builder
    {
        $search = $request->get('search');
        
        if ($search && !empty($searchableFields)) {
            $query->where(function ($q) use ($search, $searchableFields) {
                foreach ($searchableFields as $field) {
                    $q->orWhere($field, 'like', "%{$search}%");
                }
            });
        }
        
        return $query;
    }
    
    /**
     * Apply foreign key filters
     */
    protected function applyForeignKeyFilters(Builder $query, Request $request, array $foreignKeyFilters): Builder
    {
        foreach ($foreignKeyFilters as $param => $field) {
            $value = $request->get($param);
            if (!is_null($value)) {
                // تحويل النصوص 'true'/'false' إلى 1/0
                if ($value === 'true' || $value === 'false') {
                    $value = filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
                }
                // تحويل النصوص '0'/'1' إلى أرقام
                if ($value === '0' || $value === '1') {
                    $value = intval($value);
                }
                $query->where($field, $value);
            }
        }
        
        return $query;
    }
    
    /**
     * Apply date range filter
     */
    protected function applyDateFilter(Builder $query, Request $request, string $dateField = 'date'): Builder
    {
        $fromDate = $request->get('from');
        $toDate = $request->get('to');

        if ($fromDate) {
            $query->whereDate($dateField, '>=', $fromDate);
        }

        if ($toDate) {
            $query->whereDate($dateField, '<=', $toDate);
        }

        return $query;
    }
    
    /**
     * Get searchable fields for a model
     */
    protected function getSearchableFields(): array
    {
        return [];
    }
    
    /**
     * Get foreign key filters for a model
     */
    protected function getForeignKeyFilters(): array
    {
        return [
            'medical_facility_category_id' => 'medical_facility_category_id',
            'ownership_id' => 'facility_ownership_id',
            'specialty_id' => 'specialty_id',
            'governorate_id' => 'governorate_id',
            'district_id' => 'district_id',
            'area_id' => 'area_id',
            'is_general' => 'is_general',
            'is_active' => 'is_active',
            'parent_id' => 'parent_id',
            'medical_facility_id' => 'medical_facility_id',
        ];
    }
}