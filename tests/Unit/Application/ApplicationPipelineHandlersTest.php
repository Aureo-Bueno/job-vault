<?php

namespace Tests\Unit\Application;

use App\Application\Commands\Applications\AddApplicationNoteCommand;
use App\Application\Commands\Applications\ApplyToVacancyCommand;
use App\Application\Commands\Applications\MoveApplicationCommand;
use App\Application\Commands\Applications\WithdrawApplicationCommand;
use App\Application\Commands\Vacancies\CreateVacancyCommand;
use App\Application\DTOs\MoveApplicationResultDto;
use App\Application\DTOs\MyApplicationDto;
use App\Application\DTOs\PipelineColumnDto;
use App\Application\DTOs\VacancyPipelineDto;
use App\Application\Exceptions\ConflictException;
use App\Application\Exceptions\ForbiddenException;
use App\Application\Exceptions\MessageValidationException;
use App\Application\Exceptions\NotFoundException;
use App\Application\Features\Applications\AddApplicationNoteHandler;
use App\Application\Features\Applications\ApplyToVacancyHandler;
use App\Application\Features\Applications\GetVacancyPipelineHandler;
use App\Application\Features\Applications\ListMyApplicationsHandler;
use App\Application\Features\Applications\ListVacancyApplicantsHandler;
use App\Application\Features\Applications\MoveApplicationHandler;
use App\Application\Features\Applications\WithdrawApplicationHandler;
use App\Application\Features\Vacancies\CreateVacancyHandler;
use App\Application\Features\Vacancies\VacancyAccessScope;
use App\Application\Queries\Applications\GetVacancyPipelineQuery;
use App\Application\Queries\Applications\ListMyApplicationsQuery;
use App\Application\Security\MessageAuthorizer;
use App\Application\Users\UserRegistrar;
use App\Domain\JobApplication\AlreadyAppliedException;
use App\Domain\JobApplication\ApplicationStatus;
use App\Domain\JobApplication\InvalidApplicationTransitionException;
use App\Domain\Role\Role;
use App\Domain\Role\RoleId;
use App\Domain\Role\RoleName;
use App\Domain\User\User;
use App\Domain\Vacancy\VacancyCloseReason;
use App\Domain\Vacancy\VacancyId;
use App\Domain\Vacancy\VacancyNotOpenException;
use App\Domain\Vacancy\VacancyPositions;
use App\Domain\Vacancy\VacancyStatus;
use PHPUnit\Framework\TestCase;
use Tests\Support\FakeAuthorization;
use Tests\Support\FakeCurrentUser;
use Tests\Support\InMemoryCandidateProfileRepository;
use Tests\Support\InMemoryCompanyRepository;
use Tests\Support\InMemoryJobApplicationRepository;
use Tests\Support\InMemoryRoleRepository;
use Tests\Support\InMemoryUserRepository;
use Tests\Support\InMemoryVacancyRepository;

final class ApplicationPipelineHandlersTest extends TestCase
{
  private InMemoryUserRepository $users;
  private InMemoryCompanyRepository $companies;
  private InMemoryVacancyRepository $vacancies;
  private InMemoryJobApplicationRepository $applications;
  private InMemoryCandidateProfileRepository $profiles;
  private User $recruiter;
  private User $otherRecruiter;
  private User $candidate;
  private string $vacancyId;

  protected function setUp(): void
  {
    $this->users = new InMemoryUserRepository();
    $this->companies = new InMemoryCompanyRepository();
    $this->vacancies = new InMemoryVacancyRepository();
    $this->applications = new InMemoryJobApplicationRepository();
    $this->profiles = new InMemoryCandidateProfileRepository();
    $roles = new InMemoryRoleRepository();
    $roles->save(Role::create(RoleId::generate(), RoleName::recruiter()));
    $roles->save(Role::create(RoleId::generate(), RoleName::defaultUser()));
    $registrar = new UserRegistrar($this->users, $roles, $this->companies);

    $this->recruiter = $registrar->registerCompanyAccount('Rita Recrutadora', 'rita@acme.com', '123456', 'Acme');
    $this->otherRecruiter = $registrar->registerCompanyAccount('Otto', 'otto@other.com', '123456', 'Other');
    $this->candidate = $registrar->register('Caio Candidato', 'caio@example.com', '123456');

    $this->vacancyId = (new CreateVacancyHandler($this->vacancies, $this->scopeFor($this->recruiter), $this->companies))
      ->handle(new CreateVacancyCommand('Dev PHP', 'APIs', 'aberta', 'backend', 'pleno', 'remoto', ['PHP']))
      ->id;
  }

