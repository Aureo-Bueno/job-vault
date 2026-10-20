<?php

namespace Tests\Unit\Domain;

use App\Domain\Exception\BusinessRuleViolationException;
use App\Domain\Exception\InvalidValueException;
use App\Domain\Shared\BrazilianState;
use App\Domain\Shared\Seniority;
use App\Domain\Shared\WorkArea;
use App\Domain\Shared\WorkMode;
use App\Domain\User\UserId;
use App\Domain\Vacancy\Benefit;
use App\Domain\Vacancy\ContractType;
use App\Domain\Vacancy\EmploymentTerms;
use App\Domain\Vacancy\SalaryRange;
use App\Domain\Vacancy\Vacancy;
use App\Domain\Vacancy\VacancyCloseReason;
use App\Domain\Vacancy\VacancyDeadline;
use App\Domain\Vacancy\VacancyDescription;
use App\Domain\Vacancy\VacancyId;
use App\Domain\Vacancy\VacancyLocation;
use App\Domain\Vacancy\VacancyNotOpenException;
use App\Domain\Vacancy\VacancyStatus;
use App\Domain\Vacancy\VacancyTitle;
use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;

final class VacancyTermsTest extends TestCase
{
  public function testSalaryLabels(): void
  {
    $this->assertSame('A combinar', SalaryRange::negotiable()->label());
    $this->assertSame('R$ 8.000 – R$ 12.500', SalaryRange::of(8000, 12500)->label());
    $this->assertSame('A partir de R$ 8.000', SalaryRange::of(8000, null)->label());
    $this->assertSame('Até R$ 3.000', SalaryRange::of(null, 3000)->label());
    $this->assertSame('R$ 5.000', SalaryRange::of(5000, 5000)->label());
  }

  public function testSalaryMaxCannotBeBelowMin(): void
  {
    $this->expectException(InvalidValueException::class);
    SalaryRange::of(9000, 8000);
  }

  public function testSalaryMustBePositive(): void
  {
    $this->expectException(InvalidValueException::class);
    SalaryRange::of(0, null);
  }

  public function testSalaryReachUsesTheTopOfTheRange(): void
  {
    $this->assertTrue(SalaryRange::of(6000, 9000)->reaches(8000));
    $this->assertTrue(SalaryRange::of(8000, null)->reaches(8000));
    $this->assertFalse(SalaryRange::of(null, 7000)->reaches(8000));
    $this->assertFalse(SalaryRange::negotiable()->reaches(1));
  }

  public function testParseAmountAcceptsBrazilianAndPlainFormats(): void
  {
    $this->assertNull(SalaryRange::parseAmount(''));
    $this->assertNull(SalaryRange::parseAmount('   '));
    $this->assertSame(8000, SalaryRange::parseAmount('8000'));
    $this->assertSame(8000, SalaryRange::parseAmount('8.000'));
    $this->assertSame(8000, SalaryRange::parseAmount('R$ 8.000,00'));
    $this->assertSame(8000, SalaryRange::parseAmount('8000.50'));
    $this->assertSame(12500, SalaryRange::parseAmount('12.500'));
    $this->assertSame(1250000, SalaryRange::parseAmount('1.250.000'));
  }

  public function testLocationRequiresCityAndStateTogether(): void
  {
    $this->assertNull(VacancyLocation::fromInput('', ''));
    $this->assertSame('Curitiba/PR', VacancyLocation::fromInput('  Curitiba ', 'pr')?->label());

    $this->expectException(InvalidValueException::class);
    VacancyLocation::fromInput('Curitiba', '');
  }

  public function testUnknownStateIsRejected(): void
  {
    $this->expectException(InvalidValueException::class);
    VacancyLocation::fromInput('Curitiba', 'XX');
  }

