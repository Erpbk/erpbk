<?php

namespace App\Repositories;

use App\Models\Accounts;
use App\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class AccountsRepository extends BaseRepository
{
    protected $fieldSearchable = [
        'account_code',
        'account_name',
        'account_type',
        'parent_account_id',
        'opening_balance'
    ];

    public function getFieldsSearchable(): array
    {
        return $this->fieldSearchable;
    }

    public function model(): string
    {
        return Accounts::class;
    }

    /**
     * Update account even when company/branch global scopes hide the row (after controller authorizes access).
     */
    public function update(array $input, $id)
    {
        $model = $this->model->newQuery()->find($id);
        if (!$model) {
            $model = $this->model->newQuery()->withoutGlobalScopes(['company', 'branch'])->find($id);
        }
        if (!$model) {
            throw (new ModelNotFoundException())->setModel($this->model(), [$id]);
        }

        $input = $this->applyCompanyIdToPayload($input, false);
        $model->fill($input);

        if ($this->modelHasCompanyId() && empty($model->company_id) && ! (bool) $model->is_fixed) {
            $companyId = $this->resolveCurrentCompanyId();
            if ($companyId !== null) {
                $model->company_id = $companyId;
            }
        }

        $model->save();

        return $model;
    }

    protected function applyCompanyIdToPayload(array $input, bool $forceWhenMissing = true): array
    {
        if (! empty($input['is_fixed'])) {
            $input['company_id'] = null;

            return $input;
        }

        return parent::applyCompanyIdToPayload($input, $forceWhenMissing);
    }
}
