<?php
require dirname(__DIR__,3).'/services/verification-service/vendor/autoload.php';
require dirname(__DIR__,3).'/services/dictionaries-service/migrations/Version20261007120000.php';
function ok($condition,$message) { if (!$condition) throw new RuntimeException($message); }
$db=Doctrine\DBAL\DriverManager::getConnection(['driver'=>'pdo_pgsql','host'=>'127.0.0.1','user'=>'postgres','dbname'=>'postgres']);
$db->executeStatement('CREATE SCHEMA dictionary_test');
$db->executeStatement('CREATE TABLE dictionary_test.application_settings (setting_key TEXT PRIMARY KEY, value TEXT, min_value TEXT, max_value TEXT, description TEXT)');
foreach(['code_expiry_seconds','resend_interval_seconds','max_code_regenerations','max_verification_attempts','active_signup_ttl_seconds'] as $key) $db->insert('dictionary_test.application_settings',['setting_key'=>$key,'value'=>'0']);
$_ENV['DB_SCHEMA']='dictionary_test';
$m=new DoctrineMigrations\Version20261007120000($db,new Psr\Log\NullLogger());
$m->up(new Doctrine\DBAL\Schema\Schema());
foreach($m->getSql() as $q) $db->executeStatement($q->getStatement(),$q->getParameters());
$values=$db->fetchAllKeyValue('SELECT setting_key,value FROM dictionary_test.application_settings');
foreach(['code_expiry_seconds'=>300,'resend_interval_seconds'=>60,'max_code_regenerations'=>3,'max_verification_attempts'=>5,'active_signup_ttl_seconds'=>600] as $k=>$v) ok((int)$values[$k]===$v,'Wrong setting '.$k);
echo "PASS: agreed dictionary registration settings.\n";
