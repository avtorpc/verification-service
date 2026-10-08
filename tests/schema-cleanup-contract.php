<?php

declare(strict_types=1);

require dirname(__DIR__).'/vendor/autoload.php';

use Doctrine\DBAL\{DriverManager, Tools\DsnParser};
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

$schema=getenv('DB_SCHEMA');
if(!in_array($schema,['auth','verification'],true))throw new RuntimeException('Expected auth/verification DB_SCHEMA');
$service=$schema==='auth'?'auth-service':'verification-service';
$kept=$schema==='auth'?['refresh_tokens','users']:['registration_outbox','signup_requests'];
$old=$schema==='auth'?['users_companies_link']:['companies','company_addresses','company_bank_details','company_contacts','company_leaders','registration_progress','verification_users'];
$url=getenv('DATABASE_URL');
if(!is_string($url)||$url==='')throw new RuntimeException('DATABASE_URL required');
$params=(new DsnParser(['postgresql'=>'pdo_pgsql','postgres'=>'pdo_pgsql']))->parse($url);
$admin=DriverManager::getConnection($params);
function migrate(Application $app,?string $version=null):void {
 $args=['command'=>'doctrine:migrations:migrate','--no-interaction'=>true];
 if($version!==null)$args['version']=$version;
 $input=new ArrayInput($args);$input->setInteractive(false);$output=new BufferedOutput();
 if($app->run($input,$output)!==0)throw new RuntimeException($output->fetch());
}
function fixture(Doctrine\DBAL\Connection $db,string $schema,string $table,array $values=[]):void {
 $cols=$db->fetchAllAssociative("SELECT column_name,data_type FROM information_schema.columns WHERE table_schema=? AND table_name=? AND is_nullable='NO' AND column_default IS NULL AND is_identity='NO'",[$schema,$table]);
 foreach($cols as $col){
  $key=$col['column_name'];if(array_key_exists($key,$values))continue;
  $values[$key]=match($col['data_type']){
   'uuid'=>'00000000-0000-4000-8000-000000000001',
   'bigint','integer'=>1,
   'boolean'=>true,
   'timestamp without time zone','timestamp with time zone'=>'2026-10-08 00:00:00',
   'jsonb'=>'{}',
   default=>'RU',
  };
 }
 $db->insert($schema.'.'.$table,$values);
}
function snapshot(Doctrine\DBAL\Connection $db,string $schema,array $tables):array {
 $result=[];
 foreach($tables as $table)$result[$table]=$db->fetchFirstColumn("SELECT to_jsonb(t)::text FROM {$schema}.{$table} t ORDER BY to_jsonb(t)::text");
 return $result;
}
foreach(['fresh','upgrade'] as $mode){
 $name=$schema.'_cleanup_contract_'.bin2hex(random_bytes(5));$kernel=null;$db=null;
 try{
  $admin->executeStatement('CREATE DATABASE '.$name);
  $targetParams=$params;$targetParams['dbname']=$name;$db=DriverManager::getConnection($targetParams);
  $db->executeStatement('CREATE SCHEMA '.$schema);
  $newUrl=preg_replace('~/[^/?]+(?=\?|$)~','/'.$name,$url,1);
  $_SERVER['DATABASE_URL']=$_ENV['DATABASE_URL']=$newUrl;
  $_SERVER['DB_SCHEMA']=$_ENV['DB_SCHEMA']=$schema;
  putenv('DATABASE_URL='.$newUrl);putenv('DB_SCHEMA='.$schema);
  $kernel=new App\Kernel('prod',false);$app=new Application($kernel);$app->setAutoExit(false);$app->setCatchExceptions(false);
  $before=null;
  if($mode==='upgrade'){
   migrate($app,'DoctrineMigrations\\Version20261007170000');
   if($schema==='verification'){
    fixture($db,$schema,'signup_requests',['request_id'=>'00000000-0000-4000-8000-000000000001','email'=>'preserve@example.invalid','role_code'=>'applicant','status'=>'pending','accept_terms'=>true]);
    fixture($db,$schema,'registration_outbox',['event_id'=>'00000000-0000-4000-8000-000000000002','request_id'=>'00000000-0000-4000-8000-000000000001','kind'=>'email','generation'=>0,'payload'=>'{"preserve":true}']);
   }else{
    fixture($db,$schema,'refresh_tokens');
   }
   foreach($old as $table)fixture($db,$schema,$table);
   $before=snapshot($db,$schema,$kept);
  }
  migrate($app);migrate($app);
  $tables=$db->fetchFirstColumn("SELECT tablename FROM pg_tables WHERE schemaname=? AND tablename NOT LIKE 'doctrine_migration_versions%' ORDER BY tablename",[$schema]);
  if($tables!==$kept)throw new RuntimeException('Unexpected tables: '.json_encode($tables));
  if($before!==null&&$before!==snapshot($db,$schema,$kept))throw new RuntimeException('Working data changed during cleanup');
  echo "PASS {$service}: {$mode}, final table set, repeated migration".($before!==null?', nonempty legacy tables removed, working data preserved':'')."\n";
 }finally{
  if($kernel!==null)$kernel->shutdown();
  if($db!==null)$db->close();
  $admin->executeStatement('DROP DATABASE IF EXISTS '.$name.' WITH (FORCE)');
 }
}
$admin->close();
