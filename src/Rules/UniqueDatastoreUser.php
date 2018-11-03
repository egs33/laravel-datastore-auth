<?php

namespace DatastoreAuth\Rules;

use Google\Cloud\Datastore\DatastoreClient;
use Illuminate\Contracts\Validation\Rule;

class UniqueDatastoreUser implements Rule
{

    /**
     * @var DatastoreClient
     */
    private $datastoreClient;

    /**
     * @var string
     */
    private $kind;

    /**
     * UniqueDatastoreUser constructor.
     * @param DatastoreClient $datastoreClient
     * @param null|string $kind
     */
    public function __construct(?DatastoreClient $datastoreClient = null, ?string $kind = null)
    {
        $this->datastoreClient = $datastoreClient ?? resolve(DatastoreClient::class);
        $this->kind = $kind ?? config('datastore_auth.kind') ?? 'users';
    }


    /**
     * Determine if the validation rule passes.
     *
     * @param  string $attribute
     * @param  mixed $value
     * @return bool
     */
    public function passes($attribute, $value): bool
    {
        $query = $this->datastoreClient->query()->kind($this->kind)
            ->filter($attribute, '=', $value)->keysOnly()->limit(1);
        $result = $this->datastoreClient->runQuery($query);

        return \iterator_count($result) === 0;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message(): string
    {
        return trans('validation.unique');
    }
}
