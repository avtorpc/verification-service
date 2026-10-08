<?php
declare(strict_types=1);
namespace App\Application\Registration\Email;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
final class RegistrationTransport {
 public function __construct(private HttpClientInterface $http,
  #[Autowire('%env(AUTH_SERVICE_URL)%')] private string $authUrl,
  #[Autowire('%env(KAFKA_GATEWAY_URL)%')] private string $gateway,
  #[Autowire('%env(REGISTRATION_INTERNAL_TOKEN)%')] private string $token) {}
 private function auth(string $path,array $data): array {
  if(strlen($this->token)<32)throw new \LogicException('Registration credential is not configured');
  $response=$this->http->request('POST',rtrim($this->authUrl,'/').'/registration/'.$path,
   ['headers'=>['X-Registration-Token'=>$this->token],'json'=>$data,'timeout'=>3,'max_duration'=>5,'max_redirects'=>0]);
  $status=$response->getStatusCode();
  if($status===409)throw new RegistrationProblem(409,'EMAIL_EXISTS','Этот email уже занят. Войдите в существующий аккаунт или используйте другой email.');
  if($status>=300)throw new RegistrationProblem(503,'AUTH_UNAVAILABLE','Сервис регистрации временно недоступен. Попробуйте позже.');
  return $response->toArray(false);
 }
 public function emailExists(string $email): bool { return (bool)($this->auth('email-exists',['email'=>$email])['exists']??false); }
 public function createAccount(array $data): void { $this->auth('complete',$data); }
 public function sendEmail(array $event): void {
  $response=$this->http->request('POST',rtrim($this->gateway,'/').'/kafka/mail-send',['json'=>$event,'timeout'=>3,'max_duration'=>5,'max_redirects'=>0]);
  if($response->getStatusCode()>=300)throw new \RuntimeException('Email event gateway unavailable');
 }
}
