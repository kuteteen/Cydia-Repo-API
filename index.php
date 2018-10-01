<?php
header('Access-Control-Allow-Origin: *');
header("content-type: application/json");


ini_set('memory_limit','300M');




function getContents($url) {
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch,CURLOPT_USERAGENT,'Telesphoreo APT-HTTP/1.0.592');

  curl_setopt($ch, CURLOPT_URL,$url);
  curl_setopt($ch, CURLOPT_HEADER, 1);
  $result=curl_exec($ch);

  $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
  $header = substr($result, 0, $header_size);
  $body = substr($result, $header_size);

  curl_close($ch);
  return array($header, $body);
}



function cleanUp($data) {
  $removeN = str_replace("\n", "", $data);
  $removeR = str_replace("\n", "", $removeN);
  return $removeR;
}

function convertRepo2($r, $p, $u, $uoverwrite = false) {

  $info = $r;

  if($uoverwrite) {
    $parseurl = parse_url($u);
    $imageurl = $u;
    $u = $parseurl['scheme']."://".$parseurl['host']."/";

  } else {
    $imageurl = $u;
  }

  $parseurl = parse_url($u);
  if($parseurl['host'] == "apt.saurik.com" || $parseurl['host'] == "cydia.zodttd.com" || $parseurl['host'] == "apt.thebigboss.org" || $parseurl['host'] == "apt.modmyi.com") {
    $default = true;
  }

  $info['icon'] =  $imageurl ."CydiaIcon.png";

  $packs = preg_split("#\n\s*\n#Uis", $p);
  $pacarr = array();
  foreach($packs as $package) {
    $pack = explode(PHP_EOL, $package);

    $groupPack = array();
    foreach($pack as $el) {
      if(!$el == "") {

      $el2 = explode(": ", $el);

      if(isset($el2[1])) {
        if($el2[0] == "Filename") {

          $groupPack[$el2[0]] = $u . "". $el2[1];
        } else {
          $groupPack[$el2[0]] = $el2[1];
        }

      } else {
        $groupPack[$el2[0]] = null;
      }

    }
    }
    if (isset($groupPack['Package'])) {

    $pac = [];
    foreach($groupPack as $k=>$pa) {

      $pac[cleanUp(mb_convert_encoding($k, "UTF-8", "UTF-8"))] = cleanUp(mb_convert_encoding($pa, "UTF-8", "UTF-8"));
    }

    if(!isset($_GET['extended'])) {
      unset($pac['SHA256']);
      unset($pac['SHA1']);
      unset($pac['MD5sum']);
      unset($pac['Architecture']);
      unset($pac['Tag']);
    }

    if($pac['Name'] == null) {
      $pac['Name'] = $pac['Package'];
    }


    if($default) {
      $pac['Icon'] = "http://cydia.saurik.com/icon@2x/".$pac['Package'].".png";
    }



    ksort($pac);
    $pacarr[] = $pac;


    }



  }

  $packages = [];
  foreach($pacarr as $pa) {
    $packages[cleanUp(mb_convert_encoding($pa['Package'], "UTF-8", "UTF-8"))][] = $pa;
  }

  $info["package_count"] = count($packages);



  $sections = array();
  foreach($packages as $i=>$pack) {

    $sections[] = $pack[0]['Section'];
  }
  $sections = array_unique($sections);
  $sections2 = array();
  foreach($sections as $section) {
    $sections2[] = $section;
  }

  $sections_count = array();
  foreach($sections2 as $section) {
    foreach($packages as $package) {
      if($package[0]['Section'] == $section) {
        $sections_count[$section][] = $package;
      }
    }
  }

  $sections_count2 = array();
  foreach($sections_count as $section=>$ps) {
    $sections_count2[$section] = count($ps);
  }
  ksort($sections_count2);


  $arr = array("status" => null, "info" => $info, "section_count" => count($sections), "sections" => $sections_count2, "packages" => $packages);
  if($arr['info']['Version'] != null) {
    $arr['status'] = true;
  } else {
    $arr['status'] = false;
  }
  return $arr;
}




function deleteTemps($folder) {
  $files = glob($folder."/*");
  if(count($files) > 0) {
    foreach($files as $file) {
      unlink($file);
    }
  }

  rmdir($folder);
}