  public function testRecruiterMovesApplicationThroughThePipeline(): void
  {
    $applicationId = $this->apply();

    $this->move($applicationId, MoveApplicationCommand::ADVANCE, 'recebida');
    $this->move($applicationId, MoveApplicationCommand::ADVANCE, 'em_analise');

    $pipeline = $this->pipeline();
    $this->assertSame(1, $pipeline->total);
    $this->assertSame(1, $pipeline->inProcess);
    $this->assertSame(
      ['recebida', 'em_analise', 'entrevista', 'aprovada', 'reprovada', 'desistiu'],
      array_map(static fn (PipelineColumnDto $c): string => $c->status, $pipeline->columns)
    );

    $card = $pipeline->columns[2]->applicants[0];
    $this->assertSame('Caio Candidato', $card->name);
    $this->assertSame('Entrevista', $card->statusLabel);
    $this->assertSame('Aprovada', $card->nextStatusLabel);
    $this->assertTrue($card->canReject);
    $this->assertFalse($card->canReopen);
    $this->assertSame(['Candidato', 'Rita Recrutadora', 'Rita Recrutadora'], array_map(static fn ($e) => $e->actorName, $card->history));
  }

  public function testRejectWithFeedbackAndReopen(): void
  {
    $applicationId = $this->apply();

    $this->move($applicationId, MoveApplicationCommand::REJECT, 'recebida', 'Perfil muito júnior para a vaga.');
    $card = $this->pipeline()->columns[4]->applicants[0];
    $this->assertSame('Perfil muito júnior para a vaga.', $card->candidateFeedback);
    $this->assertTrue($card->canReopen);
    $this->assertNull($card->nextStatusLabel);
    $this->assertSame(0, $this->pipeline()->inProcess);

    $this->move($applicationId, MoveApplicationCommand::REOPEN, 'reprovada');
    $this->assertSame('em_analise', $this->pipeline()->columns[1]->applicants[0]->status);
  }

  public function testStaleBoardIsRejectedAsConflict(): void
  {
    $applicationId = $this->apply();
    $this->move($applicationId, MoveApplicationCommand::ADVANCE, 'recebida');

    $this->expectException(ConflictException::class);
    $this->move($applicationId, MoveApplicationCommand::ADVANCE, 'recebida');
  }

  public function testApprovingTheLastPositionClosesTheVacancy(): void
  {
    $applicationId = $this->apply();
    $this->move($applicationId, MoveApplicationCommand::ADVANCE);
    $this->move($applicationId, MoveApplicationCommand::ADVANCE);

    $result = $this->move($applicationId, MoveApplicationCommand::ADVANCE);

    $this->assertSame('aprovada', $result->status);
    $this->assertTrue($result->vacancyClosedAsFilled);
    $vacancy = $this->vacancies->get(VacancyId::fromString($this->vacancyId));
    $this->assertSame(VacancyStatus::Closed, $vacancy?->status());
    $this->assertSame(VacancyCloseReason::Filled, $vacancy?->closeReason());
  }

  public function testVacancyStaysOpenWhilePositionsRemain(): void
  {
    $vacancy = $this->vacancies->get(VacancyId::fromString($this->vacancyId));
    $this->assertNotNull($vacancy);
    $vacancy->revise($vacancy->title(), $vacancy->description(), positions: VacancyPositions::fromInt(2));
    $this->vacancies->save($vacancy);

    $applicationId = $this->apply();
    $this->move($applicationId, MoveApplicationCommand::ADVANCE);
    $this->move($applicationId, MoveApplicationCommand::ADVANCE);

    $this->assertFalse($this->move($applicationId, MoveApplicationCommand::ADVANCE)->vacancyClosedAsFilled);
    $this->assertTrue($this->vacancies->get(VacancyId::fromString($this->vacancyId))?->isOpen());
  }

