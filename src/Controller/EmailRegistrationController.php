<?php
declare(strict_types=1);
namespace App\Controller;
use App\Application\Registration\Email\{RegistrationService,RegistrationProblem};
use App\Infrastructure\Http\ClientIpResolver;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\{Request,JsonResponse};
use Symfony\Component\Routing\Attribute\Route;
final class EmailRegistrationController {
 public function __construct(private RegistrationService $service,private ClientIpResolver $ip,
  #[Autowire('%env(WEB_SERVICE_INTERNAL_TOKEN)%')] private string $token, private \Psr\Log\LoggerInterface $logger) {}
 #[Route('/quick-signup',methods:['POST'])]
 #[Route('/check-code-email',methods:['POST'])]
 #[Route('/resend-code-email',methods:['POST'])]
 #[Route('/cancel',methods:['POST'])]
 #[Route('/status',methods:['POST'])]
 public function __invoke(Request $request): JsonResponse {
  if($this->token===''||!hash_equals($this->token,(string)$request->headers->get('X-Web-Service-Token')))return new JsonResponse(['success'=>false,'error'=>['code'=>'FORBIDDEN','message'=>'Доступ только через web-service.']],403);
  try{
   try{$data=$request->toArray();}catch(\Throwable){throw new RegistrationProblem(400,'INVALID_JSON','Некорректный запрос.');}
   $id=is_string($data['requestId']??null)?$data['requestId']:'';
   $path=basename($request->getPathInfo());
   $result=match($path){
    'quick-signup'=>$this->service->start($data,$this->ip->resolve($request)),
    'check-code-email'=>$this->service->confirm($id,$data['verificationCode']??null),
    'resend-code-email'=>$this->service->resend($id),
    'cancel'=>$this->service->cancel($id),
    'status'=>$this->service->status($id),
   };
   // Fast path; the supervised worker also retries after crashes or outages.
   $this->service->dispatch(2,$result['requestId']);
   if($path!=='cancel')$result=$this->service->status($result['requestId']);
   return new JsonResponse($result,200,['Cache-Control'=>'no-store']);
  }catch(RegistrationProblem $e){return new JsonResponse($e->body(),$e->status,['Cache-Control'=>'no-store']+(isset($e->details['retryAfter'])?['Retry-After'=>(string)$e->details['retryAfter']]:[]));}
  catch(\Throwable $e){$this->logger->error('Registration request failed', ['exceptionClass'=>$e::class]);return new JsonResponse(['success'=>false,'error'=>['code'=>'SERVICE_UNAVAILABLE','message'=>'Сервис временно недоступен. Повторите запрос позже.']],503,['Cache-Control'=>'no-store']);}
 }
}
