<?php

namespace App\Domain\Shared;

use App\Domain\Exception\InvalidValueException;

enum WorkArea: string
{
  case Backend = 'backend';
  case Frontend = 'frontend';
  case Fullstack = 'fullstack';
  case Mobile = 'mobile';
  case Dados = 'dados';
  case Devops = 'devops';
  case Qa = 'qa';
  case ProdutoDesign = 'produto_design';
  case Seguranca = 'seguranca';
  case Outros = 'outros';

  public static function fromString(string $value): self
  {
    $normalized = strtolower(trim($value));
    $area = self::tryFrom($normalized);
    if ($area === null) {
      throw new InvalidValueException('Área de atuação inválida.');
    }

    return $area;
  }

  public static function fromStored(?string $value): self
  {
    if ($value === null || trim($value) === '') {
      return self::Backend;
    }

    return self::tryFrom(strtolower(trim($value))) ?? self::Outros;
  }

  public function label(): string
  {
    return match ($this) {
      self::Backend => 'Backend',
      self::Frontend => 'Frontend',
      self::Fullstack => 'Fullstack',
      self::Mobile => 'Mobile',
      self::Dados => 'Dados & IA',
      self::Devops => 'DevOps & Cloud',
      self::Qa => 'QA & Testes',
      self::ProdutoDesign => 'Produto & Design',
      self::Seguranca => 'Segurança',
      self::Outros => 'Outros',
    };
  }

  /**
   * Affinity ratio (0.0 to 1.0) between candidate area ($this) and vacancy area ($other).
   */
  public function affinityWith(self $other): float
  {
    if ($this === $other) {
      return 1.0;
    }

    $webFamily = [self::Backend, self::Frontend, self::Fullstack];
    if (
      ($this === self::Fullstack && in_array($other, $webFamily, true))
      || ($other === self::Fullstack && in_array($this, $webFamily, true))
    ) {
      return 0.65;
    }

    return 0.0;
  }

  /**
   * @return array<string,string>
   */
  public static function options(): array
  {
    $options = [];
    foreach (self::cases() as $case) {
      $options[$case->value] = $case->label();
    }

    return $options;
  }

  /**
   * Popular topic suggestions shown as quick-add buttons in forms.
   *
   * @return list<string>
   */
  public static function popularTopics(): array
  {
    return [
      'PHP', 'Laravel', 'JavaScript', 'TypeScript', 'Node.js', 'React', 'Vue.js',
      'MySQL', 'PostgreSQL', 'Docker', 'Python', 'C#', '.NET', 'Java', 'AWS',
      'Git', 'API REST', 'DDD', 'PHPUnit', 'HTML5', 'CSS3', 'SQL',
    ];
  }
}
