<?php
require __DIR__.'/../vendor/autoload.php';
$root = realpath(__DIR__.'/..');
$private = $root.'/tmp/e2e';
foreach (['storage/framework/views','storage/framework/cache/data','storage/framework/sessions','storage/logs','storage/app','cache'] as $dir) if (!is_dir($private.'/'.$dir)) mkdir($private.'/'.$dir,0777,true);
putenv('APP_ENV=testing');putenv('APP_DEBUG=true');putenv('APP_URL=http://127.0.0.1:8766');
putenv('APP_CONFIG_CACHE='.$private.'/no-config.php');putenv('APP_ROUTES_CACHE='.$private.'/no-routes.php');
$app = require $root.'/bootstrap/app.php';
$app->useStoragePath($private.'/storage');
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
config([
 'app.env'=>'testing','app.debug'=>true,'app.url'=>'http://127.0.0.1:8766',
 'database.default'=>'sqlite','database.connections.sqlite'=>['driver'=>'sqlite','database'=>$private.'/test.sqlite','prefix'=>'','foreign_key_constraints'=>true,'busy_timeout'=>5000],
 'cache.default'=>'array','cache.stores.file.path'=>$private.'/cache',
 'session.lifetime'=>525600,'session.driver'=>'file','session.files'=>$private.'/storage/framework/sessions','session.cookie'=>'zemtab_e2e_session','session.secure'=>false,'session.domain'=>null,'session.expire_on_close'=>true,
 'view.compiled'=>$private.'/storage/framework/views','mail.default'=>'array',
]);
if(is_file($private.'/clock.json')) { $clock=json_decode(file_get_contents($private.'/clock.json'),true); Illuminate\Support\Carbon::setTestNow($clock['now']); Carbon\CarbonImmutable::setTestNow($clock['now']); }
return $app;
