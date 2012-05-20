package TmapBlogList;
use Web::Scraper;
use URI;

require 'tmap_blog_query.pl';

sub url_scrape($){
	my $url = shift;
	my $text = scraper {
		process '//div[@class="r-details yjSt"]/div/em', 'url[]' => 'TEXT';
		process '//div[@class="r-details yjSt"]/a[1]', 'site[]' => 'TEXT';
		process '//a[@class="r-title yjM"]', 'title[]' => 'TEXT';
		process '//em[@class="r-date yjS"]', 'date[]' => 'TEXT';
		result 'url', 'site', 'title', 'date';
	}->scrape(URI->new($url));
	return $text;
}

sub get_url_list(@){
	my $hash = TmapBlogQuery::get_query_string(@_);
	my $ref = url_scrape($hash->{'src_url'});
	my $key = scalar(@{$ref->{'url'}});
	my $url_obj = [];
	for(my $i = 0; $i < $key; $i++){	
		my @d = split(/[^\d]+/, $ref->{'date'}->[$i]);
		my @sd = ();
		foreach(@d){
			if($_ < 10){
				push @sd, '0'.$_;
			}else{
				push @sd, $_;
			}
		}
		my $obj = {
			url => "http://".$ref->{'url'}->[$i],
			site => Unicode::Japanese->new(TmapBlogQuery::urldecode($ref->{'site'}->[$i]))->euc,
			title => Unicode::Japanese->new(TmapBlogQuery::urldecode($ref->{'title'}->[$i]))->euc,
			date => $sd[0].'-'.$sd[1].'-'.$sd[2].' '.$sd[3].':'.$sd[4]
		};
		push @$url_obj, $obj;
	}
	$hash->{'url_list'} = $url_obj;
	return $hash;
}

1;