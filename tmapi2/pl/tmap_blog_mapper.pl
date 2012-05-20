#!/usr/bin/perl
use strict;
use warnings;
use CGI;
use FindBin::libs qw{ export base=syscommon };
use MyLibs::TMAP::Blogeo::DB;
use MyLibs::TMAP::Blogeo::BlogMapper;

# CGI開始
my $cgi = new CGI;
print $cgi->header(-type=>"text/html", -charset=>"utf-8");

# マッピングデータ取得処理開始
my $blogeo = MyLibs::TMAP::Blogeo::BlogMapper->new();

# マッピングデータ取得
$blogeo->mapper({
	lng      => $cgi->param("lng"),
	lat      => $cgi->param("lat"),
	dist     => $cgi->param("dist"),
	num      => $cgi->param("num"),
	callback => $cgi->param("callback") || ""
});

my $json = $blogeo->get_json();

print $json;