  public function testBenefitsAreDeduplicatedIgnoringCaseAndAccents(): void
  {
    $benefits = Benefit::listFromInput('Plano de saúde, plano de saude; Vale-refeição' . "\n" . ' PLR ');

    $this->assertSame(['Plano de saúde', 'Vale-refeição', 'PLR'], array_map(static fn (Benefit $b): string => $b->label(), $benefits));
    $this->assertSame('vale-refeicao', $benefits[1]->slug());
  }

  public function testTooManyBenefitsAreRejected(): void
  {
    $this->expectException(InvalidValueException::class);
    Benefit::listFromInput(array_map(static fn (int $i): string => 'Benefício ' . $i, range(1, Benefit::MAX_PER_VACANCY + 1)));
  }

  public function testDeadlineUsesBrasiliaTime(): void
  {
    $deadline = VacancyDeadline::fromString('2026-10-31');

    // 23:30 in Brasília is already 02:30 of the next day in UTC: still within the deadline.
    $this->assertFalse($deadline->hasPassed(new DateTimeImmutable('2026-11-01 02:30:00', new DateTimeZone('UTC'))));
    $this->assertTrue($deadline->hasPassed(new DateTimeImmutable('2026-11-01 03:00:00', new DateTimeZone('UTC'))));
    $this->assertSame('31/10/2026', $deadline->label());
  }

  public function testInvalidDeadlineIsRejected(): void
  {
    $this->expectException(InvalidValueException::class);
    VacancyDeadline::fromString('2026-02-30');
  }

  public function testOnSiteVacancyRequiresLocation(): void
  {
    $this->expectException(BusinessRuleViolationException::class);
    $this->expectExceptionMessage('presenciais');
    $this->publish(WorkMode::Presencial, EmploymentTerms::standard());
  }

  public function testRemoteVacancyDoesNotRequireLocation(): void
  {
    $vacancy = $this->publish(WorkMode::Remoto, EmploymentTerms::of(ContractType::Pj, SalaryRange::of(9000, 13000), null, 'PLR'));

    $this->assertSame(ContractType::Pj, $vacancy->terms()->contractType());
    $this->assertSame('R$ 9.000 – R$ 13.000', $vacancy->terms()->salary()->label());
    $this->assertSame(['PLR'], $vacancy->terms()->benefitLabels());
    $this->assertNull($vacancy->terms()->location());
  }

  public function testSwitchingToHybridWithoutLocationIsRejected(): void
  {
    $vacancy = $this->publish(WorkMode::Remoto, EmploymentTerms::standard());

    $this->expectException(BusinessRuleViolationException::class);
    $this->expectExceptionMessage('híbridas');
    $vacancy->revise($vacancy->title(), $vacancy->description(), workMode: WorkMode::Hibrido);
  }

  public function testSwitchingToHybridWithLocationIsAccepted(): void
  {
    $vacancy = $this->publish(WorkMode::Remoto, EmploymentTerms::standard());

    $vacancy->revise(
      $vacancy->title(),
      $vacancy->description(),
      workMode: WorkMode::Hibrido,
      terms: EmploymentTerms::of(location: VacancyLocation::of('Recife', BrazilianState::PE))
    );

    $this->assertSame(WorkMode::Hibrido, $vacancy->workMode());
    $this->assertSame('Recife/PE', $vacancy->terms()->location()?->label());
  }

  public function testDeadlineCannotBeSetInThePast(): void
  {
    $this->expectException(BusinessRuleViolationException::class);
    $this->publish(WorkMode::Remoto, null, VacancyDeadline::fromString('2026-01-09'), new DateTimeImmutable('2026-01-10 12:00:00'));
  }

  public function testApplicationsAreRefusedAfterTheDeadlineEvenBeforeTheJobCloses(): void
  {
    $vacancy = $this->publish(WorkMode::Remoto, null, VacancyDeadline::fromString('2026-01-31'), new DateTimeImmutable('2026-01-10 12:00:00'));

    $this->assertTrue($vacancy->acceptsApplications(new DateTimeImmutable('2026-01-31 20:00:00')));
    $this->assertFalse($vacancy->acceptsApplications(new DateTimeImmutable('2026-02-01 12:00:00')));
    $this->assertTrue($vacancy->isOpen());

    $this->expectException(VacancyNotOpenException::class);
    $vacancy->receiveApplicationFrom(UserId::generate(), new DateTimeImmutable('2026-02-01 12:00:00'));
  }

