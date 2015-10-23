<?php

namespace mcc\deploy;

interface deployI {

  // Build new release (includes cloning git repositories, making new release folder and removing old)
  // Returns finalize closure that is called to take the new release to use after all the other init actions
  public function build();
  
  // Creates structure for new project
  public function create();

  // Deploy given task
  public function deploy($task);
  
  // Displays error and dies
  public function error($str);
  
  // Calls bower
  public function bower($command = 'install', $path = 'release');
    
  public function chmod($chmod, $dir);
  
  // Clones git repo to depth 1
  public function cloneGitRepo($repo, $dir);
 
  public function composer($command = 'install', $path = 'release');
  
  public function cp($from, $to);
  
  public function cssUglify($dir = 'release/css', $file = 'style-min.css');
  
  public function fetchGitRepo($dir, $branch = 'master');
  
  // Recursively make given dir
  public function mkdir($name, $chmod = 755);
 
  public function rmdir($dir);
  
  public function mv($from, $to);
  
  // Short cut to register {{path}} variable for the root of the service
  public function path($value);
  
  public function removeOldReleases();
  
  // Register other repositories to specific directories
  public function repo($dir, $remote);
  
  // Run all shell commands through, includes automatic variable substitution
  public function run($command);
  
  // $from and $to are directories
  public function scss($from, $to);
  
  public function symlink($real, $link);
  
  // Register new task to be run with Deploy ($function can also be array of task names)
  public function task($name, $function);
  
  // Register variable that can be used as {{VARIABLE_NAME}}
  // You can override the variables set in constructor also
  public function varreg($name, $value);
  
}
