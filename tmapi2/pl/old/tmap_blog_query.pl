package TmapBlogQuery;
use Encode;
use DBI;

sub get_search_uri($;@){
	my $k;
	my $q = "";
	my $url = shift;
	my %hash = @_;
	foreach $k (keys %hash){
		$q .= $k."=".$hash{$k};
		$q .= "&";
	}
	chop($q);
	return $url.$q;
}

sub re {
	my $re =<<RE;
(?-xism:(?:(?:(?:[富岡]|和歌)山|(?:[広徳]|鹿児)島|(?:[石香]|神奈)
川|山[口形梨]|福[井岡島]|[佐滋]賀 |宮[城崎]|愛[媛知]|長[崎野]|三重|
兵庫|千葉|埼玉|奈良|岐阜|岩手|島根|新潟|栃木|沖縄|熊本|秋田|群馬|茨城|
青森|静岡|高知|鳥取)県|大(?:分県|阪府)|京都府|北海道|東京都))
RE
    $re =~ s/\n//g;
    $re;
}

sub urlencode($){
	my $str = shift;
	$str =~ s/([^\w\.])/'%'.unpack("H2", $1)/eg;
	$str =~ tr/ /+/;
	return $str;
}

sub urldecode($) {
	my $str = shift;
	$str =~ tr/+/ /;
	$str =~ s/%([0-9A-Fa-f][0-9A-Fa-f])/pack('H2', $1)/eg;
	return $str;
}

sub get_addr_code($){
	my $addr = shift;
	my $re = re;
	my ($city, $aza, @code);
	my ($sql, $sth);
	if($addr =~ /$re/g){
		$aza = $addr;
		$city = $addr;
		$aza =~ s/($re)//;
		$city =~ s/$aza//;
		my $db_state = {
			host => 'localhost',
			port => 5432,
			dbname => 'tmap_api',
			user => 'postgres',
			passwd => 'psql'
		};
		#DB Connect
		my $dbh = DBI->connect("dbi:Pg:dbname=$db_state->{'dbname'};host=$db_state->{'host'};port=$db_state->{'port'}", $db_state->{'user'}, $db_state->{'passwd'});
		#CITY CODE
		my $sqlstmt_city = "SELECT code FROM tmap_code_city WHERE name = '$city'";
		$sth = $dbh->prepare($sqlstmt_city);
		$sth->execute;
		#$code{'city'} = $sth->fetchrow_arrayref->[0];
		push @code, $sth->fetchrow_arrayref->[0];
		#AZA CODE
		my $sqlstmt_aza = "SELECT code FROM tmap_code_aza WHERE name = '$aza' AND code LIKE '$code[0]%'";
		$sth = $dbh->prepare($sqlstmt_aza);
		$sth->execute;
		#$code{'aza'} = $sth->fetchrow_arrayref->[0];
		push @code, $sth->fetchrow_arrayref->[0];
		$sth->finish();
		$dbh->disconnect();
	}
	return @code;
}

sub get_query_string(@){
	my %list = @_;
	my $src_url = 'http://blog-search.yahoo.co.jp/search?ei=UTF-8&';
	#City and Aza Code Check
	my @code = get_addr_code($list{'addr'}) or die "Can't acquire code";
	my %hash = (
		site => $list{'site'},
		addr => $list{'addr'},
		num  => $list{'num'},
		page => $list{'page'},
		sort => $list{'sort'}
	);
	my $max_num = 30;
	my $def_num = 10;
	my %result = ();
	#URLクエリを取得
	#ドメインが定義されている場合
	if(defined($hash{'site'})){
		if($hash{'site'} =~ /s?https?:\/\/[-_.!~*'()a-zA-Z0-9;\/?:\@&=+\$,%#]+/g){
			my $enc_utf_site = $hash{'site'};
			$result{'site'} = "site:".urlencode($enc_utf_site);
		}else{
			die("Invalid URL:".$hash{'site'});
		}
	}
	#ドメインが定義されていない場合
	else{
		$result{'site'} = "all";
	}
	#住所クエリを取得
	if(defined($hash{'addr'})){
		my $enc_utf_addr = Unicode::Japanese->new(urldecode($hash{'addr'}), 'euc')->get;
		$result{'addr'} = urlencode($enc_utf_addr);
	}
	#表示件数(default:10)
	if(defined($hash{'num'}) && $hash{'num'} =~ /[\w]+/g){
		if($hash{'num'} > $max_num){
			$result{'num'} = $def_num;
		}else{
			$result{'num'} = $hash{'num'};
		}
	}else{
		$result{'num'} = $def_num;
	}
	#ページ数
	if(defined($hash{'page'}) && $hash{'page'} =~ /[\w]+/g){
		$result{'page'} = $hash{'page'};
	}
	#ソート条件(日付or適合度)(default:fit)
	$result{'sort'} = 'gd';
	if(defined($hash{'sort'})){
		if($hash{'sort'} eq 'fit'){
			$hash{'sort'} =~ s/fit/gd/g;
			$result{'sort'} = $hash{'sort'}
		}elsif($hash{'sort'} eq 'day'){
			$hash{'sort'} =~ s/day/dd/g;
			$result{'sort'} = $hash{'sort'}
		}
	}
	#URL生成
	if(keys(%result) == 5){
		#クエリを連結
		my %q = ();
		$q{'p'} = $result{'site'} eq "all" ? $result{'addr'} : $result{'site'}."+".$result{'addr'};
		$q{'n'} = $result{'num'};
		$q{'b'} = $result{'page'};
		$q{'so'} = $result{'sort'};
		my $data = {
			src_url => get_search_uri($src_url, %q),
			address => $hash{'addr'},
			code => {
				city => $code[0],
				aza => $code[1]
			},
			url_list => ""
		};
		return $data;
	}
}

1;