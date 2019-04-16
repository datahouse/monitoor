#!/usr/bin/env python

import hashlib
import os
import sys
import time
import psycopg2
from datetime import datetime, timedelta
from psycopg2 import extensions
import random
from cStringIO import StringIO

import nltk
from nltk.model import NgramModel
from nltk.probability import LidstoneProbDist

import numpy as np

sys.path.append('../backend')
from datahouse.moonitor.app import loadDatabaseConfig

#
# Load script configuration
#
DO_COMMIT = True
AVG_URLS_PER_USER = 30         # number of URLs each fake user wants
                               # to monitor on average
AVG_DOCS_PER_ALERT = 3        # how many historic fake documents to
                               # generate per alert
AVG_DAYS_BACK = 365            # how old the avg. document should be
AVG_CONTENT_WORD_LENGTH = 10   # random words per document
MOCK_SPIDER_UUID = "8365ba89-8789-4fb1-b4f1-facda1d645a3"

SQL_CHECK_DATA = """
SELECT COUNT(1) FROM mon_user
UNION ALL
SELECT COUNT(1) FROM url
UNION ALL
SELECT COUNT(1) FROM spider_job
UNION ALL
SELECT COUNT(1) FROM alert
UNION ALL
SELECT COUNT(1) FROM alert_x_url
UNION ALL
SELECT COUNT(1) FROM alert_x_url_group
UNION ALL
SELECT COUNT(1) FROM url_x_group
UNION ALL
SELECT COUNT(1) FROM spider
UNION ALL
SELECT COUNT(1) FROM spider_document
UNION ALL
SELECT COUNT(1) FROM notification;
"""

SQL_QUERY_ALERTS = """
SELECT job_id, url_id, array_agg(alert_id) AS alert_ids FROM
    (
    SELECT DISTINCT job_id, url_id, alert_id
    FROM spider_job_alert_type_cycle
    WHERE url_active AND alert_active
    ) AS x
GROUP BY job_id, url_id;
"""

SQL_RESET_SEQUENCES = [
    "mon_user_user_id_seq",
    "url_url_id_seq",
    "url_group_url_group_id_seq",
    "spider_job_job_id_seq",
    "alert_alert_id_seq",
    "spider_document_spider_document_id_seq",
    ]

def emitCopyRow(columns):
    row = [str(x) if x is not None else "\\N" for x in columns]
    return "\t".join(row) + "\n"

def appendRow(sio, columns):
    line = emitCopyRow(columns)
    sio.write(line)

