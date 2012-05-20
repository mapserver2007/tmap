-- 都道府県団体コードのマスターテーブルを作成するSQL
-- 
-- 2008.01.23 Tanaka Ryuichi

-- 取得元
-- http://www.lasdec.nippon-net.ne.jp/com/addr/jyu_top.htm

-- あらかじめ以下のコマンドを実行しておく
-- createdb blogeo
-- createlang plpgsql blogeo
-- psql -d blogeo -f lwpostgis.sql

-- 都道府県コードテーブル tmap_code_city

DROP TABLE tmap_code_city;

CREATE TABLE tmap_code_city (
	code varchar(2) PRIMARY KEY,
	name varchar(8) NOT NULL
);

GRANT ALL ON tmap_code_city TO postgres;

CREATE INDEX code_city ON tmap_code_city (code);

-- 市区町村コードテーブル tmap_code_aza

DROP TABLE tmap_code_aza;

CREATE TABLE tmap_code_aza (
	code varchar(6) PRIMARY KEY,
	name varchar(20) NOT NULL
);

GRANT ALL ON tmap_code_aza TO postgres;

CREATE INDEX code_aza ON tmap_code_aza (code);

-- 住所テーブル tmap_addr

DROP TABLE tmap_addr;

CREATE TABLE tmap_addr (
	id serial PRIMARY KEY,
	grp_id int NOT NULL,
	city varchar(2) NOT NULL,
	aza varchar(6) NOT NULL,
	address text NOT NULL
);

GRANT ALL ON tmap_addr TO postgres;

SELECT AddGeometryColumn('blogeo', 'tmap_addr', 'geom', 4326, 'POINT', 2);

CREATE INDEX addr ON tmap_addr (grp_id);

CREATE INDEX tmap_addr_geom on tmap_addr USING GIST ( geom GIST_GEOMETRY_OPS);

-- ブログテーブル tmap_blog

DROP TABLE tmap_blog;

CREATE TABLE tmap_blog (
	grp_id serial PRIMARY KEY,
	url text unique NOT NULL,
	site text NOT NULL,
	title text NOT NULL,
	date timestamp NOT NULL
);

GRANT ALL ON tmap_blog TO postgres;

CREATE INDEX blog ON tmap_blog (grp_id);

-- 重要語テーブル extract_terms

-- DROP TABLE extract_terms;

-- CREATE TABLE extract_terms (
-- 	id serial PRIMARY KEY,
-- 	grp_id int NOT NULL,
-- 	term1 varchar(20),
-- 	term2 varchar(20),
-- 	term3 varchar(20),
-- 	term4 varchar(20),
-- 	term5 varchar(20),
-- 	frequency double
-- );

-- GRANT ALL ON extract_terms TO postgres;

-- CREATE INDEX terms ON extract_terms (grp_id);
