<?php

class CrawlerDb {
    
    private $connection;
    private $sitesToViewTbl = 'sites_to_view';
    private $sitesViewedTbl = 'sites_viewed';
    
    public function __construct() {
       
        $this->connection = $this->setConnection();
    }
    
    
    public function setToViewList($urlList) {
        
        foreach($urlList as $url) {
            $this->setToView($url);
        }
    }

    /**
     * @param type $url
     * @return bool
     */
    public function setToView($url) {
        if( $this->getToViewByUrl($url) === false) {
            
            $stmt = $this->connection->prepare("INSERT INTO $this->sitesToViewTbl (url) VALUES(:url)");
            return $stmt->execute(array( ':url'=>$url));
        }
        else {
            return false;
        }
    }
    
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
    
    public function deleteToView($url) {
        $stmt = $this->connection->prepare("DELETE FROM $this->sitesToViewTbl WHERE url = :url");
        $stmt->execute(array(':url'=>$url));
        return $stmt->rowCount();
    }

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
     * 
     * @param type $url
     * @return array or false
     */
    private function getToViewByUrl($url) {
                
        $stmt = $this->connection->prepare("SELECT * FROM $this->sitesToViewTbl WHERE url=:url LIMIT 1"); 
        $stmt->execute(array(':url'=>$url)); 
        return $stmt->fetch();
    }
    
    /**
     * 
     * @param type $url
     * @return array or false
     */
    private function getViewedByUrl($url) {
                
        $stmt = $this->connection->prepare("SELECT * FROM $this->sitesViewedTbl WHERE url=:url LIMIT 1"); 
        $stmt->execute(array(':url'=>$url)); 
        return $stmt->fetch();
    }


    private function setConnection() {
        $host = '127.0.0.1';
        $db   = 'crawler';
        $user = 'root';
        $pass = '';
        $charset = 'utf8mb4';

        $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
        $opt = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        return new PDO($dsn, $user, $pass, $opt);
    }
}
