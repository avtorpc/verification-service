<?php
declare(strict_types=1);
namespace App\Application\Registration\Email;

use App\Domain\Settings\AppSettingsService;
use App\Infrastructure\DB\SchemaSqlHelper;
use App\Domain\Event\EmailVerificationEventFactory;
use App\Infrastructure\EventPublisher\EmailEventSerializer;
use Doctrine\DBAL\Connection;
use Ramsey\Uuid\Uuid;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/** All state transitions use PostgreSQL row/advisory locks; no check/insert race. */
final class RegistrationService
{
 public function __construct(private Connection $db, private SchemaSqlHelper $schema, private AppSettingsService $settings,
  private RegistrationTransport $transport, private EmailVerificationEventFactory $emails, private EmailEventSerializer $serializer,
  #[Autowire('%env(REGISTRATION_INTERNAL_TOKEN)%')] private string $secret) {}
 private function table(string $name): string { return $this->schema->table($name); }
 private function now(): float { return (float)$this->db->fetchOne('SELECT EXTRACT(EPOCH FROM clock_timestamp())'); }
 private function time(?string $value): float { return $value === null ? 0 : (float)(new \DateTimeImmutable($value))->format('U.u'); }
 private function limits(): array {
  return ['ttl'=>$this->settings->getSignupTtl(), 'code'=>$this->settings->getCodeExpirySeconds(), 'resend'=>$this->settings->getResendIntervalSeconds(),
   'regens'=>$this->settings->getMaxCodeRegenerations(), 'attempts'=>$this->settings->getMaxVerificationAttempts(),
   'email'=>$this->settings->getEmailCooldown(), 'ipWindow'=>$this->settings->getIpWindow(), 'ipMax'=>$this->settings->getIpMaxAttempts()];
 }
 private function problem(int $status,string $code,string $message,array $details=[]): never { throw new RegistrationProblem($status,$code,$message,$details); }
 private function digest(string $id,string $code): string {
  if(strlen($this->secret)<32) throw new \LogicException('Registration credential is not configured');
  return hash_hmac('sha256',$id.':'.$code,$this->secret);
 }
 private function lock(string $name): void { $this->db->fetchOne('SELECT pg_advisory_xact_lock(hashtextextended(:name, 17))',['name'=>$name]); }
 private function row(string $id, bool $lock=false): array {
  if(!Uuid::isValid($id)) $this->problem(400,'INVALID_REQUEST','Некорректная заявка.');
  $r=$this->db->fetchAssociative('SELECT * FROM '.$this->table('signup_requests').' WHERE request_id=:id'.($lock?' FOR UPDATE':''),['id'=>$id]);
  if(!$r || !$r['password_hash'] && !$r['confirmed_at'] && !$r['cancelled_at']) $this->problem(404,'REQUEST_NOT_FOUND','Заявка не найдена. Начните регистрацию заново.');
  return $r;
 }
 private function active(array $r,float $now): void {
  if($r['cancelled_at']) $this->problem(410,'CANCELLED','Регистрация отменена. Начните заново.');
  if($r['confirmed_at']) $this->problem(409,'ALREADY_CONFIRMED','Email уже подтверждён.');
  if($this->time($r['expires_at'])<=$now) $this->problem(410,'REQUEST_EXPIRED','Время регистрации истекло. Начните заново.');
 }
 public function start(array $input,string $ip): array {
  $email=$input['email']??null; $name=$input['name']??null; $password=$input['password']??null; $role=$input['roleCode']??null;
  if(!is_string($email)||strlen($email)>255||!filter_var(trim($email),FILTER_VALIDATE_EMAIL)) $this->problem(422,'INVALID_EMAIL','Введите корректный email.');
  $email=strtolower(trim($email));
  if(!is_string($name)||trim($name)===''||mb_strlen(trim($name))>100) $this->problem(422,'INVALID_NAME','Введите имя, не более 100 символов.');
  if(!is_string($password)||mb_strlen($password)<8||strlen($password)>72||str_contains($password,"\0")) $this->problem(422,'INVALID_PASSWORD','Пароль: не менее 8 символов и не более 72 байт.');
  if(!in_array($role,['applicant','employer'],true)) $this->problem(422,'INVALID_ROLE','Выберите соискателя или работодателя.');
  if(($input['acceptTerms']??false)!==true) $this->problem(422,'TERMS_REQUIRED','Подтвердите согласие на обработку персональных данных.');
  $profile=['name'=>trim($name)];
  if($role==='employer') {
   $type=$input['employerType']??null; $company=$input['company']??null;
   if(!in_array($type,['company','entrepreneur','private'],true)||!is_string($company)||trim($company)===''||mb_strlen(trim($company))>150) $this->problem(422,'INVALID_EMPLOYER','Укажите тип и название работодателя, не более 150 символов.');
   $profile+=['employerType'=>$type,'company'=>trim($company)];
  }
  $id=$input['requestId']??null;
  if(!is_string($id)||!Uuid::isValid($id))$this->problem(400,'INVALID_REQUEST','Некорректная заявка.');
  $existing=$this->db->fetchAssociative('SELECT * FROM '.$this->table('signup_requests').' WHERE request_id=:id',['id'=>$id]);
  $l=$this->limits();
  if($existing){if($existing['email']!==$email||$existing['role_code']!==$role)$this->problem(409,'REQUEST_CONFLICT','Заявка относится к другой регистрации.');return $this->view($existing,$l,$this->now());}
  // Auth owns uniqueness across roles. Fail closed when it cannot be reached.
  if($this->transport->emailExists($email)) $this->problem(409,'EMAIL_EXISTS','Этот email уже занят. Войдите в существующий аккаунт или используйте другой email.');
  $hash=password_hash($password,PASSWORD_BCRYPT,['cost'=>12]); unset($password,$input['password']);
  return $this->db->transactional(function() use($email,$profile,$role,$ip,$hash,$l,$id) {
   $this->lock('signup-ip:'.$ip); $this->lock('signup-email:'.$email);
   $now=$this->now(); $t=$this->table('signup_requests');
   $existing=$this->db->fetchAssociative("SELECT * FROM {$t} WHERE request_id=:id",['id'=>$id]);
   if($existing)return $this->view($existing,$l,$now);
   $recent=$this->db->fetchAssociative("SELECT * FROM {$t} WHERE lower(email)=:email ORDER BY created_at DESC LIMIT 1",['email'=>$email]);
   if($recent && $this->time($recent['created_at'])+$l['email']>$now) $this->problem(429,'EMAIL_COOLDOWN','Подождите перед новой регистрацией.',['retryAfter'=>(int)ceil($this->time($recent['created_at'])+$l['email']-$now)]);
   if($recent && !$recent['cancelled_at'] && ($recent['confirmed_at'] || $this->time($recent['expires_at'])>$now)) $this->problem(409,'ACTIVE_REQUEST','Регистрация уже начата. Продолжите подтверждение или отмените заявку.');
   $count=(int)$this->db->fetchOne("SELECT count(*) FROM {$t} WHERE ip_address=:ip AND created_at > clock_timestamp()-(:window*INTERVAL '1 second')",['ip'=>$ip,'window'=>$l['ipWindow']]);
   if($count >= $l['ipMax']) $this->problem(429,'IP_LIMIT','Слишком много регистраций. Попробуйте позже.',['retryAfter'=>$l['ipWindow']]);
   $this->db->executeStatement("INSERT INTO {$t} (request_id,email,role_code,country_alpha2,status,accept_terms,accept_marketing,ip_address,profile,password_hash,expires_at,created_at) VALUES (:id,:email,:role,'RU','pending',true,false,:ip,CAST(:profile AS JSONB),:hash,clock_timestamp()+(:ttl*INTERVAL '1 second'),clock_timestamp())",
    ['id'=>$id,'email'=>$email,'role'=>$role,'ip'=>$ip,'profile'=>json_encode($profile,JSON_THROW_ON_ERROR),'hash'=>$hash,'ttl'=>$l['ttl']]);
   $this->generate($this->row($id,true),$l,$now,0);
   return $this->view($this->row($id),$l,$now);
  });
 }
 private function generate(array $r,array $l,float $now,int $generation): void {
  do { $code=(string)random_int(100000,999999); $hash=$this->digest($r['request_id'],$code); } while($r['code_hash'] && hash_equals($r['code_hash'],$hash));
  $ttl=(int)floor(min($l['code'],$this->time($r['expires_at'])-$now));
  if($ttl<1) $this->problem(410,'REQUEST_EXPIRED','Время регистрации истекло.');
  $this->db->executeStatement('UPDATE '.$this->table('signup_requests')." SET code_hash=:hash,code_expires_at=LEAST(expires_at,clock_timestamp()+(:ttl*INTERVAL '1 second')),verification_code='',verification_attempts=0,resend_attempts=:generation,date_time=clock_timestamp() WHERE request_id=:id",
   ['id'=>$r['request_id'],'hash'=>$hash,'ttl'=>$ttl,'generation'=>$generation]);
  // Invalidate jobs still waiting for the gateway; already delivered old codes remain invalid.
  $this->db->executeStatement('UPDATE '.$this->table('registration_outbox')." SET state='obsolete',payload='{}' WHERE request_id=:id AND kind='email' AND state='pending'",['id'=>$r['request_id']]);
  $event=$this->serializer->toArray($this->emails->create($r['request_id'],$r['email'],$code,$ttl,max(0,$l['regens']-$generation)));
  $event['payload']['expires_at']=gmdate(DATE_ATOM,(int)floor($now+$ttl));
  $this->queue($r['request_id'],'email',$generation,$event);
 }
 private function queue(string $id,string $kind,int $generation,array $payload): void {
  $this->db->executeStatement('INSERT INTO '.$this->table('registration_outbox').' (event_id,request_id,kind,generation,payload) VALUES (:event,:id,:kind,:generation,CAST(:payload AS JSONB)) ON CONFLICT(request_id,kind,generation) DO NOTHING',
   ['event'=>$payload['event_id']??Uuid::uuid4()->toString(),'id'=>$id,'kind'=>$kind,'generation'=>$generation,'payload'=>json_encode($payload,JSON_THROW_ON_ERROR)]);
 }
 public function resend(string $id): array {
  $l=$this->limits();
  return $this->db->transactional(function()use($id,$l){
   $r=$this->row($id,true);$now=$this->now();$this->active($r,$now);
   if((int)$r['resend_attempts'] >= $l['regens']) $this->problem(429,'RESEND_LIMIT','Повторные отправки исчерпаны.');
   $wait=(int)ceil($this->time($r['date_time'])+$l['resend']-$now);
   if($wait>0)$this->problem(429,'RESEND_COOLDOWN','Подождите перед повторной отправкой.',['retryAfter'=>$wait]);
   $this->generate($r,$l,$now,(int)$r['resend_attempts']+1);
   return $this->view($this->row($id),$l,$now);
  });
 }
 public function confirm(string $id,mixed $code): array {
  if(!is_string($code)||!preg_match('/^[0-9]{6}$/D',$code)) $this->problem(422,'INVALID_CODE_FORMAT','Введите 6 цифр из письма.');
  $l=$this->limits();
  $result=$this->db->transactional(function()use($id,$code,$l){
   $r=$this->row($id,true);$now=$this->now();
   if($r['confirmed_at']) return $this->view($r,$l,$now);
   $this->active($r,$now);
   if($this->time($r['code_expires_at'])<=$now)$this->problem(410,'CODE_EXPIRED','Код истёк. Запросите новый.');
   if((int)$r['verification_attempts']>=$l['attempts'])$this->problem(429,'CODE_BLOCKED','Попытки исчерпаны. Запросите новый код.');
   if(!hash_equals((string)$r['code_hash'],$this->digest($id,$code))){
    $left=$l['attempts']-(int)$r['verification_attempts']-1;
    $this->db->executeStatement('UPDATE '.$this->table('signup_requests').' SET verification_attempts=verification_attempts+1 WHERE request_id=:id',['id'=>$id]);
    // Return the error after commit: throwing here would roll back the attempt counter.
    return new RegistrationProblem($left>0?422:429,$left>0?'WRONG_CODE':'CODE_BLOCKED',$left>0?'Неверный код.':'Попытки исчерпаны. Запросите новый код.',['attemptsLeft'=>max(0,$left)]);
   }
   $this->db->executeStatement('UPDATE '.$this->table('signup_requests')." SET confirmed_at=clock_timestamp(),is_verified=true,status='creating',code_hash=NULL WHERE request_id=:id",['id'=>$id]);
   $this->queue($id,'account',0,['requestId'=>$id,'email'=>$r['email'],'roleCode'=>$r['role_code'],'profile'=>json_decode($r['profile'],true,512,JSON_THROW_ON_ERROR),'passwordHash'=>$r['password_hash']]);
   return $this->view($this->row($id),$l,$now);
  });
  if($result instanceof RegistrationProblem)throw $result;
  return $result;
 }
 public function cancel(string $id): array {
  return $this->db->transactional(function()use($id){
   $r=$this->row($id,true);
   if($r['confirmed_at'])$this->problem(409,'ALREADY_CONFIRMED','Подтверждённую регистрацию отменить нельзя.');
   $this->db->executeStatement('UPDATE '.$this->table('signup_requests')." SET cancelled_at=COALESCE(cancelled_at,clock_timestamp()),status='cancelled',code_hash=NULL,password_hash=NULL WHERE request_id=:id",['id'=>$id]);
   $this->db->executeStatement('UPDATE '.$this->table('registration_outbox')." SET state='obsolete',payload='{}' WHERE request_id=:id AND kind='email' AND state='pending'",['id'=>$id]);
   return ['success'=>true,'requestId'=>$id,'status'=>'cancelled'];
  });
 }
 public function status(string $id): array { return $this->view($this->row($id),$this->limits(),$this->now()); }
 private function view(array $r,array $l,float $now): array {
  $status=$r['status'];
  if(!$r['confirmed_at']&&!$r['cancelled_at']&&$this->time($r['expires_at'])<=$now)$status='expired';
  return ['success'=>true,'requestId'=>$r['request_id'],'email'=>$r['email'],'roleCode'=>$r['role_code'],'status'=>$status,
   'expiresAt'=>gmdate(DATE_ATOM,(int)floor($this->time($r['expires_at']))),'codeExpiresAt'=>gmdate(DATE_ATOM,(int)floor($this->time($r['code_expires_at']))),
   'retryAfter'=>max(0,(int)ceil($this->time($r['date_time'])+$l['resend']-$now)),'resendsLeft'=>max(0,$l['regens']-(int)$r['resend_attempts']),
   'attemptsLeft'=>max(0,$l['attempts']-(int)$r['verification_attempts'])];
 }
 public function dispatch(int $limit=20,?string $requestId=null): int {
  $table=$this->table('registration_outbox');$params=[];$filter='';
  if($requestId!==null){$filter=' AND request_id=:id';$params['id']=$requestId;}
  $jobs=$this->db->fetchAllAssociative("SELECT event_id,request_id FROM {$table} WHERE state='pending' AND next_attempt_at<=clock_timestamp(){$filter} ORDER BY created_at LIMIT ".max(1,min($limit,100)),$params);
  $sent=0;
  foreach($jobs as $job){
   $sent+=(int)$this->db->transactional(function()use($job,$table){
    // Always lock signup first, then outbox, matching confirm/resend/cancel.
    $r=$this->row($job['request_id'],true);
    $e=$this->db->fetchAssociative("SELECT * FROM {$table} WHERE event_id=:id FOR UPDATE",['id'=>$job['event_id']]);
    if(!$e||$e['state']!=='pending'||$this->time($e['next_attempt_at'])>$this->now())return false;
    if($e['kind']==='email'&&($r['cancelled_at']||$r['confirmed_at']||$this->time($r['code_expires_at'])<=$this->now()||(int)$r['resend_attempts']!==(int)$e['generation'])){
     $this->db->executeStatement("UPDATE {$table} SET state='obsolete',payload='{}' WHERE event_id=:id",['id'=>$e['event_id']]);return false;
    }
    $payload=json_decode($e['payload'],true,512,JSON_THROW_ON_ERROR);
    try{
     if($e['kind']==='email')$this->transport->sendEmail($payload);else $this->transport->createAccount($payload);
     if($e['kind']==='account')$this->db->executeStatement('UPDATE '.$this->table('signup_requests')." SET completed_at=clock_timestamp(),status='ready',password_hash=NULL WHERE request_id=:id",['id'=>$r['request_id']]);
     $this->db->executeStatement("UPDATE {$table} SET state='sent',payload='{}',attempts=attempts+1 WHERE event_id=:id",['id'=>$e['event_id']]);return true;
    }catch(RegistrationProblem $problem){
     if($e['kind']==='account'&&$problem->status===409){
      $this->db->executeStatement('UPDATE '.$this->table('signup_requests')." SET status='conflict',password_hash=NULL WHERE request_id=:id",['id'=>$r['request_id']]);
      $this->db->executeStatement("UPDATE {$table} SET state='failed',payload='{}' WHERE event_id=:id",['id'=>$e['event_id']]);return false;
     }
    }catch(\Throwable){ /* Retry same persisted payload, never generate a new code/password. */ }
    $delay=min(60,2**min(6,(int)$e['attempts']+1));
    $this->db->executeStatement("UPDATE {$table} SET attempts=attempts+1,next_attempt_at=clock_timestamp()+(:delay*INTERVAL '1 second') WHERE event_id=:id",['id'=>$e['event_id'],'delay'=>$delay]);return false;
   });
  }
  return $sent;
 }
}
