﻿#!/usr/local/bin/ruby
require 'net/http'
Net::HTTP.version_1_2
Net::HTTP.start('www.ruby-lang.org', 80) { |http|
  response = http.get('/ja/') # ディレクトリを指定
  response.body
}