  public function testCloseIfExpired(): void
  {
    $vacancy = $this->publish(WorkMode::Remoto, null, VacancyDeadline::fromString('2026-01-31'), new DateTimeImmutable('2026-01-10 12:00:00'));

    $this->assertFalse($vacancy->closeIfExpired(new DateTimeImmutable('2026-01-31 12:00:00')));
    $this->assertTrue($vacancy->closeIfExpired(new DateTimeImmutable('2026-02-01 12:00:00')));
    $this->assertSame(VacancyStatus::Closed, $vacancy->status());
    $this->assertSame(VacancyCloseReason::Deadline, $vacancy->closeReason());
    $this->assertFalse($vacancy->closeIfExpired(new DateTimeImmutable('2026-02-02 12:00:00')), 'already closed');
  }

  public function testDraftsAreNotClosedByTheDeadline(): void
  {
    $vacancy = $this->publish(WorkMode::Remoto, null, VacancyDeadline::fromString('2026-01-31'), new DateTimeImmutable('2026-01-10 12:00:00'), VacancyStatus::Draft);

    $this->assertFalse($vacancy->closeIfExpired(new DateTimeImmutable('2026-02-01 12:00:00')));
    $this->assertTrue($vacancy->isDraft());
  }

  public function testReopeningRequiresANewDeadlineOnceItPassed(): void
  {
    $vacancy = $this->publish(WorkMode::Remoto, null, VacancyDeadline::fromString('2026-01-31'), new DateTimeImmutable('2026-01-10 12:00:00'));
    $vacancy->closeIfExpired(new DateTimeImmutable('2026-02-01 12:00:00'));

    try {
      $vacancy->open(new DateTimeImmutable('2026-02-05 12:00:00'));
      $this->fail('Reopening with a past deadline should be refused.');
    } catch (BusinessRuleViolationException $e) {
      $this->assertStringContainsString('31/01/2026', $e->getMessage());
      $this->assertStringContainsString('reabrir', $e->getMessage());
    }

    $vacancy->reschedule(VacancyDeadline::fromString('2026-02-28'), new DateTimeImmutable('2026-02-05 12:00:00'));
    $vacancy->open(new DateTimeImmutable('2026-02-05 12:00:00'));
    $this->assertTrue($vacancy->acceptsApplications(new DateTimeImmutable('2026-02-05 12:00:00')));
  }

  public function testKeepingAnExpiredDeadlineIsAllowed(): void
  {
    $vacancy = $this->publish(WorkMode::Remoto, null, VacancyDeadline::fromString('2026-01-31'), new DateTimeImmutable('2026-01-10 12:00:00'));

    $vacancy->reschedule(VacancyDeadline::fromString('2026-01-31'), new DateTimeImmutable('2026-03-01 12:00:00'));
    $this->assertSame('2026-01-31', $vacancy->closesAt()?->value());

    $vacancy->reschedule(null, new DateTimeImmutable('2026-03-01 12:00:00'));
    $this->assertNull($vacancy->closesAt());
  }

  private function publish(
    WorkMode $workMode,
    ?EmploymentTerms $terms = null,
    ?VacancyDeadline $closesAt = null,
    ?DateTimeImmutable $now = null,
    VacancyStatus $status = VacancyStatus::Open
  ): Vacancy {
    return Vacancy::publish(
      VacancyId::generate(),
      VacancyTitle::fromString('Dev PHP'),
      VacancyDescription::fromString('APIs'),
      $status,
      $now ?? new DateTimeImmutable('2026-01-10 12:00:00'),
      null,
      WorkArea::Backend,
      Seniority::Pleno,
      $workMode,
      ['PHP'],
      null,
      $terms,
      $closesAt
    );
  }
}
