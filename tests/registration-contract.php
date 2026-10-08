<?php
declare(strict_types=1);
// Run only against a disposable PostgreSQL instance (see docs). Never production.
$root=dirname(__DIR__,3);
require dirname(__DIR__).'/vendor/autoload.php';
require $root.'/services/auth-service/src/Application/Registration/AccountProvisioner.php';
use Doctrine\DBAL\{DriverManager,Connection};
use Doctrine\DBAL\Schema\Schema;
use App\Infrastructure\DB\SchemaSqlHelper;
use App\Domain\Settings\AppSettingsService;
use App\Application\Registration\Email\{RegistrationService,RegistrationTransport,RegistrationProblem};
use App\Application\Registration\AccountProvisioner;
use App\Domain\Event\EmailVerificationEventFactory;
use App\Infrastructure\EventPublisher\EmailEventSerializer;
use Symfony\Component\HttpClient\{MockHttpClient,Response\MockResponse};
use Ramsey\Uuid\Uuid;
function check(bool $condition,string $message): void {if(!$condition)throw new RuntimeException($message);}
function reject(callable $call,string $code): void {try{$call();}catch(RegistrationProblem $e){check($e->errorCode===$code,'Expected '.$code.', got '.$e->errorCode);return;}throw new RuntimeException('Expected '.$code);}
$db=DriverManager::getConnection(['driver'=>'pdo_pgsql','host'=>'127.0.0.1','user'=>'postgres','dbname'=>'postgres']);
foreach(['registration_contract','auth_contract'] as $schema){$db->executeStatement("DROP SCHEMA IF EXISTS {$schema} CASCADE");$db->executeStatement("CREATE SCHEMA {$schema}");}
function migration(Connection $db,string $path,string $schema,string $namespace): void {
 $code=file_get_contents($path);$code=str_replace('namespace DoctrineMigrations;','namespace '.$namespace.';',$code);eval(substr($code,5));
 $_ENV['DB_SCHEMA']=$schema;$class=$namespace.'\\'.basename($path,'.php');$m=new $class($db,new Psr\Log\NullLogger());$m->up(new Schema());foreach($m->getSql() as $sql)$db->executeStatement($sql->getStatement(),$sql->getParameters());
}
migration($db,$root.'/services/verification-service/migrations/Version20260330210426.php','registration_contract','TestV1');
migration($db,$root.'/services/verification-service/migrations/Version20261007120000.php','registration_contract','TestV2');
migration($db,$root.'/services/verification-service/migrations/Version20261007170000.php','registration_contract','TestV3');
migration($db,$root.'/services/auth-service/migrations/Version20260608145511.php','auth_contract','TestA1');
migration($db,$root.'/services/auth-service/migrations/Version20261007170000.php','auth_contract','TestA2');
$settings=new class extends AppSettingsService {
 public function __construct(){}
 public function getSignupTtl():int{return 600;}
 public function getCodeExpirySeconds():int{return 300;}
 public function getResendIntervalSeconds():int{return 60;}
 public function getMaxCodeRegenerations():int{return 3;}
 public function getMaxVerificationAttempts():int{return 5;}
 public function getEmailCooldown():int{return 60;}
 public function getIpWindow():int{return 60;}
 public function getIpMaxAttempts():int{return 5;}
};
$accounts=new AccountProvisioner($db,new SchemaSqlHelper('auth_contract'));
$events=[];$accountDown=false;$emailDown=false;
$http=new MockHttpClient(function($method,$url,$options)use($accounts,&$events,&$accountDown,&$emailDown){
 $p=json_decode($options['body'],true);
 if(str_ends_with($url,'email-exists'))return new MockResponse(json_encode(['success'=>true,'exists'=>$accounts->emailExists($p['email'])]));
 if(str_ends_with($url,'complete')){
  if($accountDown)return new MockResponse('{}',['http_code'=>503]);
  try{return new MockResponse(json_encode($accounts->complete($p)));}catch(Symfony\Component\HttpKernel\Exception\ConflictHttpException){return new MockResponse('{}',['http_code'=>409]);}
 }
 $events[]=$p;return new MockResponse('{}',['http_code'=>$emailDown?503:202]);
});
function service(Connection $db,AppSettingsService $settings,MockHttpClient $http): RegistrationService {
 return new RegistrationService($db,new SchemaSqlHelper('registration_contract'),$settings,new RegistrationTransport($http,'http://auth','http://gateway',str_repeat('t',64)),new EmailVerificationEventFactory(),new EmailEventSerializer(),str_repeat('t',64));
}
$s=service($db,$settings,$http);
function payload(string $email,string $role='applicant'): array {return ['requestId'=>Uuid::uuid4()->toString(),'email'=>$email,'name'=>'Тестовый пользователь','password'=>'Test password 123','roleCode'=>$role,'acceptTerms'=>true,'employerType'=>'company','company'=>'Компания Тест'];}
function code(Connection $db,string $id): string {return json_decode($db->fetchOne("SELECT payload FROM registration_contract.registration_outbox WHERE request_id=? AND kind='email' ORDER BY generation DESC LIMIT 1",[$id]),true)['payload']['verification_code'];}
function age(Connection $db,string $id): void {$db->executeStatement("UPDATE registration_contract.signup_requests SET date_time=clock_timestamp()-interval '61 seconds' WHERE request_id=?",[$id]);}
reject(fn()=>$s->start(array_replace(payload('bad@example.invalid'),['acceptTerms'=>false]),'203.0.113.1'),'TERMS_REQUIRED');
reject(fn()=>$s->start(array_replace(payload('bad@example.invalid'),['password'=>'short']),'203.0.113.1'),'INVALID_PASSWORD');
reject(fn()=>$s->start(array_replace(payload('bad@example.invalid','employer'),['company'=>'']),'203.0.113.1'),'INVALID_EMPLOYER');
$p=payload('First@Example.invalid');$r=$s->start($p,'203.0.113.1');$id=$r['requestId'];$original=code($db,$id);
check($r['status']==='pending'&&$r['resendsLeft']===3,'Initial state');
check(!$accounts->emailExists('first@example.invalid'),'Account created before confirmation');
check($s->start($p,'203.0.113.1')['requestId']===$id,'Start not idempotent');
reject(fn()=>$s->start(payload('first@example.invalid'),'203.0.113.2'),'EMAIL_COOLDOWN');
reject(fn()=>$s->resend($id),'RESEND_COOLDOWN');
for($i=0;$i<5;$i++)reject(fn()=>$s->confirm($id,'000000'),$i===4?'CODE_BLOCKED':'WRONG_CODE');
reject(fn()=>$s->confirm($id,$original),'CODE_BLOCKED');
$deadline=$r['expiresAt'];age($db,$id);$r=$s->resend($id);$fresh=code($db,$id);check($fresh!==$original&&$r['attemptsLeft']===5&&$r['expiresAt']===$deadline,'Resend replacement/deadline');
reject(fn()=>$s->confirm($id,$original),'WRONG_CODE');
$emailDown=true;$s->dispatch(20,$id);$eventId=$events[0]['event_id'];
$db->executeStatement("UPDATE registration_contract.registration_outbox SET next_attempt_at=clock_timestamp() WHERE request_id=?",[$id]);$emailDown=false;$s->dispatch(20,$id);
check($events[1]['event_id']===$eventId&&$events[1]['payload']['verification_code']===$fresh,'Technical retry changed code/event');
$accountDown=true;check($s->confirm($id,$fresh)['status']==='creating','Confirmation state');
$s->dispatch(20,$id);check(!$accounts->emailExists($p['email']),'Auth outage unexpectedly created account');
$db->executeStatement("UPDATE registration_contract.signup_requests SET expires_at=clock_timestamp()-interval '1 second' WHERE request_id=?",[$id]);
check($s->confirm($id,$fresh)['status']==='creating','Repeated confirmed request expires');
$db->executeStatement("UPDATE registration_contract.registration_outbox SET next_attempt_at=clock_timestamp() WHERE request_id=?",[$id]);$accountDown=false;$s->dispatch(20,$id);
check($s->status($id)['status']==='ready','Confirmed request does not finish after expiry');
$user=$db->fetchAssociative('SELECT * FROM auth_contract.users WHERE user_uuid=?',[$id]);check(password_verify('Test password 123',$user['password_hash']),'Original password not retained');check($user['role_code']==='applicant'&&$user['phone_number']===null,'Email-only applicant account');
$accountPayload=['requestId'=>$id,'email'=>'first@example.invalid','roleCode'=>'applicant','profile'=>['name'=>'Тестовый пользователь'],'passwordHash'=>$user['password_hash']];$accounts->complete($accountPayload);check((int)$db->fetchOne('SELECT count(*) FROM auth_contract.users WHERE user_uuid=?',[$id])===1,'Duplicate account');
reject(fn()=>$s->cancel($id),'ALREADY_CONFIRMED');reject(fn()=>$s->start(payload('first@example.invalid','employer'),'203.0.113.2'),'EMAIL_EXISTS');
$p=payload('employer@example.invalid','employer');$r=$s->start($p,'203.0.113.2');$id=$r['requestId'];$s->confirm($id,code($db,$id));$s->dispatch(20,$id);$profile=json_decode($db->fetchOne('SELECT profile FROM auth_contract.users WHERE user_uuid=?',[$id]),true);check($profile['company']==='Компания Тест'&&$profile['employerType']==='company','Employer fields lost');
$p=payload('cancel@example.invalid');$r=$s->start($p,'203.0.113.3');$id=$r['requestId'];$old=code($db,$id);$s->cancel($id);$s->cancel($id);reject(fn()=>$s->confirm($id,$old),'CANCELLED');reject(fn()=>$s->resend($id),'CANCELLED');reject(fn()=>$s->start(payload($p['email']),'203.0.113.4'),'EMAIL_COOLDOWN');
$db->executeStatement("UPDATE registration_contract.signup_requests SET created_at=clock_timestamp()-interval '61 seconds' WHERE request_id=?",[$id]);check($s->start(payload($p['email']),'203.0.113.4')['requestId']!==$id,'Cancel cannot restart');
$p=payload('resends@example.invalid');$r=$s->start($p,'203.0.113.5');$id=$r['requestId'];for($i=0;$i<3;$i++){age($db,$id);$s->resend($id);}age($db,$id);reject(fn()=>$s->resend($id),'RESEND_LIMIT');
$db->executeStatement("UPDATE registration_contract.signup_requests SET expires_at=clock_timestamp()-interval '1 second' WHERE request_id=?",[$id]);reject(fn()=>$s->confirm($id,code($db,$id)),'REQUEST_EXPIRED');
$p=payload('clip@example.invalid');$r=$s->start($p,'203.0.113.6');$id=$r['requestId'];age($db,$id);$db->executeStatement("UPDATE registration_contract.signup_requests SET expires_at=clock_timestamp()+interval '100 seconds' WHERE request_id=?",[$id]);$r=$s->resend($id);check(strtotime($r['codeExpiresAt'])<=strtotime($r['expiresAt']),'Code outlives request');
// Two simultaneous resends produce only one new generation.
$p=payload('parallel-resend@example.invalid');$r=$s->start($p,'203.0.113.7');$resendId=$r['requestId'];age($db,$resendId);
$db->close();$resendChildren=[];
for($i=0;$i<2;$i++){
 $pid=pcntl_fork();if($pid===0){
  $child=DriverManager::getConnection(['driver'=>'pdo_pgsql','host'=>'127.0.0.1','user'=>'postgres','dbname'=>'postgres']);
  try{service($child,$settings,new MockHttpClient())->resend($resendId);exit(0);}catch(RegistrationProblem $e){exit($e->errorCode==='RESEND_COOLDOWN'?10:20);}
 }$resendChildren[]=$pid;
}
$outcomes=[];foreach($resendChildren as $pid){pcntl_waitpid($pid,$status);$outcomes[]=pcntl_wexitstatus($status);}sort($outcomes);check($outcomes===[0,10],'Parallel resend bypassed cooldown');
check((int)$db->fetchOne('SELECT resend_attempts FROM registration_contract.signup_requests WHERE request_id=?',[$resendId])===1,'Parallel resend generated multiple codes');
// Concurrent attempts with different emails must share the same atomic IP window.
$db->close();$children=[];
for($i=0;$i<8;$i++){
 $pid=pcntl_fork();if($pid===0){
  $child=DriverManager::getConnection(['driver'=>'pdo_pgsql','host'=>'127.0.0.1','user'=>'postgres','dbname'=>'postgres']);
  $childHttp=new MockHttpClient(fn()=>new MockResponse('{"success":true,"exists":false}'));
  try{service($child,$settings,$childHttp)->start(payload('race'.$i.'@example.invalid'),'198.51.100.1');exit(0);}catch(RegistrationProblem $e){exit($e->errorCode==='IP_LIMIT'?10:20);}
 }
 $children[]=$pid;
}
$passed=0;$limited=0;foreach($children as $pid){pcntl_waitpid($pid,$status);$exit=pcntl_wexitstatus($status);check(in_array($exit,[0,10],true),'Concurrent request failed unexpectedly');$passed+=(int)($exit===0);$limited+=(int)($exit===10);}
check($passed===5&&$limited===3,'Parallel IP window exceeded');
echo "Registration contracts passed: roles, original password, expiry, attempts, resend/cancel, recovery, idempotency, concurrent IP limits.\n";
