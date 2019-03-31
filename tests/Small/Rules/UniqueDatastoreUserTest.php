<?php

namespace Tests\Small\Rules;

use DatastoreAuth\Rules\UniqueDatastoreUser;
use Google\Cloud\Datastore\DatastoreClient;
use Google\Cloud\Datastore\Entity;
use Google\Cloud\Datastore\Query\Query;
use Illuminate\Container\Container;
use Illuminate\Translation\Translator;
use Mockery;
use PHPUnit\Framework\TestCase;

/**
 * Class UniqueDatastoreUserTest
 * @package Tests\Small\Rules
 * @small
 */
class UniqueDatastoreUserTest extends TestCase
{

    public function tearDown()
    {
        Mockery::close();
    }

    public function testPasses()
    {
        $query = Mockery::mock(Query::class);
        $query->shouldReceive('kind')->once()->andReturnSelf();
        $query->shouldReceive('filter')->once()->withArgs(['email', '=', 'test@example.com'])->andReturnSelf();
        $query->shouldReceive('keysOnly')->once()->andReturnSelf();
        $query->shouldReceive('limit')->once()->andReturnSelf();
        $client = Mockery::mock(DatastoreClient::class);
        $client->shouldReceive('query')->once()->andReturn($query);
        $client->shouldReceive('runQuery')->once()->with($query)->andReturn(new \ArrayIterator([]));

        $rule = new UniqueDatastoreUser($client, 'users');
        $this->assertTrue($rule->passes('email', 'test@example.com'));
    }

    public function testPassesFail()
    {
        $query = Mockery::mock(Query::class);
        $query->shouldReceive('kind')->once()->andReturnSelf();
        $query->shouldReceive('filter')->once()->withArgs(['email', '=', 'test@example.com'])->andReturnSelf();
        $query->shouldReceive('keysOnly')->once()->andReturnSelf();
        $query->shouldReceive('limit')->once()->andReturnSelf();
        $client = Mockery::mock(DatastoreClient::class);
        $client->shouldReceive('query')->once()->andReturn($query);
        $client->shouldReceive('runQuery')->once()->with($query)->andReturn(new \ArrayIterator([new Entity()]));

        $rule = new UniqueDatastoreUser($client, 'users');
        $this->assertFalse($rule->passes('email', 'test@example.com'));
    }

    public function testMassage()
    {
        $client = Mockery::mock(DatastoreClient::class);
        $rule = new UniqueDatastoreUser($client, 'users');

        $translator = Mockery::mock(Translator::class);
        $translator->shouldReceive('trans')->andReturn('validation message');
        Container::getInstance()->bind('translator', function () use ($translator) {
            return $translator;
        });

        $this->assertEquals('validation message', $rule->message());
    }
}
