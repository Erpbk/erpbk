<?php

namespace App\Repositories;

use App\Models\Company;
use Illuminate\Container\Container as Application;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

abstract class BaseRepository
{
    /**
     * @var Model
     */
    protected $model;

    /**
     * @throws \Exception
     */
    public function __construct()
    {
        $this->makeModel();
    }

    /**
     * Get searchable fields array
     */
    abstract public function getFieldsSearchable(): array;

    /**
     * Configure the Model
     */
    abstract public function model(): string;

    /**
     * Make Model instance
     *
     * @throws \Exception
     *
     * @return Model
     */
    public function makeModel()
    {
        $model = app($this->model());

        if (!$model instanceof Model) {
            throw new \Exception("Class {$this->model()} must be an instance of Illuminate\\Database\\Eloquent\\Model");
        }

        return $this->model = $model;
    }

    /**
     * Paginate records for scaffold.
     */
    public function paginate(int $perPage, array $columns = ['*']): LengthAwarePaginator
    {
        $query = $this->allQuery();

        return $query->paginate($perPage, $columns);
    }

    /**
     * Build a query for retrieving all records.
     */
    public function allQuery(array $search = [], ?int $skip = null, ?int $limit = null): Builder
    {
        $query = $this->model->newQuery();

        if (count($search)) {
            foreach ($search as $key => $value) {
                if (in_array($key, $this->getFieldsSearchable())) {
                    $query->where($key, $value);
                }
            }
        }

        if (!is_null($skip)) {
            $query->skip($skip);
        }

        if (!is_null($limit)) {
            $query->limit($limit);
        }

        return $query;
    }

    /**
     * Retrieve all records with given filter criteria
     */
    public function all(array $search = [], ?int $skip = null, ?int $limit = null, array $columns = ['*']): Collection
    {
        $query = $this->allQuery($search, $skip, $limit);

        return $query->get($columns);
    }

    /**
     * Create model record
     */
    public function create(array $input): Model
    {
        $input = $this->applyCompanyIdToPayload($input);
        $model = $this->model->newInstance($input);

        $model->save();

        return $model;
    }

    /**
     * Find model record for given id
     *
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection|Model|null
     */
    public function find($id, array $columns = ['*'])
    {
        $query = $this->model->newQuery();

        return $query->find($id, $columns);
    }

    /**
     * Update model record for given id
     *
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection|Model
     */
    public function update(array $input, $id)
    {
        $query = $this->model->newQuery();

        $model = $query->findOrFail($id);

        $input = $this->applyCompanyIdToPayload($input, false);
        $model->fill($input);

        if ($this->modelHasCompanyId() && empty($model->company_id)) {
            $companyId = $this->resolveCurrentCompanyId();
            if ($companyId !== null) {
                $model->company_id = $companyId;
            }
        }

        $model->save();

        return $model;
    }

    /**
     * @throws \Exception
     *
     * @return bool|mixed|null
     */
    public function delete($id)
    {
        $query = $this->model->newQuery();

        $model = $query->findOrFail($id);

        return $model->delete();
    }

    protected function applyCompanyIdToPayload(array $input, bool $forceWhenMissing = true): array
    {
        if (!$this->modelHasCompanyId()) {
            return $input;
        }

        $companyId = $this->resolveCurrentCompanyId();
        if ($companyId === null) {
            return $input;
        }

        if ($forceWhenMissing || empty($input['company_id'])) {
            $input['company_id'] = $companyId;
        }

        return $input;
    }

    protected function modelHasCompanyId(): bool
    {
        if (!method_exists($this->model, 'getTable')) {
            return false;
        }

        $connection = $this->model->getConnectionName() ?: config('database.default');
        return Schema::connection($connection)->hasColumn($this->model->getTable(), 'company_id');
    }

    protected function resolveCurrentCompanyId(): ?int
    {
        if (app()->runningInConsole()) {
            return null;
        }

        if (Auth::guard('admin')->check()) {
            return null;
        }

        $request = request();
        $company = $request?->attributes->get('company');
        if ($company && isset($company->id)) {
            return (int) $company->id;
        }

        $authUser = Auth::user();
        if ($authUser && !empty($authUser->company_id)) {
            return (int) $authUser->company_id;
        }

        $companySlug = $request?->route('company_slug') ?? $request?->session()->get('company_slug');
        if (empty($companySlug)) {
            return null;
        }

        $resolvedCompany = Company::query()->where('slug', (string) $companySlug)->first();
        if (!$resolvedCompany && is_numeric($companySlug)) {
            $resolvedCompany = Company::query()->find((int) $companySlug);
        }

        if (!$resolvedCompany) {
            return null;
        }

        $request?->attributes->set('company', $resolvedCompany);
        return (int) $resolvedCompany->id;
    }
}
