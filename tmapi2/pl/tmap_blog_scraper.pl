#!/usr/bin/perl
use strict;
use warnings;
use FindBin::libs qw{ export base=syscommon };
use MyLibs::TMAP::Blogeo::DB;
use MyLibs::TMAP::Blogeo::BlogRequest;
use MyLibs::TMAP::Blogeo::BlogScraper;
use MyLibs::TMAP::Blogeo::BlogRegister;

# ----------------------ここからパラメータ設定----------------------

# 取得を許可するブログドメイン
my $domains = [
	"http://blogs.yahoo.co.jp",
	"http://blog.livedoor.jp",
	"http://d.hatena.ne.jp/",
	"http://plaza.rakuten.co.jp/"
];

# 取得を許可する住所(市区町村まで指定)
my $addrs = [
	"東京都港区"
];

# 1ブログドメインあたりの取得数
my $num = 10;

#　ソート方法
my $sort = "fit";

# TMAP API設定
my $tmapi_conf = {
	base_uri => 'http://summer-lights.dyndns.ws/tmap/tmapi2/pl/tmap_extract.pl',
	ext      => 'oki',
	apikey   => '7a110b05d6eaadd4b609431640f13c2b4a9479e90e117d2e078f2c88f6c634b5'
};

# ----------------------ここまでパラメータ設定----------------------

# Yahooブログ検索のURL生成
my $blogeo = MyLibs::TMAP::Blogeo::BlogRequest->new();

# リクエストURLを生成しセットする
$blogeo->setURLQuery({
	domain => $domains,
	addr   => $addrs,
	num    => $num,
	sort   => $sort
});

# リクエストURL・住所・住所コードのリストを取得
my $request_list = $blogeo->getURLQuery();

# Yahooブログ検索の結果をスクレイプ
my $scraper = MyLibs::TMAP::Blogeo::BlogScraper->new();

# スクレイプ、経緯度取得
$scraper->scrape($request_list, $tmapi_conf);

# ブログリストを取得する
my $blog_list = $scraper->get_blog_list();

# DBに登録
my $register = MyLibs::TMAP::Blogeo::BlogRegister->new();

# 登録処理
$register->register($blog_list);
