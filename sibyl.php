<?php


$time = microtime();
$time = explode(' ', $time);
$time = $time[1] + $time[0];
$start = $time;
ob_start();
if ($_GET[SID] !== ""){
  $sid = $_GET[SID];
}else{
  $sid = "test";
}

if (!file_exists("data/$sid")) {
    mkdir("data/$sid", 0777, true);
}

/*
$path = "/data/$sid/good.txt";
$f = (file_exists($path))? fopen($path, "a+") : fopen($path, "w+");
fwrite($f, $msg);
fclose($f);

$path = "/data/$sid/bad.txt";
$f = (file_exists($path))? fopen($path, "a+") : fopen($path, "w+");
fwrite($f, $msg);
fclose($f);

$path = "/data/$sid/sentence.txt";
$f = (file_exists($path))? fopen($path, "a+") : fopen($path, "w+");
fwrite($f, $msg);
fclose($f);
*/

$gs;
$bs;
$ss;

$graphString = "";

$filter = true;

$words = array();
if ($_GET[TRAINING] == true){
  $good = $_GET[GOOD];
  $bad = $_GET[BAD];
  train($bad, $good, $_GET[perc], $_GET[filter]);
}

$points = 0;


function graphPoint($point){
  global $graphString;
  global $start;
  global $points;
  
  
  
  $points++;
  $back = debug_backtrace();
  $back = array_shift($back);
  
  $time = microtime();
  $time = explode(' ', $time);
  $time = $time[1] + $time[0];
  $now = $time;
  $total_time = round(($now - $start), 4);

  if ($graphString == ""){
    $graphString = '<table border="1"><tr><th>DataPoint</th><th>CPU (%)</th><th>RAM used (%)</th><th>Actual RAM (MB)</th><th>Time since beginning</th><th>Line</th><th>point #</th></tr>';
  }
    $graphString = $graphString."<tr><td>".$point."</td><td>".get_server_cpu_usage()."</td><td>".get_server_memory_usage()."</td><td>".(memory_get_usage()/1048576)."</td><td>$total_time seconds</td><td>".$back['line']."</td><td>$points</td></tr>";
  if ($point == "end"){
    $graphString = $graphString."</table>";
  }
  return;
}



function get_server_memory_usage(){

    $free = shell_exec('free');
    $free = (string)trim($free);
    $free_arr = explode("\n", $free);
    $mem = explode(" ", $free_arr[1]);
    $mem = array_filter($mem);
    $mem = array_merge($mem);
    $memory_usage = $mem[2]/$mem[1]*100;

    return $memory_usage;
}

function get_server_cpu_usage(){

    $load = sys_getloadavg();
    return $load[0];

}


if ($_GET[SID] !== ""){
  $sid = $_GET[SID];
}else{
  $sid = 'test';
}


if ($_GET[METHOD] != ""){
  $method = $_GET[METHOD];
}else{
  $method = "GB";
}


$percentage;
$filter;
function train($bad, $good, $percent, $filt){
  global $percentage;
  global $filter;
  $filter = $filt;
  $percentage = $percent;
  $goodwords[] = [];
  $badwords[] = [];
  $goodwords = findWords($good);
  $badwords = findWords($bad);
  if ($filt == true){
  //print_r(count_sentence_words($good, $bad, findWords($good), findWords($bad)));
  
  //print_r($sentenceArray);
  //print_r($sentenceArray);
  $sentence = $goodwords;
  //print_r($sentence);
  //print_r($sentence);
  learn($sentence, "good");
  $sentence = $badwords;
  learn($sentence, "bad");
  //return $goodwords;
  }else{
    learn($goodwords, "good");
    learn($badwords, "bad");
  }
}

function findWords($string){
  graphPoint("findWords");
  $wordArray = explode(" " , $string);
  $wordArray2 = [];
  foreach ($wordArray as &$word){
    $word = preg_replace('/[^a-z0-9]+/i', "", $word);
    //print($word);
    $wordArray2[] = strtolower($word);
  }
  $uniqueWords = $wordArray2;

  $uniqueWords = array_count_values(array_map('strtolower', $uniqueWords));
  //print_r($uniqueWords);
  arsort($uniqueWords);
  //print_r($uniqueWords);
  return $uniqueWords;
}

function remove_words($array, $string){
  //print_r($array);
  //$sarray = explode(",", $array);
  graphPoint("Removing Words");
  $string = strtolower($string);
  foreach ($array as $word => $value){
    $string = str_replace(" $word ", " ", $string);
  }
  //print_r($string);
  return $string;
}

function extract_unit($string, $start, $end){
  $pos = stripos($string, $start);
  $str = substr($string, $pos);
  $str_two = substr($str, strlen($start));
  $second_pos = stripos($str_two, $end);
  $str_three = substr($str_two, 0, $second_pos);
  $unit = trim($str_three);
  return $unit;
}

