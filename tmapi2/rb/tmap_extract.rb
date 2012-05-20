#!/usr/local/bin/ruby
require 'rubygems'
require 'httpclient'
require 'cgi'
require 'kconv'

# Const Value
CONST = {"OKI"=>"oki", "GAJ"=>"gaj"}

# Extract Lambda Method
extract = lambda { |addr| 
  url = 'http://api.locosticker.jp/v1/extract_place/'
  hc = HTTPClient.new
  response = hc.get_content(url, {'text' => addr.toutf8})
  puts response

}

# GET Parameter
cgi = CGI.new()
puts cgi.header({"type" => "text/html", "charset" => "EUC-JP"})
ext = cgi['ext'] != "oki" && cgi['ext'] != "gaj" ? "oki" : cgi['ext']
call = cgi['callback'] =~ /[^\d|^\w]/ ? "" : cgi['callback']
req = (cgi['uri'].sub(/summer-lights.dyndns.ws/, "localhost") if cgi['uri'] != "") || (cgi['para'] if cgi['para'] != "") || exit

extract.call("東京都")

puts "おおお"



puts req if req =~ /s?https?:\/\/[-_.!~*'()a-zA-Z0-9;\/?:\@&=+\$,%#]+/