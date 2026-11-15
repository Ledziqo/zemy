<?php
$path=parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH);
$public=realpath(__DIR__.'/../public');
$candidate=realpath($public.$path);
if($candidate && str_starts_with($candidate,$public.DIRECTORY_SEPARATOR) && is_file($candidate)) {
 $mime=(new finfo(FILEINFO_MIME_TYPE))->file($candidate);
 if(str_ends_with($candidate,'.css'))$mime='text/css';
 if(str_ends_with($candidate,'.js'))$mime='application/javascript';
 header('Content-Type: '.$mime);readfile($candidate);return;
}
$app=require __DIR__.'/e2e-bootstrap.php';
$kernel=$app->make(Illuminate\Contracts\Http\Kernel::class);
$request=Illuminate\Http\Request::capture();$response=$kernel->handle($request);$response->send();$kernel->terminate($request,$response);