function get_words($array, $warray, $count, $persent, $add){
//  print_r($array);
//  print_r($warray);
//  print_r($count);
//  print_r($persent);
//  print_r($add);
//  print("-----------------");
  //$back = debug_backtrace();
  //print_r(array_shift($back));
    //echo is_array($warray);
    //print_r($array);
    global $filter;
    if ($filter == true){
      $keys = array_keys($array);
      $loops = 0;
      $warray2;
      foreach ($array as $key => $value) {
        if ($value/$count * 100 >= $persent & strlen($key) < 5){
          if (inarray($key, $warray) != true & $loops <= 5){
            //echo "in array";
            $warray2[$key] = $value;
            $loops++;
          }
        }
        
      }
      //echo "<br>";
      if ($add == 1){
        $warray = array_sum_identical_keys($warray, $warray2);
        //print_r($warray);
      }else{
        $warray = $warray2;
      }
      return $warray;
    }else{
      return "";
    }
}

function count_sentence_words($str1, $str2, $arr1, $arr2) {
  global $sid;
  global $percentage;
  if ($percentage = ""){
    $percentage = 3;
  }
  $count = substr_count($str1, " ") + substr_count($str2, " ");
  $counta = substr_count($str1, " ");
  $countb = substr_count($str2, " ");
  
  //print($counta." ayy ".$countb);
  //echo "<h2>$counta, $countb - $str1, $str2</h2>";
  $warray = [];
  /*if ($file == 1){
    if (file_exists("data/$sid/sentence.txt")){
      $filedata = file_get_contents("data/$sid/sentence.txt");
    }else{
      file_put_contents("data/$sid/sentence.txt", "");
    }
    
    $farray = findWords($filedata);
    
    //preg_match_all('/(\w+)="([^"]*)"/', $filedata, $farray);
    //print_r($farray);
    
  }*/
  settype($warray, "array");
  //print_r($arr1);
  //if ($file == 1 & sizeof($farray) > 2){
    //$warray = $farray;
  //}
  $warray = get_words($arr2, get_words($arr1, $warray, $countb, $percentage, 0), $counta, $percentage, 1);
  
  learn($warray, "sentence");
  return $warray;
  
}

function file_count_sentence_words() {
  global $sid;
  $warray = [];
  
    if (file_exists("data/$sid/sentence.txt")){
      $filedata = file_get_contents("data/$sid/sentence.txt");
    }else{
      file_put_contents("data/$sid/sentence.txt", "");
    }
    $farray = findWords($filedata);
    
  settype($warray, "array");
  //print_r($arr1);
  $warray = $farray;
  return $warray;
  
}


function inarray($string, $array){
  //print_r($array);
  foreach ($array as $word){
    if ($word == $string) {
      return true;
    }
  }
  return false;
}

function learn($array, $file, $method = "GB"){
  global $sid;
  global $bs;
  global $gs;
  $sid = $_GET[SID];
  if ($sid == ""){
    $sid = "test";
  }
  //print_r($array);
  if (file_exists("data/$sid/$file.txt")){
    $filedata = file_get_contents("data/$sid/$file.txt");
  }else{
    file_put_contents("data/$sid/$file.txt", "");
  }
  
  foreach ($array as $key => $val){
    $line = str_repeat($key." ", $val);
    $filecontents = $filecontents.$line;
  }
  
  if (strpos($filedata,$filecontents) !== false) {
    //echo $filecontents;
  }else{
    $filedata = $filedata.$filecontents;
    echo "putting data to $sid";
    file_put_contents("data/$sid/$file.txt", $filedata);
  } 
  
  if ($file == "good"){
    $gs = $filedata;
  } elseif ($file == "bad"){
    $bs = $filedata;
  }
  
  //print_r($filecontents);
}

function array_sum_identical_keys() {
    $arrays = func_get_args();
    $keys = array_keys(array_reduce($arrays, function ($keys, $arr) { return $keys + $arr; }, array()));
    $sums = array();

    foreach ($keys as $key) {
        $sums[$key] = array_reduce($arrays, function ($sum, $arr) use ($key) { return $sum + @$arr[$key]; });
    }
    return $sums;
}

