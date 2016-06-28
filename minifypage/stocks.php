<?php
date_default_timezone_set("PRC");
require_once(dirname(__FILE__) . "/common/Request.class.php");
require_once(dirname(__FILE__) . "/common/iwookongConfig.class.php");
require_once(dirname(__FILE__) . "/common/CheckUserLogin.class.php");
require_once(dirname(__FILE__) . "/common/Utility.class.php");
ob_start("ob_gzhandler");
if (CheckLogin::check() == -1) {
    header("Location:login.php ");
    exit();
}
$stockCode = isset($_GET['stock']) ? $_GET['stock'] : "";

if (empty($stockCode)) {
    header("Location:error.php ");
    exit();
}
$related_info_result = RequestUtil::get(iwookongConfig::$requireUrl . "information/1/related_info.fcgi",
    array(
        "user_id" => $_SESSION['user_id'],
        "token" => $_SESSION["token"],
        "query_type" => 1,
        "key" => $stockCode . ",",
        "start_id" => 0,
        "info_type_list" => "1,1,1,1,1,1"
    ));
$json_ris = json_decode($related_info_result, true);
if ($json_ris["status"] != "1") {
    header("Location:error.php");
    exit();
}
$related_stock_result = RequestUtil::get(iwookongConfig::$requireUrl . "information/1/stock_base.fcgi",
    array(
        "user_id" => $_SESSION['user_id'],
        "token" => $_SESSION["token"],
        "stock_code" => $stockCode . ","
    ));
$json_rss = json_decode($related_stock_result, true);
if ($json_rss["status"] == "1") {
    $stockName = $json_rss["stock_info"]["stock_name"];
} else {
    header("Location:error.php");
    exit();
}
$related_industry = "";
$related_concept = "";

//获取个股折线图（查看，搜索，关注）
$stock_line_result = RequestUtil::get(iwookongConfig::$requireUrl . "stock/1/single_real_time_hot.fcgi",
    array(
        "user_id" => $_SESSION['user_id'],
        "token" => $_SESSION["token"],
        "stock_code" => $stockCode . ","
    ));
$json_line = json_decode($stock_line_result, true);
if ($json_line["status"] != "1") {
    header("Location:error.php");
    exit();
}
//获取大盘数据
//$trade_price_result = RequestUtil::get(iwookongConfig::$requireUrl . "stock/1/trade_price.fcgi",
//    array(
//        "user_id" => $_SESSION['user_id'],
//        "token" => $_SESSION["token"],
//        "stock_code" => $stockCode . ","
//    ));
//$json_tradeprice = json_decode($trade_price_result, true);
//if ($json_tradeprice["status"] != "1") {
//    UtilityTools::recordLogs("获取[trade_price.fcgi]接口数据失败,[" . $json_tradeprice['msg'] . "]", "error");
//    header("Location:error.php");
//    exit();
//}

$viewdata = "";
$searchdata = "";
$followdata = "";
$stockdata_time = "";
$stockdata_line = "";

//foreach ($json_tradeprice["trade"] as $key => $value) {
//    $stockdata_time .= "\"" . date('H:i', $key) . "\",";
//    $stockdata_line .= $value . ",";
//}

