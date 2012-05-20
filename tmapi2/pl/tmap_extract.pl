#!/usr/bin/perl
use strict;
use warnings;
use CGI;
use FindBin::libs qw{ export base=syscommon };
use MyLibs::Common::Auth::ApiAuth;
use MyLibs::TMAP::API::AddressExtract;

# CGI開始
my $cgi = new CGI;
print $cgi->header(-type=>"application/javascript", -charset=>"utf8");

# パラメータ取得
#my $ext     = $cgi->param("ext") ne "oki" && $cgi->param("ext") ne "gaj" ? "oki" : $cgi->param("ext");
my $ext     = "oki";
my $req     = $cgi->param("content") || exit;
my $call    = $cgi->param("callback");
my $referer = $cgi->referer();
my $apikey  = $cgi->param("apikey");

# API認証開始
my $auth = MyLibs::Common::Auth::ApiAuth->new({
	"apikey" => $apikey,
	"referer" => $referer
});

# API認証失敗時のエラーハンドリング
if(!$auth->execAuthentication()){
	print $call . "({error: 'authorization failed'})";
	exit;
};

# TMAP API開始
my $tmapi = MyLibs::TMAP::API::AddressExtract->new();
$tmapi->set_query($req);
my $jsonp = $tmapi->get_lnglat($ext, $call);

# JSONP(またはJSON)出力
print $jsonp;
