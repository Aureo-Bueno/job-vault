<?php

namespace Tests\Unit\Domain;

use App\Domain\Exception\InvalidValueException;
use App\Domain\User\UserId;
use App\Domain\Vacancy\Vacancy;
use App\Domain\Vacancy\VacancyDescription;
use App\Domain\Vacancy\VacancyId;
use App\Domain\Vacancy\VacancyNotOpenException;
use App\Domain\Vacancy\VacancyStatus;
use App\Domain\Vacancy\VacancyTitle;
use PHPUnit\Framework\TestCase;

final class VacancyTest extends TestCase
{
  public function testPublishedVacancyIsOpenByDefault(): void
  {
    $vacancy = $this->vacancy();

    $this->assertTrue($vacancy->isOpen());
    $this->assertSame('Dev PHP', $vacancy->title()->value());
  }

  public function testTitleAndDescriptionAreRequiredAndTrimmed(): void
  {
    $this->assertSame('Dev', VacancyTitle::fromString('  Dev  ')->value());

    $this->expectException(InvalidValueException::class);
    VacancyTitle::fromString('   ');
  }

  public function testDescriptionCannotBeEmpty(): void
  {
    $this->expectException(InvalidValueException::class);
    VacancyDescription::fromString('');
  }

  public function testReviseAndChangeStatus(): void
  {
    $vacancy = $this->vacancy();

    $vacancy->revise(VacancyTitle::fromString('Dev Go'), VacancyDescription::fromString('Backend'));
    $vacancy->changeStatus(VacancyStatus::Inactive);

    $this->assertSame('Dev Go', $vacancy->title()->value());
    $this->assertSame('Backend', $vacancy->description()->value());
    $this->assertFalse($vacancy->isOpen());

    $vacancy->open();
    $this->assertTrue($vacancy->isOpen());
  }

  public function testOpenVacancyReceivesApplication(): void
  {
    $vacancy = $this->vacancy();
    $candidate = UserId::generate();

    $application = $vacancy->receiveApplicationFrom($candidate);

    $this->assertTrue($application->vacancyId()->equals($vacancy->id()));
    $this->assertTrue($application->candidateId()->equals($candidate));
  }

  public function testClosedVacancyRejectsApplications(): void
  {
    $vacancy = $this->vacancy();
    $vacancy->close();

    $this->expectException(VacancyNotOpenException::class);
    $vacancy->receiveApplicationFrom(UserId::generate());
  }

  private function vacancy(): Vacancy
  {
    return Vacancy::publish(
      VacancyId::generate(),
      VacancyTitle::fromString('Dev PHP'),
      VacancyDescription::fromString('Laravel, MySQL')
    );
  }
}
