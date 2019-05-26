<?
// This is a template for a PHP scraper on morph.io (https://morph.io)
// including some code snippets below that you should find helpful

// require 'scraperwiki.php';
// require 'scraperwiki/simple_html_dom.php';
//
// // Read in a page
// $html = scraperwiki::scrape("http://foo.com");
//
// // Find something on the page using css selectors
// $dom = new simple_html_dom();
// $dom->load($html);
// print_r($dom->find("table.list"));
//
// // Write out to the sqlite database using scraperwiki library
// scraperwiki::save_sqlite(array('name'), array('name' => 'susan', 'occupation' => 'software developer'));
//
// // An arbitrary query against the database
// scraperwiki::select("* from data where 'name'='peter'")

// You don't have to do things with the ScraperWiki library.
// You can use whatever libraries you want: https://morph.io/documentation/php
// All that matters is that your final data is written to an SQLite database
// called "data.sqlite" in the current working directory which has at least a table
// called "data".
?>
<?php
require 'scraperwiki.php';
require 'scraperwiki/simple_html_dom.php';
ini_set('max_execution_time', 600);
setlocale(LC_ALL, 'en_UK.UTF8');
//the first time, we set manually the first page
$value = "properties-for-sale/bình-dương/thủ-dầu-một?sort=min_price";
//if $value is a valid page
while ($value != "") {
    $htmlCentre = scraperWiki::scrape("https://www.dotproperty.com.vn" . $value);
    $domCentre = new simple_html_dom();
    $domCentre->load($htmlCentre);
    //find the announces in the current page and store the records
    findAnnounces ($domCentre);
    //look for the next page link
    $value = "";
    foreach($domCentre->find('.next') as $data){
        $value = $data->href;
    }
}
/***************** Functions *********************/
function findAnnounces($strDataDom){
    //for each page, there are up to 10 records, each of them marked with DOM class 'plus'
    foreach($strDataDom->find('.plus') as $data){
        $value = $data->href;
        //is it a valid URL for an announce?
        if (strrpos ($value , "https://www.dotproperty.com.vn/")){
            //go into the announce
            $htmlContent = scraperWiki::scrape($value);
            //look for the start of the json record which has all the information of the announce
            //and manually trim it to the correct json format
            //echo $htmlContent;
            $strStart = strpos($htmlContent, "initGoogleMap");
            $strEnd = strpos($htmlContent, "#containerGoogleMap");
    
            $strData = substr ($htmlContent, $strStart, $strEnd - $strStart - 6);     
            $strData = ltrim($strData, "initGoogleMap([");
            //is it UTF format? just in case we convert it   
            $strDataUTF = iconv('UTF-8', 'ASCII//TRANSLIT', $strData);
            //the function will transform the string into a json object and store it in the database
            storeJson($strDataUTF);
        }
    }
}
/*************************************************/
function storeJson($strData){
    $record=array(); 
    //decode the string
    $jsonVar = json_decode($strData);
    //echo $strData;     
    //if the decode ended with no error
    if (json_last_error() === JSON_ERROR_NONE) { 
        $record["title"] = $jsonVar -> title;
	$record["price"] = $jsonVar -> price;
	$record["accommodation"] = $jsonVar -> surface;
  $record["url"] = "https://www.dotproperty.com.vn/" . $jsonVar -> id;
        
        //save the record
        if ($record["price"] <> 0 or $record["rent"] <> 0 or $record["price_by_m2"] <> 0) {
            scraperwiki::save_sqlite(array('id'), $record);
        }
    }    
} 
?>
