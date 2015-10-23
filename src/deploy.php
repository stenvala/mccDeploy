<?php

namespace mcc\deploy;

class deploy implements deployI {

  private $tasks = array();
  private $vars = array();
  private $extRepos = array();

  public function __construct() {
    $this->vars['keep'] = 5; // how many releases are kept in backup
    $this->vars['bower'] = 'bower';
    $this->vars['composer'] = 'composer';
    $this->vars['uglifycss'] = 'uglifycss';
    $this->vars['scss'] = 'scss';
    $this->vars['displayCommand'] = true;
    $this->vars['sleep'] = 300; // in mu-s sleep always after run command, causes less instability
    $this->vars['preventCreateOnNonEmpty'] = true;
  }

  // new release
  // return finalizing function which changes the symlinks and can be run after everything is done, to minimize the downtime
  // sets variable {{newRelease}} which can be used in other functions
  public function build() {
    $time = date('YmdHis');
    $this->run("mkdir {{path}}/releases/$time");
    $this->cloneGitRepo('{{release}}', "releases/$time");
    // Register new    
    $this->varreg('newRelease', "releases/$time");

    $finalize = function() use ($time) {
      $this->run('rm {{path}}/release');
      $this->run("ln -s {{path}}/releases/$time {{path}}/release");
    };
    foreach ($this->extRepos as $repo) {
      $this->mkdir($repo['dir']);
      $this->cloneGitRepo($repo['remote'], $repo['dir']);
    }
    $this->removeOldReleases();
    return $finalize;
  }

  // init folder structure for a new project
  public function create() {
    $dir = $this->substituteVars('{{path}}');
    if (!is_dir($dir)) {
      $this->run('mkdir {{path}}');
    }
    if ($this->vars['preventCreateOnNonEmpty'] &&
        count(scandir($dir)) != 2) {
      $this->error("Cannot create to non-empty dir.");
    } else {
      $this->run('cd {{path}} rm -rf *');
    }
    $this->run('mkdir {{path}}/releases');
  }

  // perform given task
  public function deploy($task) {
    if (!array_key_exists($task, $this->tasks)) {
      $this->error("Task '$task' does not exist.");
    }
    $t = $this->tasks[$task];
    if (is_array($t)) {
      foreach ($t as $newtask) {
        $this->deploy($newtask);
      }
    } else {
      print colors::color("-- Executing task: $task --", "blue", "white") . "\n";
      $t();
    }
  }

  public function error($str) {
    die(colors::color("ERROR: $str EXITING.", "red", "yellow") . "\n");
  }

  // short-cuts to command line commands
  public function bower($command = 'install', $path = 'release') {
    $this->run("cd {{path}}/$path; {{bower}} $command");
  }

  public function chmod($chmod, $dir) {
    $this->run("chmod $chmod {{path}}/$dir");
  }

  public function cloneGitRepo($repo, $dir) {
    $this->run("cd {{path}}/$dir; git clone --depth=1 $repo .");
  }

  public function composer($command = 'install', $path = 'release') {
    $this->run("cd {{path}}/$path; {{composer}} $command");
  }

  public function cp($from, $to) {
    $this->run("cp -R {{path}}/$from {{path}}/$to");
  }

  public function cssUglify($dir = 'release/css', $file = 'style-min.css') {
    $this->run("cd {{path}}/$dir; {{uglifycss}} *.css > $file");
  }

  public function fetchGitRepo($dir, $branch = 'master') {
    $this->run("cd {{path}}/$dir; git fetch; git rebase origin/$branch");
  }

  public function mkdir($name, $chmod = 755) {
    if (is_array($name)) {
      foreach ($name as $dir) {
        $this->mkdir($dir, $chmod);
      }
      return;
    }
    $var = explode('/', $name);
    $dir = '{{path}}';
    foreach ($var as $d) {
      $dir .= '/' . $d;
      if (!file_exists($this->substituteVars($dir))) { // could be as well symlink and not file
        $this->run("mkdir $dir; chmod $chmod $dir");
      }
    }
    $this->run("cd $dir; chmod $chmod .");
  }

  public function rmdir($dir) {
    $this->run("rm -rf {{path}}/$dir.");
  }

  public function mv($from, $to) {
    $this->run("mv {{path}}/$from {{path}}/$to");
  }

  public function path($value) {
    $this->varreg('path', $value);
  }

  public function removeOldReleases() {
    $files = glob($this->substituteVars("{{path}}/releases/2*"));
    for ($i = 0; $i < count($files); $i++) {
      if ((count($files) - $i - $this->vars['keep'] - 1) < 0) {
        break;
      }
      $this->run('rm -rf ' . $files[$i]);
    }
  }

  // register variables and deployfunctions
  public function repo($dir, $remote) {
    array_push($this->extRepos, array('dir' => $dir, 'remote' => $remote));
  }

  // run all shell commands through, includes automatic variable substitution
  public function run($command) {
    $command = $this->substituteVars($command);
    if ($this->vars['displayCommand']) {
      $commands = explode(';', $command);
      foreach ($commands as $c) {
        print trim($c) . PHP_EOL;
      }
    }
    shell_exec($command);
    if ($this->vars['sleep'] > 0) {
      usleep($this->vars['sleep']);
    }
  }

  public function scss($from, $to) {
    $pattern = $this->substituteVars("{{path}}/$from/*.scss");
    $files = glob($pattern);
    print 'Found ' . count($files) . " .scss files to compile in '$pattern'\n";
    foreach ($files as $file) {
      preg_match('#/([a-zA-Z-_0-9\.]*)(\.scss)$#', $file, $str);
      if (count($str) != 3) {
        $this->error("Cannot run scss command. Error with file '$file'.");
      }
      $fileName = $str[1];
      $this->run("{{scss}} $file {{path}}/$to/$fileName.css");
    }
  }

  public function symlink($real, $link) {
    $this->run("ln -s {{path}}/$real {{path}}/$link");
  }

  public function task($name, $function) {
    $this->tasks[$name] = $function;
  }

  // register variable that can be used as {{VARIABLE_NAME}}
  public function varreg($name, $value) {
    $this->vars[$name] = $value;
  }

  // private  
  private function substituteVars($command) {
    $commandOriginal = $command;
    while (true) {
      preg_match('@({{)([a-zA-Z-]*)(}})@', $command, $ar);
      if (count($ar) == 0) {
        break;
      }
      if (!array_key_exists($ar[2], $this->vars)) {
        $this->error("Variable '{$ar[2]}' does not exist (required in '$commandOriginal').");
      }
      $command = str_replace($ar[1] . $ar[2] . $ar[3], $this->vars[$ar[2]], $command);
    }
    return $command;
  }

}