  public function testUnknownActionIsInvalid(): void
  {
    $this->expectException(MessageValidationException::class);
    $this->move($this->apply(), 'hire', 'recebida');
  }

  public function testCannotRejectApprovedApplication(): void
  {
    $applicationId = $this->apply();
    $this->move($applicationId, MoveApplicationCommand::ADVANCE);
    $this->move($applicationId, MoveApplicationCommand::ADVANCE);
    $this->move($applicationId, MoveApplicationCommand::ADVANCE);

    $this->expectException(InvalidApplicationTransitionException::class);
    $this->move($applicationId, MoveApplicationCommand::REJECT);
  }

  public function testRecruiterOfAnotherCompanyCannotMoveOrAnnotate(): void
  {
    $applicationId = $this->apply();

    try {
      $this->move($applicationId, MoveApplicationCommand::ADVANCE, '', '', $this->otherRecruiter);
      $this->fail('Expected ForbiddenException');
    } catch (ForbiddenException) {
    }

    $this->expectException(ForbiddenException::class);
    $this->noteHandler($this->otherRecruiter)->handle(new AddApplicationNoteCommand($applicationId, 'Intrometido'));
  }

  public function testRecruiterOfAnotherCompanyCannotSeeThePipeline(): void
  {
    $this->apply();

    $this->expectException(ForbiddenException::class);
    $this->pipeline($this->otherRecruiter);
  }

  public function testNotesAppearOnTheCard(): void
  {
    $applicationId = $this->apply();

    $this->noteHandler($this->recruiter)->handle(new AddApplicationNoteCommand($applicationId, 'Ligar na segunda.'));

    $notes = $this->pipeline()->columns[0]->applicants[0]->notes;
    $this->assertCount(1, $notes);
    $this->assertSame('Rita Recrutadora', $notes[0]->authorName);
    $this->assertSame('Ligar na segunda.', $notes[0]->body);
  }

  public function testUnknownApplicationIsNotFound(): void
  {
    $this->expectException(NotFoundException::class);
    $this->move('00000000-0000-4000-8000-000000000000', MoveApplicationCommand::ADVANCE);
  }

  public function testCandidateSeesOwnApplicationsWithTimelineButNoRecruiterNames(): void
  {
    $applicationId = $this->apply();
    $this->move($applicationId, MoveApplicationCommand::ADVANCE);
    $this->move($applicationId, MoveApplicationCommand::REJECT, '', 'Seguimos com outro perfil.');

    $mine = $this->myApplications();

    $this->assertCount(1, $mine);
    $this->assertSame('Dev PHP', $mine[0]->vacancyTitle);
    $this->assertSame('Acme', $mine[0]->companyName);
    $this->assertSame('Não selecionada', $mine[0]->statusLabel);
    $this->assertSame('Seguimos com outro perfil.', $mine[0]->feedback);
    $this->assertFalse($mine[0]->canWithdraw);
    $this->assertSame(['Candidatura enviada', 'Em análise', 'Não selecionada'], array_map(static fn ($e) => $e->label, $mine[0]->timeline));
    $this->assertSame(['', '', ''], array_map(static fn ($e) => $e->actorName, $mine[0]->timeline));
    $this->assertSame('Seguimos com outro perfil.', $mine[0]->timeline[2]->reason);
  }

  public function testCandidateWithdrawsAndReappliesWhileVacancyIsOpen(): void
  {
    $applicationId = $this->apply();
    $this->assertTrue($this->myApplications()[0]->canWithdraw);

    (new WithdrawApplicationHandler($this->applications, FakeCurrentUser::as($this->candidate->id()->value())))
      ->handle(new WithdrawApplicationCommand($applicationId));

    $mine = $this->myApplications();
    $this->assertSame('desistiu', $mine[0]->status);
    $this->assertTrue($mine[0]->canReapply);
    $this->assertSame([], $this->applications->vacancyIdsAppliedBy($this->candidate->id()));

    $this->assertSame($applicationId, $this->apply());
    $this->assertSame('recebida', $this->myApplications()[0]->status);
    $this->assertCount(3, $this->myApplications()[0]->timeline);
  }

