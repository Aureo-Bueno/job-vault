<?php

namespace Tests\Unit\Domain;

use App\Domain\CandidateProfile\CandidateProfile;
use App\Domain\Company\Company;
use App\Domain\Company\CompanyId;
use App\Domain\Company\CompanyName;
use App\Domain\Exception\InvalidValueException;
use App\Domain\Matching\MatchCalculator;
use App\Domain\Shared\Seniority;
use App\Domain\Shared\TopicTag;
use App\Domain\Shared\WorkArea;
use App\Domain\Shared\WorkMode;
use App\Domain\User\UserId;
use App\Domain\Vacancy\Vacancy;
use App\Domain\Vacancy\VacancyDescription;
use App\Domain\Vacancy\VacancyId;
use App\Domain\Vacancy\VacancyStatus;
use App\Domain\Vacancy\VacancyTitle;
use PHPUnit\Framework\TestCase;

final class MatchingAndProfileTest extends TestCase
{
  public function testTopicTagNormalizesAndDeduplicatesAliases(): void
  {
    $tags = TopicTag::listFromInput('Node.js, nodejs, ReactJS, C#, .NET, Orientação a Objetos');
    $slugs = array_map(static fn (TopicTag $t): string => $t->slug(), $tags);

    $this->assertSame(['nodejs', 'react', 'c#', 'dotnet', 'poo'], $slugs);
    $this->assertSame('Node.js', $tags[0]->label());
  }

  public function testCompanyValidatesNameAndDefaultsSector(): void
  {
    $company = Company::register(CompanyId::generate(), CompanyName::fromString('  TechNova  '), '');
    $this->assertSame('TechNova', $company->name()->value());
    $this->assertSame('Tecnologia', $company->sector());

    $this->expectException(InvalidValueException::class);
    CompanyName::fromString('   ');
  }

  public function testCandidateProfileCompletenessAndTopics(): void
  {
    $userId = UserId::generate();
    $empty = CandidateProfile::emptyFor($userId);
    $this->assertFalse($empty->isComplete());

    $filled = CandidateProfile::define(
      $userId,
      'Dev Backend PHP',
      'Experiência com DDD e microsserviços',
      WorkArea::Backend,
      Seniority::Pleno,
      WorkMode::Remoto,
      'https://linkedin.com/in/dev',
      'https://github.com/dev',
      'PHP, MySQL, Docker, php'
    );

    $this->assertTrue($filled->isComplete());
    $this->assertSame(['PHP', 'MySQL', 'Docker'], $filled->topicLabels());
  }

  public function testMatchCalculatorScoresFullPartialAndNullProfile(): void
  {
    $calculator = new MatchCalculator();
    $vacancy = Vacancy::publish(
      VacancyId::generate(),
      VacancyTitle::fromString('Dev Backend PHP'),
      VacancyDescription::fromString('API REST e DDD'),
      VacancyStatus::Active,
      null,
      CompanyId::generate(),
      WorkArea::Backend,
      Seniority::Pleno,
      WorkMode::Remoto,
      ['PHP', 'MySQL', 'Docker', 'DDD']
    );

    $nullMatch = $calculator->calculate(null, $vacancy);
    $this->assertSame(0, $nullMatch->score);
    $this->assertSame(['PHP', 'MySQL', 'Docker', 'DDD'], $nullMatch->missingTopics);
    $this->assertSame('low', $nullMatch->level());

    $perfectCandidate = CandidateProfile::define(
      UserId::generate(),
      'Backend Engineer',
      'Bio',
      WorkArea::Backend,
      Seniority::Pleno,
      WorkMode::Remoto,
      '',
      '',
      'php, mysql, docker, ddd, redis'
    );
    $perfectMatch = $calculator->calculate($perfectCandidate, $vacancy);
    $this->assertSame(100, $perfectMatch->score);
    $this->assertSame(['PHP', 'MySQL', 'Docker', 'DDD'], $perfectMatch->matchedTopics);
    $this->assertSame([], $perfectMatch->missingTopics);
    $this->assertSame('high', $perfectMatch->level());

    $partialCandidate = CandidateProfile::define(
      UserId::generate(),
      'Fullstack Dev',
      'Bio',
      WorkArea::Fullstack,
      Seniority::Junior,
      WorkMode::Hibrido,
      '',
      '',
      'PHP, JavaScript, MySQL'
    );
    $partialMatch = $calculator->calculate($partialCandidate, $vacancy);
    $this->assertGreaterThanOrEqual(50, $partialMatch->score);
    $this->assertLessThan(90, $partialMatch->score);
    $this->assertSame(['PHP', 'MySQL'], $partialMatch->matchedTopics);
    $this->assertSame(['Docker', 'DDD'], $partialMatch->missingTopics);
    $this->assertSame('medium', $partialMatch->level());
  }
}
