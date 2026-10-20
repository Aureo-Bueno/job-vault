<?php

namespace App\Application\Features\Profile;

use App\Application\Abstractions\Command;
use App\Application\Abstractions\CommandHandlerInterface;
use App\Application\Commands\Profile\SaveMyCandidateProfileCommand;
use App\Application\DTOs\CandidateProfileDto;
use App\Application\Exceptions\UnauthorizedException;
use App\Application\Security\CurrentUserProvider;
use App\Domain\CandidateProfile\CandidateProfile;
use App\Domain\CandidateProfile\CandidateProfileRepositoryInterface;
use App\Domain\Shared\Seniority;
use App\Domain\Shared\WorkArea;
use App\Domain\Shared\WorkMode;
use App\Domain\User\UserId;
use InvalidArgumentException;

final class SaveMyCandidateProfileHandler implements CommandHandlerInterface
{
  public function __construct(
    private CandidateProfileRepositoryInterface $profiles,
    private CurrentUserProvider $currentUser
  ) {
  }

  public function commandClass(): string
  {
    return SaveMyCandidateProfileCommand::class;
  }

  public function handle(Command $command): CandidateProfileDto
  {
    if (!$command instanceof SaveMyCandidateProfileCommand) {
      throw new InvalidArgumentException('Invalid command for ' . self::class);
    }

    $logged = $this->currentUser->currentUser();
    if ($logged === null) {
      throw new UnauthorizedException('Você precisa entrar para continuar.');
    }

    $profile = CandidateProfile::define(
      UserId::fromString($logged->id),
      $command->headline,
      $command->bio,
      WorkArea::fromString($command->area),
      Seniority::fromString($command->seniority),
      WorkMode::fromString($command->workMode),
      $command->linkedinUrl,
      $command->githubUrl,
      $command->topics
    );

    $this->profiles->save($profile);

    return CandidateProfileDto::fromProfile($profile);
  }
}
