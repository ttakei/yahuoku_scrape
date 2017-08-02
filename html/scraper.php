<?php

require_once("logger.php");

class Scraper {
    function __construct() {
        $this->logger= new Logger();
    }

    function scrape($url) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $html = curl_exec($ch);
        curl_close($ch);
    
        if (empty($html)) {
            $this->logger->warning("failed request $url");
            return false;
        }
    
        $html = str_replace(array("\r", "\n"), "", $html);
    
        $cats = $this->extract_category($html);
        if (empty($cats)) {
            $this->logger->warning("could not extract category $url");
            return false;
        }
    
        $desc = $this->extract_desc($html);
        if ($desc === false) {
            $this->logger->warning("failed extract desc $url");
            return false;
        }
    
        return array($cats, $desc);
    }

    function extract_category($html) {
        if (preg_match("|<!--\s*TopicPath\s*-->(.*?)<!--\s*/TopicPath\s*-->|", $html, $matches)) {
            $topic_html = $matches[1];
        } else {
            return false;
        }
    
        $cats = array();
        if (preg_match_all("|<a[^>]*>([^<]*)</a>|", $topic_html, $matches)) {
            $cats = $matches[1];
        } else {
            return false;
        }
    
        return $cats;
    }

    function extract_desc($html) {
        if (preg_match('|<div[^>]*id="acMdUsrPrv"[^>]*>(.*?)</div>|', $html, $matches)) {
            $desc_html = $matches[1];
        } else {
            return false;
        }
    
        if (preg_match("|商品詳細(.*)発送詳細|", $desc_html, $matches)) {
            $desc = strip_tags($matches[1]);
        } else {
            return false;
        }
    
        return $desc;
    }
}
