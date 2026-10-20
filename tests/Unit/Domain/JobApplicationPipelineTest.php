<?php

namespace Tests\Unit\Domain;

use App\Domain\Exception\BusinessRuleViolationException;
use App\Domain\Exception\InvalidValueException;
use App\Domain\JobApplication\ApplicationNote;
use App\Domain\JobApplication\ApplicationStatus;
use App\Domain\JobApplication\InvalidApplicationTransitionException;
use App\Domain\JobApplication\JobApplication;
use App\Domain\JobApplication\JobApplicationId;
use App\Domain\User\UserId;
use App\Domain\Vacancy\Vacancy;
use App\Domain\Vacancy\VacancyDescription;
use App\Domain\Vacancy\VacancyId;
use App\Domain\Vacancy\VacancyNotOpenException;
use App\Domain\Vacancy\VacancyStatus;
use App\Domain\Vacancy\VacancyTitle;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class JobApplicationPipelineTest extends TestCase
{
  private UserId $candidate;
  private UserId $recruiter;

  protected function setUp(): void
  {
    $this->candidate = UserId::generate();
    $this->recruiter = UserId::generate();
  }

  /**
   * @return iterable<string,array{ApplicationStatus,ApplicationStatus,bool}>
   */
  public static function transitions(): iterable
  {
    $allowed = [
      'recebida' => ['em_analise', 'reprovada', 'desistiu'],
      'em_analise' => ['entrevista', 'reprovada', 'desistiu'],
      'entrevista' => ['aprovada', 'reprovada', 'desistiu'],
      'aprovada' => [],
      'reprovada' => ['em_analise'],
      'desistiu' => ['recebida'],
    ];

    foreach (ApplicationStatus::cases() as $from) {
      foreach (ApplicationStatus::cases() as $to) {
        $expected = in_array($to->value, $allowed[$from->value], true);
        yield "{$from->value} -> {$to->value}" => [$from, $to, $expected];
      }
    }
  }

  #[DataProvider('transitions')]
  public function testTransitionTable(ApplicationStatus $from, ApplicationStatus $to, bool $expected): void
  {
    $this->assertSame($expected, $from->canTransitionTo($to));
  }

  public function testSubmittedApplicationStartsAsReceivedWithHistory(): void
  {
    $application = $this->submitted();

    $this->assertSame(ApplicationStatus::Received, $application->status());
    $this->assertCount(1, $application->history());
    $this->assertNull($application->history()[0]->from);
    $this->assertSame(ApplicationStatus::Received, $application->history()[0]->to);
    $this->assertTrue($application->history()[0]->actorId?->equals($this->candidate));
    $this->assertCount(1, $application->releaseNewEvents());
    $this->assertSame([], $application->releaseNewEvents());
  }

  public function testAdvanceGoesOneStageAtATimeUntilApproved(): void
  {
    $application = $this->submitted();
    $application->releaseNewEvents();

    $application->advance($this->recruiter, $this->at('2026-01-02'));
    $this->assertSame(ApplicationStatus::InReview, $application->status());
    $application->advance($this->recruiter, $this->at('2026-01-03'));
    $this->assertSame(ApplicationStatus::Interview, $application->status());
    $application->advance($this->recruiter, $this->at('2026-01-04'));
    $this->assertSame(ApplicationStatus::Approved, $application->status());

    $this->assertEquals($this->at('2026-01-04'), $application->statusChangedAt());
    $this->assertCount(4, $application->history());
    $this->assertCount(3, $application->releaseNewEvents());

    $this->expectException(InvalidApplicationTransitionException::class);
    $application->advance($this->recruiter);
  }

  public function testRejectStoresFeedbackAndReopenClearsIt(): void
  {
    $application = $this->submitted();
    $application->advance($this->recruiter);

    $application->reject($this->recruiter, '  Buscamos alguém com mais experiência em Go.  ');
    $this->assertSame(ApplicationStatus::Rejected, $application->status());
    $this->assertSame('Buscamos alguém com mais experiência em Go.', $application->candidateFeedback());
    $this->assertSame('Buscamos alguém com mais experiência em Go.', $application->history()[2]->reason);

    $application->reopen($this->recruiter);
    $this->assertSame(ApplicationStatus::InReview, $application->status());
    $this->assertNull($application->candidateFeedback());
  }

  public function testApprovedApplicationCannotBeRejected(): void
  {
    $application = $this->submitted();
    $application->advance($this->recruiter);
    $application->advance($this->recruiter);
    $application->advance($this->recruiter);

    $this->expectException(InvalidApplicationTransitionException::class);
    $application->reject($this->recruiter);
  }

  public function testOnlyRejectedApplicationsCanBeReopened(): void
  {
    $this->expectException(InvalidApplicationTransitionException::class);
    $this->submitted()->reopen($this->recruiter);
  }

  public function testFeedbackIsLimited(): void
  {
    $this->expectException(InvalidValueException::class);
    $this->submitted()->reject($this->recruiter, str_repeat('a', 501));
  }

  public function testCandidateWithdrawsWhileInProcess(): void
  {
    $application = $this->submitted();
    $application->advance($this->recruiter);

    $application->withdraw($this->candidate);

    $this->assertSame(ApplicationStatus::Withdrawn, $application->status());
    $this->assertTrue($application->status()->isClosed());
  }

  public function testOnlyTheCandidateCanWithdraw(): void
  {
    $this->expectException(BusinessRuleViolationException::class);
    $this->submitted()->withdraw($this->recruiter);
  }

  public function testCannotWithdrawAfterRejection(): void
  {
    $application = $this->submitted();
    $application->reject($this->recruiter);

    $this->expectException(InvalidApplicationTransitionException::class);
    $application->withdraw($this->candidate);
  }

  public function testWithdrawnApplicationCanBeResubmittedWhileVacancyIsOpen(): void
  {
    $vacancy = $this->vacancy();
    $application = $vacancy->receiveApplicationFrom($this->candidate);
    $application->withdraw($this->candidate);

    $vacancy->acceptResubmission($application, $this->candidate);

    $this->assertSame(ApplicationStatus::Received, $application->status());
    $this->assertCount(3, $application->history());
  }

  public function testResubmissionRequiresOpenVacancy(): void
  {
    $vacancy = $this->vacancy();
    $application = $vacancy->receiveApplicationFrom($this->candidate);
    $application->withdraw($this->candidate);
    $vacancy->close();

    $this->expectException(VacancyNotOpenException::class);
    $vacancy->acceptResubmission($application, $this->candidate);
  }

  public function testResubmissionRequiresWithdrawnStatus(): void
  {
    $vacancy = $this->vacancy();
    $application = $vacancy->receiveApplicationFrom($this->candidate);

    $this->expectException(InvalidApplicationTransitionException::class);
    $vacancy->acceptResubmission($application, $this->candidate);
  }

  public function testResubmissionOfAnotherVacancyApplicationIsRejected(): void
  {
    $application = $this->vacancy()->receiveApplicationFrom($this->candidate);
    $application->withdraw($this->candidate);

    $this->expectException(BusinessRuleViolationException::class);
    $this->vacancy()->acceptResubmission($application, $this->candidate);
  }

  public function testStatusesForTheBoard(): void
  {
    $this->assertSame(ApplicationStatus::InReview, ApplicationStatus::Received->next());
    $this->assertNull(ApplicationStatus::Approved->next());
    $this->assertNull(ApplicationStatus::Rejected->next());
    $this->assertTrue(ApplicationStatus::Interview->isInProcess());
    $this->assertFalse(ApplicationStatus::Rejected->isInProcess());
    $this->assertSame(ApplicationStatus::Received, ApplicationStatus::fromStored('desconhecido'));
    $this->assertSame('Não selecionada', ApplicationStatus::Rejected->candidateLabel());
  }

  public function testNoteBodyIsRequiredAndTrimmed(): void
  {
    $note = ApplicationNote::write(JobApplicationId::generate(), $this->recruiter, '  Ótima entrevista técnica.  ');
    $this->assertSame('Ótima entrevista técnica.', $note->body);

    $this->expectException(InvalidValueException::class);
    ApplicationNote::write(JobApplicationId::generate(), $this->recruiter, '   ');
  }

  private function submitted(): JobApplication
  {
    return $this->vacancy()->receiveApplicationFrom($this->candidate, $this->at('2026-01-01'));
  }

  private function vacancy(): Vacancy
  {
    return Vacancy::publish(
      VacancyId::generate(),
      VacancyTitle::fromString('Dev PHP'),
      VacancyDescription::fromString('Backend'),
      VacancyStatus::Open
    );
  }

  private function at(string $date): DateTimeImmutable
  {
    return new DateTimeImmutable($date . ' 10:00:00');
  }
}
