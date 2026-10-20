<?php

namespace Tests\Unit\Domain;

use App\Domain\Exception\BusinessRuleViolationException;
use App\Domain\Exception\InvalidValueException;
use App\Domain\User\UserId;
use App\Domain\Vacancy\Vacancy;
use App\Domain\Vacancy\VacancyCloseReason;
use App\Domain\Vacancy\VacancyDescription;
use App\Domain\Vacancy\VacancyId;
use App\Domain\Vacancy\VacancyNotOpenException;
use App\Domain\Vacancy\VacancyPositions;
use App\Domain\Vacancy\VacancyStatus;
use App\Domain\Vacancy\VacancyTitle;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class VacancyLifecycleTest extends TestCase
{
  public function testDraftIsNotOpenAndDoesNotReceiveApplications(): void
  {
    $vacancy = $this->vacancy(VacancyStatus::Draft);

    $this->assertTrue($vacancy->isDraft());
    $this->assertFalse($vacancy->isOpen());

    $this->expectException(VacancyNotOpenException::class);
    $vacancy->receiveApplicationFrom(UserId::generate());
  }

  public function testOpeningADraftSetsThePublicationDate(): void
  {
    $vacancy = $this->vacancy(VacancyStatus::Draft, new DateTimeImmutable('2026-01-01 08:00:00'));

    $vacancy->open(new DateTimeImmutable('2026-02-01 09:00:00'));

    $this->assertTrue($vacancy->isOpen());
    $this->assertSame('2026-02-01 09:00:00', $vacancy->publishedAt()->format('Y-m-d H:i:s'));
  }

  public function testReopeningAClosedVacancyKeepsTheOriginalPublicationDate(): void
  {
    $vacancy = $this->vacancy(VacancyStatus::Open, new DateTimeImmutable('2026-01-01 08:00:00'));
    $vacancy->close(VacancyCloseReason::Manual, new DateTimeImmutable('2026-01-10 08:00:00'));

    $this->assertSame(VacancyStatus::Closed, $vacancy->status());
    $this->assertSame(VacancyCloseReason::Manual, $vacancy->closeReason());
    $this->assertSame('2026-01-10 08:00:00', $vacancy->closedAt()?->format('Y-m-d H:i:s'));

    $vacancy->open(new DateTimeImmutable('2026-01-20 08:00:00'));

    $this->assertTrue($vacancy->isOpen());
    $this->assertNull($vacancy->closeReason());
    $this->assertNull($vacancy->closedAt());
    $this->assertSame('2026-01-01 08:00:00', $vacancy->publishedAt()->format('Y-m-d H:i:s'));
  }

  public function testPublishedVacancyCannotGoBackToDraft(): void
  {
    $vacancy = $this->vacancy(VacancyStatus::Open);

    $this->expectException(BusinessRuleViolationException::class);
    $vacancy->changeStatus(VacancyStatus::Draft);
  }

  public function testDraftCannotBeClosed(): void
  {
    $this->expectException(BusinessRuleViolationException::class);
    $this->vacancy(VacancyStatus::Draft)->close();
  }

  public function testChangeStatusIsIdempotent(): void
  {
    $vacancy = $this->vacancy(VacancyStatus::Open, new DateTimeImmutable('2026-01-01 08:00:00'));
    $vacancy->changeStatus(VacancyStatus::Open);
    $this->assertSame('2026-01-01 08:00:00', $vacancy->publishedAt()->format('Y-m-d H:i:s'));

    $vacancy->changeStatus(VacancyStatus::Closed);
    $vacancy->changeStatus(VacancyStatus::Closed);
    $this->assertSame(VacancyCloseReason::Manual, $vacancy->closeReason());

    $draft = $this->vacancy(VacancyStatus::Draft);
    $draft->changeStatus(VacancyStatus::Draft);
    $this->assertTrue($draft->isDraft());
  }

  public function testClosesWhenAllPositionsAreFilled(): void
  {
    $vacancy = $this->vacancy(VacancyStatus::Open, null, 2);

    $this->assertFalse($vacancy->closeIfFilled(1));
    $this->assertTrue($vacancy->isOpen());

    $this->assertTrue($vacancy->closeIfFilled(2));
    $this->assertSame(VacancyCloseReason::Filled, $vacancy->closeReason());
    $this->assertFalse($vacancy->closeIfFilled(3));
  }

  public function testManuallyClosedVacancyIsNotReclosedAsFilled(): void
  {
    $vacancy = $this->vacancy(VacancyStatus::Open);
    $vacancy->close();

    $this->assertFalse($vacancy->closeIfFilled(5));
    $this->assertSame(VacancyCloseReason::Manual, $vacancy->closeReason());
  }

  public function testPositionsAreBetweenOneAndNineHundredNinetyNine(): void
  {
    $this->assertSame(3, VacancyPositions::fromInt(3)->value());

    $this->expectException(InvalidValueException::class);
    VacancyPositions::fromInt(0);
  }

  public function testReviseChangesPositions(): void
  {
    $vacancy = $this->vacancy(VacancyStatus::Open);

    $vacancy->revise(VacancyTitle::fromString('Dev'), VacancyDescription::fromString('D'), positions: VacancyPositions::fromInt(4));

    $this->assertSame(4, $vacancy->positions()->value());
  }

  public function testStatusLabelsAndStoredValues(): void
  {
    $this->assertSame('Rascunho', VacancyStatus::Draft->label());
    $this->assertSame(VacancyStatus::Open, VacancyStatus::fromString(' ABERTA '));
    $this->assertSame(VacancyStatus::Closed, VacancyStatus::fromStored('???'));
    $this->assertSame('Posições preenchidas', VacancyCloseReason::Filled->label());

    $this->expectException(InvalidValueException::class);
    VacancyStatus::fromString('ativa');
  }

  private function vacancy(VacancyStatus $status, ?DateTimeImmutable $now = null, int $positions = 1): Vacancy
  {
    return Vacancy::publish(
      VacancyId::generate(),
      VacancyTitle::fromString('Dev PHP'),
      VacancyDescription::fromString('Backend'),
      $status,
      $now,
      positions: VacancyPositions::fromInt($positions)
    );
  }
}
