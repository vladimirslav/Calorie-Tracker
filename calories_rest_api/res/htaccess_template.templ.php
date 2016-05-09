<IfModule headers_module>
Header add Access-Control-Allow-Credentials "true"
Header add Access-Control-Allow-Origin "*"
Header add Access-Control-Allow-Headers "origin, x-requested-with, content-type"
Header add Access-Control-Allow-Methods "PUT, GET, POST, DELETE, OPTIONS"
</IfModule>

<ifModule mod_rewrite.c>

RewriteEngine on
RewriteBase {{execution_dir}}
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^api/(.*)$ api.php?uri=%{REQUEST_URI} [QSA,NC,L]
 
RewriteCond %{REQUEST_URI} !^{{execution_dir}}/api.*$
RewriteRule ^(.*)$ index.php [L]

</IfModule>