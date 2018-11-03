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
     */
    public function __construct(?DatastoreClient $datastoreClient = null)
    {
        $this->datastoreClient = $datastoreClient ?? resolve(DatastoreClient::class);
        $this->kind = config('datastore_auth.kind') ?? 'users';
    }


    /**
     * Determine if the validation rule passes.
     *
     * @param  string $attribute
     * @param  mixed $value
     * @return bool
     */
    public function passes($attribute, $value)
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
    public function message()
    {
        return trans('validation.unique');
    }
}
