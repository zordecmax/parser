<?php
/**
 * Created by PhpStorm.
 * User: Максим
 * Date: 11.05.2021
 * Time: 11:18
 */
require_once "vendor/autoload.php";

use GuzzleHttp\Client;

$categoriesToScan = [];
$file = fopen($argv[1], "r");

if ($file) {
    while (($line = fgets($file)) !== false) {
        $categoriesToScan[] = explode('|',$line);
    }

    fclose($file);
} else {
    echo "No file";
}


foreach($categoriesToScan as $category){
    $categoryName = explode('/',$category[0]);
    $categoryName = $categoryName[count($categoryName) - 2];
    $linksToParse = [];
    $page = 1;
    $restOfLinks = 0;
    $numbersOfLinks = $category[1];
    while(count($linksToParse) < $category[1]){

    $client = new GuzzleHttp\Client([
        'base_uri' => $category[0]."?from=".$page,
        'cookies' => true
    ]);

    $response = $client->request('GET' , '');

    $body = $response->getBody();

    $dom = new DOMDocument();

    $dom->loadHTML($body->getContents());
//    libxml_use_internal_errors(true);

    $xpath = new DOMXpath($dom);
    $links = $xpath->query("//div[@class='item  ']/a"); // Get all links with video

    if($category[1] > 99)  {
        if($restOfLinks === 0){
            $numbersOfLinks = 99;
            $restOfLinks = $category[1] - 99;
        }

        if($restOfLinks > 0 && $restOfLinks < 100){
            $numbersOfLinks = $restOfLinks;
        }

        if($restOfLinks > 99){
            $numbersOfLinks = 99;
            $restOfLinks = $restOfLinks - 99;
        }

    }


        for($i = 0; $i < $numbersOfLinks; $i++){
            $linksToParse[] = $links[$i] !== null ? $links[$i]->getAttribute("href") : $i;
        }


    $page++;
    }


    foreach($linksToParse as $videoLink){

        $client = new GuzzleHttp\Client([
            'base_uri' => $videoLink,
            'cookies' => true
        ]);


        $response = $client->request('GET' , '');

        $body = $response->getBody();

        $dom = new DOMDocument();
        $dom->loadHTML($body->getContents());
        $xpath = new DOMXpath($dom);

        $videoTitle = $xpath->query("//h2");
        $videoTitle = $videoTitle[0]->nodeValue;

        $videoDescription = $xpath->query("//div[@class='item']//em");
        $videoDescription = $videoDescription[0]->nodeValue;

        $videoCategories = $xpath->query("//div[@class='item']/span[text()='Categories:']/following-sibling::div//a");

        $arrayOfCategories = [];

        for($i = 0; $i < $videoCategories->length; $i++ ){
            $arrayOfCategories[] = $videoCategories[$i]->nodeValue;
        }

        $videoTags = $xpath->query("//div[@class='item']/span[text()='Tags:']/following-sibling::div//a");
        $arrayOfTags = [];
        for($i = 0; $i < $videoTags->length; $i++ ){
            $arrayOfTags[] = $videoTags[$i]->nodeValue;
        }

        $videoModels = $xpath->query("//div[@class='item']/span[text()='Models:']/following-sibling::div//a");
        $arrayOfModels = [];
        for($i = 0; $i < $videoModels->length; $i++ ){
            $arrayOfModels[] = $videoModels[$i]->nodeValue;
        }

        $videoChannel = $xpath->query("//div[@class='item']/span[text()='Channel:']/following-sibling::div//a");
        $arrayOfChannel = [];
        for($i = 0; $i < $videoChannel->length; $i++ ){
            $arrayOfChannel[] = $videoChannel[$i]->nodeValue;
        }

        $video = $xpath->query('//script[contains(text(), "video_id")]');
        $parsedJs = $video[0]->nodeValue;

        $pattern = "/video_url:.'(.[^']*)'/";
        $patternHd = "/video_alt_url:.'(.[^']*)'/";
        $patternFullHd = "/video_alt_url2:.'(.[^']*)'/";
        preg_match($pattern, $parsedJs, $matches);
        $video = $matches[1];

        preg_match($patternHd, $parsedJs, $matches);
        $videoHd = $matches[1];
        preg_match($patternFullHd, $parsedJs, $matches);
        $videoFullHd = $matches[1];
        if(!empty($videoFullHd)){ // Add Full HD video
            $fileNameFullHd = explode('/',$videoFullHd);
            $fileNameFullHd = $fileNameFullHd[count($fileNameFullHd)-2];
            $pathToFile = "video/$fileNameFullHd";
            file_put_contents($pathToFile, fopen($video, 'r'));

        }else if(!empty($videoHd)){ // Add HD video
            $fileNameHd = explode('/',$videoHd);
            $fileNameHd = $fileNameHd[count($fileNameHd)-2];
            $pathToFile = "video/$fileNameHd";
           file_put_contents($pathToFile, fopen($video, 'r'));

        }else {
            $fileName = explode('/',$video); // Add HD video
            $fileName = $fileName[count($fileName)-2];

            $pathToFile = "video/$fileName";
            file_put_contents($pathToFile, fopen($video, 'r'));
        }
//        $videoRow = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]" . $pathToFile . "|" . $videoTitle . "|" . $videoDescription  . "|" . implode(',', $arrayOfCategories)  . "|" .
        $videoRow = $pathToFile . "|" . $videoTitle . "|" . $videoDescription  . "|" . implode(',', $arrayOfCategories)  . "|" .
            implode(',', $arrayOfTags) . "|" .  implode(',', $arrayOfModels) . "|" .  implode(',', $arrayOfChannel);
        file_put_contents('result.txt', $videoRow."\n", FILE_APPEND);

    }

}


