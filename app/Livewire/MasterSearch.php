<?php

namespace App\Livewire;

use App\Support\SearchRegistry;
use Livewire\Component;

class MasterSearch extends Component
{
    public string $term = '';

    public function clear(): void
    {
        $this->term = '';
    }

    public function render()
    {
        $term = trim($this->term);

        $results = $term === ''
            ? []
            : app(SearchRegistry::class)->search($term, perSource: 5);

        $totalCount = array_sum(array_column($results, 'count'));

        return view('livewire.master-search', [
            'results' => $results,
            'totalCount' => $totalCount,
        ]);
    }
}
