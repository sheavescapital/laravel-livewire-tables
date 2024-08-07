<?php

namespace Rappasoft\LaravelLivewireTables\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Rappasoft\LaravelLivewireTables\Traits\Configuration\FilterConfiguration;
use Rappasoft\LaravelLivewireTables\Traits\Helpers\FilterHelpers;

trait WithFilters
{
    use FilterConfiguration,
        FilterHelpers;

    public bool $filtersStatus = true;

    public bool $filtersVisibilityStatus = true;

    public bool $filterPillsStatus = true;

    public bool $filterSlideDownDefaultVisible = false;

    public string $filterLayout = 'popover';

    public int $filterCount;

    protected ?Collection $filterCollection;

    public array $filterComponents = [];

    public array $appliedFilters = [];

    public array $filterGenericData = [];

    public function filters(): array
    {
        return [];
    }

    protected function queryStringWithFilters(): array
    {
        if ($this->queryStringIsEnabled() && $this->filtersAreEnabled()) {
            return [
                'appliedFilters' => ['except' => null, 'history' => false, 'keep' => false, 'as' => $this->getQueryStringAlias().'-filters'],
            ];
        }

        return [];
    }

    public function applyFilters(): Builder
    {
        if ($this->filtersAreEnabled() && $this->hasFilters() && $this->hasAppliedFiltersWithValues()) {
            foreach ($this->getFilters() as $filter) {
                foreach ($this->getAppliedFiltersWithValues() as $key => $value) {
                    if ($filter->getKey() === $key && $filter->hasFilterCallback()) {
                        // Let the filter class validate the value
                        $value = $filter->validate($value);

                        if ($value === false) {
                            continue;
                        }

                        $this->callHook('filterApplying', ['filter' => $filter->getKey(), 'value' => $value]);
                        $this->callTraitHook('filterApplying', ['filter' => $filter->getKey(), 'value' => $value]);

                        ($filter->getFilterCallback())($this->getBuilder(), $value);
                    }
                }
            }
        }

        return $this->getBuilder();
    }

    public function updatedFilterComponents(string|array|null $value, string $filterName): void
    {
        $this->resetComputedPage();

        // Clear bulk actions on filter - if enabled
        if ($this->getClearSelectedOnFilter()) {
            $this->clearSelected();
            $this->setSelectAllDisabled();
        }

        // Clear filters on empty value
        $filter = $this->getFilterByKey($filterName);

        if ($filter && $filter->isEmpty($value)) {
            $this->callHook('filterRemoved', ['filter' => $filter->getKey()]);
            $this->callTraitHook('filterRemoved', ['filter' => $filter->getKey()]);

            $this->resetFilter($filterName);
        } elseif ($filter) {
            $this->callHook('filterUpdated', ['filter' => $filter->getKey(), 'value' => $value]);
            $this->callTraitHook('filterUpdated', ['filter' => $filter->getKey(), 'value' => $value]);

        }
    }
}
