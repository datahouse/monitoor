# Monitoor

The Monitoor from Datahouse AG is a web application that enables fully automated detection of changes on web pages, which can be individually selected by the user. By defining keywords and thanks to further intelligent filters, only relevant changes are reported. Thus, important information can be efficiently and systematically collected, graphically processed and analyzed. Thanks to its easy-to-use interface, the web application is simple to operate and can be used without any special expertise. Structured customer and competitor monitoring are just two examples for the use of the software. The application consists of a Webfrontend, a backend and an API.

## Installation & Usage

### API and Frontend Webserver

Simply let your apache serve the following two directories:

    js/main/app # for the frontend
    php/api     # for the API

You might need to create and/or adjust a .htaccess file in both of these directories. Test the API with something like (this should yield a JSON encoded “Forbidden” error, as you’re not logged in):

    http://localhost/mon_api/api.php?request=v1/alert/listing/de    // w/o rewriting
    http://localhost/mon_api/v1/alert/listing/de                    // with rewriting in place

### Frontend

The frontend uses some Javascript magic.

    sudo apt-get install nodejs npm
    sudo npm cache clean -f
    sudo npm install -g n
    sudo n 6.9.4

After this installation you should have a node version 0.12 or newer and npm 2.9 or newer. Check with *node -v* and *npm -v*.

Install bower

    sudo npm install -g bower

Go to the project (e.g. /project-MON/js/main):

``` {.force-pandoc-to-ouput-fenced-code-block}
npm install
```

for developing tools and

``` {.force-pandoc-to-ouput-fenced-code-block}
bower install
```

(if it asks which angular version to use select 1.3.15)

if it fails check git and use https instead git:// change it with

``` {.force-pandoc-to-ouput-fenced-code-block}
git config --global url."https://".insteadOf git://
```

(wup firewall blocks git protocol)
another fail can be locks in your home/.npm folder delete them.

build project

``` {.force-pandoc-to-ouput-fenced-code-block}
ant build
```

or

``` {.force-pandoc-to-ouput-fenced-code-block}
ant cleanBuild
```

### Backend

The following Debian packages are required:

python-twisted-core python-scrapy python-psycopg2 python-psutil html2text python-html2text xsltproc python-pdfminer

An additional python module ‘txpostgres’ is used, however, that one is not currently packaged for Debian, so it’s simply copied into our repository (as of v1.4.0 from https://github.com/wulczer/txpostgres/releases).

Please ensure to configure the database to connect to in *\$(project-MON-checkout)/.db.conf.json* (alternatively in \$(project-MON-checkout)/python/backend, as used for stand-alone backend deployments). A UUID for the spider instance will be generated automatically upon the first startup.

To run the monitor backend:

```
cd $(project-MON-checkout)/python/backend
twistd -y monitoor.tac
```

### API / Job

After fetching the latest code simply run ant to build the API (php/api) and the Job Component (php/job).
The following symlinks are needed:

    ln -s .%.htaccess php/api/.htaccess
    ln -s .db.%.conf.json php/api/conf/.db.conf.json
    ln -s .db.%.conf.json php/job/conf/.db.conf.json
    ln -s .%.env.conf.json php/api/conf/.env.conf.json
    ln -s .%.env.conf.json php/job/conf/.env.conf.json


## Contributing
We welcome contributions from the community! If you find a bug, have a feature request, or would like to contribute code, please create an issue or submit a pull request.

Before submitting a pull request, please make sure to run the tests and ensure that your code meets the project's coding standards.

## License
Monitoor is released under the BSD 3-Clause license. See LICENSE for more information.