if ($json_line["status"] == "1") {
    for ($i = 0; $i < count($json_line["visit"]); $i++) {
        $viewdata .= $json_line["visit"][$i] . ($i + 1 < count($json_line["visit"]) ? "," : "");
    }
    for ($i = 0; $i < count($json_line["search"]); $i++) {
        $searchdata .= $json_line["search"][$i] . ($i + 1 < count($json_line["search"]) ? "," : "");
    }
    for ($i = 0; $i < count($json_line["follow"]); $i++) {
        $followdata .= $json_line["follow"][$i] . ($i + 1 < count($json_line["follow"]) ? "," : "");
    }
} else {
    header("Location:error.php?code=1005");
    exit();
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head> <meta charset="UTF-8"> <title><?php echo $stockName . "(" . $stockCode . ")" ?>热度情况</title> <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1"> <meta name="viewport" content="width=device-width, initial-scale=1"> <link rel="stylesheet" href="http://cdn.bootcss.com/bootstrap/3.3.6/css/bootstrap.min.css"> <link rel="stylesheet" href="http://cdn.bootcss.com/bootstrap/3.3.6/css/bootstrap-theme.min.css"> <link rel="stylesheet" href="http://cdn.bootcss.com/font-awesome/4.6.3/css/font-awesome.min.css"> <link rel="stylesheet" href="http://cdn.bootcss.com/malihu-custom-scrollbar-plugin/3.1.3/jquery.mCustomScrollbar.min.css"> <link rel="stylesheet" href="http://static.iwookong.com/plugins/typeahead/jquery.typeahead.min.css"> <link rel="stylesheet" href="http://static.iwookong.com/css/common.min.css"> <link rel="stylesheet" href="http://static.iwookong.com/css/wookong/index.min.css"></head>
<body>
<?php include("share/_header.php") ?>
<div class="container wk-container">
    <section class="wk-time-hot"> <p class="wk-hot-title"><?php echo $stockName ?>热度情况&nbsp; <i class="fa fa-question-circle-o" data-toggle="popover" data-content="<?php echo $stockName . "(" . $stockCode . ")" ?>每小时产生的热度量"></i> <span>行业：</span> <?php if (count($json_rss["stock_info"]["industry"]) > 0) { for ($i = 0; $i < count($json_rss["stock_info"]["industry"]); $i++) { $indus_name = $json_rss["stock_info"]["industry"][$i]["indus"]; echo "<a href='industry.php?name=" . $indus_name . "' target='_blank'>" . $indus_name . "</a>"; } $related_industry = $json_rss["stock_info"]["industry"][0]["indus"]; } else { echo "<a>无</a>"; } ?> <span>概念：</span> <?php if (count($json_rss["stock_info"]["section"]) > 0) { for ($i = 0; $i < count($json_rss["stock_info"]["section"]); $i++) { $concept_name = $json_rss["stock_info"]["section"][$i]["sect"]; echo "<a href='concept.php?name=" . $concept_name . "' target='_blank'>" . $concept_name . "</a>"; } $related_concept = $json_rss["stock_info"]["section"][0]["sect"]; } else { echo "<a>无</a>"; } ?> </p> <div class="col-md-8 left-charts" id="left-chart"></div> <div class="col-md-4 right-infos"> <p> <?php echo $stockName ?>最近热度 &nbsp;<i class="fa fa-question-circle-o" data-toggle="popover" data-placement="bottom" data-content=""></i> </p> <hr> <table class="table table-condensed first-table"> <?php if (count($json_line["visit"]) > 0) { $view = explode(",", $viewdata); echo "<tr><td>查看</td><td>" . end($view) . "</td><td>" . date("Y-m-d H:00") . "</td></tr>"; } else { echo "<tr><td>查看</td><td>-</td><td>-</td></tr>"; } ?> <?php if (count($json_line["search"]) > 0) { $search = explode(",", $searchdata); echo "<tr><td>搜索</td><td>" . end($search) . "</td><td>" . date("Y-m-d H:00") . "</td></tr>"; } else { echo "<tr><td>搜索</td><td>-</td><td>-</td></tr>"; } ?> <?php if (count($json_line["follow"]) > 0) { $follow = explode(",", $followdata); echo "<tr><td>关注</td><td>" . end($follow) . "</td><td>" . date("Y-m-d H:00") . "</td></tr>"; } else { echo "<tr><td>关注</td><td>-</td><td>-</td></tr>"; } ?> </table> <p><?php echo $stockName ?>今日最热度</p> <hr> <table class="table table-condensed"> <?php if (count($json_line["visit"]) > 0) { $vmax = (int)array_search(max($json_line["visit"]), $json_line["visit"]); echo "<tr><td>查看</td><td>" . ($vmax - 1 < 0 ? "0" : $vmax - 1) . ":00 - " . $vmax . ":00</td></tr>"; } else { echo "<tr><td>查看</td><td>-</td></tr>"; } ?> <?php if (count($json_line["search"]) > 0) { $smax = (int)array_search(max($json_line["search"]), $json_line["search"]); echo "<tr><td>搜索</td><td>" . ($smax - 1 < 0 ? "0" : $smax - 1) . ":00 - " . $smax . ":00</td></tr>"; } else { echo "<tr><td>搜索</td><td>-</td></tr>"; } ?> <?php if (count($json_line["follow"]) > 0) { $fmax = (int)array_search(max($json_line["follow"]), $json_line["follow"]); echo "<tr><td>关注</td><td>" . ($fmax - 1 < 0 ? "0" : $fmax - 1) . ":00 - " . $fmax . ":00</td></tr>"; } else { echo "<tr><td>关注</td><td>-</td></tr>"; } ?> </table> </div> </section>
    <section class="wk-all-hot">
        <div class="wk-con-news"> <p class="wk-hot-title">关联资讯 <span>关联股票：</span> <?php if (count($json_ris["stock"]) > 0) { foreach ($json_ris["stock"] as $stocks) { echo empty($stocks) ? "<a>无</a>" : "<a href='stocks.php?stock=" . $stocks['stock_code'] . "' target='_blank'>" . $stocks['stock_name'] . "</a>"; } } else { echo "<a>无</a>"; } ?> <span>关联行业：</span> <?php if (count($json_ris["industry"]) > 0) { foreach ($json_ris["industry"] as $industry) { echo empty($industry) ? "<a>无</a>" : "<a href='industry.php?name=" . $industry['industry'] . "' target='_blank'>" . $industry['industry'] . "</a>"; } } else { echo "<a>无</a>"; } ?> <span>关联概念：</span> <?php if (count($json_ris["notion"]) > 0) { foreach ($json_ris["notion"] as $concept) { echo empty($concept) ? "<a>无</a>" : "<a href='concept.php?name=" . $concept['section'] . "' target='_blank'>" . $concept['section'] . "</a>"; } } else { echo "<a>无</a>"; } ?> </p> <div class="wk-con-box"> <ul class="nav nav-tabs" role="tablist"> <li role="presentation" class="active"><a href="#wk-news" aria-controls="wk-news" role="tab" data-toggle="tab">新闻</a></li> <li role="presentation"><a href="#wk-selfmedia" aria-controls="wk-selfmedia" role="tab" data-toggle="tab"><label></label>达人观点</a></li> <li role="presentation"><a href="#wk-newsflash" aria-controls="wk-newsflash" role="tab" data-toggle="tab">快讯</a></li> </ul> <div class="tab-content"> <div role="tabpanel" class="tab-pane active" id="wk-news"> <?php if (count($json_ris["news"]) > 0) { for ($i = 0; $i < count($json_ris["news"]); $i++) { $news = $json_ris["news"][$i]; ?> <div class="wk-news-list" id="news_<?php echo $news["info_id"] ?>"> <div class="wk-news-list-head"> <label class="wk-news-title-num"><?php echo($i + 1) ?></label> <p class="wk-news-list-title"><a href="detail.php?infoid=<?php echo $news["info_id"] ?>" target="_blank"><?php echo $news["title"] ?></a></p> <?php echo UtilityTools::getEmotion((int)$news["sentiment"]) ?> </div> <div class="wk-news-list-con"> <p> <?php echo empty($news["summary"]) ? "" : "<strong>【机器人摘要】</strong>" . preg_replace('/\s| /', '', $news["summary"]) . "<a href=\"detail.php?infoid=" . $news["info_id"] . "\" target=\"_blank\"><i class=\"fa fa-link\"></i>详情链接</a>" ?> </p> <span>来源：<?php echo empty($news["from"]) ? "未知" : $news["from"] ?>&nbsp;&nbsp;&nbsp;&nbsp;<?php echo date('Y-m-d H:i', $news["timestamp"] / 1000); ?></span> </div> <hr> </div> <?php } } else { ?> <div class="wk-news-no"> <img src="http://static.iwookong.com/imgs/i/nonews.png"><span>暂无相关新闻资讯</span> </div> <?php } ?> </div> <div role="tabpanel" class="tab-pane fade" id="wk-selfmedia"> <?php if (count($json_ris["me_media"]) > 0) { foreach ($json_ris["me_media"] as $media) { ?> <div class="wk-news-list" id="media_<?php echo $media["info_id"] ?>"> <div class="wk-news-list-head"> <p class="wk-news-list-title"><a href="detail.php?infoid=<?php echo $media["info_id"] ?>" target="_blank"><?php echo $media["title"] ?></a></p> <?php echo UtilityTools::getEmotion((int)$media["sentiment"]) ?> </div> <div class="wk-news-list-con"> <p> <?php echo empty($media["summary"]) ? "" : "<strong>【机器人摘要】</strong>" . preg_replace('/\s| /', '', $media["summary"]) . "<a href=\"detail.php?infoid=" . $media["info_id"] . "\" target=\"_blank\"><i class=\"fa fa-link\"></i>详情链接</a>" ?> </p> <span>来源：<?php echo empty($media["from"]) ? "未知" : $media["from"] ?>&nbsp;&nbsp;&nbsp;&nbsp;<?php echo date('Y-m-d H:i', $media["timestamp"] / 1000); ?></span> </div> <hr> </div> <?php } } else { ?> <div class="wk-news-no"> <img src="http://static.iwookong.com/imgs/i/nonews.png"><span>暂无相关达人观点</span> </div> <?php } ?> </div> <div role="tabpanel" class="tab-pane fade" id="wk-newsflash"> <div class="wk-news-list"> <table class="table"> <?php if (count($json_ris["fast_info"]) > 0) { for ($i = 0; $i < count($json_ris["fast_info"]); $i++) { ?> <tr id="fast_<?php echo $json_ris["fast_info"][$i]["info_id"] ?>"> <td><label><?php echo date('H:i', $json_ris["fast_info"][$i]["timestamp"] / 1000) ?></label></td> <td><?php echo $json_ris["fast_info"][$i]["summary"] ?></td> </tr> <?php } } else { ?> <div class="wk-news-no"> <img src="http://static.iwookong.com/imgs/i/nonews.png"><span>暂无相关快讯</span> </div> <?php } ?> </table> </div> </div> </div> </div> </div>
        <?php if (count($json_rss["stock_info"]["industry"]) > 0) {
            $related_industry_result = RequestUtil::get(iwookongConfig::$requireUrl . "stock/1/top_twenty_stock.fcgi", array(
                "user_id" => $_SESSION['user_id'],
                "token" => $_SESSION["token"],
                "hy" => $related_industry
            ));
            $json_industry = json_decode($related_industry_result, true);
            if ($json_industry["status"] == "1") {
                $industry_stock_v = $json_industry["result"]["code_info"]["shv_"];//热股查看
                $industry_stock_s = $json_industry["result"]["code_info"]["shs_"];//热股搜索
                $industry_stock_f = $json_industry["result"]["code_info"]["shf_"];//热股关注
                $industry_stock_map_v = $json_industry["result"]["code_info"]["suv_"];//热股查看热力图
                $industry_stock_map_s = $json_industry["result"]["code_info"]["sus_"];//热股搜索热力图
                $industry_stock_map_f = $json_industry["result"]["code_info"]["suf_"];//热股关注热力图
            }
            ?>
            <div class="wk-con-industry"> <p class="wk-hot-title"><?php echo $related_industry ?>行业热度情况</p> <div class="wk-con-box"> <ul class="nav nav-tabs" role="tablist"> <li role="presentation" class="active"><a href="#industry-view" aria-controls="industry-view" role="tab" data-toggle="tab">查看热度</a></li> <li role="presentation"><a href="#industry-search" aria-controls="industry-search" role="tab" data-toggle="tab">搜索热度</a></li> <li role="presentation"><a href="#industry-follow" aria-controls="industry-follow" role="tab" data-toggle="tab">关注热度</a></li> </ul> <span class="wk-hot-time">数据日期:<?php echo date("Y-m-d H:"); echo UtilityTools::getNowMinute(); ?></span> <div class="tab-content"> <div role="tabpanel" class="tab-pane active" id="industry-view"> <div class="col-md-5 left"> <p class="wk-hot-sub-title">查看热度排行&nbsp;<i class="fa fa-question-circle-o" data-toggle="popover" data-content="截至当前产生的总热度量的排行"></i></p> <table class="table table-hover table-condensed table-striped wk-hot-table"> <thead> <tr> <td>序号</td> <td>股票名称</td> <td>关注热度</td> <td>热度增量</td> <td>价格</td> </tr> </thead> <tbody> <?php for ($i = 0; $i < count($industry_stock_v); $i++) { ?> <tr> <td><?php echo $i + 1 ?></td> <td><a href="stocks.php?stock=<?php echo $industry_stock_v[$i]["code"] ?>" target="_blank"><?php echo $industry_stock_v[$i]["name"] ?></a></td> <td><?php echo $industry_stock_v[$i]["value"] ?></td> <td> <?php if ((float)$industry_stock_v[$i]["increment"] > 0) { echo $industry_stock_v[$i]["increment"] . "<span class='wk-red'>↑</span>"; } elseif ((float)$industry_stock_v[$i]["increment"] < 0) { echo $industry_stock_v[$i]["increment"] . "<span class='wk-green'>↓</span>"; } else { echo $industry_stock_v[$i]["increment"]; } ?> </td> <?php if ((float)$industry_stock_v[$i]["price"] == 0) { echo "<td class=\"wk-gray\">未交易</td>"; } else { echo "<td class=\"" . UtilityTools::getPriceColor((float)$industry_stock_v[$i]["mark_z_d"]) . "\">" . $industry_stock_v[$i]["price"] . "</td>"; } ?> </tr> <?php } ?> </tbody> </table> </div> <div class="col-md-7 right"> <p class="wk-hot-sub-title">查看热度涨跌幅排行&nbsp;<i class="fa fa-question-circle-o" data-toggle="popover" data-content="当前最新与前一个小时的热度指标相比较产生的数值"></i></p> <div class="charts" id="wk-industry-view-treemap"></div> <p class="wk-hot-sub-tips"><label>●</label>方块大小表示成交量，越大的板块成交量越大</p> <p class="wk-hot-sub-tips"><label>●</label>方块颜色表示热度涨跌幅，涨跌越大，颜色越深，上涨红色，下跌绿色</p> </div> </div> <div role="tabpanel" class="tab-pane fade" id="industry-search"> <div class="col-md-5 left"> <p class="wk-hot-sub-title">搜索热度排行&nbsp;<i class="fa fa-question-circle-o" data-toggle="popover" data-content="截至当前产生的总热度量的排行"></i></p> <table class="table table-hover table-condensed table-striped wk-hot-table"> <thead> <tr> <td>序号</td> <td>股票名称</td> <td>关注热度</td> <td>热度增量</td> <td>价格</td> </tr> </thead> <tbody> <?php for ($i = 0; $i < count($industry_stock_s); $i++) { ?> <tr> <td><?php echo $i + 1 ?></td> <td><a href="stocks.php?stock=<?php echo $industry_stock_s[$i]["code"] ?>" target="_blank"><?php echo $industry_stock_s[$i]["name"] ?></a></td> <td><?php echo $industry_stock_s[$i]["value"] ?></td> <td> <?php if ((float)$industry_stock_s[$i]["increment"] > 0) { echo $industry_stock_s[$i]["increment"] . "<span class='wk-red'>↑</span>"; } elseif ((float)$industry_stock_s[$i]["increment"] < 0) { echo $industry_stock_s[$i]["increment"] . "<span class='wk-green'>↓</span>"; } else { echo $industry_stock_s[$i]["increment"]; } ?> </td> <?php if ((float)$industry_stock_s[$i]["price"] == 0) { echo "<td class=\"wk-gray\">未交易</td>"; } else { echo "<td class=\"" . UtilityTools::getPriceColor((float)$industry_stock_s[$i]["mark_z_d"]) . "\">" . $industry_stock_s[$i]["price"] . "</td>"; } ?> </tr> <?php } ?> </tbody> </table> </div> <div class="col-md-7 right"> <p class="wk-hot-sub-title">搜索热度涨跌幅排行&nbsp;<i class="fa fa-question-circle-o" data-toggle="popover" data-content="当前最新与前一个小时的热度指标相比较产生的数值"></i></p> <div class="charts" id="wk-industry-search-treemap"></div> <p class="wk-hot-sub-tips"><label>●</label>方块大小表示成交量，越大的板块成交量越大</p> <p class="wk-hot-sub-tips"><label>●</label>方块颜色表示热度涨跌幅，涨跌越大，颜色越深，上涨红色，下跌绿色</p> </div> </div> <div role="tabpanel" class="tab-pane fade" id="industry-follow"> <div class="col-md-5 left"> <p class="wk-hot-sub-title">关注热度排行&nbsp;<i class="fa fa-question-circle-o" data-toggle="popover" data-content="截至当前产生的总热度量的排行"></i></p> <table class="table table-hover table-condensed table-striped wk-hot-table"> <thead> <tr> <td>序号</td> <td>股票名称</td> <td>关注热度</td> <td>热度增量</td> <td>价格</td> </tr> </thead> <tbody> <?php for ($i = 0; $i < count($industry_stock_f); $i++) { ?> <tr> <td><?php echo $i + 1 ?></td> <td><a href="stocks.php?stock=<?php echo $industry_stock_f[$i]["code"] ?>" target="_blank"><?php echo $industry_stock_f[$i]["name"] ?></a></td> <td><?php echo $industry_stock_f[$i]["value"] ?></td> <td> <?php if ((float)$industry_stock_f[$i]["increment"] > 0) { echo $industry_stock_f[$i]["increment"] . "<span class='wk-red'>↑</span>"; } elseif ((float)$industry_stock_f[$i]["increment"] < 0) { echo $industry_stock_f[$i]["increment"] . "<span class='wk-green'>↓</span>"; } else { echo $industry_stock_f[$i]["increment"]; } ?> </td> <?php if ((float)$industry_stock_f[$i]["price"] == 0) { echo "<td class=\"wk-gray\">未交易</td>"; } else { echo "<td class=\"" . UtilityTools::getPriceColor((float)$industry_stock_f[$i]["mark_z_d"]) . "\">" . $industry_stock_f[$i]["price"] . "</td>"; } ?> </tr> <?php } ?> </tbody> </table> </div> <div class="col-md-7 right"> <p class="wk-hot-sub-title">关注热度涨跌幅排行&nbsp;<i class="fa fa-question-circle-o" data-toggle="popover" data-content="当前最新与前一个小时的热度指标相比较产生的数值"></i></p> <div class="charts" id="wk-industry-follow-treemap"></div> <p class="wk-hot-sub-tips"><label>●</label>方块大小表示成交量，越大的板块成交量越大</p> <p class="wk-hot-sub-tips"><label>●</label>方块颜色表示热度涨跌幅，涨跌越大，颜色越深，上涨红色，下跌绿色</p> </div> </div> </div> </div> </div>
        <?php } ?>

        <?php if (count($json_rss["stock_info"]["section"]) > 0) {
            $related_concept_result = RequestUtil::get(iwookongConfig::$requireUrl . "stock/1/top_twenty_stock.fcgi", array(
                "user_id" => $_SESSION['user_id'],
                "token" => $_SESSION["token"],
                "gn" => $related_concept
            ));
            $json_concept = json_decode($related_concept_result, true);
            if ($json_concept["status"] == "1") {
                $concept_stock_v = $json_concept["result"]["code_info"]["shv_"];//热股查看
                $concept_stock_s = $json_concept["result"]["code_info"]["shs_"];//热股搜索
                $concept_stock_f = $json_concept["result"]["code_info"]["shf_"];//热股关注
                $concept_stock_map_v = $json_concept["result"]["code_info"]["suv_"];//热股查看热力图
                $concept_stock_map_s = $json_concept["result"]["code_info"]["sus_"];//热股搜索热力图
                $concept_stock_map_f = $json_concept["result"]["code_info"]["suf_"];//热股关注热力图
            }
            ?>
            <div class="wk-con-concept"> <p class="wk-hot-title"><?php echo $related_concept ?>概念热度情况</p> <div class="wk-con-box"> <ul class="nav nav-tabs" role="tablist"> <li role="presentation" class="active"><a href="#concept-view" aria-controls="stock-view" role="tab" data-toggle="tab">查看热度</a></li> <li role="presentation"><a href="#concept-search" aria-controls="concept-search" role="tab" data-toggle="tab">搜索热度</a></li> <li role="presentation"><a href="#concept-follow" aria-controls="concept-follow" role="tab" data-toggle="tab">关注热度</a></li> </ul> <span class="wk-hot-time">数据日期:<?php echo date("Y-m-d H:");echo UtilityTools::getNowMinute(); ?></span> <div class="tab-content"> <div role="tabpanel" class="tab-pane active" id="concept-view"> <div class="col-md-5 left"> <p class="wk-hot-sub-title">查看热度排行&nbsp;<i class="fa fa-question-circle-o" data-toggle="popover" data-content="截至当前产生的总热度量的排行"></i></p> <table class="table table-hover table-condensed table-striped wk-hot-table"> <thead> <tr> <td>序号</td> <td>股票名称</td> <td>关注热度</td> <td>热度增量</td> <td>价格</td> </tr> </thead> <tbody> <?php for ($i = 0; $i < count($concept_stock_v); $i++) { ?> <tr> <td><?php echo $i + 1 ?></td> <td><a href="stocks.php?stock=<?php echo $concept_stock_v[$i]["code"] ?>" target="_blank"><?php echo $concept_stock_v[$i]["name"] ?></a></td> <td><?php echo $concept_stock_v[$i]["value"] ?></td> <td> <?php if ((float)$concept_stock_v[$i]["increment"] > 0) { echo $concept_stock_v[$i]["increment"] . "<span class='wk-red'>↑</span>"; } elseif ((float)$concept_stock_v[$i]["increment"] < 0) { echo $concept_stock_v[$i]["increment"] . "<span class='wk-green'>↓</span>"; } else { echo $concept_stock_v[$i]["increment"]; } ?> </td> <?php if ((float)$concept_stock_v[$i]["price"] == 0) { echo "<td class=\"wk-gray\">未交易</td>"; } else { echo "<td class=\"" . UtilityTools::getPriceColor((float)$concept_stock_v[$i]["mark_z_d"]) . "\">" . $concept_stock_v[$i]["price"] . "</td>"; } ?> </tr> <?php } ?> </tbody> </table> </div> <div class="col-md-7 right"> <p class="wk-hot-sub-title">查看热度涨跌幅排行&nbsp;<i class="fa fa-question-circle-o" data-toggle="popover" data-content="当前最新与前一个小时的热度指标相比较产生的数值"></i></p> <div class="charts" id="wk-concept-view-treemap"></div> <p class="wk-hot-sub-tips"><label>●</label>方块大小表示成交量，越大的板块成交量越大</p> <p class="wk-hot-sub-tips"><label>●</label>方块颜色表示热度涨跌幅，涨跌越大，颜色越深，上涨红色，下跌绿色</p> </div> </div> <div role="tabpanel" class="tab-pane fade" id="concept-search"> <div class="col-md-5 left"> <p class="wk-hot-sub-title">搜索热度排行&nbsp;<i class="fa fa-question-circle-o" data-toggle="popover" data-content="截至当前产生的总热度量的排行"></i></p> <table class="table table-hover table-condensed table-striped wk-hot-table"> <thead> <tr> <td>序号</td> <td>股票名称</td> <td>关注热度</td> <td>热度增量</td> <td>价格</td> </tr> </thead> <tbody> <?php for ($i = 0; $i < count($concept_stock_s); $i++) { ?> <tr> <td><?php echo $i + 1 ?></td> <td><a href="stocks.php?stock=<?php echo $concept_stock_s[$i]["code"] ?>" target="_blank"><?php echo $concept_stock_s[$i]["name"] ?></a></td> <td><?php echo $concept_stock_s[$i]["value"] ?></td> <td> <?php if ((float)$concept_stock_s[$i]["increment"] > 0) { echo $concept_stock_s[$i]["increment"] . "<span class='wk-red'>↑</span>"; } elseif ((float)$concept_stock_s[$i]["increment"] < 0) { echo $concept_stock_s[$i]["increment"] . "<span class='wk-green'>↓</span>"; } else { echo $concept_stock_s[$i]["increment"]; } ?> </td> <?php if ((float)$concept_stock_s[$i]["price"] == 0) { echo "<td class=\"wk-gray\">未交易</td>"; } else { echo "<td class=\"" . UtilityTools::getPriceColor((float)$concept_stock_s[$i]["mark_z_d"]) . "\">" . $concept_stock_s[$i]["price"] . "</td>"; } ?> </tr> <?php } ?> </tbody> </table> </div> <div class="col-md-7 right"> <p class="wk-hot-sub-title">搜索热度涨跌幅排行&nbsp;<i class="fa fa-question-circle-o" data-toggle="popover" data-content="当前最新与前一个小时的热度指标相比较产生的数值"></i></p> <div class="charts" id="wk-concept-search-treemap"></div> <p class="wk-hot-sub-tips"><label>●</label>方块大小表示成交量，越大的板块成交量越大</p> <p class="wk-hot-sub-tips"><label>●</label>方块颜色表示热度涨跌幅，涨跌越大，颜色越深，上涨红色，下跌绿色</p> </div> </div> <div role="tabpanel" class="tab-pane fade" id="concept-follow"> <div class="col-md-5 left"> <p class="wk-hot-sub-title">关注热度排行&nbsp;<i class="fa fa-question-circle-o" data-toggle="popover" data-content="截至当前产生的总热度量的排行"></i></p> <table class="table table-hover table-condensed table-striped wk-hot-table"> <thead> <tr> <td>序号</td> <td>股票名称</td> <td>关注热度</td> <td>热度增量</td> <td>价格</td> </tr> </thead> <tbody> <?php for ($i = 0; $i < count($concept_stock_f); $i++) { ?> <tr> <td><?php echo $i + 1 ?></td> <td><a href="stocks.php?stock=<?php echo $concept_stock_f[$i]["code"] ?>" target="_blank"><?php echo $concept_stock_f[$i]["name"] ?></a></td> <td><?php echo $concept_stock_f[$i]["value"] ?></td> <td> <?php if ((float)$concept_stock_f[$i]["increment"] > 0) { echo $concept_stock_f[$i]["increment"] . "<span class='wk-red'>↑</span>"; } elseif ((float)$concept_stock_f[$i]["increment"] < 0) { echo $concept_stock_f[$i]["increment"] . "<span class='wk-green'>↓</span>"; } else { echo $concept_stock_f[$i]["increment"]; } ?> </td> <?php if ((float)$concept_stock_f[$i]["price"] == 0) { echo "<td class=\"wk-gray\">未交易</td>"; } else { echo "<td class=\"" . UtilityTools::getPriceColor((float)$concept_stock_f[$i]["mark_z_d"]) . "\">" . $concept_stock_f[$i]["price"] . "</td>"; } ?> </tr> <?php } ?> </tbody> </table> </div> <div class="col-md-7 right"> <p class="wk-hot-sub-title">关注热度涨跌幅排行&nbsp;<i class="fa fa-question-circle-o" data-toggle="popover" data-content="当前最新与前一个小时的热度指标相比较产生的数值"></i></p> <div class="charts" id="wk-concept-follow-treemap"></div> <p class="wk-hot-sub-tips"><label>●</label>方块大小表示成交量，越大的板块成交量越大</p> <p class="wk-hot-sub-tips"><label>●</label>方块颜色表示热度涨跌幅，涨跌越大，颜色越深，上涨红色，下跌绿色</p> </div> </div> </div> </div> </div>
        <?php } ?>
    </section>
</div>
<script src="http://cdn.bootcss.com/jquery/2.2.4/jquery.min.js"></script><script src="http://cdn.bootcss.com/bootstrap/3.3.6/js/bootstrap.min.js"></script><script src="http://cdn.bootcss.com/echarts/3.1.10/echarts.min.js"></script><script src="http://cdn.bootcss.com/malihu-custom-scrollbar-plugin/3.1.3/jquery.mCustomScrollbar.concat.min.js"></script><script src="http://static.iwookong.com/plugins/typeahead/jquery.typeahead.min.js"></script><script src="static/js/all.min.js"></script><script src="static/js/common.min.js"></script><script src="static/js/Utility.min.js"></script><script> var stockcode=Utility.getQueryStringByName("stock");$(function(){var arrData={query_type:1,key:"<?php echo $stockCode?>",start_id:0,info_type_list:""};$("i[data-toggle='popover']").popover({container:"body",trigger:"hover"});$("#wk-news").mCustomScrollbar({autoHideScrollbar:true,theme:"minimal-dark",axis:"y",callbacks:{onTotalScrollOffset:150,onTotalScroll:function(){arrData.start_id=$("#wk-news .wk-news-list:last").attr("id").replace("news_","");arrData.info_type_list="1,0,0,0,0,0";common.getNews(arrData)}}});$("#wk-selfmedia").mCustomScrollbar({autoHideScrollbar:true,theme:"minimal-dark",axis:"y",callbacks:{onTotalScrollOffset:150,onTotalScroll:function(){arrData.start_id=$("#wk-selfmedia .wk-news-list:last").attr("id").replace("media_","");arrData.info_type_list="0,0,1,0,0,0";common.getSelfMedia(arrData)}}});$("#wk-newsflash").mCustomScrollbar({autoHideScrollbar:true,theme:"minimal-dark",axis:"y",callbacks:{onTotalScrollOffset:150,onTotalScroll:function(){arrData.start_id=$("#wk-newsflash .wk-news-list tr:last").attr("id").replace("fast_","");arrData.info_type_list="0,1,0,0,0,0";common.getFastNews(arrData)}}});initLineChart();initTreemap();$('a[data-toggle="tab"]').on('shown.bs.tab',function(){initTreemap()})});var viewData=[<?php echo $viewdata?>];var searchData=[<?php echo $searchdata?>];var followdata=[<?php echo $followdata?>];var stockdata_time=[<?php echo rtrim($stockdata_time,",")?>];var stockdata_line=[<?php echo rtrim($stockdata_line,",")?>];function initLineChart(){var myChart=echarts.init(document.getElementById("left-chart"));var xtime=["0:00","1:00","2:00","3:00","4:00","5:00","6:00","7:00","8:00","9:00","10:00","11:00","12:00","13:00","14:00","15:00","16:00","17:00","18:00","19:00","20:00","21:00","22:00","23:00","24：00"];myChart.setOption({color:["rgb(243, 104, 97)","rgb(76, 93, 186)","rgb(118, 172, 245)"],tooltip:{trigger:"axis",formatter:function(params){var showLabel="";showLabel+=params[0].name+"<br>";for(p in params){if(params[p].value!=0){showLabel+="<label style='color: "+params[p].color+";font-size: 18px;'>●</label> "+params[p].seriesName+":"+params[p].value+"<br>"}}return showLabel}},grid:{top:"12%",left:"3%",right:"5%",bottom:"1%",containLabel:true},legend:{left:"left",data:["查看","搜索","关注"],padding:[0,0,0,15]},xAxis:[{type:"category",boundaryGap:false,data:xtime}],yAxis:{type:"value",position:"right",scale:true,min:1},series:[{name:"查看",type:"line",smooth:true,symbolSize:function(value){return value==0?0:4},data:viewData},{name:"搜索",type:"line",smooth:true,symbolSize:function(value){return value==0?0:4},data:searchData},{name:"关注",type:"line",smooth:true,symbolSize:function(value){return value==0?0:4},data:followdata}]});window.onresize=myChart.resize}var wk_treemap_data=<?php $jsonData="[";$jsonData.="{\"key\":\"wk-industry-view-treemap\",\"value\":".json_encode($industry_stock_map_v)."},";$jsonData.="{\"key\":\"wk-industry-search-treemap\",\"value\":".json_encode($industry_stock_map_s)."},";$jsonData.="{\"key\":\"wk-industry-follow-treemap\",\"value\":".json_encode($industry_stock_map_f)."},";$jsonData.="{\"key\":\"wk-concept-view-treemap\",\"value\":".json_encode($concept_stock_map_v)."},";$jsonData.="{\"key\":\"wk-concept-search-treemap\",\"value\":".json_encode($concept_stock_map_s)."},";$jsonData.="{\"key\":\"wk-concept-follow-treemap\",\"value\":".json_encode($concept_stock_map_f)."}]";echo $jsonData;?>;function initTreemap(){for(var x in wk_treemap_data){if(Utility.timeRange("09:15","09:25")){$("#"+wk_treemap_data[x].key).html("<div class=\"wk-hotmap-no\"><img src=\"http://static.iwookong.com/imgs/i/nonews.png\"><span>自由竞价时间,暂无数据</span></div>")}else{var myChart=echarts.init(document.getElementById(""+wk_treemap_data[x].key+""));var cdata=[];for(var y in wk_treemap_data[x].value){var tname=wk_treemap_data[x].value[y].name;var tcode=wk_treemap_data[x].value[y].code;var tvalue=(parseFloat(wk_treemap_data[x].value[y].value)*100).toFixed(2);var tpricelevel=wk_treemap_data[x].value[y].price_level;var tstop=wk_treemap_data[x].value[y].stop;if(tstop==1){cdata.push("{name:\""+tname+"\\n("+tcode+")\\n"+(tvalue>0?"+":"")+tvalue+"%\"")}else{cdata.push("{name:\""+tname+"\\n("+tcode+")\\n"+(tvalue>0?"+":"")+tvalue+"%\"")}cdata.push("value:"+wk_treemap_data[x].value[y].count);cdata.push("itemStyle:{normal:{color:'"+Utility.getTreeMapColor(tpricelevel)+"'}}");if(tpricelevel==-1){cdata.push("label:{normal:{textStyle:{color:'#23a64c'}}}}")}else if(tpricelevel==1){cdata.push("label:{normal:{textStyle:{color:'#f54545'}}}}")}else{cdata.push("label:{normal:{textStyle:{color:'#fff'}}}}")}}myChart.setOption({tooltip:{formatter:"{b}"},series:[{type:'treemap',breadcrumb:{show:false},roam:false,nodeClick:false,width:"100%",height:"100%",itemStyle:{normal:{borderWidth:1}},data:eval("["+cdata.join(',')+"]")}]});window.onresize=myChart.resize}}}</script>
</body>
</html>
<?php
ob_end_flush();
?>