<?php

namespace App\Presentation\Support;

use App\Domain\Shared\BrazilianState;
use App\Domain\Shared\Seniority;
use App\Domain\Shared\WorkArea;
use App\Domain\Shared\WorkMode;
use App\Domain\Vacancy\ContractType;
use App\Domain\Vacancy\SalaryRange;
use App\Domain\Vacancy\VacancyDeadline;

/**
 * Shared pieces of the vacancy create/edit pages: select options and POST parsing.
 */
final class VacancyForm
{
  public const POPULAR_BENEFITS = [
    'Plano de saúde',
    'Plano odontológico',
    'Vale-refeição',
    'Vale-alimentação',
    'Vale-transporte',
    'Auxílio home office',
    'PLR',
    'Gympass',
    'Auxílio educação',
    'Day off no aniversário',
  ];

  private function __construct()
  {
  }

  /**
   * View variables common to both pages.
   *
   * @return array<string,mixed>
   */
  public static function options(): array
  {
    return [
      'areas' => WorkArea::options(),
      'seniorities' => Seniority::options(),
      'workModes' => WorkMode::options(),
      'popularTopics' => WorkArea::popularTopics(),
      'contractTypes' => ContractType::options(),
      'states' => BrazilianState::options(),
      'popularBenefits' => self::POPULAR_BENEFITS,
      'today' => VacancyDeadline::today(),
    ];
  }

  /**
   * Copies the posted fields into the form model (what the user typed is shown back on errors).
   */
  public static function fillFromPost(object $vacancy, array $post): void
  {
    $vacancy->title = (string) ($post['title'] ?? '');
    $vacancy->description = (string) ($post['description'] ?? '');
    $vacancy->status = (string) ($post['vacancy_status'] ?? $vacancy->status);
    $vacancy->positions = (int) ($post['positions'] ?? $vacancy->positions);
    $vacancy->area = (string) ($post['area'] ?? 'backend');
    $vacancy->seniority = (string) ($post['seniority'] ?? 'pleno');
    $vacancy->workMode = (string) ($post['work_mode'] ?? 'remoto');
    $vacancy->rawTopics = (string) ($post['topics'] ?? '');
    $vacancy->topics = self::splitList($vacancy->rawTopics);
    $vacancy->contractType = (string) ($post['contract_type'] ?? 'clt');
    $vacancy->salaryMin = trim((string) ($post['salary_min'] ?? ''));
    $vacancy->salaryMax = trim((string) ($post['salary_max'] ?? ''));
    $vacancy->city = trim((string) ($post['city'] ?? ''));
    $vacancy->state = trim((string) ($post['state'] ?? ''));
    $vacancy->rawBenefits = (string) ($post['benefits'] ?? '');
    $vacancy->benefits = self::splitList($vacancy->rawBenefits);
    $vacancy->closesAt = trim((string) ($post['closes_at'] ?? ''));
  }

  /**
   * Salary text field -> whole reais (throws InvalidValueException on garbage).
   */
  public static function salary(mixed $value): ?int
  {
    return SalaryRange::parseAmount(is_scalar($value) ? (string) $value : null);
  }

  /**
   * @return list<string>
   */
  private static function splitList(string $raw): array
  {
    return array_values(array_filter(array_map('trim', explode(',', $raw)), static fn (string $t): bool => $t !== ''));
  }
}
