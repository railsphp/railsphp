<?php
function vd() {
    $vars = func_get_args();
    call_user_func_array('var_dump', $vars);
}

function vp() {
    echo '<pre>';
    $vars = func_get_args();
    call_user_func_array('var_dump', $vars);
    echo '</pre>';
}

function vde() {
    $vars = func_get_args();
    call_user_func_array('var_dump', $vars);
    exit;
}

function vpe() {
    echo '<pre>';
    call_user_func_array('vd', func_get_args());
    echo '</pre>';
    exit;
}

function st($end = false) {
  static $starttime;
  
  $mtime = microtime(); 
  $mtime = explode(" ",$mtime); 
  $mtime = $mtime[1] + $mtime[0]; 
  
  if (!$end) {
    $starttime = $mtime;
  } else {
    $endtime = $mtime; 
    $totaltime = ($endtime - $starttime); 
    echo $totaltime;
  }
}
function mu() {
  echo 'Memory usage: '.number_to_human_size(memory_get_usage());
}
function number_to_human_size($bytes){ 
	$size = $bytes / 1024; 
	if($size < 1024){ 
		$size = number_format($size, 1); 
		$size .= ' KB'; 
	} else { 
		if($size / 1024 < 1024){ 
				$size = number_format($size / 1024, 1); 
				$size .= ' MB'; 
		} else if ($size / 1024 / 1024 < 1024) { 
				$size = number_format($size / 1024 / 1024, 1); 
				$size .= ' GB'; 
		}  
	} 
	return $size; 
}
