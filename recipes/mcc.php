<?php

$dep->varreg('composer', '/home/stenvala/Dropbox/Antti_private/bin/composer.phar');
$dep->varreg('keep', 5);

// set external repos and where to clone them
$dep->repo('{{newRelease}}/js-mcc','git://github.com/mathCodingClub/js-mcc.git');
$dep->repo('{{newRelease}}/php/mcc','git://github.com/mathCodingClub/mcc-php-backend.git');

// init new server
$dep->task('init', function() use ($dep) {    
  $dep->create();
  $dep->mkdir(array('cache','uploads'), 777);  
  $dep->run('cp -R {{devdir}}/uploads/* {{path}}/uploads');    
  $dep->mkdir(array('extra','fig','files'));    
  $dep->deploy('copy');  
  $dep->deploy('release');  
});

// copy data that is external to release from dev to live
$dep->task('copy',function() use ($dep){  
  $dep->run('cp -R {{devdir}}/extra/* {{path}}/extra');  
  $dep->run('cp -R {{devdir}}/fig/* {{path}}/fig');  
  $dep->run('cp -R {{devdir}}/files/* {{path}}/files');
});

// copy uploaded files to devdir
$dep->task('uploadToDev',function() use ($dep){  
  $dep->run('cp -R {{path}}/uploads/* {{devdir}}/uploads');    
});

// release
$dep->task('release', function() use ($dep) {
  $finalize = $dep->build();

  $dep->run('cd {{path}}/{{newRelease}}/js; php minimize.php');

  $dep->symlink('uploads', '{{newRelease}}/uploads');
  $dep->symlink('cache', '{{newRelease}}/cache');
  $dep->symlink('extra', '{{newRelease}}/extra');
  $dep->symlink('fig', '{{newRelease}}/fig');
  $dep->symlink('files', '{{newRelease}}/files');

  $dep->run('cd {{path}}/{{newRelease}}/js-mcc; php minimize.php');  
  $dep->chmod(777,'{{newRelease}}/php/mcc/obj/cache/files');
  
  $dep->mkdir('{{newRelease}}/css'); // css is not part of git repository

  $dep->scss('{{newRelease}}/js-mcc/scss', '{{newRelease}}/css');  
  $dep->scss('{{newRelease}}/scss', '{{newRelease}}/css');  
  $dep->cssUglify('{{newRelease}}/css');  

  $dep->composer('install','{{newRelease}}');
  $dep->bower('install','{{newRelease}}');
  
  $finalize();
});

// just fetch, remember depth is one
$dep->task('pull', function() use ($dep) {    
  $dep->run("cd {{path}}/release; git pull");  
  $dep->scss('{{path}}/release/scss', '{{path}}/release/css');  
});