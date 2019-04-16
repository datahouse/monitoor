CREATE TRIGGER url_insert_trigger BEFORE INSERT ON url
  FOR EACH ROW EXECUTE PROCEDURE url_insert_trigger_func();

CREATE TRIGGER url_update_trigger BEFORE UPDATE ON url
  FOR EACH ROW EXECUTE PROCEDURE url_update_trigger_func();

CREATE TRIGGER url_delete_trigger BEFORE DELETE ON url
  FOR EACH ROW EXECUTE PROCEDURE url_delete_trigger_func();