function get_senarray($string) {
  $uniqueWords = explode(" ", $string);
  $uniqueWords = array_count_values(array_map('strtolower', $uniqueWords));
  return $uniqueWords;
}
/*
echo "<br>";
echo file_get_contents("data/$sid/good.txt");
echo "<br>";
echo file_get_contents("data/$sid/bad.txt");
echo "<br>";
echo file_get_contents("data/$sid/sentence.txt");
*/
function infer($string, $sen){
  global $sid;
  global $gs;
  global $bs;
  
  $sid = $_GET[SID];
  if ($sid == ""){
    $sid = "test";
  }
  
  $string = strtolower($string);
  $count = substr_count($string, " ");
  
  if ($sen){
    $words = explode(" ", remove_words(file_count_sentence_words(), $string));
  }else{
    $words = explode(" ", $string);
  }
  
  //echo"$gs, $bs";
  
  //$gs = file_get_contents("data/$sid/good.txt");
  //$bs = file_get_contents("data/$sid/bad.txt");
  
  //echo "$gs, $bs";
  $good = get_senarray($gs);
  //print_r($good);
  $bad = get_senarray($bs);
  //print_r($bad);
  $scoreg = 0;
  $scoreb = 0;
  $total = 0;
  //print($_GET[SID]);
  //print(file_get_contents("data/$sid/good.txt"));
  //print_r(findWords(file_get_contents("data/$sid/good.txt")));
  //print_r(findWords(file_get_contents("data/$sid/bad.txt")));
  if($gs == ""){
    $gs = file_get_contents("data/$sid/good.txt");
  }
  if ($bs == ""){
    $bs = file_get_contents("data/$sid/bad.txt");
  }
  $sentenceArray = count_sentence_words($gs, $bs, findWords(file_get_contents("data/$sid/good.txt")), findWords(file_get_contents("data/$sid/bad.txt")));
  //print_r($sentenceArray);
  $bad = findWords(remove_words($sentenceArray, $bs));
  $good = findWords(remove_words($sentenceArray, $gs));
  
  foreach ($words as $key => $word){
    //echo "sorting... $word...";
    $word = preg_replace('/[^a-z0-9]+/i', "", $word);
    $i = 1;
    //print_r($good);
    //print_r($bad);
    //print_r($words);
      if (array_key_exists($word, $bad) & array_key_exists($word, $good)){
       //echo "here - both"; 
       $total = $total + (($bad[$word] + $good[$word]));
       $scoreb = $scoreb + $bad[$word];
       $scoreg = $scoreg + $good[$word];
      }elseif (array_key_exists($word, $bad)){
        //echo "here - bad ";
        $scoreb = $scoreb + $bad[$word];
        $total = $total + $bad[$word];
      }elseif(array_key_exists($word, $good)){
        //echo "here - good ";
        $scoreg = $scoreg + $good[$word];
        $total = $total + $good[$word];
    }
  }
  echo "<h1>$scoreg</h1>";
  echo "<h1>$scoreb</h1>";
  $percent = ($scoreg / $total) * 100;
  echo "<h1> $percent similar, and $wordpercent similar in length </h1>";
}
//print_r(train("Advanced placement into a school of higher grade proof-reading is determined by the results of the Promotion Test strictly for class type. Ranging from A class with the best facilities anyone can offer all the way down to F Class which is composed of low dining tables, rotten tatami mats and other worn out facilities. Students can change classes by competing using the Examination Summons Battle system or ESB. Students summon characters with their equivalent test mark scores and use them to compete with other classes.", "Shu's entire world was shattered after a meteorite crashed into Japan, unleashing the lethal Apocalypse Virus. The chaos and anarchy born of the outbreak cost Shu his family and reduced him to a timid, fearful shell of the boy he'd once been. His life took another unexpected turn after a chance encounter with the stunning pop star, Inori. This mysterious beauty introduced Shu to the King's Right Hand: a genetic mutation that allows him to reach into hearts of mortals and turn them into weapons.Shu finds himself caught in the crossfire between those who desperately seek his newfound strength. On one side lurks a clandestine government agency, and on the other, Inori and the spirited band of rebels known as Funeral Parlour. The choice is Shu's to make - and the world is his to change.", 3));


//train("i want to convey my passion for your generosity supporting folks that require assistance with the topic your very own", "based on your artwork from elementary school i would guess you drew panels 1 and 4 and the camera on wayne coyne microphone you look like a pirate", 100, false);

//NOTATION: train(bad (string), good (string), percentage of text needed to qualify a sentence word (1-100), filtering of sentence words (true or false))


//infer("i would guess wayne coyne look like a pirate", false);

//NOTATION: infer(inference text (string), filter out sentences (true or false))
//print($_GET[infer]);
infer($_GET[infer], $_GET[filter])
?>
<html>
  <form action="sibyl.php" method="get">
    <input type="text" name="SID" placeholder="Session Identifyer" value="<?echo $_GET[SID]?>"></br>
    <textarea name="BAD" placeholder="Example of what is bad - if training" rows="20" cols="50"></textarea></br>
    <textarea name="GOOD" placeholder="Example of what is good - if training" rows="20" cols="50"></textarea>
    </br>
    <input type="checkbox" name="TRAINING">Are you planning to train?</br>
    <textarea name="infer" placeholder="What to make an inference off" rows="20" cols="50"></textarea></br>
    <input type="checkbox" name="filter">Automatically remove sentence words? EG The, And, To, For ...</br>
    <input type="text" name="perc" placeholder="percentage of text treated as sentence text"></br>
    <input type="submit">
    <?echo $graphString?>
  </form>
</html>