function getPackages($repo) {


  $requestPackagesBZ2 = getStatusCode($repo ."Packages.bz2");
  if($requestPackagesBZ2 == 200) {
    $id = microtime();
    mkdir("tmp/".md5($id));
    $file = "tmp/".md5($id)."/".md5($id+1).".bz2";

    file_put_contents($file, file_get_contents($repo ."Packages.bz2"));

    $bz = bzopen($file, "r") or die("Couldn't open $file");

    $package = '';
    while (!feof($bz)) {
      $package .= bzread($bz, 4096);
    }
    bzclose($bz);

    deleteTemps("tmp/".md5($id));



    return $package;


  } else if($requestPackagesBZ2 == 302) {


  } else if($requestPackagesBZ2 == 404) {
    $requestPackagesGZ = getStatusCode($repo ."Packages.gz");
    if($requestPackagesGZ == 200) {

      mkdir("tmp/".md5($id));
      $file = "tmp/".md5($id)."/".md5($id+1).".gz";
      file_put_contents($file, file_get_contents($repo."Packages.gz"));

      $package = '';
      foreach(gzfile($file) as $line) {
        $package .= $line;
      }


      return $package;

    } else if($requestPackagesGZ == 404) {
      $requestPackages = getStatusCode($repo ."Packages");
      if($requestPackages == 200) {

        return file_get_contents($repo."Packages");

      } else if($requestPackages == 404) {
        return false;
      }
    }
  }


}

function searchPackage($q, $json) {
  $data = json_decode($json, true);
  $arr = array("status" => null, "count" => null, "query" => $q, "results" => array());
  foreach($data['packages'] as $i=>$pack) {
    if (stripos(strtolower($pack[0]['Name']), $q) !== false) {
      $arr['results'][$i] = $pack;
    }
  }
  $arr['count'] = count($arr['results']);
  if($arr['count'] != null || $arr['count'] != 0) {
    $arr['status'] = true;
  } else {
    $arr['status'] = false;
  }
  return json_encode($arr, JSON_PRETTY_PRINT);


}

function getPackagesBySection($s, $json) {
  $data = json_decode($json, true);
  $arr = array("repo" => $data['info']['Label'], "status" => null, "count" => null, "section" => $s, "packages" => array());
  foreach($data['packages'] as $i=>$pack) {
    if (stripos(strtolower($pack[0]['Section']), $s) !== false) {
      $arr['packages'][$i] = $pack;
    }
  }
  $arr['count'] = count($arr['packages']);
  if($arr['count'] != null || $arr['count'] != 0) {
    $arr['status'] = true;
  } else {
    $arr['status'] = false;
  }
  return json_encode($arr, JSON_PRETTY_PRINT);
}

function getPackagesById($id, $json) {
  $data = json_decode($json, true);
  $arr = array("repo" => $data['info']['Label'], "status" => null, "id" => $id, "package" => array());
  if(array_key_exists($id, $data['packages'])) {

    $arr['status'] = true;
    $arr['package'] = $data['packages'][$id];
  } else {
    $arr['status'] = false;
  }
  return json_encode($arr, JSON_PRETTY_PRINT);
}

function getReleaseOnly($json) {
  $data = json_decode($json, true);
  if($data['status']) {
    $data['info']['status'] = true;
    return json_encode($data['info'], JSON_PRETTY_PRINT);
  } else {
    return "something wrong with api";
  }
}



function btw($str, $startDelimiter, $endDelimiter) {
  $contents = array();
  $startDelimiterLength = strlen($startDelimiter);
  $endDelimiterLength = strlen($endDelimiter);
  $startFrom = $contentStart = $contentEnd = 0;
  while (false !== ($contentStart = strpos($str, $startDelimiter, $startFrom))) {
    $contentStart += $startDelimiterLength;
    $contentEnd = strpos($str, $endDelimiter, $contentStart);
    if (false === $contentEnd) {
      break;
    }
    $contents[] = substr($str, $contentStart, $contentEnd - $contentStart);
    $startFrom = $contentEnd + $endDelimiterLength;
  }

  return $contents;
}

function validateUrl($url) {
  $url = filter_var($url, FILTER_SANITIZE_URL);
  if (filter_var($url, FILTER_VALIDATE_URL)) {
    return true;
  } else {
    return false;
  }
}


function getStatusCode($url) {
  $ch = curl_init($url);
  curl_setopt($ch, CURLOPT_HEADER, true);
  curl_setopt($ch, CURLOPT_NOBODY, true);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
  curl_setopt($ch, CURLOPT_TIMEOUT,10);
  $output = curl_exec($ch);
  $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);



  $arr = array($httpcode);
  return $httpcode;
}

