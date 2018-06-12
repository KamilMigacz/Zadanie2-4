<?php

$errorMessage = '';
$links = array();

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $url = trim($_POST['url'], '/');
}
elseif(isset($_GET['url'])){
    $url = trim($_GET['url'], '/');
}
else {
    $url = '';
}

if(!empty($url)) {
    
    if (filter_var($url, FILTER_VALIDATE_URL)) {
        libxml_use_internal_errors(true);
        $dOMDocument = new DOMDocument();
        
        if($dOMDocument->loadHTMLFile($url)) {  
        
            require_once ('./create_db.php');
            $crawlerDb = new CrawlerDb();
            $crawlerDb->setViewed($url, $dOMDocument->textContent);
            $crawlerDb->deleteToView($url);
            
            $elementsByTagName = $dOMDocument->getElementsByTagName('a');

            foreach($elementsByTagName as $elem) {

                $explode = explode('#', $elem->getAttribute('href'));
                if( strpos($explode[0], 'http') !== 0){
                    $explode[0] = $url.$explode[0];
                }

                $links[] = empty($explode[0])? $url : trim($explode[0], '/');
            }

            $links = array_unique($links);
            $crawlerDb->setToViewList($links);
            $viewedList = $crawlerDb->getViewedList();
        }
    }
    else {
        $errorMessage = '"'.$url.'"'.' nie jest poprawnym URL';
    }
}
?>


<html>
    <head>
        <title>Crawler</title>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" type="text/css" href="crawler.css" />
    </head>
    <body>
        <div class="container">
            <div class="error"><?php echo $errorMessage; ?></div>
            <form method="POST" action="/">
                <input type="text" name="url" placeholder="url.." required="" value="<?php echo $url; ?>"/>
                <input type="submit" value="Crawl!"/>
            </form>
            <?php if(!empty($links)): ?>
            <div class="legendContainer">
                <div class="legendToView">toView</div>
                <div class="legendVied">viewed</div>
            </div>
        <div class="listContainer">
            <h1>LINKS LIST </h1>
            <ul>
                <?php 
                    foreach ($links as $link):
                        $class = in_array($link, $viewedList)? 'viewed' : 'toView';
                ?>
                <li><a href="/?url=<?php echo $link; ?>" class="<?php echo $class; ?>"><?php echo $link; ?></a></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
        </div>
        
    </body>
</html>