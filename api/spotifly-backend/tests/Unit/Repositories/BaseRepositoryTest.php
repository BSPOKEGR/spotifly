<?php

declare(strict_types=1);

namespace Tests\Unit\Repositories;

use App\Dto\Filters\FilterData;
use App\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\User;
use Tests\TestCase;

class BaseRepositoryTest extends TestCase
{
    use DatabaseTransactions;

    /** @var BaseRepository<User> */
    private BaseRepository $baseRepository;
    private User $user;

    public function setUp(): void
    {
        parent::setUp();

        $this->baseRepository = new BaseRepository(User::class);
    }

    public function testDeleteOneWhere(): void
    {
        $this->createSingleUser();

        $response = $this->baseRepository->deleteOneWhere(['email' => 'test@test.com']);

        $this->assertFalse($response);

        $response = $this->baseRepository->deleteOneWhere(['email' => $this->user->email]);

        $this->assertTrue($response);
    }

    public function testDeleteWhereIn(): void
    {
        $this->createUsers(['email1@test.com', 'email2@test.com', 'email3@test.com']);

        $this->assertDatabaseHas('users', ['email' => 'email1@test.com']);
        $this->assertDatabaseHas('users', ['email' => 'email2@test.com']);

        $response = $this->baseRepository->deleteWhereIn('email', ['email1@test.com', 'email2@test.com']);

        $this->assertEquals(2, $response);

        $this->assertDatabaseMissing('users', ['email' => 'email1@test.com']);
        $this->assertDatabaseMissing('users', ['email' => 'email2@test.com']);
        $this->assertDatabaseHas('users', ['email' => 'email3@test.com']);
    }

    public function testWhereKeyIn(): void
    {
        $this->createUsers(['email1@test.com', 'email2@test.com', 'email3@test.com']);

        $response = $this->baseRepository->whereKeyIn('email', ['email1@test.com', 'email3@test.com']);

        $this->assertCount(2, $response);
        $this->assertEqualsCanonicalizing(['email1@test.com', 'email3@test.com'], $response->pluck('email')->toArray());

        $this->assertNotContains('email2@test.com', $response->pluck('email')->toArray());
    }

    public function testFindWithPagination(): void
    {
        $this->createUsers(['email1@test.com', 'email2@test.com', 'email3@test.com', 'email4@test.com']);

        $filters = [
            'field' => 'email',
            'operator' => 'like',
            'value' => '%email%'
        ];
        $filters = FilterData::from($filters);

        $response = $this->baseRepository->findWithPagination([$filters], 2, 1);

        $this->assertEquals(2, $response->perPage());
        $this->assertEquals(1, $response->currentPage());
        $this->assertEquals(4, $response->total());

        $this->assertEquals(['email1@test.com', 'email2@test.com'], $response->pluck('email')->toArray());
    }

    public function testUpdateWhere(): void
    {
        $this->createSingleUser();

        $conditions = ['email' => 'lalala@gmail.com'];
        $data = ['name' => 'New Name'];

        $updatedUser = $this->baseRepository->updateWhere($conditions, $data);

        $this->assertEquals('New Name', $updatedUser->name);
        $this->assertDatabaseHas('users', ['email' => 'lalala@gmail.com', 'name' => 'New Name']);

        $this->expectException(ModelNotFoundException::class);
        $this->baseRepository->updateWhere(['email' => 'somethingRandom@test.com'], $data);
    }

    public function testUpdateForcedWhere(): void
    {
        $this->createSingleUser();

        $conditions = ['email' => 'lalala@gmail.com'];
        $data = ['name' => 'Forced Update Name'];

        $updatedUser = $this->baseRepository->updateForcedWhere($conditions, $data);

        $this->assertEquals('Forced Update Name', $updatedUser->name);
        $this->assertDatabaseHas('users', ['email' => 'lalala@gmail.com', 'name' => 'Forced Update Name']);

        $this->expectException(ModelNotFoundException::class);
        $this->baseRepository->updateForcedWhere(['email' => 'somethingRandom@test.com'], $data);
    }

    private function createSingleUser(): void
    {
        $this->user = User::factory()->create(
            [
                'email' => 'lalala@gmail.com',
            ]
        );
    }

    /**
     * @param array<string> $emails
     */
    private function createUsers(array $emails): void
    {
        foreach ($emails as $email) {
            User::factory()->create(['email' => $email]);
        }
    }
}
