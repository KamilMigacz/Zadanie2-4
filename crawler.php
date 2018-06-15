<?php

$errorMessage = ''; // treści błędów formularza do podania linku do procesowania
$links = array(); // tablica do wypełniania szczytanymi pod zadanym adresem linkami

if($_SERVER['REQUEST_METHOD'] == 'POST') { // jeżeli adres url do procesowania przesłany formularzem (request wysłany metodą POST )
    $url = trim($_POST['url'], '/');      // przypisz do zmiennej $url adres( $_POST['url']) wysłany z formularza
}
elseif(isset($_GET['url'])){ // JEŻELI adres url do procesowania wysłany w adresie poprzez kliknięcie w link z listy (request wysłany metodą GET)
    $url = trim($_GET['url'], '/');  // przypisz do zmiennej $url adres( $_GET['url']) wysłany w linku
}
else { // jeżeli adres url do procesowania nie został wysłany
    $url = '';
}

if(!empty($url)) { // jeżeli zmienna $url ma przypisany adres do prcesowania
    
    if (filter_var($url, FILTER_VALIDATE_URL)) { // validacja adresu url (czy poprawny adres url)
        libxml_use_internal_errors(true); // wyłaczenie warningów z biblioteki DOMDocument
        $dOMDocument = new DOMDocument(); // inicjowanie obiektu DOMDocument()
        
        if($dOMDocument->loadHTMLFile($url)) {  // jeżeli udało się szczytać stronę o zadanym $url (adresie url)
        
            require_once ('./create_db.php'); // załączenie plikuc create_db.php
            $crawlerDb = new CrawlerDb(); // inicjacja obiektu classy do obsługi basy danych
            $crawlerDb->setViewed($url, $dOMDocument->textContent); // zapis kontentu strony do bazy danych
            $crawlerDb->deleteToView($url); // usunięcie rekordu z tabeli db linków do szczytania z db. Link już jest szczytany :) 
            
            $elementsByTagName = $dOMDocument->getElementsByTagName('a'); // zebranie linków razem z tagami <a href.. z contentu strony

            foreach($elementsByTagName as $elem) { // listowanie szczytanych linków

                $explode = explode('#', $elem->getAttribute('href')); // wyodrębnienie linku z tagu <a href.. oraz odcięcię z linku wszystkiego po #
                if( strpos($explode[0], 'http') !== 0){ // jeżeli wyodrębniony link nie zaczyna się od http (lub https)
                    $explode[0] = $url.$explode[0]; // dopisz na początku adres szczytanej strony
                }

                $links[] = empty($explode[0])? $url : trim($explode[0], '/'); // przypisanie do tabeli szczytanych linków przygotowanego wyżej adresu stronu, bez '/' na końcu. Jeżeli przefiltrowany link jest pusty przypisz adres głowny szczytywanej storny
            }

            $links = array_unique($links); // usunięcie duplikatów uzyskanych adresów
            $crawlerDb->setToViewList($links); // zapis uzyskanych linków w tabeli db do procesowania (szczytywania)
            $viewedList = $crawlerDb->getViewedList(); // pobranie listy linków które zostały już szczytane
        }
    }
    else {
        $errorMessage = '"'.$url.'"'.' nie jest poprawnym URL'; // komunikat błędu formularza do podania adresu do szczytania
    }
}
?>

<!--wyświetlanie formularza i listy linków-->
<html>
    <head>
        <title>Crawler</title>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" type="text/css" href="crawler.css" />
    </head>
    <body>
        <div class="container">
            <!--FORMULARZ DO PODANIA LINKU DO SZCZYTANIA--> 
            <div class="error"><?php echo $errorMessage; ?></div>
            <form method="POST" action="/">
                <input type="text" name="url" placeholder="url.." required="" value="<?php echo $url; ?>"/>
                <input type="submit" value="Crawl!"/>
            </form>
            <?php if(!empty($links)): ?>
            <div class="legendContainer">
                <!--LEGENDA KOLORÓW LINKÓW NIEBIESKI - DO SZCZYTANIA; ZIELONY - LINKI SZCZYTANE-->
                <div class="legendToView">toView</div>
                <div class="legendVied">viewed</div>
            </div>
        <div class="listContainer">
            <!--LISTA UZYSKANYCH LINKÓW-->
            <h1>LINKS LIST </h1>
            <ul>
                <?php 
                    // WYPISOWANIE UZYSKANYCH LINKÓW Z CZYTANIA ZADANEGO ADRESU URL
                    foreach ($links as $link):
                        $class = in_array($link, $viewedList)? 'viewed' : 'toView'; // PRZYPISANIE CLASSY DANEJ POZYCJI LISTY LINKÓW CELEM OZNACZENIA CZY LINK ZOSTAŁ JUŻ PRZECZYTANY CZY NIE.
                ?>
                <li><a href="/?url=<?php echo $link; ?>" class="<?php echo $class; ?>"><?php echo $link; ?></a></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
        </div>        
    </body>
</html>