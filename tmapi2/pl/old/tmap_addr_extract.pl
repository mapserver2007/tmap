#!/usr/bin/perl
use WebService::OkiLab::ExtractPlace;
use LWP::UserAgent;
use Unicode::Japanese;
use HTML::TokeParser::Simple;
use DBI;
use SQL::Abstract;
use SQL::Abstract::Limit;
use Config::Simple;
require 'tmap_blog_list.pl';

#print "Content-type: text/html; charset=EUC-JP\n\n";

sub extract(@){
	my ($ref, $type, $num, $cnt) = @_;
	my $max_cnt = 3; #同一ページで許可する住所数
	my $ref_list = $ref->{'url_list'};
	my $ref_addr = $ref->{'address'};
	my $ref_code = $ref->{'code'};
	my $db_state = {
		host => 'localhost',
		port => 5432,
		dbname => 'tmap_api',
		user => 'postgres',
		passwd => 'psql'
	};
	#DB Connect
	my $dbh = DBI->connect("dbi:Pg:dbname=$db_state->{'dbname'};host=$db_state->{'host'};port=$db_state->{'port'}", $db_state->{'user'}, $db_state->{'passwd'});
	my ($sql, $stmt, @bind, $sth);
	for(my $i = 0; $i < $num; $i++){
		#Register DB - tmap_blog
		my %insert_blog = (
			url => "$ref_list->[$i]->{'url'}",
			site => "$ref_list->[$i]->{'site'}",
			title => "$ref_list->[$i]->{'title'}",
			date => "$ref_list->[$i]->{'date'}"
		);
		$sql = SQL::Abstract->new;
		($stmt, @bind) = $sql->insert('tmap_blog', \%insert_blog);
		$sth = $dbh->prepare($stmt);
		my $rv = $sth->execute(@bind) or next;
		$sth->finish();
		if($rv == 1){
			#------------------------------------------------
			#print 'BLOG:'.$ref_list->[$i]->{'url'}." [OK]\n";
			#------------------------------------------------
		}
		#Extract Address & Place by OKILab.jp
		my $ua = LWP::UserAgent->new;
		my $req = HTTP::Request->new(GET => $ref_list->[$i]->{'url'});
		my $res = $ua->request($req);
		my $plain_html = $res->content;
		my $utf_str = Unicode::Japanese->new($plain_html, 'auto')->get;
		my $p = HTML::TokeParser::Simple->new(\$utf_str);
		my $strip_str = "";
		while(my $token = $p->get_token){
			next unless $token->is_text;
			$strip_str .= $token->as_is;
		}
		my $explace = WebService::OkiLab::ExtractPlace->new;
		my $result = $explace->extract($strip_str);
		my @data = $result->{'result_select'}->[0];

		#GROUP ID
		$stmt = "SELECT grp_id FROM tmap_blog ORDER BY grp_id DESC LIMIT 1 OFFSET 0";
		$sth = $dbh->prepare($stmt);
		$sth->execute;
		my $grp_id = $sth->fetchrow_arrayref->[0];
		$sth->finish();
		#Register DB - tmap_addr
		my $del_id = $grp_id;
		my $j = 0;
		my $k = 1;
		while(exists($data[0][$j])){
			if($data[0][$j]->{'type'} eq $type){
				my $addr = Unicode::Japanese->new($data[0][$j]->{'text'})->euc;
				if($addr =~ /($ref_addr)([^\d]+)(?:\xA3[\xB0-\xB9]|[\d]|一|二|三|四|五|六|七|八|九|十)/g && $addr ne $ref_addr){
					($stmt, @bind) = "INSERT INTO tmap_addr (grp_id,city,aza,address,type,geom) VALUES ($grp_id,'$ref_code->{'city'}','$ref_code->{'aza'}','$addr','$data[0][$j]->{'type'}',GeometryFromText('POINT($data[0][$j]->{'lng'} $data[0][$j]->{'lat'})', 4326))";
					$sth = $dbh->prepare($stmt);
					$sth->execute(@bind);
					$sth->finish();
					$$cnt += 1;
					#-----------------------------
					print 'ADDR:'.$addr."\n";
					#-----------------------------
					$del_id = "";
					$k++;
				}
				if($k > $max_cnt){last;}
			}
			$j++;
		}
		if($del_id){
			$stmt = "DELETE FROM tmap_blog WHERE grp_id = $del_id";
			$sth = $dbh->prepare($stmt);
			$sth->execute;
			$sth->finish();
			print 'DELETE:'.$del_id." [OK]\n";
		}
	}
	$dbh->disconnect();
}

sub get_list(){
	my ($site_num, $addr_num);
	my $list;
	#Parse Config
	my $cfg = new Config::Simple('tmap_blogeo.ini') or die "$!";
	my %Config = $cfg->vars();
	my %conf = (
		site => '',
		addr => '',
		num  => $Config{'default.NUM'},
		page => 1,
		sort => $Config{'default.SORT'}
	);
	
	if(ref($Config{'default.SITE'}) eq 'ARRAY'){
		$site_num = scalar(@{$Config{'default.SITE'}});
	}else{
		$site_num = 1;
	}
	if(ref($Config{'default.ADDR'}) eq 'ARRAY'){
		$addr_num = scalar(@{$Config{'default.ADDR'}});
	}else{
		$addr_num = 1;
	}
	for(my $i = 0; $i < $site_num; $i++){
		if($site_num > 1){
			$conf{'site'} = $Config{'default.SITE'}->[$i];
		}else{
			$conf{'site'} = $Config{'default.SITE'};
		}
		for(my $j = 0; $j < $addr_num; $j++){
			if($addr_num > 1){
				$conf{'addr'} = $Config{'default.ADDR'}->[$j];
			}else{
				$conf{'addr'} = $Config{'default.ADDR'};
			}
			#NUM件取得するまで繰り返す
			my $cnt = 0; #正常に取得できた住所数
			my $qn = 1; #検索結果数をカウント
			my $max_qn = 5;
			#my $max_qn = 100;
			for(;;){
				$conf{'page'} = $qn;
				$list = TmapBlogList::get_url_list(%conf);
				my $list_keys = @{$list->{'url_list'}};
				if($list_keys){
					extract($list, 'address', $list_keys, \$cnt);
				}else{
					print "＞＜";
				}
				if($qn >= $max_qn || $cnt >= $conf{'num'}){last};
				$qn++;
			}
		}
	}
}

get_list();
