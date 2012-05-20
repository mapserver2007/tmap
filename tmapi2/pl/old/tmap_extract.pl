#!/usr/bin/perl
use strict;
use warnings;
use Data::Dumper;
use CGI;
use Geography::AddressExtract::Japan;
use WebService::OkiLab::ExtractPlace;
use Encode;
use Encode::Guess qw(euc-jp utf8 shiftjis 7bit-jis ascii);
use LWP::UserAgent;
use HTML::TokeParser::Simple;
use JSON;

# Const Value
use constant EXTRACTER => {"OKI"=>"oki", "GAJ"=>"gaj"};

my $cgi = new CGI;
#print $cgi->header(-type=>"text/javascript+json",-charset=>"euc-jp");
print $cgi->header(-type=>"text/html", -charset=>"euc-jp");

# GET Parameter
my $ext = $cgi->param("ext") ne "oki" && $cgi->param("ext") ne "gaj" ? "oki" : $cgi->param("ext");
my $req = $cgi->param("uri") || $cgi->param("para") || exit;
#my $call = !($cgi->param("callback") =~ /[^\d|^\w]/) ? $cgi->param("callback") : "";
my $call = $cgi->param("callback");
$req =~ s/summer-lights.dyndns.ws/localhost/;

# Get Query for Extract
my $query = $req =~ /s?https?:\/\/[-_.!~*'()a-zA-Z0-9;\/?:\@&=+\$,%#]+/g 
	? sub {
		my $ua = LWP::UserAgent->new;
		my $res = $ua->request(HTTP::Request->new(GET => $req));
		my $str = decode(guess_encoding($res->content)->name, $res->content);
		my $p = HTML::TokeParser::Simple->new(\$str);
		my $strip_str = "";
		while(my $token = $p->get_token){
			next unless $token->is_text;
			$strip_str .= $token->as_is;
		}
		return $strip_str;
	} 
	: sub {
		return decode(guess_encoding($req)->name, $req);
	};

# Extract Address
my $json = sub {
	my ($text, $mode) = @_;
	my $extract = $mode eq EXTRACTER->{"OKI"} ? 
		sub {
			$text = encode("utf8", $text);  #UTF-8 Encode
			my $explace = WebService::OkiLab::ExtractPlace->new;
			my $result = $explace->extract($text);
			my @data = $result->{'result_select'}->[0];
			my $obj = [];
			my $i = 0;
			while(exists($data[0][$i])){
				if($data[0][$i]->{'type'} eq "address"){
					my $addr = $data[0][$i]->{'text'};
					$addr = decode('utf8', $addr);  #UTF-8 Decode
					#$addr = encode('euc-jp', $addr);  #UTF-8 Decode
					my $addr_data = {
						addr => $addr,
						lng => $data[0][$i]->{'lng'},
						lat => $data[0][$i]->{'lat'}
					};
					push @$obj, $addr_data;
				}
				$i++;
			}
			#return $call."(".to_json($obj).")";
			return $call ? $call."(".to_json($obj).")" : to_json($obj);
		} : 
		sub {
			my $result = Geography::AddressExtract::Japan->extract($text);
			my $obj = [];
			my @data = map { $_->{"city"} . $_->{"aza"} . $_->{"number"}; }@{$result};
			foreach(@data){ push(@$obj, $_); }
			return $call != "" ? $call."(".to_json($obj).")" : to_json($obj);
		};
	return $extract->();
} if ($ext eq EXTRACTER->{"OKI"} || $ext eq EXTRACTER->{"GAJ"});


my $text = $query->() || die("Get Query is Failed");
my $res = $json->($text, $ext);
print $res;
