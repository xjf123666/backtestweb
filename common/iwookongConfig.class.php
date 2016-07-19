<?php

/**
 * Class iwookongConfig 配置文件
 */
class iwookongConfig
{
    public static $usercookie = "Uj8$!mhy";
    //正式版地址
    //222.73.34.104
    //public static $requireUrl = "http://222.73.34.104/cgi-bin/wookong/";//接口前缀地址
    public static $requireUrl = "http://222.73.34.104/cgi-bin/wookong130/";//接口前缀地址
    //public static $requireUrl = "http://222.73.34.92/cgi-bin/test_wookong/";

    /**
     * 获取接口地址前缀
     * @return string
     */
    protected static function getRequireUrl()
    {
        return self::$requireUrl;
    }

    /**
     * 获取策略详情页头部信息
     * @return string
     */
    public static function getStrategyBrief()
    {
        return self::$requireUrl . "strategy/1/strategy_brief.fcgi";
    }

    /**
     * 获取调仓记录
     * @return string
     */
    public static function getStockTransfer()
    {
        return self::$requireUrl . "strategy/1/stock_transfer.fcgi";
    }

    /**获取策略行业股票百分比
     * @return string
     */
    public static function getIndusPercent()
    {
        return self::$requireUrl . "strategy/1/indus_percent.fcgi";
    }

    /**
     * 获取单只股票
     * @return string
     */
    public static function getSingleStock()
    {
        return self::$requireUrl . "stock/1/single_stock.fcgi";
    }
}