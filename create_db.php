<?php

class CrawlerDb {
    
    private $connection; 
    private $sitesToViewTbl = 'sites_to_view'; // nazwa tabeli db stron do przeprocesowania
    private $sitesViewedTbl = 'sites_viewed'; // nazwa tabeli db stron przeprocesowanych
    
    public function __construct() {
       //ustanowienie połączenia do bazy danych
       $this->connection = $this->setConnection();
    }
    
    /**
     * Zapis listy linków do procesowania w db
     * @param type $urlList
     */
    public function setToViewList($urlList) {
        
        foreach($urlList as $url) {
            $this->setToView($url);
        }
    }

    /**
     * zapis pojedynczego adresu do przeprocesowania w bazie danych
     * @param type $url
     * @return bool: udany zapis: true, nie udany bądź link już istniej w bazie: false
     */
    public function setToView($url) {
        if( $this->getToViewByUrl($url) === false) { // sprawdzenie czy dany adres jest już w bazie
            // zapis do bazy
            $stmt = $this->connection->prepare("INSERT INTO $this->sitesToViewTbl (url) VALUES(:url)");
            return $stmt->execute(array( ':url'=>$url));
        }
        else {
            return false;
        }
    }
    
    /**
     * zapis pojedynczego adresu który został przeprocesowany (szczytany content) w bazie danych
     * @param type $url
     * @param type $content
     * @return type
     */
    public function setViewed($url, $content) {
        
        if( $this->getViewedByUrl($url) === false) {
            
            $stmt = $this->connection->prepare("INSERT INTO $this->sitesViewedTbl (url, content) VALUES(:url, :content)");
            return $stmt->execute(array( ':url'=>$url, ':content'=>$content));
        }
        else {
            $sql = "UPDATE $this->sitesViewedTbl SET content = :content WHERE url = :url";
            return $this->connection->prepare($sql)->execute(array(':content'=>$content, ':url'=>$url));
        }
    }
    
    /**
     * usunięcie rekodru z adresem przeprocesowanym z tabeli linków do przeprocesowania
     * @param type $url
     * @return type
     */
    public function deleteToView($url) {
        $stmt = $this->connection->prepare("DELETE FROM $this->sitesToViewTbl WHERE url = :url");
        $stmt->execute(array(':url'=>$url));
        return $stmt->rowCount();
    }

    /**
     * Pobranie listy przeprocesowanych (szczytany content stron) linków z bazy danych
     * @return array (lista linków)
     */
    public function getViewedList() {
        $stmt = $this->connection->prepare("SELECT url FROM $this->sitesViewedTbl"); 
        $stmt->execute(); 
        $fetchAll = $stmt->fetchAll();
        
        $return = array();
        foreach($fetchAll as $row) {
            $return[] = $row['url'];
        }
         
         return $return;
    }


    /**
     * Pobranie rekordu adresów do przeprocesowania z bazy danych o zadanych url
     * @param type $url
     * @return array or false
     */
    private function getToViewByUrl($url) {
                
        $stmt = $this->connection->prepare("SELECT * FROM $this->sitesToViewTbl WHERE url=:url LIMIT 1"); 
        $stmt->execute(array(':url'=>$url)); 
        return $stmt->fetch();
    }
    
    /**
     * Pobranie rekordu adresów przeprocesowanych z bazy danych o zadanych url* 
     * @param type $url
     * @return array or false
     */
    private function getViewedByUrl($url) {
                
        $stmt = $this->connection->prepare("SELECT * FROM $this->sitesViewedTbl WHERE url=:url LIMIT 1"); 
        $stmt->execute(array(':url'=>$url)); 
        return $stmt->fetch();
    }

    /**
     * funkcja zwraca połaczenie do bazy danych
     */
    private function setConnection() {
        $host = '127.0.0.1'; // adres db
        $db   = 'crawler'; // nazwa bazy danych
        $user = 'root'; // nazwa użytkownika bazy danych
        $pass = ''; // hasło do bazy
        $charset = 'utf8mb4'; // kodowanie

        $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
        // opcje połączenia
        $opt = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        // zwraca połączenie do bazy danych
        return new PDO($dsn, $user, $pass, $opt);
    }
}
