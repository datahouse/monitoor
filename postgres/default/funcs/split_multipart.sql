SET search_path TO public, thirdparty;

-- Splits a multipart document into its parts, for use with RSS
-- documents when generating pending_change entries.
DROP FUNCTION split_multipart(in_data TEXT, in_boundary TEXT);
CREATE FUNCTION split_multipart(in_data TEXT, in_boundary TEXT)
RETURNS TABLE (
  mime_type TEXT,
  content_id TEXT,
  ts TIMESTAMP WITH TIME ZONE,
  body TEXT[]
)
RETURNS NULL ON NULL INPUT
AS $$
  part = None
  in_header = True
  for line in in_data.split('\n'):
      if line.startswith(in_boundary):
          # a message boundary, yield everything so far, start the next part
          if part is not None:
              yield [part['mime_type'], part['content_id'], part['ts'], part['body']]
          part = {
              'mime_type': 'text/plain',
              'body': [],
              'ts': None,
              'content_id': None
          }
          in_header = True
      elif in_header and part is not None:
          if line == '':
              in_header = False
          elif line.lower().startswith('content-type:'):
              part['mime_type'] = line[13:].strip()
          elif line.lower().startswith('content-id:'):
               part['content_id'] = line[11:].strip()
          elif line.lower().startswith('date:'):
              part['ts'] = line[5:].strip()
          else:
              pass # ignore other headers
      elif part is not None:
          part['body'].append(line)
      else:
          # ignore rubish content before boundary
          pass
$$ LANGUAGE plpython3u IMMUTABLE;

GRANT EXECUTE
  ON FUNCTION split_multipart(TEXT, TEXT)
  TO project_mon;
