# currently this is not a complete example config, sorry
```
 	location ~ ^/.*?/deleted/(src|thumb)/.*$ {
		return 404;
	}
	location ~ ^/(.*?)/res/([0-9]+)\.html$ {
		if_modified_since off; 
		add_header Last-Modified "";
		try_files $uri /getthread.php?board=$1&thread=$2;
	}
	location ~ ^/(.*?)/([0-9]+)\.html$ {
		if_modified_since off; 
		add_header Last-Modified "";
		try_files $uri /getpage.php?board=$1&page=$2;
	}
	location ~ ^/(.*?)/$ {
		if_modified_since off;
		add_header Last-Modified "";
		try_files $uri/index.html /getpage.php?board=$1&page=0;
	}
	location ~ ^/(.*?)/index\.html$ {
		if_modified_since off;
		add_header Last-Modified "";
		try_files $uri /getpage.php?board=$1&page=0;
	}
	location ~ ^/(.*?)/catalog\.(html|json)$ {
		if_modified_since off; 
		add_header Last-Modified "";
		try_files $uri /getpage.php?board=$1&catalog=1&format=$2;
	}
```