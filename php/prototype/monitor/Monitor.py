from conf import *
from lib import group
from tempfile import mkstemp
import datetime
import os
import hashlib
import shutil
import MySQLdb
import codecs
import html2text

class URLHandler:
    @staticmethod
    def getList():
        db = DB()
        return db.getSiteList()

class MonitoredSite:
    def __init__(self,response):
        self.response = response
        self.url = response.url
        if (response.request.meta.has_key('redirect_urls') and len(response.request.meta['redirect_urls']) > 0):
            self.url = response.request.meta['redirect_urls'][0]
        if (response.request.url.find('#') != -1):
            self.url = response.request.url
        self.now = datetime.datetime.now()
    def paths(self):
        db = DB()
        return db.getPaths(self.url)
    def process(self):
        for p in self.paths():
            item = Item()
            str = []
            for el in self.response.xpath(p.xpath).extract():
                str.append(self._html2text(el,p.width).strip())
            item.write(str,p.fmt)
            item.close()
            if p.version.hash != item.hash():
                p.addVersion(item.hash(),self.now)
                ist = ItemStorage(item)
                ist.store()
            p.addHit(self.now)
    def _html2text(self,html,width):
        h = html2text.HTML2Text()
        h.body_width = width
        return h.handle(html)

class Path:
    def __init__(self, id, xpath, fmt, width):
        self.id = id
        self.xpath = xpath
        self.fmt = fmt
        self.width = width
        db = DB()
        self.version = db.getCurrentVersion(self.id)
    def getCurrentVersion(self):
        return self.version
    def addVersion(self,hash,timestamp):
        db = DB()
        self.version = db.addVersion(self.id, Version(None,hash,timestamp))
    def addHit(self,timestamp):
        db = DB()
        db.addHit(self.version.id,timestamp)

class Version:
    def __init__(self, id, hash, timestamp):
        self.id = id
        self.hash = hash
        self.timestamp = timestamp

class Item:
    def __init__(self):
        self._hash = None
        fd, self.path = mkstemp()
        self.file = codecs.open(self.path, 'wb','utf-8-sig')
        os.close(fd)
    def write(self, content, fmt):
        if fmt is None:
            fmt = '%s'
        fmt = fmt + "\n"
        for line in group(content,fmt.count('%s')):
            self.file.write(fmt % line)
    def close(self):
        self.file.close()
    def hash(self):
        if not self._hash:
            self._hash = hashlib.md5(open(self.path).read()).hexdigest()
        return self._hash
    def __del__(self):
        if os.path.isfile(self.path):
            os.remove(self.path)

class ItemStorage:
    def __init__(self,item):
        self.item = item
    def store(self):
        dest = self._hash2path(self.item.hash())
        shutil.move(self.item.path,dest)
    def _hash2path(self,h):
        path = ''.join([
            STORAGE_PATH,'/',
            '/'.join(list(h[0:STORAGE_NESTING])),'/',h[STORAGE_NESTING:],
        ])
        return path

class Singleton(type):
    _instances = {}
    def __call__(cls, *args, **kwargs):
        if cls not in cls._instances:
            cls._instances[cls] = super(Singleton, cls).__call__(*args, **kwargs)
        return cls._instances[cls]

class DB:
    __metaclass__ = Singleton
    def __init__(self):
        self.conn = MySQLdb.connect(
            host=DATABASE['HOST'],
            user=DATABASE['USER'],
            passwd=DATABASE['PASSWORD'],
            db=DATABASE['DB'],
        )
        cur = self.conn.cursor()
        cur.execute("SET NAMES utf8")
        self.conn.commit()
        cur.close()
    def getSiteList(self):
        cur = self.conn.cursor()
        cur.execute("SELECT site_uri FROM site WHERE site_active = TRUE")
        row = cur.fetchall()
        cur.close()
        list = []
        for r in row:
            list.append(r[0])
        return list
    def getPaths(self,url):
        cur = self.conn.cursor()
        cur.execute("SELECT path_id, path_xpath, path_format, path_body_width FROM path JOIN site USING(site_id) WHERE site_uri = '%s'" % url)
        row = cur.fetchall()
        cur.close()
        list = []
        for r in row:
            list.append(Path(r[0],r[1],r[2],r[3]))
        return list
    def getCurrentVersion(self,id):
        cur = self.conn.cursor()
        cur.execute("SELECT version_id, version_hash, version_timestamp FROM version WHERE path_id = %d ORDER BY version_timestamp DESC LIMIT 1" % id)
        row = cur.fetchone()
        cur.close()
        if not row:
            return Version(None,None,None)
        return Version(row[0],row[1],row[2])
    def addVersion(self,id,version):
        cur = self.conn.cursor()
        cur.execute("INSERT INTO version (path_id, version_hash, version_timestamp) VALUES (%d,'%s','%s')" % (id,version.hash,version.timestamp))
        self.conn.commit()
        new = cur.lastrowid
        cur.close()
        return Version(new,version.hash,version.timestamp)
    def addHit(self,id,timestamp):
        cur = self.conn.cursor()
        cur.execute("INSERT INTO hit (version_id, hit_timestamp) VALUES (%d,'%s')" % (id,timestamp))
        self.conn.commit()
        cur.close()
    def __del__(self):
        self.conn.close()