  public function testCannotApplyTwiceWhileInProcess(): void
  {
    $this->apply();

    $this->expectException(AlreadyAppliedException::class);
    $this->apply();
  }

  public function testCannotReapplyToClosedVacancy(): void
  {
    $applicationId = $this->apply();
    (new WithdrawApplicationHandler($this->applications, FakeCurrentUser::as($this->candidate->id()->value())))
      ->handle(new WithdrawApplicationCommand($applicationId));
    $vacancy = $this->vacancies->get(VacancyId::fromString($this->vacancyId));
    $this->assertNotNull($vacancy);
    $vacancy->close();
    $this->vacancies->save($vacancy);

    $this->assertFalse($this->myApplications()[0]->canReapply);
    $this->expectException(VacancyNotOpenException::class);
    $this->apply();
  }

  public function testCandidateCannotWithdrawSomeoneElsesApplication(): void
  {
    $applicationId = $this->apply();

    $this->expectException(NotFoundException::class);
    (new WithdrawApplicationHandler($this->applications, FakeCurrentUser::as($this->recruiter->id()->value())))
      ->handle(new WithdrawApplicationCommand($applicationId));
  }

  public function testWithdrawnApplicationsAreNotCountedAsApplicants(): void
  {
    $applicationId = $this->apply();
    (new WithdrawApplicationHandler($this->applications, FakeCurrentUser::as($this->candidate->id()->value())))
      ->handle(new WithdrawApplicationCommand($applicationId));

    $this->assertSame([], $this->applications->countPerVacancy());
    $this->assertSame(1, $this->pipeline()->columns[5]->count());
    $this->assertSame(ApplicationStatus::Withdrawn->value, $this->pipeline()->columns[5]->status);
  }

  private function apply(): string
  {
    (new ApplyToVacancyHandler($this->vacancies, $this->applications, FakeCurrentUser::as($this->candidate->id()->value())))
      ->handle(new ApplyToVacancyCommand($this->vacancyId));

    return (string) $this->applications->findFor($this->candidate->id(), VacancyId::fromString($this->vacancyId))?->id()->value();
  }

  private function move(string $applicationId, string $action, string $expected = '', string $feedback = '', ?User $as = null): MoveApplicationResultDto
  {
    $as ??= $this->recruiter;
    return (new MoveApplicationHandler(
      $this->applications,
      $this->vacancies,
      $this->scopeFor($as),
      FakeCurrentUser::as($as->id()->value())
    ))->handle(new MoveApplicationCommand($applicationId, $action, $expected, $feedback));
  }

  private function noteHandler(User $as): AddApplicationNoteHandler
  {
    return new AddApplicationNoteHandler(
      $this->applications,
      $this->vacancies,
      $this->scopeFor($as),
      FakeCurrentUser::as($as->id()->value())
    );
  }

  private function pipeline(?User $as = null): VacancyPipelineDto
  {
    $as ??= $this->recruiter;
    return (new GetVacancyPipelineHandler(new ListVacancyApplicantsHandler(
      $this->vacancies,
      $this->applications,
      $this->users,
      $this->profiles,
      $this->scopeFor($as)
    )))->handle(new GetVacancyPipelineQuery($this->vacancyId));
  }

  /**
   * @return list<MyApplicationDto>
   */
  private function myApplications(): array
  {
    return (new ListMyApplicationsHandler(
      $this->applications,
      $this->vacancies,
      $this->companies,
      FakeCurrentUser::as($this->candidate->id()->value())
    ))->handle(new ListMyApplicationsQuery());
  }

  private function scopeFor(User $user): VacancyAccessScope
  {
    return new VacancyAccessScope(
      FakeCurrentUser::as($user->id()->value()),
      $this->users,
      new FakeAuthorization(['vacancy.manage', 'vacancy.create', MessageAuthorizer::MANAGE_APPLICATIONS], false, 'gestor'),
      $this->companies
    );
  }
}

