ALTER TABLE change
  DROP CONSTRAINT change_has_delta_check,
  ADD CONSTRAINT change_has_delta_check CHECK (
    delta IS NOT NULL OR
    (old_doc_id IS NOT NULL AND new_doc_id IS NOT NULL)
  );
