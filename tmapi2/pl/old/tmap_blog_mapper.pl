#!/usr/bin/perl
use DBI;
use JSON;

print "Content-type: text/html; charset=EUC-JP\n\n";

sub mapper(@){
	my $geom = [];
	my %q = validate(@_);
	if(scalar(keys(%q) != 4)){ return; }
	my $db_state = {
		host => 'localhost',
		port => 5432,
		dbname => 'tmap_api',
		user => 'postgres',
		passwd => 'psql'
	};
	#DB Connect
	my $dbh = DBI->connect("dbi:Pg:dbname=$db_state->{'dbname'};host=$db_state->{'host'};port=$db_state->{'port'}", $db_state->{'user'}, $db_state->{'passwd'});
	my ($stmt, $sth, @bind);
	$stmt = "SELECT address, x(geom), y(geom), distance_sphere(geom, GeometryFromText(? ,4326)) AS distance, site, title, url ";
	$stmt.= "FROM tmap_addr, tmap_blog WHERE distance_sphere(geom, GeometryFromText(? ,4326)) < ? AND tmap_addr.grp_id = tmap_blog.grp_id ";
	$stmt.= "ORDER BY distance LIMIT ? OFFSET 0";
	$sth = $dbh->prepare($stmt);
	my $gft = 'POINT('.$q{'lng'}.' '.$q{'lat'}.')';
	@bind = ($gft, $gft, $q{'dist'}, $q{'n'});
	$sth->execute(@bind);
	while(my $g = $sth->fetchrow_arrayref){
		my ($addr, $lng, $lat, $dist, $site, $title, $url) = @$g;
		my $obj = {
			address => $addr,
			lng => $lng,
			lat => $lat,
			distance => int($dist),
			site => $site,
			title => $title,
			url => $url
		};
		push @$geom, $obj;
	}
	$sth->finish;
	$dbh->disconnect();
	return $geom;
}

sub validate(@){
	my %q = @_;
	my %valid_q;
	my $max_num = 100;
	my $def_num = 10;
	my $max_dist = 100000;
	my $def_dist = 10000;
	#Longitude
	if($q{'lng'} > 120 && $q{'lng'} < 150){
		$valid_q{'lng'} = $q{'lng'};
	}
	#Latitude
	if($q{'lat'} > 23 && $q{'lat'} < 47){
		$valid_q{'lat'} = $q{'lat'};
	}
	#data num
	if($q{'n'} < $max_num && $q{'n'} > 0){
		$valid_q{'n'} = int($q{'n'});
	}else{
		$valid_q{'n'} = $def_num;
	}
	#Distance
	if($q{'dist'} <= $max_dist && $q{'dist'} > 0){
		$valid_q{'dist'} = int($q{'dist'});
	}else{
		$valid_q{'dist'} = $def_dist;
	}
	return %valid_q;
}

sub get_query($){
	my @query = split(/&/, shift);
	#my $call = shift;
	my %hash = ();
	foreach(@query){
		my ($k, $v) = split(/=/, $_);
		$hash{$k} = $v;
	}
	return %hash;
}
my %q = get_query($ENV{'QUERY_STRING'});
my $ref = mapper(%q);
print $q{'callback'}.'('.to_json($ref).')';
