<?php
declare(strict_types=1);
namespace App\Application\Registration\Email;
final class RegistrationProblem extends \RuntimeException {
 public function __construct(public readonly int $status, public readonly string $errorCode, string $message, public readonly array $details = []) { parent::__construct($message); }
 public function body(): array { return ['success'=>false,'error'=>['code'=>$this->errorCode,'message'=>$this->getMessage()]+$this->details]; }
}
