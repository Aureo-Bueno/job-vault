<?php

namespace Tests\Unit\Application;

use App\Application\Commands\Applications\ApplyToVacancyCommand;
use App\Application\Commands\Vacancies\CreateVacancyCommand;
use App\Application\Commands\Vacancies\DeleteVacancyCommand;
use App\Application\Commands\Vacancies\UpdateVacancyCommand;
use App\Application\Exceptions\NotFoundException;
use App\Application\Exceptions\UnauthorizedException;
use App\Application\Features\Applications\ApplyToVacancyHandler;
use App\Application\Features\Applications\ListMyAppliedVacancyIdsHandler;
use App\Application\Features\Vacancies\CreateVacancyHandler;
use App\Application\Features\Vacancies\DeleteVacancyHandler;
use App\Application\Features\Vacancies\ListOpenVacanciesHandler;
use App\Application\Features\Vacancies\SearchVacanciesHandler;
use App\Application\Features\Vacancies\UpdateVacancyHandler;
use App\Application\Queries\Applications\ListMyAppliedVacancyIdsQuery;
use App\Application\Queries\Vacancies\ListOpenVacanciesQuery;
use App\Application\Queries\Vacancies\SearchVacanciesQuery;
use App\Domain\Exception\InvalidValueException;
use App\Domain\JobApplication\AlreadyAppliedException;
use App\Domain\Vacancy\VacancyId;
use App\Domain\Vacancy\VacancyNotOpenException;
use PHPUnit\Framework\TestCase;
use Tests\Support\FakeCurrentUser;
use Tests\Support\InMemoryJobApplicationRepository;
use Tests\Support\InMemoryVacancyRepository;

final class VacancyHandlersTest extends TestCase
{
  private InMemoryVacancyRepository $vacancies;
  private InMemoryJobApplicationRepository $applications;

  protected function setUp(): void
  {
    $this->vacancies = new InMemoryVacancyRepository();
    $this->applications = new InMemoryJobApplicationRepository();
  }

  public function testCreateUpdateAndDelete(): void
  {
    $created = $this->create('Dev PHP');
    $this->assertTrue($created->isActive);

    (new UpdateVacancyHandler($this->vacancies))->handle(
      new UpdateVacancyCommand($created->id, 'Dev PHP Sr', 'Nova descrição', false)
    );
    $stored = $this->vacancies->get(VacancyId::fromString($created->id));
    $this->assertSame('Dev PHP Sr', $stored?->title()->value());
    $this->assertFalse($stored?->isOpen());

    (new DeleteVacancyHandler($this->vacancies))->handle(new DeleteVacancyCommand($created->id));
    $this->assertNull($this->vacancies->get(VacancyId::fromString($created->id)));
  }

  public function testCreateRejectsEmptyTitleInsteadOfInventingOne(): void
  {
    $this->expectException(InvalidValueException::class);
    (new CreateVacancyHandler($this->vacancies))->handle(new CreateVacancyCommand('', 'desc'));
  }

  public function testUpdateUnknownVacancy(): void
  {
    $this->expectException(NotFoundException::class);
    (new UpdateVacancyHandler($this->vacancies))->handle(new UpdateVacancyCommand('123', 'T', 'D'));
  }

  public function testSearchFiltersByTitleAndStatus(): void
  {
    $this->create('Dev PHP');
    $this->create('Dev Java', false);
    $this->create('Analista');

    $search = new SearchVacanciesHandler($this->vacancies);
    $this->assertSame(2, $search->handle(new SearchVacanciesQuery('dev'))->total);
    $this->assertSame(1, $search->handle(new SearchVacanciesQuery('dev', true))->total);
    $this->assertSame(1, $search->handle(new SearchVacanciesQuery(null, false))->total);

    $open = (new ListOpenVacanciesHandler($this->vacancies))->handle(new ListOpenVacanciesQuery());
    $this->assertCount(2, $open);
  }

  public function testApplyOncePerOpenVacancy(): void
  {
    $vacancy = $this->create('Dev PHP');
    $currentUser = FakeCurrentUser::as('7');
    $apply = new ApplyToVacancyHandler($this->vacancies, $this->applications, $currentUser);

    $apply->handle(new ApplyToVacancyCommand($vacancy->id));

    $applied = (new ListMyAppliedVacancyIdsHandler($this->applications, $currentUser))
      ->handle(new ListMyAppliedVacancyIdsQuery());
    $this->assertSame([$vacancy->id], $applied);

    $this->expectException(AlreadyAppliedException::class);
    $apply->handle(new ApplyToVacancyCommand($vacancy->id));
  }

  public function testCannotApplyToClosedVacancy(): void
  {
    $vacancy = $this->create('Dev PHP', false);

    $this->expectException(VacancyNotOpenException::class);
    (new ApplyToVacancyHandler($this->vacancies, $this->applications, FakeCurrentUser::as('7')))
      ->handle(new ApplyToVacancyCommand($vacancy->id));
  }

  public function testApplyRequiresAuthenticatedUser(): void
  {
    $vacancy = $this->create('Dev PHP');

    $this->expectException(UnauthorizedException::class);
    (new ApplyToVacancyHandler($this->vacancies, $this->applications, new FakeCurrentUser()))
      ->handle(new ApplyToVacancyCommand($vacancy->id));
  }

  private function create(string $title, bool $active = true): \App\Application\DTOs\VacancyDto
  {
    return (new CreateVacancyHandler($this->vacancies))->handle(new CreateVacancyCommand($title, 'Descrição', $active));
  }
}