def main(argv):
    with open('urls.txt', 'r') as f:
        lines = f.read().split('\n')
        urls = [line.strip() for line in lines if line.strip() != '']
    urls = list(set(urls))  # eliminate duplicates

    with open('randomnames.txt', 'r') as f:
        lines = f.read().split('\n')
        names = [line.strip() for line in lines if line.strip() != '']
    names = list(set(names))  # eliminate duplicates

    print("Loaded %d distinct urls and %d names." % (
        len(urls), len(names)))

    print("Generating distributions...")

    # For each URL, how popular it is over all users, i.e. total
    # number of users interested and monitoring the URL.
    url_factor = len(urls) * AVG_URLS_PER_USER / len(names)
    url_dist = np.random.exponential(url_factor, len(urls))
    url_dist = [int(x) if x > 0 else 1 for x in url_dist]

    # Now, randomly distribute the URLs to the users.
    url_ids_to_distribute = []
    for url_id in range(0, len(urls)):
        url_ids_to_distribute += [url_id] * url_dist[url_id]

    # For each user, create a distribution of how many URLs he has
    # entered in the system.
    user_dist = np.random.exponential(AVG_URLS_PER_USER, len(names))
    user_dist = [int(x) if x > 0 else 1 for x in user_dist]

    # Shuffle well.
    np.random.shuffle(url_ids_to_distribute)
    print("Will generate %d URLs (user_dist sum: %d, avg: %d)\n  10%%-ile: %d\n  50%%-ile: %d\n  90%%-ile: %d"
          % tuple([len(url_ids_to_distribute), sum(user_dist), np.average(user_dist)] + 
                  np.percentile(user_dist, [10, 50, 90])))

    dsn = loadDatabaseConfig()
    print("Connecting to the database...")
    conn = psycopg2.connect(dsn)
    cur = conn.cursor()

    cur.execute(SQL_CHECK_DATA);
    count = sum([row[0] for row in cur.fetchall()])
    if count > 0:
        raise Exception("Database already contains data, "
                        "refusing to populate.");

    # reset sequences
    for seq in SQL_RESET_SEQUENCES:
        cur.execute("SELECT setval('%s', 1, false);" % seq)

    # populate mon_user
    user_data = StringIO()
    for user_id in range(1, len(names) + 1):
        parts = names[user_id - 1].split(' ')
        assert(len(parts) == 2)
        email = '.'.join(parts).lower() + "@example.com"
        row = [user_id,
               email,
               '', # '5aa5a330c478658204d4753b02484cf3c2d9be3d703dc36e8d21ad8ff784d003',
               ''] #'e[(?3oMm+Xc|YHMF']
        appendRow(user_data, row)

    user_data.seek(0)
    print("Loading users...")
    cur.copy_from(user_data, 'mon_user',
                  columns=('user_id', 'user_email', 'user_password', 'user_password_salt'))

    # Distribute the url_ids among the users to populate the url table.
    url_data = StringIO()
    acl_data = StringIO()
    alert_data = StringIO()
    alert_x_url_data = StringIO()
    alert_x_url_group_data = StringIO()
    alert_x_type_cycle_data = StringIO()
    url_group_data = StringIO()
    url_x_group_data = StringIO()
    url_id = 1
    alert_id = 1
    group_id = 1
    for user_id in range(1, len(names) + 1):
        count_urls = user_dist[user_id - 1]
        if count_urls < 1:
            count_urls = 1
        username = names[user_id - 1]

        if count_urls > AVG_URLS_PER_USER:
            mode = 3
            # Five groups, url randomly distributed
            firstNormalGroupId = group_id
            parentGroupId = group_id + 5
            # Create the parent group, first.
            row = [username + " main group",
                   "Parent group of %s" % username,
                   user_id,
                   None]
            appendRow(url_group_data, row)
            # Create other groups, making the first three children of
            # the parent group.
            for i in range(0, 5):
                row = [username + " group %d" % (i+1),
                       "Group %d of %s" % (i+1, username),
                       user_id,
                       parentGroupId if i < 3 else None]
                appendRow(url_group_data, row)
            group_id += 6
        elif count_urls > AVG_URLS_PER_USER / 2:
            mode = 2
            firstNormalGroupId = group_id
            row = [username + " main group",
                   "The one group of " + username,
                   user_id,
                   None]
            appendRow(url_group_data, row)
            group_id += 1
        else:
            mode = 1

        for j in range(0, count_urls):
            # Use modulo to cycle in url_ids_to_distribute, if we
            # happened to not quite get as many.
            i = url_ids_to_distribute[(url_id - 1) % len(url_ids_to_distribute)]
            url = urls[i]
            url_title = url[7:].replace('/', ' ')
            check_frequency_id = random.randint(1, 3)

            row = [url_id, url_title, url, check_frequency_id, user_id]
            appendRow(url_data, row)

            row = [user_id, url_id, 2]
            appendRow(acl_data, row)

            # add an alert for the url..
            row = [url_title[:20] + " alert",
                   "an alert on " + url[:20],
                   user_id,
                   (random.random() > 0.2)]
            appendRow(alert_data, row)

            # ..and one or two random type_cycle(s) per alert
            if random.randint(1, 2) == 1:
                type_x_cycle_id = random.randint(1, 3)
                row = [alert_id, type_x_cycle_id]
                appendRow(alert_x_type_cycle_data, row)
            else:
                skip_id = random.randint(1, 3)
                for type_x_cycle_id in range(1, 3):
                    if type_x_cycle_id == skip_id:
                        continue
                    row = [alert_id, type_x_cycle_id]
                    appendRow(alert_x_type_cycle_data, row)

            if mode == 1 or (random.random() > 0.5):
                # simply link the url to the alert
                row = [alert_id, url_id]
                appendRow(alert_x_url_data, row)
            elif mode == 2:
                row = [url_id, firstNormalGroupId]
                appendRow(url_x_group_data, row)

                row = [alert_id, firstNormalGroupId]
                appendRow(alert_x_url_group_data, row)
            elif mode == 3:
                # Add to one of the five groups. Not ever to the
                # parent, directly.
                thisGroupId = firstNormalGroupId + random.randint(0, 4)
                row = [url_id, thisGroupId]
                appendRow(url_x_group_data, row)

            url_id += 1
            alert_id += 1

    url_data.seek(0)
    url_group_data.seek(0)
    url_x_group_data.seek(0)
    acl_data.seek(0)
    alert_data.seek(0)
    alert_x_url_data.seek(0)
    alert_x_url_group_data.seek(0)
    alert_x_type_cycle_data.seek(0)
    print("Loading urls...")
    cur.copy_from(url_data, 'url',
                  columns=('url_id', 'url_title', 'url', 'check_frequency_id',
                           'url_creator_user_id'))
    cur.copy_from(acl_data, 'access_control',
                  columns=('user_id', 'url_id', 'access_type_id'))
    print("Loading groups...")
    cur.copy_from(url_group_data, 'url_group',
                  columns=('url_group_title', 'url_group_description',
                           'url_group_creator_user_id', 'parent_url_group_id'))
    cur.copy_from(url_x_group_data, 'url_x_group',
                  columns=('url_id', 'url_group_id'))
    print("Loading alerts...")
    cur.copy_from(alert_data, 'alert',
                  columns=('alert_title', 'alert_description', 'user_id', 'alert_active'))
    cur.copy_from(alert_x_type_cycle_data, 'alert_x_type_cycle',
                  columns=('alert_id', 'type_x_cycle_id'))
    cur.copy_from(alert_x_url_data, 'alert_x_url',
                  columns=('alert_id', 'url_id'))
    cur.copy_from(alert_x_url_group_data, 'alert_x_url_group',
                  columns=('alert_id', 'url_group_id'))

    cur.execute("INSERT INTO spider (spider_uuid, spider_last_seen) VALUES (%(uuid)s, now());",
                {'uuid': MOCK_SPIDER_UUID})

    print("Initializing NLTK...")
    text = nltk.Text(nltk.corpus.brown.words())
    estimator = lambda fdist, bins: LidstoneProbDist(fdist, 0.2)
    trigram_model = NgramModel(3, text, estimator)

    print("Fetching alerts generated...")
    cur.execute(SQL_QUERY_ALERTS)
    jobList = cur.fetchall()

    print("Generating document distribution...")
    doc_dist = np.random.exponential(AVG_DOCS_PER_ALERT, len(jobList))
    doc_dist = [int(x) if x >= 1 else 1 for x in doc_dist]
    docsTotal = sum(doc_dist)
    docsCreated = 0
    print("Generating %d documents for %d jobs..."
          % (docsTotal, len(jobList)))
    tStart = datetime.now()
    for i in range(0, len(jobList)):
        if i % 10 == 9:
            doneFactor = float(docsCreated) / docsTotal
            if doneFactor > 0.0:
                tNow = datetime.now()
                tDiff = (tNow - tStart).total_seconds() / doneFactor
                eta = tStart + timedelta(seconds=tDiff)
                remaining = (eta - tNow).total_seconds() / 60.0
                eta = eta.isoformat(' ')
                print("  %02.1f%% - ETA %s - %0.1f minutes left"
                      % (doneFactor * 100.0, eta, remaining))

        jobId, urlId, alertIds = jobList[i]
        numDocs = doc_dist[i]
        tsArr = np.random.exponential(AVG_DAYS_BACK, numDocs)
        tsArr = [tStart - timedelta(days=x) for x in tsArr]
        tsArr.sort()

        # Generate some stupid random text
        lastDocId = None
        for j in range(0, numDocs):
            word_length = int(np.random.exponential(AVG_CONTENT_WORD_LENGTH))
            contents = ' '.join(trigram_model.generate(word_length))
            tSingleStart = time.time()
            cur.execute("""
                SELECT spider_store_document(%(job_id)s, %(old_doc_id)s,
                                             %(uuid)s, convert_to(%(contents)s, 'utf-8'),
                                             %(ts)s, %(alert_ids)s);
                                             """,
                {'job_id': jobId,
                 'old_doc_id': lastDocId,
                 'uuid': MOCK_SPIDER_UUID,
                 'contents': contents,
                 'ts': tsArr[j],
                 'alert_ids': alertIds})
            lastDocId = cur.fetchone()[0]
        docsCreated += numDocs

    cur.close()
    if DO_COMMIT:
        verb = "Created"
        conn.commit()
    else:
        verb = "Rolled-back"
        conn.rollback()

    assert(url_id == alert_id)
    print("%s %d urls (and alerts), %d url_groups, %d documents"
          % (verb, url_id-1, group_id-1, docsCreated))

if __name__ == "__main__":
    if not os.path.exists('urls.txt'):
        print("Please run testcrap.py, first, to generate urls.txt.")
        sys.exit(1)
    main(sys.argv)
