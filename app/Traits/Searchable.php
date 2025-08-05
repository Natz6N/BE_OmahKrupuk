<?php
namespace App\Traits;

trait Searchable
{
    /**
     * Scope untuk pencarian umum
     */
    public function scopeSearch($query, $search)
    {
        if (empty($search)) return $query;

        $searchableFields = $this->getSearchableFields();

        return $query->where(function ($q) use ($search, $searchableFields) {
            foreach ($searchableFields as $field) {
                if (strpos($field, '.') !== false) {
                    // Relasi field
                    $parts = explode('.', $field);
                    $relation = $parts[0];
                    $column = $parts[1];

                    $q->orWhereHas($relation, function ($sq) use ($column, $search) {
                        $sq->where($column, 'like', "%{$search}%");
                    });
                } else {
                    // Direct field
                    $q->orWhere($field, 'like', "%{$search}%");
                }
            }
        });
    }

    /**
     * Get searchable fields - override di model yang menggunakan trait ini
     */
    protected function getSearchableFields()
    {
        return property_exists($this, 'searchable') ? $this->searchable : ['name'];
    }
}
