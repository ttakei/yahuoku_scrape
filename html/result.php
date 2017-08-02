<?php

require_once("const.php");
require_once("logger.php");

class Result {
    function __construct($jobid) {
        $this->logger = new Logger();
        $this->jobid = $jobid;
        $this->results = array();
    }

    public function file_name() {
        return sprintf("%s.%s.tsv", SCRAPE_RESULT_FILE_PREFIX, $this->jobid);
    }

    public function clear() {
        $file = $this->file_name();
        if (!unlink($file)) {
            $this->logger->error("failed unlink status file ($file)");
            return false;
        }
        return true;
    }

    public function add($url, $cats=null, $desc=null) {
        $this->results[$url] = array($cats, $desc);
    }

    public function output() {
        $output_str = $this->format();
        if (!file_put_contents($this->file_name(), $output_str)) {
            return false;
        } else {
            return true;
        }
    }

    protected function format_cats($cats) {
        return " ". implode(" > ", $cats). " ";
    }

    protected function format() {
        // $this->logger->debug(sprintf("result:%s", var_export($this->results, true)));
        $output = "";
        foreach ($this->results as $url => $pair) {
            if (!isset($pair[1])) {
                $cats_str = "ページ無し";
                $desc = "";
            } else {
                $cats = $pair[0];
                $cats_str = $this->format_cats($cats);
                $desc = $pair[1];
            }
            $output = $output. sprintf("%s\t%s\t%s\n", $url, $cats_str, $desc);
        }
        return $output;
    }
}
