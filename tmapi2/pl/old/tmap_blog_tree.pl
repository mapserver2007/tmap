#!/usr/bin/perl
use DBI;
use JSON;

print "Content-type: text/html; charset=EUC-JP\n\n";

sub get_blog($){
	my $limit = shift;
	my $blog_list = [];
	my ($city_ref, $aza_ref);
	my $db_state = {
		host => 'localhost',
		port => 5432,
		dbname => 'tmap_api',
		user => 'postgres',
		passwd => 'psql'
	};
	#DB Connect
	my $dbh = DBI->connect("dbi:Pg:dbname=$db_state->{'dbname'};host=$db_state->{'host'};port=$db_state->{'port'}", $db_state->{'user'}, $db_state->{'passwd'});
	my ($stmt, $sth);
	
	#CITY DATA
	$stmt = "SELECT tmap_addr.city, tmap_code_city.name FROM tmap_addr, tmap_code_city ";
	$stmt.= "WHERE tmap_addr.city = tmap_code_city.code GROUP BY tmap_addr.city, tmap_code_city.name";
	$sth = $dbh->prepare($stmt);
	$sth->execute;
	while(my $city_ref = $sth->fetchrow_arrayref){
		my ($city_code, $city_name) = @$city_ref;
		my $obj = {
			name => $city_name,
			code => $city_code,
			aza => ''
		};
		push @$blog_list, $obj;
	}
	$sth->finish();
	
	#AZA DATA
	my $city_key = scalar(@$blog_list);
	for(my $i = 0; $i < $city_key; $i++){
		my ($stmt2, $sth2);
		my $aza_list = [];
		$stmt2 = "SELECT tmap_addr.aza, tmap_code_aza.name FROM tmap_addr, tmap_code_aza ";
		$stmt2.= "WHERE tmap_addr.aza LIKE ? AND tmap_addr.aza = tmap_code_aza.code GROUP BY tmap_addr.aza, tmap_code_aza.name ";
		$stmt2.= "ORDER BY tmap_addr.aza";
		$sth2 = $dbh->prepare($stmt2);
		$sth2->bind_param(1, $blog_list->[$i]->{'code'}.'%');
		$sth2->execute;
		while(my $aza_ref = $sth2->fetchrow_arrayref){
			my ($aza_code, $aza_name) = @$aza_ref;
			my $addr_list = [];
			my ($stmt3, $sth3);
			$stmt3 = "SELECT aza, address, x(geom), y(geom), url, site, title, date FROM tmap_addr, tmap_blog ";
			$stmt3.= "WHERE tmap_addr.grp_id = tmap_blog.grp_id AND tmap_addr.aza = ? ORDER BY address, date DESC ";
			$stmt3.= "LIMIT ? OFFSET 0";
			$sth3 = $dbh->prepare($stmt3);
			$sth3->bind_param(1, $aza_code);
			$sth3->bind_param(2, $limit);
			$sth3->execute;
			while(my $addr_ref = $sth3->fetchrow_arrayref){
				my ($id, $address, $lng, $lat, $url, $site, $title, $date) = @$addr_ref;
				my $addr = {
					id => $id,
					address => $address,
					lng => $lng,
					lat => $lat,
					url => $url,
					site => $site,
					title => $title,
					date => $date
				};
				push @$addr_list, $addr;
			}
			$sth3->finish;
			my $obj = {
				name => $aza_name,
				code => $aza_code,
				addr => $addr_list
			};
			push @$aza_list, $obj;
		}
		$blog_list->[$i]->{'aza'} = $aza_list;
		$sth2->finish;
	}
	$dbh->disconnect();
	return $blog_list;
}

sub get_query($){
	my @query = split(/&/, shift);
	my %hash = ();
	foreach(@query){
		my ($k, $v) = split(/=/, $_);
		$hash{$k} = $v;
	}
	return %hash;
}

sub get_num($){
	my $n = int(shift);
	my $defn = 10;
	my $maxn = 99;
	return $n =~ /[\d]/ && $n <= $maxn ? $n : $defn;
}

my %q = get_query($ENV{'QUERY_STRING'});
my $n = get_num($q{'n'});

print $q{'callback'}.'('.to_json(get_blog($n)).')';
