<?php
require_once("const.php");
require_once("logger.php");
require_once("render.php");
require_once("status.php");
require_once("result.php");
require_once("scraper.php");

function validate_input() {
    $required = array("urls");
    foreach ($required as $id) {
        if (!isset($_POST[$id])) {
            return false;
        }
    }
    return true;
}

function issue_jobid() {
    return uniqid();
}

function main() {
    $logger = new Logger();
    $render = new Render();
    $scraper = new Scraper();

    // 入力チェックと入力パラメータの取得
    if (!validate_input()) {
        $render->text_exit("入力が不正です", "400");
    }
    $urls_raw = $_POST["urls"];
    $urls = explode("\n", $urls_raw);
   
    // ジョブIDの発行、開始時刻の保存
    $jobid = issue_jobid();
    $status = new Status($jobid);
    $result = new Result($jobid);
    $logger->info("start {$jobid}");
    
    // スクレイプ処理 
    $total = count($urls);
    $done_cnt = 0;
    $fail_cnt = 0;
    foreach ($urls as $url) {
        $url = rtrim($url);
        if (empty($url)) {
            continue;
        }

        // スクレイプ実行
        $scraped = $scraper->scrape($url);
        if (!isset($scraped[1])) {
            $logger->warning("failed scrape url:{$url} jobid:{$jobid}");
            $fail_cnt++;
            $result->add($url);
        } else {
            $cats = $scraped[0];
            $desc = $scraped[1];
            $result->add($url, $cats, $desc);
        }

        // ステータス更新
        $done_cnt++;
        $dont_cnt_str = "{$done_cnt} (/{$total})";
        if (!$status->update_processing($dont_cnt_str, $fail_cnt)) {
            $logger->error("failed update status to {$done_percent} jobid:{$jobid}");
       }
    }
   
    // 結果のファイル出力
    if (!$result->output()) {
        $logger->error("failed /utput result jobid:{$jobid}");
        $render->text_exit("処理(id:{$jobid})は結果の出力に失敗しました", "500");
    }

    // ステータス更新
    if (!$status->update_done($done_cnt, $fail_cnt)) {
        $logger->error("failed update status to done jobid:{$jobid}");
        $render->text_exit("処理(id:{$jobid})はステータスの更新に失敗しました", "500");
    }

    // 終了通知
    $logger->info("done {$jobid}");
    $render->text_exit(sprintf("処理(id:%s)が完了しました", $jobid));
}

main();
