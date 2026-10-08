<?php
declare(strict_types=1);
namespace App\Command;
use App\Application\Registration\Email\RegistrationService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
#[AsCommand(name:'app:registration:dispatch',description:'Retry durable email/account jobs without regenerating credentials')]
final class RegistrationDispatchCommand extends Command {
 public function __construct(private RegistrationService $registrations){parent::__construct();}
 protected function execute(InputInterface $input,OutputInterface $output): int { $output->writeln('Delivered jobs: '.$this->registrations->dispatch());return Command::SUCCESS; }
}