if(isset($_GET['url'])) {

  $url = htmlspecialchars($_GET['url']);
  if(!validateUrl($url)){
    die("Invalid url.");
  }


  $parseurl = parse_url($url);

  if(substr($parseurl['path'], -1) == "/") {

  } else {
    $parseurl['path'] = $parseurl['path'] ."/";
  }

  $url = $parseurl['scheme'] ."://". $parseurl['host'] . $parseurl['path'];


  $cachefilename = "cache/".$parseurl['host'].".".md5(json_encode($parseurl)).".txt";



  if(!file_exists($cachefilename) || isset($_GET['fetch'])) {


  function getRelease($url) {
    $statusCode = getStatusCode($url ."Release");
    if($statusCode == 200) {
      $releaseResponse = getContents($url ."Release")[1];
      $str = explode(PHP_EOL, $releaseResponse);
      $tmp = [];
      foreach($str as $line) {
        preg_match('/([A-Z]\w+)\: (.*\w+)/', $line, $matches);
        $tmp[] = $matches;
      }

      $arr = [];
      foreach($tmp as $r) {
        $arr[$r[1]] = $r[2];
      }
      unset($arr[""]);

        $arr['Description'] = str_replace('"', "", $arr['Description']);


      return array("status" => true, "url" => $url, "error" => null, "code" => $statusCode, "result" => $arr);
    } else if($statusCode == 301) {
      $parseurl = parse_url($url);
      $newurl = "https://".$parseurl['host'] . $parseurl['path'];
      return getRelease($newurl);

    } else if($statusCode == 404) {
      return array("status" => false, "url" => $url, "error" => "No Release file found", "code" => $statusCode);
    } else {
      return array("status" => false, "url" => $url, "error" => "UNKNOWN ERROR", "code" => $statusCode, "http_status" => getContents($url."Release")[0]);
    }
  }




    if($parseurl['host'] == "apt.thebigboss.org") {

      $url = 'http://apt.thebigboss.org/repofiles/cydia/dists/stable/';

      $release = getRelease($url);
      if($release['status']) {
        $packageurl = "http://files11.thebigboss.org/repofiles/cydia/dists/stable/main/binary-iphoneos-arm/";
        $package = getPackages($packageurl);
      } else {
        die(json_encode($release, JSON_PRETTY_PRINT));
      }

      $uoverwrite = true;



    } else if($parseurl['host'] == "apt.saurik.com" && $parseurl['path'] == "/") {

      $url = 'http://apt.saurik.com/dists/ios/';
      $release = getRelease($url);
      if($release['status']) {
        $packageurl = $release['url'] . "main/binary-iphoneos-arm/";
        $package = getPackages($packageurl);
      } else {
        die(json_encode($release, JSON_PRETTY_PRINT));
      }
      $uoverwrite = true;





    } else if($parseurl['host'] == "cydia.zodttd.com") {

      $url = 'http://cydia.zodttd.com/repo/cydia/dists/stable/';
      $release = getRelease($url);

      if($release['status']) {
        $packageurl = $release['url'] . "main/binary-iphoneos-arm/";
        $package = getPackages($packageurl);
      } else {
        die(json_encode($release, JSON_PRETTY_PRINT));
      }
      $uoverwrite = true;



    } else if($parseurl['host'] == "apt.modmyi.com") {

      $url = 'http://apt.modmyi.com/dists/stable/';
      $release = getRelease($url);
      if($release['status']) {
        $packageurl = $release['url'] . "main/binary-iphoneos-arm/";
        $package = getPackages($packageurl);
      } else {
        die(json_encode($release, JSON_PRETTY_PRINT));
      }
      $uoverwrite = true;

    } else {

      $release = getRelease($url);
      if($release['status']) {
        $tmpfile = md5(microtime());
        $package = getPackages($release['url']);

      } else {
        die(json_encode($release, JSON_PRETTY_PRINT));
      }
    }



    if($uoverwrite) {
        $converted = convertRepo2($release['result'], $package, $release['url'], true);
    } else {
        $converted = convertRepo2($release['result'], $package, $release['url']);
    }




if(isset($_GET['pretty'])) {
  $json = json_encode($converted, JSON_PRETTY_PRINT);
} else {
  $json = json_encode($converted);
}

if ($json)
  if(isset($_GET['q'])) {
    echo searchPackage(htmlspecialchars($_GET['q']), $json);
  } else if(isset($_GET['s'])) {
    echo getPackagesBySection(htmlspecialchars($_GET['s']), $json);
  } else if(isset($_GET['id'])) {
    echo getPackagesById(htmlspecialchars($_GET['id']), $json);
  } else if(isset($_GET['releaseOnly'])) {
    echo getReleaseOnly($json);

  } else {

   echo $json;
  }
else
    die(json_last_error_msg());




    file_put_contents($cachefilename, $json);

  } else {
    if(isset($_GET['pretty'])) {
      $json = json_encode(json_decode(file_get_contents($cachefilename), true), JSON_PRETTY_PRINT);
    } else {
      $json = file_get_contents($cachefilename);
    }

    if(isset($_GET['q'])) {
      echo searchPackage(htmlspecialchars($_GET['q']), $json);

    } else if(isset($_GET['s'])) {
      echo getPackagesBySection(htmlspecialchars($_GET['s']), $json);
    } else if(isset($_GET['id'])) {
      echo getPackagesById(htmlspecialchars($_GET['id']), $json);
    } else if(isset($_GET['releaseOnly'])) {
      echo getReleaseOnly($json);

    } else {

     echo $json;
   }
 }



}




?>
