<?php

Route::group(['namespace' => 'Api'], function () {
    Route::any('yzy/create', 'YzyController@create');
    Route::resource('yzy', 'YzyController');

    // PING检测
    Route::get('ping', 'PingController@ping');
    // iOS
    Route::post('quick/login', 'QuickiOSController@login');//账号登录
    Route::post('quick/quick', 'QuickiOSController@quick');//快速登录
    Route::post('quick/getData', 'QuickiOSController@getData');//获取数据
    Route::post('quick/setAccount', 'QuickiOSController@setAccount');//设置账号
    Route::post('quick/verifyAccount', 'QuickiOSController@verifyAccount');//验证账号
    Route::post('quick/setPassword', 'QuickiOSController@setPassword');//设置密码
    Route::post('quick/resetPassword', 'QuickiOSController@resetPassword');//重置密码
    Route::post('quick/traffic', 'QuickiOSController@getTraffic');//查询流量信息
    Route::post('quick/nodeList', 'QuickiOSController@getNodeList');//节点列表
    Route::post('quick/nodeInfo', 'QuickiOSController@getNodeInfo');//节点信息
    Route::post('quick/verifyLocal', 'QuickiOSController@verifyLocal');//验证地址
    Route::post('quick/goodsList', 'QuickiOSController@getGoodsList');//商品列表
    Route::post('quick/verifyReceipt', 'QuickiOSController@verifyReceipt');//验证收据
    Route::post('quick/createAdsOrder', 'QuickiOSController@createAdsOrder');//广告订单
    Route::post('quick/checkAdsOrder', 'QuickiOSController@checkAdsOrder');//校验广告订单

    // iOS Dev
    Route::post('quick_dev/login', 'QuickiOSDevController@login');//账号登录
    Route::post('quick_dev/quick', 'QuickiOSDevController@quick');//快速登录
    Route::post('quick_dev/getData', 'QuickiOSDevController@getData');//获取数据
    Route::post('quick_dev/setAccount', 'QuickiOSDevController@setAccount');//设置账号
    Route::post('quick_dev/verifyAccount', 'QuickiOSDevController@verifyAccount');//验证账号
    Route::post('quick_dev/setPassword', 'QuickiOSDevController@setPassword');//设置密码
    Route::post('quick_dev/resetPassword', 'QuickiOSDevController@resetPassword');//重置密码
    Route::post('quick_dev/traffic', 'QuickiOSDevController@getTraffic');//查询流量信息
    Route::post('quick_dev/nodeList', 'QuickiOSDevController@getNodeList');//节点列表
    Route::post('quick_dev/nodeInfo', 'QuickiOSDevController@getNodeInfo');//节点信息
    Route::post('quick_dev/verifyLocal', 'QuickiOSDevController@verifyLocal');//验证地址
    Route::post('quick_dev/goodsList', 'QuickiOSDevController@getGoodsList');//商品列表
    Route::post('quick_dev/verifyReceipt', 'QuickiOSDevController@verifyReceipt');//验证收据
    Route::post('quick_dev/createAdsOrder', 'QuickiOSDevController@createAdsOrder');//广告订单
    Route::post('quick_dev/checkAdsOrder', 'QuickiOSDevController@checkAdsOrder');//校验广告订单

    // Mac
    Route::post('quickmac/login', 'QuickMacController@login');//账号登录
    Route::post('quickmac/quick', 'QuickMacController@quick');//快速登录
    Route::post('quickmac/getData', 'QuickMacController@getData');//获取数据
    Route::post('quickmac/setAccount', 'QuickMacController@setAccount');//设置账号
    Route::post('quickmac/verifyAccount', 'QuickMacController@verifyAccount');//验证账号
    Route::post('quickmac/setPassword', 'QuickMacController@setPassword');//设置密码
    Route::post('quickmac/resetPassword', 'QuickMacController@resetPassword');//重置密码
    Route::post('quickmac/traffic', 'QuickMacController@getTraffic');//查询流量信息
    Route::post('quickmac/nodeList', 'QuickMacController@getNodeList');//节点列表
    Route::post('quickmac/nodeInfo', 'QuickMacController@getNodeInfo');//节点信息
//    Route::post('quickmac/goodsList', 'QuickMacController@getGoodsList');//商品列表
//    Route::post('quickmac/verifyReceipt', 'QuickMacController@verifyReceipt');//验证收据
//    Route::post('quickmac/createAdsOrder', 'QuickMacController@createAdsOrder');//广告订单
//    Route::post('quickmac/checkAdsOrder', 'QuickMacController@checkAdsOrder');//校验广告订单


    // Mac Dev

    // Win
    Route::post('quickwin/login', 'QuickWinController@login');//账号登录
    Route::post('quickwin/quick', 'QuickWinController@quick');//快速登录
    Route::post('quickwin/getData', 'QuickWinController@getData');//获取数据
    Route::post('quickwin/setAccount', 'QuickWinController@setAccount');//设置账号
    Route::post('quickwin/verifyAccount', 'QuickWinController@verifyAccount');//验证账号
    Route::post('quickwin/setPassword', 'QuickWinController@setPassword');//设置密码
    Route::post('quickwin/resetPassword', 'QuickWinController@resetPassword');//重置密码
    Route::post('quickwin/traffic', 'QuickWinController@getTraffic');//查询流量信息
    Route::post('quickwin/nodeList', 'QuickWinController@getNodeList');//节点列表
    Route::post('quickwin/nodeInfo', 'QuickWinController@getNodeInfo');//节点信息
//    Route::post('quickwin/goodsList', 'QuickWinController@getGoodsList');//商品列表
//    Route::post('quickwin/verifyReceipt', 'QuickWinController@verifyReceipt');//验证收据
//    Route::post('quickwin/createAdsOrder', 'QuickWinController@createAdsOrder');//广告订单
//    Route::post('quickwin/checkAdsOrder', 'QuickWinController@checkAdsOrder');//校验广告订单


    // Win Dev


    // Android
    Route::post('quickdroid/login', 'QuickAndroidController@login');//账号登录
    Route::post('quickdroid/quick', 'QuickAndroidController@quick');//快速登录
    Route::post('quickdroid/getData', 'QuickAndroidController@getData');//获取数据
    Route::post('quickdroid/setAccount', 'QuickAndroidController@setAccount');//设置账号
    Route::post('quickdroid/verifyAccount', 'QuickAndroidController@verifyAccount');//验证账号
    Route::post('quickdroid/setPassword', 'QuickAndroidController@setPassword');//设置密码
    Route::post('quickdroid/resetPassword', 'QuickAndroidController@resetPassword');//重置密码
    Route::post('quickdroid/traffic', 'QuickAndroidController@getTraffic');//查询流量信息
    Route::post('quickdroid/nodeList', 'QuickAndroidController@getNodeList');//节点列表
    Route::post('quickdroid/nodeInfo', 'QuickAndroidController@getNodeInfo');//节点信息
    Route::post('quickdroid/goodsList', 'QuickAndroidController@getGoodsList');//商品列表
    Route::post('quickdroid/verifyReceipt', 'QuickAndroidController@verifyReceipt');//验证收据
    Route::post('quickdroid/createAdsOrder', 'QuickAndroidController@createAdsOrder');//广告订单
    Route::post('quickdroid/checkAdsOrder', 'QuickAndroidController@checkAdsOrder');//校验广告订单

    // Android Dev


});
